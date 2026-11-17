<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\DataProvider;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;

use function number_format;
use function sprintf;

class CurrencyAmountProvider
{
    private CurrencyProvider $currencyProvider;
    private PriceCurrencyInterface $priceCurrency;

    public function __construct(
        CurrencyProvider $currencyProvider,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->currencyProvider = $currencyProvider;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(float $amount, int $storeId): string
    {
        $currency = $this->currencyProvider->get($storeId);
        $convertedAmount = $this->priceCurrency->convertAndRound($amount, $storeId, $currency);

        return sprintf('%s %s', number_format($convertedAmount, 2, '.', ''), $currency->getCode());
    }
}
