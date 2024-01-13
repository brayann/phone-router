<?php

namespace App\Console\Commands;

use App\Vendors\Carriers\CarrierInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class SetupAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private CarrierInterface $carrier;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (file_exists('.env') && Config::get('app.env') === 'production' && ! $this->confirm('Are you sure you want to run this in production?')) {
            return;
        }

        $this->setupEnv();

        if (! Config::get('app.key') || $this->option('force')) {
            Artisan::call('key:generate --force --quiet');
        }        

        if (! Config::get('services.twilio.sid') || $this->option('force')) {
            $sid = $this->ask('What is your Twilio Account SID?');
            $this->setupEnvVariable('TWILIO_ACCOUNT_SID', $sid);
        }

        if (! Config::get('services.twilio.token') || $this->option('force')) {
            $token = $this->ask('What is your Twilio token?');
            $this->setupEnvVariable('TWILIO_TOKEN', $token);
        }
        Artisan::call('config:cache');
        $this->carrier = app(CarrierInterface::class);

        if (! Config::get('services.twilio.app_sid') || $this->option('force')) {
            $domain = $this->ask('What is your full base domain so we can create a Twilio app for you? make sure it\'s publicly accessible and uses https');
            $app = $this->carrier->createTwilioApp($domain, isset($sid) ? $sid : Config::get('services.twilio.sid'), isset($token) ? $token : Config::get('services.twilio.token'));
            $this->setupEnvVariable('APP_URL', $domain);
            $this->setupEnvVariable('TWILIO_APP_SID', $app->sid);
            Artisan::call('config:cache');
        }

        if (! Config::get('services.twilio.number') || $this->option('force')) {
            $number = $this->ask('What is your Twilio number? put only the area code if you want the app to get a new one for you');
            $number = $this->setupNumber($number);
            $this->setupEnvVariable('TWILIO_NUMBER', (int) $number->phoneNumber);
            $this->info("Your App's Twilio number {$number->phoneNumber} was successfully setup!");
        }

        if (! Config::get('services.twilio.agent_number') || $this->option('force')) {
            $number = $this->ask("What is your agent's number? put only the area code if you want the app to get a new one for you (does not work for trial accounts, you'll have to use verified numbers instead)");
            if (strlen($number) === 3) {
                $this->info('Creating a new Twilio number for you...');
                $number = $this->carrier->buyNumber($number)->sid;
            }
            $this->setupEnvVariable('TWILIO_AGENT_NUMBER', (int) $number);
            $this->info("Your Agent's Twilio number {$number} was successfully setup!");
        }
        Artisan::call('config:clear');
        $this->info('App setup completed!');

    }

    /**
     * Setup the Twilio number.
     *
     * @param  string  $number
     */
    protected function setupNumber($number)
    {
        if (strlen($number) === 3) {
            $this->info('Creating a new Twilio number for you...');
            $number = $this->carrier->buyNumber($number);
        } else {
            $this->info('Setting up your Twilio number...');
            $number = $this->carrier->searchNumber($number);
            $this->carrier->configureNumber($number->sid);
        }

        return $number;
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
        if (! file_exists('.env')) {
            $this->info('Copying .env.example to .env');
            copy('.env.example', '.env');
        }
        $this->setupEnvVariable('DB_DATABASE', base_path('db.sqlite'));
        Artisan::call('config:cache');
        Artisan::call('migrate --quiet');
    }
}
