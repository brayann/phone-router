<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwilioControllerTest extends TestCase
{
    use RefreshDatabase;

    private function parseResponse($response)
    {
        $response = simplexml_load_string($response->getContent());

        return json_decode(json_encode((array) $response), true);
    }

    /**
     * A basic feature test example.
     */
    public function test_inbound_call_should_return_options(): void
    {
        $response = $this->post(route('twilio.inbound'), [
            'accountSid' => config('services.twilio.sid'),
            'CallSid' => 'CA1234567890ABCDE',
            'From' => '+15555555555',
            'To' => '+16666666666',
            'CallStatus' => 'in-progress',
        ])->assertStatus(200);
        $response = $this->parseResponse($response);
        $this->assertArrayHasKey('Gather', $response);
        $this->assertArrayHasKey('Say', $response['Gather']);
    }

    public function test_it_should_route_to_phone_number_when_1_is_pressed(): void
    {
        $response = $this->post(route('twilio.inbound'), [
            'accountSid' => config('services.twilio.sid'),
            'CallSid' => 'CA1234567890ABCDE',
            'From' => '+15555555555',
            'To' => '+16666666666',
            'CallStatus' => 'in-progress',
            'Digits' => '1',
        ])->assertStatus(200);
        $response = $this->parseResponse($response);
        $this->assertArrayHasKey('Dial', $response);
        $this->assertArrayHasKey('Say', $response);
    }

    public function test_it_should_record_a_message_when_2_is_pressed(): void
    {
        $response = $this->post(route('twilio.inbound'), [
            'accountSid' => config('services.twilio.sid'),
            'CallSid' => 'CA1234567890ABCDE',
            'From' => '+15555555555',
            'To' => '+16666666666',
            'CallStatus' => 'in-progress',
            'Digits' => '2',
        ])->assertStatus(200);
        $response = $this->parseResponse($response);
        $this->assertArrayHasKey('Say', $response);
        $this->assertArrayHasKey('Record', $response);
    }

    public function test_it_should_say_goodbye_when_call_is_completed(): void
    {
        $response = $this->post(route('twilio.inbound'), [
            'accountSid' => config('services.twilio.sid'),
            'CallSid' => 'CA1234567890ABCDE',
            'From' => '+15555555555',
            'To' => '+16666666666',
            'CallStatus' => 'completed',
        ])->assertStatus(200);
        $response = $this->parseResponse($response);
        $this->assertArrayHasKey('Say', $response);
        $this->assertEquals($response['Say'], 'Goodbye.');
    }

    public function test_it_should_handle_a_voicemail(): void
    {
        $this->mock(\App\Vendors\Carriers\CarrierInterface::class, function ($mock) {
            $mock->shouldReceive('sendSms')->once()->andReturn([
                'CallSid' => 'SM1234567890ABCDE',
                'CallStatus' => 'queued',
            ]);
        });
        $response = $this->post(route('twilio.voicemail'), [
            'accountSid' => config('services.twilio.sid'),
            'CallSid' => 'CA1234567890ABCDE',
            'From' => '+15555555555',
            'To' => config('services.twilio.number'),
            'recordingUrl' => 'https://example.com',
            'recordingDuration' => '30',
        ])->assertStatus(200);
        $response = $this->parseResponse($response);
        $this->assertArrayHasKey('Say', $response);
        $this->assertEquals($response['Say'], 'Thank you for your message.');
    }
}
