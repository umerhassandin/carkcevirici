<?php
/**
 * 404 template. Per the project brief: don't just apologize, give the
 * visitor something to do -- a working wheel and links to the main tools.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main site-404">
	<div class="entry-content" style="max-width:720px;margin:0 auto;padding:2rem 1rem;text-align:center;">
		<h1><?php esc_html_e( 'Sayfa bulunamadı — en azından bir çark çevir!', 'kadence-child' ); ?></h1>
		<p>
			<?php esc_html_e( 'Aradığın sayfa taşınmış veya hiç var olmamış olabilir. Ama madem buradasın, aşağıdaki çarkı çevirebilirsin.', 'kadence-child' ); ?>
		</p>

		<?php echo do_shortcode( '[cark preset="anasayfa"]' ); ?>

		<h2><?php esc_html_e( 'Popüler araçlar', 'kadence-child' ); ?></h2>
		<ul style="list-style:none;padding:0;display:flex;flex-wrap:wrap;gap:.75rem;justify-content:center;">
			<li><a href="<?php echo esc_url( home_url( '/isim-carki/' ) ); ?>"><?php esc_html_e( 'İsim Çarkı', 'kadence-child' ); ?></a></li>
			<li><a href="<?php echo esc_url( home_url( '/kura-cekme/' ) ); ?>"><?php esc_html_e( 'Kura Çekme', 'kadence-child' ); ?></a></li>
			<li><a href="<?php echo esc_url( home_url( '/cekilis-carki/' ) ); ?>"><?php esc_html_e( 'Çekiliş Çarkı', 'kadence-child' ); ?></a></li>
			<li><a href="<?php echo esc_url( home_url( '/karar-carki/' ) ); ?>"><?php esc_html_e( 'Karar Çarkı', 'kadence-child' ); ?></a></li>
			<li><a href="<?php echo esc_url( home_url( '/araclar/' ) ); ?>"><?php esc_html_e( 'Tüm araçlar →', 'kadence-child' ); ?></a></li>
		</ul>
	</div>
</main>

<?php
get_footer();
