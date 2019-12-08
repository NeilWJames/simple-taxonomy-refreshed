<?php
/**
 * Plugin Name:       Simple Taxonomy 2
 * Plugin URI:        https://github.com/NeilWJames/simple-taxonomy-2
 * Description:       WordPress provides simple custom taxonomy, this plugin makes it even simpler, removing the need for you to write <em>any</em> code
 *                    Converted, Standardised and Extended from Simple Taxonomy by Amaury Balmer
 * Version:           1.0.0
 * Requires at least: 4.2
 * Requires PHP:      5.6
 * Author:            Neil James
 * License:           GPL v3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       simple-taxonomy-2
 *
 * @package           simple-taxonomy-2
 */

/*
Copyright This Version 2019 Neil James (neil@familyjames.com)
Copyright Original Version 2010-2013 Amaury Balmer (amaury@beapi.fr)

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

// Folder name.
define( 'STAXO_OPTION', 'simple-taxonomy' );

// Call client classes.
require_once __DIR__ . '/includes/class-simpletaxonomy-client.php';
require_once __DIR__ . '/includes/class-simpletaxonomy-widget.php';

add_action( 'plugins_loaded', 'init_simple_taxonomy' );

/**
 * Initialise the client and set for widget/admin initialisation.
 *
 * @return void
 * @author Neil James from Amaury Balmer
 */
function init_simple_taxonomy() {
	// Client.
	new SimpleTaxonomy_Client();

	// Admin (Load when needed).
	if ( is_admin() ) {
		// Load admin classes.
		require_once __DIR__ . '/includes/class-simpletaxonomy-admin.php';
		require_once __DIR__ . '/includes/class-simpletaxonomy-admin-conversion.php';
		require_once __DIR__ . '/includes/class-simpletaxonomy-admin-import.php';

		$staxo_a = SimpleTaxonomy_Admin::get_instance();
		$staxo_c = SimpleTaxonomy_Admin_Conversion::get_instance();
		new SimpleTaxonomy_Admin_Import();
	}

	// Widget.
	add_action( 'widgets_init', 'init_staxo_widget' );
}

/**
 * Register the widget.
 *
 * @return void
 * @author Neil James
 */
function init_staxo_widget() {
	register_widget( 'SimpleTaxonomy_Widget' );
}
