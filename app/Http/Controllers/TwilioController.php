<?php

namespace App\Http\Controllers;

use App\Vendors\Carriers\Twilio\TwilioService;
use Illuminate\Http\Request;

class TwilioController extends Controller
{
    /**
     * Handles an inbound call from Twilio
     */
    public function inbound(Request $request, TwilioService $service)
    {
        return response($service->handleInboundCall($request), 200)
            ->header('Content-Type', 'text/xml');
    }

    /**
     * Handles a voicemail recording from Twilio
     */
    public function voicemail(Request $request, TwilioService $service)
    {
        return response($service->handleVoicemail($request), 200)
            ->header('Content-Type', 'text/xml');
    }
}
