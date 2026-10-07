=== Simple Taxonomy Refreshed ===

Contributors: nwjames
Tags: tags, taxonomies, custom taxonomies, taxonomy, category
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 4.0.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin provides a no-code facility to manage your taxonomies - either by defining your own or by adding additional function to existing ones.

== Description ==

Supports adding one or more taxonomies (either hierarchical or tag) to any objects registered on your installation just by giving them a name and some options on the admin screens.

It then creates the taxonomy for you linking them to your post types.

This plugin requires WordPress 6.9 or later and PHP 8.2 or later, and is tested with WordPress 6.9 and 7.1 and PHP 8.2 to 8.4.

Optionally you can set values to ease administering your posts, such as:
- Requiring posts to have a minimum and/or maximum number of terms attached.
  This control can be set to operate at different statuses (e.g. whilst draft or only when published)
  When the maximum is set to one taxonomy, the checkbox list will be converted to a set of radio icons
- To define support within WPGraphQL
- Adding taxonomy filters to their post admin screens
- Define which post_statuses contribute to taxonomy counts.
- Where there are multiple taxonomies to define their display column order.

These optional functions can be applied to any taxonomy, not just the taxonomies defined by the plugin.

Blocks supporting taxonomies are available (also as shortcode or widget):
- Taxonomy cloud or list
- List of terms attached to a post

A number of support tools are available for managing your taxonomies:
- Export or Import the configuration.
- Export a taxonomy definition in PHP format so that it can be included directly in your code.
- Rename a taxonomy slug updating the internal references (terms and usages) to the new slug.
- Create terms for a taxonomy by entering them into a list.
- Creating that list by copying terms from an existing taxonomy.
- Merge terms together - updating their usages to be the merged term.

Again the term manipulation tool applies to terms of any taxonomy.

Additional information on plugin usage is available in the help pulldown area of the screens.

For full information go to the [Simple Taxonomy Refreshed documentation](https://github.com/NeilWJames/simple-taxonomy-refreshed/blob/master/docs/readme.md).

To try the plugin without installing it, open the [demo site in WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/NeilWJames/simple-taxonomy-refreshed/master/playground/blueprint.json). It runs in your browser, with example taxonomies, posts and blocks already set up.

== Frequently Asked Questions ==

= Does this plugin handles custom fields? =

No, it is focused only on registering and supporting Taxonomies and their terms.

= There is a very large number of options - are they all needed? =

The standard WordPress functionality provides very many options and labels - and in the spirit of no-coding, this provides them all.

Very few are required. 

Enter just the Name (slug) whether Hierarchical or not and the Post Types used on the Main Options tab and Name (label) on the Labels tab will get you going.

== Installation ==

1. Download, unzip and upload to your WordPress plugins directory
2. Activate the plugin within you WordPress Administration Backend
3. Go to Taxonomies > Add Taxonomy to define a taxonomy, or Taxonomies > All Taxonomies to add functions to an existing one. See the [Taxonomies](https://github.com/NeilWJames/simple-taxonomy-refreshed/blob/master/docs/taxonomies.md) page.

== Changelog ==

* Version 4.0.0  (08/10/2026)

	This version is a significant upgrade made with the use of Claude Code helping to provide a significant test suite.
	Many bugs have been fixed in all elements of the plugin and they are identified in the code repository on GitHub.
	It is marked as a breaking change version since:

	* CHG: Format of the Export function data is changed for security reasons; so files exported with the old version are not compatible.
	* CHG: Blocks had formatting attributes (e.g. Background Colour). Now any theme styling are brought into the block and can be overridden. Existing attributes are ignored.
	* DEV: Code for Term Counting for installations prior to WP 5.7 has been removed. Sites refering to this code from outside the plugin will experience problems.
	* DEV: Minimum WordPress version increased to 6.9.

	Other:

	* Post editing: Terms Control (block editor, classic editor, Quick Edit and REST API)

	* NEW: Block editor: a hierarchical taxonomy whose Terms Control allows one term at most is shown with radio button. When the minimum is not set to one, a "No term" choice is offered.
	* FIX: Term Control "When user cannot change terms give notification message" now applies.
	* FIX: Block editor: the Terms Control notice for a post already outside the limits is now shown.
	* FIX: Block editor: Terms Control checks as terms are changed now work for a taxonomy with its own REST base.
	* FIX: Block editor term limits only report too many terms when the maximum is exceeded.
	* FIX: Editor scripts wait for the page and the block editor iframe to be ready.
	* FIX: REST term-limit check uses the request data, falling back to the post's existing terms.
	* FIX: Classic editor: a one-term taxonomy without a minimum stayed as checkboxes when it had no Most Used terms yet.

	* Front end: post terms, blocks and widget

	* CHG: Display Post Terms and Taxonomy Cloud blocks use the plugin's standard block supports: alignment, text and background colour (including gradients), margin, padding, font size and line height. Link colour is no 
 	* FIX: Post terms display "before", separator and "after" text reviewed.
	* FIX: Taxonomy Cloud block and widget: a cloud showed its terms as a bulleted list, one per line, in themes that do not style tag clouds (such as Twenty Twenty-Five). A small stylesheet now shows them on one line, without bullets, keeping the list for screen readers.
	* FIX: Taxonomy Cloud block: "Maximum number of terms to display" could not be set back to 0 (all terms), and showed 1 for a block that shows all the terms.
	* FIX: Widget no longer raises PHP warnings when its taxonomy is not registered, and shows a message for an empty list.
	* FIX: Display Post Terms and Taxonomy Cloud blocks use block wrapper attributes.
	* FIX: The widget settings lists marked the chosen option with escaped quotes (`selected=&#039;selected&#039;`).
	* DEV: Widget numeric settings are sanitised.

	* Taxonomy settings screens (custom and external taxonomies)

	* NEW: A taxonomy whose REST name (its REST Base, or its name) is already a field of posts in the REST API, such as "format", "status" or "type", or another taxonomy's REST name, is refused when it is added, changed or renamed, and skipped by the configuration import. WordPress leaves such a taxonomy out of the posts' REST data, so the block editor could not set its terms. Setting a different REST Base makes the name usable.
	* CHG: Term Count now available for taxonomies using WordPress's standard count (`_update_post_term_count`) entered in their taxonomy registration, i.e. categories and tags, and also those registered by other plugins.
	* CHG: Term Count for an external taxonomy with a count function of its own: the tab warns that counts made with these options may not match its own, and a new "Use these options" box (setting `st_cb_override`) replaces its count function with WordPress's standard one and the statuses chosen. Without the explicit setting, its own function is kept.
	* FIX: Some PHP warnings removed.
	* FIX: External taxonomy settings saved.
	* FIX: External taxonomies with WPGraphQL turned on caused a fatal error when the taxonomy was registered (the settings were written to the taxonomy object as if it were an array).
	* FIX: The Term Control tab showed nothing after "Current value:" (the taxonomy's Display on admin setting) until that setting was changed.
	* FIX: The taxonomy form's "Display Terms with Posts" and EP_MASK lists marked the chosen option with escaped quotes (`selected=&#039;selected&#039;`).
	* FIX: Changing Hierarchical now updates the Admin List Filter options in all browsers, not just Firefox.
	* FIX: The taxonomy name links on the All Taxonomies page had an empty tooltip (title attribute).
	* FIX: Bold text in the Flush & Delete warning on the settings screen.
	* FIX: Deleting a taxonomy removes it from the admin list orderings.
	* FIX: Export PHP: text from the taxonomy settings can no longer break out of comments in the generated code.
	* FIX: Configuration export keeps any taxonomy missing from the chosen order and ignores names that are not stored taxonomies (they raised PHP warnings and could drop taxonomies from the file).

	* Taxonomy List Order

	* FIX: Taxonomy List Order page script error that stopped sorting.
	* FIX: Taxonomy List Order only accepts the post type's own taxonomies, and the admin list ignores taxonomies no longer shown.
	* FIX: Taxonomy List Order page markup when no post type has more than one taxonomy.

	* Term tools: Terms Import, Terms Merge, Terms Migrate and Rename Slug

	* NEW: Terms Migrate, Terms Import and Terms Merge check each taxonomy's own capabilities: 
	       Terms can only be copied from a taxonomy where the user has its manage_terms capability;
	       They can only be copied or imported into one where the user has its edit_terms capability; 
	       Merging needs its manage_terms, delete_terms and assign_terms capabilities, and also edit_terms when child terms would be moved.
	       Other taxonomies are listed but cannot be chosen. This will only affect taxonomies whose capabilities are not granted to administrators.
	* NEW: Tools ensure that the user has the correct capability
	* NEW: Terms Import lists any lines it skips on loading, with the line number, term and reason.
	* NEW: Terms Merge now asks where the child terms of merged terms should go: under the destination term (the default) or up a level (as before).
	* NEW: Terms Merge lists any posts the merge would leave below the Terms Control minimum, and lets you choose whether to merge anyway.
	* FIX: Terms Import places terms correctly when the first line is indented or a line skips a level.
	* FIX: Terms Import no longer stops with a fatal error when WordPress refuses a term.
	* FIX: Terms Merge now merges and deletes every selected source term, not just the first.
	* FIX: Terms Merge shows the terms-control warning.
	* FIX: Terms Merge term labels select their checkbox or radio button for hierarchical taxonomies.
	* FIX: Terms Merge screen loads the plugin's admin stylesheet, so its term lists are laid out correctly.
	* FIX: Terms Merge page no longer stops with a permissions message when the first taxonomy it checks is one the user cannot merge.
	* FIX: Terms Conversion lists one term per line (it showed `&#013;` between the terms), and names containing `&` are shown as typed.
	* FIX: Terms Migrate page no longer has a script error when a taxonomy cannot be copied to.
	* FIX: Rename reports the correct number of migrated terms.
	* FIX: Rename Slug keeps the taxonomy's default term setting.
	* FIX: Rename Slug uses a new query_var when the old one was the default.

	* Security and permissions

	* NEW: Filter `staxo_can_edit_callbacks` controls who may edit the callback fields.
	* DEV: Callback fields are read-only for users without `unfiltered_html` (super admin on multisite).
	* FIX: Configuration import checks and sanitises each taxonomy as the admin form does. A taxonomy is not imported when its name is not valid, WordPress or another plugin already uses the name, or a setting has a value the form would not store (such as HTML in a label, or a Term Control option out of range); a notice lists them. Settings the plugin does not use are ignored. If nothing in the file is valid, the current configuration is kept.
	* DEV: Capability checks added to merge, convert, configuration export/import, delete and PHP export.
	* DEV: Rename validates the new slug and only renames taxonomies defined by this plugin.
	* DEV: Rename Slug requires the manage_options capability, as its page does.
	* DEV: Terms Migrate: "Copy From" needs the taxonomy's manage_terms capability and "Copy To" its edit_terms capability; other taxonomies are listed but cannot be selected, and the request is checked again when sent.
	* DEV: Terms Import requires the manage_options capability, as its page does, and the taxonomy's edit_terms capability (it accepted the taxonomy's manage_terms alone); other taxonomies are listed but cannot be chosen.
	* DEV: Terms Merge requires the manage_options capability, as its page does, and the taxonomy's manage_terms, delete_terms and assign_terms capabilities (it accepted manage_terms alone); edit_terms is also needed when source terms have child terms to move. Other taxonomies are listed but cannot be selected.
	* DEV: Notice about the original Simple Taxonomy plugin is shown only to administrators in admin.

	* Development

	* DEV: Removed term-count code for WordPress before 5.7, including `SimpleTaxonomyRefreshed_Client::term_count_cb_sel()`, `term_count_query_filter_sel()` and `$wp_version`.
	* DEV: Code checked with PHPStan (level 5).

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

For information on earlier version changes, see the [Simple Taxonomy Refreshed Changes](https://github.com/NeilWJames/simple-taxonomy-refreshed/blob/master/docs/changelog.md) page.
	
== Upgrade Notice ==

= 4.0.0 =

Requires WordPress 6.9. Breaking changes: link colour removed from the blocks, Terms Merge by default will not leave posts below the Terms Control minimum. See the Changelog.

== Migration Notice ==

= From Simple Taxonomy =

It is a drop-in replacement for the now withdrawn [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy) - using the same options table entry.

If this is installed, deactivate it first.

However since this plugin uses the Simple Taxonomy options data to save setting it up again completely if you wish to revert, before deactivating you can use the Simple Taxonomy export function to take a copy of your data.

**NB.** The Export/Import functions are not compatible between plugins. So you need to use the file made with its version of the plugin.

To have the Taxonomy metaboxes available in the Block Editor, ensure that "show_in_rest" has been set to true.

When migrating and before an update to the existing parameters, the taxonomy will treat "show_in_rest" as true. If not wanted, set to false.
