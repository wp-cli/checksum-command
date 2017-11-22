<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

WP_CLI::add_command( 'checksum core', 'Checksum_Core_Command' );
WP_CLI::add_command( 'checksum plugin', 'Checksum_Plugin_Command' );
if ( class_exists( 'WP_CLI\Dispatcher\CommandNamespace' ) ) {
	WP_CLI::add_command( 'checksum', 'Checksum_Namespace' );
}
