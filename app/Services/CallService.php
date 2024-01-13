<?php

namespace App\Services;

use App\Models\Call;
use Illuminate\Support\Arr;

class CallService
{
    public function firstOrCreate(array $data)
    {
        $call = Call::firstOrCreate([
            'uuid' => Arr::get($data, 'CallSid', null),
            'from' => Arr::get($data, 'From', null),
            'to' => Arr::get($data, 'To', null),
        ]);
        $call->status = Arr::get($data, 'CallStatus', $call->status);

        return $call->save();
    }
}
