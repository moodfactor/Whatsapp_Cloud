<?php

namespace BiztechEG\WhatsAppCloudApi\Console\Commands;

use Illuminate\Console\Command;
use BiztechEG\WhatsAppCloudApi\Templates\TemplateManager;

class SyncWhatsAppTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:sync-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync WhatsApp message templates from the API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // In a real implementation, you would fetch templates from the WhatsApp Cloud API.
        // For demonstration, we simulate template sync by registering sample templates.
        $sampleTemplate = [
            'name' => 'order_update',
            'language' => 'en_US',
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['text' => 'Hello {{name}}, your order {{order_id}} is {{status}}.']
                    ]
                ]
            ]
        ];

        TemplateManager::registerTemplate('order_update', $sampleTemplate);

        $this->info('Templates synced successfully. Registered templates: ' . json_encode(TemplateManager::listTemplates()));

        return 0;
    }
}
