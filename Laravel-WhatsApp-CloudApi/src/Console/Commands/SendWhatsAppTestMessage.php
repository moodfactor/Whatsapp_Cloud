<?php

namespace BiztechEG\WhatsAppCloudApi\Console\Commands;

use Illuminate\Console\Command;
use BiztechEG\WhatsAppCloudApi\WhatsAppClient;
use BiztechEG\WhatsAppCloudApi\Messages\TextMessage;

class SendWhatsAppTestMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test {recipient : The recipient phone number} {message=Test message : The test message to send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test WhatsApp message';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $recipient = $this->argument('recipient');
        $messageText = $this->argument('message');

        $client = app(WhatsAppClient::class);
        $message = new TextMessage($recipient, $messageText);

        try {
            $response = $client->sendMessage($message);
            $this->info("Test message sent successfully. Response: " . json_encode($response));
        } catch (\Exception $e) {
            $this->error("Failed to send test message: " . $e->getMessage());
        }

        return 0;
    }
}
