<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Facades\Client\PaymentFacade;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CheckPendingPayments extends Command
{
    protected $signature = 'payments:check-pending';

    protected $description = 'Verify pending payments whose webhook never arrived';

    public function handle(): int
    {
        $payments = Payment::query()
            ->where('status', PaymentStatus::Pending)
            // الدفعة الـ pending دايمًا على أوردر لسه pending (التأكيد بييجي من الدفع الناجح، والإلغاء ممنوع وفيه دفعة pending)
            ->whereHas('order', fn ($query) => $query->where('status', OrderStatus::Pending))
            // بنستنى شوية الأول عشان نسيب فرصة للويبهوك يوصل لوحده
            ->where('created_at', '<', now()->subMinutes((int) config('payment.pending.check_after_minutes')))
            ->get();

        foreach ($payments as $payment) {
            try {
                $payment = PaymentFacade::checkPending($payment);

                $this->line("{$payment->reference}: {$payment->status->value}");
            } catch (Throwable $e) {
                // دفعة واحدة بايظة (مثلًا الجيتواي اتشال من الكونفيج) ماتوقفش الباقي
                Log::error('Pending payment check failed.', ['reference' => $payment->reference, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Checked {$payments->count()} pending payment(s).");

        return self::SUCCESS;
    }
}
