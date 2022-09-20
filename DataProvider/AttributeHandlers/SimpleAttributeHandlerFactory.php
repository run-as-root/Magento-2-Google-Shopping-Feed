<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Framework\ObjectManagerInterface;

class SimpleAttributeHandlerFactory
{
    private ObjectManagerInterface $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $arguments = []): SimpleAttributeHandler
    {
        return $this->objectManager->create(SimpleAttributeHandler::class, $arguments);
    }
}
