# Changelog

## Version 3.2.0  (27/11/2023)
	* NEW: Support Taxonomies having zero or one term to use radio buttons by use of a "No term" selector.
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
