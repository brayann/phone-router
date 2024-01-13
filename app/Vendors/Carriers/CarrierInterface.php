<?php

namespace App\Vendors\Carriers;

interface CarrierInterface
{
    public function sendSms(array $parameters): array;

    public function searchNumber(string $number): ?object;

    public function buyNumber(string $number): object;

    public function configureNumber(string $sid): object;

    public function createTwilioApp(string $domain, string $sid, string $token): object;
}
