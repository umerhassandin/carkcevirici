<?php
/**
 * Generates /llms.txt and /llms-full.txt per the llms.txt convention, so AI
 * answer engines have a clean, crawlable index of the site without having
 * to render JavaScript or wade through template chrome. Both files are
 * built from whatever pages/posts/hazir_cark entries are published, cached
 * in a transient, and regenerated the moment any of that content changes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cark_Llms {

	const QUERY_VAR    = 'cark_llms';
	const TRANSIENT_TXT  = 'cark_llms_txt';
	const TRANSIENT_FULL = 'cark_llms_full_txt';

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
		add_action( 'save_post', array( $this, 'clear_cache' ) );
	}

	public function add_rewrite_rules() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?' . self::QUERY_VAR . '=txt', 'top' );
		add_rewrite_rule( '^llms-full\.txt$', 'index.php?' . self::QUERY_VAR . '=full', 'top' );
	}

	public function add_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	public function clear_cache() {
		delete_transient( self::TRANSIENT_TXT );
		delete_transient( self::TRANSIENT_FULL );
	}

	public function maybe_render() {
		$mode = get_query_var( self::QUERY_VAR );

		if ( 'txt' === $mode ) {
			nocache_headers();
			header( 'Content-Type: text/markdown; charset=utf-8' );
			echo $this->get_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput -- plain text output, built from escaped/sanitized parts.
			exit;
		}

		if ( 'full' === $mode ) {
			nocache_headers();
			header( 'Content-Type: text/markdown; charset=utf-8' );
			echo $this->get_llms_full_txt(); // phpcs:ignore WordPress.Security.EscapeOutput
			exit;
		}
	}

	public function get_llms_txt() {
		$cached = get_transient( self::TRANSIENT_TXT );
		if ( false !== $cached ) {
			return $cached;
		}

		$lines   = array();
		$lines[] = '# Çark Çevirici';
		$lines[] = '';
		$lines[] = '> Çark Çevirici (carkcevirici.com), isim çekme, kura çekme, karar verme, çekiliş ve sınıf etkinlikleri için ücretsiz, hesap gerektirmeyen bir online çark çevirme aracıdır. Tüm araçlar tarayıcıda çalışır; kullanıcı verisi sunucuda saklanmaz.';
		$lines[] = '> Çark Çevirici (carkcevirici.com) is a free, no-signup online spinning wheel and random-picker toolset for Turkish users -- for names, prize draws, decisions and classroom use. Everything runs in the browser; no user data is stored server-side.';
		$lines[] = '';

		$tool_pages = $this->get_top_level_pages();
		if ( ! empty( $tool_pages ) ) {
			$lines[] = '## Araçlar';
			$lines[] = '';
			foreach ( $tool_pages as $page ) {
				$lines[] = $this->format_link_line( $page );
			}
			$lines[] = '';
		}

		if ( post_type_exists( 'hazir_cark' ) ) {
			$wheels = get_posts( array(
				'post_type'      => 'hazir_cark',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			) );
			if ( ! empty( $wheels ) ) {
				$lines[] = '## Hazır Çarklar';
				$lines[] = '';
				foreach ( $wheels as $wheel ) {
					$lines[] = $this->format_link_line( $wheel );
				}
				$lines[] = '';
			}
		}

		$guides = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		if ( ! empty( $guides ) ) {
			$lines[] = '## Rehber';
			$lines[] = '';
			foreach ( $guides as $guide ) {
				$lines[] = $this->format_link_line( $guide );
			}
			$lines[] = '';
		}

		$output = implode( "\n", $lines );
		set_transient( self::TRANSIENT_TXT, $output, DAY_IN_SECONDS );

		return $output;
	}

	public function get_llms_full_txt() {
		$cached = get_transient( self::TRANSIENT_FULL );
		if ( false !== $cached ) {
			return $cached;
		}

		$sections = array();

		$front_page_id = (int) get_option( 'page_on_front' );
		if ( $front_page_id ) {
			$sections[] = $this->render_post_markdown( get_post( $front_page_id ) );
		}

		foreach ( $this->get_top_level_pages() as $page ) {
			if ( $page->ID === $front_page_id ) {
				continue;
			}
			$sections[] = $this->render_post_markdown( $page );
		}

		if ( post_type_exists( 'hazir_cark' ) ) {
			$wheels = get_posts( array(
				'post_type'      => 'hazir_cark',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			) );
			foreach ( $wheels as $wheel ) {
				$sections[] = $this->render_post_markdown( $wheel );
			}
		}

		$methodology = get_page_by_path( 'nasil-calisir' );
		if ( $methodology && $methodology->ID !== $front_page_id ) {
			$sections[] = $this->render_post_markdown( $methodology );
		}

		$output = implode( "\n\n---\n\n", array_filter( $sections ) );
		set_transient( self::TRANSIENT_FULL, $output, DAY_IN_SECONDS );

		return $output;
	}

	private function get_top_level_pages() {
		return get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		) );
	}

	private function format_link_line( $post ) {
		$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 24 );
		$excerpt = trim( preg_replace( '/\s+/', ' ', (string) $excerpt ) );

		return sprintf( '- [%s](%s)%s', $post->post_title, get_permalink( $post ), $excerpt ? ': ' . $excerpt : '' );
	}

	/**
	 * Minimal, dependency-free HTML -> Markdown pass. It only needs to
	 * handle what the block editor actually outputs (headings, paragraphs,
	 * lists, links, bold/italic); anything else is stripped to plain text.
	 */
	private function render_post_markdown( $post ) {
		if ( ! $post || 'publish' !== $post->post_status ) {
			return '';
		}

		$html = apply_filters( 'the_content', $post->post_content );

		$html = preg_replace( '/<h1[^>]*>(.*?)<\/h1>/is', "\n# $1\n", $html );
		$html = preg_replace( '/<h2[^>]*>(.*?)<\/h2>/is', "\n## $1\n", $html );
		$html = preg_replace( '/<h3[^>]*>(.*?)<\/h3>/is', "\n### $1\n", $html );
		$html = preg_replace( '/<h4[^>]*>(.*?)<\/h4>/is', "\n#### $1\n", $html );
		$html = preg_replace( '/<li[^>]*>(.*?)<\/li>/is', "- $1\n", $html );
		$html = preg_replace( '/<(strong|b)[^>]*>(.*?)<\/\1>/is', '**$2**', $html );
		$html = preg_replace( '/<(em|i)[^>]*>(.*?)<\/\1>/is', '_$2_', $html );
		$html = preg_replace( '/<a[^>]+href="([^"]*)"[^>]*>(.*?)<\/a>/is', '[$2]($1)', $html );
		$html = preg_replace( '/<\/p>/is', "\n\n", $html );
		$html = wp_strip_all_tags( $html );
		$html = html_entity_decode( $html, ENT_QUOTES, 'UTF-8' );
		$html = preg_replace( "/\n{3,}/", "\n\n", $html );
		$html = trim( $html );

		return '# ' . $post->post_title . "\n\n" . $html;
	}
}
