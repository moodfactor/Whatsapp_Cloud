<?php


namespace YourVendor\WhatsAppCloudApi\Tests;

use PHPUnit\Framework\TestCase;
use YourVendor\WhatsAppCloudApi\WhatsAppClient;
use YourVendor\WhatsAppCloudApi\Messages\TextMessage;
use YourVendor\WhatsAppCloudApi\Exceptions\WhatsAppException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

class WhatsAppClientTest extends TestCase
{
    public function testSendMessageSuccess()
    {
        // Create a mock response simulating a successful API call.
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true, 'message_id' => '12345']))
        ]);
        $handlerStack = HandlerStack::create($mock);

        // Create a custom Guzzle client with the mock handler.
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Define test configuration.
        $config = [
            'api_url' => 'https://graph.facebook.com/v16.0/',
            'access_token' => 'fake_access_token',
            'phone_number_id' => 'fake_phone_id',
        ];

        // Instantiate the WhatsAppClient.
        $client = new WhatsAppClient($config);
        // Override the http client for testing using reflection.
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($client, $guzzleClient);

        // Create a text message.
        $message = new TextMessage('+1234567890', 'Test message');

        // Send the message.
        $result = $client->sendMessage($message);

        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message_id', $result);
        $this->assertEquals('12345', $result['message_id']);
    }

    public function testSendMessageFailure()
    {
        // Create a mock that simulates a failed request.
        $mock = new MockHandler([
            new RequestException("Error Communicating with Server", $this->createMock(RequestInterface::class))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $config = [
            'api_url' => 'https://graph.facebook.com/v16.0/',
            'access_token' => 'fake_access_token',
            'phone_number_id' => 'fake_phone_id',
        ];

        $client = new WhatsAppClient($config);
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($client, $guzzleClient);

        $message = new TextMessage('+1234567890', 'Test message');

        $this->expectException(WhatsAppException::class);
        $client->sendMessage($message);
    }
}
