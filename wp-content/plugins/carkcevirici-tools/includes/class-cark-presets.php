<?php
/**
 * Loads built-in preset wheels from /presets/*.json and merges in any
 * presets saved from the admin screen (Ayarlar -> Çark Hazır Listeler),
 * so new topic wheels can be added without touching code.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cark_Presets {

	const OPTION_KEY = 'cark_custom_presets';

	private static $instance = null;

	private $cache = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Built-in presets shipped in /presets, keyed by slug (filename minus .json).
	 */
	public function get_builtin_presets() {
		$presets = array();
		$dir     = CARK_PLUGIN_DIR . 'presets/';

		if ( ! is_dir( $dir ) ) {
			return $presets;
		}

		foreach ( glob( $dir . '*.json' ) as $file ) {
			$slug = basename( $file, '.json' );
			$data = json_decode( (string) file_get_contents( $file ), true );

			if ( is_array( $data ) ) {
				$presets[ $slug ] = $data;
			}
		}

		return $presets;
	}

	/**
	 * Presets added/edited from the admin screen, stored as one option.
	 */
	public function get_custom_presets() {
		$stored = get_option( self::OPTION_KEY, array() );
		return is_array( $stored ) ? $stored : array();
	}

	public function save_custom_presets( $presets ) {
		update_option( self::OPTION_KEY, $presets, false );
		$this->cache = null;
	}

	/**
	 * Custom presets win over built-ins with the same slug, so a site owner
	 * can override a shipped preset from the admin screen.
	 */
	public function get_all_presets() {
		if ( null === $this->cache ) {
			$this->cache = array_merge( $this->get_builtin_presets(), $this->get_custom_presets() );
		}
		return $this->cache;
	}

	public function get_preset( $slug ) {
		$all = $this->get_all_presets();
		return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
	}

	/**
	 * Normalizes a preset (or shortcode attributes) into the shape the
	 * front-end wheel expects: a title and a list of plain-text entries.
	 */
	public function normalize_entries( $preset ) {
		if ( ! is_array( $preset ) || empty( $preset['entries'] ) ) {
			return array();
		}

		$entries = array();
		foreach ( $preset['entries'] as $entry ) {
			if ( is_array( $entry ) ) {
				$entries[] = array(
					'text'   => isset( $entry['text'] ) ? (string) $entry['text'] : '',
					'weight' => isset( $entry['weight'] ) ? max( 1, (int) $entry['weight'] ) : 1,
				);
			} else {
				$entries[] = array(
					'text'   => (string) $entry,
					'weight' => 1,
				);
			}
		}

		return array_values( array_filter( $entries, function ( $e ) {
			return '' !== trim( $e['text'] );
		} ) );
	}
}
