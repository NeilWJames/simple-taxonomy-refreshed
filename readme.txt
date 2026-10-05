=== Simple Taxonomy Refreshed ===

Contributors: nwjames, momo360modena
Tags: tags, taxonomies, custom taxonomies, taxonomy, category
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 4.0.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin provides a no-code facility to manage your taxonomies - either by defining your own or by adding additional function to existing ones.

== Description ==

Supports adding one or more taxonomies (either hierarchical or tag) to any objects registered on your installation.

This plugin started as a functional conversion from [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy/) (now closed). It requires WordPress 6.9 or later and PHP 8.2 or later, and is tested up to WordPress 7.1.

This plugin allows you to add a taxonomy just by giving them a name and some options in the backend. It then creates the taxonomy for you and takes care of the URL rewrites.

It provides a widget that you can use to display a "taxonomy cloud" or a list of all the terms; it allows you to show the taxonomy contents at the end of posts and excerpts as well. To increase flexibility, a shortcode and block has been provided to output these terms wherever desired.

You can also export the Taxonomy definition to include it directly in your own code.

You can also create terms easily by typing them into a list; or by copying them from an existing taxonomy.

A tool has been provided to support changing the taxonomy slug. Any terms and their usages will also be linked to the renamed slug.

For admin screens displaying multiple taxonomies it is possible to define their display column order.

A tool is provided to merge a number of terms within a taxonomy into a single one. All usages of the selected terms are changed to the merged one.

Options are provided to add a selection dropdown in the admin list and to define minimum and maximum required term counts using posts of selected statuses (and not only "published"). These capabilities are available for any taxonomy whether defined using this taxonomy or elsewhere.

For full information go the [Simple Taxonomy Refreshed](https://github.com/NeilWJames/simple-taxonomy-refreshed/blob/master/docs/readme.md) page.

When using the admin screen, additional information is available in the help pulldown area.

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

* Version 4.0.0  (xx/xx/2026)
	* NEW: Filter `staxo_can_edit_callbacks` controls who may edit the callback fields.
	* NEW: Terms Import lists any lines it skips, with the line number, term and reason.
	* NEW: Terms Merge asks where the child terms of merged terms should go: under the destination term (the default) or up a level (as before).
	* NEW: Terms Merge lists any posts the merge would leave below the Terms Control minimum, and lets you choose whether to merge anyway.
	* FIX: Terms Merge now merges and deletes every selected source term, not just the first.
	* FIX: Block editor notices no longer break when a label or translation contains a quote.
	* FIX: Editor scripts wait for the page and the block editor iframe to be ready.
	* FIX: Block editor term limits only report too many terms when the maximum is exceeded.
	* FIX: Publish sidebar is disabled and re-enabled correctly when term limits are not met.
	* FIX: REST term-limit check uses the request data, falling back to the post's existing terms.
	* FIX: Editing an external taxonomy now loads its saved settings.
	* FIX: Terms Merge shows the terms-control warning.
	* FIX: Spacing check for the "after" text of post terms.
	* FIX: Taxonomy List Order page script error that stopped sorting.
	* FIX: Taxonomy List Order cache used the wrong cache group, so it was never reused.
	* FIX: Bold text in the Flush & Delete warning on the settings screen.
	* FIX: Widget no longer raises PHP warnings when its taxonomy is not registered, and shows a message for an empty list.
	* FIX: Term counts for external taxonomies that are not yet registered no longer raise PHP warnings.
	* FIX: Changing Hierarchical now updates the Admin List Filter options in all browsers, not just Firefox.
	* FIX: Terms Import places terms correctly when the first line is indented or a line skips a level.
	* FIX: Terms Import no longer stops with a fatal error when WordPress refuses a term.
	* FIX: Deleting a taxonomy removes it from the admin list orderings.
	* FIX: Rename reports the correct number of migrated terms.
	* FIX: Terms Merge term labels select their checkbox or radio button for hierarchical taxonomies.
	* FIX: No PHP warning when counting terms on a site with no external taxonomies configured.
	* FIX: Rename Slug keeps the taxonomy's default term setting.
	* FIX: Rename Slug uses a new query_var when the old one was the default.
	* FIX: Export PHP: text from the taxonomy settings can no longer break out of comments in the generated code.
	* FIX: Terms Conversion lists one term per line (it showed `&#013;` between the terms), and names containing `&` are shown as typed.
	* FIX: PHP deprecation notice when adding the first taxonomy on a site.
	* FIX: Terms Control "published and scheduled only" (type 1) is applied; drafts were being checked as for type 2.
	* FIX: Terms Control no longer raises a PHP warning when all terms are removed in the classic editor.
	* FIX: Terms Control no longer raises PHP errors for external taxonomies that are not registered or have no post types selected.
	* FIX: Post terms display adds the space after the "before" text when the separator has spaces (such as the default ", ").
	* FIX: Post terms "before", separator and "after" text is escaped (plain text) or filtered as post HTML when displayed.
	* FIX: Admin list filter no longer raises a PHP warning for an external taxonomy that is not registered.
	* FIX: Display Post Terms and Taxonomy Cloud blocks only use block wrapper attributes while one of their own blocks is rendering, so their output can also be produced outside a block.
	* FIX: Display Post Terms and Taxonomy Cloud blocks use the plugin's standard block supports: alignment, text and background colour (including gradients), margin, padding, font size and line height. Link colour is no longer offered.
	* FIX: Terms Merge screen loads the plugin's admin stylesheet, so its term lists are laid out correctly.
	* FIX: Taxonomy List Order keeps an order saved for a single post type (it was discarded).
	* FIX: Taxonomy List Order only accepts the post type's own taxonomies, and the admin list ignores taxonomies no longer shown.
	* DEV: Capability checks added to merge, convert, configuration export/import, delete and PHP export.
	* DEV: Callback fields are read-only for users without `unfiltered_html` (super admin on multisite).
	* DEV: Rename validates the new slug and only renames taxonomies defined by this plugin.
	* DEV: Rename Slug requires the manage_options capability, as its page does.
	* DEV: Terms Migrate: "Copy From" needs the taxonomy's manage_terms capability and "Copy To" its edit_terms capability; other taxonomies are listed but cannot be selected, and the request is checked again when sent.
	* DEV: Terms Import requires the manage_options capability, as its page does, and the taxonomy's edit_terms capability (it accepted the taxonomy's manage_terms alone); other taxonomies are listed but cannot be chosen.
	* DEV: Terms Merge requires the manage_options capability, as its page does, and the taxonomy's manage_terms, delete_terms and assign_terms capabilities (it accepted manage_terms alone); edit_terms is also needed when source terms have child terms to move. Other taxonomies are listed but cannot be selected.
	* FIX: Terms Merge page no longer stops with a permissions message when the first taxonomy it checks is one the user cannot merge.
	* FIX: Terms Migrate page no longer has a script error when a taxonomy cannot be copied to.
	* DEV: Widget numeric settings are sanitised.
	* DEV: Notice about the original Simple Taxonomy plugin is shown only to administrators in admin.
	* DEV: Minimum WordPress version increased to 6.9.
	* DEV: Removed term-count code for WordPress before 5.7, including `SimpleTaxonomyRefreshed_Client::term_count_cb_sel()`, `term_count_query_filter_sel()` and `$wp_version`.
	* DEV: JavaScript reviewed with wp-scripts lint-js.
	* DEV: Code checked with PHPStan (level 5).
	* DEV: PHPUnit test suite (154 tests) with shared fixtures covering configuration import, Terms Import, Terms Merge, term counts, taxonomy add/update/delete, Export PHP, Terms Conversion, Rename Slug, Terms Control, front-end output, Taxonomy List Order and a capability/nonce sweep of every admin handler.
	* DEV: Export PHP code is built by `SimpleTaxonomyRefreshed_Admin::build_php_export()`.
	* DEV: Configuration export file content is built by `SimpleTaxonomyRefreshed_Admin_Config::build_config_export()`.
	* DEV: End-to-end tests with Playwright against wp-env (Playground runtime locally, Docker in CI), starting with the Add Taxonomy screen.
	* FIX: Configuration export keeps any taxonomy missing from the chosen order and ignores names that are not stored taxonomies (they raised PHP warnings and could drop taxonomies from the file).
	* FIX: Taxonomy List Order page markup when no post type has more than one taxonomy.
	* FIX: The taxonomy name links on the All Taxonomies page had an empty tooltip (title attribute).
	* FIX: External taxonomies with WPGraphQL turned on caused a fatal error when the taxonomy was registered (the settings were written to the taxonomy object as if it were an array).
	* FIX: Term Count now works for taxonomies that use WordPress's standard count, such as categories and tags, and those registered by other plugins with `_update_post_term_count`; the options were not offered for them.
	* NEW: Term Count for an external taxonomy with a count function of its own: the tab warns that counts made with these options may not match its own, and a new "Use these options" box (setting `st_cb_override`) replaces its count function with WordPress's standard one and the statuses chosen. Without the tick, its own function is kept.
	* FIX: The taxonomy form's "Display Terms with Posts" and EP_MASK lists marked the chosen option with escaped quotes (`selected=&#039;selected&#039;`).
	* FIX: Term Count "Selection" for an external taxonomy raised PHP warnings for each status left unticked (unticked boxes were not saved).
	* NEW: Block editor: a hierarchical taxonomy whose Terms Control allows one term at most is shown with radio buttons, as in the classic editor, with its search and Add New Term form (the term checkboxes are replaced through the `editor.PostTaxonomyType` filter). Without a minimum, a "No term" choice is offered.
	* FIX: Block editor: the Terms Control notice for a post already outside the limits was never shown (its script was added after the page head had been printed).
	* FIX: Block editor: Terms Control notices showed HTML entities, such as `&#039;` for an apostrophe in the taxonomy label.
	* FIX: Block editor: Terms Control checks as terms are changed now work for a taxonomy with its own REST base.
	* DEV: New `build:editor` script builds the block editor radio term selector (`src/editor`) to `build/editor`; `npm run build` includes it.

* Version 3.4.1  (03/09/2026)
	* FIX: Taxonomy counts work within iFramed content.
	* FIX: Restrict addition of terms to content/excerpt for main pages only.
	* DEV: Tested against WP 7.1.
	* DEV: Reviewed for Plugin Check 2.1.

* Version 3.4.0  (08/08/2026)
	* NEW: Blocks use API V3 and block.json.
	* FIX: Correct type of Hierarchy Depth.
	* FIX: Permit Term Controls to work for sub-set of possible post types.
	* DEV: Tested against WP 7.0.
	* DEV: Reviewed for WP Coding Standards 3.4.
	* DEV: Reviewed for Plugin Check 2.0.

* Version 3.3.0  (06/12/2024)
	* DEV: Ensure option autoloaded.
	* DEV: Review of list_table ordering processing.
	* DEV: Reviewed for WP Coding Standards 3.1.
	* FIX: Register against CPT created after init process run.

For information on earlier version changes, see the [Simple Taxonomy Refreshed Changes](https://github.com/NeilWJames/simple-taxonomy-refreshed/blob/master/docs/changelog.md) page.
	
== Upgrade Notice ==

= 4.0.0 =

Requires WordPress 6.9. Breaking changes: link colour removed from the blocks, Terms Merge moves child terms under the destination and by default will not leave posts below the Terms Control minimum, post terms text is filtered. See the Migration Notice.

== Migration Notice ==

= Upgrading to 4.0.0 =

Version 4.0.0 has these breaking changes:

* Requires WordPress 6.9 or later.
* Display Post Terms and Taxonomy Cloud blocks no longer offer link colour. Link colours set on these blocks are lost; the links now use the theme's link colour, which can be changed in the theme's styles.
* Terms Merge moves the child terms of a merged term under the destination term by default. Before, they moved up a level; to keep that, choose "Move them up a level" on the confirmation screen.
* Terms Merge does not merge, by default, when posts would be left below the Terms Control minimum. It lists those posts, and you can choose "Merge anyway".
* Post terms "before", separator and "after" text: HTML is filtered as post content, so tags such as `<script>`, `<style>` and `<iframe>` are removed; plain text is escaped.
* Callback fields can only be edited by users with the `unfiltered_html` capability (super admins on multisite). Use the `staxo_can_edit_callbacks` filter to change this.
* Removed for code that calls the plugin directly: `SimpleTaxonomyRefreshed_Client::term_count_cb_sel()`, `term_count_query_filter_sel()` and `$wp_version` (term-count code for WordPress before 5.7).
* Terms Migrate, Terms Import and Terms Merge check each taxonomy's own capabilities: terms can only be copied from a taxonomy where the user has its manage_terms capability, and only copied or imported into one where the user has its edit_terms capability; merging needs its manage_terms, delete_terms and assign_terms capabilities, and also edit_terms when child terms would be moved. Other taxonomies are listed but cannot be chosen. This only affects taxonomies whose capabilities are not granted to administrators.

= From Simple Taxonomy =

It is a drop-in replacement for the now withdrawn [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy) - using the same options table entry.

If this is installed, deactivate it first.

However since this plugin uses the Simple Taxonomy options data to save setting it up again completely if you wish to revert, before deactivating you can use the Simple Taxonomy export function to take a copy of your data.

**NB.** The Export/Import functions are not compatible between plugins. So you need to use the file made with its version of the plugin.

To have the Taxonomy metaboxes available in the Block Editor, ensure that "show_in_rest" has been set to true.

When migrating and before an update to the existing parameters, the taxonomy will treat "show_in_rest" as true. If not wanted, set to false.
