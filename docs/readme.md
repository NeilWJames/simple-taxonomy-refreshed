# Simple Taxonomy 2

WordPress provides a simple custom taxonomy, this plugin makes it even simpler, removing the need for you to write <em>any</em> code.

* Contributors: 
* Tags: tags, taxonomies, custom taxonomies, taxonomy, category, categories, hierarchical, termmeta, meta, term meta, term conversion, conversion
* Requires at least: 4.8
* Stable tag: 1.0
* Tested up to: 5.2.3
* License: GPLv3 or later
* License URI: https://www.gnu.org/licenses/gpl-3.0.html

## Description

WordPress provides a simple custom taxonomy, this plugin makes it even simpler, removing the need for you to write <em>any</em> code.

Add support for hierarchical taxonomies or simple tags.

Supports adding taxonomy to any object registered on your installation.

This plugin was converted and developed on WordPress 5.1/5.2/5.3 and PHP 7.2.

This plugin allows you to add taxonomy just by giving them a name and some options in the backend. It creates the taxonomy for you, takes care of the URL rewrites, provides a widget you can use to display a "taxonomy cloud" or a list of the terms, and it allows you to show the taxonomy contents at the end of posts and excerpts as well.

You can also create terms easily by typing them into a list; or by copying them from an existing taxonomy.

Functionally replaces [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy/)

For those wishing to code, a number of filters are available. These are summarised on the [Filters](./filters.md) page.

For full info go the [Simple Taxonomy 2](https://github.com/NeilWJames/simple-taxonomy-2) page.

Also see the [example page](./example.md) to see usage of the update screen and the tools. 

## Frequently Asked Questions

### Does this plugin handle custom fields, roles or post types?

No, it is focused only on registering Taxonomies.

There are a number of very good plugins for these functions.


## Migration process

Functionally replaces [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy/) so if this is installed, deactivate it first.

If you are an existing user of the Simple Taxonomy plugin as this plugin uses the Simple Taxonomy options data to save setting it up again completely, before starting you can use the Simple Taxonomy export function to first take a copy of your data.

You should review the parameters to ensure that your needs are correctly set.

**NB.** The Export/Import functions are not compatible between plugins. So you need to use the file with its version of the plugin.

## Installation

**Required - Supported version of PHP.**

1. Download, unzip and upload to your WordPress plugins directory
2. Activate the plugin within you WordPress Administration Backend
3. Go to Settings > Custom Taxonomies and follow the steps on the [Simple Taxonomy 2](https://github.com/NeilWJames/simple-taxonomy-2) page.

See [Usage details](./usage.md) for more information on the usage of the plugin.

## Changelog

* Version 1.0 :
	* Initial version with source taken from [Simple Taxonomy](https://github.com/herewithme/simple-taxonomy)
	* Incorporates additional fixes made there but not released
	* Passed though WP Coding Standards. This has many significant changes to the naming and structure of the code
	* Now uses json for export/import, so existing exports cannot be used to import into this version or vice versa.
	* Added most current taxonomy parameters including those that control the display of the taxonomies (including block editor)

        This may require review and update of existing configuration on upgrade to this version
	* Removed an amount of code where processing is now in core Wordpress (but this generally requires a parameter to be set).
	* Removed special metaboxes as current standard processing takes care of this (except possibly ensuring one term only per post is allowed)
	* Nonces and class usage standardised.
	* Fixed the code to display custom terms on their posts when requested.
	* Terms Import reviewed and can use tabs to denote the hierarchy.
	* Tools to copy terms from one taxonomy to another reviewed.
