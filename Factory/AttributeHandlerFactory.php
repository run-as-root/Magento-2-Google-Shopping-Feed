<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Factory;

use Magento\Framework\ObjectManagerInterface;
use RunAsRoot\GoogleShoppingFeed\Data\AttributeConfigData;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\AttributeHandlerInterface;
use RunAsRoot\GoogleShoppingFeed\Exception\HandlerIsNotSpecifiedException;
use RunAsRoot\GoogleShoppingFeed\Exception\WrongInstanceException;

class AttributeHandlerFactory
{
    private ObjectManagerInterface $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @throws WrongInstanceException
     * @throws HandlerIsNotSpecifiedException
     */
    public function create(AttributeConfigData $attribute): AttributeHandlerInterface
    {
        if ($attribute->getAttributeHandler() === null) {
            throw new HandlerIsNotSpecifiedException(__('Handler should be specified for each attribute.'));
        }

        $handlerClass = $attribute->getAttributeHandler();
        $instance = $this->objectManager->create($handlerClass);

        if (!$instance instanceof AttributeHandlerInterface) {
            throw new WrongInstanceException(__('Class should implement AttributeHandlerInterface interface.'));
        }

        return $instance;
    }
}
