# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- Shipping: each product now gets the single table rate that checkout would charge for it (matched by final price, weight or quantity against the condition value, including the handling fee), per country and per region via `<g:region>`, instead of every `shipping_tablerate` row. Rows of other websites, ZIP-specific rows are skipped, regions of countries without ISO 3166-2 region codes raise the country rate to their highest rate instead of being exported, the carrier's "Ship to Specific Countries" setting is applied, and when a product would exceed Google's limit of 100 entries, the rates from table rate rows for all countries are left out (#35)
- Shipping: no `<g:shipping>` is exported when the table rate carrier is disabled, or for virtual and downloadable products (#35)

### Changed
- `ShippingProvider` has new constructor arguments (`TableRateCarrierConfigProvider`, `MatchingTableRatesProvider`, `StoreManagerInterface`, `LoggerInterface`). This only affects classes that extend it (#35)

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