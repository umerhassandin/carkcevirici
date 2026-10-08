<?php
/**
 * The /embed/ endpoint: a bare page containing only a wheel, meant to be
 * put in an <iframe> on someone else's site. It is noindex (it is not
 * content of its own -- the real page is the one that embeds it) and
 * carries a small credit link back to carkcevirici.com, which is the
 * natural backlink the "Sitene Ekle" feature is built to earn.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cark_Embed {

	const QUERY_VAR = 'cark_embed';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );
	}

	public function add_rewrite_rules() {
		add_rewrite_rule( '^embed/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	public function add_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	public function maybe_render() {
		if ( ! get_query_var( self::QUERY_VAR ) ) {
			return;
		}

		nocache_headers();

		$preset  = isset( $_GET['preset'] ) ? sanitize_title( wp_unslash( $_GET['preset'] ) ) : '';
		$title   = isset( $_GET['title'] ) ? sanitize_text_field( wp_unslash( $_GET['title'] ) ) : '';
		$theme   = isset( $_GET['theme'] ) ? sanitize_key( wp_unslash( $_GET['theme'] ) ) : 'klasik';
		$entries = isset( $_GET['entries'] ) ? sanitize_textarea_field( wp_unslash( $_GET['entries'] ) ) : '';

		$shortcode_atts = array(
			'preset' => $preset,
			'title'  => $title,
			'theme'  => $theme,
			'entries' => $entries,
		);

		$wheel_html = Cark_Shortcodes::instance()->render_wheel( $shortcode_atts );

		$css_url = CARK_PLUGIN_URL . 'assets/css/wheel.css';
		$js_url  = CARK_PLUGIN_URL . 'assets/js/wheel.js';
		$confetti_url = CARK_PLUGIN_URL . 'assets/js/confetti.js';
		$site_url = home_url( '/' );

		header( 'Content-Type: text/html; charset=utf-8' );
		?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $title ?: __( 'Çark Çevirici – Gömülü Çark', 'carkcevirici-tools' ) ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>?v=<?php echo esc_attr( CARK_VERSION ); ?>">
<style>
	html,body{margin:0;padding:8px;background:transparent;}
	.cark-embed-credit{display:block;text-align:center;font:12px/1.4 system-ui,sans-serif;margin-top:6px;color:#666;}
	.cark-embed-credit a{color:#2563eb;text-decoration:none;}
</style>
</head>
<body>
	<?php echo $wheel_html; // phpcs:ignore WordPress.Security.EscapeOutput -- wheel markup is already escaped field-by-field. ?>
	<a class="cark-embed-credit" href="<?php echo esc_url( $site_url ); ?>" target="_top" rel="noopener">
		<?php esc_html_e( 'Çark Çevirici ile oluşturuldu', 'carkcevirici-tools' ); ?>
	</a>
	<script src="<?php echo esc_url( $confetti_url ); ?>?v=<?php echo esc_attr( CARK_VERSION ); ?>" defer></script>
	<script src="<?php echo esc_url( $js_url ); ?>?v=<?php echo esc_attr( CARK_VERSION ); ?>" defer></script>
</body>
</html>
		<?php
		exit;
	}

	/**
	 * Builds the iframe snippet shown on "Sitene Ekle" / the embed generator page.
	 */
	public static function build_embed_snippet( $args = array() ) {
		$query = array();
		foreach ( array( 'preset', 'title', 'theme', 'entries' ) as $key ) {
			if ( ! empty( $args[ $key ] ) ) {
				$query[ $key ] = $args[ $key ];
			}
		}

		$url = add_query_arg( $query, home_url( '/embed/' ) );

		return sprintf(
			'<iframe src="%s" width="500" height="600" style="border:0;max-width:100%%;" loading="lazy" title="%s"></iframe>',
			esc_url( $url ),
			esc_attr__( 'Çark Çevirici', 'carkcevirici-tools' )
		);
	}
}
