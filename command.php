<?php

use WP_CLI\Utils;

/**
 * Generate salts.
 *
 * ## OPTIONS
 *
 * [--wp-api]
 * : Generate from WP API.
 *
 * [--format]
 * : Render output in a particular format. -– default: table options: - table - csv - ids - json - yaml -–.
 */
WP_CLI::add_command('salts-gen', function ($args, $assoc_args) {
	$salts = $items = [];
	$definitions = [
		'AUTH_KEY',
		'SECURE_AUTH_KEY',
		'LOGGED_IN_KEY',
		'NONCE_KEY',
		'AUTH_SALT',
		'SECURE_AUTH_SALT',
		'LOGGED_IN_SALT',
		'NONCE_SALT'
	];

	if (Utils\get_flag_value($assoc_args, 'wp-api')) {
		$api_response = Utils\http_request('get', 'https://api.wordpress.org/secret-key/1.1/salt/');

		if (20 != substr($api_response->status_code, 0, 2)) {
			WP_CLI::error(
				"Couldn't generate salts keys form API (HTTP code {$api_response->status_code}).\n"
				"Fallback to generate localy."
			);
		} else {
			$salts_response = explode("\n", $api_response->body);

			foreach ($definitions as $key) {
				$salts[$key] = substr(array_shift($salts_response), 28, 64);
			}
		}
	} else {
		foreach ($definitions as $key) {
			$salts[$key] = wp_generate_password(64, true, true);
		}
	}

	if (!$salts) {
		WP_CLI::error('Failed to generate salts keys.');
		return;
	}

	$format = Utils\get_flag_value($assoc_args, 'format') ?: 'table';

	foreach ($salts as $key => $val) {
		$items[] = [
			'key' => $key,
			'value' => $val,
		];
	}

	Utils\format_items($format, $items, ['key', 'value']);
	return;
});
