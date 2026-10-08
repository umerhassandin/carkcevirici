<?php
/**
 * Shortcodes for the wheel and the five non-wheel tools. Every shortcode
 * renders real server-side HTML (entries as a plain <ol>, team lists, dice
 * faces) so the content exists for crawlers with JavaScript off; wheel.js /
 * tools.js then hydrate that markup on the pages that load them.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cark_Shortcodes {

	private static $instance = null;

	private $counter = 0;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'cark', array( $this, 'render_wheel' ) );
		add_shortcode( 'takim_olusturucu', array( $this, 'render_takim_olusturucu' ) );
		add_shortcode( 'kura_cekme', array( $this, 'render_kura_cekme' ) );
		add_shortcode( 'sayi_secici', array( $this, 'render_sayi_secici' ) );
		add_shortcode( 'yazi_tura', array( $this, 'render_yazi_tura' ) );
		add_shortcode( 'zar_at', array( $this, 'render_zar_at' ) );
	}

	/* -------------------------------------------------------------- *
	 *  [cark]
	 * -------------------------------------------------------------- */

	public function render_wheel( $atts ) {
		$atts = shortcode_atts( array(
			'preset'        => '',
			'entries'       => '',
			'title'         => '',
			'theme'         => 'klasik',
			'remove_winner' => '',
			'winners'       => 1,
			'min'           => '',
			'max'           => '',
			'sound'         => 'on',
			'duration'      => 6,
		), $atts, 'cark' );

		$preset_data = $atts['preset'] ? Cark_Presets::instance()->get_preset( sanitize_title( $atts['preset'] ) ) : null;

		$title     = $atts['title'];
		$entries   = array();
		$theme     = $atts['theme'];
		$remove_winner_default = false;
		$type      = 'list';
		$min       = $atts['min'] !== '' ? (int) $atts['min'] : 1;
		$max       = $atts['max'] !== '' ? (int) $atts['max'] : 100;

		if ( $preset_data ) {
			$title                  = $title ?: ( $preset_data['title'] ?? '' );
			$theme                  = $preset_data['theme'] ?? $theme;
			$remove_winner_default  = ! empty( $preset_data['removeWinnerDefault'] );
			$entries                = Cark_Presets::instance()->normalize_entries( $preset_data );
			if ( isset( $preset_data['type'] ) && 'range' === $preset_data['type'] ) {
				$type = 'range';
				$min  = (int) ( $preset_data['min'] ?? 1 );
				$max  = (int) ( $preset_data['max'] ?? 100 );
			}
		}

		if ( $atts['entries'] ) {
			$entries = array();
			foreach ( explode( '|', $atts['entries'] ) as $line ) {
				$line = trim( $line );
				if ( '' !== $line ) {
					$entries[] = array( 'text' => $line, 'weight' => 1 );
				}
			}
		}

		if ( '' !== $atts['remove_winner'] ) {
			$remove_winner_default = in_array( $atts['remove_winner'], array( '1', 'true', 'yes', 'evet' ), true );
		}

		if ( ! $title ) {
			$title = __( 'Çark Çevir', 'carkcevirici-tools' );
		}

		$this->counter++;
		$id = 'cark-' . $this->counter . '-' . wp_rand( 1000, 9999 );

		$config = array(
			'id'            => $id,
			'title'         => $title,
			'type'          => $type,
			'entries'       => $entries,
			'min'           => $min,
			'max'           => $max,
			'theme'         => $theme,
			'removeWinner'  => $remove_winner_default,
			'winnersPerSpin' => max( 1, (int) $atts['winners'] ),
			'sound'         => 'off' !== $atts['sound'],
			'duration'      => max( 3, min( 10, (float) $atts['duration'] ) ),
		);

		Cark_Assets::instance()->enqueue_wheel();

		ob_start();
		?>
		<div class="cark-wheel" id="<?php echo esc_attr( $id ); ?>" data-cark="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
			<h2 class="cark-title screen-reader-text"><?php echo esc_html( $title ); ?></h2>

			<div class="cark-layout">
				<div class="cark-stage">
					<div class="cark-canvas-wrap">
						<canvas class="cark-canvas" width="640" height="640" aria-hidden="true"></canvas>
						<button type="button" class="cark-spin-btn">
							<?php esc_html_e( 'ÇEVİR', 'carkcevirici-tools' ); ?>
						</button>
					</div>
					<p class="cark-live" aria-live="polite" role="status"></p>
					<div class="cark-toolbar" role="toolbar" aria-label="<?php esc_attr_e( 'Çark araçları', 'carkcevirici-tools' ); ?>">
						<button type="button" class="cark-btn" data-action="fullscreen"><?php esc_html_e( 'Tam Ekran', 'carkcevirici-tools' ); ?></button>
						<button type="button" class="cark-btn" data-action="mute"><?php esc_html_e( 'Sesi Kapat', 'carkcevirici-tools' ); ?></button>
						<button type="button" class="cark-btn" data-action="share"><?php esc_html_e( 'Paylaş', 'carkcevirici-tools' ); ?></button>
						<button type="button" class="cark-btn" data-action="embed"><?php esc_html_e( 'Sitene Ekle', 'carkcevirici-tools' ); ?></button>
						<button type="button" class="cark-btn" data-action="my-wheels"><?php esc_html_e( 'Çarklarım', 'carkcevirici-tools' ); ?></button>
						<button type="button" class="cark-btn" data-action="settings" aria-expanded="false"><?php esc_html_e( 'Ayarlar', 'carkcevirici-tools' ); ?></button>
					</div>
				</div>

				<div class="cark-panel">
					<label class="cark-label" for="<?php echo esc_attr( $id ); ?>-entries">
						<?php esc_html_e( 'Her satıra bir seçenek yaz', 'carkcevirici-tools' ); ?>
					</label>
					<textarea class="cark-entries-input" id="<?php echo esc_attr( $id ); ?>-entries" rows="10"
						placeholder="<?php esc_attr_e( "Ahmet\nAyşe\nMehmet", 'carkcevirici-tools' ); ?>"></textarea>
					<div class="cark-entry-actions">
						<button type="button" class="cark-btn cark-btn-sm" data-action="shuffle"><?php esc_html_e( 'Karıştır', 'carkcevirici-tools' ); ?></button>
						<button type="button" class="cark-btn cark-btn-sm" data-action="sort"><?php esc_html_e( 'Sırala', 'carkcevirici-tools' ); ?></button>
						<button type="button" class="cark-btn cark-btn-sm" data-action="clear"><?php esc_html_e( 'Temizle', 'carkcevirici-tools' ); ?></button>
						<button type="button" class="cark-btn cark-btn-sm" data-action="sample"><?php esc_html_e( 'Örnek liste yükle', 'carkcevirici-tools' ); ?></button>
						<label class="cark-btn cark-btn-sm cark-file-label">
							<?php esc_html_e( '.txt / .csv Aktar', 'carkcevirici-tools' ); ?>
							<input type="file" class="cark-import-file" accept=".txt,.csv" hidden>
						</label>
					</div>
					<p class="cark-entry-count"></p>
				</div>
			</div>

			<div class="cark-settings-panel" hidden>
				<h3><?php esc_html_e( 'Ayarlar', 'carkcevirici-tools' ); ?></h3>
				<div class="cark-settings-grid">
					<label>
						<?php esc_html_e( 'Dönüş süresi (saniye)', 'carkcevirici-tools' ); ?>
						<input type="range" min="3" max="10" step="0.5" class="cark-set-duration">
					</label>
					<label>
						<input type="checkbox" class="cark-set-remove-winner"> <?php esc_html_e( 'Kazananı otomatik kaldır', 'carkcevirici-tools' ); ?>
					</label>
					<label>
						<input type="checkbox" class="cark-set-weights"> <?php esc_html_e( 'Ağırlıklı seçenekler (ör. Ahmet*3)', 'carkcevirici-tools' ); ?>
					</label>
					<label>
						<?php esc_html_e( 'Her çevirişte kazanan sayısı', 'carkcevirici-tools' ); ?>
						<input type="number" min="1" max="20" class="cark-set-winners">
					</label>
					<label>
						<?php esc_html_e( 'Renk teması', 'carkcevirici-tools' ); ?>
						<select class="cark-set-theme">
							<option value="klasik"><?php esc_html_e( 'Klasik', 'carkcevirici-tools' ); ?></option>
							<option value="okyanus"><?php esc_html_e( 'Okyanus', 'carkcevirici-tools' ); ?></option>
							<option value="orman"><?php esc_html_e( 'Orman', 'carkcevirici-tools' ); ?></option>
							<option value="gunbatimi"><?php esc_html_e( 'Gün Batımı', 'carkcevirici-tools' ); ?></option>
							<option value="parti"><?php esc_html_e( 'Parti', 'carkcevirici-tools' ); ?></option>
							<option value="pastel"><?php esc_html_e( 'Pastel', 'carkcevirici-tools' ); ?></option>
						</select>
					</label>
					<label>
						<input type="checkbox" class="cark-set-dark"> <?php esc_html_e( 'Karanlık mod', 'carkcevirici-tools' ); ?>
					</label>
				</div>
			</div>

			<div class="cark-history" hidden>
				<h3><?php esc_html_e( 'Kazananlar Geçmişi', 'carkcevirici-tools' ); ?></h3>
				<ol class="cark-history-list"></ol>
				<div class="cark-entry-actions">
					<button type="button" class="cark-btn cark-btn-sm" data-action="copy-history"><?php esc_html_e( 'Kopyala', 'carkcevirici-tools' ); ?></button>
					<button type="button" class="cark-btn cark-btn-sm" data-action="download-history"><?php esc_html_e( '.txt indir', 'carkcevirici-tools' ); ?></button>
				</div>
			</div>

			<?php if ( ! empty( $entries ) ) : ?>
			<details class="cark-ssr-list">
				<summary><?php esc_html_e( 'Çarktaki seçenekler', 'carkcevirici-tools' ); ?></summary>
				<ol>
					<?php foreach ( $entries as $entry ) : ?>
						<li><?php echo esc_html( $entry['text'] ); ?></li>
					<?php endforeach; ?>
				</ol>
			</details>
			<?php endif; ?>

			<noscript>
				<p class="cark-noscript-note">
					<?php esc_html_e( 'Çarkı çevirmek için tarayıcınızda JavaScript etkin olmalıdır. Yukarıdaki liste, çarktaki tüm seçenekleri gösterir.', 'carkcevirici-tools' ); ?>
				</p>
			</noscript>
		</div>
		<?php
		return ob_get_clean();
	}

	/* -------------------------------------------------------------- *
	 *  [takim_olusturucu]
	 * -------------------------------------------------------------- */

	public function render_takim_olusturucu( $atts ) {
		Cark_Assets::instance()->enqueue_tools();
		$id = 'takim-' . wp_rand( 1000, 9999 );
		ob_start();
		?>
		<div class="cark-tool cark-takim" id="<?php echo esc_attr( $id ); ?>">
			<div class="cark-layout">
				<div class="cark-panel">
					<label class="cark-label" for="<?php echo esc_attr( $id ); ?>-names">
						<?php esc_html_e( 'Her satıra bir isim yaz', 'carkcevirici-tools' ); ?>
					</label>
					<textarea class="cark-entries-input cark-takim-names" id="<?php echo esc_attr( $id ); ?>-names" rows="10"></textarea>
					<div class="cark-settings-grid">
						<label>
							<?php esc_html_e( 'Bölme yöntemi', 'carkcevirici-tools' ); ?>
							<select class="cark-takim-mode">
								<option value="team-count"><?php esc_html_e( 'Takım sayısına göre', 'carkcevirici-tools' ); ?></option>
								<option value="per-team"><?php esc_html_e( 'Takımdaki kişi sayısına göre', 'carkcevirici-tools' ); ?></option>
							</select>
						</label>
						<label>
							<?php esc_html_e( 'Sayı', 'carkcevirici-tools' ); ?>
							<input type="number" class="cark-takim-count" min="2" value="2">
						</label>
						<label>
							<input type="checkbox" class="cark-takim-leader"> <?php esc_html_e( 'Her takıma lider seç', 'carkcevirici-tools' ); ?>
						</label>
					</div>
					<button type="button" class="cark-btn cark-takim-create"><?php esc_html_e( 'Takımları Oluştur', 'carkcevirici-tools' ); ?></button>
				</div>
				<div class="cark-stage">
					<div class="cark-takim-results" aria-live="polite"></div>
					<div class="cark-entry-actions">
						<button type="button" class="cark-btn cark-btn-sm cark-takim-print"><?php esc_html_e( 'Yazdır', 'carkcevirici-tools' ); ?></button>
						<button type="button" class="cark-btn cark-btn-sm cark-takim-copy"><?php esc_html_e( 'Kopyala', 'carkcevirici-tools' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* -------------------------------------------------------------- *
	 *  [kura_cekme]
	 * -------------------------------------------------------------- */

	public function render_kura_cekme( $atts ) {
		Cark_Assets::instance()->enqueue_tools();
		$id = 'kura-' . wp_rand( 1000, 9999 );
		ob_start();
		?>
		<div class="cark-tool cark-kura" id="<?php echo esc_attr( $id ); ?>">
			<div class="cark-layout">
				<div class="cark-panel">
					<label class="cark-label" for="<?php echo esc_attr( $id ); ?>-names">
						<?php esc_html_e( 'Her satıra bir isim yaz', 'carkcevirici-tools' ); ?>
					</label>
					<textarea class="cark-entries-input cark-kura-names" id="<?php echo esc_attr( $id ); ?>-names" rows="10"></textarea>
					<div class="cark-settings-grid">
						<label>
							<?php esc_html_e( 'Kazanan sayısı', 'carkcevirici-tools' ); ?>
							<input type="number" class="cark-kura-winners" min="1" value="1">
						</label>
						<label>
							<?php esc_html_e( 'Yedek sayısı', 'carkcevirici-tools' ); ?>
							<input type="number" class="cark-kura-subs" min="0" value="0">
						</label>
					</div>
					<button type="button" class="cark-btn cark-kura-draw"><?php esc_html_e( 'Kura Çek', 'carkcevirici-tools' ); ?></button>
				</div>
				<div class="cark-stage">
					<div class="cark-kura-results" aria-live="polite"></div>
					<div class="cark-entry-actions">
						<button type="button" class="cark-btn cark-btn-sm cark-kura-print"><?php esc_html_e( 'Sonucu Yazdır', 'carkcevirici-tools' ); ?></button>
						<button type="button" class="cark-btn cark-btn-sm cark-kura-copy"><?php esc_html_e( 'Kopyala', 'carkcevirici-tools' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* -------------------------------------------------------------- *
	 *  [sayi_secici]
	 * -------------------------------------------------------------- */

	public function render_sayi_secici( $atts ) {
		$atts = shortcode_atts( array(
			'min' => 1,
			'max' => 100,
		), $atts, 'sayi_secici' );

		Cark_Assets::instance()->enqueue_tools();
		$id = 'sayi-' . wp_rand( 1000, 9999 );
		ob_start();
		?>
		<div class="cark-tool cark-sayi" id="<?php echo esc_attr( $id ); ?>">
			<div class="cark-settings-grid">
				<label><?php esc_html_e( 'Min', 'carkcevirici-tools' ); ?> <input type="number" class="cark-sayi-min" value="<?php echo esc_attr( (int) $atts['min'] ); ?>"></label>
				<label><?php esc_html_e( 'Maks', 'carkcevirici-tools' ); ?> <input type="number" class="cark-sayi-max" value="<?php echo esc_attr( (int) $atts['max'] ); ?>"></label>
				<label><?php esc_html_e( 'Kaç sayı', 'carkcevirici-tools' ); ?> <input type="number" class="cark-sayi-count" min="1" value="1"></label>
				<label><input type="checkbox" class="cark-sayi-unique" checked> <?php esc_html_e( 'Tekrar yok', 'carkcevirici-tools' ); ?></label>
				<label><input type="checkbox" class="cark-sayi-sort"> <?php esc_html_e( 'Sırala', 'carkcevirici-tools' ); ?></label>
			</div>
			<button type="button" class="cark-btn cark-sayi-pick"><?php esc_html_e( 'Sayı Seç', 'carkcevirici-tools' ); ?></button>
			<div class="cark-sayi-result" aria-live="polite"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* -------------------------------------------------------------- *
	 *  [yazi_tura]
	 * -------------------------------------------------------------- */

	public function render_yazi_tura( $atts ) {
		Cark_Assets::instance()->enqueue_tools();
		$id = 'tura-' . wp_rand( 1000, 9999 );
		ob_start();
		?>
		<div class="cark-tool cark-tura" id="<?php echo esc_attr( $id ); ?>">
			<div class="cark-coin-wrap">
				<div class="cark-coin" aria-hidden="true">
					<div class="cark-coin-face cark-coin-yazi"><?php esc_html_e( 'YAZI', 'carkcevirici-tools' ); ?></div>
					<div class="cark-coin-face cark-coin-tura"><?php esc_html_e( 'TURA', 'carkcevirici-tools' ); ?></div>
				</div>
			</div>
			<button type="button" class="cark-btn cark-tura-flip"><?php esc_html_e( 'Yazı Tura At', 'carkcevirici-tools' ); ?></button>
			<p class="cark-live" aria-live="polite" role="status"></p>
			<p class="cark-tura-stats"></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/* -------------------------------------------------------------- *
	 *  [zar_at]
	 * -------------------------------------------------------------- */

	public function render_zar_at( $atts ) {
		$atts = shortcode_atts( array( 'zar' => 1 ), $atts, 'zar_at' );
		Cark_Assets::instance()->enqueue_tools();
		$id = 'zar-' . wp_rand( 1000, 9999 );
		ob_start();
		?>
		<div class="cark-tool cark-zar" id="<?php echo esc_attr( $id ); ?>" data-zar-count="<?php echo esc_attr( max( 1, (int) $atts['zar'] ) ); ?>">
			<div class="cark-settings-grid">
				<label><?php esc_html_e( 'Zar sayısı', 'carkcevirici-tools' ); ?>
					<input type="number" class="cark-zar-count" min="1" max="6" value="<?php echo esc_attr( max( 1, (int) $atts['zar'] ) ); ?>">
				</label>
			</div>
			<div class="cark-zar-dice" aria-hidden="true"></div>
			<button type="button" class="cark-btn cark-zar-roll"><?php esc_html_e( 'Zar At', 'carkcevirici-tools' ); ?></button>
			<p class="cark-live" aria-live="polite" role="status"></p>
		</div>
		<?php
		return ob_get_clean();
	}
}
