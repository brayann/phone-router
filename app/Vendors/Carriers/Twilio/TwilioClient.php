<?php

namespace App\Vendors\Carriers\Twilio;

use App\Vendors\Carriers\CarrierInterface;
use Illuminate\Support\Arr;
use Twilio\Rest\Client;

class TwilioClient implements CarrierInterface
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    public function sendSms(array $parameters): array
    {
        $message = $this->client->messages->create(
            Arr::get($parameters, 'to'),
            [
                'from' => Arr::get($parameters, 'from', config('services.twilio.number')),
                'body' => Arr::get($parameters, 'body'),
            ]
        );

        return [
            'sid' => $message->sid,
            'status' => $message->status,
        ];
    }

    public function searchNumber(string $number): ?object
    {
        $number = $this->client->incomingPhoneNumbers->read(['phoneNumber' => $number]);

        return $number ? $number[0] : null;
    }

    public function buyNumber(string $number): object
    {
        return $this->client->incomingPhoneNumbers->create([
            'areaCode' => $number,
            'voiceApplicationSid' => config('services.twilio.app_sid'),
            'smsApplicationSid' => config('services.twilio.app_sid'),
        ]);
    }

    public function configureNumber(string $sid): object
    {
        return $this->client->incomingPhoneNumbers($sid)->update([
            'voiceApplicationSid' => config('services.twilio.app_sid'),
            'smsApplicationSid' => config('services.twilio.app_sid'),
        ]);
    }

    public function createTwilioApp(string $domain, string $sid, string $token): object
    {
        $this->client = new Client($sid, $token);

        return $this->client->applications->create([
            'friendlyName' => 'Aloware Assignment - Brayan',
            'voiceUrl' => "{$domain}/api/twilio/inbound",
            'statusCallback' => "{$domain}/api/twilio/inbound",
            'voiceMethod' => 'POST',
            'smsUrl' => "{$domain}/twilio/sms",
            'smsMethod' => 'POST',
        ]);
    }
}
