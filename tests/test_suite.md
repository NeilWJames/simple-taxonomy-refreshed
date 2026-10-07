# STR Test Suite

Summary of the PHPUnit test suite for Simple Taxonomy Refreshed. **Keep this file up to date whenever tests, fixtures or helpers are added or changed** (see `CLAUDE.md`).

Full design and rationale: `STR-test-suite-plan.md` in the Claude project "STR Plugin Development".

_Last updated: 4 Oct 2026_

## Status

| Item | State |
|---|---|
| Tests written | 257 in 18 classes (A–Q + the original main test); class Q (config import, 9) written 5 Oct 2026; REST name checks (B +4, G +1, Q +1) written 7 Oct 2026, not yet run |
| Run in CI | All 228 pass, including classes M, N and O — 5 Oct 2026 (179 on 2 Oct); CI rebuilds `build/blocks` from `src/` when it changes |
| Coverage (Codecov, 5 Oct 2026, final for 4.0.0) | Whole plugin 90.3% (3,673 / 4,067 lines; 54.5% on 2 Oct). widget 100%, conversion 98.9%, import 97.7%, order 97.7%, rename 96.8%, merge 95.7%, admin 88.8%, config 85.2%, client 85.1%, main file 13.6% (runs while the plugin loads). Coverage work closed: items 4 and 5 of "Further coverage" not pursued |
| Still to write | None — plan complete (see "Planned" for follow-ups) |
| End-to-end (Playwright) | 26 tests in 6 specs, all passing locally (Playground) — 5 Oct 2026: full run 24/26, then `radio.spec.js` 5/5 after the "No term" fix. CI e2e to be re-run — see "End-to-end tests" |

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

End-to-end config files (one taxonomy each, for posts, built from the suite entries): `staxo-config-e2e-notice.json` (`e2e_notes`, label `Writer's "Notes"`, flat, Terms Control any status, checked when saved, min 1 max 2), `staxo-config-e2e-hard.json` (`e2e_hard`, label `Editor's Terms`, the same but checked as terms are changed), `staxo-config-e2e-merge.json` (`e2e_genre`, label `Genres`, hierarchical, no control), `staxo-config-e2e-radio.json` (`e2e_kind`, label `Kinds`, hierarchical, REST base `kinds`, exactly one term on any saved post, checked when saved), `staxo-config-e2e-radio-notify.json` (the same `e2e_kind` with notification only and no minimum, so "No term" is offered).

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

### B. Taxonomy admin — `class-test-staxo-taxonomy-admin.php` (`@group taxonomy`, 23)

Add taxonomy (stored, registered at next init, redirect `message=added`); form values sanitised (name via `sanitize_title`, labels, `st_before` via kses); default WP labels not stored; core/STR names refused; REST name clashes refused on add and update (`format`, `status`, a REST Base of `type`, `author` or another taxonomy's `tags`, a field added with `register_rest_field()`), with a REST Base or without REST the name can be used; update (labels, count type, status choices cleared unless type 2); update of unknown refused; callback fields protected; editor and bad nonce refused. Delete keeps terms and relationships; flush-delete removes terms, relationships and meta (but WordPress keeps a taxonomy's default term); delete updates `list_order`; unknown / editor / bad nonce refused. Export PHP (`build_php_export()`): valid PHP for all fixture taxonomies (`token_get_all( …, TOKEN_PARSE )`); values in comments cannot become code (review L3); name exported as a string literal and safe function name; editor / unknown taxonomy refused.

### C. Terms Import — `class-test-staxo-terms-import.php` (`@group import`, 18)

Flat list; tab and space hierarchies; re-import creates nothing; same name under two parents; blank lines ignored (flat and hierarchical); hierarchy into flat taxonomy rejected; indented first line → top level; skipped level → nearest ancestor; skipped-line warning notice (line number, term, reason; children of a skipped term skipped; name escaped); term named `0`; unknown taxonomy, subscriber, editor and bad nonce rejected; a taxonomy without the user's edit_terms capability refused (and accepted once granted) and shown disabled in the page's list.

### D. Term assignment and counts — `class-test-staxo-term-assignment.php` (`@group assignment`, 14)

Fixture matrix check; add term (published / draft); remove term; remove last term; delete term (children move up); default term not deletable and given to new posts; default counts (published only); STR count rules type 2 (publish + draft), with trash, type 1 (all except trash); counts follow status changes; `staxo_term_count_statuses` filter.

### E. Terms Merge — `class-test-staxo-merge.php` (`@group merge`, 31)

Phase one lists terms / shows terms-control warning; phase two disables destination (flat) and destination + ancestors (hierarchical, label `for` matches input `id`); phase three filters invalid sources; when a source has child terms (hierarchical only), phase three offers "under the destination" (default) or "up a level"; single source (duplicate row, meta deleted, count); several sources (review H1); parent term (children move under the destination, or up a level when chosen; with "up a level" a destination below the source is not moved); parent and child together; destination below a source (moved up first, no loop); flat across posts and pages (object cache cleared); count rules respected; terms control kept; posts the merge would take below the terms-control minimum listed in phase three with a Do not merge / Merge anyway choice (default: do not merge), merge refused or done accordingly, type 1 ignores drafts; no valid sources; invalid / other-taxonomy destination; invalid taxonomy; subscriber; bad nonce; capabilities: the page disables taxonomies without manage_terms, delete_terms and assign_terms, each of delete_terms and assign_terms required, and edit_terms required only when a source has child terms (refused in phase three and phase four, nothing deleted; a source without children merges).

### F. Terms Conversion — `class-test-staxo-convert.php` (`@group convert`, 11)

Hierarchical → hierarchical (space-indented tree, imported tree identical); hierarchical → flat (sorted flat list); flat → hierarchical (top level, includes default term); posts not moved; one term per line (no `&#013;`); a name with `&` escaped once, shown as typed and imported unchanged; unknown taxonomy and editor refused; the page disables Copy From without the taxonomy's manage_terms and Copy To without its edit_terms; copying from without manage_terms, or to without edit_terms, refused.

### G. Rename slug — `class-test-staxo-rename.php` (`@group rename`, 12)

Rename moves definition, `term_taxonomy` rows and posts' terms, hierarchy kept, "Done, 10 terms were migrated."; query_var (empty / equal to new slug → default; a new value is kept even when the old one was the default); `<taxonomy>_children` and `default_term_<taxonomy>` options move; `list_order` updated; new rewrite slug stored and flush scheduled; term cache cleared; invalid new slugs refused (empty, > 32, same, existing — review M6); a new slug whose REST name clashes (`status`, `tags`) refused; core and unknown taxonomies refused; subscriber and bad nonce refused.

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


### L. Admin screens — `class-test-staxo-admin-pages.php` (`@group pages`, 10)

The admin screen classes as WordPress loads them in wp-admin: each is a singleton whose constructor hooks its menu (instance cleared by reflection so the constructor runs); each adds its page under Taxonomies with its help tabs on the page's load hook; each adds help tabs to the screen; Configuration and List Order load the sortable script and the plugin's admin script and style. Pages: Configuration (several taxonomies → sortable list and Export/Import forms; one taxonomy → no reordering; no custom taxonomies; no configuration → no Export form); Rename (a radio and script per taxonomy; no taxonomies → stops); Terms Import (posted taxonomy and hierarchy kept selected); List Order (post type tabs and sortable lists). Configuration export (plan A4): `build_config_export()` output re-imported gives the same settings; chosen order applied, unknown names ignored, unlisted taxonomies kept, no settings → empty export.

### M. External taxonomies — `class-test-staxo-externals.php` (`@group externals`, 14)

STR settings on taxonomies registered by someone else. Two are registered in `set_up` as another plugin would (no count callback): `ext_topic` (flat, REST base `topics`) and `ext_area` (hierarchical). Saving the external form (`merge-external` → `update_external()`): stores only the integration settings under `externals` (no labels, objects, `public`…), redirects with `message=updated`; Term Count statuses kept only for "Selection"; editors refused. Control cache built from the registered taxonomy (integers, REST base, label); not cached for other post types or an unregistered external. REST check refuses a published post with no `topics` term. Term Count "Selection" (publish + draft) counts a draft but not a pending post. WPGraphQL settings set on the `WP_Taxonomy` object by `registered_taxonomy()`. The external edit form (`page_manage()`): "External Taxonomy : Topics", `merge-external`, hidden name, no Main Options tab, saved control shown; an unknown taxonomy stops with a message. Categories (count callback `_update_post_term_count`) get Term Count "Selection" too, and their form shows the options; a taxonomy with its own callback (`_update_generic_term_count`) keeps it and gets no statuses unless "use these options" (`st_cb_override`) is ticked; the form warns, names the function and shows the options and the box. With the box ticked, `init_2()` replaces the callback with `_update_post_term_count`, `original_count_callback()` still names the own function, and a draft is counted but not a pending post.

### N. Custom Taxonomies screen — `class-test-staxo-taxonomy-form.php` (`@group form`, 15)

`page_manage()` rendered with output buffering, logged in as an administrator with the suite taxonomies. List screen: a row per custom taxonomy with its edit, Export PHP, Delete and Flush & Delete links (nonces) and its columns (post type labels, hierarchical, rewrite, public, block editor); the other public taxonomies (Categories, Tags) in the external list, with Delete Extra Functions only once settings are saved; a label with quotes is shown, encoded, in the link and its title; no settings → "No custom taxonomy." and every public taxonomy is external. Notices for `message=added|updated|deleted|flush-deleted|ext_deleted` with the name stripped of tags; none for an unknown message or no name. Add form: heading, `add-taxonomy` and its nonce, all 11 tabs and panels, typed name, defaults (no post type, not hierarchical, public, REST, `, ` separator, WordPress labels including "No term", capabilities), Term Count Standard, no Term Control, no `st_cb_override` box, Add disabled. Edit form: heading, `merge-taxonomy` and its nonce, read-only name, saved values (hierarchical, Display Terms, post types, labels, query var, EP_NONE), Update enabled; `test_flat` REST base and default term; `test_cntl` Term Control (Any, on save, 1–2 terms, the scripts' start values); `test_count` Term Count selection (each status box and `aria-checked`); a custom taxonomy with its own count function shows the message, not the options; rewrite slug and options and WPGraphQL names taken from the registration arguments; settings saved by older versions (without the fields added in 1.0–3.1) filled with defaults. Callback fields (REST controller class, count function, meta box callbacks) editable for an administrator and read-only, with a note each, when `staxo_can_edit_callbacks` says no (add form too). `action=edit` without a name shows the list; an unknown name stops.

### O. Post screens — `class-test-staxo-post-screens.php` (`@group screens`, 19)

`check_posts_outside_limits()` (on `all_admin_notices`) with `$post` and the current screen set, classic or block editor (the screen's `is_block_editor()` flag; the plugin's `$use_block_editor` and `$enqueue_client` are reset for each run, and `staxo_radio_editor` is registered so its inline settings can be read without `build/editor`). Classic editor notices: too few terms (error; "needs to be at least" on a new post), too many, a hidden notice within the limits, nothing on an auto-draft, a warning for "Published only" on a draft, a warning with "You will not be able to save" for a user who cannot assign the terms, notification only (no notice for users who can change the terms, a warning without the save line for others). Block editor: the notice is a `core/notices` script on `staxo_client` with its id, not when the taxonomy is not in REST, and not for "as terms are changed". A label with quotes is HTML-escaped (classic) and JSON-escaped (block). Checks as terms are changed: the `tax_cntl` entry and `dom_tag_cntl_check()` / `dom_hier_cntl_check()` / `block_limit()`; none for a user who cannot change the terms. Radio buttons (hierarchical, maximum 1, any control level): the `tax_cntl` entry with "No term" and `dom_radio_client()` once the editor is ready; not with two terms already, for a flat taxonomy or a maximum of 2; on a new post; in the block editor `window.staxo_radio[ slug ]` with `noTerm` ("No term", or null with a minimum of 1). Post list: `dom_qe_radio_client()` and `dom_qe_cntl_check()` (only "as terms are changed"), nothing printed. Other screens: nothing. `is_block_editor()` from the post when there is no screen (and the `use_block_editor_for_post` filter), and `block_editor_active()`. `enqueue_radio_editor()` loads `build/editor` only when it has been built.

### P. Widget — `class-test-staxo-widget.php` (`@group widget`, 12)

The Simple Taxonomy Widget beyond the output tested in class I. Registration (`str_widgets_init()`: id base `staxonomy`, name, class, shown in REST; `init_staxo_widget()` registers the plugin's `$strw`; `hide_staxonomy_widget()` hides it from the Legacy Widget block). Settings form (a new instance, number 2): defaults (title "Advanced Taxonomy Cloud", tags, cloud, justify, by count, descending, count shown, sizes 50/150, 45 terms, minimum 0), saved settings (escaped title, chosen options, count unticked), unknown taxonomy shown as tags, `form()` returns ''. `get_taxonomies()`: public taxonomies by label, sorted, cached and read from the cache. Output: title from the taxonomy's name when empty, `staxo_widget_title` filter (with the instance and id base), no heading for an empty title, the widget area markup; list items with link, `aria-label` with "2 items" / "1 item", count shown or not; number of terms with count / descending order; cloud with font sizes when counts differ, alignment, filters removed afterwards, a list when the taxonomy has no tag cloud. `filter_terms()` (minimum replaces the "has posts" condition, random order) and `filter_result()` (sizes removed only for equal counts). Block: `staxo_widgets_block_init()` registers it with the render callback, translated title and description, and `staxo_data` for the editor script (skipped without `build/blocks`); `update_settings()` leaves other blocks alone; rendering with `header` gives an `<h2>` title, `ordering` is the order.

### Q. Configuration import checks — `class-test-staxo-config-import.php` (`@group config`, 10)

Review finding M1, decided by Neil (5 Oct 2026): each imported taxonomy goes through the admin form's sanitising (`SimpleTaxonomyRefreshed_Admin::clean_taxonomy_fields()`, now shared with `check_merge_taxonomy()`) and range checks; a taxonomy is skipped (listed in a `config_skipped` warning) when its name is not valid (not 1–32 characters, not as `sanitize_title()` gives it, different from its `name` setting), when WordPress or another plugin already registered the name (`SimpleTaxonomyRefreshed_Client::registered_by_plugin()` lets the plugin's own taxonomies through), when its settings are not a list, when its REST name (REST Base, or name) clashes with a field of posts or another taxonomy's REST name, or when any setting the plugin uses would be stored differently (HTML in a label or the before text, Term Control type 7, `st_cc_hard` "yes", minimum -1, `st_ep_mask` "all", a nested post type list, a slug with a line break). Settings the plugin does not use, unknown top-level groups and bad list-order items are dropped and counted (`config_ignored`). Externals keep only the integration settings, with defaults for missing ones; WordPress's own taxonomies may be used. Missing settings of a custom taxonomy take their defaults; an empty choice (older versions) is accepted. Files written by the plugin (suite, e2e fixtures) import unchanged with no warnings. Nothing valid → error, configuration kept. A taxonomy registered by the plugin can be re-imported after its settings are removed.

## End-to-end tests (Playwright)

Browser tests of the admin screens, with `@playwright/test` and `@wordpress/e2e-test-utils-playwright`, against a WordPress started by `@wordpress/env`.

| Where | WordPress runtime | Database |
|---|---|---|
| Locally (Windows, Node only) | **WordPress Playground** (`npm run env:start`, blueprint `tests/e2e/blueprint.json`): WebAssembly, no Docker, a new site at every start | SQLite |
| CI (`e2e.yml`) | wp-env **Docker** runtime | MySQL |

The site is `http://127.0.0.1:8888` locally (Playground listens on IPv4 only and uses that as its site URL) and `http://localhost:8888` in CI; log in as `admin` / `password`. Time limits are raised (test 5 min, action 30 s, navigation 60 s, `expect` 20 s) because an admin page takes several seconds from the network share. PHP 8.2 (the minimum supported), latest WordPress, `WP_DEBUG` on: a PHP notice on a visited admin page fails the test.

Locally Playground is started directly, not through wp-env: wp-env's Playground runtime stops the server if it is not ready within 120 seconds, and a start with the plugin on the network share takes longer (first start about 10 minutes, mostly downloading WordPress, which is then cached in `%USERPROFILE%\.wordpress-playground`). The `lockWholeFile: unlock failed` lines Playground prints on Windows are harmless.

### Running

```bash
npm install                      # once, and after package.json changes
npx playwright install chromium  # once per computer (the browser goes in the user profile)

npm run env:start                # start WordPress and leave it running (separate window; Ctrl+C to stop)
npm run test:e2e                 # runs all specs; starts WordPress for the run if it is not running
npm run test:e2e:ui              # Playwright's UI mode: pick tests, watch them, time-travel
npx playwright test add-taxonomy # one spec
```

For repeated runs keep `env:start` running in its own window: otherwise each `test:e2e` starts and stops WordPress. The tests reset the STR settings themselves. Failures leave a trace, screenshot and video in `artifacts/test-results/`; open a trace with `npx playwright show-trace <file>`.

Playground's SQLite is not MySQL: anything that depends on SQL behaviour (merge, counts) stays covered by PHPUnit and by the CI run.

### Files

| File | Purpose |
|---|---|
| `tests/e2e/blueprint.json` | Local Playground site: activates the plugin, `WP_DEBUG`. No `login` step, and `env:start` passes `--no-login` (the CLI's `server` command turns auto-login on by default): Playground's auto-login redirects any request without its cookie, so Playwright's readiness check looped on redirects. Playwright logs in itself (`admin` / `password`). `npm run env:start` mounts the plugin folder and the helper below |
| `.wp-env.json` | The same site for wp-env (used in CI with the Docker runtime): this plugin, the helper as a must-use plugin, PHP 8.2, `WP_DEBUG`, no separate tests site |
| `playwright.config.js` | Extends the `@wordpress/scripts` Playwright config: specs in `tests/e2e/specs`, site on port 8888; starts `npm run env:start` locally or `wp-env start` when `CI` is set, and waits up to 15 minutes for the site to answer |
| `tests/e2e/mu-plugins/staxo-e2e-helper.php` | Classic editor switch (`staxo_e2e_classic=1` on `post.php`, through `use_block_editor_for_post`, kept on the redirect after a save, so no Classic Editor plugin is needed). Test-only REST routes (`staxo-e2e/v1`, administrators only): `POST /reset` deletes the STR settings and the terms of their taxonomies; `POST /config` with `{ "file": "<name>.json" }` loads a fixture from `tests/files` as the STR settings. Not shipped (`tests/` is in `.distignore`) |
| `tests/e2e/helpers.js` | Shared helpers: admin URLs (`LIST_URL`, `ADD_URL`, `editUrl()`), `resetStaxo()` and `loadConfig()` (the helper routes), `taxonomyForm()` locators (fields whose label is used on several tabs are looked up in their tab panel), `enterSlug()`, `createTerms()`, `getPost()`, `editPostClassic()`, `openQuickEdit()`, `collectPageErrors()` |
| `tests/e2e/specs/*.spec.js` | The tests |
| `artifacts/` | Output (login state, traces); gitignored |

Test data is set through the REST API (`requestUtils`) and the helper routes, not WP-CLI, because the Playground runtime has no `wp-env run`.

### Specs

| Spec | Tests |
|---|---|
| `add-taxonomy.spec.js` | Add Taxonomy screen (4): the submit button stays disabled until a name is entered; tabs show one panel at a time (`aria-selected`); adding a hierarchical taxonomy for posts shows the confirmation and the list row, and WordPress registers it (its terms screen, with a Parent field); a name already used (`category`) is refused. Settings reset before each test and at the end |
| `edit-taxonomy.spec.js` | Edit Taxonomy screen (7), with `staxo-config-suite.json` loaded before each test: the list links to each taxonomy's form (name read-only, Update enabled); saved values shown on Main Options and Labels (`test_hier`), REST base and default term (`test_flat`), Term Count selection and statuses (`test_count`), Term Control type, timing and limits (`test_cntl`); changed labels of a custom taxonomy are saved and used by WordPress; Term Control settings for an external taxonomy (`category`) are saved and shown again (`update_external()`, not covered by PHPUnit) |
| `block-editor.spec.js` | Terms Control in the block editor (5); the draft is created before the fixture is loaded. Checked when saved (`e2e_notes`): opening a draft with no term shows the PHP inline-script notice with the quoted label and no script error (review H2); Save draft is refused by the REST check ("Not enough terms…"); with a term added the draft saves. Checked as terms change (`e2e_hard`, `block_limit()`): saving locked and notice shown with no term; one term unlocks; three terms (max 2) lock with the maximum notice; two terms unlock and the draft saves. Notice checks look only at `.components-notice__content` (the screen-reader live region keeps notice text after the notice has gone); the post is edited before "Save draft", which is only offered for a changed post |
| `merge.spec.js` | Terms Merge page, all steps in the browser (3), with terms and posts created over REST (Music > Jazz > Bebop, Art): Bebop into Jazz (button disabled until a taxonomy is chosen, destination disabled as a source, "already linked" notice, count 2, Bebop deleted, both posts on Jazz once); Music into Art moves Jazz under Art by default; or up a level when chosen |
| `radio.spec.js` | Radio buttons for a one-term taxonomy (5, review H3), `e2e_kind`: block editor (radio term selector from `src/editor`, needs `npm run build:editor`: a `radiogroup` of radio buttons and no checkboxes, no "No term" as a term is required, choosing Jazz replaces Rock, Add New Category creates Blues and chooses it, saved over REST base `kinds`); classic editor (`dom_radio_client()`: radio inputs only, choosing one clears the other, Save Draft saves it); Quick Edit (`dom_qe_radio_client()`: radio inputs, list role `radiogroup`, Update saves). Notification only (2, fixture `-radio-notify`): block editor radio buttons with "No term" first and chosen for a post with no term, Jazz saved, then "No term" saves no term; classic editor radio inputs, Jazz saved. No script errors on any page |
| `classic-limits.spec.js` | Terms Control checked as terms change, classic editor and Quick Edit (2, review H3), `e2e_hard`: classic (`dom_tag_cntl_check()`: reason shown, Save Draft stopped, one tag hides the reason, two tags make the tag field read-only, then saves); Quick Edit (`dom_qe_cntl_check()`: reason shown and Update disabled, one tag clears it, Update saves). No script errors |

## Planned

The test plan (A–K) is complete. Follow-ups:

| Area | Notes |
|---|---|
| Config import sanitising | **Done 5 Oct 2026** (review finding M1): see class Q |
| Phase 2: browser tests (in progress) | Playwright tests for the taxonomy admin form, decided 2 Oct 2026; set-up and the first spec written 4 Oct 2026 (see "End-to-end tests"). Next: edit an existing taxonomy (loaded with the `config` helper route), the other form tabs, then block-editor JavaScript (terms-control notices, radio buttons, iframe canvas) |

### Further coverage (from Codecov line data)

Source: the Codecov upload for the CI run of 2 Oct 2026, 16:18 UTC (one PHP flag; its config figure is 84.7%, against 85.2% on the Codecov summary). Whole plugin: 2,177 of 3,994 statements (54.5%). In priority order, after Phase 2:

| # | Area | Uncovered | What to do |
|---|---|---|---|
| 1 | **External taxonomies** (STR settings on a taxonomy it doesn't own, e.g. `category`) | `admin.php` `update_external()` never called; externals branches of `check_merge_taxonomy()` (lines 2164–2170); `client.php` `init()` externals (124–131) and `refresh_term_cntl_cache()` externals (966–996) | **Written 5 Oct 2026:** class M (11 tests, not yet run). Was: a real feature gap. Add an `externals` entry to a fixture (term count + terms control on `post_tag` or a test-registered taxonomy) and test: saving through the admin form, registration changes in `init()`/`init_2()`, the control cache, counts |
| 2 | **Add/edit taxonomy form rendered** | `page_manage()` 183, `form_merge_custom_type()` 792, `page_form()` and `option_*()` helpers ~50 (~1,025 statements, about half of `admin.php`) | **Written 5 Oct 2026:** class N (15 tests, not yet run); the external form is in class M. Was: render with output buffering as class L does (new, edit custom, edit external, list screen). Brings `admin.php` to about 76% and the plugin to about 80%. Complements the Phase 2 Playwright tests rather than replacing them |
| 3 | **Terms-control notices on the post and edit screens** | `check_posts_outside_limits()`, `_edit()`, `_post()` (~130), `notice_script()`, `on_dom_ready()`, `script_radio*()`, `hard_term_limits_edit()`, `term_limits_push()`, `is_block_editor()` (~100) | **Done 5 Oct 2026:** class O (19 tests, passing). Was: set `$post` / `$current_screen`, capture `all_admin_notices` output; classic and block editor; each post status group |
| 4 | **Configuration import error branches; download handlers** | `admin-config.php`: upload error (121–122), prefix OK but JSON not an array (132), non-array taxonomy entry (143–144), no known keys (166), `taxo_list_arr` order (107–109). Export download headers + `die()` (85–101) and `check_export_taxonomy()` (2200–2221) | Small tests for the branches. Move the headers and `die()` of both downloads into one `send_download()` helper and mark only that `@codeCoverageIgnore` (as done for `build_config_export()`) |
| 5 | **Code that runs while the plugin loads** | `simple-taxonomy-refreshed.php` (44), client and widget constructors, `str_widgets_init()`, `registered_taxonomy()`, `admin_menu()`, `add_help_tab()`, `activity_box_end()`, enqueue functions | Runs in `tests/bootstrap.php` before coverage starts, so shows as 0. Call again from a test, or accept. Also: `prepare_args()` optional args (`rest_base`, `rest_namespace`, `rest_controller_class`, GraphQL, meta box callbacks, `update_count_callback`) via one "kitchen-sink" taxonomy; `wp_title()`, `template_redirect()`, classic widget `form()` (67), block registration |

Smaller gaps in the target files: merge `move_children()` (7 lines, child-term edge cases), `posts_below_minimum()` (4); rename `page_rename()` (4). The `ABSPATH` guard line in each file cannot be covered (it only runs outside WordPress and ends the process).

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
- **2 Oct 2026** — CI clean: all 169 tests pass (9,534 assertions). CI now rebuilds `build/blocks` when `src/` changes, runs with read-only permissions and uploads coverage to Codecov (one flag per matrix job). First coverage figures for the plan's five files recorded in Status; three are below the 80% target.
- **2 Oct 2026** — Class L (admin screens, 10) for coverage of the plan's files: singletons and constructors, menu pages, help tabs, sortable scripts, Configuration / Rename / Terms Import / List Order pages, and the configuration export round trip (plan A4). Plugin changes: configuration export content built by `SimpleTaxonomyRefreshed_Admin_Config::build_config_export()` (export keeps taxonomies missing from the chosen order and ignores unknown names); List Order page markup fixed for the no-multiple case. Total 179.
- **2 Oct 2026** — CI clean: all 179 tests pass, including class L on its first run. Coverage target (plan §7, ≥ 80% for import, merge, rename, conversion and config) met for all five; figures in Status. Test-suite phase complete.
- **2 Oct 2026** — Phase 2 decided (Neil): Playwright tests for the admin form.
- **4 Oct 2026** — Codecov line data for the 2 Oct run analysed; the optional coverage follow-up replaced by a prioritised list under "Planned" (external taxonomies first). No tests changed.
- **4 Oct 2026** — Phase 2 started: Playwright set-up (`.wp-env.json`, `playwright.config.js`, helper must-use plugin with `reset` and `config` routes, npm scripts `test:e2e`, `test:e2e:ui`, `env:start`, `env:stop`) and the first spec, `add-taxonomy.spec.js` (4 tests). Lint clean; not yet run. CI workflow `e2e.yml` drafted in the Claude folder (`work/workflows/`).
- **4 Oct 2026** — Local e2e site: wp-env's Playground runtime timed out after 120 s with the plugin on the network share (first start took about 10 minutes). `npm run env:start` now runs Playground directly with `tests/e2e/blueprint.json`; Playwright waits for the site URL (Playground answers 502 while booting), up to 15 minutes. `.wp-env.json` kept for CI, with `testsEnvironment: false`. `env:stop` removed (Ctrl+C).
- **4 Oct 2026** — First local run hung on Playwright's check of `http://localhost:8888` (Playground listens on 127.0.0.1 only). Local base URL is now `http://127.0.0.1:8888`, the Playground site URL; time limits raised for the network share.
- **4 Oct 2026** — Readiness check still hung: the blueprint's `login` step turns on Playground auto-login, which answers every cookie-less request with a redirect (curl: 302 after 120 s of redirects). `login` step removed; the Playwright global setup logs in with `admin` / `password`.
- **5 Oct 2026** — Still looping after the blueprint change: Playground CLI's `server` command enables auto-login by default. `env:start` now passes `--no-login`.
- **5 Oct 2026** — First local runs: 3 of 4 passed at once; "adds a hierarchical taxonomy" failed because "Hierarchical ?" and "Post types" each label three fields on different tabs (strict-mode violation). Those locators are now scoped to the Main Options panel (`#mainopts`). All 4 pass (1.9 min). The "Failed to load resource: 500" console line comes from the duplicate-name test: `wp_die()` answers with HTTP 500, as expected.
- **5 Oct 2026** — Second spec `edit-taxonomy.spec.js` (7 tests, not yet run). Shared helpers moved to `tests/e2e/helpers.js`; `add-taxonomy.spec.js` uses them (`addButton` replaces `submit`).
- **5 Oct 2026** — First run of all 11: 8 passed. The 3 tests that save the form failed only on their URL check: WordPress removes `message` from the address bar after load (removable query args), and on this run it did so before Playwright looked. The page snapshots show the save and its notice worked. The URL checks now look for `page=staxo_settings…&staxo=<name>`; the notice text is still checked.
- **5 Oct 2026** — All 11 e2e tests pass locally (3.8 min), including the external-taxonomy save (`update_external()`).
- **5 Oct 2026** — Specs `block-editor.spec.js` (5) and `merge.spec.js` (3), with fixtures `staxo-config-e2e-notice.json`, `staxo-config-e2e-hard.json`, `staxo-config-e2e-merge.json`. Not yet run. 19 e2e tests.
- **5 Oct 2026** — First run of `block-editor.spec.js` and `merge.spec.js`: merge 3/3 pass; block editor 1/5. Plugin bugs found and fixed: (1) the notice for a post already outside the limits was added to `staxo_placeholder`, which is printed in the page head before the check runs, so it never appeared; it now goes on `staxo_client` (printed in the footer); (2) `block_limit()` showed the HTML-escaped messages as text (`Editor&#039;s`); it now decodes them. Test fixes: notices are looked up in `.components-notice__content` only; the post is edited before "Save draft".
- **5 Oct 2026** — Review H3 before 4.0 (Neil): radio buttons in the block editor implemented (`block_radio()` in `staxo-client.js`, called from `script_radio()`); `tax_cntl` entries gain the REST base (10) and label (11), and `block_limit()` reads the terms under the REST base. Specs `radio.spec.js` (3) and `classic-limits.spec.js` (2), fixture `staxo-config-e2e-radio.json`, classic-editor switch in the helper plugin, more shared helpers. 24 e2e tests.
- **5 Oct 2026** — Block editor radio redesigned (Neil): instead of `block_radio()` (removed from `staxo-client.js`), the plugin's radio term selector (`src/editor/radio-term-selector.js`, adapted from WordPress's `HierarchicalTermSelector`, with search and Add New Term) replaces the checkboxes through the `editor.PostTaxonomyType` filter for the taxonomies `script_radio()` lists in `window.staxo_radio`. Built with `npm run build:editor` to `build/editor`, enqueued on `enqueue_block_editor_assets`. `tax_cntl` entry 11 (label) dropped. `radio.spec.js` block test rewritten (real radio inputs, Add New Category).
- **5 Oct 2026** — Run of block-editor, radio (old store version) and classic-limits: 8 of 10 passed, including all 5 block-editor tests after the fixes. The two classic-editor saves did save, but WordPress redirected to `post.php` without the classic switch, so the block editor opened and "Post draft updated." was not shown. The helper plugin now keeps the switch on the redirect (`redirect_post_location`).
- **5 Oct 2026** — After `npm run build:editor`: block-editor, radio and classic-limits all pass (10/10, 1.7 min), including the new block-editor radio term selector with Add New Category and the classic-editor saves. All 24 e2e tests have now passed locally.
- **5 Oct 2026** — Class M (externals, 11) for the first coverage follow-up. Plugin fix made while writing it: `SimpleTaxonomyRefreshed_Client::registered_taxonomy()` wrote the WPGraphQL settings for an external taxonomy into `$wp_taxonomies[ $taxonomy ]` as an array, but registered taxonomies are `WP_Taxonomy` objects, so with WPGraphQL turned on for an external taxonomy the next registration of it ended in a fatal error; it now sets the properties on the object. Note: `phpunit9.xml` has `disableCodeCoverageIgnore="true"`, so the planned `@codeCoverageIgnore` on a download helper would not take effect without changing that setting. 190 tests.
- **5 Oct 2026** — Full e2e run: 24/24 pass (2.9 min).
- **5 Oct 2026** — Term Count for taxonomies counted by WordPress's `_update_post_term_count()` (categories, tags, and other plugins' taxonomies with that callback or none), decided by Neil: that function applies the `update_post_term_count_statuses` filter, so the counts are the ones STR would make. New `SimpleTaxonomyRefreshed_Client::counts_by_post_status()` used by `init()`, `term_count_sel_cache()`, the external form (`page_manage()`) and the Term Count tab; `staxo-admin.js` `hideCnt()` likewise. Other callbacks are left alone. Class M +2 (13); 192 tests. `phpunit9.xml`: `disableCodeCoverageIgnore` now `false` (Neil), so `@codeCoverageIgnore` takes effect.
- **5 Oct 2026** — External taxonomies with a count function of their own (Neil): the Term Count tab shows a warning naming the function, the options, and a new box "Use these options to count terms, instead of the taxonomy's own function" (new setting `st_cb_override`, stored with the external's settings; hidden 0 so unticking is saved). Ticked: `SimpleTaxonomyRefreshed_Client::apply_count_overrides()` (from `init_2()`) replaces the callback with `_update_post_term_count`; `original_count_callback()` keeps the own name for the form; `init()` and `term_count_sel_cache()` honour the setting. Also fixed a stray `"` after the `count_sel_0` / `count_sel_1` span attributes. Class M: own-callback test rewritten, override test added (14); 193 tests.
- **5 Oct 2026** — Class N (Custom Taxonomies screen, 15) for the second coverage follow-up: list screen, notices, add form, edit forms and read-only callback fields. Plugin fix made while writing it: the "Display Terms with Posts" and EP_MASK options passed `selected()` through `esc_attr()`, so the chosen option was written `selected=&#039;selected&#039;` (browsers still selected it, but the markup was wrong); `esc_attr()` removed (15 places). 208 tests.
- **5 Oct 2026** — CI (with only part of the changes pushed) showed a real bug: saving an external taxonomy with Term Count "Selection" stored only the ticked status boxes (unticked boxes are not posted, and `merge-external` keeps only posted fields), so `term_count_sel_cache()` read undefined `st_cb_*` keys. `update_external()` now stores every status (0 when unticked or when the type is not Selection), and `term_count_sel_cache()` reads them with `empty()`. Shown by class M `test_external_term_count` and `test_external_term_count_category`. PHPStan: `$nt_label` documented as `string|null` in `script_radio()` / `script_radio_edit()`; the WPGraphQL properties set on `WP_Taxonomy` in `registered_taxonomy()` marked `@phpstan-ignore-line`. `build:editor` now uses `--webpack-src-dir=src/editor` (it also copied the blocks' `block.json` files into `build/editor/blocks`).
- **5 Oct 2026** — Neil's manual check on Playground (Categories, Term Control min 0 / max 1, "How Control is applied" left on the notification option): no radio buttons, as designed — `refresh_term_cntl_cache()` leaves out taxonomies with `st_cc_hard` 0, so neither radio buttons nor limits apply. Found on the way: the Term Control tab's "Current value:" (Display on admin) was never printed (`esc_attr()` without `echo`; the script only fills it when the select changes, and the external form has no select). Fixed; class N `test_edit_form` checks it.
- **5 Oct 2026** — Notification-only Term Control ("How Control is applied" = first option, `st_cc_hard` 0), decided by Neil: it should show radio buttons like the other levels. `refresh_term_cntl_cache()` now caches every level (was: `st_cc_hard` must be non-empty, so level 0 did nothing at all); `check_taxonomy_value_set()` and `check_taxonomy_value_rest()` skip level 0, so saving is never blocked; on the post screen the level-0 notices are only for users who cannot assign the terms. Class H: `test_control_cache_exclusions` now uses no control (`st_cc_type` 0) instead of level 0; new `test_notification_only` (cached at level 0, classic and REST saves not refused).
- **5 Oct 2026** — Codecov after classes M and N: whole plugin 81.4% (3,309 / 4,063); `admin.php` 23.3% → 76.1%, `client.php` 75.8% → 84.6%. Coverage items 1 and 2 done. Largest remaining gaps: `admin.php` 454 lines (mostly item 3, the post-screen notices and scripts), widget 111, main file 44 (item 5).
- **5 Oct 2026** — Class O (post screens, 19) for coverage item 3. Plugin fix made while writing it: on the post screen the "as terms are changed" `tax_cntl` entry always said the taxonomy was hierarchical (`true` passed to `term_limits_push()`); it now passes the taxonomy's own setting (the client only uses it for "No term" on radio lists, so no visible change). 228 tests.
- **5 Oct 2026** — `radio.spec.js` +2 for notification-only Terms Control (Neil): new fixture `staxo-config-e2e-radio-notify.json`; block editor ("No term" offered and saved) and classic editor radio buttons. 26 e2e tests.
- **5 Oct 2026** — CI: all 228 pass (class N `test_edit_form` needed `\s*` before the "Current value" span). Codecov: whole plugin 87.4% (3,553 / 4,067); `admin.php` 88.8% (was 76.1%). Coverage item 3 done. Remaining: widget 111 lines, client 86, main file 44 (load time), config 23.
- **5 Oct 2026** — Class P (widget, 12) to raise widget coverage (49.8%: the settings form, taxonomy list and block registration were not run). Plugin fix made while writing it: the widget settings form passed `selected()` through `esc_attr()` (5 lists), as the taxonomy form did. Also covers the main file's `init_staxo_widget()`, `hide_staxonomy_widget()` and `staxo_widgets_block_init()`. 240 tests.
- **5 Oct 2026** — First run of the 2 notification-only radio tests: both failed. Classic editor: a real bug — with no minimum, `add_no_term()` copies the first item of each list to make "No term", but the Most Used list is empty until a term has posts, so `add_nt_element()` failed on `inp[0]` and the radio conversion never ran (the existing radio fixture has a minimum, so it never added "No term"). `add_nt_element()` now skips an empty list. Block editor: the second `editor.saveDraft()` returned on the first save's "Draft saved" notice before saving; the test now polls the stored terms.
- **5 Oct 2026** — After the fix, `radio.spec.js` 5/5 locally: all 26 e2e tests have passed.
- **5 Oct 2026** — CI e2e "saves Term Control settings for an external taxonomy" failed on Docker/MySQL: `Undefined array key "st_cc_max"` in `refresh_term_cntl_cache()` was printed before the redirect. The form disables the maximum when "Use maximum" is False, a disabled field is not posted, and `merge-external` keeps only posted fields (locally the category settings already held a maximum). Fix: new `SimpleTaxonomyRefreshed_Admin::external_defaults()` (also used by `page_manage()`); `update_external()` merges it under the posted fields; `refresh_term_cntl_cache()` defaults the min/max fields for externals saved earlier; `prepare_filter_args()` reads the Admin List Filter flags with `empty()`. Class M +2 (fields not sent; older settings without a maximum). 242 tests.
- **5 Oct 2026** — Codecov after class P: whole plugin 90.3% (3,673 / 4,067); widget 100% (was 49.8%), main file 13.6% (was 0%). Neil: coverage is good enough; the test creation phase is closed. Items 4 (config import error branches, `send_download()`) and 5 (load-time code) left as they are.
- **5 Oct 2026** — Review finding M1 (Neil: skip invalid taxonomies and import the rest; a supported setting with an invalid value rejects the whole taxonomy; unknown settings dropped quietly with a count). `SimpleTaxonomyRefreshed_Admin_Config::prepare_import()` with `import_entry_problem()`, `import_value_ranges()`, `import_invalid_field()`, `same_value()` and `import_notices()`; the form's field loop moved to `SimpleTaxonomyRefreshed_Admin::clean_taxonomy_fields()`; `external_defaults()` made public; `SimpleTaxonomyRefreshed_Client::registered_by_plugin()`. Class Q (9). 251 tests.
- **7 Oct 2026** — REST name clashes (found with the playground: a taxonomy named `format` clashes with the post's own `format` field, so WordPress leaves it out of the posts' REST data and the block editor cannot set its terms). New `SimpleTaxonomyRefreshed_Admin::REST_POST_FIELDS` and `rest_base_conflict()` (the REST Base, or the name, against the post fields, fields added with `register_rest_field()` for the taxonomy's post types, and other taxonomies' REST names; only when shown in REST). Refused on add and update (`refuse_rest_base_conflict()`, `wp_die` with a back link), on Rename Taxonomy Slug, and skipped on configuration import. B +4, G +1, Q +1. 257 tests.
