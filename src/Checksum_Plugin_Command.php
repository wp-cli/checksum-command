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
	 * Cached plugin data for all installed plugins.
	 *
	 * @var array|null
	 */
	private $plugins_data;

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
	 *
	 * [--format=<format>]
	 * : Render output in a specific format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 */
	public function __invoke( $args, $assoc_args ) {

		$fetcher = new \WP_CLI\Fetchers\Plugin();
		$plugins = $fetcher->get_many( $args );
		$all     = \WP_CLI\Utils\get_flag_value( $assoc_args, 'all', false );

		if ( empty( $plugins ) && ! $all ) {
			WP_CLI::error( 'You need to specify either one or more plugin slugs to check or use the --all flag to check all plugins.' );
		}

		$errors = array();

		foreach ( $plugins as $plugin ) {
			$version = $this->get_plugin_version( $plugin->file );

			if ( false === $version ) {
				WP_CLI::warning( "Could not retrieve the version for plugin {$plugin->name}, skipping." );
				continue;
			}

			$checksums = $this->get_plugin_checksums( $plugin->name, $version );

			if ( false === $checksums ) {
				WP_CLI::warning( "Could not retrieve the checksums for plugin {$plugin->name}, skipping." );
				continue;
			}

			$files = $this->get_plugin_files( $plugin->file );

			foreach ( $checksums as $file => $checksum_array ) {
				if ( ! array_key_exists( $file, $files ) ) {
					$error['plugin_name'] = $plugin->name;
					$error['file'] = $file;
					$error['message'] = 'File is missing';
					$errors[] = $error;
				}
			}

			foreach ( $files as $file ) {
				$result = $this->check_file_checksum( $file, $checksums );
				if ( true !== $result ) {
					$error['plugin_name'] = $plugin->name;
					$error['file'] = $file;
					$error['message'] = $result;
					$errors[] = $error;
				}
			}
		}

		if ( empty( $errors ) ) {
			WP_CLI::success(
				count( $plugins ) > 1
					? 'Plugins verify against checksums.'
					: 'Plugin verifies against checksums.'
			);
		} else {
			$formatter = new \WP_CLI\Formatter( $assoc_args, array( 'plugin_name', 'file', 'message' ) );
			$formatter->display_items( $errors );

			WP_CLI::error(
				count( $plugins ) > 1
					? 'One or more plugins don\'t verify against checksums.'
					: 'Plugin doesn\'t verify against checksums.'
			);
		}
	}

	/**
	 * Get the currently installed version for a given plugin.
	 *
	 * @param string $path Relative path to plugin file to get the version for.
	 *
	 * @return string|false Installed version of the plugin, or false if not
	 *                      found.
	 */
	private function get_plugin_version( $path ) {
		if ( ! isset( $this->plugins_data ) ) {
			$this->plugins_data = get_plugins();
		}

		if ( ! array_key_exists( $path, $this->plugins_data ) ) {
			return false;
		}

		return $this->plugins_data[ $path ]['Version'];
	}

	/**
	 * Gets the checksums for the given version of plugin.
	 *
	 * @param string $version Version string to query.
	 * @param string $plugin  plugin string to query.
	 *
	 * @return bool|array False on failure. An array of checksums on success.
	 */
	private function get_plugin_checksums( $plugin, $version ) {
		$url = str_replace(
			array(
				'{slug}',
				'{version}',
			),
			array(
				$plugin,
				$version,
			),
			$this->url_template
		);

		$options = array(
			'timeout' => 30,
		);

		$headers  = array(
			'Accept' => 'application/json',
		);
		$response = Utils\http_request( 'GET', $url, null, $headers, $options );

		if ( ! $response->success || 200 !== $response->status_code ) {
			return false;
		}

		$body = trim( $response->body );
		$body = json_decode( $body, true );

		if ( ! is_array( $body ) || ! isset( $body['files'] ) || ! is_array( $body['files'] ) ) {
			return false;
		}

		return $body['files'];
	}

	/**
	 * Get the list of files that are part of the given plugin.
	 *
	 * @param string $path Relative path to the main plugin file.
	 *
	 * @return array<string> Array of files with their relative paths.
	 */
	private function get_plugin_files( $path ) {
		// TODO: Fetch and return the lift of plugin files.
		return array();
	}

	/**
	 * Check the integrity of a single plugin file by comparing it to the
	 * officially provided checksum.
	 *
	 * @param string $path      Relative path to the plugin file to check the
	 *                          integrity of.
	 * @param array  $checksums Array of provided checksums to compare against.
	 *
	 * @return true|string
	 */
	private function check_file_checksum( $path, $checksums ) {
		// TODO: Compute the checksum and compare it to the provided one.
		return true;
	}
}
