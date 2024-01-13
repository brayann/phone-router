<?php

namespace App\Vendors\Carriers\Twilio;

use App\Models\Call;
use App\Services\CallService;
use App\Vendors\Carriers\CarrierInterface;
use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

class TwilioService
{
    private VoiceResponse $response;

    const ROUTE_TO_AGENT = 1;

    const RECORD_VOICEMAIL = 2;

    public function __construct(private CarrierInterface $client, private CallService $callService)
    {
        $this->response = new VoiceResponse();
    }

    /**
     * @return void
     */
    private function saveCall(Request $request)
    {
        $this->callService->firstOrCreate($request->all());
    }

    /**
     * Handle an inbound call from Twilio
     *
     * @return VoiceResponse
     */
    public function handleInboundCall(Request $request)
    {
        $this->saveCall($request);
        switch ($request->CallStatus) {
            case 'ringing':
            case 'in-progress':
                if (! $request->has('Digits')) {
                    return $this->gather();
                }

                return $this->handleOption($request);
            case 'completed':
                $this->response->say('Goodbye.');

                return $this->response;
        }
    }

    /**
     * @return VoiceResponse
     */
    private function gather()
    {
        $gather = $this->response->gather(['numDigits' => 1]);
        $gather->say('Press '.self::ROUTE_TO_AGENT.' to be routed to the agent, or '.self::RECORD_VOICEMAIL.' to record a voicemail');

        return $this->response;
    }

    /**
     * @return VoiceResponse
     */
    private function handleOption(Request $request)
    {
        switch ($request->Digits) {
            case self::ROUTE_TO_AGENT:
                return $this->routeToAgent($request);
            case self::RECORD_VOICEMAIL:
                return $this->recordVoicemail();
        }
    }

    /**
     * @return VoiceResponse
     */
    private function routeToAgent(Request $request)
    {
        $this->response->say('Please wait while we connect you to an agent.');
        $dial = $this->response->dial('', ['callerId' => $request->From]);
        $dial->number(config('services.twilio.agent_number'));

        return $this->response;
    }

    /**
     * @return VoiceResponse
     */
    private function recordVoicemail()
    {
        $this->response->say('Please leave a message after the tone, then press the pound sign.');
        $this->response->record(['action' => route('twilio.voicemail'), 'finishOnKey' => '#', 'maxLength' => 60, 'method' => 'POST']);

        return $this->response;
    }

    /**
     * Handle a voicemail recording from Twilio
     *
     * @return VoiceResponse
     */
    public function handleVoicemail(Request $request)
    {
        $this->sendText(config('services.twilio.agent_number'), "New voicemail from {$request->From}: {$request->RecordingUrl}");
        $this->response->say('Thank you for your message.');
        $this->response->hangup();

        return $this->response;
    }

    /**
     * @param  string  $to
     * @param  string  $message
     * @return array
     */
    public function sendText($to, $message)
    {
        return $this->client->sendSms([
            'to' => $to,
            'body' => $message,
        ]);
    }
}
