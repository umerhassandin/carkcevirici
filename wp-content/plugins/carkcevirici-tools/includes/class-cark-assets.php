<?php
/**
 * Registers the plugin's CSS/JS but only enqueues them on pages that
 * actually render a tool, so no other page on the site pays for the
 * wheel's weight. Shortcodes and the Gutenberg block call enqueue_wheel()
 * or enqueue_tools() from their own render callback, which runs during
 * the_content -- well before wp_footer prints footer-registered scripts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cark_Assets {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register' ), 5 );
	}

	public function register() {
		$css_dir = CARK_PLUGIN_URL . 'assets/css/';
		$js_dir  = CARK_PLUGIN_URL . 'assets/js/';

		wp_register_style( 'cark-wheel', $css_dir . 'wheel.css', array(), CARK_VERSION );

		wp_register_script( 'cark-confetti', $js_dir . 'confetti.js', array(), CARK_VERSION, array(
			'in_footer' => true,
			'strategy'  => 'defer',
		) );

		wp_register_script( 'cark-wheel', $js_dir . 'wheel.js', array( 'cark-confetti' ), CARK_VERSION, array(
			'in_footer' => true,
			'strategy'  => 'defer',
		) );

		wp_register_script( 'cark-tools', $js_dir . 'tools.js', array(), CARK_VERSION, array(
			'in_footer' => true,
			'strategy'  => 'defer',
		) );

		wp_localize_script( 'cark-wheel', 'CARK_I18N', $this->get_i18n_strings() );
	}

	/**
	 * Enqueued by the [cark] shortcode / cark/wheel block render callback.
	 */
	public function enqueue_wheel() {
		wp_enqueue_style( 'cark-wheel' );
		wp_enqueue_script( 'cark-wheel' );
	}

	/**
	 * Enqueued by the non-wheel tool shortcodes (team builder, draw, dice...).
	 * They share the wheel's base styles (buttons, panels, dark mode) but not its JS.
	 */
	public function enqueue_tools() {
		wp_enqueue_style( 'cark-wheel' );
		wp_enqueue_script( 'cark-tools' );
	}

	private function get_i18n_strings() {
		return array(
			'winner'        => __( 'Kazanan', 'carkcevirici-tools' ),
			'spin'          => __( 'ÇEVİR', 'carkcevirici-tools' ),
			'spinning'      => __( 'Çevriliyor...', 'carkcevirici-tools' ),
			'addEntries'    => __( 'Çarkı çevirmek için en az iki seçenek ekle.', 'carkcevirici-tools' ),
			'spinAgain'     => __( 'Tekrar Çevir', 'carkcevirici-tools' ),
			'removeWinner'  => __( 'Kazananı Kaldır', 'carkcevirici-tools' ),
			'close'         => __( 'Kapat', 'carkcevirici-tools' ),
			'copied'        => __( 'Kopyalandı', 'carkcevirici-tools' ),
			'shareCopied'   => __( 'Paylaşım bağlantısı kopyalandı', 'carkcevirici-tools' ),
			'embedCopied'   => __( 'Ekleme kodu kopyalandı', 'carkcevirici-tools' ),
		);
	}
}
