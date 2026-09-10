<?php
/**
 * Plugin Name: HS Article Versions
 * Description: Keeps editor-selected article snapshots available on the original article URL.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;
/**
 * Adds explicit editorial versions without changing an article's permalink.
 *
 * WordPress revisions may be pruned by the site's revision limit, so this
 * feature stores only the snapshots explicitly selected by an editor as
 * post meta. The live article remains the original WordPress post.
 *
 * @phpstan-type ArticleVersion array{title:string,content:string,excerpt:string,created_gmt:string}
 * @phpstan-type ArticleVersions array<int, ArticleVersion>
 * @phpstan-type ArticleVersionState array{versions:ArticleVersions,active:int}
 */
final class HS_Article_Versions {
	private const ACTION            = 'hs_create_article_version';
	private const UPDATE_ACTION     = 'hs_update_article_version';
	private const DELETE_ACTION     = 'hs_delete_article_version';
	private const SET_ACTIVE_ACTION = 'hs_set_article_version_active';
	private const META_KEY          = '_hs_article_versions';
	private const ACTIVE_META_KEY   = '_hs_article_versions_active';
	private const STATE_META_KEY    = '_hs_article_versions_state';
	private const REST_NAMESPACE    = 'manacost/v1';
	private const REST_ROUTE        = '/article-versions/(?P<post_id>\d+)/(?P<version>\d+)';
	/** Prevents nested switchers while normal content filters render a snapshot.
	 *
	 * @var bool
	 */
	private static bool $rendering_snapshot = false;

	/** Registers the scoped editor, frontend, and read-only REST entrypoints. */
	public static function boot(): void {
		add_action( 'media_buttons', array( __CLASS__, 'render_media_button' ), 20, 1 );
		add_action( 'add_meta_boxes_post', array( __CLASS__, 'register_versions_metabox' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_create_version' ) );
		add_action( 'admin_post_' . self::UPDATE_ACTION, array( __CLASS__, 'handle_update_version' ) );
		add_action( 'admin_post_' . self::DELETE_ACTION, array( __CLASS__, 'handle_delete_version' ) );
		add_action( 'admin_post_' . self::SET_ACTIVE_ACTION, array( __CLASS__, 'handle_set_active_version' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notice' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_route' ) );
		add_filter( 'the_title', array( __CLASS__, 'replace_active_title' ), 1, 2 );
		add_filter( 'the_content', array( __CLASS__, 'prepend_switcher' ), 99 );
		add_action( 'wp_head', array( __CLASS__, 'render_styles' ), 99 );
		add_action( 'wp_footer', array( __CLASS__, 'render_script' ), 99 );
	}

	/**
	 * Renders an intentional snapshot action beside Classic Editor's Add Media button.
	 *
	 * @param string $editor_id Current editor identifier.
	 */
	public static function render_media_button( string $editor_id = 'content' ): void {
		global $post;

		if (
			'content' !== $editor_id ||
			! $post instanceof WP_Post ||
			'post' !== $post->post_type ||
			'publish' !== $post->post_status ||
			'' !== $post->post_password ||
			! current_user_can( 'edit_post', $post->ID )
		) {
			return;
		}

		$url = admin_url( 'admin-post.php?action=' . self::ACTION . '&post_id=' . absint( $post->ID ) );
		$url = wp_nonce_url( $url, self::nonce_action( $post->ID ) );

		echo '<a class="button hs-article-versions__create" href="' . esc_url( $url ) . '" ';
		echo 'onclick="return window.confirm(\'Сначала сохраните черновик, если есть несохранённые изменения. Создать снимок текущей версии статьи?\');">';
		echo esc_html__( 'Создать новую версию', 'manacost' );
		echo '</a>';
	}

	/** Registers the version-management panel on the native post editor. */
	public static function register_versions_metabox(): void {
		add_meta_box(
			'hs-article-versions',
			esc_html__( 'Версии статьи', 'manacost' ),
			array( __CLASS__, 'render_versions_metabox' ),
			'post',
			'normal',
			'high'
		);
	}

	/**
	 * Renders the concise snapshot list or its selected edit form.
	 *
	 * @param WP_Post $post Article loaded by the native post editor.
	 */
	public static function render_versions_metabox( WP_Post $post ): void {
		if ( ! self::can_manage_versions( $post ) ) {
			echo '<p>' . esc_html__( 'Версии доступны для опубликованных статей без пароля.', 'manacost' ) . '</p>';
			return;
		}

		$versions = self::get_versions( $post->ID );
		if ( array() === $versions ) {
			echo '<p>' . esc_html__( 'Снимков пока нет. Сначала сохраните статью, затем нажмите «Создать новую версию» рядом с «Добавить медиафайл».', 'manacost' ) . '</p>';
			return;
		}

		$editing = self::request_positive_integer( 'hs_article_version_edit' );
		if ( $editing > 0 && isset( $versions[ $editing - 1 ] ) ) {
			self::render_version_edit_form( $post, $editing, $versions[ $editing - 1 ] );
			return;
		}

		$active_number = self::get_active_version_number( $post->ID, $versions );

		echo '<div class="hs-article-versions-admin" data-hs-article-version-endpoint="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<p class="description">' . esc_html__( 'Оригинальная запись WordPress никогда не изменяется и не удаляется этим плагином. Здесь можно редактировать, сделать актуальным или удалить только сохранённый снимок.', 'manacost' ) . '</p>';
		echo '<ol class="hs-article-versions-admin__list">';
		foreach ( $versions as $index => $snapshot ) {
			$number    = $index + 1;
			$is_active = $number === $active_number;
			echo '<li class="hs-article-versions-admin__item' . ( $is_active ? ' is-active' : '' ) . '">';
			echo '<div><strong>' . esc_html( self::version_name( $number ) ) . '</strong>';
			if ( $is_active ) {
				echo '<span class="hs-article-versions-admin__active">' . esc_html__( 'Актуальная', 'manacost' ) . '</span>';
			}
			echo '<span>' . esc_html( self::snapshot_date_label( $snapshot['created_gmt'] ) ) . '</span></div>';
			echo '<div class="hs-article-versions-admin__actions">';
			echo '<a class="button button-secondary" href="' . esc_url( self::edit_version_url( $post->ID, $number ) ) . '">';
			echo esc_html__( 'Редактировать', 'manacost' );
			echo '</a>';
			if ( ! $is_active ) {
				self::render_set_active_form( $post->ID, $number, __( 'Сделать актуальной', 'manacost' ) );
			}
			self::render_delete_form( $post->ID, $number );
			echo '</div></li>';
		}
		echo '</ol>';
		if ( 0 === $active_number ) {
			echo '<p class="description hs-article-versions-admin__original">' . esc_html__( 'Сейчас на исходном URL показана оригинальная статья.', 'manacost' ) . '</p>';
		} else {
			echo '<div class="hs-article-versions-admin__restore">';
			self::render_set_active_form( $post->ID, 0, __( 'Вернуть оригинальную статью', 'manacost' ) );
			echo '</div>';
		}
		echo '</div>';
		self::render_metabox_styles();
		self::render_metabox_script();
	}

	/**
	 * Displays a snapshot edit form without changing the current article fields.
	 *
	 * @param WP_Post $post Current article.
	 * @param int     $number One-based snapshot number.
	 * @param array   $snapshot Saved snapshot.
	 * @phpstan-param ArticleVersion $snapshot
	 */
	private static function render_version_edit_form( WP_Post $post, int $number, array $snapshot ): void {
		echo '<div class="hs-article-versions-admin hs-article-versions-admin--editing" data-hs-article-version-endpoint="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<h3>' . esc_html(
			sprintf(
				/* translators: %s: saved article version label. */
				__( 'Редактирование: %s', 'manacost' ),
				self::version_name( $number )
			)
		) . '</h3>';
		echo '<p class="description">' . esc_html( self::snapshot_date_label( $snapshot['created_gmt'] ) ) . '</p>';
		echo '<p><label for="hs-article-version-title"><strong>' . esc_html__( 'Заголовок версии', 'manacost' ) . '</strong></label><br>';
		echo '<input class="widefat" id="hs-article-version-title" type="text" value="' . esc_attr( $snapshot['title'] ) . '"></p>';
		echo '<p><label for="hs-article-version-content"><strong>' . esc_html__( 'Текст версии', 'manacost' ) . '</strong></label></p>';
		wp_editor(
			$snapshot['content'],
			'hs_article_version_content',
			array(
				'textarea_name' => 'hs_article_version_content',
				'textarea_rows' => 14,
				'media_buttons' => false,
			)
		);
		echo '<p><label for="hs-article-version-excerpt"><strong>' . esc_html__( 'Краткое описание', 'manacost' ) . '</strong></label><br>';
		echo '<textarea class="widefat" id="hs-article-version-excerpt" rows="3">' . esc_textarea( $snapshot['excerpt'] ) . '</textarea></p>';
		echo '<p>';
		self::render_action_button( $post->ID, $number, self::UPDATE_ACTION, __( 'Сохранить версию', 'manacost' ), 'button button-primary' );
		echo ' ';
		echo '<a class="button button-secondary" href="' . esc_url( self::editor_url( $post->ID ) ) . '">' . esc_html__( 'Отмена', 'manacost' ) . '</a></p>';
		echo '</div>';
		self::render_metabox_styles();
		self::render_metabox_script();
	}

	/**
	 * Prints a dedicated destructive-action button for one snapshot.
	 *
	 * @param int $post_id Article ID.
	 * @param int $number One-based snapshot number.
	 */
	private static function render_delete_form( int $post_id, int $number ): void {
		self::render_action_button(
			$post_id,
			$number,
			self::DELETE_ACTION,
			__( 'Удалить', 'manacost' ),
			'button-link-delete',
			__( 'Удалить только сохранённую версию? Оригинальная статья останется без изменений.', 'manacost' )
		);
	}
	/** Renders the protected button that selects a current snapshot.
	 *
	 * @param int    $post_id Article ID.
	 * @param int    $number Snapshot number, or zero for the original article.
	 * @param string $label Visible action label.
	 */
	private static function render_set_active_form( int $post_id, int $number, string $label ): void {
		self::render_action_button( $post_id, $number, self::SET_ACTIVE_ACTION, $label, 'button button-secondary' );
	}
	/**
	 * Renders a protected version-action button.
	 *
	 * @param int    $post_id Article ID.
	 * @param int    $number Snapshot number, or zero for the original article.
	 * @param string $action Protected admin-post action.
	 * @param string $label Visible button label.
	 * @param string $button_class Button classes.
	 * @param string $confirmation Optional confirmation copy.
	 */
	private static function render_action_button( int $post_id, int $number, string $action, string $label, string $button_class, string $confirmation = '' ): void {
		echo '<button class="' . esc_attr( $button_class ) . ' hs-article-versions-admin__action" type="button" data-hs-article-version-action="' . esc_attr( $action ) . '" data-hs-article-version-post-id="' . esc_attr( (string) $post_id ) . '" data-hs-article-version-number="' . esc_attr( (string) $number ) . '" data-hs-article-version-nonce="' . esc_attr( wp_create_nonce( self::version_nonce_action( $action, $post_id, $number ) ) ) . '"';
		if ( '' !== $confirmation ) {
			echo ' data-hs-article-version-confirm="' . esc_attr( $confirmation ) . '"';
		}
		echo '>' . esc_html( $label ) . '</button>';
	}
	/** Keeps the native metabox compact without leaking styles to other admin screens. */
	private static function render_metabox_styles(): void {
		echo '<style>.hs-article-versions-admin__list{margin:12px 0 0}.hs-article-versions-admin__item{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0;padding:12px 0;border-top:1px solid #dcdcde}.hs-article-versions-admin__item:first-child{border-top:0}.hs-article-versions-admin__item.is-active{border-inline-start:3px solid #2271b1;padding-inline-start:10px;background:#f0f6fc}.hs-article-versions-admin__item span{display:block;margin-top:3px;color:#646970;font-size:12px}.hs-article-versions-admin__item .hs-article-versions-admin__active{display:inline-block;margin:0 0 0 7px;padding:2px 6px;border-radius:999px;background:#e7f3ff;color:#135e96;font-weight:600}.hs-article-versions-admin__actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.hs-article-versions-admin__action{margin:0}.hs-article-versions-admin__restore,.hs-article-versions-admin__original{margin-top:14px}.hs-article-versions-admin--editing{max-width:900px}@media(max-width:600px){.hs-article-versions-admin__item{align-items:flex-start;flex-direction:column}.hs-article-versions-admin__actions{width:100%}.hs-article-versions-admin__actions .button{min-height:36px}}</style>';
	}
	/** Submits version actions from outside WordPress's native #post form. */
	private static function render_metabox_script(): void {
		echo '<script>(function(){document.addEventListener("click",function(event){var button=event.target.closest(".hs-article-versions-admin__action");if(!button){return;}var confirmation=button.getAttribute("data-hs-article-version-confirm");if(confirmation&&!window.confirm(confirmation)){return;}var panel=button.closest(".hs-article-versions-admin"),endpoint=panel&&panel.getAttribute("data-hs-article-version-endpoint"),action=button.getAttribute("data-hs-article-version-action");if(!endpoint||!action){return;}var fields={action:action,post_id:button.getAttribute("data-hs-article-version-post-id"),version:button.getAttribute("data-hs-article-version-number"),_wpnonce:button.getAttribute("data-hs-article-version-nonce")};if(action==="' . esc_js( self::UPDATE_ACTION ) . '"){var title=document.getElementById("hs-article-version-title"),content=document.getElementById("hs_article_version_content"),excerpt=document.getElementById("hs-article-version-excerpt"),editor=window.tinymce&&window.tinymce.get("hs_article_version_content");fields.title=title?title.value:"";fields.content=editor?editor.getContent():(content?content.value:"");fields.excerpt=excerpt?excerpt.value:"";}var form=document.createElement("form");form.method="post";form.action=endpoint;form.style.display="none";Object.keys(fields).forEach(function(key){var input=document.createElement("input");input.type="hidden";input.name=key;input.value=fields[key]||"";form.appendChild(input);});document.body.appendChild(form);form.submit();});}());</script>';
	}
	/** Creates a durable explicit snapshot, then returns the editor to the same post. */
	public static function handle_create_version(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The object-specific nonce is verified and the scalar is validated below.
		$raw_post_id = isset( $_GET['post_id'] ) ? wp_unslash( $_GET['post_id'] ) : 0;
		if ( ! is_scalar( $raw_post_id ) ) {
			wp_die( esc_html__( 'Некорректная статья.', 'manacost' ), '', array( 'response' => 400 ) );
		}
		$post_id = absint( $raw_post_id );
		$post    = get_post( $post_id );
		if (
			! $post instanceof WP_Post ||
			'post' !== $post->post_type ||
			'publish' !== $post->post_status ||
			'' !== $post->post_password ||
			! current_user_can( 'edit_post', $post_id )
		) {
			wp_die( esc_html__( 'Недостаточно прав для создания версии статьи.', 'manacost' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::nonce_action( $post_id ) );
		$versions      = self::get_versions( $post_id );
		$active_number = self::get_active_version_number( $post_id, $versions );
		if ( self::matches_latest_snapshot( $versions, $post ) ) {
			self::redirect_to_editor( $post_id, 'hs_article_version_exists', '1' );
		}
		$versions[] = self::snapshot_post( $post );
		if ( ! self::store_version_state( $post_id, $versions, $active_number ) ) {
			wp_die( esc_html__( 'Не удалось сохранить версию статьи. Повторите попытку.', 'manacost' ), '', array( 'response' => 500 ) );
		}
		clean_post_cache( $post_id );
		self::redirect_to_editor( $post_id, 'hs_article_version_created', (string) count( $versions ) );
	}

	/** Updates one saved snapshot without modifying the live article. */
	public static function handle_update_version(): void {
		$post_id = self::request_positive_integer_from_post( 'post_id' );
		$number  = self::request_positive_integer_from_post( 'version' );
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post || ! self::can_manage_versions( $post ) ) {
			wp_die( esc_html__( 'Недостаточно прав для изменения версии статьи.', 'manacost' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::version_nonce_action( self::UPDATE_ACTION, $post_id, $number ) );
		$versions      = self::get_versions( $post_id );
		$active_number = self::get_active_version_number( $post_id, $versions );
		$index         = $number - 1;
		if ( $index < 0 || ! isset( $versions[ $index ] ) ) {
			wp_die( esc_html__( 'Версия статьи не найдена.', 'manacost' ), '', array( 'response' => 404 ) );
		}

		$title   = self::request_post_text( 'title' );
		$content = self::request_post_text( 'content' );
		$excerpt = self::request_post_text( 'excerpt' );
		if ( null === $title || null === $content || null === $excerpt ) {
			wp_die( esc_html__( 'Некорректные данные версии статьи.', 'manacost' ), '', array( 'response' => 400 ) );
		}

		$updated_snapshot = array(
			'title'       => sanitize_text_field( $title ),
			'content'     => current_user_can( 'unfiltered_html' ) ? $content : wp_kses_post( $content ),
			'excerpt'     => sanitize_textarea_field( $excerpt ),
			'created_gmt' => $versions[ $index ]['created_gmt'],
		);
		if ( $updated_snapshot === $versions[ $index ] ) {
			self::redirect_to_editor( $post_id, 'hs_article_version_unchanged', (string) $number );
		}

		$versions[ $index ] = $updated_snapshot;
		if ( ! self::store_version_state( $post_id, $versions, $active_number ) ) {
			wp_die( esc_html__( 'Не удалось сохранить версию статьи. Повторите попытку.', 'manacost' ), '', array( 'response' => 500 ) );
		}

		clean_post_cache( $post_id );
		self::redirect_to_editor( $post_id, 'hs_article_version_updated', (string) $number );
	}
	/** Selects a safe snapshot or the original post without mutating wp_posts. */
	public static function handle_set_active_version(): void {
		$post_id = self::request_positive_integer_from_post( 'post_id' );
		$number  = self::request_nonnegative_integer_from_post( 'version' );
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post || ! self::can_manage_versions( $post ) ) {
			wp_die( esc_html__( 'Недостаточно прав для выбора актуальной версии статьи.', 'manacost' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::version_nonce_action( self::SET_ACTIVE_ACTION, $post_id, $number ) );
		$versions = self::get_versions( $post_id );
		if ( $number > 0 && ! isset( $versions[ $number - 1 ] ) ) {
			wp_die( esc_html__( 'Версия статьи не найдена.', 'manacost' ), '', array( 'response' => 404 ) );
		}

		if ( self::get_active_version_number( $post_id, $versions ) === $number ) {
			self::redirect_after_active_version_change( $post_id, $number );
		}

		if ( ! self::store_version_state( $post_id, $versions, $number ) ) {
			wp_die( esc_html__( 'Не удалось выбрать актуальную версию статьи. Повторите попытку.', 'manacost' ), '', array( 'response' => 500 ) );
		}

		clean_post_cache( $post_id );
		self::redirect_after_active_version_change( $post_id, $number );
	}
	/** Removes exactly one explicitly saved snapshot after an explicit confirmation. */
	public static function handle_delete_version(): void {
		$post_id = self::request_positive_integer_from_post( 'post_id' );
		$number  = self::request_positive_integer_from_post( 'version' );
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post || ! self::can_manage_versions( $post ) ) {
			wp_die( esc_html__( 'Недостаточно прав для удаления версии статьи.', 'manacost' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::version_nonce_action( self::DELETE_ACTION, $post_id, $number ) );
		$versions      = self::get_versions( $post_id );
		$active_number = self::get_active_version_number( $post_id, $versions );
		$next_active   = $active_number;
		$index         = $number - 1;
		if ( $index < 0 || ! isset( $versions[ $index ] ) ) {
			wp_die( esc_html__( 'Версия статьи не найдена.', 'manacost' ), '', array( 'response' => 404 ) );
		}

		array_splice( $versions, $index, 1 );
		if ( $number === $active_number ) {
			$next_active = 0;
		} elseif ( $number < $active_number ) {
			$next_active = $active_number - 1;
		}

		if ( ! self::store_version_state( $post_id, $versions, $next_active ) ) {
			wp_die( esc_html__( 'Не удалось удалить версию статьи. Повторите попытку.', 'manacost' ), '', array( 'response' => 500 ) );
		}

		clean_post_cache( $post_id );
		self::redirect_to_editor( $post_id, 'hs_article_version_deleted', (string) $number );
	}
	/** Shows a concise result after the editor returns to the original article. */
	public static function render_admin_notice(): void {
		$created  = self::request_positive_integer( 'hs_article_version_created' );
		$exists   = self::request_positive_integer( 'hs_article_version_exists' );
		$updated  = self::request_positive_integer( 'hs_article_version_updated' );
		$deleted  = self::request_positive_integer( 'hs_article_version_deleted' );
		$same     = self::request_positive_integer( 'hs_article_version_unchanged' );
		$active   = self::request_positive_integer( 'hs_article_version_active' );
		$original = self::request_positive_integer( 'hs_article_version_original' );

		if ( $created > 0 ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html(
				sprintf(
					/* translators: %d: saved article version number. */
					__( 'Версия %d сохранена. Теперь редактируйте и обновляйте эту же статью как обычно.', 'manacost' ),
					$created
				)
			);
			echo '</p></div>';
		} elseif ( $exists > 0 ) {
			echo '<div class="notice notice-info is-dismissible"><p>';
			echo esc_html__( 'Текущая сохранённая статья уже является последней версией.', 'manacost' );
			echo '</p></div>';
		} elseif ( $updated > 0 ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html(
				sprintf(
					/* translators: %d: saved article version number. */
					__( 'Версия %d обновлена.', 'manacost' ),
					$updated
				)
			);
			echo '</p></div>';
		} elseif ( $deleted > 0 ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html(
				sprintf(
					/* translators: %d: deleted article version number. */
					__( 'Версия %d удалена.', 'manacost' ),
					$deleted
				)
			);
			echo '</p></div>';
		} elseif ( $active > 0 ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html(
				sprintf(
					/* translators: %d: active saved article version number. */
					__( 'Версия %d теперь отображается как актуальная по исходному адресу статьи.', 'manacost' ),
					$active
				)
			);
			echo '</p></div>';
		} elseif ( $original > 0 ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html__( 'Оригинальная статья снова отображается как актуальная по исходному адресу.', 'manacost' );
			echo '</p></div>';
		} elseif ( $same > 0 ) {
			echo '<div class="notice notice-info is-dismissible"><p>';
			echo esc_html__( 'В этой версии нет новых изменений для сохранения.', 'manacost' );
			echo '</p></div>';
		}
	}

	/** Registers a read-only public endpoint for explicitly saved, published snapshots. */
	public static function register_rest_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_public_version' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'post_id' => self::nonnegative_integer_argument(),
					'version' => self::nonnegative_integer_argument(),
				),
			)
		);
	}

	/**
	 * Returns one saved snapshot from a public article.
	 *
	 * @param WP_REST_Request $request Validated REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_public_version( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$version = absint( $request->get_param( 'version' ) );
		$post    = get_post( $post_id );

		if (
			! $post instanceof WP_Post ||
			'post' !== $post->post_type ||
			'publish' !== $post->post_status ||
			( '' !== $post->post_password && post_password_required( $post ) )
		) {
			return new WP_Error(
				'hs_article_version_not_found',
				__( 'Версия статьи не найдена.', 'manacost' ),
				array( 'status' => 404 )
			);
		}

		if ( 0 === $version ) {
			return rest_ensure_response(
				array(
					'version' => 0,
					'title'   => (string) $post->post_title,
					'content' => self::render_snapshot_content( $post, (string) $post->post_content ),
				)
			);
		}

		$versions = self::get_versions( $post_id );
		$index    = $version - 1;

		if ( $index < 0 || ! isset( $versions[ $index ] ) ) {
			return new WP_Error(
				'hs_article_version_not_found',
				__( 'Версия статьи не найдена.', 'manacost' ),
				array( 'status' => 404 )
			);
		}

		$snapshot = $versions[ $index ];

		return rest_ensure_response(
			array(
				'version' => $version,
				'title'   => $snapshot['title'],
				'content' => self::render_snapshot_content( $post, $snapshot['content'] ),
			)
		);
	}
	/** Returns the selected snapshot title only in the primary public article loop.
	 *
	 * @param string    $title Existing article title.
	 * @param int|false $post_id Post ID passed by the title filter.
	 */
	public static function replace_active_title( string $title, $post_id = false ): string {
		if ( self::$rendering_snapshot ) {
			return $title;
		}

		$context = self::frontend_context( true );
		if ( null === $context || absint( $post_id ) !== $context['post']->ID ) {
			return $title;
		}

		$active_number = $context['active_number'];
		if ( 0 === $active_number ) {
			return $title;
		}

		return $context['versions'][ $active_number - 1 ]['title'];
	}

	/**
	 * Adds the small selector immediately before the fully-rendered body content.
	 *
	 * A late priority keeps it before content ads while avoiding wpautop or
	 * shortcode filters transforming the selector markup.
	 *
	 * @param string $content Rendered article content.
	 * @return string
	 */
	public static function prepend_switcher( string $content ): string {
		if ( self::$rendering_snapshot ) {
			return $content;
		}

		$context = self::frontend_context( true );
		if ( null === $context ) {
			return $content;
		}

		$post          = $context['post'];
		$versions      = $context['versions'];
		$active_number = $context['active_number'];
		$options       = '<option value="0" data-hs-article-version-url="' . esc_url( self::version_url( $post->ID, 0 ) ) . '"' . ( 0 === $active_number ? ' selected' : '' ) . '>';
		$options      .= esc_html__( 'Оригинальная статья', 'manacost' ) . '</option>';

		foreach ( $versions as $index => $snapshot ) {
			$number   = $index + 1;
			$url      = self::version_url( $post->ID, $number );
			$options .= '<option value="' . esc_attr( (string) $number ) . '" data-hs-article-version-url="' . esc_url( $url ) . '"' . ( $number === $active_number ? ' selected' : '' ) . '>';
			$label    = self::version_name( $number ) . ' · ' . self::snapshot_date_label( $snapshot['created_gmt'] );
			if ( $number === $active_number ) {
				$label .= ' · ' . __( 'Актуальная', 'manacost' );
			}
			$options .= esc_html( $label );
			$options .= '</option>';
		}

		$selector  = '<nav class="hs-article-versions" aria-label="' . esc_attr__( 'Версии статьи', 'manacost' ) . '">';
		$selector .= '<span class="hs-article-versions__copy"><span class="hs-article-versions__eyebrow">' . esc_html__( 'Архив редакций', 'manacost' ) . '</span>';
		$selector .= '<strong class="hs-article-versions__label">' . esc_html__( 'Версия статьи', 'manacost' ) . '</strong></span>';
		$selector .= '<span class="hs-article-versions__control"><label class="screen-reader-text" for="hs-article-version-select">' . esc_html__( 'Выберите версию статьи', 'manacost' ) . '</label>';
		$selector .= '<select id="hs-article-version-select" class="hs-article-versions__select">' . $options . '</select></span>';
		$selector .= '<span id="hs-article-version-status" class="hs-article-versions__status" role="status" aria-live="polite"></span>';
		$selector .= '</nav>';

		$visible_content = $content;
		if ( $active_number > 0 ) {
			$visible_content = self::render_snapshot_content( $post, $versions[ $active_number - 1 ]['content'] );
		}

		return $selector . '<div id="hs-article-version-content" data-hs-article-version-content="' . esc_attr( (string) $active_number ) . '">' . $visible_content . '</div>';
	}

	/** Prints presentation scoped to articles with manually saved versions. */
	public static function render_styles(): void {
		if ( null === self::frontend_context() ) {
			return;
		}

		echo '<style id="hs-article-versions-styles">';
		echo '.hs-article-versions{display:grid;grid-template-columns:minmax(0,1fr) minmax(190px,auto);align-items:center;gap:14px;max-width:100%;margin:0 0 18px;padding:11px 13px 11px 15px;border:1px solid rgba(70,82,96,.2);border-inline-start:4px solid var(--theme-palette-color-1,#c98a27);border-radius:7px;background:linear-gradient(90deg,rgba(201,138,39,.11),rgba(245,247,250,.76) 42%,rgba(245,247,250,.76));color:var(--theme-text-color,#263241);font-size:13px;line-height:1.35}.hs-article-versions__copy{min-width:0}.hs-article-versions__eyebrow{display:block;margin-bottom:2px;color:var(--theme-palette-color-1,#a66b18);font-size:10px;font-weight:800;letter-spacing:.08em;line-height:1.2;text-transform:uppercase}.hs-article-versions__label{display:block;font-size:14px;font-weight:750;white-space:nowrap}.hs-article-versions__control{min-width:0}.hs-article-versions__select{width:100%;min-width:190px;min-height:38px;margin:0;padding:5px 34px 5px 10px;border:1px solid rgba(70,82,96,.3);border-radius:4px;background:#fff;color:inherit;font:inherit;font-weight:600;cursor:pointer}.hs-article-versions__select:focus{outline:2px solid var(--theme-palette-color-1,#c98a27);outline-offset:2px}.hs-article-versions__select:disabled{cursor:wait;opacity:.7}.hs-article-versions__status{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(1px,1px,1px,1px);white-space:nowrap}';
		echo '@media (max-width:560px){.hs-article-versions{grid-template-columns:1fr;gap:8px;width:100%;margin-bottom:15px;padding:12px}.hs-article-versions__select{min-width:0;min-height:42px}}';
		echo '</style>';
	}

	/** Prints a progressive enhancement that keeps the visible page URL unchanged. */
	public static function render_script(): void {
		if ( null === self::frontend_context() ) {
			return;
		}

		echo '<script id="hs-article-versions-script">';
		echo '(function(){var select=document.getElementById("hs-article-version-select"),content=document.getElementById("hs-article-version-content"),status=document.getElementById("hs-article-version-status"),switcher=document.querySelector(".hs-article-versions");if(!select||!content){return;}var title=document.querySelector("article .entry-title"),previous=select.value;select.addEventListener("change",function(){var option=select.options[select.selectedIndex],next=option.value,url=option.getAttribute("data-hs-article-version-url");if(!url){select.value=previous;return;}select.disabled=true;if(switcher){switcher.setAttribute("aria-busy","true");}if(status){status.textContent="Загружается выбранная версия статьи.";}fetch(url,{credentials:"same-origin",headers:{"Accept":"application/json"}}).then(function(response){if(!response.ok){throw new Error("version request failed");}return response.json();}).then(function(payload){if(!payload||"string"!==typeof payload.content){throw new Error("invalid version response");}content.innerHTML=payload.content;if(title&&"string"===typeof payload.title){title.textContent=payload.title;}previous=next;if(status){status.textContent="Показана "+option.text+".";}}).catch(function(){select.value=previous;if(status){status.textContent="Не удалось загрузить выбранную версию статьи.";}}).finally(function(){select.disabled=false;if(switcher){switcher.removeAttribute("aria-busy");}});});}());';
		echo '</script>';
	}

	/**
	 * Returns clean snapshots from atomic state or the legacy collection.
	 *
	 * @param int $post_id Article ID.
	 * @return array<int, array{title:string,content:string,excerpt:string,created_gmt:string}>
	 */
	private static function get_versions( int $post_id ): array {
		$state  = get_post_meta( $post_id, self::STATE_META_KEY, true );
		$stored = is_array( $state ) && isset( $state['versions'] ) && is_array( $state['versions'] ) ? $state['versions'] : get_post_meta( $post_id, self::META_KEY, true );

		return is_array( $stored ) ? self::normalize_versions( $stored ) : array();
	}
	/**
	 * Filters malformed snapshot metadata before presentation or mutation.
	 *
	 * @param array<int, mixed> $stored Candidate snapshot collection.
	 * @phpstan-return ArticleVersions
	 */
	private static function normalize_versions( array $stored ): array {
		$versions = array();
		foreach ( $stored as $snapshot ) {
			if (
				! is_array( $snapshot ) ||
				! isset( $snapshot['title'], $snapshot['content'], $snapshot['excerpt'], $snapshot['created_gmt'] ) ||
				! is_string( $snapshot['title'] ) ||
				! is_string( $snapshot['content'] ) ||
				! is_string( $snapshot['excerpt'] ) ||
				! is_string( $snapshot['created_gmt'] )
			) {
				continue;
			}

			$versions[] = array(
				'title'       => $snapshot['title'],
				'content'     => $snapshot['content'],
				'excerpt'     => $snapshot['excerpt'],
				'created_gmt' => $snapshot['created_gmt'],
			);
		}

		return $versions;
	}

	/** Atomically writes both snapshots and their active selection in one metadata row.
	 *
	 * @param int   $post_id Article ID.
	 * @param array $versions Clean snapshots.
	 * @param int   $active_number Snapshot number, or zero for the original article.
	 * @phpstan-param ArticleVersions $versions
	 */
	private static function store_version_state( int $post_id, array $versions, int $active_number ): bool {
		$state = array(
			'versions' => $versions,
			'active'   => $active_number > 0 && isset( $versions[ $active_number - 1 ] ) ? $active_number : 0,
		);

		return false !== update_post_meta( $post_id, self::STATE_META_KEY, wp_slash( $state ) );
	}

	/** Gets a valid snapshot number, or zero for the original post.
	 *
	 * @param int                                                                              $post_id Article ID.
	 * @param array<int, array{title:string,content:string,excerpt:string,created_gmt:string}> $versions Saved snapshots.
	 */
	private static function get_active_version_number( int $post_id, array $versions ): int {
		$state         = get_post_meta( $post_id, self::STATE_META_KEY, true );
		$active_number = is_array( $state ) && isset( $state['versions'], $state['active'] ) && is_array( $state['versions'] ) ? absint( $state['active'] ) : absint( get_post_meta( $post_id, self::ACTIVE_META_KEY, true ) );
		return $active_number > 0 && isset( $versions[ $active_number - 1 ] ) ? $active_number : 0;
	}

	/**
	 * Checks the single article state that can safely expose and manage snapshots.
	 *
	 * @param WP_Post $post Candidate article.
	 */
	private static function can_manage_versions( WP_Post $post ): bool {
		return 'post' === $post->post_type
			&& 'publish' === $post->post_status
			&& '' === $post->post_password
			&& current_user_can( 'edit_post', $post->ID );
	}

	/**
	 * Builds the concise human label used in the editor and public selector.
	 *
	 * @param int $number One-based snapshot number.
	 */
	private static function version_name( int $number ): string {
		return sprintf(
			/* translators: %d: saved article version number. */
			__( 'Версия %d', 'manacost' ),
			$number
		);
	}

	/**
	 * Formats the durable snapshot timestamp in the site timezone.
	 *
	 * @param string $created_gmt Snapshot timestamp in UTC.
	 */
	private static function snapshot_date_label( string $created_gmt ): string {
		$timestamp = strtotime( $created_gmt . ' UTC' );
		if ( false === $timestamp ) {
			return __( 'Дата сохранения неизвестна', 'manacost' );
		}

		return sprintf(
			/* translators: %s: local snapshot timestamp. */
			__( 'Сохранено %s', 'manacost' ),
			wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp )
		);
	}

	/**
	 * Copies the revisioned article fields into one explicit snapshot.
	 *
	 * @param WP_Post $post Current article.
	 * @return array{title:string,content:string,excerpt:string,created_gmt:string}
	 */
	private static function snapshot_post( WP_Post $post ): array {
		return array(
			'title'       => (string) $post->post_title,
			'content'     => (string) $post->post_content,
			'excerpt'     => (string) $post->post_excerpt,
			'created_gmt' => (string) current_time( 'mysql', true ),
		);
	}

	/**
	 * Avoids storing an identical snapshot twice when the editor retries the action.
	 *
	 * @param array<int, array{title:string,content:string,excerpt:string,created_gmt:string}> $versions Existing snapshots.
	 * @param WP_Post                                                                          $post Current article.
	 * @return bool Whether the latest snapshot matches the article.
	 */
	private static function matches_latest_snapshot( array $versions, WP_Post $post ): bool {
		if ( array() === $versions ) {
			return false;
		}

		$latest = $versions[ count( $versions ) - 1 ];

		return $latest['title'] === (string) $post->post_title
			&& $latest['content'] === (string) $post->post_content
			&& $latest['excerpt'] === (string) $post->post_excerpt;
	}

	/**
	 * Redirects to the same canonical article's existing edit screen.
	 *
	 * @param int    $post_id Article ID.
	 * @param string $key Notice query key.
	 * @param string $value Notice query value.
	 */
	private static function redirect_to_editor( int $post_id, string $key, string $value ): void {
		wp_safe_redirect( add_query_arg( $key, $value, self::editor_url( $post_id ) ) );
		exit;
	}

	/** Returns to the original editor after selecting a current version.
	 *
	 * @param int $post_id Article ID.
	 * @param int $number Snapshot number, or zero for the original article.
	 */
	private static function redirect_after_active_version_change( int $post_id, int $number ): void {
		if ( 0 === $number ) {
			self::redirect_to_editor( $post_id, 'hs_article_version_original', '1' );
		}

		self::redirect_to_editor( $post_id, 'hs_article_version_active', (string) $number );
	}

	/**
	 * Returns the canonical native editor URL for a post.
	 *
	 * @param int $post_id Article ID.
	 */
	private static function editor_url( int $post_id ): string {
		$edit_url = get_edit_post_link( $post_id, 'url' );
		if ( ! is_string( $edit_url ) || '' === $edit_url ) {
			$edit_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
		}

		return $edit_url;
	}

	/**
	 * Returns the native editor URL with a selected snapshot management view.
	 *
	 * @param int $post_id Article ID.
	 * @param int $number One-based snapshot number.
	 */
	private static function edit_version_url( int $post_id, int $number ): string {
		return add_query_arg( 'hs_article_version_edit', (string) $number, self::editor_url( $post_id ) );
	}

	/**
	 * Finds the current public article and its saved explicit snapshots.
	 *
	 * @param bool $require_loop Whether this call must originate from the main content loop.
	 * @return array{post:WP_Post,versions:array<int, array{title:string,content:string,excerpt:string,created_gmt:string}>,active_number:int}|null Frontend context.
	 */
	private static function frontend_context( bool $require_loop = false ): ?array {
		if (
			! is_singular( 'post' ) ||
			( $require_loop && ( ! in_the_loop() || ! is_main_query() ) )
		) {
			return null;
		}

		$post_id = $require_loop ? absint( get_the_ID() ) : absint( get_queried_object_id() );
		$post    = get_post( $post_id );
		if (
			! $post instanceof WP_Post ||
			'post' !== $post->post_type ||
			'publish' !== $post->post_status ||
			'' !== $post->post_password
		) {
			return null;
		}

		$versions = self::get_versions( $post_id );
		if ( array() === $versions ) {
			return null;
		}
		return array(
			'post'          => $post,
			'versions'      => $versions,
			'active_number' => self::get_active_version_number( $post_id, $versions ),
		);
	}

	/**
	 * Defines a nonnegative REST path argument; zero denotes the original post.
	 *
	 * @return array<string, mixed>
	 */
	private static function nonnegative_integer_argument(): array {
		return array(
			'type'              => 'integer',
			'required'          => true,
			'validate_callback' => array( __CLASS__, 'validate_nonnegative_integer' ),
			'sanitize_callback' => array( __CLASS__, 'sanitize_nonnegative_integer' ),
		);
	}
	/** Validates a nonnegative integer REST path parameter.
	 *
	 * @param mixed $value Candidate value.
	 */
	public static function validate_nonnegative_integer( $value ): bool {
		return is_scalar( $value ) && ctype_digit( (string) $value );
	}
	/** Normalizes an already-validated nonnegative integer path parameter.
	 *
	 * @param mixed $value Candidate value.
	 */
	public static function sanitize_nonnegative_integer( $value ): int {
		return absint( $value );
	}

	/**
	 * Builds the internal read-only endpoint URL without altering the page URL.
	 *
	 * @param int $post_id Article ID.
	 * @param int $version Zero for the original article, otherwise a saved version number.
	 * @return string REST URL.
	 */
	private static function version_url( int $post_id, int $version ): string {
		return rest_url( self::REST_NAMESPACE . '/article-versions/' . $post_id . '/' . $version );
	}

	/**
	 * Renders archived content through normal content filters inside a post query context.
	 *
	 * @param WP_Post $article Published article.
	 * @param string  $content Saved snapshot content.
	 * @return string Sanitized rendered content.
	 */
	private static function render_snapshot_content( WP_Post $article, string $content ): string {
		$query = new WP_Query(
			array(
				'p'                   => $article->ID,
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return '';
		}

		$query->the_post();
		self::$rendering_snapshot = true;
		try {
			$rendered = apply_filters( 'the_content', $content );
		} finally {
			self::$rendering_snapshot = false;
		}
		wp_reset_postdata();
		return wp_kses_post( $rendered );
	}

	/**
	 * Reads a positive integer from an editor redirect URL.
	 *
	 * @param string $key Allowed notice query key.
	 * @return int Positive query value or zero.
	 */
	private static function request_positive_integer( string $key ): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- This is a read-only notice from a controlled redirect key.
		if ( ! isset( $_GET[ $key ] ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The scalar is checked before use.
		$value = wp_unslash( $_GET[ $key ] );

		return is_scalar( $value ) && ctype_digit( (string) $value ) ? absint( $value ) : 0;
	}

	/**
	 * Reads a finite positive integer from a state-changing editor form.
	 *
	 * @param string $key Expected form key.
	 */
	private static function request_positive_integer_from_post( string $key ): int {
		$value = self::request_post_text( $key );

		return is_string( $value ) && ctype_digit( $value ) ? absint( $value ) : 0;
	}

	/** Reads a nonnegative integer from the protected snapshot-selection form.
	 *
	 * @param string $key Expected form key.
	 */
	private static function request_nonnegative_integer_from_post( string $key ): int {
		$value = self::request_post_text( $key );

		return is_string( $value ) && ctype_digit( $value ) ? absint( $value ) : 0;
	}

	/**
	 * Reads one scalar form value after the object-specific nonce is checked.
	 *
	 * @param string $key Expected form key.
	 * @return string|null Raw unslashed value or null for an invalid shape.
	 */
	private static function request_post_text( string $key ): ?string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Every caller verifies an object-specific nonce before acting.
		if ( ! isset( $_POST[ $key ] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Scalar shape is validated here; semantic sanitization happens in the write handler.
		$value = wp_unslash( $_POST[ $key ] );

		return is_scalar( $value ) ? (string) $value : null;
	}

	/**
	 * Builds an object-specific nonce action for the editor snapshot request.
	 *
	 * @param int $post_id Article ID.
	 * @return string Nonce action.
	 */
	private static function nonce_action( int $post_id ): string {
		return self::ACTION . '_' . $post_id;
	}

	/**
	 * Builds a unique nonce scope for a destructive or editing action on one snapshot.
	 *
	 * @param string $action Action identifier.
	 * @param int    $post_id Article ID.
	 * @param int    $number One-based snapshot number.
	 */
	private static function version_nonce_action( string $action, int $post_id, int $number ): string {
		return $action . '_' . $post_id . '_' . $number;
	}
}
HS_Article_Versions::boot();
