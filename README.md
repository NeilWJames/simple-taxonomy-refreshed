# Simple Taxonomy Refreshed

[![CI](https://github.com/NeilWJames/simple-taxonomy-refreshed/actions/workflows/ci.yml/badge.svg)](https://github.com/NeilWJames/simple-taxonomy-refreshed/actions/workflows/ci.yml)
[![codecov](https://codecov.io/github/NeilWJames/simple-taxonomy-refreshed/graph/badge.svg?token=CL6BDUAKMY)](https://codecov.io/github/NeilWJames/simple-taxonomy-refreshed)

This WordPress plugin provides a no-code facility to manage your taxonomies - either by defining your own or by adding additional function to existing ones.

## Quick Start

**[Download from WordPress.org](https://wordpress.org/plugins/simple-taxonomy-refreshed/)** | **[View Documentation](docs/readme.md)** | **[Try it in WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/NeilWJames/simple-taxonomy-refreshed/master/playground/blueprint.json)**

The Playground link runs the latest development code from this repository. The Live Preview on the WordPress.org page runs the released version, so the two may differ.

## What is Simple Taxonomy Refreshed?

A WordPress plugin that simplifies the management of Taxomonies and their Terms for a WordPress site. 

## Key Features

Supports adding one or more taxonomies (either hierarchical or tag) to any objects registered on your installation just by giving them a name and some options on the admin screens.

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

A number of support tools are available for managing all your taxonomies :
- Export or Import the configuration.
- Export a taxonomy definition in PHP format so that it can be included directly in your code.
- Rename a taxonomy slug updating the internal references (terms and usages) to the new slug.
- Create terms for a taxonomy by entering them into a list.
- Creating that list by copying terms from an existing taxonomy.
- Merge terms together - updating their usages to be the merged term.

Additional information on plugin usage is available in the help pulldown area of the screens, and in the [documentation](docs/readme.md).

## Requirements

- **WordPress:** 6.9 or higher
- **PHP:** 8.2 or higher
- **Tested up to:** WordPress 7.1

## Installation

### From WordPress Admin (Recommended)

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "Simple Taxonomy Refreshed"
3. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the latest release from [WordPress.org](https://wordpress.org/plugins/simple-taxonomy-refreshed/)
2. Upload the plugin files to `/wp-content/plugins/simple-taxonomy-refreshed/`
3. Activate the plugin through the **Plugins** menu in WordPress

