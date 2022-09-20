<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers;

use Magento\Framework\ObjectManagerInterface;

class SelectAttributeHandlerFactory
{
    private ObjectManagerInterface $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $arguments = []): SelectAttributeHandler
    {
        return $this->objectManager->create(SelectAttributeHandler::class, $arguments);
    }
}
