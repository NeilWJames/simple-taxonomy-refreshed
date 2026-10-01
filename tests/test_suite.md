# STR Test Suite

Summary of the PHPUnit test suite for Simple Taxonomy Refreshed. **Keep this file up to date whenever tests, fixtures or helpers are added or changed** (see `CLAUDE.md`).

Full design and rationale: `STR-test-suite-plan.md` in the Claude project "STR Plugin Development".

_Last updated: 1 Oct 2026_

## Status

| Item | State |
|---|---|
| Tests written | 59 in 5 classes (A, C, D, E + the original main test) |
| Run in CI | Not yet confirmed for the new classes (first CI run pending) |
| Still to write | B, F, G, H, I, J, K (see "Planned") |

## Running

```bash
bash script/install-wp-tests wordpress_test root '' localhost latest
composer install
vendor/bin/phpunit -c phpunit9.xml                  # all
vendor/bin/phpunit -c phpunit9.xml --group merge    # one area
WP_MULTISITE=1 vendor/bin/phpunit -c phpunit9.xml   # multisite
```

Groups: `config`, `import`, `merge`, `assignment`.

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
| `ajax()`, `merge_phase()`, `merge()` | AJAX base only: call a handler / a merge phase / a full merge |

## Test classes

### A. Config import — `class-test-staxo-refreshed-config.php` (`@group config`, 8)

Import saves the option; taxonomy registered with its settings; suite file registers all four taxonomies (rest_base, default term, terms-control cache); a second import replaces the first; bad file rejected; editor refused; callback fields kept/cleared by `staxo_can_edit_callbacks`.

### C. Terms Import — `class-test-staxo-terms-import.php` (`@group import`, 15)

Flat list; tab and space hierarchies; re-import creates nothing; same name under two parents; blank lines ignored (flat and hierarchical); hierarchy into flat taxonomy rejected; indented first line → top level; skipped level → nearest ancestor; skipped-line warning notice (line number, term, reason; children of a skipped term skipped; name escaped); term named `0`; unknown taxonomy, subscriber and bad nonce rejected.

### D. Term assignment and counts — `class-test-staxo-term-assignment.php` (`@group assignment`, 14)

Fixture matrix check; add term (published / draft); remove term; remove last term; delete term (children move up); default term not deletable and given to new posts; default counts (published only); STR count rules type 2 (publish + draft), with trash, type 1 (all except trash); counts follow status changes; `staxo_term_count_statuses` filter.

### E. Terms Merge — `class-test-staxo-merge.php` (`@group merge`, 20)

Phase one lists terms / shows terms-control warning; phase two disables destination (flat) and destination + ancestors (hierarchical, label `for` matches input `id`); phase three filters invalid sources and warns that children move (hierarchical only); single source (duplicate row, meta deleted, count); several sources (review H1); parent term (children move under the destination); parent and child together; destination below a source (moved up first, no loop); flat across posts and pages (object cache cleared); count rules respected; terms control kept; no valid sources; invalid / other-taxonomy destination; invalid taxonomy; subscriber; bad nonce.

### Main — `class-test-staxo-refreshed-main.php` (2)

Multisite flag as expected; plugin loaded.

## Planned

| Ref | Area | Notes |
|---|---|---|
| B | Create / update / delete taxonomy via admin form | Delete vs flush-delete; `list_order` cleanup (fixed 1 Oct); Export PHP |
| F | Terms conversion (copy between taxonomies) | Output feeds Terms Import; posts not moved |
| G | Rename taxonomy slug | Known bug: moves `default_taxonomy_<slug>` instead of `default_term_<slug>` |
| H | Terms control on save (classic and REST) | Note: `st_cc_type` compared with `===` to int 1 — check stored type |
| I | Front-end output | the_content/excerpt, shortcode, block, feed, admin filter, widget `filter_min` |
| J | Admin list ordering | |
| K | Security sweep | Capability + nonce on every state-changing handler |

## Behaviour decided for tests

- Merging a parent term moves its children under the destination term (decided 1 Oct 2026). If the destination is below a source, it is first moved up to the source's parent.
- A merge that leaves posts below the terms-control minimum warns but proceeds.
- Terms Import: an indented first line is top level; skipped terms are reported with line numbers.

## Change log

- **1 Oct 2026** — Shared fixtures and base classes; classes A (rewritten), C, D, E. Fixed hierarchical merge input id (`tax<ID>` → `tax_<ID>`).
- **1 Oct 2026** — phpcs: `$GLOBALS['wp_settings_errors']` writes moved into `clear_settings_errors()`; assignment alignment fixed in `import_config()`.
- **1 Oct 2026** — First CI run of the new classes. `test_fixture_matrix` exposed a plugin bug: `term_count_sel_cache()` read `$options['externals']` when no external taxonomies were configured ("Undefined array key" warning whenever a taxonomy STR doesn't manage, such as `category`, was counted). Fixed in `class-simpletaxonomyrefreshed-client.php`; every fixture-based test (classes D and E) depends on it.
- **1 Oct 2026** — Terms Merge now moves the children of each source term under the destination (`SimpleTaxonomyRefreshed_Admin_Merge::move_children()`); phase three says so for hierarchical taxonomies. Merge tests updated and 3 added (parent and child together, destination below a source, no notice for flat).
