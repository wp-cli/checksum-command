<?php

use \WP_CLI\Utils;

/**
 * Verifies plugin file integrity by comparing to published checksums.
 *
 * @package wp-cli
 */
class Checksum_Plugin_Command extends Checksum_Base_Command {

	/**
	 * URL template that points to the API endpoint to use.
	 *
	 * @var string
	 */
	private $url_template = 'https://downloads.wordpress.org/plugin-checksums/{slug}/{version}.json';

	/**
	 * Verify plugin files against WordPress.org's checksums.
	 *
	 * ## OPTIONS
	 *
	 * [<plugin>...]
	 * : One or more plugins to verify.
	 *
	 * [--all]
	 * : If set, all plugins will be verified.
	 */
	public function __invoke( $args, $assoc_args ) {

		$fetcher = new \WP_CLI\Fetchers\Plugin();
		$plugins = $fetcher->get_many( $args );
		$all = \WP_CLI\Utils\get_flag_value( $assoc_args, 'all', false );

		$has_errors = false;

		// Iterate over plugins
		//  - fetch plugin's checksums from wordpress.org server
		//  - Iterate over plugin's files
		//     - Verify plugin file checksum against downloaded checksum

		if ( ! $has_errors ) {
			WP_CLI::success(
				count( $plugins ) > 1
					? 'Plugins verify against checksums.'
					: 'Plugin verifies against checksums.'
			);
		} else {
			WP_CLI::error(
				count( $plugins ) > 1
					? 'One or more plugins don\'t verify against checksums.'
					: 'Plugin doesn\'t verify against checksums.'
			);
		}
	}

	/**
	 * Gets the checksums for the given version of plugin.
	 *
	 * @param string $version Version string to query.
	 * @param string $plugin plugin string to query.
	 * @return bool|array False on failure. An array of checksums on success.
	 */
	private function get_plugin_checksums( $plugin, $version ) {
		$url = str_replace(
			array(
				'{slug}',
				'{version}'
			),
			array(
				$plugin,
				$version
			),
			$this->url_template
		);

		$options = array(
			'timeout' => 30
		);

		$headers = array(
			'Accept' => 'application/json'
		);
		$response = Utils\http_request( 'GET', $url, null, $headers, $options );

		if ( ! $response->success || 200 != $response->status_code ) {
			return false;
		}

		$body = trim( $response->body );
		$body = json_decode( $body, true );

		if ( ! is_array( $body ) || ! isset( $body['checksums'] ) || ! is_array( $body['checksums'] ) ) {
			return false;
		}

		return $body['checksums'];
	}
}
