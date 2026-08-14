<?php
namespace App\Services;

use App\Services\SmsProviders\TwilioProvider;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $provider;

    public function __construct()
    {
        $settings = getSmsSettings();
        Log::info("All SMS settings: " . print_r($settings, true));

       $type = blank(request('sms_type')) ? 'twilio' : request('sms_type');

        // ✅ FIX: DO NOT THROW ERROR
        if (!isset($settings[$type])) {
            Log::warning("SMS provider settings missing for: {$type}");
            $this->provider = null;
            return;
        }

        // same provider, same logic
        $this->provider = new TwilioProvider($settings[$type]);
    }

    public function sendSMS($to, $message)
    {
        if (!$this->provider) {
            return false;
        }

        return $this->provider->send($to, $message);
    }
}
