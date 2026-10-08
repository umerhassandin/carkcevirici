<?php
/**
 * A lightweight contact form -- no Contact Form 7 / WPForms dependency.
 * [iletisim_formu] renders the form; submissions post to admin-post.php,
 * pass a honeypot check, and are emailed to the site's admin address.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cark_Contact {

	private static $instance = null;

	private $feedback = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'iletisim_formu', array( $this, 'render' ) );
		add_action( 'admin_post_nopriv_cark_contact', array( $this, 'handle_submit' ) );
		add_action( 'admin_post_cark_contact', array( $this, 'handle_submit' ) );
	}

	public function render( $atts ) {
		Cark_Assets::instance()->enqueue_tools();

		$id = 'iletisim-' . wp_rand( 1000, 9999 );

		ob_start();

		if ( isset( $_GET['cark_contact'] ) && 'ok' === $_GET['cark_contact'] ) {
			?>
			<div class="cark-tool" role="status">
				<p><?php esc_html_e( 'Mesajın için teşekkürler! En kısa sürede dönüş yapacağız.', 'carkcevirici-tools' ); ?></p>
			</div>
			<?php
			return ob_get_clean();
		}

		$error = isset( $_GET['cark_contact'] ) && 'error' === $_GET['cark_contact'];
		?>
		<div class="cark-tool" id="<?php echo esc_attr( $id ); ?>">
			<?php if ( $error ) : ?>
				<p role="alert"><?php esc_html_e( 'Bir şeyler ters gitti, lütfen tekrar dener misin?', 'carkcevirici-tools' ); ?></p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cark_contact">
				<?php wp_nonce_field( 'cark_contact_submit', 'cark_contact_nonce' ); ?>

				<p>
					<label for="<?php echo esc_attr( $id ); ?>-name"><?php esc_html_e( 'Adın', 'carkcevirici-tools' ); ?></label><br>
					<input type="text" id="<?php echo esc_attr( $id ); ?>-name" name="cark_name" required class="cark-entries-input" style="min-height:auto;padding:.6rem .8rem;">
				</p>
				<p>
					<label for="<?php echo esc_attr( $id ); ?>-email"><?php esc_html_e( 'E-posta adresin', 'carkcevirici-tools' ); ?></label><br>
					<input type="email" id="<?php echo esc_attr( $id ); ?>-email" name="cark_email" required class="cark-entries-input" style="min-height:auto;padding:.6rem .8rem;">
				</p>
				<p>
					<label for="<?php echo esc_attr( $id ); ?>-message"><?php esc_html_e( 'Mesajın', 'carkcevirici-tools' ); ?></label><br>
					<textarea id="<?php echo esc_attr( $id ); ?>-message" name="cark_message" required rows="6" class="cark-entries-input"></textarea>
				</p>

				<!-- Honeypot: hidden from real visitors via CSS, bots that auto-fill every field get caught. -->
				<p style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
					<label for="<?php echo esc_attr( $id ); ?>-website"><?php esc_html_e( 'Web siteniz (boş bırakın)', 'carkcevirici-tools' ); ?></label>
					<input type="text" id="<?php echo esc_attr( $id ); ?>-website" name="cark_website" tabindex="-1" autocomplete="off">
				</p>

				<button type="submit" class="cark-btn cark-btn-primary"><?php esc_html_e( 'Gönder', 'carkcevirici-tools' ); ?></button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function handle_submit() {
		$redirect_base = wp_get_referer() ? wp_get_referer() : home_url( '/iletisim/' );

		if ( ! isset( $_POST['cark_contact_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['cark_contact_nonce'] ), 'cark_contact_submit' ) ) {
			wp_safe_redirect( add_query_arg( 'cark_contact', 'error', $redirect_base ) );
			exit;
		}

		// Honeypot tripped -> silently pretend success so bots don't learn.
		if ( ! empty( $_POST['cark_website'] ) ) {
			wp_safe_redirect( add_query_arg( 'cark_contact', 'ok', $redirect_base ) );
			exit;
		}

		$name    = isset( $_POST['cark_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cark_name'] ) ) : '';
		$email   = isset( $_POST['cark_email'] ) ? sanitize_email( wp_unslash( $_POST['cark_email'] ) ) : '';
		$message = isset( $_POST['cark_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cark_message'] ) ) : '';

		if ( ! $name || ! is_email( $email ) || ! $message ) {
			wp_safe_redirect( add_query_arg( 'cark_contact', 'error', $redirect_base ) );
			exit;
		}

		$to      = get_option( 'admin_email' );
		$subject = sprintf( '[%s] Yeni iletişim mesajı', get_bloginfo( 'name' ) );
		$body    = sprintf(
			"Gönderen: %s <%s>\n\nMesaj:\n%s",
			$name,
			$email,
			$message
		);
		$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

		wp_mail( $to, $subject, $body, $headers );

		wp_safe_redirect( add_query_arg( 'cark_contact', 'ok', $redirect_base ) );
		exit;
	}
}
