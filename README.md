## Intro

First of all, thank you for the opportunity, I really enjoyed the process so far, including this test, and hopefully you like my solution as well :-)

### Dependencies

- All you need to have installed are: PHP, Composer, ngrok and php-sqlite3 extension for SQLite. Since it is just an assessment I decided to just use sqlite, but obviously it is not recommended to use it in production, my preference would be either Postgres or MySQL.
- You will also need an agent number: If your Twilio account has option to buy more numbers, you can use the app to get a new one or configure an existing number. If you're creating a trial account, you'll have to add an external phone as a verified phone number and use it as the agent's phone.

### Configuration

Once you cloned this repository, open a terminal and run these commands from the app's root folder:

- > composer install
- > php artisan app:setup

The last command below will do a few things: 

- Create a .env based on .env.example
- Setup the application key
- Prompt you for some info: Twilio Account SID, Twilio Auth Token
- Prompt you for your ngrok live link (you can start it by running `ngrok http 8000` on another terminal tab)
- Create a Twiml App
- Prompt for an existing phone number on the Twilio account that can be used and re-configured, or just an area code so it buys and configures a new one. This number will be used as the app's number, which will either make a call or send a text with the VM link to the agent's number (which the app will also prompt you to pass).
- Lastly it asks for the agent's number, as explained on the dependencies section.
  
That's it, the app is ready to use, you can now start it by running `php artisan serve`. If you typed anything wrong just run the command again but with the `--force` option and it will start over.

### Usage

Now just call the app's number and you'll get the prompts to play with. Press 1 so it calls your verified phone number, and 2 so it asks you to record a message and at the end it sends a text to your verified (agent's) number. Ideally you should call from a different number than the agent's number, otherwise if you press 1 you'll call yourself. For that, if you're using a trial account, you'll need to also add it as a verified number. (I know it sounds a bit confusing, sorry)

### Tests

> php artisan test

### About the Solution

After reading the project requirements, it seemed to me that there would be too much configurations on the app and also on Twilio's side. So in order to make a smooth setup process, I've created a command where you can setup (almost) everything from it, besides the verified callers that are needed (only for trial accounts).
The implementation was very straight forward, I've created a model to save the call records per the specs, and on every status update it also updates on the db record.
I like to keep the controller methods with the single responsibility of deciding how we will deliver the response (format, http code) and leave the processing/logic of that request inside of a service or actions. On this case there wasn't a need to implement, but when dealing with records I also like to use resources to have a standard/definition when retrieving a specific model for example. Same with: form requests, policies, etc, really enjoy using them but on this case they weren't used.

### Design Decisions

The app is expected only to work just with Twilio, but anything that will be interacting with outside resources, such as APIs, I prefer using interfaces and then bind the final implementation later. That allows you to add different implementations later by just creating another class that implementing that interface. For that I've used Laravel's service container.
So the structure consists in: 
    - Two routes (twilio.inbound and twilio.voicemail)
    - A TwilioController that handles these routes and calls the TwilioService to process the requests.
    - TwilioService which is responsible to decide what to return based on the webhook payloads from Twilio. It also uses the TwilioClient when it receives the callback from the voicemail, in order to send the SMS for the agent with the recording URL.
    - CarrierInterface which defines all the methods that will interact with Twilio's API
    - An implementation of that interface `TwilioClient` which has all the methods defined by the interface.
    - Unit tests that simulates the payloads from Twilio's webhooks and for testing the SMS sending we are mocking an implementation of the `CarrierInterface`.
    - A CallService that adds a layer on top of the Call model, so every interaction with the database also goes through a layer instead of using a direct implementation/access to the db itself, making it easier also for testing and future modifications.
Here is a diagram for a better understanding of the structure and what access what:
![Database ER Missing :(](/uml.png)

### Further Considerations

On a bigger project I would add a few more layers to it, such as Repositories or even DTOs, but for the scope of this project I believe this is already well organized. Also would be a good idea to have a table/model for saving call recordings.
I would also save the recordings on a private S3 instead of keeping them on Twilio's storage, so it saves $ and you have better control of it, more security (can make them private for example), and could use them with Cloudfront to have faster access.
There was not much to be done once the call ends, but I've added an ending message and also I am grabbing the callback and saving the call status into the database.
I would also add a Twilio Signature verification for further security.