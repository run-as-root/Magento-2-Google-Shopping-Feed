<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use RunAsRoot\GoogleShoppingFeed\Data\AttributeConfigData;
use RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\AttributeHandlerInterface;
use RunAsRoot\GoogleShoppingFeed\Exception\HandlerIsNotSpecifiedException;
use RunAsRoot\GoogleShoppingFeed\Exception\WrongInstanceException;
use RunAsRoot\GoogleShoppingFeed\Factory\AttributeHandlerFactory;

class AttributeHandlerProvider
{
    private AttributeHandlerFactory $factory;

    /**
     * @var AttributeHandlerInterface[]
     */
    private array $handlersPool = [];

    public function __construct(AttributeHandlerFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @throws HandlerIsNotSpecifiedException
     * @throws WrongInstanceException
     */
    public function get(AttributeConfigData $attribute): AttributeHandlerInterface
    {
        $name = $attribute->getFieldName();

        if (isset($this->handlersPool[$name])) {
            return $this->handlersPool[$name];
        }

        $handler = $this->factory->create($attribute);
        $this->handlersPool[$name] = $handler;

        return $handler;
    }
}
