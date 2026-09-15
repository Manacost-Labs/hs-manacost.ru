<?php
/**
 * PHP bytecode invalidation policy, separate from content cache invalidation.
 *
 * @package ManacostCachePurge
 */

namespace Manacost\CachePurge;

defined( 'ABSPATH' ) || exit;

/**
 * Reserved post lifecycle source families do not change PHP files.
 *
 * These families are internal content-event origins, not arbitrary operation
 * names. Code/configuration changes must use ci_deploy or another full-purge
 * origin outside the reserved prefixes. Other source families stay full.
 *
 * @param string $source Recorded purge origin, including the automatic prefix.
 * @return bool Whether the existing full bytecode reset should run.
 */
function should_reset_opcache( string $source ): bool {
	return 1 !== preg_match(
		'/^auto:(?:(?:content_|updated_|status_|after_update_|after_publish_|delete_).+|newspaper_theme_options|deferred_change)$/D',
		$source
	);
}

/**
 * Preserve the existing bytecode reset for manual, deployment and other purges.
 *
 * @return string Existing diagnostic message for the full reset step.
 */
function reset_opcache(): string {
	if ( function_exists( 'opcache_reset' ) ) {
		opcache_reset();
		return 'opcache_reset';
	}

	return 'not available';
}
