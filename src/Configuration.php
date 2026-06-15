<?php

namespace OpenIDConnectServer;

class Configuration {
	public const OPTION_NAME   = 'oidc_server_settings';
	public const DEFAULT_SCOPE = 'openid profile';

	public static function get_settings(): array {
		$settings = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			return array();
		}

		return $settings;
	}

	public static function get_public_key(): string {
		if ( self::is_public_key_locked() ) {
			return (string) OIDC_PUBLIC_KEY;
		}

		$settings = self::get_settings();

		return isset( $settings['public_key'] ) ? (string) $settings['public_key'] : '';
	}

	public static function get_private_key(): string {
		if ( self::is_private_key_locked() ) {
			return (string) OIDC_PRIVATE_KEY;
		}

		$settings = self::get_settings();

		return isset( $settings['private_key'] ) ? (string) $settings['private_key'] : '';
	}

	public static function is_public_key_locked(): bool {
		return defined( 'OIDC_PUBLIC_KEY' );
	}

	public static function is_private_key_locked(): bool {
		return defined( 'OIDC_PRIVATE_KEY' );
	}

	public static function has_required_keys(): bool {
		return '' !== trim( self::get_public_key() ) && '' !== trim( self::get_private_key() );
	}

	public static function get_option_clients(): array {
		$settings = self::get_settings();

		if ( empty( $settings['clients'] ) || ! is_array( $settings['clients'] ) ) {
			return array();
		}

		return $settings['clients'];
	}

	public static function get_filtered_clients(): array {
		$clients = apply_filters( 'oidc_registered_clients', array() );

		if ( ! is_array( $clients ) ) {
			return array();
		}

		return $clients;
	}

	public static function get_clients(): array {
		$clients = self::get_option_clients();

		foreach ( self::get_filtered_clients() as $client_id => $client ) {
			$clients[ $client_id ] = $client;
		}

		return $clients;
	}

	public static function has_valid_public_key( string $key ): bool {
		return (bool) preg_match( '/^-----BEGIN\s.*PUBLIC KEY-----.*-----END\s.*PUBLIC KEY-----$/s', trim( $key ) );
	}

	public static function has_valid_private_key( string $key ): bool {
		return (bool) preg_match( '/^-----BEGIN\s.*PRIVATE KEY-----.*-----END\s.*PRIVATE KEY-----$/s', trim( $key ) );
	}

	public static function uninstall() {
		delete_option( self::OPTION_NAME );
	}
}
