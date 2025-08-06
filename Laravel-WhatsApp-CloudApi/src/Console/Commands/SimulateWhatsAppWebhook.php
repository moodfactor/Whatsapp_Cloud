<?php

namespace BiztechEG\WhatsAppCloudApi\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use BiztechEG\WhatsAppCloudApi\Http\Controllers\WhatsAppWebhookController;

class SimulateWhatsAppWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:webhook:simulate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate an incoming WhatsApp webhook for testing purposes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Create a sample payload to simulate an inbound message.
        $samplePayload = [
            'text' => ['body' => 'This is a simulated inbound message.'],
            'from' => '+1234567890',
            'type' => 'text',
            'metadata' => ['session_id' => 'simulated-session']
        ];

        // Create a fake request with the sample payload.
        $request = Request::create('/webhook/whatsapp', 'POST', $samplePayload);

        // Create instance of the webhook controller and call handle method.
        $controller = new WhatsAppWebhookController();
        $response = $controller->handle($request);

        $this->info('Simulated webhook response: ' . $response->getContent());

        return 0;
    }
}
