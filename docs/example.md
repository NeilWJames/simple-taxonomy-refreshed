# Example Plug-in Usage

## Creation of a Taxonomy

We will add a taxonomy called test_hier with a few minimal attributes on the various panels:

<u>Main Options</u>       

	Name: test_hier
	Type: Hierarchical
	Attached to: Posts
	Display terms on: Content

<u>Labels</u>

	Name: Test Terms
	Menu Name: Test Terms
	Singular Name: Test Term

And then added using the *Add Taxonomy* button.

## Display of a Taxonomy

The listing will now contain the Taxonomy:

![Taxonomy Listing](../images/AddTaxList.png)

Clicking on *Export PHP* will download a file call test_hier.php to the browser with the content:

	<?php
	/*
	Plugin Name: XXX - Test Terms
	Version: x.y.z
	Plugin URI: http://www.example.com
	Description: XXX - Taxonomy Test Terms
	Author: XXX - Simple Taxonomy Generator
	Author URI: http://www.example.com
	
	----
	
	Copyright 2019 - XXX-Author
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
	*/
	
	add_action( 'init', 'register_staxo_test_hier', 10 );
	
	function register_staxo_test_hier() {
	register_taxonomy( "test_hier", 
	  array (
	  0 => 'post',
	),
	  array (
	  'name' => 'test_hier',
	  'description' => '',
	  'labels' => 
	  array (
	    'name' => 'Test Terms',
	    'menu_name' => 'Test Terms',
	    'singular_name' => 'Test Term',
	    'search_items' => 'Search Terms',
	    'popular_items' => 'Popular Terms',
	    'all_items' => 'All Terms',
	    'parent_item' => 'Parent Term',
	    'parent_item_colon' => 'Parent Term:',
	    'edit_item' => 'Edit Term',
	    'view_item' => 'View Term',
	    'update_item' => 'Update Term',
	    'add_new_item' => 'Add New Term',
	    'new_item_name' => 'New Term Name',
	    'separate_items_with_commas' => 'Separate terms with commas',
	    'add_or_remove_items' => 'Add or remove terms',
	    'choose_from_most_used' => 'Choose from the most used terms',
	    'not_found' => 'No Terms found',
	    'no_terms' => 'No Terms',
	    'items_list_navigation' => 'Terms list navigation',
	    'items_list' => 'Terms list',
	    'most_used' => 'Most Used',
	    'back_to_items' => '← Back to Terms',
	  ),
	  'public' => true,
	  'publicly_queryable' => true,
	  'hierarchical' => true,
	  'show_ui' => true,
	  'show_in_menu' => true,
	  'show_in_nav_menus' => true,
	  'show_tagcloud' => true,
	  'show_in_quick_edit' => true,
	  'show_admin_column' => true,
	  'capabilities' => 
	  array (
	    'manage_terms' => 'manage_categories',
	    'edit_terms' => 'manage_categories',
	    'delete_terms' => 'manage_categories',
	    'assign_terms' => 'edit_posts',
	  ),
	  'rewrite' => false,
	  'query_var' => 'test_hier',
	  'show_in_rest' => true,
	  'rest_base' => '',
	  'rest_controller_class' => '',
	  'sort' => false,
	) );
	}
	// Display Terms with Posts: content.

This can be included in your own code - but then should be deleted as a custom taxonomy.

You can also use it as a single page reference, which is why the last line comment has been added.

## Adding Terms to the taxonomy
Since show_in_menu is set to true, it is available as a sub-menu from the Posts menu.

However, we'll add some via the [Terms Import](./TermImp.md) functionality.

Enter the four terms for the taxonomy using leading tabs to denote the levels:

![Enter terms](../images/AddTermImp.png)

Once the Import these words as Terms button have been pressed, we can see them in the Terms screen.

![Test terms](../images/AddTestTerms.png)

## Using the Taxonomy in the Post

Now create a post using the block editor with some Terms added:

![Create Post](../images/AddPostTerms.png)

When viewing the Post, we can see the terms that have been added:

![View Post](../images/ShowPostTerms.png)

## Migrating Terms

We can use the Terms migrator to copy the terms from one taxonomy to another.

We will copy the ones just entered into Categories. After clicking on the *Copy From* of the Test Terms taxonomy.

![Terms migrate screen](../images/MigScreen1.png)

Various options have been made unavailable as a result of that initial click. Click on the *To* option of the Category taxonomy. 

![Terms migrate screen](../images/MigScreen2.png)

Now that both options have been selected, the Copy Terms button has become available.

![Terms migrate screen](../images/MigScreen3.png)

Once this is clicked, it is now possible to click the *Copy Terms* button. This extracts the *all* the terms making them available in the Terms import form.

### Importing the Terms

![Terms migrate screen](../images/MigScreen4.png)

When the Import button is clicked, the data will be loaded. Confirmation messages are output.

![Initial messages](../images/Imp1st.png)

These show the number of non-blank lines processed, together with the number of terms created (if any).

As the same screen (and data) is returned to the user, clicking the Import button again shows a slightly different message. The same number of items have been processed, but nothing has added. 

![Update messages](../images/Imp2nd.png)

When the data is entered, they can be seen within the Categories Terms

![Terms migrate screen](../images/MigScreen5.png)

As the data has been entered hierarchically, this is how it is loaded.

![Terms migrate screen](../images/MigScreen6.png)


