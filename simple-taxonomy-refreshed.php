<?php
/**
 * Plugin Name:       Simple Taxonomy Refreshed
 * Plugin URI:        https://github.com/NeilWJames/simple-taxonomy-refreshed
 * Description:       WordPress provides simple custom taxonomy, this plugin makes it even simpler, removing the need for you to write <em>any</em> code
 *                    Converted, Standardised and Extended from Simple Taxonomy by Amaury Balmer
 * Version:           2.1.0
 * Requires at least: 4.8
 * Requires PHP:      5.6
 * Author:            Neil James
 * License:           GPL v3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       simple-taxonomy-refreshed
 *
 * @package           simple-taxonomy-refreshed
 */

/*
Copyright This Version 2019-22 Neil James (neil@familyjames.com)
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

add_action( 'plugins_loaded', 'init_staxo_refreshed' );

/**
 * Initialise the client and set for widget/admin initialisation.
 *
 * @return void
 * @author Neil James from Amaury Balmer
 */
function init_staxo_refreshed() {
	// Detect if Simple Taxonomy is active. If found then bail with message.
	if ( in_array( 'simple-taxonomy/simple-taxonomy.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
		// Plugin is activated.
		alert_if_original_active();
		add_action( 'wp_head', 'alert_if_original_active' );
		return;
	}

	// Option name.
	define( 'OPTION_STAXO', 'simple-taxonomy' );

	// Call client classes.
	require_once __DIR__ . '/includes/class-simpletaxonomyrefreshed-client.php';
	require_once __DIR__ . '/includes/class-simpletaxonomyrefreshed-widget.php';

	// Client.
	global $strc, $stra;
	$strc = new SimpleTaxonomyRefreshed_Client();

	// Admin (Load when needed).
	if ( is_admin() ) {
		// Load admin classes.
		require_once __DIR__ . '/includes/class-simpletaxonomyrefreshed-admin.php';
		require_once __DIR__ . '/includes/class-simpletaxonomyrefreshed-admin-conversion.php';
		require_once __DIR__ . '/includes/class-simpletaxonomyrefreshed-admin-import.php';
		require_once __DIR__ . '/includes/class-simpletaxonomyrefreshed-admin-merge.php';
		require_once __DIR__ . '/includes/class-simpletaxonomyrefreshed-admin-order.php';
		require_once __DIR__ . '/includes/class-simpletaxonomyrefreshed-admin-rename.php';
		require_once __DIR__ . '/includes/class-simpletaxonomyrefreshed-admin-config.php';

		if ( ! $stra ) {
			$stra = SimpleTaxonomyRefreshed_Admin::get_instance();
		}
		$staxo_c = SimpleTaxonomyRefreshed_Admin_Conversion::get_instance();
		$staxo_i = SimpleTaxonomyRefreshed_Admin_Import::get_instance();
		$staxo_o = SimpleTaxonomyRefreshed_Admin_Merge::get_instance();
		$staxo_o = SimpleTaxonomyRefreshed_Admin_Order::get_instance();
		$staxo_r = SimpleTaxonomyRefreshed_Admin_Rename::get_instance();
		$staxo_e = SimpleTaxonomyRefreshed_Admin_Config::get_instance();
	}

	// Widget.
	add_action( 'widgets_init', 'init_staxo_widget' );

	// And its block equivalent.
	add_action( 'init', 'staxo_widgets_block_init', 99999 );

}

/**
 * Register the widget.
 *
 * @return void
 * @author Neil James
 */
function init_staxo_widget() {
	register_widget( 'SimpleTaxonomyRefreshed_Widget' );
}

/**
 * Callback to register the widget block.
 *
 * Call with low priority to let taxonomies be registered.
 */
function staxo_widgets_block_init() {
	$staxo_widget = new SimpleTaxonomyRefreshed_Widget();
	$staxo_widget->staxo_widget_block();
}

/**
 * Alert if Simple Taxonomy active.
 *
 * @return void
 * @author Neil James
 */
function alert_if_original_active() {
	?>
		<script type="text/javascript">
				function alert_message() {
						<?php
						echo 'alert("';
						// translators: Do not translate Simple Taxonomy.
						esc_html_e( 'Plugin Simple Taxonomy is active.', 'simple-taxonomy-refreshed' );
						echo '\n';
						// translators: Do not translate Simple Taxonomy Refreshed.
						esc_html_e( 'Although Simple Taxonomy Refreshed is also active, its use has been disabled.', 'simple-taxonomy-refreshed' );
						echo '\n';
						// translators: Do not translate Simple Taxonomy.
						esc_html_e( 'Inactivate the plugin Simple Taxonomy to use.', 'simple-taxonomy-refreshed' );
						echo '");';
						?>
				}
				window.onload = alert_message;
		</script>

		<?php
}
