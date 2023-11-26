=== Simple Taxonomy Refreshed ===

Contributors: nwjames, momo360modena
Tags: tags, taxonomies, custom taxonomies, taxonomy, category, block editor
Tested up to: 6.4.1
Requires PHP: 7.4
Stable tag: 3.2.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin provides a no-code facility to manage your taxonomies - either by defining your own or by adding additional function to existing ones.

== Description ==

Supports adding one or more taxonomies (either hierarchical or tag) to any objects registered on your installation.

This plugin started as a functional conversion from [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy/) and developed on WordPress 5.1-6.3 with PHP 7.2-8.1.

This plugin allows you to add a taxonomy just by giving them a name and some options in the backend. It then creates the taxonomy for you and takes care of the URL rewrites.

It provides a widget that you can use to display a "taxonomy cloud" or a list of all the terms; it allows you to show the taxonomy contents at the end of posts and excerpts as well. To increase flexibility, a shortcode and block has been provided to output these terms wherever desired.

You can also export the Taxonomy definition to include it directly in your own code.

You can also create terms easily by typing them into a list; or by copying them from an existing taxonomy.

A tool has been provided to support changing the taxonomy slug. Any terms and their usages will also be linked to the renamed slug.

For admin screens displaying multiple taxonomies it is possible to define their display column order.

A tool is provided to merge a number of terms within a taxonomy into a single one. All usages of the selected terms are changed to the merged one.

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

* Version 3.2.0  (27/11/2023)
	* NEW: Support Taxonomies having zero or one term to use radio buttons by use of a "No term" selector.
	* DEV: Reviewed for WP Coding Standards 3.0.
	* DEV: Minimum supported version of PHP increased to 7.4.

* Version 3.1.1  (11/08/2023)
	* NEW: JS register uses defer with 6.3 onwards. 
	* FIX: PHP 8.1 deprecation error.
	* FIX: Tested with WP version 6.3.

* Version 3.1.0  (11/04/2023)
	* NEW: Term controls may be applied to only a sub-set of post types.
	* NEW: Labels that are the same as the WP defaults are not saved.
	* FIX: PHP 8.1 error with array map on null.
	* FIX: Tested with WP version 6.2.

* Version 3.0.0  (17/11/2022)
	* NEW: Post taxonomy lists may use html tags to format text.
	* NEW: Term controls extended to Quick Edit options.
	* NEW: Controls defined as operating as terms are entered now works.
	* NEW: Further accessibility changes made to administration screens.
	* NEW: Term control front end logic moved from page to js file.
	* FIX: Server-side Tag Term Counts incorrect.

* Version 2.3.0 (24/08/2022)
	* NEW: Export configuration allows taxonomies to be ordered (and so will be in this order on re-import).
	* NEW: Further accessibility changes made to administration screen.

* Version 2.2.0 (06/06/2022)
	* NEW: Accessibility changes made to administration screen.
	* NEW: Shortcode `staxo_post_terms` and Block for displaying Terms attached to post.
	* NEW: Some common css and js code moved from inline to separate files.

* Version 2.1.0 (15/02/2022)
	* NEW: Taxonomy widget upgraded and extended to be able to be invoked as a block.
	* FIX: Some a11y issues addressed.
	* FIX: Term Counts for non_WP External Taxonomies may not have worked.

* Version 2.0.0 (10/07/2021)
	* NEW: Taxonomy labels that are default values are not saved with options.
	* NEW: Taxonomy labels use core translations for default values rather plugin-specific ones.
	* NEW: Support of Description labels and 'rest_namespace' introduced with WP 5.9.
	* NEW: Support of `item_link` and `item_link_description` labels introduced with WP 5.8.
	* NEW: Restructure functions to regroup them under a Taxonomy menu item.
	* NEW: Add/Modify taxonomy and Export/Import configuration split into separate functions.
	* NEW: Enable extra functions for all taxonomies.
	* NEW: Provide a Merge taxonomy terms function.
	* NEW: Deliver Custom Taxonomy terms to RSS Feeds.
	* FIX: Server-side terms control errors passed back to Block Editor screens and for Quick Edit.
	* FIX: Help Text reviewed. (Also github documentation.)

For information on earlier version changes, go the [Simple Taxonomy Refreshed Changes](https://github.com/NeilWJames/simple-taxonomy-refreshed/blob/master/docs/changelog.md) page.
	
== Migration Notice ==

= From Simple Taxonomy =

It is a drop-in replacement for [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy) - using the same options table entry.

If this is installed, deactivate it first.

However since this plugin uses the Simple Taxonomy options data to save setting it up again completely if you wish to revert, before deactivating you can use the Simple Taxonomy export function to take a copy of your data.

**NB.** The Export/Import functions are not compatible between plugins. So you need to use the file made with its version of the plugin.

To have the Taxonomy metaboxes available in the Block Editor, ensure that "show_in_rest" has been set to true.

When migrating and before an update to the existing parameters, the taxonomy will treat "show_in_rest" as true. If not wanted, set to false.
