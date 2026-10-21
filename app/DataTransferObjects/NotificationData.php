<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\NotificationType;
use App\Exceptions\ValidationException;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Notification Data DTO
 *
 * Normalizes recipients to:
 *   - Collection<User>  (specific users)
 *   - null              (all users / broadcast)
 */
readonly class NotificationData
{
    /**
     * @var Collection<User>|null
     */
    public ?Collection $users;

    public array $payload;

    /**
     * @param  User|Collection|array<int|User>|null  $users
     */
    public function __construct(
        User|Collection|array|null $users,
        public string $titleEn,
        public string $titleAr,
        public string $bodyEn,
        public string $bodyAr,
        public NotificationType $type,
        array $payload = [],
        public bool $isAllUsers = false,
        public bool $isTopic = false,
        public ?string $topic = null,
    ) {
        $this->users = $this->normalizeUsers($users);

        $this->payload = array_merge([
            'type' => $this->type->value,
            'target_id' => null,
        ], $payload);
    }

    /**
     * Normalize recipients to Collection<User> or null
     */
    private function normalizeUsers(
        User|Collection|array|null $input
    ): ?Collection {
        // Broadcast case
        if ($this->isAllUsers || $input === null) {
            return null;
        }

        // Already a collection
        if ($input instanceof Collection) {
            return $input->values();
        }

        // Single model instance (User)
        if ($input instanceof Model) {
            return collect([$input]);
        }

        // Array of IDs or Users
        if (is_array($input)) {
            $ids = collect($input)
                ->map(function ($item) {
                    if ($item instanceof User) {
                        return $item->id;
                    }

                    if (is_array($item)) {
                        return $item['id'] ?? null;
                    }

                    return $item;
                })
                ->filter()
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                return collect();
            }

            return User::whereIn('id', $ids)->get();
        }

        throw new ValidationException('Invalid users type provided.');
    }

    public function isBroadcast(): bool
    {
        return $this->users === null;
    }

    public function hasUsers(): bool
    {
        return $this->users !== null && $this->users->isNotEmpty();
    }

    public function userCount(): int
    {
        return $this->users?->count() ?? 0;
    }

    public function firstUser(): ?User
    {
        return $this->users?->first();
    }

    public function getPayloadValue(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $default;
    }

    public function hasPayloadKey(string $key): bool
    {
        return array_key_exists($key, $this->payload);
    }

    public function getUsers(): ?Collection
    {
        return $this->users;
    }
}
