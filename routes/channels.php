<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('research-chat.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

  Broadcast::channel('whatsapp-messages', function ($user) {
      // Allow authenticated admin users to listen to WhatsApp messages
      return $user && $user instanceof \App\Models\Admin\Admins;
  });
