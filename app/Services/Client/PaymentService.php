<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\GatewayTimeoutException;
use App\Exceptions\ValidationException;
use App\Http\Traits\Paginatable;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentGateway;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class PaymentService
{
    use Paginatable;

    public function index(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for($this->userPayments(), $request)
            ->allowedFilters(AllowedFilter::exact('status'), AllowedFilter::exact('order_id'))
            ->defaultSort('-id')
            ->paginate($this->perPage($request))
            ->appends($request->query());
    }

    public function show(int $id): Payment
    {
        return $this->userPayments()->findOrFail($id);
    }

    public function process(int $orderId, string $method): Payment
    {
        $payment = DB::transaction(function () use ($orderId, $method) {
            // بنقفل الأوردر: لو طلبين دفع وصلوا في نفس اللحظة، التاني يستنى الأول يخلص
            // ويلاقي الدفعة اللي اتعملت فيرجع 409، بدل ما العميل يتخصم منه مرتين
            $order = auth('api')->user()->orders()->lockForUpdate()->findOrFail($orderId);

            $this->validateOrderCanBePaid($order);

            return $this->createPayment($order, $method);
        });

        $result = $this->chargeGateway($payment);

        return $this->updatePaymentStatus($payment, $result);
    }

    public function webhook(string $gateway, string $rawBody): Payment
    {
        $this->validateGatewayExists($gateway);

        $data = $this->validateWebhookPayload($rawBody);

        return DB::transaction(function () use ($gateway, $data) {
            $payment = $this->findLockedPayment($gateway, $data['reference']);

            $this->validateWebhookAmount($payment, $data);

            $this->applyStatus($payment, PaymentStatus::from($data['status']), $data['gateway_reference'] ?? null);

            return $payment;
        });
    }

    /**
     * Called by payments:check-pending for a payment whose webhook never arrived.
     */
    public function checkPending(Payment $payment): Payment
    {
        // بنسأل الجيتواي بس (verify) ومابنخصمش تاني أبدًا، وإلا العميل ممكن يتخصم مرتين
        $result = $this->verifyGateway($payment);

        $payment = $this->updatePaymentStatus($payment, $result);

        // لو لسه مفيش رد بعد مدة طويلة بنسيبها pending ونسجّل عشان حد يراجعها بإيده
        if ($payment->status === PaymentStatus::Pending
            && $payment->created_at->lt(now()->subHours((int) config('payment.pending.alert_after_hours')))) {
            Log::warning('Payment still pending, needs manual review.', ['reference' => $payment->reference]);
        }

        return $payment;
    }

    // ─── Payment Flow ───

    private function createPayment(Order $order, string $method): Payment
    {
        return $order->payments()->create([
            'reference' => (string) Str::ulid(),
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'method' => $method,
            'status' => PaymentStatus::Pending,
        ]);
    }

    /**
     * @return array{status: PaymentStatus, gateway_reference: ?string}
     */
    private function chargeGateway(Payment $payment): array
    {
        // بنكلم الجيتواي برا الـ transaction عشان مانفضلش قافلين الأوردر طول ما الطلب رايح جاي على الشبكة
        return $this->pendingOnTimeout($payment, fn () => $this->gateway($payment->method)->charge($payment));
    }

    /**
     * @return array{status: PaymentStatus, gateway_reference: ?string}
     */
    private function verifyGateway(Payment $payment): array
    {
        return $this->pendingOnTimeout($payment, fn () => $this->gateway($payment->method)->verify($payment));
    }

    private function pendingOnTimeout(Payment $payment, callable $call): array
    {
        try {
            return $call();
        } catch (GatewayTimeoutException) {
            // التايم أوت مش معناه إن الدفع فشل: ممكن الفلوس تكون اتخصمت فعلًا.
            // بنسيبها pending (مش failed) ومانعيدش الخصم، والويبهوك هو اللي يحسم
            Log::warning('Payment gateway timeout, payment left pending.', ['reference' => $payment->reference]);

            return ['status' => PaymentStatus::Pending, 'gateway_reference' => null];
        }
    }

    private function updatePaymentStatus(Payment $payment, array $result): Payment
    {
        return DB::transaction(function () use ($payment, $result) {
            // بنقفل الدفعة لأن الويبهوك ممكن يوصل في نفس اللحظة، واللي يمسك القفل الأول بس هو اللي يغيّر الحالة
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            $this->applyStatus($payment, $result['status'], $result['gateway_reference']);

            return $payment;
        });
    }

    private function findLockedPayment(string $gateway, string $reference): Payment
    {
        // نفس القفل اللي في process(): الويبهوك والرد بتاع الجيتواي مايغيروش نفس الدفعة في نفس الوقت
        $payment = Payment::query()
            ->where('method', $gateway)
            ->where('reference', $reference)
            ->lockForUpdate()
            ->first();

        // 404 مش 200: يمكن الويبهوك سبق حفظ الدفعة، فالجيتواي يعيد المحاولة بعدين
        if (! $payment) {
            throw new ValidationException(__('messages.webhook.payment_not_found'), status: 404);
        }

        return $payment;
    }

    // ─── Checks ───

    private function validateOrderCanBePaid(Order $order): void
    {
        // الأوردر بيتأكد بالدفع الناجح، فالمؤكد يعني مدفوع
        if ($order->paid_at !== null || $order->status === OrderStatus::Confirmed) {
            throw new ValidationException(__('messages.payment.order_already_paid'), status: 409);
        }

        if ($order->status !== OrderStatus::Pending) {
            throw new ValidationException(__('messages.payment.order_not_payable'));
        }

        if ($order->payments()->whereIn('status', PaymentStatus::active())->exists()) {
            throw new ValidationException(__('messages.payment.already_active'), status: 409);
        }
    }

    private function validateGatewayExists(string $gateway): void
    {
        if (! config("payment.gateways.{$gateway}.driver")) {
            throw new ValidationException(__('messages.webhook.gateway_not_found'), status: 404);
        }
    }

    private function validateWebhookPayload(string $rawBody): array
    {
        $payload = json_decode($rawBody, true);

        return Validator::validate(is_array($payload) ? $payload : [], [
            'reference' => ['required', 'string'],
            'status' => ['required', 'in:successful,failed'],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'gateway_reference' => ['nullable', 'string'],
        ], [
            'status.in' => __('messages.webhook.invalid_status'),
        ]);
    }

    private function validateWebhookAmount(Payment $payment, array $data): void
    {
        $amount = number_format((float) $data['amount'], 2, '.', '');

        // الفلوس اللي الجيتواي بيقول عليها لازم تطابق الدفعة، وإلا مانغيرش حاجة ونسجّل عشان المراجعة
        if ($amount !== (string) $payment->amount || strtoupper($data['currency']) !== $payment->currency) {
            Log::warning('Webhook amount/currency mismatch.', [
                'reference' => $payment->reference,
                'expected' => [(string) $payment->amount, $payment->currency],
                'received' => [$amount, $data['currency']],
            ]);

            throw new ValidationException(__('messages.webhook.amount_mismatch'));
        }
    }

    /**
     * The caller must hold the payment row lock.
     */
    private function applyStatus(Payment $payment, PaymentStatus $to, ?string $gatewayReference): void
    {
        // الحالة بتتغير بس لو الانتقال مسموح: ويبهوك مكرر أو متأخر (failed بعد successful) بيعدّي من غير ما يغيّر حاجة
        if (! $payment->status->canTransitionTo($to)) {
            return;
        }

        $payment->update([
            'status' => $to,
            'gateway_reference' => $payment->gateway_reference ?? $gatewayReference,
        ]);

        // الدفع الناجح هو اللي بيأكد الأوردر
        if ($to === PaymentStatus::Successful) {
            $payment->order()->update(['status' => OrderStatus::Confirmed, 'paid_at' => now()]);
        }
    }

    /**
     * Adding a gateway = one class + one config entry.
     */
    private function gateway(string $method): PaymentGateway
    {
        return app()->make(config("payment.gateways.{$method}.driver"));
    }

    private function userPayments()
    {
        return Payment::query()->whereHas('order', fn ($query) => $query->where('user_id', auth('api')->id()));
    }
}
