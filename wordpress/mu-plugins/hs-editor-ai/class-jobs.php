<?php
/**
 * Private, expiring background jobs; editor save never waits for a model.
 *
 * @package Manacost
 */

namespace Manacost\EditorAi;

defined( 'ABSPATH' ) || exit;

/** One pending job per editor, with explicit post authorization. */
final class Jobs {
	private const TTL = 1800;

	/**
	 * Register private REST routes and the queue worker.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
		add_action( 'hs_editor_ai_process', array( self::class, 'process' ) );
		add_action( 'hs_editor_ai_expire', array( self::class, 'expire' ) );
	}

	/**
	 * Existing editor rollout; no additional author access is introduced.
	 *
	 * @return bool
	 */
	public static function allowed(): bool {
		return function_exists( 'hs_ew_is_enabled' ) && hs_ew_is_enabled() && current_user_can( 'edit_posts' );
	}

	/**
	 * Register cookie-authenticated, nonce-protected routes.
	 *
	 * @return void
	 */
	public static function routes(): void {
		register_rest_route(
			'hs-editor-ai/v1',
			'/jobs',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( self::class, 'allowed' ),
				'callback'            => array( self::class, 'start' ),
				'args'                => array(
					'post_id' => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 1,
					),
					'mode'    => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'cards', 'editor', 'proofread' ),
					),
					'source'  => array(
						'type'      => 'string',
						'required'  => true,
						'maxLength' => 160000,
					),
					'context' => array(
						'type'      => 'string',
						'required'  => true,
						'maxLength' => 200000,
					),
				),
			)
		);
		register_rest_route(
			'hs-editor-ai/v1',
			'/jobs/(?P<id>[a-f0-9]{32})',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( self::class, 'allowed' ),
				'callback'            => array( self::class, 'status' ),
			)
		);
		register_rest_route(
			'hs-editor-ai/v1',
			'/jobs/(?P<id>[a-f0-9]{32})/run',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( self::class, 'allowed' ),
				'callback'            => array( self::class, 'dispatch' ),
			)
		);
	}

	/**
	 * Run an already authorized job in a separate browser request; cron is backup.
	 *
	 * @param \WP_REST_Request $request Owner lookup, authenticated with REST nonce.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function dispatch( \WP_REST_Request $request ) {
		$status = self::status( $request );
		if ( is_wp_error( $status ) ) {
			return $status;
		}
		if ( ! Provider::enabled() ) {
			return new \WP_Error( 'editor_ai_disabled', __( 'ИИ отключён. Исходный текст сохранён.', 'manacost' ), array( 'status' => 503 ) );
		}
		self::process( (string) $request['id'], true );
		return self::status( $request );
	}

	/**
	 * Start a bounded job, reusing an identical in-flight request.
	 *
	 * @param \WP_REST_Request $request Authorized input.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function start( \WP_REST_Request $request ) {
		$post_id = (int) $request['post_id'];
		$source  = $request['source'];
		$context = $request['context'];
		$mode    = $request['mode'];
		if ( ! current_user_can( 'edit_post', $post_id ) || 'post' !== get_post_type( $post_id ) ) {
			return new \WP_Error( 'editor_ai_forbidden', __( 'Нет доступа к этой статье.', 'manacost' ), array( 'status' => 403 ) );
		}
		if ( ! Provider::enabled() ) {
			return new \WP_Error( 'editor_ai_disabled', __( 'Серверный API-ключ не настроен или ИИ отключён.', 'manacost' ), array( 'status' => 503 ) );
		}
		if ( ! self::valid_input( $source, $context, $mode ) ) {
			return new \WP_Error( 'editor_ai_size', __( 'Выберите текст до 160 КБ, контекст до 200 КБ и допустимый режим.', 'manacost' ), array( 'status' => 400 ) );
		}
		$user        = get_current_user_id();
		$fingerprint = hash( 'sha256', $post_id . ':' . $mode . ':' . strlen( $source ) . ':' . $source . $context );
		$cached_id   = self::cached_id( $user, $fingerprint );
		if ( is_string( $cached_id ) ) {
			return rest_ensure_response( array( 'id' => $cached_id ) );
		}
		$lock     = 'hs_editor_ai_lock_' . $user;
		$existing = get_option( $lock );
		if ( is_array( $existing ) && (int) ( $existing['expires'] ?? 0 ) > time() ) {
			if ( ( $existing['fingerprint'] ?? '' ) === $fingerprint && is_string( $existing['id'] ?? null ) ) {
				return rest_ensure_response( array( 'id' => $existing['id'] ) );
			}
			return new \WP_Error( 'editor_ai_busy', __( 'Дождитесь завершения предыдущего запроса.', 'manacost' ), array( 'status' => 429 ) );
		}
		if ( is_array( $existing ) ) {
			self::release( $lock, $existing );
		}
		$id = str_replace( '-', '', wp_generate_uuid4() );
		if ( ! add_option(
			$lock,
			array(
				'id'          => $id,
				'fingerprint' => $fingerprint,
				'expires'     => time() + 300,
			),
			'',
			false
		) ) {
			return new \WP_Error( 'editor_ai_busy', __( 'Запрос уже запускается.', 'manacost' ), array( 'status' => 429 ) );
		}
		if ( ! self::budget( $user ) ) {
			delete_option( $lock );
			return new \WP_Error( 'editor_ai_rate', __( 'Лимит: 20 обработок в час. Попробуйте позже.', 'manacost' ), array( 'status' => 429 ) );
		}
		$job = array(
			'user'    => $user,
			'post_id' => $post_id,
			'source'  => $source,
			'context' => wp_strip_all_tags( $context ),
			'mode'    => $mode,
			'state'   => 'queued',
			'created' => time(),
		);
		set_transient( 'hs_editor_ai_' . $id, $job, self::TTL );
		set_transient( 'hs_editor_ai_fp_' . $user . '_' . $fingerprint, $id, self::TTL );
		if ( ! wp_schedule_single_event( time() + 310, 'hs_editor_ai_expire', array( $id ) ) || ! wp_schedule_single_event( time(), 'hs_editor_ai_process', array( $id ) ) ) {
			self::finish( $id, $job, new \WP_Error( 'editor_ai_queue', __( 'Не удалось запустить обработку. Попробуйте позже.', 'manacost' ) ) );
			return new \WP_Error( 'editor_ai_queue', __( 'Очередь временно недоступна.', 'manacost' ), array( 'status' => 503 ) );
		}
		return rest_ensure_response( array( 'id' => $id ) );
	}

	/**
	 * Return only this editor's result; source/context never leave the queue.
	 *
	 * @param \WP_REST_Request $request Job lookup.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function status( \WP_REST_Request $request ) {
		$id  = (string) $request['id'];
		$job = get_transient( 'hs_editor_ai_' . $id );
		if ( ! is_array( $job ) || ( $job['user'] ?? 0 ) !== get_current_user_id() || ! current_user_can( 'edit_post', (int) ( $job['post_id'] ?? 0 ) ) ) {
			return new \WP_Error( 'editor_ai_missing', __( 'Результат недоступен или срок его хранения истёк.', 'manacost' ), array( 'status' => 404 ) );
		}
		if ( in_array( $job['state'], array( 'queued', 'running' ), true ) && time() - $job['created'] > 300 ) {
			self::finish( $id, $job, new \WP_Error( 'editor_ai_timeout', __( 'Обработка не завершилась. Исходный текст сохранён; проверьте очередь WP-Cron.', 'manacost' ) ) );
			$job = get_transient( 'hs_editor_ai_' . $id );
		}
		if ( ! is_array( $job ) ) {
			return new \WP_Error( 'editor_ai_missing', __( 'Результат истёк.', 'manacost' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response(
			array(
				'state'  => $job['state'],
				'phase'  => $job['phase'] ?? $job['state'],
				'result' => $job['result'] ?? '',
				'error'  => $job['error'] ?? '',
			)
		);
	}

	/**
	 * Background worker. A duplicate callback never repeats a model call.
	 *
	 * @param string $id           Opaque job ID.
	 * @param bool   $split_review Limit an HTTP worker to one bounded model call.
	 * @return void
	 */
	public static function process( string $id, bool $split_review = false ): void {
		$job = get_transient( 'hs_editor_ai_' . $id );
		if ( ! is_array( $job ) || 'queued' !== ( $job['state'] ?? '' ) ) {
			return;
		}
		$worker_lock = 'hs_editor_ai_worker_' . $id;
		if ( ! add_option( $worker_lock, time(), '', false ) ) {
			return;
		}
		try {
			// Another worker may have finished between our first read and lock.
			$job = get_transient( 'hs_editor_ai_' . $id );
			if ( ! is_array( $job ) || 'queued' !== ( $job['state'] ?? '' ) ) {
				return;
			}
			if ( ! user_can( (int) $job['user'], 'edit_post', (int) $job['post_id'] ) ) {
				self::finish( $id, $job, new \WP_Error( 'editor_ai_forbidden', __( 'Права на статью изменились.', 'manacost' ) ) );
				return;
			}
			$job['state'] = 'running';
			set_transient( 'hs_editor_ai_' . $id, $job, self::TTL );
			$result = self::generate( $job, $id, $split_review );
			if ( is_array( $result ) ) {
				$job['candidate'] = $result['candidate'];
				$job['state']     = 'queued';
				$job['phase']     = 'review';
				set_transient( 'hs_editor_ai_' . $id, $job, self::TTL );
				if ( ! wp_next_scheduled( 'hs_editor_ai_process', array( $id ) ) && ! wp_schedule_single_event( time(), 'hs_editor_ai_process', array( $id ) ) ) {
					self::finish( $id, $job, new \WP_Error( 'editor_ai_queue', __( 'Проверка временно недоступна. Исходный текст сохранён.', 'manacost' ) ) );
				}
				return;
			}
			self::finish( $id, $job, $result );
		} catch ( \Throwable $exception ) {
			self::finish( $id, $job, new \WP_Error( 'editor_ai_worker', __( 'Обработка остановлена. Исходный текст сохранён.', 'manacost' ) ) );
		} finally {
			delete_option( $worker_lock );
		}
	}

	/**
	 * Generate validated suggestions and independently review full editing.
	 *
	 * @param array<string,mixed> $job Authorized worker state.
	 * @param string              $id Job identity for review progress.
	 * @param bool                $split_review Defer review to a separate request.
	 * @return string|array{candidate:string}|\WP_Error
	 */
	private static function generate( array $job, string $id, bool $split_review ) {
		$source = (string) $job['source'];
		if ( 'review' === ( $job['phase'] ?? '' ) && is_string( $job['candidate'] ?? null ) ) {
			return self::review( $source, $job['candidate'] );
		}
		$mode  = (string) $job['mode'];
		$cards = 'cards' === $mode ? Catalog::candidates( $source, Catalog::all() ) : array();
		if ( 'cards' === $mode && ! $cards ) {
			return new \WP_Error( 'editor_ai_catalog', __( 'В словарях сайта не найдены подходящие карты. Уточните их названия в выделении.', 'manacost' ) );
		}
		$response = Provider::complete(
			Prompts::editing( $mode ),
			array(
				'segments' => Content::segments( $source ),
				'context'  => $job['context'],
				'catalog'  => $cards,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( ! is_array( $response['edits'] ?? null ) || ! array_is_list( $response['edits'] ) ) {
			return new \WP_Error( 'editor_ai_response', __( 'Неверный формат предложений.', 'manacost' ) );
		}
		$result = Content::apply( $source, $response['edits'], $mode, $cards );
		if ( is_wp_error( $result ) || 'editor' !== $mode || $source === $result ) {
			return $result;
		}
		if ( $split_review ) {
			return array( 'candidate' => $result );
		}
		$job['phase'] = 'review';
		set_transient( 'hs_editor_ai_' . $id, $job, self::TTL );
		return self::review( $source, $result );
	}

	/**
	 * Independently verify a validated candidate; no repeated primary generation.
	 *
	 * @param string $source Original article.
	 * @param string $result Validated, private candidate.
	 * @return string|\WP_Error
	 */
	private static function review( string $source, string $result ) {
		$review = Provider::complete(
			Prompts::review(),
			array(
				'original'  => wp_strip_all_tags( $source ),
				'candidate' => wp_strip_all_tags( $result ),
			)
		);
		if ( is_wp_error( $review ) ) {
			return $review;
		}
		return true === ( $review['approved'] ?? false ) ? $result : new \WP_Error( 'editor_ai_review', __( 'Независимая проверка не подтвердила сохранность смысла. Исходный текст сохранён.', 'manacost' ) );
	}

	/**
	 * Replace payload with an expiring result and release the editor slot.
	 *
	 * @param string              $id Job identity.
	 * @param array<string,mixed> $job Current state.
	 * @param string|\WP_Error    $result Validated output.
	 * @return void
	 */
	private static function finish( string $id, array $job, $result ): void {
		$current = get_transient( 'hs_editor_ai_' . $id );
		if ( ! is_array( $current ) || ! in_array( $current['state'] ?? '', array( 'queued', 'running' ), true ) ) {
			return;
		}
		unset( $job['source'], $job['context'], $job['candidate'] );
		$job['state']  = is_wp_error( $result ) ? 'failed' : 'ready';
		$job['error']  = is_wp_error( $result ) ? $result->get_error_message() : '';
		$job['result'] = is_string( $result ) ? $result : '';
		set_transient( 'hs_editor_ai_' . $id, $job, self::TTL );
		do_action( 'hs_editor_ai_outcome', $id, $job['mode'], $job['state'], is_wp_error( $result ) ? $result->get_error_code() : 'ok', time() - (int) $job['created'] );
		$lock     = 'hs_editor_ai_lock_' . $job['user'];
		$existing = get_option( $lock );
		if ( is_array( $existing ) && ( $existing['id'] ?? '' ) === $id ) {
			self::release( $lock, $existing );
		}
	}

	/**
	 * Reuse only a live operation or an already checked result.
	 *
	 * @param int    $user Owner.
	 * @param string $fingerprint Operation hash.
	 * @return string|false
	 */
	private static function cached_id( int $user, string $fingerprint ) {
		$id  = get_transient( 'hs_editor_ai_fp_' . $user . '_' . $fingerprint );
		$job = is_string( $id ) ? get_transient( 'hs_editor_ai_' . $id ) : false;
		if ( ! is_string( $id ) || ! is_array( $job ) ) {
			return false;
		}
		if ( 'ready' === ( $job['state'] ?? '' ) ) {
			return $id;
		}
		return time() - (int) ( $job['created'] ?? 0 ) <= 300 && in_array( $job['state'] ?? '', array( 'queued', 'running' ), true ) ? $id : false;
	}

	/**
	 * Independently validate the worker input, including direct callers.
	 *
	 * @param mixed $source Original text.
	 * @param mixed $context Article context.
	 * @param mixed $mode Operation.
	 * @return bool
	 */
	private static function valid_input( $source, $context, $mode ): bool {
		return is_string( $source ) && is_string( $context ) && is_string( $mode )
			&& in_array( $mode, array( 'cards', 'editor', 'proofread' ), true )
			&& '' !== trim( $source ) && strlen( $source ) <= 160000 && strlen( $context ) <= 200000;
	}

	/**
	 * Expire abandoned queue payloads even when the editor closes the tab.
	 *
	 * @param string $id Job identity.
	 * @return void
	 */
	public static function expire( string $id ): void {
		$job = get_transient( 'hs_editor_ai_' . $id );
		if ( is_array( $job ) && in_array( $job['state'] ?? '', array( 'queued', 'running' ), true ) ) {
			self::finish( $id, $job, new \WP_Error( 'editor_ai_timeout', __( 'Срок обработки истёк. Исходный текст сохранён.', 'manacost' ) ) );
		}
		delete_option( 'hs_editor_ai_worker_' . $id );
	}

	/**
	 * Consume a per-user hourly budget while holding the user's atomic lease.
	 *
	 * @param int $user Editor identity.
	 * @return bool
	 */
	private static function budget( int $user ): bool {
		$name  = 'hs_editor_ai_budget_' . $user;
		$hour  = (int) floor( time() / HOUR_IN_SECONDS );
		$value = get_option( $name );
		$count = is_array( $value ) && ( $value['hour'] ?? 0 ) === $hour ? (int) ( $value['count'] ?? 0 ) : 0;
		if ( $count >= 20 ) {
			return false;
		}
		update_option(
			$name,
			array(
				'hour'  => $hour,
				'count' => $count + 1,
			),
			false
		);
		return true;
	}

	/**
	 * Compare-and-delete so an expired lease cannot remove a newer request.
	 *
	 * @param string              $name Lock option name.
	 * @param array<string,mixed> $expected Previously read lease.
	 * @return void
	 */
	private static function release( string $name, array $expected ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Atomic compare-and-delete cannot use delete_option().
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, maybe_serialize( $expected ) ) );
		wp_cache_delete( $name, 'options' );
	}
}
