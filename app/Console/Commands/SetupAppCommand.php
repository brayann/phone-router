<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class SetupAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-app {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->laravel->isProduction() && !$this->confirm('Are you sure you want to run this in production?')) {
            return;
        }

        $this->setupEnv();

        if (!Config::get('services.twilio.sid') || $this->option('force')) {
            $sid = $this->ask('What is your Twilio Account SID?');
            $this->setupEnvVariable('TWILIO_ACCOUNT_SID', $sid);
        }

        if (!Config::get('services.twilio.token') || $this->option('force')) {
            $token = $this->ask('What is your Twilio token?');
            $this->setupEnvVariable('TWILIO_TOKEN', $token);
        }

        if (!Config::get('services.twilio.app_sid') || $this->option('force')) {
            $domain = $this->ask('What is your full base domain so we can create a Twilio app for you? make sure it\'s publicly accessible and uses https');
            $app = $this->createTwilioApp($domain, isset($sid) ? $sid : Config::get('services.twilio.sid'), isset($token) ? $token : Config::get('services.twilio.token'));
            $this->setupEnvVariable('APP_URL', $domain);
            $this->setupEnvVariable('TWILIO_APP_SID', $app->sid);
        }

        $this->info('App setup completed!');
        
    }

    private function setupEnvVariable($key, $value)
    {
        file_put_contents(base_path('.env'), preg_replace(
            "/{$key}=(.*)/",
            "{$key}={$value}",
            file_get_contents(base_path('.env'))
        ));
    }

    /**
     * Setup the .env file.
     */
    protected function setupEnv()
    {
        if (!file_exists('.env')) {
            $this->info('Copying .env.example to .env');
            copy('.env.example', '.env');
        }
    }

    /**
     * Create a Twilio app.
     *
     * @param string $domain
     */
    protected function createTwilioApp($domain, $sid, $token)
    {
        $client = new \Twilio\Rest\Client($sid, $token);

        return $client->applications->create([
            'friendlyName' => 'Aloware',
            'voiceUrl' => "{$domain}/twilio/voice",
            'voiceMethod' => 'POST',
            'smsUrl' => "{$domain}/twilio/sms",
            'smsMethod' => 'POST',
        ]);
    }
}
