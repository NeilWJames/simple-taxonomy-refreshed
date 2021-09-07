=== Simple Taxonomy Refreshed ===

Contributors: nwjames, momo360modena
Tags: tags, taxonomies, custom taxonomies, taxonomy, category, block editor
Requires at least: 4.8
Tested up to: 5.8
Requires PHP: 5.6
Stable tag: 2.0.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin provides a no-code facility to manage your taxonomies - either by defining your own or by adding additional function to existing ones.

== Description ==

Supports adding one or more taxonomies (either hierarchical or tag) to any objects registered on your installation.

This plugin started as a functional conversion from [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy/) and developed on WordPress 5.1-5.7 with PHP 7.2-7.4.

This plugin allows you to add a taxonomy just by giving them a name and some options in the backend. It then creates the taxonomy for you and takes care of the URL rewrites.

It provides a widget that you can use to display a "taxonomy cloud" or a list of all the terms; it allows you to show the taxonomy contents at the end of posts and excerpts as well.

You can also export the Taxonomy definition to include it directly in your own code.

You can also create terms easily by typing them into a list; or by copying them from an existing taxonomy.

A tool has been provided to support changing the taxonomy slug. Any terms and their usages will also be linked to the renamed slug.

For admin screens displaying multiple taxonomies it is possible to define their display order.

A tool is provided to merge a number of terms within a taxonomy into a single one. All usages of the terms are changed to the merged one.

Options are provided to add a selection dropdown in the admin list and to define minimum and maximum required term counts using posts of selected statuses (and not only "published"). [Term counts with WP 5.7+ requires version 1.3+ of this plugin.] These capabilities are available for any taxonomy whether defined using this taxonomy or elsewhere.

For full information go the [Simple Taxonomy Refreshed](https://github.com/NeilWJames/simple-taxonomy-refreshed/blob/master/docs/readme.md) page.

== Frequently Asked Questions ==

= Does this plugin handles custom fields? =

No, it is focused only on registering and supporting Taxonomies and their terms.

= There is a very large number of options - are they all needed? =

The standard WordPress functionality provides many options and labels - and in the spirit of no-coding, this provides them all.

Very few are required. 

Enter just the Name (slug) whether Hierarchical or not and the Post Types used on the Main Options tab and Name (label) on the Labels tab will get you going.

== Installation ==

Functionally replaces [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy/) so if this is installed, deactivate it first.

1. Download, unzip and upload to your WordPress plugins directory
2. Activate the plugin within you WordPress Administration Backend
3. Go to Settings > Custom Taxonomies and follow the steps on the [Simple Taxonomy Refreshed](https://github.com/NeilWJames/simple-taxonomy-refreshed/blob/master/docs/addmod.md) page.

== Changelog ==

* Version 2.0.0
	* NEW: Restructure functions to regroup under a Taxonomy menu item.
	* NEW: Add/Modify taxonomy and Export/Import configuration split into separate functions.
	* NEW: Enable extra functions for all taxonomies.
	* NEW: Provide a Merge taxonomy terms function.
	* NEW: Allow Custom Taxonomy terms to be delivered to RSS Feeds.
	* FIX: Server-side terms control errors passed back to Block Editor screens and for Quick Edit.
	* FIX: Help Text reviewed.

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
	* Add tool to rename custom taxonomy slug. Terms and their usages will be remain linked to the taxonomy.
	* Some labels clarified.

* Version 1.0.1
	* Ensure rewrite rules flushed if parameters require it. (Also affects original plugin.)

* Version 1.0.0
	* Initial version using source taken from [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy) by Amaury Balmer.
	* Passed though WP Coding Standards.
	* Incorporates additional fixes made there but not released
	* Now uses json for export/import, so existing exports cannot be used
	* Added most current taxonomy parameters including those that control the display of the taxonomies and ability to use with Block Editor

        This may require review and update of existing configuration on upgrade to this version

	* Removed the option for special metaboxes as standard processing takes care of this (except possibly ensuring that only one term per post is allowed)
	* Nonces and class usage standardised
	
== Migration Notice ==

= From Simple Taxonomy =

It is a drop-in replacement for [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy) - using the same options table entry.

If this is installed, deactivate it first.

However since this plugin uses the Simple Taxonomy options data to save setting it up again completely if you wish to revert, before deactivating you can use the Simple Taxonomy export function to take a copy of your data.

**NB.** The Export/Import functions are not compatible between plugins. So you need to use the file made with its version of the plugin.

To have the Taxonomy metaboxes available in the Block Editor, ensure that "show_in_rest" has been set to true.

When migrating and before an update to the existing parameters, the taxonomy will treat "show_in_rest" as true. If not wanted, set to false.