<?php
/**
 * Registers the cark/wheel Gutenberg block. The block is dynamic (PHP
 * render_callback) so the front end always gets the exact same markup as
 * the [cark] shortcode -- one rendering code path, not two to keep in sync.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cark_Blocks {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'cark-block-editor',
			CARK_PLUGIN_URL . 'blocks/cark/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			CARK_VERSION,
			true
		);

		register_block_type( CARK_PLUGIN_DIR . 'blocks/cark', array(
			'editor_script'   => 'cark-block-editor',
			'render_callback' => array( __CLASS__, 'render' ),
		) );
	}

	/**
	 * Converts block attributes into [cark] shortcode attributes and
	 * delegates to the exact same renderer the shortcode uses.
	 */
	public static function render( $attributes ) {
		$shortcode_atts = array(
			'preset'        => $attributes['preset'] ?? '',
			'entries'       => isset( $attributes['entries'] ) ? str_replace( "\n", '|', $attributes['entries'] ) : '',
			'title'         => $attributes['title'] ?? '',
			'theme'         => $attributes['theme'] ?? 'klasik',
			'remove_winner' => ! empty( $attributes['removeWinner'] ) ? '1' : '0',
			'winners'       => $attributes['winners'] ?? 1,
			'duration'      => $attributes['duration'] ?? 6,
		);

		return Cark_Shortcodes::instance()->render_wheel( $shortcode_atts );
	}
}
