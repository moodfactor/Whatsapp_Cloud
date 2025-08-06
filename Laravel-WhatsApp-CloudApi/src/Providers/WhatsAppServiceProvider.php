<?php

namespace BiztechEG\WhatsAppCloudApi\Providers;

use Illuminate\Support\ServiceProvider;
use BiztechEG\WhatsAppCloudApi\WhatsAppClient;
use BiztechEG\WhatsAppCloudApi\InteractiveSessionManager;
use BiztechEG\WhatsAppCloudApi\WhatsAppClientFactory;

class WhatsAppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

        // Merge package configuration.
        $this->mergeConfigFrom(__DIR__.'/../../config/whatsapp.php', 'whatsappApi');

        // Bind the default WhatsAppClient using the factory with the 'default' account.
        $this->app->singleton(WhatsAppClient::class, function ($app) {
            return WhatsAppClientFactory::create('default');
        });

        // Bind InteractiveSessionManager so it can be replaced if necessary.
        $this->app->singleton(InteractiveSessionManager::class, function ($app) {
            return new InteractiveSessionManager();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration file.
        $this->publishes([
            __DIR__.'/../../config/whatsapp.php' => config_path('whatsapp.php'),
        ], 'config');
        
        // Publish migration files.
        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations'),
        ], 'migrations');

        // Publish view files.
        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/whatsapp'),
        ], 'views');
        

        // Load the webhook and playground routes.
        if (!$this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__.'/../../routes/Whatsapp.php', 'whatsapp');
        }

        // Load package views.
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'whatsapp');

        // Register console commands.
        if ($this->app->runningInConsole()) {
            $this->commands([
                \BiztechEG\WhatsAppCloudApi\Console\Commands\SendWhatsAppCampaign::class,
                \BiztechEG\WhatsAppCloudApi\Console\Commands\SendWhatsAppTestMessage::class,
                \BiztechEG\WhatsAppCloudApi\Console\Commands\SyncWhatsAppTemplates::class,
                \BiztechEG\WhatsAppCloudApi\Console\Commands\SimulateWhatsAppWebhook::class,
            ]);
        }
    }
}
