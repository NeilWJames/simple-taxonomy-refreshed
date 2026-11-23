=== Simple Taxonomy Refreshed ===

Contributors: nwjames
Tags: tags, taxonomies, custom taxonomies, taxonomy, category
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 4.0.1
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

To try the plugin without installing it, use the Live Preview button on this page. It opens a demo site in your browser, with this version of the plugin and example taxonomies, posts and blocks already set up. The documentation and its own demo follow the latest development code on GitHub, so they may differ from this version.

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

* Version 4.0.1  (24/11/2026)

	This version is an accessibility review of the plugin: the admin screens, the post editing screens and the front-end output were checked against WCAG 2.2 A and AA, and the problems found are fixed.
	These changes may affect existing sites:

	* CHG: Taxonomy Cloud block: the title is now a heading, level 2 by default. A new "Title heading level" setting chooses level 2 to 6, or "Not a heading" (a paragraph). Themes that styled the plain title text may show it differently.
	* CHG: Taxonomy Cloud block and widget: the smallest text size is now at least 75% (the default is now 80%, was 50%). Smaller saved sizes are shown, and saved by the widget form, as 75%.
	* CHG: Taxonomies defined by the plugin: an empty label is no longer registered as empty; WordPress's default label is used (for example "Choose from the most used tags").

	Other:

	* Post editing: Terms Control (block editor, classic editor, Quick Edit and REST API)

	* FIX: Classic editor and Quick Edit: Terms Control messages are announced to screen readers. When a save is stopped, the reason is announced, and in the classic editor focus moves to the message.
	* FIX: Quick Edit: while the terms are outside the limits, Update is marked unavailable (`aria-disabled`) but stays focusable and is described by the message; using it, or pressing Enter in a field, announces the reason instead of saving.
	* FIX: Classic editor: when a tags box reaches the Terms Control maximum, a note under the field says why no more terms can be added (and is announced to screen readers), and the "most used" tags can no longer be clicked. The note goes when a term is removed.
	* FIX: Block editor: when publishing is blocked by Terms Control, the Publish button is marked unavailable (`aria-disabled`) instead of being disabled, so keyboard focus stays on it, and the reason is announced.
	* FIX: Classic editor and Quick Edit: the radio buttons of a one-term taxonomy are a group named by the taxonomy, and no longer carry a redundant `role`.
	* FIX: Block editor radio term selector: while the terms load, "Loading terms…" is given to screen readers and the list is marked busy.

	* Front end: post terms, blocks and widget

	* FIX: Taxonomy Cloud block and widget, list display: the terms are a plain list. The `grid`, `row` and `link` roles (not valid on a list) are removed, so screen readers announce a list of links.

	* Taxonomy settings screens (custom and external taxonomies)

	* FIX: Taxonomy settings tabs follow the WAI-ARIA tabs pattern: only the selected tab is in the Tab order, the arrow keys, Home and End move between tabs, each panel is named by its tab and can take focus, and focus is visible on every tab (it was hidden on the selected tab).
	* FIX: Field descriptions on the taxonomy form are read by screen readers with their fields (`aria-describedby`), including the read-only note on callback fields.
	* FIX: Add Taxonomy: the Name (slug) field is required, the Add taxonomy button is enabled as the name is typed (not when the field is left), and a note linked to both says why the button is unavailable.
	* FIX: Term Count options: the radio buttons no longer carry `aria-selected` (not valid on them), which screen readers announced wrongly.
	* FIX: Admin List Filter: each filter dropdown on the posts list has a label for screen readers (the taxonomy's "Filter by" label), as WordPress's own category filter has.
	* FIX: Taxonomy form: checkboxes and radio buttons no longer carry ARIA that native controls do not need (`role`, `aria-checked`, `tabindex`, `aria-readonly`), and the Add/Update button lost its `role` and `tabindex` (and the double border that went with them). Screen readers announced some of these wrongly.
	* FIX: Taxonomy form: the groups of checkboxes and radio buttons are named by their header cell (no `<label>` without a field), the empty fieldsets inside them are gone, and the Minimum and Maximum Control settings are whole table rows (they were rows inside another row's cell, which screen readers read as an empty row). Paragraphs that were inside tables, tables inside inline elements and a stray `</p>` are fixed.

	* Taxonomy List Order

	* NEW: Taxonomy List Order and the export order on Configuration Export/Import can be set with the keyboard: each taxonomy has Move up and Move down buttons as well as being draggable, and each move is announced to screen readers.
	* FIX: The Taxonomy List Order post type tabs follow the same keyboard and focus pattern as the taxonomy settings tabs.

	* Term tools: Terms Import, Terms Merge, Terms Migrate and Rename Slug

	* FIX: Terms Import: the Tab key only types a tab when the hierarchy uses tabs, and Escape then Tab (or Shift+Tab) leaves the list, so keyboard users can reach the Import button. A note under the list says so.
	* FIX: Configuration import: the Config file field has a label.
	* FIX: Terms Merge and Rename Taxonomy Slug: "Choose a taxonomy" names its group of radio buttons (a fieldset legend). In Terms Merge the term lists are groups named "Destination term" and "Source terms"; the group around a hierarchical list was built but never output.
	* FIX: Taxonomy Values Migrator: each checkbox is announced with its column and taxonomy ("Copy From Categories"), not only the taxonomy, and the taxonomy cells' ids can no longer clash with other ids on the page.
	* FIX: Export/Import Configuration: "Import config file" is a button that says whether the import form is shown, and moves to the file field when it opens.
	* FIX: Taxonomy List Order: typos in two messages.

	* Development

	* DEV: Development tools updated: `@wordpress/scripts` 37, `@wordpress/env` 12, `@wordpress/e2e-test-utils-playwright` 3, Playwright 1.65 and terser 5.51. They need Node.js 22.22.2 or later (`engines` in `package.json`; was 20).
	* DEV: Composer: `phpunit/phpunit` ^9.6 added, so `composer install` gives `bin/phpunit` (CI keeps its own PHPUnit); `yoast/phpunit-polyfills` limited to ^4.0 (was any version, which could pull in a PHPUnit the WordPress test suite does not support); PHPCSUtils ^1.2.3, the PHPCS installer ^1.2 and PHPStan ^2.3.
	* DEV: Accessibility end-to-end tests (`tests/e2e/specs/accessibility.spec.js`) with `@axe-core/playwright`: WCAG 2.2 A and AA checks of the plugin's admin screens (every tab of the taxonomy form), the posts list filters, the classic editor tags box and the front-end blocks, and keyboard tests of the tabs, Taxonomy List Order, Terms Import and the tags box.
	* DEV: The accessibility e2e tests also apply axe's best-practice rules.
	* DEV: CI runs the PHPUnit tests on PHP 8.5, and on PHP 8.6 with the latest WordPress. The PHP 8.6 job uses setup-php's 8.6 build without code coverage (Xdebug does not yet support PHP 8.6), and its failure does not fail the run.
	* DEV: The release leaves out `README.md` (`.distignore` named it `readme.md`) and `.phpunit.result.cache`.

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

= 4.0.1 =

Accessibility fixes. Changes: the Taxonomy Cloud block title is a heading (level 2, adjustable); the smallest cloud text is 75%; empty taxonomy labels use the WordPress defaults. See the Changelog.

= 4.0.0 =

Requires WordPress 6.9. Breaking changes: link colour removed from the blocks, Terms Merge by default will not leave posts below the Terms Control minimum. See the Changelog.

== Migration Notice ==

= Upgrading to 4.0.1 =

* Taxonomy Cloud block: the title is now a heading, level 2 by default. A new "Title heading level" setting chooses level 2 to 6, or "Not a heading" (a paragraph). Themes that styled the plain title text may show it differently.
* Taxonomy Cloud block and widget: the smallest text size is now at least 75% (the default is now 80%, was 50%). Smaller saved sizes are shown, and saved by the widget form, as 75%.
* Taxonomies defined by the plugin: an empty label is no longer registered as empty; WordPress's default label is used (for example "Choose from the most used tags").

= From Simple Taxonomy =

It is a drop-in replacement for the now withdrawn [Simple Taxonomy](https://wordpress.org/plugins/simple-taxonomy) - using the same options table entry.

If this is installed, deactivate it first.

However since this plugin uses the Simple Taxonomy options data to save setting it up again completely if you wish to revert, before deactivating you can use the Simple Taxonomy export function to take a copy of your data.

**NB.** The Export/Import functions are not compatible between plugins. So you need to use the file made with its version of the plugin.

To have the Taxonomy metaboxes available in the Block Editor, ensure that "show_in_rest" has been set to true.

When migrating and before an update to the existing parameters, the taxonomy will treat "show_in_rest" as true. If not wanted, set to false.
