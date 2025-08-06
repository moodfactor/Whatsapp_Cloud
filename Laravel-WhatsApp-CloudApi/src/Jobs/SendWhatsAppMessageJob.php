<?php

namespace BiztechEG\WhatsAppCloudApi\Jobs;

use BiztechEG\WhatsAppCloudApi\Messages\MessageInterface;
use BiztechEG\WhatsAppCloudApi\WhatsAppClient;
use BiztechEG\WhatsAppCloudApi\Events\WhatsAppMessageSent;
use BiztechEG\WhatsAppCloudApi\Events\WhatsAppMessageFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    protected MessageInterface $message;
    protected array $clientConfig;

    /**
     * Create a new job instance.
     *
     * @param MessageInterface $message
     * @param array $clientConfig
     */
    public function __construct(MessageInterface $message, array $clientConfig)
    {
        $this->message = $message;
        $this->clientConfig = $clientConfig;
    }

    /**
     * Determine the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff(): int
    {
        // Exponential backoff: 2^attempt seconds (e.g., 2, 4, 8, 16, ... seconds)
        return pow(2, $this->attempts());
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $client = new WhatsAppClient($this->clientConfig);
            $client->sendMessage($this->message);

            // Dispatch event on success.
            event(new WhatsAppMessageSent($this->message->toArray()));
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message in job', [
                'error' => $e->getMessage(),
                'messagePayload' => $this->message->toArray(),
                'attempts' => $this->attempts(),
            ]);

            // The job will be retried automatically based on $tries and backoff.
            // Optionally dispatch a failure event after maximum attempts.
            if ($this->attempts() >= $this->tries) {
                event(new WhatsAppMessageFailed($this->message->toArray(), $e->getMessage()));
            }

            // Let the exception bubble up to allow Laravel to retry the job.
            throw $e;
        }
    }

    /**
     * Optional: Specify when the job should no longer be retried.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        // Retry for up to 10 minutes from job creation.
        return now()->addMinutes(10);
    }
}


// usage
// use BiztechEG\WhatsAppCloudApi\Jobs\SendWhatsAppMessageJob;
// use BiztechEG\WhatsAppCloudApi\Messages\TextMessage;

// // Create your message instance
// $message = new TextMessage('+1234567890', 'Hello, this message is delayed.');

// // Dispatch the job with a delay of 10 minutes
// SendWhatsAppMessageJob::dispatch($message, config('whatsapp'))->delay(now()->addMinutes(10));
