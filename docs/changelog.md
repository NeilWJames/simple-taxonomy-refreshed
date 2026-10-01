# Changelog

## Version 4.0.0  (xx/xx/2026)

* NEW: Filter `staxo_can_edit_callbacks` controls who may edit the callback fields.
* NEW: Terms Import lists any lines it skips, with the line number, term and reason.
* NEW: Terms Merge moves the child terms of the merged terms under the destination term (previously they moved up a level).
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
* DEV: Capability checks added to merge, convert, configuration export/import, delete and PHP export.
* DEV: Callback fields are read-only for users without `unfiltered_html` (super admin on multisite).
* DEV: Rename validates the new slug and only renames taxonomies defined by this plugin.
* DEV: Widget numeric settings are sanitised.
* DEV: Notice about the original Simple Taxonomy plugin is shown only to administrators in admin.
* DEV: Minimum WordPress version increased to 6.9.
* DEV: Removed term-count code for WordPress before 5.7, including `SimpleTaxonomyRefreshed_Client::term_count_cb_sel()`, `term_count_query_filter_sel()` and `$wp_version`.
* DEV: JavaScript reviewed with wp-scripts lint-js.
* DEV: Code checked with PHPStan (level 5).
* DEV: PHPUnit test suite with shared fixtures covering configuration import, Terms Import, Terms Merge, term counts, taxonomy add/update/delete, Export PHP, Terms Conversion, Rename Slug, Terms Control and front-end output.
* DEV: Export PHP code is built by `SimpleTaxonomyRefreshed_Admin::build_php_export()`.

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
