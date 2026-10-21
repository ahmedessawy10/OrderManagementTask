<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class FcmService
{
    private ?string $projectId;

    private string $url;

    private ?string $accessToken = null;

    const PRIORITY_HIGH = 'high';

    const PRIORITY_NORMAL = 'normal';

    const CONCURRENT_LIMIT = 20;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id');
        $this->url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    }

    /**
     * Check if FCM is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->projectId) && file_exists($this->getCredentialsPath());
    }

    /**
     * Store device token for user
     */
    public function storeToken(int $tokenableId, string $token, string $tokenableType = User::class): DeviceToken
    {
        return DeviceToken::updateOrCreate(
            ['tokenable_id' => $tokenableId, 'tokenable_type' => $tokenableType, 'device_token' => $token],
            ['tokenable_id' => $tokenableId, 'tokenable_type' => $tokenableType, 'device_token' => $token]
        );
    }

    /**
     * Remove device token
     * If token is provided, removes only that specific token
     * Otherwise removes all tokens for the given user
     */
    public function removeToken(int $tokenableId, ?string $token = null, string $tokenableType = User::class): bool
    {
        $query = DeviceToken::where('tokenable_id', $tokenableId)
            ->where('tokenable_type', $tokenableType);

        if ($token) {
            $query->where('device_token', $token);
        }

        return $query->delete() > 0;
    }

    /**
     * Remove a specific invalid token (used internally when FCM reports token as invalid)
     */
    private function removeInvalidToken(string $token): bool
    {
        return DeviceToken::where('device_token', $token)->delete() > 0;
    }

    /**
     * Send notification to a single token
     */
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = [],
        string $priority = self::PRIORITY_HIGH,
    ): array {
        if (! $this->isConfigured()) {
            return ['success' => false, 'error' => __('messages.fcm.not_configured')];
        }

        $message = $this->buildMessage($token, $title, $body, $data, $priority);

        return $this->send($message);
    }

    /**
     * Send a silent (data-only) push to all of a user's devices.
     * No visible alert is shown; Android gets high-priority data delivery
     * and iOS gets a background content-available push.
     */
    public function sendSilentToUser(
        int $userId,
        array $data = [],
        string $tokenableType = User::class,
    ): array {
        $tokens = DeviceToken::where('tokenable_id', $userId)->where('tokenable_type', $tokenableType)->pluck('device_token')->toArray();

        if (empty($tokens)) {
            Log::warning('FCM silent: aborted, user has no device tokens', [
                'user_id' => $userId,
                'tokenable_type' => $tokenableType,
                'data' => $data,
            ]);

            return ['success_count' => 0, 'failure_count' => 0, 'responses' => []];
        }

        if (! $this->isConfigured()) {
            Log::error('FCM silent: aborted, FCM is not configured', [
                'user_id' => $userId,
                'token_count' => count($tokens),
                'project_id' => $this->projectId,
                'credentials_path' => $this->getCredentialsPath(),
                'credentials_exists' => file_exists($this->getCredentialsPath()),
            ]);

            return ['success_count' => 0, 'failure_count' => count($tokens), 'responses' => []];
        }

        Log::info('FCM silent: sending', [
            'user_id' => $userId,
            'token_count' => count($tokens),
            'tokens' => array_map(fn (string $token) => $this->maskToken($token), $tokens),
            'data' => $data,
        ]);

        $messages = array_map(fn (string $token) => [
            'token' => $token,
            'payload' => $this->buildSilentMessage($token, $data),
        ], $tokens);

        $result = $this->sendConcurrentBatch($messages);

        Log::info('FCM silent: finished', [
            'user_id' => $userId,
            'success_count' => $result['success_count'],
            'failure_count' => $result['failure_count'],
            'responses' => $result['responses'],
        ]);

        return $result;
    }

    /**
     * Build a silent (data-only) FCM message: no `notification` block.
     */
    private function buildSilentMessage(string $token, array $data = []): array
    {
        return [
            'message' => [
                'token' => $token,
                'data' => $this->normalizeData($data),
                'android' => ['priority' => self::PRIORITY_HIGH],
                'apns' => [
                    'headers' => [
                        'apns-push-type' => 'background',
                        'apns-priority' => '5',
                    ],
                    'payload' => [
                        'aps' => [
                            'content-available' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Send notification to user (all their devices)
     */
    public function sendToUser(
        int $userId,
        string $title,
        string $body,
        array $data = [],
        string $priority = self::PRIORITY_HIGH,
        string $tokenableType = User::class,
    ): array {
        $tokens = DeviceToken::where('tokenable_id', $userId)->where('tokenable_type', $tokenableType)->pluck('device_token')->toArray();

        return $this->sendToTokens($tokens, $title, $body, $data, $priority);
    }

    /**
     * Send notification to multiple tokens (concurrent)
     */
    public function sendToTokens(
        array $tokens,
        string $title,
        string $body,
        array $data = [],
        string $priority = self::PRIORITY_HIGH,
    ): array {
        if (empty($tokens)) {
            Log::warning('FCM: aborted, no device tokens to send to', [
                'title' => $title,
                'data' => $data,
            ]);

            return ['success_count' => 0, 'failure_count' => 0, 'responses' => []];
        }

        if (! $this->isConfigured()) {
            Log::error('FCM: aborted, FCM is not configured', [
                'token_count' => count($tokens),
                'project_id' => $this->projectId,
                'credentials_path' => $this->getCredentialsPath(),
                'credentials_exists' => file_exists($this->getCredentialsPath()),
            ]);

            return ['success_count' => 0, 'failure_count' => count($tokens), 'responses' => []];
        }

        // Single token - use direct send
        if (count($tokens) === 1) {
            $response = $this->sendToToken($tokens[0], $title, $body, $data, $priority);

            return [
                'success_count' => $response['success'] ? 1 : 0,
                'failure_count' => $response['success'] ? 0 : 1,
                'responses' => [[
                    'token' => $this->maskToken($tokens[0]),
                    'success' => $response['success'],
                    'error' => $response['error'] ?? null,
                ]],
            ];
        }

        // Multiple tokens - concurrent batch send
        $messages = array_map(fn (string $token) => [
            'token' => $token,
            'payload' => $this->buildMessage($token, $title, $body, $data, $priority),
        ], $tokens);

        return $this->sendConcurrentBatch($messages);
    }

    /**
     * Send notification to multiple users grouped by locale
     *
     * @param  array<int>  $userIds
     * @param  array<int, string>  $userLocales  Map of user_id => locale ('en'|'ar')
     */
    public function sendToUsers(
        array $userIds,
        array $userLocales,
        string $titleEn,
        string $titleAr,
        string $bodyEn,
        string $bodyAr,
        array $data = [],
        string $priority = self::PRIORITY_HIGH,
        string $tokenableType = User::class,
    ): array {
        $results = ['success_count' => 0, 'failure_count' => 0, 'invalid_tokens' => []];

        if (empty($userIds) || ! $this->isConfigured()) {
            return $results;
        }

        // ONE query for ALL device tokens
        $allTokens = DeviceToken::whereIn('tokenable_id', $userIds)
            ->where('tokenable_type', $tokenableType)
            ->select('tokenable_id as user_id', 'device_token')
            ->get();

        if ($allTokens->isEmpty()) {
            return $results;
        }

        // Group tokens by locale
        $tokensByLocale = ['en' => [], 'ar' => []];
        foreach ($allTokens as $deviceToken) {
            if (empty($deviceToken->device_token)) {
                continue;
            }

            $locale = $userLocales[$deviceToken->user_id] ?? 'en';
            $key = $locale === 'ar' ? 'ar' : 'en';
            $tokensByLocale[$key][] = $deviceToken->device_token;
        }

        // Send per locale group
        $localeConfig = [
            'en' => ['title' => $titleEn, 'body' => $bodyEn],
            'ar' => ['title' => $titleAr, 'body' => $bodyAr],
        ];

        foreach ($localeConfig as $locale => $config) {
            $tokens = $tokensByLocale[$locale];

            if (empty($tokens)) {
                continue;
            }

            $batchResult = $this->sendToTokens($tokens, $config['title'], $config['body'], $data, $priority);

            $results['success_count'] += $batchResult['success_count'];
            $results['failure_count'] += $batchResult['failure_count'];
        }

        Log::info('FCM batch send completed', [
            'user_count' => count($userIds),
            'token_count' => $allTokens->count(),
            'success_count' => $results['success_count'],
            'failure_count' => $results['failure_count'],
        ]);

        return $results;
    }

    /**
     * Send pre-built messages with different title/body per token (concurrent)
     *
     * @param  array<int, array{token: string, title: string, body: string, data?: array, priority?: string}>  $messages
     */
    public function sendBatchMessages(array $messages): array
    {
        if (empty($messages)) {
            return ['success_count' => 0, 'failure_count' => 0, 'responses' => []];
        }

        if (! $this->isConfigured()) {
            return ['success_count' => 0, 'failure_count' => count($messages), 'responses' => []];
        }

        $prepared = array_map(fn (array $msg) => [
            'token' => $msg['token'],
            'payload' => $this->buildMessage(
                $msg['token'],
                $msg['title'],
                $msg['body'],
                $msg['data'] ?? [],
                $msg['priority'] ?? self::PRIORITY_HIGH,
            ),
        ], $messages);

        return $this->sendConcurrentBatch($prepared);
    }

    /**
     * Send multiple FCM messages concurrently using Http::pool()
     * Processes in batches of CONCURRENT_LIMIT to avoid overwhelming the server
     *
     * @param  array<int, array{token: string, payload: array}>  $messages
     */
    private function sendConcurrentBatch(array $messages): array
    {
        $results = [
            'success_count' => 0,
            'failure_count' => 0,
            'responses' => [],
        ];

        try {
            $accessToken = $this->getAccessToken();
        } catch (\Exception $e) {
            Log::error('FCM batch: Failed to get access token', ['error' => $e->getMessage()]);
            $results['failure_count'] = count($messages);

            return $results;
        }

        Log::info('FCM: Starting concurrent batch send', [
            'total_messages' => count($messages),
            'batch_size' => self::CONCURRENT_LIMIT,
        ]);

        foreach (array_chunk($messages, self::CONCURRENT_LIMIT) as $batch) {
            $batch = array_values($batch);

            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn (int $i) => $pool->as((string) $i)
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$accessToken,
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(30)
                    ->post($this->url, $batch[$i]['payload']),
                array_keys($batch)
            ));

            foreach ($batch as $i => $msg) {
                $token = $msg['token'];
                $response = $responses[(string) $i] ?? null;

                if ($response === null || $response instanceof \Throwable) {
                    $results['failure_count']++;
                    $errorMsg = $response instanceof \Throwable ? $response->getMessage() : 'No response';

                    Log::error('FCM: Batch item failed', [
                        'token' => $this->maskToken($token),
                        'error' => $errorMsg,
                    ]);

                    $results['responses'][] = [
                        'token' => $this->maskToken($token),
                        'success' => false,
                        'error' => $errorMsg,
                    ];

                    continue;
                }

                if ($response->successful()) {
                    $results['success_count']++;
                    $results['responses'][] = [
                        'token' => $this->maskToken($token),
                        'success' => true,
                        'error' => null,
                    ];
                } else {
                    $results['failure_count']++;
                    $error = $response->json();
                    $errorCode = $error['error']['code'] ?? $response->status();
                    $errorMessage = $error['error']['message'] ?? __('messages.unknown_error');

                    Log::error('FCM: Batch item failed', [
                        'token' => $this->maskToken($token),
                        'error_code' => $errorCode,
                        'error_message' => $errorMessage,
                        'http_status' => $response->status(),
                    ]);

                    if ($errorCode === 'UNREGISTERED' || $errorCode === 404) {
                        $this->removeInvalidToken($token);
                    }

                    $this->handleFcmError($errorCode, $errorMessage, $msg['payload']);

                    $results['responses'][] = [
                        'token' => $this->maskToken($token),
                        'success' => false,
                        'error' => $errorMessage,
                    ];
                }
            }
        }

        Log::info('FCM: Concurrent batch send completed', [
            'success_count' => $results['success_count'],
            'failure_count' => $results['failure_count'],
        ]);

        return $results;
    }

    /**
     * Send notification to a topic
     */
    public function sendToTopic(
        string $topic,
        string $title,
        string $body,
        array $data = [],
        string $priority = self::PRIORITY_HIGH,
    ): array {
        if (! $this->isConfigured()) {
            return ['success' => false, 'error' => __('messages.fcm.not_configured')];
        }

        $imageUrl = $this->extractImageUrl($data);

        $notification = ['title' => $title, 'body' => $body];
        $android = ['priority' => $priority];
        $apns = [
            'headers' => [
                'apns-priority' => $priority === self::PRIORITY_HIGH ? '10' : '5',
                'apns-push-type' => 'alert',
            ],
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                    'mutable-content' => 1,
                ],
            ],
        ];

        if ($imageUrl) {
            $notification['image'] = $imageUrl;
            $android['notification'] = ['image' => $imageUrl];
            $apns['fcm_options'] = ['image' => $imageUrl];
        }

        $message = [
            'message' => [
                'topic' => $topic,
                'notification' => $notification,
                'data' => $this->normalizeData($data),
                'android' => $android,
                'apns' => $apns,
            ],
        ];

        return $this->send($message);
    }

    /**
     * Build FCM message structure
     */
    private function buildMessage(
        string $token,
        string $title,
        string $body,
        array $data = [],
        string $priority = self::PRIORITY_HIGH,
    ): array {
        $imageUrl = $this->extractImageUrl($data);

        $notification = ['title' => $title, 'body' => $body];
        $android = ['priority' => $priority];
        $apns = [
            'headers' => [
                'apns-priority' => $priority === self::PRIORITY_HIGH ? '10' : '5',
                'apns-push-type' => 'alert',
            ],
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                    'mutable-content' => 1,
                ],
            ],
        ];

        if ($imageUrl) {
            $notification['image'] = $imageUrl;
            $android['notification'] = ['image' => $imageUrl];
            $apns['fcm_options'] = ['image' => $imageUrl];
        }

        return [
            'message' => [
                'token' => $token,
                'notification' => $notification,
                'data' => $this->normalizeData($data),
                'android' => $android,
                'apns' => $apns,
            ],
        ];
    }

    /**
     * Extract image URL from data array (accepts 'image_url' or 'image' key)
     */
    private function extractImageUrl(array $data): ?string
    {
        foreach (['image_url', 'image'] as $key) {
            if (! empty($data[$key]) && is_string($data[$key])) {
                return $data[$key];
            }
        }

        return null;
    }

    /**
     * Send the FCM request
     */
    private function send(array $message): array
    {
        try {
            $token = $this->getAccessToken();

            $target = $message['message']['token'] ?? $message['message']['topic'] ?? 'unknown';
            $targetType = isset($message['message']['topic']) ? 'topic' : 'token';

            Log::info('FCM: Sending notification', [
                'target_type' => $targetType,
                'target' => $targetType === 'token' ? $this->maskToken($target) : $target,
                'title' => $message['message']['notification']['title'] ?? null,
                'body' => $message['message']['notification']['body'] ?? null,
                'data' => $message['message']['data'] ?? [],
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($this->url, $message);

            if ($response->successful()) {
                Log::info('FCM: Notification sent successfully', [
                    'target_type' => $targetType,
                    'target' => $targetType === 'token' ? $this->maskToken($target) : $target,
                    'message_id' => $response->json('name'),
                ]);

                return [
                    'success' => true,
                    'message_id' => $response->json('name'),
                    'response' => $response->json(),
                ];
            }

            $error = $response->json();
            $errorCode = $error['error']['code'] ?? $response->status();
            $errorMessage = $error['error']['message'] ?? __('messages.unknown_error');

            Log::error('FCM: Notification failed', [
                'target_type' => $targetType,
                'target' => $targetType === 'token' ? $this->maskToken($target) : $target,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'http_status' => $response->status(),
            ]);

            // Remove invalid token from database
            if ($errorCode === 'UNREGISTERED' || $errorCode === 404) {
                $messageToken = $message['message']['token'] ?? '';
                if ($messageToken) {
                    $this->removeInvalidToken($messageToken);
                }
            }

            $this->handleFcmError($errorCode, $errorMessage, $message);

            return [
                'success' => false,
                'error_code' => $errorCode,
                'error' => $errorMessage,
                'details' => $error['error']['details'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('FCM send failed', [
                'error' => $e->getMessage(),
                'message' => $message,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle FCM-specific errors
     */
    private function handleFcmError(int|string $code, string $message, array $payload): void
    {
        switch ($code) {
            case 404:
            case 'UNREGISTERED':
                Log::warning('FCM token is unregistered/invalid', [
                    'token' => $this->maskToken($payload['message']['token'] ?? ''),
                ]);
                break;

            case 429:
                Log::warning('FCM rate limit exceeded');
                break;

            case 503:
                Log::warning('FCM service temporarily unavailable');
                break;
        }
    }

    /**
     * Get OAuth2 access token with caching
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        // Cache token for 55 minutes (tokens expire in 60 minutes)
        $this->accessToken = Cache::remember('fcm_access_token', 55 * 60, function () {
            return $this->generateAccessToken();
        });

        return $this->accessToken;
    }

    /**
     * Generate new OAuth2 access token
     */
    private function generateAccessToken(): string
    {
        $keyFilePath = $this->getCredentialsPath();

        if (! file_exists($keyFilePath)) {
            throw new \RuntimeException(__('messages.fcm.credentials_file_not_found'));
        }

        $keyData = json_decode(file_get_contents($keyFilePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(__('messages.fcm.invalid_json_credentials'));
        }

        if (! isset($keyData['client_email'], $keyData['private_key'])) {
            throw new \RuntimeException(__('messages.fcm.missing_required_credentials'));
        }

        $jwt = $this->createJwt($keyData);

        $response = Http::asForm()
            ->timeout(30)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException(__('messages.fcm.oauth_token_request_failed', ['error' => $response->body()]));
        }

        $accessToken = $response->json('access_token');

        if (! $accessToken) {
            throw new \RuntimeException(__('messages.fcm.no_access_token_in_response'));
        }

        return $accessToken;
    }

    /**
     * Create JWT for OAuth2 authentication
     */
    private function createJwt(array $keyData): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $now = time();
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $keyData['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ]));

        $signatureInput = $header.'.'.$claims;

        if (! openssl_sign($signatureInput, $signature, $keyData['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException(__('messages.fcm.failed_to_sign_jwt'));
        }

        return $signatureInput.'.'.$this->base64UrlEncode($signature);
    }

    /**
     * Get credentials file path
     */
    private function getCredentialsPath(): string
    {
        return config('services.fcm.credentials');
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Normalize data array - all values must be strings for FCM
     */
    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = json_encode($value);
            } elseif (is_bool($value)) {
                $normalized[$key] = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $normalized[$key] = '';
            } else {
                $normalized[$key] = (string) $value;
            }
        }

        return $normalized;
    }

    /**
     * Mask token for logging (security)
     */
    private function maskToken(string $token): string
    {
        if (strlen($token) <= 20) {
            return '***';
        }

        return substr($token, 0, 10).'...'.substr($token, -10);
    }

    /**
     * Clear cached access token
     */
    public function clearTokenCache(): void
    {
        Cache::forget('fcm_access_token');
        $this->accessToken = null;
    }

    /**
     * Validate a Firebase ID token (sent by mobile clients after Google Sign-In)
     * Returns decoded user info: uid, email, name, picture
     *
     * @throws \RuntimeException on invalid/expired token
     */
    public function validateFirebaseToken(string $idToken): array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new \RuntimeException(__('messages.auth.invalid_google_token'));
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $header = json_decode($this->base64UrlDecode($headerB64), true);
        if (! isset($header['kid'])) {
            throw new \RuntimeException(__('messages.auth.invalid_google_token'));
        }

        $keys = $this->fetchGooglePublicKeys();
        if (! isset($keys[$header['kid']])) {
            throw new \RuntimeException(__('messages.auth.invalid_google_token'));
        }

        $signedData = $headerB64.'.'.$payloadB64;
        $signature = $this->base64UrlDecode($signatureB64);
        $publicKey = openssl_pkey_get_public($keys[$header['kid']]);

        $verified = openssl_verify($signedData, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new \RuntimeException(__('messages.auth.invalid_google_token'));
        }

        $payload = json_decode($this->base64UrlDecode($payloadB64), true);
        $now = time();

        if (($payload['iss'] ?? '') !== "https://securetoken.google.com/{$this->projectId}") {
            throw new \RuntimeException(__('messages.auth.invalid_google_token'));
        }
        if (($payload['aud'] ?? '') !== $this->projectId) {
            throw new \RuntimeException(__('messages.auth.invalid_google_token'));
        }
        if (($payload['exp'] ?? 0) <= $now) {
            throw new \RuntimeException(__('messages.auth.google_token_expired'));
        }
        if (($payload['iat'] ?? PHP_INT_MAX) > $now) {
            throw new \RuntimeException(__('messages.auth.invalid_google_token'));
        }

        return [
            'uid' => $payload['sub'] ?? '',
            'email' => $payload['email'] ?? '',
            'name' => $payload['name'] ?? '',
            'picture' => $payload['picture'] ?? '',
        ];
    }

    /**
     * Fetch and cache Google's public x509 certificates for Firebase ID token verification.
     * Returns array of kid => PEM public key string.
     */
    private function fetchGooglePublicKeys(): array
    {
        return Cache::remember('firebase_google_public_keys', 3600, function () {
            $response = Http::timeout(10)
                ->get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');

            if ($response->failed()) {
                throw new \RuntimeException(__('messages.auth.google_keys_fetch_failed'));
            }

            // Response is { kid: x509_cert_pem, ... } — extract public key from each cert
            $keys = [];
            foreach ($response->json() as $kid => $cert) {
                $pubKey = openssl_pkey_get_public($cert);
                if ($pubKey !== false) {
                    $details = openssl_pkey_get_details($pubKey);
                    $keys[$kid] = $details['key'];
                }
            }

            return $keys;
        });
    }

    /**
     * Base64 URL decode (inverse of base64UrlEncode)
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/').str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
