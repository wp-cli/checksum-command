<?php

use WP_CLI\Fetchers;
use WP_CLI\Formatter;
use WP_CLI\Utils;
use WP_CLI\WpOrgApi;

/**
 * Verifies plugin file integrity by comparing to published checksums.
 *
 * @package wp-cli
 */
class Checksum_Plugin_Command extends Checksum_Base_Command {

	/**
	 * Cached plugin data for all installed plugins.
	 *
	 * @var array|null
	 */
	private $plugins_data;

	/**
	 * Array of detected errors.
	 *
	 * @var array
	 */
	private $errors = array();

	/**
	 * Verifies plugin files against WordPress.org's checksums.
	 *
	 * ## OPTIONS
	 *
	 * [<plugin>...]
	 * : One or more plugins to verify.
	 *
	 * [--all]
	 * : If set, all plugins will be verified.
	 *
	 * [--strict]
	 * : If set, even "soft changes" like readme.txt changes will trigger
	 * checksum errors.
	 *
	 * [--version=<version>]
	 * : Verify checksums against a specific plugin version.
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
	 *
	 * [--insecure]
	 * : Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
	 *
	 * [--exclude=<name>]
	 * : Comma separated list of plugin names that should be excluded from verifying.
	 *
	 * [--exclude-mu-plugins]
	 * : Exclude must-use plugins from verification.
	 *
	 * ## EXAMPLES
	 *
	 *     # Verify the checksums of all installed plugins
	 *     $ wp plugin verify-checksums --all
	 *     Success: Verified 8 of 8 plugins.
	 *
	 *     # Verify the checksums of a single plugin, Akismet in this case
	 *     $ wp plugin verify-checksums akismet
	 *     Success: Verified 1 of 1 plugins.
	 */
	public function __invoke( $args, $assoc_args ) {

		$fetcher    = new Fetchers\UnfilteredPlugin();
		$all        = Utils\get_flag_value( $assoc_args, 'all', false );
		$strict     = Utils\get_flag_value( $assoc_args, 'strict', false );
		$insecure   = Utils\get_flag_value( $assoc_args, 'insecure', false );
		$exclude_mu = Utils\get_flag_value( $assoc_args, 'exclude-mu-plugins', false );
		$plugins    = $fetcher->get_many( $all ? $this->get_all_plugin_names() : $args );

		/**
		 * @var string $exclude
		 */
		$exclude     = Utils\get_flag_value( $assoc_args, 'exclude', '' );
		$version_arg = isset( $assoc_args['version'] ) ? $assoc_args['version'] : '';

		if ( empty( $plugins ) && ! $all ) {
			WP_CLI::error( 'You need to specify either one or more plugin slugs to check or use the --all flag to check all plugins.' );
		}

		$exclude_list = explode( ',', $exclude );

		$skips = 0;

		foreach ( $plugins as $plugin ) {
			$version = empty( $version_arg ) ? $this->get_plugin_version( $plugin->file ) : $version_arg;

			if ( in_array( $plugin->name, $exclude_list, true ) ) {
				++$skips;
				continue;
			}

			if ( 'hello' === $plugin->name ) {
				$this->verify_hello_dolly_from_core( $assoc_args );
				continue;
			}

			if ( false === $version ) {
				WP_CLI::warning( "Could not retrieve the version for plugin {$plugin->name}, skipping." );
				++$skips;
				continue;
			}

			$wp_org_api = new WpOrgApi( [ 'insecure' => $insecure ] );

			try {
				/**
				 * @var array|false $checksums
				 */
				$checksums = $wp_org_api->get_plugin_checksums( $plugin->name, $version );
			} catch ( Exception $exception ) {
				WP_CLI::warning( $exception->getMessage() );
				$checksums = false;
			}

			if ( false === $checksums ) {
				WP_CLI::warning( "Could not retrieve the checksums for version {$version} of plugin {$plugin->name}, skipping." );
				++$skips;
				continue;
			}

			$files = $this->get_plugin_files( $plugin->file );

			foreach ( $files as $file ) {
				if ( ! array_key_exists( $file, $checksums ) ) {
					$this->add_error( $plugin->name, $file, 'File was added' );
					continue;
				}

				if ( ! $strict && $this->is_soft_change_file( $file ) ) {
					continue;
				}
				$result = $this->check_file_checksum( dirname( $plugin->file ) . '/' . $file, $checksums[ $file ] );
				if ( true !== $result ) {
					$this->add_error( $plugin->name, $file, is_string( $result ) ? $result : 'Checksum does not match' );
				}
			}
		}

		// Process must-use plugins if not excluded.
		$mu_plugins = array();
		if ( ! $exclude_mu ) {
			$mu_plugins = get_mu_plugins();

			foreach ( $mu_plugins as $mu_file => $mu_plugin ) {
				$plugin_name = $this->get_plugin_slug_from_path( $mu_file );

				if ( in_array( $plugin_name, $exclude_list, true ) ) {
					++$skips;
					continue;
				}

				$this->verify_mu_plugin( $mu_file, $mu_plugin, $plugin_name, $version_arg, $insecure, $strict, $skips );
			}
		}

		if ( ! empty( $this->errors ) ) {
			$formatter = new Formatter(
				$assoc_args,
				array( 'plugin_name', 'file', 'message' )
			);
			$formatter->display_items( $this->errors );
		}

		$total     = count( $plugins ) + count( $mu_plugins );
		$failures  = count( array_unique( array_column( $this->errors, 'plugin_name' ) ) );
		$successes = $total - $failures - $skips;

		Utils\report_batch_operation_results(
			'plugin',
			'verify',
			$total,
			$successes,
			$failures,
			$skips
		);
	}

	private function verify_hello_dolly_from_core( $assoc_args ) {
		$file       = 'hello.php';
		$wp_version = get_bloginfo( 'version', 'display' );
		$insecure   = Utils\get_flag_value( $assoc_args, 'insecure', false );
		$wp_org_api = new WpOrgApi( [ 'insecure' => $insecure ] );
		$locale     = 'en_US';

		try {
			$checksums = $wp_org_api->get_core_checksums( $wp_version, $locale );
		} catch ( Exception $exception ) {
			WP_CLI::error( $exception );
		}

		if ( ! is_array( $checksums ) || ! isset( $checksums['wp-content/plugins/hello.php'] ) ) {
			WP_CLI::error( "Couldn't get hello.php checksum from WordPress.org." );
		}

		$md5_file = md5_file( $this->get_absolute_path( '/' ) . $file );
		if ( $md5_file !== $checksums['wp-content/plugins/hello.php'] ) {
			$this->add_error( 'hello', $file, 'Checksum does not match' );
		}
	}

	/**
	 * Adds a new error to the array of detected errors.
	 *
	 * @param string $plugin_name Name of the plugin that had the error.
	 * @param string $file Relative path to the file that had the error.
	 * @param string $message Message explaining the error.
	 */
	private function add_error( $plugin_name, $file, $message ) {
		$error['plugin_name'] = $plugin_name;
		$error['file']        = $file;
		$error['message']     = $message;
		$this->errors[]       = $error;
	}

	/**
	 * Gets the currently installed version for a given plugin.
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
	 * Gets the names of all installed plugins.
	 *
	 * @return array<string> Names of all installed plugins.
	 */
	private function get_all_plugin_names() {
		$names = array();
		foreach ( get_plugins() as $file => $details ) {
			$names[] = Utils\get_plugin_name( $file );
		}

		return $names;
	}

	/**
	 * Gets the list of files that are part of the given plugin.
	 *
	 * @param string $path Relative path to the main plugin file.
	 *
	 * @return array<string> Array of files with their relative paths.
	 */
	private function get_plugin_files( $path ) {
		$folder = dirname( $this->get_absolute_path( $path ) );

		// Return single file plugins immediately, to avoid iterating over the
		// entire plugins folder.
		if ( WP_PLUGIN_DIR === $folder ) {
			return (array) $path;
		}

		return $this->get_files( trailingslashit( $folder ) );
	}

	/**
	 * Checks the integrity of a single plugin file by comparing it to the
	 * officially provided checksum.
	 *
	 * @param string $path      Relative path to the plugin file to check the
	 *                          integrity of.
	 * @param array  $checksums Array of provided checksums to compare against.
	 * @param string $base_dir  Optional. Base directory for the plugin. Defaults to WP_PLUGIN_DIR.
	 *
	 * @return bool|string
	 */
	private function check_file_checksum( $path, $checksums, $base_dir = null ) {
		if ( $this->supports_sha256()
			&& array_key_exists( 'sha256', $checksums )
		) {
			$sha256 = $this->get_sha256( $this->get_absolute_path( $path, $base_dir ) );
			return in_array( $sha256, (array) $checksums['sha256'], true );
		}

		if ( ! array_key_exists( 'md5', $checksums ) ) {
			return 'No matching checksum algorithm found';
		}

		$md5 = $this->get_md5( $this->get_absolute_path( $path, $base_dir ) );

		return in_array( $md5, (array) $checksums['md5'], true );
	}

	/**
	 * Checks whether the current environment supports 256-bit SHA-2.
	 *
	 * Should be supported for PHP 5+, but we might find edge cases depending on
	 * host.
	 *
	 * @return bool
	 */
	private function supports_sha256() {
		return true;
	}

	/**
	 * Gets the 256-bit SHA-2 of a given file.
	 *
	 * @param string $filepath Absolute path to the file to calculate the SHA-2
	 *                         for.
	 *
	 * @return string|false
	 */
	private function get_sha256( $filepath ) {
		return hash_file( 'sha256', $filepath );
	}

	/**
	 * Gets the MD5 of a given file.
	 *
	 * @param string $filepath Absolute path to the file to calculate the MD5
	 *                         for.
	 *
	 * @return string|false
	 */
	private function get_md5( $filepath ) {
		return hash_file( 'md5', $filepath );
	}

	/**
	 * Gets the absolute path to a relative plugin file.
	 *
	 * @param string $path     Relative path to get the absolute path for.
	 * @param string $base_dir Optional. Base directory to prepend. Defaults to WP_PLUGIN_DIR.
	 *
	 * @return string
	 */
	private function get_absolute_path( $path, $base_dir = null ) {
		if ( null === $base_dir ) {
			$base_dir = WP_PLUGIN_DIR;
		}
		return $base_dir . '/' . $path;
	}

	/**
	 * Returns a list of files that only trigger checksum errors in strict mode.
	 *
	 * @return array<string> Array of file names.
	 */
	private function get_soft_change_files() {
		static $files = array(
			'readme.txt',
			'readme.md',
		);

		return $files;
	}

	/**
	 * Checks whether a given file will only trigger checksum errors in strict
	 * mode.
	 *
	 * @param string $file File to check.
	 *
	 * @return bool Whether the file only triggers checksum errors in strict
	 * mode.
	 */
	private function is_soft_change_file( $file ) {
		return in_array( strtolower( $file ), $this->get_soft_change_files(), true );
	}

	/**
	 * Extracts the plugin slug from the plugin file path.
	 *
	 * For MU plugins that are actually standard plugins moved to mu-plugins folder,
	 * we extract the plugin slug from the file path to look up checksums.
	 *
	 * @param string $plugin_file Path to the plugin file.
	 *
	 * @return string Plugin slug.
	 */
	private function get_plugin_slug_from_path( $plugin_file ) {
		// If it's in a subdirectory, use the directory name as slug.
		if ( false !== strpos( $plugin_file, '/' ) ) {
			return dirname( $plugin_file );
		}

		// For single files, extract the slug from the filename.
		return basename( $plugin_file, '.php' );
	}

	/**
	 * Gets the version for a plugin from its header data or the version argument.
	 *
	 * @param string $version_arg Version argument from command line.
	 * @param array  $plugin_data Plugin header data.
	 *
	 * @return string|false Plugin version, or false if not found.
	 */
	private function get_plugin_version_for_verification( $version_arg, $plugin_data ) {
		if ( ! empty( $version_arg ) ) {
			return $version_arg;
		}

		if ( ! empty( $plugin_data['Version'] ) ) {
			return $plugin_data['Version'];
		}

		return false;
	}

	/**
	 * Verifies a must-use plugin against WordPress.org checksums.
	 *
	 * @param string $mu_file     Path to the MU plugin file.
	 * @param array  $mu_plugin   Plugin header data.
	 * @param string $plugin_name Plugin slug/name.
	 * @param string $version_arg Version to verify against (if specified).
	 * @param bool   $insecure    Whether to allow insecure connections.
	 * @param bool   $strict      Whether to check soft change files.
	 * @param int    &$skips      Reference to skip counter.
	 */
	private function verify_mu_plugin( $mu_file, $mu_plugin, $plugin_name, $version_arg, $insecure, $strict, &$skips ) {
		$is_single_file = false === strpos( $mu_file, '/' );

		// Get version from the plugin header.
		$version = $this->get_plugin_version_for_verification( $version_arg, $mu_plugin );

		if ( false === $version ) {
			WP_CLI::warning( "Could not retrieve the version for must-use plugin {$plugin_name}, skipping." );
			++$skips;
			return;
		}

		$wp_org_api = new WpOrgApi( [ 'insecure' => $insecure ] );

		try {
			/**
			 * @var array|false $checksums
			 */
			$checksums = $wp_org_api->get_plugin_checksums( $plugin_name, $version );
			if ( false === $checksums ) {
				throw new Exception( "Could not retrieve the checksums for version {$version} of must-use plugin {$plugin_name}, skipping." );
			}
		} catch ( Exception $exception ) {
			// If it's a single file or we can't get checksums, warn the user.
			if ( $is_single_file ) {
				WP_CLI::warning( "Must-use plugin '{$mu_file}' appears to be a custom file or loader plugin and cannot be verified." );
			} else {
				WP_CLI::warning( $exception->getMessage() );
			}
			++$skips;
			return;
		}

		$files = $this->get_mu_plugin_files( $mu_file );

		foreach ( $files as $file ) {
			if ( ! array_key_exists( $file, $checksums ) ) {
				$this->add_error( $plugin_name, $file, 'File was added' );
				continue;
			}

			if ( ! $strict && $this->is_soft_change_file( $file ) ) {
				continue;
			}

			// Build the relative path for MU plugins.
			$relative_path = $is_single_file ? $file : dirname( $mu_file ) . '/' . $file;

			$result = $this->check_file_checksum( $relative_path, $checksums[ $file ], WPMU_PLUGIN_DIR );
			if ( true !== $result ) {
				$this->add_error( $plugin_name, $file, is_string( $result ) ? $result : 'Checksum does not match' );
			}
		}
	}

	/**
	 * Gets the list of files that are part of the given MU plugin.
	 *
	 * @param string $mu_file Path to the main MU plugin file.
	 *
	 * @return array<string> Array of files with their relative paths.
	 */
	private function get_mu_plugin_files( $mu_file ) {
		// If it's a single file in the root of mu-plugins, return just that file.
		if ( false === strpos( $mu_file, '/' ) ) {
			return array( basename( $mu_file ) );
		}

		// If it's in a subdirectory, get all files in that directory.
		$folder = WPMU_PLUGIN_DIR . '/' . dirname( $mu_file );
		return $this->get_files( trailingslashit( $folder ) );
	}
}
