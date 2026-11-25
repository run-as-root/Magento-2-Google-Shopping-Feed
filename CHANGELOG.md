# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.8.0] - 2026-11-25

### Added
- `g:sale_price` is exported when the final price (special price, catalog price rules for the NOT LOGGED IN customer group) is lower than the regular price (#36)

### Fixed
- Shipping: each product gets the single table rate that checkout would charge for it, per country and per region (`<g:region>`), instead of every `shipping_tablerate` row. Rows of other websites and ZIP-specific rows are skipped, and the carrier's "Ship to Specific Countries" setting is applied (#35)
- Shipping: no `<g:shipping>` is exported when the table rate carrier is disabled, or for virtual and downloadable products (#35)
- Shipping: when a product would exceed Google's limit of 100 shipping entries, the rates from table rate rows for all countries are left out (#35)
- Products that are "Not Visible Individually" and are not children of a configurable product are no longer exported with a link to a page that does not exist (#3, #34)
- Configurable products whose children are not assigned to an exported category no longer lose all their variants in "Child Products (Variants)" mode (#34)
- Children of disabled or not visible configurable products are no longer linked to that parent's product page (#3, #34)
- Virtual children of configurable products are linked to their parent like simple children, and children are limited to the store view's website (#34)
- `g:price` contains the regular price instead of the discounted final price, so Google can show the sale (#36)
- Catalog price rules were ignored when the feed was generated from the CLI command or cron (#36)
- Configurable parent products take their price from their cheapest salable child instead of the storefront price renderer, which could add display tax adjustments and convert a non-base currency twice (#36)

### Changed
- Children of configurable products are exported only through their parent, according to the "Configurables Export" setting (#34):
  - "Child Products (Variants)" (default, previously labelled "Only Visible Child Products") exports every enabled child regardless of its visibility, linked to the parent and grouped by the parent SKU
  - "Only Parent Products" exports only the configurable product, no longer its children
- A configurable product counts as a parent only when it is enabled, visible individually and assigned to the store view's website. Children of other configurable products are exported as standalone products when they are visible individually (#34)
- Children of a configurable product are loaded with one collection query per parent instead of one product load per child (#34)
- The module now depends on `Magento_Customer` (#35)
- Constructor changes, relevant only to classes that extend these classes:
  - `ShippingProvider`: new `TableRateCarrierConfigProvider`, `MatchingTableRatesProvider`, `StoreManagerInterface`, `LoggerInterface`, `SingleItemGuestCartPriceProvider` (#35)
  - `PriceProvider`: new required `ProductPricesProvider` (#36)
  - `ProductsCollectionProvider` and `ParentProductIdProvider`: new `ExportableConfigurableParentsCollectionProvider` (#34)
  - `GenerateFeedForStore`: new `ConfigurableChildrenCollectionProvider` (#34)
- `ParentProductIdProvider::get()` accepts an optional store ID and then returns the lowest ID among the child's exportable parents in that store (#34)

### Deprecated
- The unused `RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\SpecialPriceProvider` virtual type; use `SalePriceProvider` (#36)

## [1.7.0] - 2026-11-17

### Added
- "Configurables Export" system configuration option (per store view) to export configurable parent products or their child products, optionally only visible children (#3)
- Documentation for custom shipping data sources (#18)

### Fixed
- Fatal error when MSI is disabled; the module no longer depends on MSI (#10)
- Media gallery data is added after the store filter and pagination (#17)
- The `no_selection` product image is treated as missing (#19)
- Wrong ACL resource in `system.xml` (#20)
- Duplicate items in the feed caused by `array_merge` discarding product ID keys (#29)
- Prices are formatted as `34.00 USD` independent of the store locale (#30)
- PHP 8.4 deprecation: explicit nullable type for the command name constructor parameter (#21)

### Changed
- PHPUnit 10 compatibility and PHPStan fixes

## [1.6.0] - 2025-06-03

### Fixed
- `Missing required argument $directory`: `FilesystemIterator` argument `path` replaced with `directory` (#1)

## [1.5.0] - 2023-06-13

### Added
- GitHub Actions pipeline running code style checks and unit tests (#5)

## [1.4.0] - 2022-09-22

### Added
- Admin grid listing recently generated feeds (Marketing -> run_as_root -> Google Shopping Feeds)

## [1.3.0] - 2022-09-21

### Changed
- Default cron execution time
- Added product type attribute handler
