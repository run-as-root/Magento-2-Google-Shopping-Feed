## General

Generate feed with product data.

## Installations
```
composer require run_as_root/ext-magento2-google-shopping-feed
bin/magento setup:upgrade
```

## Features

### Feed generation

Generate product feed every 2 hours with minimal required attributes, for each storeview.  
Places file into `pub/run_as_root/feed/%s_store_%s_feed.csv`.

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

| tab     | group   | section               | field              |
|:--------|:--------|:----------------------|:-------------------|
| run_as_root | general | Product Feed Exporter | Enable             |
| run_as_root | general | Product Feed Exporter | Cron Schedule      |
| run_as_root | general | Product Feed Exporter | Category Whitelist |
| run_as_root | general | Product Feed Exporter | Category Blacklist |


## Extensability points
### Add new attribute to feed
1. Create new attribute data provider. @see interface `\RunAsRoot\GoogleShoppingFeed\DataProvider\AttributeHandlers\AttributeHandlerInterface`.
2. Add configuration for new attribute in `\RunAsRoot\GoogleShoppingFeed\Enum\AttributesToImportEnumInterface::ATTRIBUTES`.
