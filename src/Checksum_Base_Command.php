<?php

use \WP_CLI\Utils;

/**
 * Base command that all checksum commands rely on.
 *
 * @package wp-cli
 */
class Checksum_Base_Command extends WP_CLI_Command {

	protected static function _read( $url ) {
		$headers = array('Accept' => 'application/json');
		$response = Utils\http_request( 'GET', $url, null, $headers, array( 'timeout' => 30 ) );
		if ( 200 === $response->status_code ) {
			return $response->body;
		} else {
			WP_CLI::error( "Couldn't fetch response from {$url} (HTTP code {$response->status_code})." );
		}
	}
}
