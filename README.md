![build](https://github.com/run-as-root/Magento-2-Google-Shopping-Feed/actions/workflows/test_extension.yml/badge.svg)

## General

Generate feed with product data.

## Installations
```
composer require run_as_root/ext-magento2-google-shopping-feed
bin/magento setup:upgrade
```

### Inventory System Compatibility

This module automatically detects and supports both inventory systems:

- **Multi Source Inventory (MSI)**: If MSI modules are installed, the module will use the MSI system for stock management
- **Legacy Catalog Inventory**: If MSI is not available, the module automatically falls back to the legacy catalog inventory system

**MSI Support (Optional)**:
If you want to use MSI features, install the MSI modules:
```
composer require magento/module-inventory-sales magento/module-inventory-sales-api
```

The module will automatically detect MSI availability and use the appropriate inventory system without any configuration changes.

## Features

### Feed generation

Generate product feed every 2 hours with minimal required attributes, for each storeview.  
Places file into `pub/media/run_as_root/feed/%s_store_%s_feed.xml`.

### Configurable Product Export Control

Configure how configurable products are exported to the feed:
- **Only Parent Products**: Export the configurable product itself with aggregated data from available children (lowest price, stock status based on child availability, parent product images)
- **Only Child Products**: Export child products that are visible individually (visibility levels: Catalog, Search, or Catalog + Search)

## Technical Specification

### Commands

| group | command | description |
|:------|:--------|:------------|
|    run_as_root   |     run_as_root:product-feed:execute    |      Runs feed generation for all store views.       |

### Crons

| group | cronjob | description |
|:------|:--------|:------------|
|    default   |   run_as_root_product_feed_exporter_cron      |   Runs feed generation based on `run_as_root_product_feed/general/cron_schedule` config - default each 15 minutes         |

### Entities

#### Attribute config
DTO: `\RunAsRoot\GoogleShoppingFeed\Data\AttributeConfigData`

Incapsulates the next information:
* attribute name/code
* handler class

List of attributes configurations can be found here: `\RunAsRoot\GoogleShoppingFeed\Enum\AttributesToImportEnumInterface::ATTRIBUTES`

### Data providers

#### `\RunAsRoot\GoogleShoppingFeed\DataProvider\AttributesConfigListProvider`
Provides the list of DTOs ( `AttributeConfigData` ).  
Used for CSV row data mapping from product - provides information regarding attributes that should be taken from the product and in wich way (handler).  
See configuration list here: `\RunAsRoot\GoogleShoppingFeed\Enum\AttributesToImportEnumInterface::ATTRIBUTES`

#### `\RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlerProvider`
Provides attribute data provider (handler) of type `\RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\AttributeHandlerInterface`, by `AttributeConfigData` DTO.

#### `\RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\AttributeHandlerInterface`
Generic interface for attribute data providers.  
Each attribute has its own data provider, that incapsulates current interface.  
Data provider for specific attribute is configured here `\RunAsRoot\GoogleShoppingFeed\Enum\AttributesToImportEnumInterface::ATTRIBUTES`.

### Services

#### `\RunAsRoot\GoogleShoppingFeed\Service\GenerateFeedService`
Perform feed generation for all storeviews with enabled feed generation.  
`\RunAsRoot\GoogleShoppingFeed\Service\GenerateFeedService` is injected.

#### `\RunAsRoot\GoogleShoppingFeed\Service\GenerateFeedService`
Generate feed for specific store, based on feed enable*disable configuration.  
Incapsulates attributes config provider `\RunAsRoot\GoogleShoppingFeed\DataProvider\AttributesConfigListProvider` and csv row mapper `\RunAsRoot\GoogleShoppingFeed\Mapper\ProductToFeedAttributesRowMapper`.  
Performs iteration on all products provided by this collection provider `\RunAsRoot\GoogleShoppingFeed\CollectionProvider\SimpleProductsCollectionProvider` and adds rows into the CSV file.

## Configuration

| tab     | group   | section               | field                 |
|:--------|:--------|:----------------------|:----------------------|
| run_as_root | general | Product Feed Exporter | Enable                |
| run_as_root | general | Product Feed Exporter | Cron Schedule         |
| run_as_root | general | Product Feed Exporter | Category Whitelist    |
| run_as_root | general | Product Feed Exporter | Category Blacklist    |
| run_as_root | general | Product Feed Exporter | Configurables Export  |

### Configurables Export Options

The "Configurables Export" setting controls how configurable products are handled in the feed:

- **Only Child Products** (default): Exports child products that are visible individually (Catalog, Search, or Catalog + Search visibility)
- **Only Parent Products**: Exports the configurable product itself instead of children, with aggregated data:
  - Price: Lowest price from available (enabled and in-stock) children
  - Stock Status: In-stock if at least one child is available
  - Images: Uses the configurable product's base image
  - Skips configurable products with no available children

This setting is configurable per store view to allow different export strategies for different stores.


## Shipping
The `shipping` attribute is exported from the Magento table rate carrier (`Stores > Configuration > Sales > Delivery Methods > Table Rates`):
* Nothing is exported when the table rate carrier is disabled, and nothing for virtual and downloadable products.
* Each product gets the rate that checkout charges for a cart holding only that product: the table rate row with the highest condition value up to the product's final price (`package_value`, `package_value_with_discount`), weight (`package_weight`) or a quantity of 1 (`package_qty`), plus the configured handling fee. Only rows of the store's website are used. Configurable products use the weight of their heaviest enabled child.
* Rates are exported for the allowed countries of the store, limited to the carrier's "Ship to Specific Countries" when that is set.
* One `<g:shipping>` is exported per country, plus one with `<g:region>` per region that has its own rate. Regions are exported only for countries whose Magento region codes are ISO 3166-2 codes (AU, BE, BR, CA, CH, MX, RO, US); region rows of other countries are skipped and the country rate applies. Rows for specific ZIP codes are skipped. Rows for all countries (`*`) apply to every country, with lower priority than country rows, as in checkout.
* Google accepts at most 100 `<g:shipping>` entries per product. Above that, rates from country rows are kept first, then rates from `*` rows in country order, and a warning is logged once per store.
* This is a single-item cart estimate and Google uses it instead of the account shipping settings. If shipping depends on the cart total or weight of multi-item orders, consider leaving the `shipping` attribute out and setting up order-price or weight-based rates in the Merchant Center account shipping settings instead.

Known limitations:
* `package_value` conditions are matched against the product's final price as exported in the feed. With tax-inclusive catalog prices, checkout compares the tax-excluded subtotal instead, so a product close to a tier boundary can get a different tier.
* When the winning rate of a region has a negative price (checkout then offers no table rate there), the region is skipped, so Google applies the country rate to that region.

## Extensability points
### Add new attribute to feed
1. Create new attribute data provider. @see interface `\RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\AttributeHandlerInterface`.
2. Add configuration for new attribute in `\RunAsRoot\GoogleShoppingFeed\Enum\AttributesToImportEnumInterface::ATTRIBUTES`.

### Use a custom shipping data source
The `shipping` attribute only reads the `shipping_tablerate` table. To feed shipping costs from another source (Colissimo, GLS, ...), add a DI `<preference>` for `\RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\ShippingProvider` pointing to your own class implementing `AttributeHandlerInterface`. Its `get(Product $product)` must return a list of `['country' => 'DE', 'price' => '12.00 EUR']` entries, optionally with a `'region' => 'BY'` key (ISO 3166-2 subdivision code without the country prefix), which is exported as `<g:region>`.

## Google Shopping Feeds Grid
Generated feeds could be reviewed inside Admin Backoffice
* Navigate to Marketing -> run_as_root -> Google Shopping Feed
