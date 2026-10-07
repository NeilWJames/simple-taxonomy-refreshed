# Changelog

## Version 4.0.0  (xx/xx/2026)

### Breaking changes

* Requires WordPress 6.9 or later.
* Display Post Terms and Taxonomy Cloud blocks no longer offer link colour. Link colours set on these blocks are lost; the links now use the theme's link colour, which can be changed in the theme's styles.
* Terms Merge moves the child terms of a merged term under the destination term by default. Before, they moved up a level; to keep that, choose "Move them up a level" on the confirmation screen.
* Terms Merge does not merge, by default, when posts would be left below the Terms Control minimum. It lists those posts, and you can choose "Merge anyway".
* Post terms "before", separator and "after" text: HTML is filtered as post content, so tags such as `<script>`, `<style>` and `<iframe>` are removed; plain text is escaped.
* Callback fields can only be edited by users with the `unfiltered_html` capability (super admins on multisite). Use the `staxo_can_edit_callbacks` filter to change this.
* Configuration import no longer stores a file as it is: taxonomies with an invalid name, a name already used by another taxonomy, or settings the admin form would not accept are not imported (a notice lists them), and settings the plugin does not use are dropped.
* Removed for code that calls the plugin directly: `SimpleTaxonomyRefreshed_Client::term_count_cb_sel()`, `term_count_query_filter_sel()` and `$wp_version` (term-count code for WordPress before 5.7).
* Terms Migrate, Terms Import and Terms Merge check each taxonomy's own capabilities: terms can only be copied from a taxonomy where the user has its manage_terms capability, and only copied or imported into one where the user has its edit_terms capability; merging needs its manage_terms, delete_terms and assign_terms capabilities, and also edit_terms when child terms would be moved. Other taxonomies are listed but cannot be chosen. This only affects taxonomies whose capabilities are not granted to administrators.

### Changes

#### Post editing: Terms Control (block editor, classic editor, Quick Edit and REST API)

* NEW: Block editor: a hierarchical taxonomy whose Terms Control allows one term at most is shown with radio buttons, as in the classic editor, with its search and Add New Term form (the term checkboxes are replaced through the `editor.PostTaxonomyType` filter). Without a minimum, a "No term" choice is offered.
* FIX: Term Control "When user cannot change terms give notification message" did nothing: the taxonomy was left out of the controls, so it got neither the notice nor radio buttons (maximum 1). It now gives both; saving is still never blocked.
* FIX: Terms Control "published and scheduled only" (type 1) is applied; drafts were being checked as for type 2.
* FIX: Block editor: the Terms Control notice for a post already outside the limits was never shown (its script was added after the page head had been printed).
* FIX: Block editor: Terms Control notices showed HTML entities, such as `&#039;` for an apostrophe in the taxonomy label.
* FIX: Block editor notices no longer break when a label or translation contains a quote.
* FIX: Block editor: Terms Control checks as terms are changed now work for a taxonomy with its own REST base.
* FIX: Block editor term limits only report too many terms when the maximum is exceeded.
* FIX: Publish sidebar is disabled and re-enabled correctly when term limits are not met.
* FIX: Editor scripts wait for the page and the block editor iframe to be ready.
* FIX: REST term-limit check uses the request data, falling back to the post's existing terms.
* FIX: Terms Control no longer raises a PHP warning when all terms are removed in the classic editor.
* FIX: Terms Control no longer raises PHP errors for external taxonomies that are not registered or have no post types selected.
* FIX: Classic editor: a one-term taxonomy without a minimum stayed as checkboxes when it had no Most Used terms yet (adding the "No term" choice stopped the script).

#### Front end: post terms, blocks and widget

* FIX: Post terms "before", separator and "after" text is escaped (plain text) or filtered as post HTML when displayed.
* FIX: Post terms display adds the space after the "before" text when the separator has spaces (such as the default ", ").
* FIX: Spacing check for the "after" text of post terms.
* FIX: Display Post Terms and Taxonomy Cloud blocks only use block wrapper attributes while one of their own blocks is rendering, so their output can also be produced outside a block.
* FIX: Display Post Terms and Taxonomy Cloud blocks use the plugin's standard block supports: alignment, text and background colour (including gradients), margin, padding, font size and line height. Link colour is no longer offered.
* FIX: Taxonomy Cloud block and widget: a cloud showed its terms as a bulleted list, one per line, in themes that do not style tag clouds (such as Twenty Twenty-Five). A small stylesheet now shows them on one line, without bullets, keeping the list for screen readers.
* FIX: Taxonomy Cloud block: "Maximum number of terms to display" could not be set back to 0 (all terms), and showed 1 for a block that shows all the terms.
* FIX: Widget no longer raises PHP warnings when its taxonomy is not registered, and shows a message for an empty list.
* FIX: The widget settings lists marked the chosen option with escaped quotes (`selected=&#039;selected&#039;`).
* DEV: Widget numeric settings are sanitised.

#### Taxonomy settings screens (custom and external taxonomies)

* NEW: A taxonomy whose REST name (its REST Base, or its name) is already a field of posts in the REST API, such as "format", "status" or "type", or another taxonomy's REST name, is refused when it is added, changed or renamed, and skipped by the configuration import. WordPress leaves such a taxonomy out of the posts' REST data, so the block editor could not set its terms. Setting a different REST Base makes the name usable.
* NEW: Term Count for an external taxonomy with a count function of its own: the tab warns that counts made with these options may not match its own, and a new "Use these options" box (setting `st_cb_override`) replaces its count function with WordPress's standard one and the statuses chosen. Without the tick, its own function is kept.
* FIX: Term Count now works for taxonomies that use WordPress's standard count, such as categories and tags, and those registered by other plugins with `_update_post_term_count`; the options were not offered for them.
* FIX: Term Count "Selection" for an external taxonomy raised PHP warnings for each status left unticked (unticked boxes were not saved).
* FIX: Term counts for external taxonomies that are not yet registered no longer raise PHP warnings.
* FIX: No PHP warning when counting terms on a site with no external taxonomies configured.
* FIX: Editing an external taxonomy now loads its saved settings.
* FIX: Saving an external taxonomy whose Term Control maximum or minimum was not used (or a flat taxonomy, whose Admin List Filter hierarchy options are disabled) left those settings out, so PHP warnings could stop the save returning to the list and appear on post lists. Missing settings now take their defaults, also for settings saved by earlier versions.
* FIX: External taxonomies with WPGraphQL turned on caused a fatal error when the taxonomy was registered (the settings were written to the taxonomy object as if it were an array).
* FIX: The Term Control tab showed nothing after "Current value:" (the taxonomy's Display on admin setting) until that setting was changed.
* FIX: The taxonomy form's "Display Terms with Posts" and EP_MASK lists marked the chosen option with escaped quotes (`selected=&#039;selected&#039;`).
* FIX: Changing Hierarchical now updates the Admin List Filter options in all browsers, not just Firefox.
* FIX: Admin list filter no longer raises a PHP warning for an external taxonomy that is not registered.
* FIX: The taxonomy name links on the All Taxonomies page had an empty tooltip (title attribute).
* FIX: Bold text in the Flush & Delete warning on the settings screen.
* FIX: Deleting a taxonomy removes it from the admin list orderings.
* FIX: PHP deprecation notice when adding the first taxonomy on a site.
* FIX: Export PHP: text from the taxonomy settings can no longer break out of comments in the generated code.
* FIX: Configuration export keeps any taxonomy missing from the chosen order and ignores names that are not stored taxonomies (they raised PHP warnings and could drop taxonomies from the file).

#### Taxonomy List Order

* FIX: Taxonomy List Order page script error that stopped sorting.
* FIX: Taxonomy List Order cache used the wrong cache group, so it was never reused.
* FIX: Taxonomy List Order keeps an order saved for a single post type (it was discarded).
* FIX: Taxonomy List Order only accepts the post type's own taxonomies, and the admin list ignores taxonomies no longer shown.
* FIX: Taxonomy List Order page markup when no post type has more than one taxonomy.

#### Term tools: Terms Import, Terms Merge, Terms Migrate and Rename Slug

* NEW: Terms Import lists any lines it skips, with the line number, term and reason.
* NEW: Terms Merge asks where the child terms of merged terms should go: under the destination term (the default) or up a level (as before).
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

#### Security and permissions

* NEW: Filter `staxo_can_edit_callbacks` controls who may edit the callback fields.
* FIX: Configuration import checks and sanitises each taxonomy as the admin form does. A taxonomy is not imported when its name is not valid, WordPress or another plugin already uses the name, or a setting has a value the form would not store (such as HTML in a label, or a Term Control option out of range); a notice lists them. Settings the plugin does not use are ignored. If nothing in the file is valid, the current configuration is kept.
* DEV: Capability checks added to merge, convert, configuration export/import, delete and PHP export.
* DEV: Callback fields are read-only for users without `unfiltered_html` (super admin on multisite).
* DEV: Rename validates the new slug and only renames taxonomies defined by this plugin.
* DEV: Rename Slug requires the manage_options capability, as its page does.
* DEV: Terms Migrate: "Copy From" needs the taxonomy's manage_terms capability and "Copy To" its edit_terms capability; other taxonomies are listed but cannot be selected, and the request is checked again when sent.
* DEV: Terms Import requires the manage_options capability, as its page does, and the taxonomy's edit_terms capability (it accepted the taxonomy's manage_terms alone); other taxonomies are listed but cannot be chosen.
* DEV: Terms Merge requires the manage_options capability, as its page does, and the taxonomy's manage_terms, delete_terms and assign_terms capabilities (it accepted manage_terms alone); edit_terms is also needed when source terms have child terms to move. Other taxonomies are listed but cannot be selected.
* DEV: Notice about the original Simple Taxonomy plugin is shown only to administrators in admin.

#### Development

* DEV: Minimum WordPress version increased to 6.9.
* DEV: Removed term-count code for WordPress before 5.7, including `SimpleTaxonomyRefreshed_Client::term_count_cb_sel()`, `term_count_query_filter_sel()` and `$wp_version`.
* DEV: Export PHP code is built by `SimpleTaxonomyRefreshed_Admin::build_php_export()`.
* DEV: Configuration export file content is built by `SimpleTaxonomyRefreshed_Admin_Config::build_config_export()`.
* DEV: New `build:editor` script builds the block editor radio term selector (`src/editor`) to `build/editor`; `npm run build` includes it.
* DEV: JavaScript reviewed with wp-scripts lint-js.
* DEV: Code checked with PHPStan (level 5).
* DEV: PHPUnit test suite (240 tests) with shared fixtures, covering configuration import and export, Terms Import, Terms Merge, Terms Conversion, Rename Slug, term counts, taxonomy add, update and delete, the taxonomy settings screens, external taxonomies, Export PHP, Terms Control on save and on the post screens, front-end output, the widget, Taxonomy List Order, and a capability and nonce check of every admin handler.
* DEV: End-to-end tests with Playwright (WordPress Playground locally, wp-env with Docker in CI): the Add and Edit Taxonomy screens, Terms Control in the block editor, classic editor and Quick Edit, radio buttons, and Terms Merge.

## Version 3.4.1  (03/09/2026)

* FIX: Taxonomy counts work within iFramed content.
* FIX: Restrict addition of terms to content/excerpt for main pages only.
* DEV: Tested against WP 7.1.
* DEV: Reviewed for Plugin Check 2.1.

## Version 3.4.0  (08/08/2026)

* NEW: Blocks use API V3 and block.json.
* FIX: Correct type of Hierarchy Depth.
* FIX: Permit Term Controls to work for sub-set of possible post types.
* DEV: Tested against WP 7.0.
* DEV: Reviewed for WP Coding Standards 3.4.
* DEV: Reviewed for Plugin Check 2.0.

## Version 3.3.0  (06/12/2024)

* DEV: Ensure option autoloaded.
* DEV: Review of list_table ordering processing.
* DEV: Reviewed for WP Coding Standards 3.1.
* FIX: Register against CPT created after init process run.

## Version 3.2.0  (29/11/2023)

* NEW: Taxonomies that have zero or one term will use radio buttons by using a "No term" selector.
* DEV: Reviewed for WP Coding Standards 3.0.
* DEV: Minimum supported version of PHP increased to 7.4.
* FIX: PHP 8.1 undefined array error.
* FIX: Tested with WP version 6.4.

## Version 3.1.1  (11/08/2023)

* NEW: JS register uses defer with WP 6.3 onwards. 
* FIX: PHP 8.1 deprecation error.
* FIX: Tested with WP version 6.3.

## Version 3.1.0 (11/04/2023)

* NEW: Term controls can be applied to a subset of post types only.
* NEW: Labels that are the same as the WP defaults are not saved.
* FIX: PHP 8.1 error with array map on null.
* FIX: Tested with WP version 6.2.

## Version 3.0.0 (17/11/2022)

* NEW: Post taxonomy lists may use html tags to format text.
* NEW: Term controls extended to Quick Edit options.
* NEW: Controls defined as operating as terms are entered now works.
* NEW: Further accessibility changes made to administration screens.
* NEW: Term control front end logic moved from page to js file.
* FIX: Server-side Tag Term Counts incorrect.

## Version 2.3.0 (24/08/2022)

* NEW: Export configuration allows taxonomies to be ordered (and so will be in this order on re-import).
* NEW: Further accessibility changes made to administration screen.

## Version 2.2.0 (06/06/2022)

* NEW: Accessibility changes made to administration screen.
* NEW: Shortcode `staxo_post_terms` and Block for displaying Terms attached to post.
* NEW: Some common css and js code moved from inline to separate files.

## Version 2.1.0 (15/02/2022)

* NEW: Taxonomy widget upgraded and extended to be able to be invoked as a block.
* FIX: Some a11y issues addressed.
* FIX: Term Counts for non_WP External Taxonomies may not have worked.

## Version 2.0.0 (10/07/2021)

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

## Version 1.3.0 (05/03/2021)

* NEW: Term counts now implemented by WP 5.7 functionality.
* NEW: Label filter_by_item supported (introduced in WP 5.7).
* NEW: Term controls test applied when saving via Rest.
* FIX: Don't test Term controls during Autosave.
* FIX: Review Term controls Front End processing.

## Version 1.2.2 (30/10/2021)

* FIX: Term counts wrong (props @cgzaal)
* FIX: Hard limits (non-Gutenberg pages) for non-hierarchical tags reviewed

## Version 1.2.1 (20/10/2021)

* FIX: PHP Error on using rename corrected.

## Version 1.2.0 (04/09/2020)

* NEW: Add parameter "default_term" to register_taxonomy (introduced in WP 5.5).
* NEW: Add facility to control minimum and maximum number of terms for a taxonomy to a post.
* NEW: Add filter 'staxo_term_count_statuses' to extend user-selected post statuses for term counts.
* NEW: Add contexual help.
* FIX: PHP Taxonomy dump of term counts corrected.

## Version 1.1.1 (14/04/2020)

* FIX: Taxonomies saved with versions prior to 1.1.0 would create a PHP warning message.

## Version 1.1.0 (18/03/2020)

* NEW: Added capability to add dropdown filter for taxonomy in the admin list screens.
* NEW: Enable term counts to be based on user-selected post statuses.

## Version 1.0.3 (14/02/2020)

* Inconsistency in treatment of query_var variable corrected (introduced in 1.0.2).
* User-defined text surrounding custom taxonomies when requested for listing in posts.

## Version 1.0.2 (25/01/2020)

* Add tool to rename custom taxonomy slug. Terms and usages will be updated as well.

## Version 1.0.1 (13/01/2020)

* Ensure rewrite rules flushed if parameters require it. (Also affects original plugin.)

## Version 1.0.0 (15/12/2019)

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
