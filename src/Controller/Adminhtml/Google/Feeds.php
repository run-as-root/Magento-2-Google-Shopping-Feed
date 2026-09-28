<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Controller\Adminhtml\Google;

class Feeds extends \Magento\Backend\App\Action
{
    protected \Magento\Framework\View\Result\PageFactory $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(): \Magento\Framework\View\Result\Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((string)__('Google Shopping Feeds'));

        return $resultPage;
    }
}
