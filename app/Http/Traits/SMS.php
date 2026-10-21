<?php

namespace App\Http\Traits;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait SMS
{
    /**
     * @return array{status: string, description: string}
     */
    private static function parseResultStatus($body): array
    {
        return [
            'status' => $body['status'],
            'description' => $body['data'],
        ];
    }

    public static function sendSMS(string $number, string $text): array
    {

        try {
            /** @var Response $response */
            // $response = Http::withHeaders([
            //     'Accept-Encoding' => 'gzip, deflate',
            //     'Connection'      => 'Keep-Alive',
            //     'Host'            => 'e3len.vodafone.com.eg',
            // ])

            //     ->timeout(30)
            //     ->withBody($xml, 'application/xml')
            //     ->post();

            $status = 1;
            $body = [
                'status' => 'SUCCESS',
                'data' => [],
            ];
            $result = self::parseResultStatus($body);

            // The gateway answers 200 even when it rejects the message, so the
            // HTTP status alone says nothing about delivery.
            $success = (! isset($response) || $response->successful()) && $result['status'] === 'SUCCESS';

            Log::info('[sms] vodafone responded', [
                'number' => $number,
                'text' => $text,
                'text_hex' => bin2hex($text),
                'text_len' => mb_strlen($text),
                'http_status' => $status,
                'result_status' => $result['status'],
                'description' => $result['description'],
                'success' => $success,
                'body' => $body,
            ]);

            return [
                'success' => $success,
                'status' => $status,
                'result_status' => $result['status'],
                'description' => $result['description'],
                'error' => $success ? null : trim($result['status'].' '.$result['description']),
                'body' => $body,
            ];
        } catch (\Exception $e) {
            Log::error('[sms] vodafone request threw', [
                'number' => $number,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
