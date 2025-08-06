<?php

namespace BiztechEG\WhatsAppCloudApi;

use BiztechEG\WhatsAppCloudApi\Models\InteractiveSession;
use Illuminate\Support\Str;

class InteractiveSessionManager
{
    /**
     * Start a new interactive session.
     *
     * @param string $recipient
     * @param array  $messagePayload
     * @return string Generated session ID.
     */
    public static function startSession(string $recipient, array $messagePayload): string
    {
        $sessionId = (string) Str::uuid();
        InteractiveSession::create([
            'session_id'      => $sessionId,
            'recipient'       => $recipient,
            'status'          => 'pending',
            'message_payload' => $messagePayload,
        ]);
        return $sessionId;
    }

    /**
     * Get the session data.
     *
     * @param string $sessionId
     * @return InteractiveSession|null
     */
    public static function getSession(string $sessionId): ?InteractiveSession
    {
        return InteractiveSession::find($sessionId);
    }

    /**
     * Update the session data.
     *
     * @param string $sessionId
     * @param array  $data
     * @return bool
     */
    public static function updateSession(string $sessionId, array $data): bool
    {
        $session = self::getSession($sessionId);
        if ($session) {
            $session->update($data);
            return true;
        }
        return false;
    }

    /**
     * End the session by marking it as finished.
     *
     * @param string $sessionId
     * @return bool
     */
    public static function endSession(string $sessionId): bool
    {
        return self::updateSession($sessionId, ['status' => 'finished']);
    }
}
