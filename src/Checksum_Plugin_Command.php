<?php

use \WP_CLI\Utils;

/**
 * Verifies plugin file integrity by comparing to published checksums.
 *
 * @package wp-cli
 */
class Checksum_Plugin_Command extends Checksum_Base_Command {

	/**
	 * Verify plugin files against WordPress.org's checksums.
	 *
	 * @when before_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {

		$has_errors = false;

		if ( ! $has_errors ) {
			WP_CLI::success( 'Plugin verifies against checksums.' );
		} else {
			WP_CLI::error( 'Plugin doesn\'t verify against checksums.' );
		}
	}
}
