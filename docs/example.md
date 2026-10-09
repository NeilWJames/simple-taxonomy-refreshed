# The demo site

The demo site is a small music and arts magazine. It shows the main features of the plugin, and every screenshot in this documentation was taken from it.

**[Open the demo in WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/NeilWJames/simple-taxonomy-refreshed/master/playground/blueprint.json)**

> **Development or released version?** This demo, like these pages, follows the plugin's latest development code on GitHub. The **Live Preview** button on the plugin's [WordPress.org page](https://wordpress.org/plugins/simple-taxonomy-refreshed/) opens the same demo with the released version. Between releases the two can differ, so something described here may not be in the released version yet.

[WordPress Playground](https://wordpress.org/playground/) runs a whole WordPress site in your browser. The demo opens logged in as the administrator, on the **Taxonomies** screen. You can change anything you like: the site only exists in your browser tab, and closing the tab discards it.

## What the demo contains

### Five taxonomies defined by the plugin

Each one is based on a taxonomy used by the plugin's test suite, so the demo shows the behaviour that the tests check.

| Taxonomy | Name (slug) | Type | Used on | What it shows |
| -------- | ----------- | ---- | ------- | ------------- |
| Genres | `genre` | Hierarchical | Posts | Terms shown after the post content, with the text "Genres:" before them. Readable term URLs that follow the hierarchy, such as `/genre/music/jazz/`. |
| Colours | `colour` | Flat | Posts and pages | Terms shown after the content and the excerpt. REST base `colours`. A default term, "Unsorted". |
| Audiences | `audience` | Flat | Posts | Term limits: every post, whatever its status, needs 1 or 2 Audiences, checked when it is saved. |
| Topics | `topic` | Hierarchical | Posts | Term counts include drafts as well as published posts. A filter on the admin post list, showing the hierarchy and the counts. |
| Sections | `section` | Hierarchical | Posts | At most one Section, so the editor shows radio buttons with a "No term" choice. The limit never stops a save: it only gives a notice to users who cannot change the terms. |

The settings of each taxonomy are described in [Taxonomies](taxonomies.md), and the term limits in [Editing posts](post-editing.md).

### Extra functions on Tags

Tags is WordPress's own taxonomy, so the plugin cannot change its definition, but it can add functions to it. In the demo, Tags has:

- a filter on the admin post list, with the number of posts for each tag;
- term counts that include all posts except those in the trash.

See [Extra functions for existing taxonomies](taxonomies.md#extra-functions-for-existing-taxonomies).

### Terms

| Taxonomy | Terms |
| -------- | ----- |
| Genres | Music › Jazz › Bebop and Big Band; Music › Rock › Punk; Art › Painting and Sculpture; Misc |
| Topics | Science › Physics › Astronomy and Quantum; Science › Biology › Botany; Humanities › History and Philosophy; Misc |
| Colours | Red, Green, Blue, Yellow, Cyan |
| Audiences | Beginners, Experts, Students, Teachers, Everyone |
| Sections | News; Reviews › Albums and Concerts; Interviews |
| Tags | live, study |

### Posts

There is one post for each post status, so that term counts and term limits can be seen at work:

| Post | Status | Genres | Colours | Audiences | Topics | Sections |
| ---- | ------ | ------ | ------- | --------- | ------ | ------- |
| A night of jazz | Published | Jazz | Red, Green | Beginners | Physics | News |
| Bebop basics | Published | Bebop | Green | Experts | Astronomy | Albums |
| From jazz to bebop | Published | Jazz, Bebop | Blue | Beginners, Students | Physics | News |
| The big band sound | Published | Big Band | Red, Blue | Students | – | Concerts |
| Rock notes (draft) | Draft | Rock | Yellow | – | Biology | – |
| Punk review (pending) | Pending | Punk | Yellow | – | Botany | – |
| Painting diary (private) | Private | Painting | Cyan | – | History | – |
| Sculpture next year (scheduled) | Scheduled | Sculpture | – | – | Philosophy | – |
| Old jazz listings (trash) | Trash | Jazz | Red | – | Physics | – |

The last five posts have no Audience, so they are outside the Audiences limits. Open one of them in the editor to see the notice, and try to save it to see the save refused (see [Editing posts](post-editing.md)).

### A page of blocks

The page **Taxonomy blocks** (Colours: Red) contains:

- the Display Post Terms block, for Colours;
- the Taxonomy Cloud block twice: Genres as a cloud, and Topics as a list with post counts;
- the `[staxo_post_terms tax="colour"]` shortcode.

See [Showing terms on the site](display.md).

### Other settings

- The taxonomy columns of the Posts list are in this order: Genres, Topics, Sections, Audiences, Colours, Categories, Tags (see [Taxonomy List Order](tools.md#taxonomy-list-order)).
- Permalinks use the post name, for example `/a-night-of-jazz/`.
- WordPress's sample post and page are removed, and the taxonomies' labels use their own names ("Add New Genre", "All Topics") rather than WordPress's defaults for categories and tags.

## Things to try

- Open **Posts > All Posts**: the taxonomy columns, and the Topics and Tags filters above the list.
- Open **Posts > Add Post**: the Sections panel has radio buttons. Try to save the post without an Audience.
- Open **Rock notes (draft)** and try to save it without an Audience.
- View **A night of jazz** and the **Taxonomy blocks** page on the site.
- Change a setting in **Taxonomies > All Taxonomies**, for example the maximum number of Audiences, and see the effect in the editor.
- Merge the Colour Cyan into Blue with **Taxonomies > Terms Merge**.

## How the demo is built

The demo is part of the plugin, in its `playground` folder:

| File | Purpose |
| ---- | ------- |
| `blueprint.json` | The [Playground blueprint](https://wordpress.github.io/wordpress-playground/blueprints/) used by the link above. It installs the plugin from GitHub, sets the site title and permalinks, runs `demo-content.php` and logs in. |
| `blueprint-local.json` | The same, for a copy of the plugin on your own computer (see below). |
| `demo-content.php` | Creates the plugin settings, the terms, the posts and the page. Its `staxo_demo_load()` function stores the settings in the `simple-taxonomy` option, the same way the plugin's own screens do. |
| `playwright.config.js`, `screenshots.spec.js` | Take the screenshots used in this documentation. They are not included in the plugin download from WordPress.org. |
| `build-assets-blueprint.js` | Builds the Live Preview blueprint (see below). Not included in the plugin download either. |

### Two versions of the demo

| Blueprint | Used by | Installs |
| --------- | ------- | -------- |
| `playground/blueprint.json` | The links in this documentation | The latest development code, from the `master` branch on GitHub |
| `assets/blueprints/blueprint.json` | The **Live Preview** button on the [WordPress.org plugin page](https://wordpress.org/plugins/simple-taxonomy-refreshed/) | The released version, from WordPress.org |

The `assets` folder in the GitHub repository holds the files for the `assets` folder of the plugin's WordPress.org repository (SVN), where the Live Preview blueprint must be stored as `assets/blueprints/blueprint.json`. It is not part of the plugin itself.

A blueprint on WordPress.org cannot load other files from the `assets` folder, so the Live Preview blueprint carries the demo code itself, in its `runPHP` step. It is built, not edited by hand:

```sh
npm run assets-blueprint
```

This takes `playground/blueprint.json`, replaces its first step with one that installs the released plugin from WordPress.org, and replaces the step that loads `demo-content.php` with the code of `playground/demo-content.php`. It writes `assets/blueprints/blueprint.json`, which is then committed to the WordPress.org `assets/blueprints` folder.

So the two demos change separately:

- The files in `playground` change with the plugin code on GitHub, and the GitHub demo follows them straight away.
- The Live Preview only changes when the blueprint is built again and committed to WordPress.org, usually at a release. As its demo code runs against the released plugin, build it only once any plugin code the demo needs has been released.

## Running the demo on your computer

You need [Node.js](https://nodejs.org/) 20 or later and a copy of the plugin's GitHub repository.

```sh
npm install
npm run playground
```

The demo site starts at http://127.0.0.1:8889 (user `admin`, password `password`) with the plugin folder mounted, so changes to the plugin code show at once. Each start is a new site.

To take the screenshots again, with or without the demo already running:

```sh
npm run screenshots
```

The images are written to the `images` folder.
