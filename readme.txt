=== Simple Taxonomy Refreshed ===

Contributors: nwjames, momo360modena
Tags: tags, taxonomies, custom taxonomies, taxonomy, category, block editor
Requires at least: 4.8
Tested up to: 5.3.2
Requires PHP: 5.6
Stable tag: 1.0.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WordPress provides a simple custom taxonomy, this plugin makes it even simpler, removing the need for you to write <em>any</em> code.

== Description ==

WordPress provides a simple custom taxonomy, this plugin makes it even simpler, removing the need for you to write <em>any</em> code.

Add support for taxonomies both hierarchical or simple tags.

Supports adding taxonomy to any objects registered on your installation.

This plugin is a functional conversion from [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy/) and developed on WordPress 5.1/2/3 and PHP 7.2.

This plugin allows you to add taxonomy just by giving them a name and some options in the backend. It then creates the taxonomy for you, takes care of the URL rewrites, provides a widget you can use to display a "taxonomy cloud" or a list of all the stuff in there, and it allows you to show the taxonomy contents at the end of posts and excerpts as well.

You can also export the Taxonomy definition to include it directly in your own code.

You can also create terms easily by typing them into a list; or by copying them from an existing taxonomy.

For full information go the [Simple Taxonomy Refreshed](https://github.com/NeilWJames/simple-taxonomy-refreshed/blob/master/docs/readme.md) page.

== Frequently Asked Questions ==

= Does this plugin handles custom fields? =

No, it is focused only on registering Taxonomies.

== Installation ==

Functionally replaces [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy/) so if this is installed, deactivate it first.

1. Download, unzip and upload to your WordPress plugins directory
2. Activate the plugin within you WordPress Administration Backend
3. Go to Settings > Custom Taxonomies and follow the steps on the [Simple Taxonomy Refreshed](https://github.com/NeilWJames/simple-taxonomy-refreshed/blob/master/docs/addmod.md) page.

== Changelog ==

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

= 1.0.0 =

It is a drop-in replacement for [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy) - using the same options table entry.

If this is installed, deactivate it first.

However since this plugin uses the Simple Taxonomy options data to save setting it up again completely if you wish to revert, before deactivating you can use the Simple Taxonomy export function to take a copy of your data.

**NB.** The Export/Import functions are not compatible between plugins. So you need to use the file made with its version of the plugin.

To have the Taxonomy metaboxes available in the Block Editor, ensure that "show_in_rest" has been set to true.

When migrating and before an update to the existing parameters, the taxonomy will treat "show_in_rest" as true. If not wanted, set to false.