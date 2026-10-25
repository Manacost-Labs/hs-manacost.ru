<?php
/**
 * Official Anthropic Messages API; credentials never enter editor payloads.
 *
 * @package Manacost
 */

namespace Manacost\EditorAi;

defined( 'ABSPATH' ) || exit;

/** Fixed-host, bounded and redacted Anthropic transport. */
final class Provider {
	/**
	 * Resolve the runtime-only credential.
	 *
	 * @return string
	 */
	private static function key(): string {
		$key = getenv( 'HS_EDITOR_AI_API_KEY' );
		if ( is_string( $key ) && '' !== trim( $key ) ) {
			return trim( $key );
		}
		$path = defined( 'HS_EDITOR_AI_KEY_FILE' ) ? HS_EDITOR_AI_KEY_FILE : '/etc/hs-manacost/editor-ai.key';
		if ( ! is_string( $path ) || ! is_readable( $path ) ) {
			return '';
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local protected credential, never a URL.
		$key = file_get_contents( $path );
		return is_string( $key ) ? trim( $key ) : '';
	}

	/**
	 * Feature availability, without returning the credential.
	 *
	 * @return bool
	 */
	public static function enabled(): bool {
		return ( ! defined( 'HS_EDITOR_AI_ENABLED' ) || HS_EDITOR_AI_ENABLED ) && '' !== self::key();
	}

	/**
	 * One Messages API call. No automatic retry of a billable request.
	 *
	 * @param string              $system Fixed operation instructions.
	 * @param array<string,mixed> $input Untrusted article data.
	 * @param bool                $review Whether this is the independent review contract.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function complete( string $system, array $input, bool $review = false ) {
		if ( ! self::enabled() ) {
			return new \WP_Error( 'editor_ai_disabled', __( 'ИИ отключён или серверный API-ключ не настроен.', 'manacost' ) );
		}
		if ( get_transient( 'hs_editor_ai_api_cooldown' ) ) {
			return new \WP_Error( 'editor_ai_rate', __( 'Лимит Anthropic временно исчерпан. Попробуйте позже.', 'manacost' ) );
		}
		$encoded_input = wp_json_encode( $input, JSON_UNESCAPED_UNICODE );
		if ( ! is_string( $encoded_input ) ) {
			return self::invalid();
		}
		$body         = array(
			'model'         => 'claude-opus-5-5',
			'max_tokens'    => 16000,
			'output_config' => array(
				'effort' => 'low',
				'format' => array(
					'type'   => 'json_schema',
					'schema' => self::schema( $review ),
				),
			),
			'system'        => $system,
			'messages'      => array(
				array(
					'role'    => 'user',
					'content' => $encoded_input,
				),
			),
		);
		$encoded_body = wp_json_encode( $body, JSON_UNESCAPED_UNICODE );
		if ( ! is_string( $encoded_body ) ) {
			return self::invalid();
		}
		$response = wp_safe_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout'             => 75,
				'redirection'         => 0,
				'sslverify'           => true,
				'limit_response_size' => 1048576,
				'headers'             => array(
					'Authorization'     => 'Bearer ' . self::key(),
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				'body'                => $encoded_body,
			)
		);
		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'editor_ai_transport', __( 'Anthropic не ответил вовремя. Текст не изменён; повторный запрос запускается вручную.', 'manacost' ) );
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( 429 === $status ) {
			$retry = (int) wp_remote_retrieve_header( $response, 'retry-after' );
			set_transient( 'hs_editor_ai_api_cooldown', true, min( 3600, max( 60, $retry ) ) );
		}
		if ( 200 !== $status ) {
			return new \WP_Error( 'editor_ai_http_' . $status, __( 'Anthropic отклонил запрос. Проверьте доступ к модели, баланс и лимиты API.', 'manacost' ) );
		}
		return self::decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Constrain generation at the provider; local content guards remain mandatory.
	 *
	 * @param bool $review Independent review instead of editing.
	 * @return array<string,mixed> Supported Anthropic JSON schema.
	 */
	private static function schema( bool $review ): array {
		$properties = $review ? array( 'approved' => array( 'type' => 'boolean' ) ) : array(
			'edits' => array(
				'type'  => 'array',
				'items' => array(
					'type'                 => 'object',
					'properties'           => array(
						'segment'     => array( 'type' => 'integer' ),
						'replacement' => array( 'type' => 'string' ),
					),
					'required'             => array( 'segment', 'replacement' ),
					'additionalProperties' => false,
				),
			),
		);
		return array(
			'type'                 => 'object',
			'properties'           => $properties,
			'required'             => array_keys( $properties ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Parse complete text blocks, excluding internal thinking.
	 *
	 * @param string $body Provider body.
	 * @return array<string,mixed>|\WP_Error
	 */
	private static function decode( string $body ) {
		$decoded = json_decode( $body, true );
		if ( ! is_array( $decoded ) || 'end_turn' !== ( $decoded['stop_reason'] ?? '' ) || ! is_array( $decoded['content'] ?? null ) ) {
			return self::invalid();
		}
		$text = '';
		foreach ( $decoded['content'] as $block ) {
			if ( is_array( $block ) && 'text' === ( $block['type'] ?? '' ) && is_string( $block['text'] ?? null ) ) {
				$text .= $block['text'];
			}
		}
		$text   = preg_replace( '~^```(?:json)?\s*|\s*```$~', '', trim( $text ) );
		$result = json_decode( (string) $text, true );
		return is_array( $result ) ? $result : self::invalid();
	}

	/**
	 * Redacted response failure.
	 *
	 * @return \WP_Error
	 */
	private static function invalid(): \WP_Error {
		return new \WP_Error( 'editor_ai_response', __( 'Ответ ИИ неполный или имеет неверный формат. Исходный текст сохранён.', 'manacost' ) );
	}
}
