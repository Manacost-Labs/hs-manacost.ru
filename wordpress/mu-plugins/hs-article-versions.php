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
 */
final class HS_Article_Versions {
	private const ACTION         = 'hs_create_article_version';
	private const META_KEY       = '_hs_article_versions';
	private const REST_NAMESPACE = 'manacost/v1';
	private const REST_ROUTE     = '/article-versions/(?P<post_id>\d+)/(?P<version>\d+)';

	/** Registers the scoped editor, frontend, and read-only REST entrypoints. */
	public static function boot(): void {
		add_action( 'media_buttons', array( __CLASS__, 'render_media_button' ), 20, 1 );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_create_version' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notice' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_route' ) );
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

		$versions = self::get_versions( $post_id );
		if ( self::matches_latest_snapshot( $versions, $post ) ) {
			self::redirect_to_editor( $post_id, 'hs_article_version_exists', '1' );
		}

		$versions[] = self::snapshot_post( $post );
		$updated    = update_post_meta( $post_id, self::META_KEY, wp_slash( $versions ) );
		if ( false === $updated ) {
			wp_die( esc_html__( 'Не удалось сохранить версию статьи. Повторите попытку.', 'manacost' ), '', array( 'response' => 500 ) );
		}

		clean_post_cache( $post_id );

		self::redirect_to_editor( $post_id, 'hs_article_version_created', (string) count( $versions ) );
	}

	/** Shows a concise result after the editor returns to the original article. */
	public static function render_admin_notice(): void {
		$created = self::request_positive_integer( 'hs_article_version_created' );
		$exists  = self::request_positive_integer( 'hs_article_version_exists' );

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
					'post_id' => self::positive_integer_argument(),
					'version' => self::positive_integer_argument(),
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
		$context = self::frontend_context( true );
		if ( null === $context ) {
			return $content;
		}

		$post     = $context['post'];
		$versions = $context['versions'];
		$options  = '<option value="current">' . esc_html__( 'Актуальная версия', 'manacost' ) . '</option>';

		foreach ( $versions as $index => $snapshot ) {
			$number   = $index + 1;
			$url      = self::version_url( $post->ID, $number );
			$options .= '<option value="' . esc_attr( (string) $number ) . '" data-hs-article-version-url="' . esc_url( $url ) . '">';
			$options .= esc_html(
				sprintf(
					/* translators: %d: saved article version number. */
					__( 'Версия %d', 'manacost' ),
					$number
				)
			);
			$options .= '</option>';
		}

		$selector  = '<nav class="hs-article-versions" aria-label="' . esc_attr__( 'Версии статьи', 'manacost' ) . '">';
		$selector .= '<span class="hs-article-versions__label">' . esc_html__( 'Версии', 'manacost' ) . '</span>';
		$selector .= '<label class="screen-reader-text" for="hs-article-version-select">' . esc_html__( 'Выберите версию статьи', 'manacost' ) . '</label>';
		$selector .= '<select id="hs-article-version-select" class="hs-article-versions__select">' . $options . '</select>';
		$selector .= '<span id="hs-article-version-status" class="hs-article-versions__status" aria-live="polite"></span>';
		$selector .= '</nav>';

		return $selector . '<div id="hs-article-version-content" data-hs-article-version-content="current">' . $content . '</div>';
	}

	/** Prints presentation scoped to articles with manually saved versions. */
	public static function render_styles(): void {
		if ( null === self::frontend_context() ) {
			return;
		}

		echo '<style id="hs-article-versions-styles">';
		echo '.hs-article-versions{display:inline-flex;align-items:center;gap:8px;max-width:100%;margin:0 0 16px;padding:6px 9px;border:1px solid rgba(70,82,96,.22);border-left:3px solid var(--theme-palette-color-1,#c98a27);border-radius:5px;background:rgba(245,247,250,.72);color:var(--theme-text-color,#263241);font-size:13px;line-height:1.35}';
		echo '.hs-article-versions__label{font-weight:700;white-space:nowrap}.hs-article-versions__select{min-width:0;max-width:210px;height:30px;margin:0;padding:3px 28px 3px 8px;border:1px solid rgba(70,82,96,.28);border-radius:3px;background:#fff;color:inherit;font:inherit;cursor:pointer}.hs-article-versions__select:focus{outline:2px solid var(--theme-palette-color-1,#c98a27);outline-offset:2px}.hs-article-versions__status{min-height:1px}.hs-article-versions__status:not(:empty){position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(1px,1px,1px,1px);white-space:nowrap}';
		echo '@media (max-width:480px){.hs-article-versions{display:flex;width:100%;flex-wrap:wrap;gap:5px;margin-bottom:14px}.hs-article-versions__select{flex:1;max-width:none;min-height:36px}}';
		echo '</style>';
	}

	/** Prints a progressive enhancement that keeps the visible page URL unchanged. */
	public static function render_script(): void {
		if ( null === self::frontend_context() ) {
			return;
		}

		echo '<script id="hs-article-versions-script">';
		echo '(function(){var select=document.getElementById("hs-article-version-select"),content=document.getElementById("hs-article-version-content"),status=document.getElementById("hs-article-version-status");if(!select||!content){return;}var title=document.querySelector("article .entry-title"),initial={content:content.innerHTML,title:title?title.textContent:""},previous="current";select.addEventListener("change",function(){var option=select.options[select.selectedIndex],next=option.value;if("current"===next){content.innerHTML=initial.content;if(title){title.textContent=initial.title;}previous=next;if(status){status.textContent="Показана актуальная версия статьи.";}return;}var url=option.getAttribute("data-hs-article-version-url");if(!url){select.value=previous;return;}select.disabled=true;if(status){status.textContent="Загружается выбранная версия статьи.";}fetch(url,{credentials:"same-origin",headers:{"Accept":"application/json"}}).then(function(response){if(!response.ok){throw new Error("version request failed");}return response.json();}).then(function(payload){if(!payload||"string"!==typeof payload.content){throw new Error("invalid version response");}content.innerHTML=payload.content;if(title&&"string"===typeof payload.title){title.textContent=payload.title;}previous=next;if(status){status.textContent="Показана версия "+next+" статьи.";}}).catch(function(){select.value=previous;if(status){status.textContent="Не удалось загрузить выбранную версию статьи.";}}).finally(function(){select.disabled=false;});});}());';
		echo '</script>';
	}

	/**
	 * Returns clean, explicitly saved snapshots. Invalid historical metadata is ignored.
	 *
	 * @param int $post_id Article ID.
	 * @return array<int, array{title:string,content:string,excerpt:string,created_gmt:string}>
	 */
	private static function get_versions( int $post_id ): array {
		$stored = get_post_meta( $post_id, self::META_KEY, true );
		if ( ! is_array( $stored ) ) {
			return array();
		}

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
		$edit_url = get_edit_post_link( $post_id, 'url' );
		if ( ! is_string( $edit_url ) || '' === $edit_url ) {
			$edit_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
		}

		wp_safe_redirect( add_query_arg( $key, $value, $edit_url ) );
		exit;
	}

	/**
	 * Finds the current public article and its saved explicit snapshots.
	 *
	 * @param bool $require_loop Whether this call must originate from the main content loop.
	 * @return array{post:WP_Post,versions:array<int, array{title:string,content:string,excerpt:string,created_gmt:string}>}|null Frontend context.
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
			'post'     => $post,
			'versions' => $versions,
		);
	}

	/**
	 * Defines one validated positive integer REST argument.
	 *
	 * @return array<string, mixed> REST argument definition.
	 */
	private static function positive_integer_argument(): array {
		return array(
			'type'              => 'integer',
			'required'          => true,
			'validate_callback' => array( __CLASS__, 'validate_positive_integer' ),
			'sanitize_callback' => array( __CLASS__, 'sanitize_positive_integer' ),
		);
	}

	/**
	 * Validates a positive integer path parameter.
	 *
	 * @param mixed $value Candidate REST value.
	 * @return bool Whether the value is a positive integer.
	 */
	public static function validate_positive_integer( $value ): bool {
		return is_scalar( $value ) && ctype_digit( (string) $value ) && absint( $value ) > 0;
	}

	/**
	 * Normalizes an already-validated positive integer path parameter.
	 *
	 * @param mixed $value Candidate REST value.
	 * @return int Normalized positive integer.
	 */
	public static function sanitize_positive_integer( $value ): int {
		return absint( $value );
	}

	/**
	 * Builds the internal read-only endpoint URL without altering the page URL.
	 *
	 * @param int $post_id Article ID.
	 * @param int $version Saved version number.
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
		$rendered = apply_filters( 'the_content', $content );
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
	 * Builds an object-specific nonce action for the editor snapshot request.
	 *
	 * @param int $post_id Article ID.
	 * @return string Nonce action.
	 */
	private static function nonce_action( int $post_id ): string {
		return self::ACTION . '_' . $post_id;
	}
}

HS_Article_Versions::boot();
