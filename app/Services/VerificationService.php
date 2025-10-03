<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VerificationService
{
    public $api_key;
    public $base_url;
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->api_key = env('INFOBIP_KEY');
        $this->base_url = env('INFOBIP_URL');
    }

    public function createVerificationApp()
    {
        $request = Http::
            withHeaders([
                'Authorization' => "App {$this->api_key}",
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ])
            ->post("{$this->base_url}/2fa/2/applications", [
                'name' => 'OTP verification',
                'enabled' => true,
                'configuration' => [
                    'pinAttempts' => 3,
                    'allowMultiplePinVerifications' => true,
                    'pinTimeToLive' => '10m',
                    'verifyPinLimit' => '2/4s',
                    'sendPinPerApplicationLimit' => '5000/12h',
                    'sendPinPerPhoneNumberLimit' => '2/1d'
                ]
            ]);

        return $request->json();
    }

    public function getVerificationApps()
    {
        $request = Http::
            withHeaders([
                'Authorization' => "App {$this->api_key}",
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ])
            ->get("{$this->base_url}/2fa/2/applications");


        return $request->json();
    }

    public function createMessageTemplate($appId)
    {
        $request = Http::
            withHeaders([
                'Authorization' => "App {$this->api_key}",
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ])
            ->post("{$this->base_url}/2fa/2/applications/{$appId}/messages", [
                'pinType' => 'NUMERIC',
                'messageText' => 'Your pin is {{pin}}',
                'pinLength' => 4,
                'senderId' => 'ServiceSMS'
            ]);

        return $request->json();
    }

    public function getMessageTemplates($appId)
    {
        $request = Http::
            withHeaders([
                'Authorization' => "App {$this->api_key}",
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ])
            ->get("{$this->base_url}/2fa/2/applications/{$appId}/messages");

        return $request->json();
    }

    public function sendOTP($appId, $messageId, $phone)
    {
        $request = Http::
            withHeaders([
                'Authorization' => "App {$this->api_key}",
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ])
            ->post("{$this->base_url}/2fa/2/pin?ncNeeded=true", [
                'applicationId' => $appId,
                'messageId' => $messageId,
                'to' => $phone,
                'from' => '447491163443'
            ]);

        return $request->json();
    }
}
