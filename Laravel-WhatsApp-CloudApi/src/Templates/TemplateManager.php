<?php

namespace BiztechEG\WhatsAppCloudApi\Templates;

class TemplateManager
{
    protected static $templates = [];

    /**
     * Register a new template.
     *
     * @param string $name
     * @param array $templateData
     * @return void
     */
    public static function registerTemplate(string $name, array $templateData): void
    {
        self::$templates[$name] = $templateData;
    }

    /**
     * Get a template by name.
     *
     * @param string $name
     * @return array|null
     */
    public static function getTemplate(string $name): ?array
    {
        return self::$templates[$name] ?? null;
    }

    /**
     * List all registered templates.
     *
     * @return array
     */
    public static function listTemplates(): array
    {
        return self::$templates;
    }

    /**
     * Validate a template before sending.
     * Ensures that required fields are present.
     *
     * @param array $templateData
     * @return bool
     */
    public static function validateTemplate(array $templateData): bool
    {
        // For example, WhatsApp templates may require: name, language, and components.
        if (!isset($templateData['name']) || !isset($templateData['language']) || !isset($templateData['components'])) {
            return false;
        }
        return true;
    }

    /**
     * Bind placeholders in the template with actual values.
     *
     * @param array $templateData
     * @param array $bindings
     * @return array
     */
    public static function bindTemplate(array $templateData, array $bindings): array
    {
        // Iterate over the components and replace placeholders with actual values.
        if (isset($templateData['components'])) {
            foreach ($templateData['components'] as &$component) {
                if (isset($component['parameters'])) {
                    foreach ($component['parameters'] as &$parameter) {
                        if (isset($parameter['text'])) {
                            foreach ($bindings as $key => $value) {
                                $parameter['text'] = str_replace("{{{$key}}}", $value, $parameter['text']);
                            }
                        }
                    }
                }
            }
        }
        return $templateData;
    }
}
