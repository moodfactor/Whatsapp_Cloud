<?php

namespace BiztechEG\WhatsAppCloudApi\Console\Commands;

use Illuminate\Console\Command;
use BiztechEG\WhatsAppCloudApi\WhatsAppClient;
use BiztechEG\WhatsAppCloudApi\Messages\TextMessage;

class SendWhatsAppCampaign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Recipients should be provided as a comma-separated list.
     *
     * @var string
     */
    protected $signature = 'whatsapp:send-campaign {message : The message to broadcast} {--recipients= : Comma separated list of recipient phone numbers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a broadcast campaign to a list of recipients via WhatsApp';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $messageText = $this->argument('message');
        $recipientsOption = $this->option('recipients');

        if (!$recipientsOption) {
            $this->error('Please provide recipients using the --recipients option.');
            return 1;
        }

        $recipients = array_map('trim', explode(',', $recipientsOption));
        $client = app(WhatsAppClient::class);

        foreach ($recipients as $recipient) {
            $message = new TextMessage($recipient, $messageText);
            try {
                $client->sendMessage($message);
                $this->info("Message sent to {$recipient}");
            } catch (\Exception $e) {
                $this->error("Failed to send message to {$recipient}: " . $e->getMessage());
            }
        }

        return 0;
    }
}
