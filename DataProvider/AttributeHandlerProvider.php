<?php

declare(strict_types=1);

namespace RunAsRoot\Feed\DataProvider;

use RunAsRoot\Feed\Data\AttributeConfigData;
use RunAsRoot\Feed\DataProvider\AttributeHandlers\AttributeHandlerInterface;
use RunAsRoot\Feed\Exception\HandlerIsNotSpecifiedException;
use RunAsRoot\Feed\Exception\WrongInstanceException;
use RunAsRoot\Feed\Factory\AttributeHandlerFactory;

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
