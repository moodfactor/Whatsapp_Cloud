<?php

namespace BiztechEG\WhatsAppCloudApi\Messages;

use BiztechEG\WhatsAppCloudApi\Templates\TemplateManager;

class TemplateMessage implements MessageInterface
{
    protected string $recipient;
    protected array $templateData;
    protected array $bindings;

    /**
     * @param string $recipient
     * @param array $templateData  The template data structure.
     * @param array $bindings      Key-value pairs to bind in the template.
     */
    public function __construct(string $recipient, array $templateData, array $bindings = [])
    {
        $this->recipient = $recipient;
        $this->templateData = $templateData;
        $this->bindings = $bindings;
    }

    /**
     * Convert the template message to an array payload.
     *
     * @return array
     */
    public function toArray(): array
    {
        // Validate the template data.
        if (!TemplateManager::validateTemplate($this->templateData)) {
            throw new \InvalidArgumentException("Invalid template data provided.");
        }

        // Bind placeholders with actual values.
        $boundTemplate = TemplateManager::bindTemplate($this->templateData, $this->bindings);

        return [
            'messaging_product' => 'whatsapp',
            'to' => $this->recipient,
            'type' => 'template',
            'template' => $boundTemplate,
        ];
    }
}
