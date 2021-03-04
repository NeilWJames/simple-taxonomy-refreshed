# Simple Taxonomy Refreshed

WordPress provides a simple custom taxonomy, this plugin makes it even simpler, removing the need for you to write <em>any</em> code.

* Contributors: nwjames, momo360modena
* Tags: tags, taxonomies, custom taxonomies, taxonomy, category, categories, term conversion, conversion
* Requires at least: 4.8
* Stable tag: 1.3.0
* Tested up to: 5.7
* License: GPLv3 or later
* License URI: https://www.gnu.org/licenses/gpl-3.0.html

## Description

WordPress provides a simple custom taxonomy, this plugin makes it even simpler, removing the need for you to write <em>any</em> code.

Supports adding one or more taxonomies (either hierarchical or tag) to any objects registered on your installation.

This plugin is a functional conversion from [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy/) and developed on WordPress 5.1-5.7 and PHP 7.2-7.4.

This plugin allows you to add a taxonomy just by giving them a name and some options in the backend. It then creates the taxonomy for you and takes care of the URL rewrites.

It provides a widget that you can use to display a "taxonomy cloud" or a list of all the terms; it allows you to show the taxonomy contents at the end of posts and excerpts as well.

You can also create terms easily by typing them into a list; or by copying them from an existing taxonomy.

A tool has been provided to support changing the taxonomy slug. Any terms and their usages will also be renamed.

Options are provided to add a selection dropdown in the admin list and to define term counts using posts of selected statuses (and not just "published").
(The Term Count functionality requires version 1.3+ when WP 5.7+ is installed.)

For those wishing to change its operation, this will require code, a number of filters are available. These are summarised on the [Filters](./filters.md) page.

For full info go the [Simple Taxonomy Refreshed](https://github.com/NeilWJames/simple-taxonomy-refreshed) page.

Also see the [example page](./example.md) to see usage of the update screen and the tools. 

## Frequently Asked Questions

### Does this plugin handle custom fields, roles or post types?

No, it is focused only on registering Taxonomies.

There are a number of very good plugins for these functions.

### There is a very large number of options - are they all needed?

The standard WordPress functionality provides many options and labels - and in the spirit of no-coding, this provides them all.

Very few are required.

Enter just the Name (slug) whether Hierarchical or not and the Post Types used on the Main Options tab and Name (label) on the Labels tab will get you going.

## Migration process

Functionally replaces [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy/) so if this is installed, deactivate it first.

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

* Version 1.3.0
	* NEW: Term counts now implemented by WP 5.7 functionality.
	* NEW: Label filter_by_item supported (introduced in WP 5.7).
	* NEW: Term controls test applied when saving via Rest.
	* FIX: Don't test Term controls during Autosave.
	* FIX: Review Term controls Front End processing.

* Version 1.2.2
	* FIX: Term counts wrong (props @cgzaal)
	* FIX: Hard limits (non-Gutenberg pages) for non-hierarchical tags reviewed

* Version 1.2.1
	* FIX: PHP Error on using rename corrected.

* Version 1.2.0
	* NEW: Add parameter "default_term" to register_taxonomy (introduced in WP 5.5).
	* NEW: Add facility to control minimum and maximum number of terms for a taxonomy to a post.
	* NEW: Add filter 'staxo_term_count_statuses' to extend user-selected post statuses for term counts.
	* NEW: Add contexual help.
	* FIX: PHP Taxonomy dump of term counts corrected.

* Version 1.1.1
	* FIX: Taxonomies saved with versions prior to 1.1.0 would create a PHP warning message.

* Version 1.1.0
	* NEW: Added capability to add dropdown filter for taxonomy in the admin list screens.
	* NEW: Enable term counts to be based on user-selected post statuses.

* Version 1.0.3
	* Inconsistency in treatment of query_var variable corrected (introduced in 1.0.2).
	* User-defined text surrounding custom taxonomies when requested for listing in posts.

* Version 1.0.2
	* Add tool to rename custom taxonomy slug. Terms and usages will be updated as well.

* Version 1.0.1
	* Ensure rewrite rules flushed if parameters require it. (Also affects original plugin.)

* Version 1.0.0 :
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
