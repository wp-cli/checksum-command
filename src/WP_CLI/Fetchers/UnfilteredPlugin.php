<?php

namespace WP_CLI\Fetchers;

/**
 * Fetch a WordPress plugin based on one of its attributes.
 *
 * This is a special version of the plugin fetcher. It doesn't use the
 * `all_plugins` filter, so that plugins cannot hide themselves from the
 * checks.
 *
 * @extends Base<object{name: string, file: string}>
 */
class UnfilteredPlugin extends Base {

	/**
	 * @var string $msg Error message to use when invalid data is provided
	 */
	protected $msg = "The '%s' plugin could not be found.";

	/**
	 * Get a plugin object by name.
	 *
	 * @param string|int $name
	 *
	 * @return object{name: string, file: string}|false
	 */
	public function get( $name ) {
		$name = (string) $name;
 		// First, check plugins detected by get_plugins()
		foreach ( get_plugins() as $file => $_ ) {
			if ( "{$name}.php" === $file ||
				( $name && $file === $name ) ||
				( dirname( $file ) === $name && '.' !== $name ) ) {
				return (object) compact( 'name', 'file' );
			}
		}

		// If not found, check if a directory with this name exists
		// This handles cases where the main plugin file is missing
		$plugin_dir = WP_PLUGIN_DIR . '/' . $name;

		// Resolve real paths to protect against path traversal and symlinks.
		$wp_plugin_dir_real = realpath( WP_PLUGIN_DIR );
		$plugin_dir_real    = realpath( $plugin_dir );

		if ( false !== $wp_plugin_dir_real
			&& false !== $plugin_dir_real
			&& is_dir( $plugin_dir_real )
			&& ! is_link( $plugin_dir_real )
			&& ( $plugin_dir_real === $wp_plugin_dir_real
				|| 0 === strpos( $plugin_dir_real, $wp_plugin_dir_real . DIRECTORY_SEPARATOR ) )
		) {
			// Use the conventional main file name, even if it doesn't exist
			// The checksum verification will handle missing files appropriately
			$file = $name . '/' . $name . '.php';
			return (object) compact( 'name', 'file' );
		}

		return false;
	}
}
