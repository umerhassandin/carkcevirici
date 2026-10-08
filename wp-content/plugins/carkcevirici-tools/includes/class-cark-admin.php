<?php
/**
 * Ayarlar -> Çark Hazır Listeler: lets a site owner add or edit preset
 * wheels (the JSON [cark preset="..."] reads) without touching code.
 * Built-in presets from /presets/*.json are shown read-only as a
 * reference; anything saved here is stored in one option and can
 * override a built-in slug.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cark_Admin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_cark_save_preset', array( $this, 'handle_save' ) );
		add_action( 'admin_post_cark_delete_preset', array( $this, 'handle_delete' ) );
	}

	public function add_menu() {
		add_options_page(
			__( 'Çark Hazır Listeler', 'carkcevirici-tools' ),
			__( 'Çark Hazır Listeler', 'carkcevirici-tools' ),
			'manage_options',
			'cark-presets',
			array( $this, 'render_page' )
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$presets        = Cark_Presets::instance();
		$builtins       = $presets->get_builtin_presets();
		$customs        = $presets->get_custom_presets();
		$editing_slug   = isset( $_GET['edit'] ) ? sanitize_title( wp_unslash( $_GET['edit'] ) ) : '';
		$editing_preset = $editing_slug ? $presets->get_preset( $editing_slug ) : null;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Çark Hazır Listeler', 'carkcevirici-tools' ); ?></h1>
			<p>
				<?php esc_html_e( 'Burada eklediğin her liste, sitede [cark preset="slug"] kısa kodu veya Çark bloğu ile kullanılabilir.', 'carkcevirici-tools' ); ?>
			</p>

			<h2><?php esc_html_e( 'Yeni / Düzenle', 'carkcevirici-tools' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cark_save_preset' ); ?>
				<input type="hidden" name="action" value="cark_save_preset">
				<table class="form-table">
					<tr>
						<th><label for="cark-slug"><?php esc_html_e( 'Slug', 'carkcevirici-tools' ); ?></label></th>
						<td>
							<input type="text" id="cark-slug" name="slug" class="regular-text" required
								value="<?php echo esc_attr( $editing_slug ); ?>"
								<?php disabled( '' !== $editing_slug && isset( $builtins[ $editing_slug ] ) ); ?>>
							<p class="description"><?php esc_html_e( 'Örn: isim-carki. [cark preset="slug"] içinde kullanılır.', 'carkcevirici-tools' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="cark-title"><?php esc_html_e( 'Başlık', 'carkcevirici-tools' ); ?></label></th>
						<td><input type="text" id="cark-title" name="title" class="regular-text"
							value="<?php echo esc_attr( $editing_preset['title'] ?? '' ); ?>"></td>
					</tr>
					<tr>
						<th><label for="cark-entries"><?php esc_html_e( 'Seçenekler (her satıra bir tane)', 'carkcevirici-tools' ); ?></label></th>
						<td>
							<textarea id="cark-entries" name="entries" rows="10" class="large-text code"><?php
								if ( $editing_preset ) {
									$lines = array();
									foreach ( Cark_Presets::instance()->normalize_entries( $editing_preset ) as $e ) {
										$lines[] = $e['weight'] > 1 ? $e['text'] . '*' . $e['weight'] : $e['text'];
									}
									echo esc_textarea( implode( "\n", $lines ) );
								}
							?></textarea>
							<p class="description"><?php esc_html_e( 'Ağırlık vermek için "Ahmet*3" gibi yaz.', 'carkcevirici-tools' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="cark-theme"><?php esc_html_e( 'Renk teması', 'carkcevirici-tools' ); ?></label></th>
						<td>
							<select id="cark-theme" name="theme">
								<?php foreach ( array( 'klasik', 'okyanus', 'orman', 'gunbatimi', 'parti', 'pastel' ) as $theme ) : ?>
									<option value="<?php echo esc_attr( $theme ); ?>" <?php selected( $editing_preset['theme'] ?? 'klasik', $theme ); ?>>
										<?php echo esc_html( ucfirst( $theme ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Kazananı otomatik kaldır', 'carkcevirici-tools' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="remove_winner" value="1" <?php checked( ! empty( $editing_preset['removeWinnerDefault'] ) ); ?>>
								<?php esc_html_e( 'Varsayılan olarak açık', 'carkcevirici-tools' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Kaydet', 'carkcevirici-tools' ) ); ?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Eklenmiş Listeler (admin ekranından)', 'carkcevirici-tools' ); ?></h2>
			<?php if ( empty( $customs ) ) : ?>
				<p><?php esc_html_e( 'Henüz admin ekranından eklenmiş bir liste yok.', 'carkcevirici-tools' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Slug', 'carkcevirici-tools' ); ?></th>
							<th><?php esc_html_e( 'Başlık', 'carkcevirici-tools' ); ?></th>
							<th><?php esc_html_e( 'Seçenek sayısı', 'carkcevirici-tools' ); ?></th>
							<th><?php esc_html_e( 'Kısa kod', 'carkcevirici-tools' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $customs as $slug => $preset ) : ?>
							<tr>
								<td><?php echo esc_html( $slug ); ?></td>
								<td><?php echo esc_html( $preset['title'] ?? '' ); ?></td>
								<td><?php echo esc_html( count( $preset['entries'] ?? array() ) ); ?></td>
								<td><code>[cark preset="<?php echo esc_attr( $slug ); ?>"]</code></td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'cark-presets', 'edit' => $slug ), admin_url( 'options-general.php' ) ) ); ?>">
										<?php esc_html_e( 'Düzenle', 'carkcevirici-tools' ); ?>
									</a>
									|
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'cark_delete_preset', 'slug' => $slug ), admin_url( 'admin-post.php' ) ), 'cark_delete_preset' ) ); ?>"
										onclick="return confirm('<?php echo esc_js( __( 'Bu listeyi silmek istediğine emin misin?', 'carkcevirici-tools' ) ); ?>');">
										<?php esc_html_e( 'Sil', 'carkcevirici-tools' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Yerleşik Listeler (koddan gelir, referans)', 'carkcevirici-tools' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Slug', 'carkcevirici-tools' ); ?></th>
						<th><?php esc_html_e( 'Başlık', 'carkcevirici-tools' ); ?></th>
						<th><?php esc_html_e( 'Seçenek sayısı', 'carkcevirici-tools' ); ?></th>
						<th><?php esc_html_e( 'Kısa kod', 'carkcevirici-tools' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $builtins as $slug => $preset ) : ?>
						<tr>
							<td><?php echo esc_html( $slug ); ?></td>
							<td><?php echo esc_html( $preset['title'] ?? '' ); ?></td>
							<td><?php echo esc_html( count( $preset['entries'] ?? array() ) ); ?></td>
							<td><code>[cark preset="<?php echo esc_attr( $slug ); ?>"]</code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Yetkiniz yok.', 'carkcevirici-tools' ) );
		}
		check_admin_referer( 'cark_save_preset' );

		$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		if ( ! $slug ) {
			wp_die( esc_html__( 'Slug zorunludur.', 'carkcevirici-tools' ) );
		}

		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$theme   = isset( $_POST['theme'] ) ? sanitize_key( wp_unslash( $_POST['theme'] ) ) : 'klasik';
		$remove_winner = ! empty( $_POST['remove_winner'] );
		$raw_entries   = isset( $_POST['entries'] ) ? (string) wp_unslash( $_POST['entries'] ) : '';

		$entries = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $raw_entries ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			if ( preg_match( '/^(.*)\*(\d+)$/', $line, $m ) ) {
				$entries[] = array( 'text' => trim( $m[1] ), 'weight' => max( 1, (int) $m[2] ) );
			} else {
				$entries[] = array( 'text' => $line, 'weight' => 1 );
			}
		}

		$presets_handler = Cark_Presets::instance();
		$customs         = $presets_handler->get_custom_presets();

		$customs[ $slug ] = array(
			'title'               => $title ?: $slug,
			'theme'               => $theme,
			'removeWinnerDefault' => $remove_winner,
			'entries'             => $entries,
		);

		$presets_handler->save_custom_presets( $customs );

		wp_safe_redirect( add_query_arg( array( 'page' => 'cark-presets', 'saved' => '1' ), admin_url( 'options-general.php' ) ) );
		exit;
	}

	public function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Yetkiniz yok.', 'carkcevirici-tools' ) );
		}
		check_admin_referer( 'cark_delete_preset' );

		$slug = isset( $_GET['slug'] ) ? sanitize_title( wp_unslash( $_GET['slug'] ) ) : '';
		if ( $slug ) {
			$presets_handler = Cark_Presets::instance();
			$customs         = $presets_handler->get_custom_presets();
			unset( $customs[ $slug ] );
			$presets_handler->save_custom_presets( $customs );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'cark-presets', 'deleted' => '1' ), admin_url( 'options-general.php' ) ) );
		exit;
	}
}
