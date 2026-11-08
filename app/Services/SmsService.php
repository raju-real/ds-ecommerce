<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SMS gateway wrapper.
 *  - log:  writes the message to storage/logs (development)
 *  - http: GET request to a typical BD bulk-SMS API. Configure the URL in
 *          admin > Settings > SMS with placeholders {api_key} {sender_id} {number} {message}
 *          e.g. http://bulksmsbd.net/api/smsapi?api_key={api_key}&senderid={sender_id}&number={number}&message={message}
 */
class SmsService
{
    public function send(string $mobile, string $message): bool
    {
        $driver = setting('sms_driver', config('shop.sms.driver'));

        if ($driver !== 'http') {
            Log::info("[SMS to $mobile] $message");

            return true;
        }

        $url = strtr((string) setting('sms_api_url', config('shop.sms.url')), [
            '{api_key}' => urlencode((string) setting('sms_api_key', config('shop.sms.api_key'))),
            '{sender_id}' => urlencode((string) setting('sms_sender_id', config('shop.sms.sender_id'))),
            '{number}' => urlencode('88'.$mobile),
            '{message}' => urlencode($message),
        ]);

        try {
            return Http::timeout(10)->get($url)->successful();
        } catch (Throwable $e) {
            Log::error('SMS failed: '.$e->getMessage());

            return false;
        }
    }

    public function isLogDriver(): bool
    {
        return setting('sms_driver', config('shop.sms.driver')) !== 'http';
    }
}
