<?php
/**
 * Plugin Name:       Çark Çevirici Araçları
 * Plugin URI:        https://carkcevirici.com/
 * Description:       Çark Çevirici'nin özel çark ve rastgele seçim araçları (vanilla JS, harici kütüphane yok).
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Çark Çevirici
 * Author URI:        https://carkcevirici.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       carkcevirici-tools
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CARK_VERSION', '1.1.0' );
define( 'CARK_PLUGIN_FILE', __FILE__ );
define( 'CARK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CARK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once CARK_PLUGIN_DIR . 'includes/class-cark-presets.php';
require_once CARK_PLUGIN_DIR . 'includes/class-cark-assets.php';
require_once CARK_PLUGIN_DIR . 'includes/class-cark-shortcodes.php';
require_once CARK_PLUGIN_DIR . 'includes/class-cark-blocks.php';
require_once CARK_PLUGIN_DIR . 'includes/class-cark-embed.php';
require_once CARK_PLUGIN_DIR . 'includes/class-cark-llms.php';
require_once CARK_PLUGIN_DIR . 'includes/class-cark-admin.php';
require_once CARK_PLUGIN_DIR . 'includes/class-cark-contact.php';

/**
 * Boot every module. Each class wires its own hooks in its constructor,
 * so this is the single place that decides what the plugin loads.
 */
function cark_boot() {
	Cark_Presets::instance();
	Cark_Assets::instance();
	Cark_Shortcodes::instance();
	Cark_Blocks::instance();
	Cark_Embed::instance();
	Cark_Llms::instance();
	Cark_Contact::instance();

	if ( is_admin() ) {
		Cark_Admin::instance();
	}
}
add_action( 'plugins_loaded', 'cark_boot' );

/**
 * /embed/ and /llms.txt need rewrite rules, so both activation and the
 * ordinary init pass (in case the rules were ever lost after a migration)
 * must flush them.
 */
function cark_activate() {
	Cark_Embed::instance()->add_rewrite_rules();
	Cark_Llms::instance()->add_rewrite_rules();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cark_activate' );

function cark_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cark_deactivate' );
