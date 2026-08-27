<?php
/**
 * Plugin Name: Manacost AIOSEO Addon
 * Description: Adds Manacost-specific SEO clusters, fallback meta, OpenGraph and Article schema enrichment for posts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Manacost_AIOSEO_Addon {
	private const META_DISABLE = '_manacost_seo_disable';
	private const META_PRIMARY = '_manacost_seo_primary_keyword';
	private const META_EXTRA = '_manacost_seo_extra_keywords';
	private const META_TITLE = '_manacost_seo_custom_title';
	private const META_DESCRIPTION = '_manacost_seo_custom_description';
	private const NONCE_ACTION = 'manacost_seo_addon_save';
	private const NONCE_NAME = 'manacost_seo_addon_nonce';

	private static array $analysis_cache = [];
	private static bool $fallback_printed = false;

	public static function boot(): void {
		add_filter( 'aioseo_get_post', [ __CLASS__, 'enrich_aioseo_post' ], 20 );
		add_filter( 'aioseo_schema_output', [ __CLASS__, 'enrich_schema' ], 20 );
		add_filter( 'aioseo_facebook_tags', [ __CLASS__, 'enrich_facebook_tags' ], 20 );
		add_filter( 'aioseo_twitter_tags', [ __CLASS__, 'enrich_twitter_tags' ], 20 );
		add_filter( 'aioseo_keywords', [ __CLASS__, 'enrich_keywords_output' ], 20 );
		add_filter( 'pre_get_document_title', [ __CLASS__, 'fallback_document_title' ], 100000 );
		add_action( 'wp_head', [ __CLASS__, 'fallback_head_meta' ], 2 );

		add_action( 'add_meta_boxes_post', [ __CLASS__, 'add_meta_box' ] );
		add_action( 'save_post_post', [ __CLASS__, 'save_meta_box' ], 10, 2 );
		add_filter( 'manage_post_posts_columns', [ __CLASS__, 'add_posts_column' ] );
		add_action( 'manage_post_posts_custom_column', [ __CLASS__, 'render_posts_column' ], 10, 2 );
	}

	public static function enrich_aioseo_post( $aioseo_post ) {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || empty( $aioseo_post->post_id ) ) {
			return $aioseo_post;
		}

		$post_id = (int) $aioseo_post->post_id;
		$current_id = (int) get_queried_object_id();
		if (
			'post' !== get_post_type( $post_id ) ||
			self::is_disabled( $post_id ) ||
			( $current_id && is_singular( 'post' ) && $post_id !== $current_id )
		) {
			return $aioseo_post;
		}

		$analysis = self::analyze_post( $post_id );
		if ( empty( $analysis ) ) {
			return $aioseo_post;
		}

		if ( self::should_replace_aioseo_value( $aioseo_post, 'title', $post_id, self::META_TITLE ) ) {
			$aioseo_post->title = $analysis['title'];
		}
		if ( self::should_replace_aioseo_value( $aioseo_post, 'description', $post_id, self::META_DESCRIPTION ) ) {
			$aioseo_post->description = $analysis['description'];
		}
		if ( self::should_replace_aioseo_value( $aioseo_post, 'og_title', $post_id, self::META_TITLE ) ) {
			$aioseo_post->og_title = $analysis['title'];
		}
		if ( self::should_replace_aioseo_value( $aioseo_post, 'og_description', $post_id, self::META_DESCRIPTION ) ) {
			$aioseo_post->og_description = $analysis['description'];
		}
		if ( self::should_replace_aioseo_value( $aioseo_post, 'twitter_title', $post_id, self::META_TITLE ) ) {
			$aioseo_post->twitter_title = $analysis['title'];
		}
		if ( self::should_replace_aioseo_value( $aioseo_post, 'twitter_description', $post_id, self::META_DESCRIPTION ) ) {
			$aioseo_post->twitter_description = $analysis['description'];
		}
		if ( empty( $aioseo_post->keywords ) ) {
			$aioseo_post->keywords = self::keywords_to_aioseo_json( $analysis['keywords'] );
		}
		if ( empty( $aioseo_post->og_article_section ) && ! empty( $analysis['section'] ) ) {
			$aioseo_post->og_article_section = $analysis['section'];
		}
		if ( empty( $aioseo_post->og_article_tags ) ) {
			$aioseo_post->og_article_tags = self::keywords_to_aioseo_json( array_slice( $analysis['keywords'], 0, 12 ) );
		}

		return $aioseo_post;
	}

	public static function enrich_schema( $graph ): array {
		if ( ! self::is_frontend_post_context() ) {
			return is_array( $graph ) ? $graph : [];
		}

		$post_id = (int) get_queried_object_id();
		if ( ! $post_id || self::is_disabled( $post_id ) ) {
			return is_array( $graph ) ? $graph : [];
		}

		$analysis = self::analyze_post( $post_id );
		if ( empty( $analysis ) ) {
			return is_array( $graph ) ? $graph : [];
		}

		$graph = is_array( $graph ) ? $graph : [];
		$article_index = null;
		$has_breadcrumb = false;

		foreach ( $graph as $index => $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			$type = $node['@type'] ?? '';
			$types = is_array( $type ) ? $type : [ $type ];
			if ( array_intersect( $types, [ 'Article', 'BlogPosting', 'NewsArticle' ] ) ) {
				$article_index = $index;
			}
			if ( in_array( 'BreadcrumbList', $types, true ) ) {
				$has_breadcrumb = true;
			}
			if ( in_array( 'WebPage', $types, true ) && self::is_generic_aioseo_template( (string) ( $node['name'] ?? '' ) ) ) {
				$graph[ $index ]['name'] = $analysis['title'];
			}
		}

		$article = self::article_schema( $post_id, $analysis );
		if ( null === $article_index ) {
			$graph[] = $article;
		} else {
			$graph[ $article_index ] = array_filter( array_merge( $graph[ $article_index ], $article ) );
		}

		if ( ! $has_breadcrumb ) {
			$graph[] = self::breadcrumb_schema( $post_id );
		}

		return $graph;
	}

	public static function enrich_facebook_tags( array $meta ): array {
		if ( ! self::is_frontend_post_context() ) {
			return $meta;
		}

		$analysis = self::analyze_post( (int) get_queried_object_id() );
		if ( empty( $analysis ) ) {
			return $meta;
		}

		$meta['og:type'] = $meta['og:type'] ?? 'article';
		$meta['og:title'] = empty( $meta['og:title'] ) ? $analysis['title'] : $meta['og:title'];
		$meta['og:description'] = empty( $meta['og:description'] ) ? $analysis['description'] : $meta['og:description'];
		$meta['article:section'] = empty( $meta['article:section'] ) ? $analysis['section'] : $meta['article:section'];
		$meta['article:tag'] = empty( $meta['article:tag'] ) ? implode( ',', array_slice( $analysis['keywords'], 0, 10 ) ) : $meta['article:tag'];

		if ( empty( $meta['og:image'] ) && ! empty( $analysis['image'] ) ) {
			$meta['og:image'] = $analysis['image'];
			$meta['og:image:secure_url'] = is_ssl() ? $analysis['image'] : '';
		}

		return $meta;
	}

	public static function enrich_twitter_tags( array $meta ): array {
		if ( ! self::is_frontend_post_context() ) {
			return $meta;
		}

		$analysis = self::analyze_post( (int) get_queried_object_id() );
		if ( empty( $analysis ) ) {
			return $meta;
		}

		$meta['twitter:card'] = $meta['twitter:card'] ?? 'summary_large_image';
		$meta['twitter:title'] = empty( $meta['twitter:title'] ) ? $analysis['title'] : $meta['twitter:title'];
		$meta['twitter:description'] = empty( $meta['twitter:description'] ) ? $analysis['description'] : $meta['twitter:description'];
		if ( empty( $meta['twitter:image'] ) && ! empty( $analysis['image'] ) ) {
			$meta['twitter:image'] = $analysis['image'];
		}

		return $meta;
	}

	public static function enrich_keywords_output( string $keywords ): string {
		if ( ! self::is_frontend_post_context() ) {
			return $keywords;
		}

		$analysis = self::analyze_post( (int) get_queried_object_id() );
		if ( empty( $analysis['keywords'] ) ) {
			return $keywords;
		}

		$current = array_filter( array_map( 'trim', explode( ',', $keywords ) ) );
		$merged = self::unique_values( array_merge( $current, $analysis['keywords'] ) );

		return implode( ',', array_slice( $merged, 0, 30 ) );
	}

	public static function fallback_document_title( string $title ): string {
		if ( ! self::is_frontend_post_context() ) {
			return $title;
		}

		$post_id = (int) get_queried_object_id();
		if ( self::is_disabled( $post_id ) ) {
			return $title;
		}

		$stored_title = trim( (string) get_post_meta( $post_id, '_aioseo_title', true ) );
		if ( '' !== $stored_title && ! self::is_generic_aioseo_template( $stored_title ) ) {
			return $title;
		}

		if ( '' !== $title && ! self::is_generic_aioseo_template( $title ) ) {
			return $title;
		}

		$analysis = self::analyze_post( $post_id );
		return ! empty( $analysis['title'] ) ? $analysis['title'] : $title;
	}

	public static function fallback_head_meta(): void {
		if ( self::$fallback_printed || self::has_aioseo() || ! self::is_frontend_post_context() ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		$analysis = self::analyze_post( $post_id );
		if ( empty( $analysis ) ) {
			return;
		}
		self::$fallback_printed = true;

		$canonical = get_permalink( $post_id );
		echo "\n<!-- Manacost SEO Addon fallback -->\n";
		echo '<meta name="description" content="' . esc_attr( $analysis['description'] ) . '">' . "\n";
		echo '<meta name="keywords" content="' . esc_attr( implode( ',', array_slice( $analysis['keywords'], 0, 30 ) ) ) . '">' . "\n";
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
		echo '<meta property="og:type" content="article">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $analysis['title'] ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $analysis['description'] ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $canonical ) . '">' . "\n";
		if ( ! empty( $analysis['image'] ) ) {
			echo '<meta property="og:image" content="' . esc_url( $analysis['image'] ) . '">' . "\n";
		}
		echo '<script type="application/ld+json">' . wp_json_encode(
			[
				'@context' => 'https://schema.org',
				'@graph'   => [
					self::article_schema( $post_id, $analysis ),
					self::breadcrumb_schema( $post_id ),
				],
			],
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		) . '</script>' . "\n";
		echo "<!-- /Manacost SEO Addon fallback -->\n";
	}

	public static function add_meta_box(): void {
		add_meta_box(
			'manacost-aioseo-addon',
			'Manacost SEO Addon',
			[ __CLASS__, 'render_meta_box' ],
			'post',
			'normal',
			'high'
		);
	}

	public static function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$analysis = self::analyze_post( $post->ID, true );
		$disabled = self::is_disabled( $post->ID );
		$primary = get_post_meta( $post->ID, self::META_PRIMARY, true );
		$extra = get_post_meta( $post->ID, self::META_EXTRA, true );
		$custom_title = get_post_meta( $post->ID, self::META_TITLE, true );
		$custom_description = get_post_meta( $post->ID, self::META_DESCRIPTION, true );

		echo '<style>
			.manacost-seo-addon-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:10px}
			.manacost-seo-addon-card{padding:12px 14px;border:1px solid #dcdcde;border-radius:8px;background:#fff}
			.manacost-seo-addon-card h4{margin:0 0 8px;font-size:13px}
			.manacost-seo-addon-card p{margin:0 0 8px;color:#3c434a}
			.manacost-seo-addon-field{margin:12px 0}
			.manacost-seo-addon-field label{display:block;font-weight:600;margin-bottom:4px}
			.manacost-seo-addon-field input,.manacost-seo-addon-field textarea{width:100%}
			.manacost-seo-addon-keywords{display:flex;flex-wrap:wrap;gap:6px}
			.manacost-seo-addon-keywords span{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef6ff;color:#0b5ed7;font-size:12px}
			@media(max-width:900px){.manacost-seo-addon-grid{grid-template-columns:1fr}}
		</style>';

		echo '<p>Аддон дополняет All in One SEO: если поля AIOSEO пустые, он подставляет Manacost-заголовок, описание, keyword cluster, OpenGraph и Article schema. Если поля заполнены руками, они остаются главнее.</p>';

		echo '<div class="manacost-seo-addon-field"><label><input type="checkbox" name="manacost_seo_disable" value="1" ' . checked( $disabled, true, false ) . '> Отключить автоусиление SEO для этой статьи</label></div>';

		echo '<div class="manacost-seo-addon-grid">';
		echo '<div class="manacost-seo-addon-card"><h4>Авто Title</h4><p>' . esc_html( $analysis['title'] ?? '' ) . '</p><small>' . esc_html( (string) mb_strlen( $analysis['title'] ?? '' ) ) . ' символов</small></div>';
		echo '<div class="manacost-seo-addon-card"><h4>Авто Description</h4><p>' . esc_html( $analysis['description'] ?? '' ) . '</p><small>' . esc_html( (string) mb_strlen( $analysis['description'] ?? '' ) ) . ' символов</small></div>';
		echo '</div>';

		echo '<div class="manacost-seo-addon-field"><label for="manacost_seo_custom_title">Свой SEO Title аддона</label><input type="text" id="manacost_seo_custom_title" name="manacost_seo_custom_title" value="' . esc_attr( $custom_title ) . '" maxlength="90"><p class="description">Используется только если нужно переопределить автогенерацию аддона. Ручное поле AIOSEO все равно главнее.</p></div>';
		echo '<div class="manacost-seo-addon-field"><label for="manacost_seo_custom_description">Свой SEO Description аддона</label><textarea id="manacost_seo_custom_description" name="manacost_seo_custom_description" rows="3" maxlength="180">' . esc_textarea( $custom_description ) . '</textarea></div>';
		echo '<div class="manacost-seo-addon-field"><label for="manacost_seo_primary_keyword">Главный запрос</label><input type="text" id="manacost_seo_primary_keyword" name="manacost_seo_primary_keyword" value="' . esc_attr( $primary ) . '" placeholder="например: топ колоды Hearthstone"></div>';
		echo '<div class="manacost-seo-addon-field"><label for="manacost_seo_extra_keywords">Дополнительные запросы через запятую</label><textarea id="manacost_seo_extra_keywords" name="manacost_seo_extra_keywords" rows="2" placeholder="муллиган, код колоды, винрейт">' . esc_textarea( $extra ) . '</textarea></div>';

		echo '<div class="manacost-seo-addon-card"><h4>Keyword cluster</h4><div class="manacost-seo-addon-keywords">';
		foreach ( array_slice( $analysis['keywords'] ?? [], 0, 28 ) as $keyword ) {
			echo '<span>' . esc_html( $keyword ) . '</span>';
		}
		echo '</div></div>';
	}

	public static function save_meta_box( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, self::META_DISABLE, isset( $_POST['manacost_seo_disable'] ) ? '1' : '0' );
		self::save_text_meta( $post_id, self::META_PRIMARY, 'manacost_seo_primary_keyword', 120 );
		self::save_text_meta( $post_id, self::META_EXTRA, 'manacost_seo_extra_keywords', 600 );
		self::save_text_meta( $post_id, self::META_TITLE, 'manacost_seo_custom_title', 90 );
		self::save_text_meta( $post_id, self::META_DESCRIPTION, 'manacost_seo_custom_description', 180 );
	}

	public static function add_posts_column( array $columns ): array {
		$columns['manacost_seo_addon'] = 'SEO cluster';
		return $columns;
	}

	public static function render_posts_column( string $column, int $post_id ): void {
		if ( 'manacost_seo_addon' !== $column ) {
			return;
		}
		if ( self::is_disabled( $post_id ) ) {
			echo '<span style="color:#b32d2e">выключено</span>';
			return;
		}
		$analysis = self::analyze_post( $post_id, true );
		$keywords = array_slice( $analysis['keywords'] ?? [], 0, 4 );
		echo esc_html( implode( ', ', $keywords ) );
	}

	private static function analyze_post( int $post_id, bool $force = false ): array {
		if ( ! $post_id ) {
			return [];
		}
		if ( ! $force && isset( self::$analysis_cache[ $post_id ] ) ) {
			return self::$analysis_cache[ $post_id ];
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
			return [];
		}

		$title = wp_strip_all_tags( get_the_title( $post ) );
		$plain = self::plain_text( $post );
		$terms = self::post_terms( $post_id );
		$haystack = mb_strtolower( $title . ' ' . $plain . ' ' . implode( ' ', $terms ) );
		$type = self::detect_type( $haystack );
		$classes = self::detect_classes( $haystack );
		$modes = self::detect_modes( $haystack );
		$patch = self::detect_patch( $title . ' ' . $plain );
		$section = self::section_label( $type, $classes, $modes );
		$custom_title = trim( (string) get_post_meta( $post_id, self::META_TITLE, true ) );
		$custom_description = trim( (string) get_post_meta( $post_id, self::META_DESCRIPTION, true ) );

		$keywords = self::build_keywords( $post_id, $title, $type, $classes, $modes, $patch, $terms );
		$description = $custom_description ?: self::build_description( $title, $type, $classes, $modes, $patch, $plain );
		$seo_title = $custom_title ?: self::build_title( $title, $type, $classes, $modes, $patch );
		$image = get_the_post_thumbnail_url( $post_id, 'full' );

		$analysis = [
			'title'       => self::limit_text( $seo_title, 68 ),
			'description' => self::limit_text( $description, 158 ),
			'keywords'    => $keywords,
			'type'        => $type,
			'classes'     => $classes,
			'modes'       => $modes,
			'patch'       => $patch,
			'section'     => $section,
			'image'       => $image ?: '',
		];

		self::$analysis_cache[ $post_id ] = $analysis;
		return $analysis;
	}

	private static function build_title( string $title, string $type, array $classes, array $modes, string $patch ): string {
		$suffix = ' | Manacost';
		$lower = mb_strtolower( $title );
		$base = $title;

		if ( 'deck' === $type && false === mb_stripos( $lower, 'код' ) ) {
			$base .= ': код колоды, винрейт и статистика';
		} elseif ( 'guide' === $type && false === mb_stripos( $lower, 'гайд' ) ) {
			$base .= ': гайд, стратегия и советы';
		} elseif ( 'meta' === $type && false === mb_stripos( $lower, 'мета' ) ) {
			$base .= ': лучшие колоды и мета';
		} elseif ( 'patch' === $type ) {
			$base .= ': изменения и разбор';
		} elseif ( 'battlegrounds' === $type ) {
			$base .= ': Поля сражений Hearthstone';
		}

		if ( $patch && false === mb_stripos( $base, $patch ) ) {
			$base .= ' ' . $patch;
		}

		if ( mb_strlen( $base . $suffix ) <= 68 ) {
			return $base . $suffix;
		}

		// Режем сам заголовок с запасом под суффикс, а не строку целиком:
		// иначе обрезался бренд и оставалась висящая палка («… |…»).
		return self::limit_text( $title, 68 - mb_strlen( $suffix ) ) . $suffix;
	}

	private static function build_description( string $title, string $type, array $classes, array $modes, string $patch, string $plain ): string {
		$bits = [];
		if ( $classes ) {
			$bits[] = implode( ', ', $classes );
		}
		if ( $modes ) {
			$bits[] = implode( ', ', $modes );
		}
		if ( $patch ) {
			$bits[] = 'патч ' . $patch;
		}
		$context = $bits ? ' (' . implode( '; ', $bits ) . ')' : '';

		if ( 'deck' === $type ) {
			return $title . $context . ': код колоды, статистика, винрейт и полезные данные для игры в Hearthstone на Manacost.';
		}
		if ( 'guide' === $type ) {
			return $title . $context . ': гайд, стратегия, ключевые советы, муллиган и практический разбор для Hearthstone.';
		}
		if ( 'meta' === $type ) {
			return $title . $context . ': обзор меты Hearthstone, сильные архетипы, тенденции и лучшие колоды на Manacost.';
		}
		if ( 'patch' === $type ) {
			return $title . $context . ': изменения, баланс карт, важные детали обновления и разбор влияния на Hearthstone.';
		}

		$excerpt = self::limit_text( $plain, 118 );
		return $excerpt ? $excerpt . ' Читайте подробный материал по Hearthstone на Manacost.' : $title . ': подробный материал по Hearthstone на Manacost.';
	}

	private static function build_keywords( int $post_id, string $title, string $type, array $classes, array $modes, string $patch, array $terms ): array {
		$primary = trim( (string) get_post_meta( $post_id, self::META_PRIMARY, true ) );
		$extra = trim( (string) get_post_meta( $post_id, self::META_EXTRA, true ) );
		$keywords = [];

		if ( $primary ) {
			$keywords[] = $primary;
		}

		$keywords = array_merge( $keywords, [ 'Hearthstone', 'Манакост', 'Manacost' ] );

		$type_keywords = [
			'deck'          => [ 'колоды Hearthstone', 'код колоды', 'винрейт колоды', 'статистика колоды', 'мета колода' ],
			'guide'         => [ 'гайд Hearthstone', 'стратегия Hearthstone', 'муллиган', 'матчапы', 'советы по игре' ],
			'meta'          => [ 'мета Hearthstone', 'топ колоды Hearthstone', 'лучшие колоды', 'тир лист Hearthstone', 'мета отчет' ],
			'patch'         => [ 'патч Hearthstone', 'обновление Hearthstone', 'баланс карт', 'изменения Hearthstone' ],
			'battlegrounds' => [ 'Поля сражений Hearthstone', 'Battlegrounds', 'тир лист Поля сражений', 'герои Поля сражений' ],
			'article'       => [ 'Hearthstone гайд', 'Hearthstone новости', 'Hearthstone статья' ],
		];
		$keywords = array_merge( $keywords, $type_keywords[ $type ] ?? $type_keywords['article'] );

		foreach ( $classes as $class ) {
			$keywords[] = $class . ' Hearthstone';
			$keywords[] = 'колода ' . $class;
			if ( 'guide' === $type ) {
				$keywords[] = 'гайд ' . $class;
			}
		}
		foreach ( $modes as $mode ) {
			$keywords[] = $mode . ' Hearthstone';
		}
		if ( $patch ) {
			$keywords[] = 'Hearthstone ' . $patch;
			$keywords[] = 'патч ' . $patch;
		}

		$keywords = array_merge( $keywords, array_slice( $terms, 0, 10 ) );

		if ( $extra ) {
			$keywords = array_merge( $keywords, preg_split( '/[,;\n]+/u', $extra ) ?: [] );
		}

		$keywords[] = $title;
		return array_slice( self::unique_values( $keywords ), 0, 40 );
	}

	private static function article_schema( int $post_id, array $analysis ): array {
		$post = get_post( $post_id );
		$author_name = $post ? get_the_author_meta( 'display_name', (int) $post->post_author ) : '';
		$permalink = get_permalink( $post_id );
		$schema = [
			'@type'            => 'BlogPosting',
			'@id'              => trailingslashit( $permalink ) . '#manacost-article',
			'name'             => $analysis['title'],
			'mainEntityOfPage' => [ '@id' => $permalink ],
			'url'              => $permalink,
			'headline'         => $analysis['title'],
			'description'      => $analysis['description'],
			'inLanguage'       => 'ru-RU',
			'isAccessibleForFree' => true,
			'datePublished'    => get_the_date( DATE_W3C, $post_id ),
			'dateModified'     => get_the_modified_date( DATE_W3C, $post_id ),
			'articleSection'   => $analysis['section'],
			'keywords'         => implode( ', ', array_slice( $analysis['keywords'], 0, 20 ) ),
			'about'            => array_map(
				static fn( $keyword ) => [ '@type' => 'Thing', 'name' => $keyword ],
				array_slice( $analysis['keywords'], 0, 8 )
			),
			'publisher'        => [
				'@type' => 'Organization',
				'name'  => 'Manacost',
				'url'   => home_url( '/' ),
			],
		];
		if ( $author_name ) {
			$schema['author'] = [
				'@type' => 'Person',
				'name'  => $author_name,
			];
		}
		if ( ! empty( $analysis['image'] ) ) {
			$schema['image'] = [ $analysis['image'] ];
		}

		return $schema;
	}

	private static function breadcrumb_schema( int $post_id ): array {
		$items = [
			[
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => 'Главная',
				'item'     => home_url( '/' ),
			],
		];

		$category = self::primary_category( $post_id );
		if ( $category ) {
			$items[] = [
				'@type'    => 'ListItem',
				'position' => count( $items ) + 1,
				'name'     => $category->name,
				'item'     => get_category_link( $category ),
			];
		}

		$items[] = [
			'@type'    => 'ListItem',
			'position' => count( $items ) + 1,
			'name'     => wp_strip_all_tags( get_the_title( $post_id ) ),
			'item'     => get_permalink( $post_id ),
		];

		return [
			'@type'           => 'BreadcrumbList',
			'@id'             => trailingslashit( get_permalink( $post_id ) ) . '#manacost-breadcrumbs',
			'itemListElement' => $items,
		];
	}

	private static function detect_type( string $haystack ): string {
		if ( preg_match( '/\b(мета|meta|vicious|тир[\s-]?лист|топ колод|лучшие колоды)\b/u', $haystack ) ) {
			return 'meta';
		}
		if ( preg_match( '/\b(патч|обновлени|баланс|нерф|бафф)\b/u', $haystack ) ) {
			return 'patch';
		}
		if ( preg_match( '/\b(поля сражений|battlegrounds|бг)\b/u', $haystack ) ) {
			return 'battlegrounds';
		}
		if ( preg_match( '/\b(колод[аы]|дек[аи]|код колоды|винрейт|муллиган)\b/u', $haystack ) ) {
			return 'deck';
		}
		if ( preg_match( '/\b(гайд|стратеги|матчап|муллиган|совет)\b/u', $haystack ) ) {
			return 'guide';
		}
		return 'article';
	}

	private static function detect_classes( string $haystack ): array {
		$classes = [
			'Рыцарь смерти' => [ 'рыцарь смерти', 'дк', 'death knight' ],
			'Охотник на демонов' => [ 'охотник на демонов', 'демон хантер', 'dh', 'demon hunter' ],
			'Друид' => [ 'друид', 'druid' ],
			'Охотник' => [ 'охотник', 'hunter' ],
			'Маг' => [ 'маг', 'mage' ],
			'Паладин' => [ 'паладин', 'paladin' ],
			'Жрец' => [ 'жрец', 'прист', 'priest' ],
			'Разбойник' => [ 'разбойник', 'рога', 'rogue' ],
			'Шаман' => [ 'шаман', 'shaman' ],
			'Чернокнижник' => [ 'чернокнижник', 'лок', 'warlock' ],
			'Воин' => [ 'воин', 'warrior' ],
		];

		$found = [];
		foreach ( $classes as $label => $needles ) {
			foreach ( $needles as $needle ) {
				if ( false !== mb_stripos( $haystack, $needle ) ) {
					$found[] = $label;
					break;
				}
			}
		}
		return array_values( array_unique( $found ) );
	}

	private static function detect_modes( string $haystack ): array {
		$modes = [
			'Стандарт' => [ 'стандарт', 'standard' ],
			'Вольный' => [ 'вольный', 'wild' ],
			'Арена' => [ 'арена', 'arena' ],
			'Поля сражений' => [ 'поля сражений', 'battlegrounds' ],
			'Твист' => [ 'твист', 'twist' ],
		];

		$found = [];
		foreach ( $modes as $label => $needles ) {
			foreach ( $needles as $needle ) {
				if ( false !== mb_stripos( $haystack, $needle ) ) {
					$found[] = $label;
					break;
				}
			}
		}
		return array_values( array_unique( $found ) );
	}

	private static function detect_patch( string $text ): string {
		if ( preg_match( '/\b(\d{1,2}\.\d{1,2}(?:\.\d{1,2})?)\b/u', $text, $m ) ) {
			return $m[1];
		}
		return '';
	}

	private static function section_label( string $type, array $classes, array $modes ): string {
		if ( $classes ) {
			return $classes[0];
		}
		if ( $modes ) {
			return $modes[0];
		}
		$labels = [
			'deck' => 'Колоды Hearthstone',
			'guide' => 'Гайды Hearthstone',
			'meta' => 'Мета Hearthstone',
			'patch' => 'Обновления Hearthstone',
			'battlegrounds' => 'Поля сражений',
		];
		return $labels[ $type ] ?? 'Hearthstone';
	}

	private static function post_terms( int $post_id ): array {
		$terms = [];
		foreach ( [ 'category', 'post_tag' ] as $taxonomy ) {
			$post_terms = get_the_terms( $post_id, $taxonomy );
			if ( is_array( $post_terms ) ) {
				foreach ( $post_terms as $term ) {
					$terms[] = $term->name;
				}
			}
		}
		return self::unique_values( $terms );
	}

	private static function primary_category( int $post_id ): ?WP_Term {
		$categories = get_the_category( $post_id );
		if ( empty( $categories ) || ! is_array( $categories ) ) {
			return null;
		}
		return $categories[0] instanceof WP_Term ? $categories[0] : null;
	}

	private static function plain_text( WP_Post $post ): string {
		$text = strip_shortcodes( $post->post_excerpt ?: $post->post_content );
		$text = wp_strip_all_tags( $text );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( (string) $text );
	}

	private static function limit_text( string $text, int $limit ): string {
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );
		if ( mb_strlen( $text ) <= $limit ) {
			return $text;
		}
		$cut = mb_substr( $text, 0, $limit - 1 );
		$last_space = mb_strrpos( $cut, ' ' );
		if ( false !== $last_space && $last_space > 40 ) {
			$cut = mb_substr( $cut, 0, $last_space );
		}
		// rtrim работает побайтово и покрошил бы многобайтные тире, поэтому
		// хвостовые пробелы и пунктуацию снимаем регуляркой в режиме UTF-8.
		$trimmed = preg_replace( '/[\s\p{P}]+$/u', '', $cut );

		if ( null !== $trimmed && '' !== $trimmed ) {
			$cut = $trimmed;
		}

		return $cut . '…';
	}

	private static function unique_values( array $values ): array {
		$seen = [];
		$out = [];
		foreach ( $values as $value ) {
			$value = trim( wp_strip_all_tags( (string) $value ) );
			if ( '' === $value ) {
				continue;
			}
			$key = mb_strtolower( $value );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$out[] = $value;
		}
		return $out;
	}

	private static function keywords_to_aioseo_json( array $keywords ): string {
		$items = [];
		foreach ( array_slice( self::unique_values( $keywords ), 0, 40 ) as $keyword ) {
			$items[] = [
				'label' => $keyword,
				'value' => $keyword,
			];
		}
		return wp_json_encode( $items, JSON_UNESCAPED_UNICODE );
	}

	private static function should_replace_aioseo_value( $aioseo_post, string $field, int $post_id, string $custom_meta ): bool {
		if ( '' !== trim( (string) get_post_meta( $post_id, $custom_meta, true ) ) ) {
			return true;
		}

		$value = isset( $aioseo_post->{$field} ) ? trim( (string) $aioseo_post->{$field} ) : '';
		if ( '' === $value ) {
			return true;
		}

		if ( self::is_generic_aioseo_template( $value ) ) {
			return true;
		}

		$manual_meta_keys = [
			'title'               => [ '_aioseo_title', '_aioseop_title' ],
			'description'         => [ '_aioseo_description', '_aioseop_description' ],
			'og_title'            => [ '_aioseo_og_title' ],
			'og_description'      => [ '_aioseo_og_description' ],
			'twitter_title'       => [ '_aioseo_twitter_title' ],
			'twitter_description' => [ '_aioseo_twitter_description' ],
		];

		foreach ( $manual_meta_keys[ $field ] ?? [] as $meta_key ) {
			$manual_value = trim( (string) get_post_meta( $post_id, $meta_key, true ) );
			if ( '' !== $manual_value && ! self::is_generic_aioseo_template( $manual_value ) ) {
				return false;
			}
		}

		return method_exists( $aioseo_post, 'exists' ) ? ! $aioseo_post->exists() : false;
	}

	private static function is_generic_aioseo_template( string $value ): bool {
		$value = trim( $value );
		if ( '' === $value ) {
			return true;
		}

		if ( preg_match( '/#(?:post_title|site_title|separator|tagline|title|category|author)/i', $value ) ) {
			return true;
		}

		if ( preg_match( '/\|\s*(Hearthstone|Манакост Hearthstone|Manacost)$/iu', $value ) ) {
			return true;
		}

		return false;
	}

	private static function save_text_meta( int $post_id, string $meta_key, string $field, int $max_length ): void {
		$value = isset( $_POST[ $field ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) : '';
		$value = self::limit_text( $value, $max_length );
		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}
		update_post_meta( $post_id, $meta_key, $value );
	}

	private static function is_frontend_post_context(): bool {
		return ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() && is_singular( 'post' );
	}

	private static function is_disabled( int $post_id ): bool {
		return '1' === (string) get_post_meta( $post_id, self::META_DISABLE, true );
	}

	private static function has_aioseo(): bool {
		return function_exists( 'aioseo' );
	}
}

Manacost_AIOSEO_Addon::boot();
