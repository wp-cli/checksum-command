<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

WP_CLI::add_hook( 'after_add_command:core', function () {
	WP_CLI::add_command( 'core verify-checksums', 'Checksum_Core_Command' );
} );

WP_CLI::add_hook( 'after_add_command:plugin', function () {
	WP_CLI::add_command( 'plugin verify-checksums', 'Checksum_Plugin_Command' );
} );
