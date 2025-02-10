# BrickLink Billbee Integration

This project provides an integration for BrickLink with Billbee, using the BrickLink API and Billbee Custom Shop SDK.

If you are not already signed up on Billbee, please use my [referral link](https://www.billbee.io?via=jens75).

## Features
- Retrieve BrickLink orders and update the order status
- Retrieve BrickLink Inventory with catalogue data
- Retrieve BrickLink shipping profiles
- Sync stock levels between BrickLink and Billbee

## Requirements
- PHP 8.0 or higher
- Composer

## Installation
0. Here are a few guides for installing the requirements:
   - [Apache Web server](https://www.digitalocean.com/community/tutorials/how-to-install-the-apache-web-server-on-ubuntu-20-04)
   - [Let's Encrypt](https://www.digitalocean.com/community/tutorials/how-to-secure-apache-with-let-s-encrypt-on-ubuntu-20-04)
   - [PHP and Composer](https://www.digitalocean.com/community/tutorials/how-to-install-php-8-1-and-set-up-a-local-development-environment-on-ubuntu-22-04)
1. Clone the repository:
   ```sh
   git clone https://github.com/Jens252/billbee-bricklink.git
   cd billbee-bricklink
   ```
2. Install dependencies using Composer:
   ```sh
   composer install
   ```
3. Create a folder `logs` with write permissions for the webserver:
   ```sh
   mkdir logs
   sudo chown -R www-data:www-data logs
   sudo chmod -R g+w logs
   ```
4. Configure the `config.ini` file with your BrickLink API credentials,
   ```ini
   [bricklink]
   consumer_key = "your_consumer_key"
   consumer_secret = "your_consumer_secret"
   token_value = "your_token_value"
   token_secret = "your_token_secret"
   ```
   an API Key for Billbee
   ```ini
   [billbee]
   secret_key = your_billbee_secret_key
   ```
   and adjust the following options as needed:
   ```ini
   [settings]
   max_quantity_for_sets = null
   import_types = SET,GEAR
   import_stockroom = false
   multiple_stockrooms = true
   ```
   - `max_quantity_for_sets`: If `null` it has no effect, otherwise this will be the maximum quantity synchronized to BrickLink (for Sets only, other types are not limited).
   - `import_types`: Comma seperated types which will be imported when using the shop import in Billbee. If included in an order, the remaining items will also be imported. Set to `null` or remove to import all.
   - `import_stockroom`: Select if stockroom items should be imported when using the shop import in Billbee.
   - `multiple_stockrooms`: Is the multiple Stockroom option activated on BrickLink? If `true`, sold out items will be moved to Stockroom C.
5. Important: Set the `src` folder as the document root for apache, not the `BillbeeBricklink` folder, as otherwise the API Keys are exposed.

## Notes
   - If the retain option is not set and an item is sold out, this will likely throw an error in Billbee when the item is sold out. Running `set_retain_option.php` will activate the retain option for all non Stockroom sets.
   - The SKU will be the Set Number for Sets where the Set ID on BrickLink ends with -1, for all other Sets the Set ID from BrickLink. For other types it is the BrickLink item No. preceded by the initial of the BrickLink Type