# Simple Taxonomy Refreshed

Simple Taxonomy Refreshed lets you manage the taxonomies of a WordPress site without writing code. You can define your own taxonomies and add extra functions to existing ones, such as Categories, Tags or taxonomies from other plugins.

**[Try it in WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/NeilWJames/simple-taxonomy-refreshed/master/playground/blueprint.json)**: a demo site opens in your browser with the plugin and some example taxonomies, posts and blocks already set up. Nothing is installed on your computer, and the site is discarded when you close the tab.

> **Development or released version?** This demo, like these pages, follows the plugin's latest development code on GitHub. The **Live Preview** button on the plugin's [WordPress.org page](https://wordpress.org/plugins/simple-taxonomy-refreshed/) opens the same demo with the released version. Between releases the two can differ, so something described here may not be in the released version yet.

The examples and screenshots in these pages all come from that demo site. [The demo site](example.md) describes what it contains.

## What the plugin does

- **Defines taxonomies.** Give a taxonomy a name, choose whether it is hierarchical (like Categories) or flat (like Tags), and choose the post types that use it. All the other settings of [`register_taxonomy()`](https://developer.wordpress.org/reference/functions/register_taxonomy/) are available, on the same screen.
- **Controls the number of terms on a post.** Set a minimum and a maximum number of terms for a taxonomy. When a post can have only one term, its checkboxes become radio buttons.
- **Adds filters to the admin post lists**, so posts can be listed by the terms of a taxonomy.
- **Counts other post statuses.** Term counts can include drafts, scheduled or private posts, not only published ones.
- **Orders the taxonomy columns** of the admin post lists.
- **Shows the terms of a post** after its content, with a shortcode, or with the Display Post Terms block.
- **Shows a term cloud or list** with the Taxonomy Cloud block or the widget.
- **Provides tools for terms:** import a list of terms, copy terms from one taxonomy to another, merge terms, rename a taxonomy, and export or import the whole configuration.
- **Supports WPGraphQL.**

The extra functions (term limits, admin list filter, term counts, list order and WPGraphQL) work for any taxonomy, not only those defined by the plugin.

## Documentation

| Page | Contents |
| ---- | -------- |
| [The demo site](example.md) | The Playground demo: its taxonomies, posts and page, and how to run it on your own computer. |
| [Taxonomies](taxonomies.md) | The Taxonomies menu. Adding and changing a taxonomy: every tab of the form. Extra functions for existing taxonomies. Export PHP and deleting a taxonomy. |
| [Editing posts](post-editing.md) | Term limits in the block editor, the classic editor and Quick Edit. The admin post lists: columns, filters and term counts. |
| [Showing terms on the site](display.md) | Terms after the post content, the `[staxo_post_terms]` shortcode, the Display Post Terms and Taxonomy Cloud blocks, and the widget. |
| [Tools](tools.md) | Taxonomy List Order, Configuration Export/Import, Rename Taxonomy Slug, Terms Migrate, Terms Import and Terms Merge. |
| [Filters](filters.md) | Filters for developers. |
| [Changelog](changelog.md) | Changes in each version. |
| [Security](SECURITY.md) | How to report a security problem. |

Each plugin screen also has help in the Help tab at the top right of the screen.

## Requirements

- WordPress 6.9 or later
- PHP 8.2 or later

## Installing

1. In the WordPress admin, go to **Plugins > Add New Plugin** and search for "Simple Taxonomy Refreshed".
2. Click **Install Now**, then **Activate**.
3. Go to **Taxonomies > Add Taxonomy** to define your first taxonomy (see [Taxonomies](taxonomies.md)), or to **Taxonomies > All Taxonomies** to add functions to an existing one.

You can also download the plugin from [WordPress.org](https://wordpress.org/plugins/simple-taxonomy-refreshed/) and upload it to `wp-content/plugins/`.

## Source code

The plugin is developed on [GitHub](https://github.com/NeilWJames/simple-taxonomy-refreshed). Issues and suggestions are welcome there.
