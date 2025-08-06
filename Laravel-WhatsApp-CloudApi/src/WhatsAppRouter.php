<?php

namespace BiztechEG\WhatsAppCloudApi;

class WhatsAppRouter
{
    protected static $routes = [];

    /**
     * Register a route with a given pattern and an action.
     *
     * @param string $pattern
     * @param callable|string $action
     *        - If callable, the function is executed when the pattern is matched.
     *        - If a string, the class will be instantiated and its handle($message) method called.
     */
    public static function on(string $pattern, $action): void
    {
        self::$routes[$pattern] = $action;
    }

    /**
     * Dispatch the incoming message payload to the appropriate route.
     *
     * @param array $messagePayload
     * @return mixed|null Returns the result of the action if a route matches; otherwise null.
     */
    public static function dispatch(array $messagePayload)
    {
        // Assuming text message is located in $messagePayload['text']['body']
        $messageText = $messagePayload['text']['body'] ?? '';

        foreach (self::$routes as $pattern => $action) {
            // A simple pattern match (case-insensitive search)
            if (stripos($messageText, $pattern) !== false) {
                // If action is callable, call it with the payload.
                if (is_callable($action)) {
                    return call_user_func($action, $messagePayload);
                }

                // If action is a string (class name), instantiate and call handle()
                if (is_string($action) && class_exists($action)) {
                    $instance = new $action;
                    if (method_exists($instance, 'handle')) {
                        return $instance->handle($messagePayload);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Clear all registered routes.
     */
    public static function clearRoutes(): void
    {
        self::$routes = [];
    }
}
