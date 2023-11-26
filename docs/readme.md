# Simple Taxonomy Refreshed

This plugin provides a no-code facility to manage your taxonomies - either by defining your own or by adding additional function to existing ones.

* Contributors: nwjames, momo360modena
* Tags: tags, taxonomies, custom taxonomies, taxonomy, category, categories, term conversion, conversion
* Stable tag: 3.2.0
* Tested up to: 6.4.1
* License: GPLv3 or later
* License URI: https://www.gnu.org/licenses/gpl-3.0.html

## Description

This plugin provides a no-code process to manage your taxonomies - either by defining your own or by adding additional function to existing ones.

Supports adding one or more taxonomies (either hierarchical or tag) to any objects registered on your installation.

This plugin started as a functional conversion from [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy/) and developed on WordPress 5.1-6.2 and PHP 7.2-8.1.

This plugin allows you to add a taxonomy just by giving them a name and some options in the backend. It then creates the taxonomy for you and takes care of the URL rewrites.

It provides a widget that you can use to display a "taxonomy cloud" or a list of all the terms; it allows you to show the taxonomy contents at the end of posts and excerpts as well. To increase flexibility, a shortcode and block has been provided to output these terms wherever desired.

You can also export the Taxonomy definition to include it directly in your own code.

You can also create terms easily by typing them into a list; or by copying them from an existing taxonomy.

A tool has been provided to support changing the taxonomy slug. Any terms and their usages will also be linked to the renamed slug.

For admin screens displaying multiple taxonomies it is possible to define their display column order.

A tool is provided to merge a number of terms within a taxonomy into a single one. All usages of the selected terms are changed to the merged one.

Options are provided to add a selection dropdown in the admin list and to define term counts using posts of selected statuses (and not just "published").
(The Term Count functionality requires version 1.3+ when WP 5.7+ is installed.) These capabilities are available for any taxonomy whether defined using this taxonomy or elsewhere.

For those wishing to change its operation, this will require code, a number of filters are available. These are summarised on the [Filters](./filters.md) page.

For full info go the [Simple Taxonomy Refreshed](https://github.com/NeilWJames/simple-taxonomy-refreshed) page.

Also see the [example page](./example.md) to see usage of the update screen and the tools. 

When using the admin screen, additional information is available in the help pulldown area.

## Frequently Asked Questions

### Does this plugin handle custom fields, roles or post types?

No, it is focused only on registering and supporting Taxonomies and their terms.

There are a number of very good plugins for these functions.

### There are a very large number of options - are they all needed?

The standard WordPress functionality provides many options and labels - and in the spirit of no-coding, this provides them all.

Very few are required as the default value provides the most-used setting.

Enter just the Name (slug) whether Hierarchical or not and the Post Types used on the Main Options tab and Name (label) on the Labels tab will get you going.

## Migration process

Functionally replaces and extends [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy/) so if this is installed, deactivate it first.

If you are an existing user of the Simple Taxonomy plugin as this plugin uses the Simple Taxonomy options data to save setting it up again completely, before starting you can use the Simple Taxonomy export function to first take a copy of your data.

You should review the parameters to ensure that your needs are correctly set.

**NB.** The Export/Import functions are not compatible between plugins. So you need to use the file with its version of the plugin.

## Installation

**Required - Supported version of PHP.**

1. Download, unzip and upload to your WordPress plugins directory
2. Activate the plugin within you WordPress Administration Backend
3. Go to Settings > Custom Taxonomies and follow the steps on the [Simple Taxonomy Refreshed](https://github.com/NeilWJames/simple-taxonomy-refreshed) page.

See [Usage details](./usage.md) for more information on the usage of the plugin.

## Changelog

See [Change log](./changelog.md) for information on the changes made to the plugin.
