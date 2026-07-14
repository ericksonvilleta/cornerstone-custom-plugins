# Hide Categories and Products for Woocommerce
Contributors: bastho, leroysabrina, agencenous, enzomangiante  
Donate link: https://apps.avecnous.eu/produit/masquer-les-categories-et-produits-pour-woocommerce/?mtm_campaign=wp-plugin&mtm_kwd=hide-categories-products-wc&mtm_medium=wp-repo&mtm_source=donate  
Tags: woocommerce, categories, product, hide, storefront  
Requires at least: 4.9.7  
Tested up to: 7.0  
Author URI: https://apps.avecnous.eu/?mtm_campaign=wp-plugin&mtm_kwd=hide-categories-products-wc&mtm_medium=wp-repo&mtm_source=author  
Stable tag: 1.3.0  
Requires PHP: 7.4  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hide Categories and Products for Woocommerce. This plugins requires WooCommerce to be installed and activated

## Description

With this plugin, you can hide on the store products from a chosen category and hide a category itself, in list or on single page.
It is compatible with Storefront (theme) shortcodes.

### How to use

1. Go to Woocommerce -> Settings -> Products.
2. Go to the subsection "Hide from categories".
3. There is twice the list of the product categories currently on your store. First list allows you to hide the categories on the store. Second list allows you to hide products from those categories on the store.

### Documentation

[APIGEN](https://apps.avecnous.eu/docs/hide-categories-products-woocommerce/)

[Video Tutorial (EN)](https://www.youtube.com/watch?v=Jj3CexyPp2A)

[Tutoriel Vidéo (FR)](https://www.youtube.com/watch?v=IbB_mdwOCLM)

## Installation

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

## Frequently Asked Questions

### How can I report security bugs?
You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/wordpress/plugin/hide-categories-products-woocommerce/vdp)

### Will hidding a category hide the products from that category on the store ?

Nope, this is why there is twice the categories list.
First list allows you to hide the categories on the store. Second list allows you to hide products from those categories on the store.

### Will hidding the products from a category hide the category on the store ?

Nope, this is why there is twice the categories list.
First list allows you to hide the categories on the store. Second list allows you to hide products from those categories on the store.
However, it is worth to note that, if your guests click on said category they will find an empty page, as all the products of that category are hidden on the store.

## Screenshots

1. A look at the setting page

## Changelog

### 1.3.0

- Alter posts_clauses, make it work in WC product collections
- Show shown/hidden status in admin table
- Reliability: Use ids instead of slugs for products

### 1.2.10

- Add security report instruction in FAQ
- Add Woocommerce as required plugin

### 1.2.9

- Comptability with WooCommerce Custom Order Table (High-Performance Order Storage)

### 1.2.8

- Fix missing product in search

### 1.2.7

- Do not filter searches on admin

### 1.2.6

- Manage deleted category case, remove php warning
- Add missing categories in log 

### 1.2.5

- Move js in static folder

### 1.2.4

- Hide categories in search results
- fix filemtime() errors

### 1.2.3

- Remove localization files

### 1.2.2

- Fix Compatibility with Woocommerce Product Addons on Add to Cart

### 1.2.1

- Compatibility with Woocommerce Product Addons

### 1.2

- Fix translation
- Improve presentation in backoffice


## Upgrade notice

### 1.3.0

For reliability, storage of "hide from categories" moves from slug to id. You'll need to set hidden products after upgrade.