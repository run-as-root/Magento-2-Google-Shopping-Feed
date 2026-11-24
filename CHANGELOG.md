# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Children of configurable products are no longer exported through the main product collection. They are exported only through their parent, according to the "Configurables Export" setting (#34)
- "Child Products (Variants)" mode (default, previously labelled "Only Visible Child Products") exports every enabled child of a configurable product regardless of its visibility, linked to the parent product and grouped by the parent SKU (#34)
- "Only Parent Products" mode exports only the configurable product, no longer its children (#34)
- A configurable product counts as a parent only when it is enabled, visible individually and assigned to the store view's website. Children of other configurable products are exported as standalone products when they are visible individually, with their own link and `item_group_id` (#34)
- `ParentProductIdProvider::get()` accepts an optional store ID and then returns the lowest ID among the child's exportable parents in that store instead of the first parent found. `ProductsCollectionProvider` and `ParentProductIdProvider` have a new constructor dependency, `ExportableConfigurableParentsCollectionProvider` (#34)
- Children of a configurable product are loaded with one collection query per parent instead of one product load per child. `GenerateFeedForStore` has a new constructor dependency, `ConfigurableChildrenCollectionProvider` (#34)

### Fixed
- Products that are "Not Visible Individually" and are not children of a configurable product (standalone simple products, children of grouped and bundle products) are no longer exported with a link to a page that does not exist (#3, #34)
- Configurable products whose children are not assigned to an exported category no longer lose all their variants in "Child Products (Variants)" mode (#34)
- Children of disabled or not visible configurable products are no longer linked to that parent's product page (#3, #34)
- Virtual children of configurable products are linked to their parent like simple children (#34)
- Children of configurable products are limited to products assigned to the store view's website (#34)

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