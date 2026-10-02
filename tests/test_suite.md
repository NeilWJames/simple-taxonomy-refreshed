# STR Test Suite

Summary of the PHPUnit test suite for Simple Taxonomy Refreshed. **Keep this file up to date whenever tests, fixtures or helpers are added or changed** (see `CLAUDE.md`).

Full design and rationale: `STR-test-suite-plan.md` in the Claude project "STR Plugin Development".

_Last updated: 1 Oct 2026_

## Status

| Item | State |
|---|---|
| Tests written | 169 in 12 classes (A–K + the original main test) |
| Run in CI | All 154 pass (8,682 assertions) — GitHub CI run #127, 2 Oct 2026. Not yet run: 3 block-supports, 3 merge child-terms and 9 capability tests (Terms Import, Terms Migrate, Terms Merge) |
| Still to write | None — plan complete (see "Planned" for follow-ups) |

## Running

```bash
bash script/install-wp-tests wordpress_test root '' localhost latest
composer install
vendor/bin/phpunit -c phpunit9.xml                  # all
vendor/bin/phpunit -c phpunit9.xml --group merge    # one area
WP_MULTISITE=1 vendor/bin/phpunit -c phpunit9.xml   # multisite
```

Groups: `config`, `import`, `merge`, `assignment`, `taxonomy`, `convert`, `rename`, `control`, `frontend`, `order`, `security`.

CI (`.github/workflows/ci.yml`) runs the suite on PHP 8.2–8.4 × WP 6.9/latest, plus multisite on PHP 8.4 / WP latest.

## Files

| File | Purpose |
|---|---|
| `bootstrap.php` | Loads the plugin on `muplugins_loaded`, the WP test library, then the helpers below |
| `trait-staxo-fixtures.php` | `STaxo_Fixtures` — shared fixtures and helpers |
| `class-staxo-test-case.php` | `STaxo_Test_Case` (extends `WP_UnitTestCase`) |
| `class-staxo-ajax-test-case.php` | `STaxo_Ajax_Test_Case` (extends `WP_Ajax_UnitTestCase`); hooks merge/convert AJAX handlers |
| `class-staxo-redirect-exception.php` | Thrown in place of `wp_redirect()` by `expect_redirect()` |
| `class-test-*.php` | Test classes (only files with this prefix are run) |
| `files/` | Fixtures (below) |
| `phpstan-bootstrap.php` | Constants for PHPStan (not part of PHPUnit) |

## Fixtures

**Taxonomies** — `files/staxo-config-suite.json`, loaded through the plugin's config import:

| Taxonomy | Hier. | Objects | Settings |
|---|---|---|---|
| `test_hier` | yes | post | auto = content, before "Test Terms:" |
| `test_flat` | no | post, page | auto = both, `rest_base` `flat-terms`, default term `Unsorted` |
| `test_cntl` | no | post | Terms control: type 2, hard, min 1, max 2 |
| `test_count` | yes | post | Term count: type 2, publish + draft |

Other config files: `staxo-config-test-hier.json` (single taxonomy), `staxo-config-test-callback.json` (callback fields), `staxo-config-bad.json` (no header).

**Terms** — loaded through Terms Import:

- `terms-hier-tab.txt` / `terms-hier-space.txt` → `test_hier`, `test_count`: Music › Jazz › Bebop, Big Band; Music › Rock › Punk; Art › Painting, Sculpture; Misc
- `terms-flat.txt` → `test_flat`, `test_cntl`: red, green, blue, yellow, cyan
- Term meta `staxo_test_meta` on Bebop (`test_hier`) and blue (`test_flat`)

**Posts** — `make_posts()`:

| Post | Status | test_hier | test_flat | test_cntl | test_count |
|---|---|---|---|---|---|
| P1 | publish | Jazz | red, green | red | Jazz |
| P2 | publish | Bebop | green | green | Bebop |
| P3 | publish | Jazz, Bebop | blue | red, blue | Jazz |
| P4 | publish | Big Band | red, blue | blue | — |
| P5 | draft | Rock | yellow | — | Rock |
| P6 | pending | Punk | yellow | — | Rock |
| P7 | private | Painting | cyan | — | Painting |
| P8 | future | Sculpture | Unsorted (default) | — | Sculpture |
| P9 | trash | Jazz | red | — | Jazz |
| PG1 | publish (page) | — | red | — | — |

## Helpers (`STaxo_Fixtures`)

| Helper | Does |
|---|---|
| `login( $role )` | Creates and logs in a user (super admin on multisite for administrator) |
| `import_config( $file, $register = true )` | Config import handler, then registers taxonomies; returns settings errors |
| `import_terms( $taxonomy, $file_or_text, $hierarchy )` | Terms Import handler; returns settings errors |
| `load_fixture()` | All four taxonomies + terms + term meta |
| `make_posts()` | The post matrix; `$this->posts['P1']` etc. |
| `term()`, `term_exists_by_name()`, `term_tree()` | Term lookups by name; `term_tree()` gives name ⇒ parent name |
| `post_terms( $post, $taxonomy )` | Sorted term names, read from the database |
| `term_count( $taxonomy, $name )` | Stored count, cache cleared |
| `messages( $errors, $type )` | Settings-error messages of one type |
| `clear_settings_errors()` | Empties `$wp_settings_errors` (the only place the global is written, with a phpcs:ignore) |
| `expect_redirect()` | `wp_redirect()` throws `STaxo_Redirect_Exception` |
| `register_locked_taxonomy()` | Registers `test_locked` (hierarchical, term "Locked term") with its own capabilities `manage_locked`, `edit_locked`, `delete_locked`, `assign_locked`, which no role has; on multisite the current user stops being a super admin. Unregistered by `reset_staxo()` |
| `grant_caps( ...$caps )` | Adds capabilities to the current user |
| `ajax()`, `merge_phase()`, `merge()` | AJAX base only: call a handler / a merge phase / a full merge |

## Test classes

### A. Config import — `class-test-staxo-refreshed-config.php` (`@group config`, 8)

Import saves the option; taxonomy registered with its settings; suite file registers all four taxonomies (rest_base, default term, terms-control cache); a second import replaces the first; bad file rejected; editor refused; callback fields kept/cleared by `staxo_can_edit_callbacks`.

### B. Taxonomy admin — `class-test-staxo-taxonomy-admin.php` (`@group taxonomy`, 19)

Add taxonomy (stored, registered at next init, redirect `message=added`); form values sanitised (name via `sanitize_title`, labels, `st_before` via kses); default WP labels not stored; core/STR names refused; update (labels, count type, status choices cleared unless type 2); update of unknown refused; callback fields protected; editor and bad nonce refused. Delete keeps terms and relationships; flush-delete removes terms, relationships and meta (but WordPress keeps a taxonomy's default term); delete updates `list_order`; unknown / editor / bad nonce refused. Export PHP (`build_php_export()`): valid PHP for all fixture taxonomies (`token_get_all( …, TOKEN_PARSE )`); values in comments cannot become code (review L3); name exported as a string literal and safe function name; editor / unknown taxonomy refused.

### C. Terms Import — `class-test-staxo-terms-import.php` (`@group import`, 18)

Flat list; tab and space hierarchies; re-import creates nothing; same name under two parents; blank lines ignored (flat and hierarchical); hierarchy into flat taxonomy rejected; indented first line → top level; skipped level → nearest ancestor; skipped-line warning notice (line number, term, reason; children of a skipped term skipped; name escaped); term named `0`; unknown taxonomy, subscriber, editor and bad nonce rejected; a taxonomy without the user's edit_terms capability refused (and accepted once granted) and shown disabled in the page's list.

### D. Term assignment and counts — `class-test-staxo-term-assignment.php` (`@group assignment`, 14)

Fixture matrix check; add term (published / draft); remove term; remove last term; delete term (children move up); default term not deletable and given to new posts; default counts (published only); STR count rules type 2 (publish + draft), with trash, type 1 (all except trash); counts follow status changes; `staxo_term_count_statuses` filter.

### E. Terms Merge — `class-test-staxo-merge.php` (`@group merge`, 31)

Phase one lists terms / shows terms-control warning; phase two disables destination (flat) and destination + ancestors (hierarchical, label `for` matches input `id`); phase three filters invalid sources; when a source has child terms (hierarchical only), phase three offers "under the destination" (default) or "up a level"; single source (duplicate row, meta deleted, count); several sources (review H1); parent term (children move under the destination, or up a level when chosen; with "up a level" a destination below the source is not moved); parent and child together; destination below a source (moved up first, no loop); flat across posts and pages (object cache cleared); count rules respected; terms control kept; posts the merge would take below the terms-control minimum listed in phase three with a Do not merge / Merge anyway choice (default: do not merge), merge refused or done accordingly, type 1 ignores drafts; no valid sources; invalid / other-taxonomy destination; invalid taxonomy; subscriber; bad nonce; capabilities: the page disables taxonomies without manage_terms, delete_terms and assign_terms, each of delete_terms and assign_terms required, and edit_terms required only when a source has child terms (refused in phase three and phase four, nothing deleted; a source without children merges).

### F. Terms Conversion — `class-test-staxo-convert.php` (`@group convert`, 11)

Hierarchical → hierarchical (space-indented tree, imported tree identical); hierarchical → flat (sorted flat list); flat → hierarchical (top level, includes default term); posts not moved; one term per line (no `&#013;`); a name with `&` escaped once, shown as typed and imported unchanged; unknown taxonomy and editor refused; the page disables Copy From without the taxonomy's manage_terms and Copy To without its edit_terms; copying from without manage_terms, or to without edit_terms, refused.

### G. Rename slug — `class-test-staxo-rename.php` (`@group rename`, 11)

Rename moves definition, `term_taxonomy` rows and posts' terms, hierarchy kept, "Done, 10 terms were migrated."; query_var (empty / equal to new slug → default; a new value is kept even when the old one was the default); `<taxonomy>_children` and `default_term_<taxonomy>` options move; `list_order` updated; new rewrite slug stored and flush scheduled; term cache cleared; invalid new slugs refused (empty, > 32, same, existing — review M6); core and unknown taxonomies refused; subscriber and bad nonce refused.

### H. Terms Control on save — `class-test-staxo-terms-control.php` (`@group control`, 17)

Control cache (values as integers; posts only; none when hard control off or post type not selected). Classic editor (`check_taxonomy_value_set()`): minimum and maximum redirect with `staxo_error`; 1–2 terms accepted; hidden `0` and "No term" `-1` not counted, comma list counted; type 2 checks drafts, type 1 only published/scheduled; new/auto-draft/trash, empty title and other post types not checked; quick edit outputs the error and stops; through `wp_update_post()` the save is stopped and terms unchanged. REST (`check_taxonomy_value_rest()`): create with too few / too many terms refused (403 `rest_minimum_terms` / `rest_maximum_terms`); update without the taxonomy uses the current terms (review M7); removing all terms refused; type 1 vs 2 on drafts; batch requests checked per item. Error notice after a refused save, and none without a valid nonce.

### I. Front-end output — `class-test-staxo-front-end.php` (`@group frontend`, 26)

Terms after the content (taxonomies set to content/both) and excerpt (excerpt/both) of a single post in the main loop, not elsewhere; pages only get page taxonomies; "not found" HTML comment for a post without terms; plain-text before/separator/after get spaces at the joins and are escaped; a plain-text separator with end spaces stays plain text; HTML before/after limited to post HTML (no `<script>`). Shortcode `[staxo_post_terms tax="…"]` (any display setting; empty for an unknown taxonomy or outside a single post's loop); Display Post Terms block. Feeds (rss2, atom, rdf) for taxonomies set to show in feeds. Admin list filter dropdown for the selected post types; unregistered external taxonomy ignored. Widget list (counts, terms without posts hidden), minimum posts incl. non-numeric values (review M3), cloud (minimum posts, no font sizes for equal counts), escaped title, fallback to tags, settings sanitised on save; Taxonomy Cloud block, and its `ordering` attribute used as the order; `get_block_attributes()` returns the wrapper attributes only while one of the plugin's own blocks renders (the display functions work, without the wrapper div, when called directly or inside another dynamic block). Block supports: both blocks are registered with the standard supports list (align; color with gradients; spacing margin and padding; typography fontSize and lineHeight), and each support (preset and custom values) is applied to the block's single wrapper div. The block tests are skipped if `build/blocks` is missing.

### J. Taxonomy List Order — `class-test-staxo-order.php` (`@group order`, 8)

Fixture columns (posts: core + four test taxonomies; pages: test_flat); saving a new order for one post type is kept (it was dropped when only one post type had its own order); the saved order is applied through `manage_taxonomies_for_{post_type}_columns`; saving the default stores nothing; unknown names, duplicates and non-strings dropped and missing taxonomies added at the end; a value that is not a list ignored; taxonomies no longer shown left out of the columns and new ones added; editor and bad nonce refused.

### K. Security sweep — `class-test-staxo-security.php` (`@group security`, 4)

Thirteen state-changing handlers, each called as its screen would call it: config import and export, add/update taxonomy, update external, delete, flush-delete, Export PHP, Terms Import, Terms Merge, Terms Conversion, Rename Slug, Taxonomy List Order. Roles below each handler's minimum (administrator for all) are refused with a permissions message; logged-out visitors are refused; an administrator with a bad nonce is refused; nothing changes in any refused case (option, terms and term relationships compared before and after). No `wp_ajax_nopriv_` actions and no REST routes are registered (review finding M4).

### Main — `class-test-staxo-refreshed-main.php` (2)

Multisite flag as expected; plugin loaded.

## Planned

The test plan (A–K) is complete. Follow-ups:

| Area | Notes |
|---|---|
| Config import sanitising | Review finding M1 is still open: imported configurations are stored without the form's sanitising |
| Browser tests | Block-editor JavaScript (notices, radio buttons, iframe) needs e2e tests (Playwright) |

## Behaviour decided for tests

- Merging a parent term moves its children under the destination term by default (decided 1 Oct 2026); the user can choose to move them up a level instead, as before 4.0.0 (decided 2 Oct 2026). If the destination is below a source and children go under it, it is first moved up to the source's parent.
- Terms control minimum (decided 1 Oct 2026): phase three lists the posts whose term count the merge would reduce below the minimum, and offers "Do not merge" (default) or "Merge anyway". Posts already below the minimum and not changed by the merge are not listed. Type 1 controls count published/scheduled posts only.
- Terms Import: an indented first line is top level; skipped terms are reported with line numbers.
- Rename query_var: an empty value or one equal to the new slug means the default (the new taxonomy name); any other value entered is stored.
- Post terms display: before/separator/after text with no `<` is plain text (escaped, spaces added at the joins); anything else is HTML filtered with `wp_kses_post()`.
- Flush-delete leaves a taxonomy's default term (WordPress `wp_delete_term()` refuses to delete it).

## Change log

- **1 Oct 2026** — Shared fixtures and base classes; classes A (rewritten), C, D, E. Fixed hierarchical merge input id (`tax<ID>` → `tax_<ID>`).
- **1 Oct 2026** — phpcs: `$GLOBALS['wp_settings_errors']` writes moved into `clear_settings_errors()`; assignment alignment fixed in `import_config()`.
- **1 Oct 2026** — First CI run of the new classes. `test_fixture_matrix` exposed a plugin bug: `term_count_sel_cache()` read `$options['externals']` when no external taxonomies were configured ("Undefined array key" warning whenever a taxonomy STR doesn't manage, such as `category`, was counted). Fixed in `class-simpletaxonomyrefreshed-client.php`; every fixture-based test (classes D and E) depends on it.
- **1 Oct 2026** — Terms Merge now moves the children of each source term under the destination (`SimpleTaxonomyRefreshed_Admin_Merge::move_children()`); phase three says so for hierarchical taxonomies. Merge tests updated and 3 added (parent and child together, destination below a source, no notice for flat).
- **1 Oct 2026** — Terms Merge checks the terms-control minimum: lists affected posts, "Do not merge" / "Merge anyway" choice (`min_control()`, `posts_below_minimum()`, `list_posts_below()`). 5 merge tests added.
- **1 Oct 2026** — Classes B (taxonomy admin, 19), F (conversion, 7), G (rename, 11). Plugin changes made for them: rename moves `default_term_<slug>` (was `default_taxonomy_<slug>`) and the `<slug>_children` option through the options API (cache-safe); rename keeps an entered query_var when the old one was the default; Export PHP code built by the new `SimpleTaxonomyRefreshed_Admin::build_php_export()`, with comment values made safe and the name exported as a string literal (review L3).
- **1 Oct 2026** — phpcs: taxonomy-admin test helpers build the request in a local array and assign it to `$_POST` / `$_GET` / `$_REQUEST`, instead of reading one superglobal into another (NonceVerification).
- **1 Oct 2026** — First CI run of B, F, G: 97 of 101 passed. The 4 conversion failures were a real plugin bug: the list put `&#013;` between terms and it was escaped again, so the browser showed one line with literal `&#013;`. The conversion now lists one term per line with real line breaks and `esc_textarea()`, and decodes stored entities (`&amp;`) so names are shown as typed. Also fixed: "false to array" deprecation when adding the first taxonomy (or editing an external one) with no saved settings; the Export PHP refusal test now asserts the messages (was risky). Conversion tests: 8.
- **1 Oct 2026** — CI clean: 102 tests pass on the full matrix; phpcs and PHPStan clean.
- **1 Oct 2026** — Class H (terms control, 17). Plugin fixes made for it: the control cache stores `st_cc_type`, `st_cc_hard`, `st_cc_min`, `st_cc_max` as integers and the checks compare as integers (type 1 was never recognised because the setting is stored as text, so drafts were always checked); classic-editor term counting moved to `count_input_terms()` (no PHP warning when all terms are removed; comma lists counted); the classic check uses the parent's status for revisions; external taxonomies that are not registered, or have an empty post-type list, no longer cause PHP errors in the control cache.
- **1 Oct 2026** — CI clean: 119 tests pass on the full matrix, including all 17 Terms Control tests on the first run; phpcs and PHPStan clean.
- **1 Oct 2026** — Class I (front end, 21). Plugin fixes made for it: post terms display treated text with spaces at the ends (such as the default ", " separator) as HTML, so no space was added after the "before" text; plain text is now detected by the absence of `<`, escaped with `esc_html()`, and HTML before/separator/after is filtered with `wp_kses_post()` when shown; taxonomy name escaped in the class attribute and the "not found" comment; admin list filter skips an external taxonomy that is not registered; Taxonomy Cloud block display works without the `ordering` attribute.
- **1 Oct 2026** — First CI run of I: 139 of 140 passed. `test_widget_block_without_ordering` called the block's render callback outside a block render, where WordPress's `get_block_wrapper_attributes()` fails; that cannot happen on a site (block attributes always get their defaults). Replaced by `test_widget_block_ordering`, which renders the block with `ordering` ASC and DESC.
- **1 Oct 2026** — Block wrapper attributes (pattern from WP Document Revisions): new `SimpleTaxonomyRefreshed_Client::get_block_attributes()` returns `get_block_wrapper_attributes()` only when `WP_Block_Supports::$block_to_render` is one of this plugin's blocks; the wrapper div is added only when it is not empty, so `block_terms()` and `staxo_widget_display()` can be called outside their block (directly, or while another dynamic block such as core/post-content renders). 2 tests added (front end: 23).
- **2 Oct 2026** — Classes J (list order, 8) and K (security sweep, 4); the test plan is complete. Plugin fixes made for them: a column order saved for only one post type was discarded (`1 === count()`), now kept; the posted order is validated (only that post type's admin-column taxonomies, once each, missing ones appended); `reorder_admin_list()` leaves out taxonomies no longer shown and copes with a missing saved order; Rename Slug requires `manage_options`, as its page does (it accepted the taxonomy's manage_terms).
- **2 Oct 2026** — CI clean (GitHub CI run #127): all 154 tests pass (8,682 assertions), including J, K and the block-wrapper tests on their first run. phpcs: an inline comment in the list-order handler ended in `)`, reworded.
- **2 Oct 2026** — Block supports: both blocks now declare the standard list (align; color with gradients; spacing margin and padding; typography fontSize and lineHeight); link colour removed. 3 front-end tests added (supports registered; each support applied to the wrapper of Display Post Terms and of Taxonomy Cloud). Front end: 26; total 157.
- **2 Oct 2026** — Terms Merge child terms: when a source has child terms, phase three asks whether they go under the destination (default) or up a level (the behaviour before 4.0.0); the notice is no longer shown when no source has children. `sources_have_children()` added. Merge tests: `test_phase_three_filters_sources` updated (Bebop has no children, so no choice), 3 added (choice shown, up a level, up a level with the destination below the source). Merge: 28; total 160.
- **2 Oct 2026** — Capabilities for Terms Migrate and Terms Import (Neil): Copy From needs the taxonomy's manage_terms, Copy To and Import need its edit_terms; taxonomies without them are shown but cannot be selected, and the handlers check again; Terms Import also needs manage_options, as its page does. Pages stay manage_options. Terms Migrate script error fixed (a row without a Copy To box broke the script). Helpers `register_locked_taxonomy()` and `grant_caps()` added. Tests: import 18 (+3), convert 11 (+3); security sweep: Terms Import minimum role now administrator. Total 166.
- **2 Oct 2026** — Capabilities for Terms Merge (Neil): manage_options (as the page) plus the taxonomy's manage_terms, delete_terms (sources are deleted) and assign_terms (posts get the destination); edit_terms as well when a source has child terms to move. Taxonomies without them are listed but disabled; the page no longer stops with a permissions message when the first taxonomy it checks is one the user cannot merge. `register_locked_taxonomy()` now uses `assign_locked` for assign_terms. Merge: 31 (+3); security sweep: Terms Merge minimum role now administrator. Total 169.
