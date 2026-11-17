<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Test\Unit\DataProvider;

use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use PHPUnit\Framework\TestCase;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyAmountProvider;
use RunAsRoot\GoogleShoppingFeed\DataProvider\CurrencyProvider;

final class CurrencyAmountProviderTest extends TestCase
{
    private CurrencyAmountProvider $sut;
    private PriceCurrencyInterface $priceCurrencyMock;
    private CurrencyProvider $currencyProviderMock;

    protected function setUp(): void
    {
        $this->priceCurrencyMock = $this->createMock(PriceCurrencyInterface::class);
        $this->currencyProviderMock = $this->createMock(CurrencyProvider::class);

        $this->sut = new CurrencyAmountProvider($this->currencyProviderMock, $this->priceCurrencyMock);
    }

    /**
     * @dataProvider amountProvider
     */
    public function testGet(float $convertedAmount, string $currencyCode, string $expected): void
    {
        $currencyMock = $this->createMock(Currency::class);
        $currencyMock->method('getCode')->willReturn($currencyCode);
        $this->currencyProviderMock->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($currencyMock);
        $this->priceCurrencyMock->expects($this->once())
            ->method('convertAndRound')
            ->with(120.00, 1, $currencyMock)
            ->willReturn($convertedAmount);

        $this->assertSame($expected, $this->sut->get(120.00, 1));
    }

    /**
     * @return array<string, array{float, string, string}>
     */
    public function amountProvider(): array
    {
        return [
            'whole amount' => [120.0, 'EUR', '120.00 EUR'],
            'single decimal' => [34.5, 'USD', '34.50 USD'],
            'thousands without grouping' => [1234.5, 'USD', '1234.50 USD'],
            'large amount' => [1234567.89, 'GBP', '1234567.89 GBP'],
            'zero' => [0.0, 'USD', '0.00 USD'],
        ];
    }
}
