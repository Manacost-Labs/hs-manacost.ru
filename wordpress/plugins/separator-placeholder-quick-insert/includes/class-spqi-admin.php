<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPQI_Admin {

	const PAGE_SLUG = 'spqi';

	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_spqi_save', array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function add_menu() {
		add_management_page(
			__( 'Разделители и заглушки', 'spqi' ),
			__( 'Разделители и заглушки', 'spqi' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function enqueue( $hook ) {
		if ( 'tools_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script(
			'spqi-admin',
			SPQI_URL . 'assets/admin.js',
			array( 'jquery' ),
			SPQI_VERSION,
			true
		);
		wp_enqueue_style(
			'spqi-admin',
			SPQI_URL . 'assets/admin.css',
			array(),
			SPQI_VERSION
		);
		wp_localize_script(
			'spqi-admin',
			'SPQI_ADMIN',
			array(
				'pickTitle'  => __( 'Выберите изображения', 'spqi' ),
				'pickButton' => __( 'Добавить выбранные', 'spqi' ),
				'removeAria' => __( 'Убрать из набора', 'spqi' ),
				'closeAria'  => __( 'Закрыть', 'spqi' ),
				'prevAria'   => __( 'Предыдущее', 'spqi' ),
				'nextAria'   => __( 'Следующее', 'spqi' ),
			)
		);
	}

	private function current_tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'separators';
		return in_array( $tab, SPQI_Store::types(), true ) ? $tab : 'separators';
	}

	private function tab_label( $tab ) {
		switch ( $tab ) {
			case 'placeholders': return __( 'Заглушки', 'spqi' );
			case 'misc':         return __( 'Разное', 'spqi' );
			case 'preview':      return __( 'Превью', 'spqi' );
			default:             return __( 'Разделители', 'spqi' );
		}
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$tab   = $this->current_tab();
		$items = SPQI_Store::get_items( $tab );
		$base  = admin_url( 'tools.php?page=' . self::PAGE_SLUG );
		?>
		<div class="wrap spqi-wrap">
			<h1><?php esc_html_e( 'Разделители и заглушки', 'spqi' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Сохранено.', 'spqi' ); ?></p></div>
			<?php endif; ?>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( SPQI_Store::types() as $t ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $t, $base ) ); ?>"
						class="nav-tab <?php echo $t === $tab ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $this->tab_label( $t ) ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'spqi_save_' . $tab ); ?>
				<input type="hidden" name="action" value="spqi_save">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="spqi-description"><?php esc_html_e( 'Описание вкладки', 'spqi' ); ?></label>
						</th>
						<td>
							<textarea id="spqi-description" name="description" rows="2" class="large-text"
								placeholder="<?php esc_attr_e( 'Короткое описание, которое увидит редактор статьи.', 'spqi' ); ?>"><?php
								echo esc_textarea( SPQI_Store::get_description( $tab ) );
							?></textarea>
							<p class="description">
								<?php esc_html_e( 'Показывается в верхней части соответствующей вкладки в редакторе.', 'spqi' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<p class="spqi-toolbar">
					<button type="button" class="button button-primary" id="spqi-add">
						<?php esc_html_e( 'Загрузить / выбрать из медиатеки', 'spqi' ); ?>
					</button>
					<span class="description">
						<?php esc_html_e( 'Изображения можно перетаскивать для изменения порядка.', 'spqi' ); ?>
					</span>
				</p>

				<ul id="spqi-list" class="spqi-grid">
					<?php if ( empty( $items ) ) : ?>
						<li class="spqi-empty-state">
							<?php esc_html_e( 'Пока ничего не добавлено.', 'spqi' ); ?>
						</li>
					<?php else : ?>
						<?php foreach ( $items as $item ) : ?>
							<li data-id="<?php echo esc_attr( $item['id'] ); ?>"
								data-full="<?php echo esc_url( $item['url'] ); ?>"
								data-title="<?php echo esc_attr( $item['title'] ); ?>">
								<img src="<?php echo esc_url( $item['thumb'] ); ?>" alt="<?php echo esc_attr( $item['alt'] ); ?>">
								<button type="button" class="spqi-remove" aria-label="<?php esc_attr_e( 'Убрать из набора', 'spqi' ); ?>">&times;</button>
								<input type="hidden" name="ids[]" value="<?php echo esc_attr( $item['id'] ); ?>">
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>

				<?php submit_button( __( 'Сохранить', 'spqi' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'spqi' ), '', array( 'response' => 403 ) );
		}
		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'separators';
		if ( ! in_array( $tab, SPQI_Store::types(), true ) ) {
			$tab = 'separators';
		}
		check_admin_referer( 'spqi_save_' . $tab );

		$ids = array();
		if ( isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) ) {
			$ids = array_map( 'absint', wp_unslash( $_POST['ids'] ) );
		}
		SPQI_Store::set_ids( $tab, $ids );

		$desc = isset( $_POST['description'] ) ? wp_unslash( $_POST['description'] ) : '';
		SPQI_Store::set_description( $tab, $desc );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'tab'     => $tab,
					'updated' => 1,
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}
}
