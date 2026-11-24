# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `g:sale_price` is exported when the final price (special price, catalog price rules for the NOT LOGGED IN customer group) is lower than the regular price (#36)

### Fixed
- `g:price` contains the regular price instead of the discounted final price, so Google can show the sale (#36)
- Catalog price rules were ignored when the feed was generated from the CLI command or cron (#36)
- Configurable parent products no longer take their price from the storefront price renderer, which could add display tax adjustments and convert a non-base currency twice; they use the regular and final price of their cheapest available child (#36)

### Deprecated
- The unused `RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\SpecialPriceProvider` virtual type; use `SalePriceProvider` (#36)

## [1.1.0] - 2024-07-27

### Added
- New "Configurables Export" system configuration option to control how configurable products are exported to the feed
- Support for exporting configurable parent products instead of child products
- Support for exporting only visible child products (Catalog/Search/Both visibility levels)
- Per-store view configuration for different configurable product export strategies

### Changed
- Refactored configurable product handling logic in GenerateFeedForStore service
- Separated configurable and grouped product processing for better maintainability
- Enhanced configurable product export with proper price aggregation, stock status calculation, and image handling

### Technical Details
- Added ConfigurableExportType source model for configuration dropdown options
- Extended FeedConfigProvider to include new configuration setting
- Updated system configuration XML with new field
- Comprehensive unit test coverage for all new functionality
- Maintained backward compatibility with existing feed generation behavior