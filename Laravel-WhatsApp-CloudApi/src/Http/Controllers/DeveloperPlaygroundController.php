<?php

namespace BiztechEG\WhatsAppCloudApi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use BiztechEG\WhatsAppCloudApi\WhatsAppClient;
use BiztechEG\WhatsAppCloudApi\Messages\TextMessage;

class DeveloperPlaygroundController extends Controller
{
    /**
     * Display the playground UI.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('whatsapp::playground');
    }

    /**
     * Handle the form submission to send a test message.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendTestMessage(Request $request)
    {
        $recipient = $request->input('recipient');
        $messageText = $request->input('message');

        /** @var WhatsAppClient $client */
        $client = app(WhatsAppClient::class);
        $message = new TextMessage($recipient, $messageText);

        try {
            $response = $client->sendMessage($message);
            return redirect()->back()->with('success', 'Message sent successfully: ' . json_encode($response));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send message: ' . $e->getMessage());
        }
    }
}
