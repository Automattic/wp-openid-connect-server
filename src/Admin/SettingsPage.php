<?php

namespace OpenIDConnectServer\Admin;

use OpenIDConnectServer\Configuration;
use OpenIDConnectServer\Http\Router;

class SettingsPage {
	private const PAGE_SLUG     = 'openid-connect-server';
	private const NOTICE_KEY    = 'oidc_settings_notices_';
	private const NONCE_ACTION  = 'oidc_save_settings';
	private const NONCE_NAME    = 'oidc_settings_nonce';
	private const OPTION_ACTION = 'oidc_save_settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_post_' . self::OPTION_ACTION, array( $this, 'save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_menu_page() {
		add_options_page(
			__( 'OpenID Connect Server', 'openid-connect-server' ),
			__( 'OpenID Connect', 'openid-connect-server' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( $hook_suffix ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_register_style( 'openid-connect-server-settings', false, array(), '2.0.0' );
		wp_enqueue_style( 'openid-connect-server-settings' );
		wp_add_inline_style(
			'openid-connect-server-settings',
			'.oidc-settings textarea{font-family:monospace;width:100%;max-width:900px}.oidc-settings .regular-text{max-width:100%}.oidc-settings-table{max-width:1100px}.oidc-settings-table th{white-space:nowrap}.oidc-settings-table input[type=text],.oidc-settings-table textarea{width:100%}.oidc-settings-table .column-actions{width:100px}.oidc-settings-source{display:inline-block;margin-top:4px;color:#646970}.oidc-settings-code-clients{margin-top:2em}.oidc-provider-urls code{display:block;white-space:normal;word-break:break-all}'
		);

		wp_enqueue_script(
			'openid-connect-server-settings',
			plugins_url( 'assets/js/settings.js', dirname( __DIR__, 2 ) . '/openid-connect-server.php' ),
			array(),
			'2.0.0',
			true
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$option_clients   = Configuration::get_option_clients();
		$filtered_clients = Configuration::get_filtered_clients();
		?>
		<div class="wrap oidc-settings">
			<h1><?php esc_html_e( 'OpenID Connect Server', 'openid-connect-server' ); ?></h1>
			<?php $this->render_notices(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::OPTION_ACTION ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<?php $this->render_key_fields(); ?>
				<?php $this->render_client_fields( $option_clients, $filtered_clients ); ?>

				<?php submit_button( __( 'Save Settings', 'openid-connect-server' ) ); ?>
			</form>

			<?php $this->render_provider_urls(); ?>
		</div>
		<?php
	}

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage OpenID Connect settings.', 'openid-connect-server' ) );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		$existing_settings = Configuration::get_settings();
		$settings          = $existing_settings;
		$errors            = array();
		$raw_settings      = array();

		if ( isset( $_POST['oidc_settings'] ) && is_array( $_POST['oidc_settings'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Values are sanitized per field below to avoid corrupting PEM keys and client secrets.
			$raw_settings = wp_unslash( $_POST['oidc_settings'] );
		}

		if ( ! Configuration::is_public_key_locked() ) {
			$public_key = $this->normalize_multiline_secret( $raw_settings['public_key'] ?? '' );
			if ( '' === $public_key || ! Configuration::has_valid_public_key( $public_key ) ) {
				$errors[] = __( 'The public key must be a valid PEM public key.', 'openid-connect-server' );
			}
			$settings['public_key'] = $public_key;
		}

		if ( ! Configuration::is_private_key_locked() ) {
			$private_key = $this->normalize_multiline_secret( $raw_settings['private_key'] ?? '' );
			if ( '' === $private_key || ! Configuration::has_valid_private_key( $private_key ) ) {
				$errors[] = __( 'The private key must be a valid PEM private key.', 'openid-connect-server' );
			}
			$settings['private_key'] = $private_key;
		}

		$clients_result      = $this->sanitize_clients( $raw_settings['clients'] ?? array() );
		$settings['clients'] = $clients_result['clients'];
		$errors              = array_merge( $errors, $clients_result['errors'] );

		if ( ! empty( $errors ) ) {
			$this->redirect_with_notices( $errors, 'error' );
		}

		update_option( Configuration::OPTION_NAME, $settings, false );
		$this->redirect_with_notices( array( __( 'OpenID Connect settings saved.', 'openid-connect-server' ) ), 'success' );
	}

	private function render_key_fields() {
		$public_key_locked  = Configuration::is_public_key_locked();
		$private_key_locked = Configuration::is_private_key_locked();
		?>
		<h2><?php esc_html_e( 'RSA Keys', 'openid-connect-server' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="oidc-public-key"><?php esc_html_e( 'Public key', 'openid-connect-server' ); ?></label>
					</th>
					<td>
						<textarea id="oidc-public-key" name="oidc_settings[public_key]" rows="8" <?php disabled( $public_key_locked ); ?>><?php echo esc_textarea( Configuration::get_public_key() ); ?></textarea>
						<p class="description">
							<?php
							if ( $public_key_locked ) {
								esc_html_e( 'Defined by the OIDC_PUBLIC_KEY constant and locked for editing.', 'openid-connect-server' );
							} else {
								esc_html_e( 'Paste the PEM-formatted public key.', 'openid-connect-server' );
							}
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="oidc-private-key"><?php esc_html_e( 'Private key', 'openid-connect-server' ); ?></label>
					</th>
					<td>
						<textarea id="oidc-private-key" name="oidc_settings[private_key]" rows="12" <?php disabled( $private_key_locked ); ?>><?php echo esc_textarea( Configuration::get_private_key() ); ?></textarea>
						<p class="description">
							<?php
							if ( $private_key_locked ) {
								esc_html_e( 'Defined by the OIDC_PRIVATE_KEY constant and locked for editing.', 'openid-connect-server' );
							} else {
								esc_html_e( 'Paste the PEM-formatted private key.', 'openid-connect-server' );
							}
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private function render_client_fields( array $option_clients, array $filtered_clients ) {
		$index = 0;
		?>
		<h2><?php esc_html_e( 'Clients', 'openid-connect-server' ); ?></h2>
		<p><?php esc_html_e( 'Configure clients that can authenticate users through this WordPress site.', 'openid-connect-server' ); ?></p>
		<table class="widefat striped oidc-settings-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Client ID', 'openid-connect-server' ); ?></th>
					<th><?php esc_html_e( 'Name', 'openid-connect-server' ); ?></th>
					<th><?php esc_html_e( 'Secret', 'openid-connect-server' ); ?></th>
					<th><?php esc_html_e( 'Redirect URI', 'openid-connect-server' ); ?></th>
					<th><?php esc_html_e( 'Scope', 'openid-connect-server' ); ?></th>
					<th><?php esc_html_e( 'Consent', 'openid-connect-server' ); ?></th>
					<th class="column-actions"><?php esc_html_e( 'Actions', 'openid-connect-server' ); ?></th>
				</tr>
			</thead>
			<tbody id="oidc-client-rows" data-next-index="<?php echo esc_attr( count( $option_clients ) ); ?>">
				<?php foreach ( $option_clients as $client_id => $client ) : ?>
					<?php $this->render_editable_client_row( $index, (string) $client_id, is_array( $client ) ? $client : array() ); ?>
					<?php ++$index; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p>
			<button type="button" class="button" id="oidc-add-client"><?php esc_html_e( 'Add Client', 'openid-connect-server' ); ?></button>
		</p>

		<script type="text/html" id="oidc-client-row-template">
			<?php $this->render_editable_client_row( '__INDEX__', '', array(), true ); ?>
		</script>

		<?php $this->render_filtered_clients( $filtered_clients ); ?>
		<?php
	}

	private function render_editable_client_row( $index, string $client_id, array $client, bool $template = false ) {
		$name             = isset( $client['name'] ) ? (string) $client['name'] : '';
		$secret           = isset( $client['secret'] ) ? (string) $client['secret'] : '';
		$redirect_uri     = isset( $client['redirect_uri'] ) ? (string) $client['redirect_uri'] : '';
		$scope            = isset( $client['scope'] ) ? (string) $client['scope'] : Configuration::DEFAULT_SCOPE;
		$requires_consent = ! array_key_exists( 'requires_consent', $client ) || false !== $client['requires_consent'];
		$disabled         = $template ? ' disabled' : '';
		?>
		<tr>
			<td>
				<input type="text" class="regular-text" data-field="client_id" name="oidc_settings[clients][<?php echo esc_attr( (string) $index ); ?>][client_id]" value="<?php echo esc_attr( $client_id ); ?>"<?php echo esc_attr( $disabled ); ?> />
				<button type="button" class="button button-small oidc-generate-client-id"<?php echo esc_attr( $disabled ); ?>><?php esc_html_e( 'Generate', 'openid-connect-server' ); ?></button>
			</td>
			<td>
				<input type="text" class="regular-text" data-field="name" name="oidc_settings[clients][<?php echo esc_attr( (string) $index ); ?>][name]" value="<?php echo esc_attr( $name ); ?>"<?php echo esc_attr( $disabled ); ?> />
			</td>
			<td>
				<textarea rows="2" data-field="secret" name="oidc_settings[clients][<?php echo esc_attr( (string) $index ); ?>][secret]"<?php echo esc_attr( $disabled ); ?>><?php echo esc_textarea( $secret ); ?></textarea>
				<button type="button" class="button button-small oidc-generate-secret"<?php echo esc_attr( $disabled ); ?>><?php esc_html_e( 'Generate', 'openid-connect-server' ); ?></button>
			</td>
			<td>
				<input type="text" class="regular-text" data-field="redirect_uri" name="oidc_settings[clients][<?php echo esc_attr( (string) $index ); ?>][redirect_uri]" value="<?php echo esc_attr( $redirect_uri ); ?>" placeholder="https://example.com/callback"<?php echo esc_attr( $disabled ); ?> />
			</td>
			<td>
				<input type="text" class="regular-text" data-field="scope" name="oidc_settings[clients][<?php echo esc_attr( (string) $index ); ?>][scope]" value="<?php echo esc_attr( $scope ); ?>"<?php echo esc_attr( $disabled ); ?> />
				<input type="hidden" name="oidc_settings[clients][<?php echo esc_attr( (string) $index ); ?>][grant_types][]" value="authorization_code"<?php echo esc_attr( $disabled ); ?> />
			</td>
			<td>
				<label>
					<input type="checkbox" name="oidc_settings[clients][<?php echo esc_attr( (string) $index ); ?>][requires_consent]" value="1" <?php checked( $requires_consent ); ?><?php echo esc_attr( $disabled ); ?> />
					<?php esc_html_e( 'Required', 'openid-connect-server' ); ?>
				</label>
			</td>
			<td>
				<button type="button" class="button-link-delete oidc-remove-client"<?php echo esc_attr( $disabled ); ?>><?php esc_html_e( 'Remove', 'openid-connect-server' ); ?></button>
			</td>
		</tr>
		<?php
	}

	private function render_filtered_clients( array $filtered_clients ) {
		if ( empty( $filtered_clients ) ) {
			return;
		}
		?>
		<div class="oidc-settings-code-clients">
			<h3><?php esc_html_e( 'Clients defined in code', 'openid-connect-server' ); ?></h3>
			<p><?php esc_html_e( 'These clients are provided by the oidc_registered_clients filter and are locked for editing.', 'openid-connect-server' ); ?></p>
			<table class="widefat striped oidc-settings-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Client ID', 'openid-connect-server' ); ?></th>
						<th><?php esc_html_e( 'Name', 'openid-connect-server' ); ?></th>
						<th><?php esc_html_e( 'Secret', 'openid-connect-server' ); ?></th>
						<th><?php esc_html_e( 'Redirect URI', 'openid-connect-server' ); ?></th>
						<th><?php esc_html_e( 'Scope', 'openid-connect-server' ); ?></th>
						<th><?php esc_html_e( 'Consent', 'openid-connect-server' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $filtered_clients as $client_id => $client ) : ?>
						<?php $client = is_array( $client ) ? $client : array(); ?>
						<tr>
							<td><input type="text" value="<?php echo esc_attr( (string) $client_id ); ?>" disabled /></td>
							<td><input type="text" value="<?php echo esc_attr( isset( $client['name'] ) ? (string) $client['name'] : '' ); ?>" disabled /></td>
							<td><textarea rows="2" disabled><?php echo esc_textarea( isset( $client['secret'] ) ? (string) $client['secret'] : '' ); ?></textarea></td>
							<td><input type="text" value="<?php echo esc_attr( isset( $client['redirect_uri'] ) ? (string) $client['redirect_uri'] : '' ); ?>" disabled /></td>
							<td><input type="text" value="<?php echo esc_attr( isset( $client['scope'] ) ? (string) $client['scope'] : '' ); ?>" disabled /></td>
							<td><input type="text" value="<?php echo esc_attr( ! array_key_exists( 'requires_consent', $client ) || false !== $client['requires_consent'] ? __( 'Required', 'openid-connect-server' ) : __( 'Not required', 'openid-connect-server' ) ); ?>" disabled /></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function render_provider_urls() {
		$urls = array(
			array(
				'label' => __( 'Issuer', 'openid-connect-server' ),
				'url'   => Router::make_url(),
			),
			array(
				'label' => __( 'Discovery', 'openid-connect-server' ),
				'url'   => Router::make_url( '.well-known/openid-configuration' ),
			),
			array(
				'label' => __( 'JWKS', 'openid-connect-server' ),
				'url'   => Router::make_url( '.well-known/jwks.json' ),
			),
			array(
				'label' => __( 'Authorization endpoint', 'openid-connect-server' ),
				'url'   => Router::make_rest_url( 'authorize' ),
			),
			array(
				'label' => __( 'Token endpoint', 'openid-connect-server' ),
				'url'   => Router::make_rest_url( 'token' ),
			),
			array(
				'label' => __( 'Userinfo endpoint', 'openid-connect-server' ),
				'url'   => Router::make_rest_url( 'userinfo' ),
			),
		);
		?>
		<h2><?php esc_html_e( 'Provider URLs', 'openid-connect-server' ); ?></h2>
		<table class="form-table oidc-provider-urls" role="presentation">
			<tbody>
				<?php foreach ( $urls as $provider_url ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $provider_url['label'] ); ?></th>
						<td><code><?php echo esc_url( $provider_url['url'] ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function sanitize_clients( $raw_clients ): array {
		$clients          = array();
		$errors           = array();
		$filtered_clients = Configuration::get_filtered_clients();

		if ( ! is_array( $raw_clients ) ) {
			return array(
				'clients' => $clients,
				'errors'  => $errors,
			);
		}

		foreach ( $raw_clients as $raw_client ) {
			if ( ! is_array( $raw_client ) ) {
				continue;
			}

			$client_id    = isset( $raw_client['client_id'] ) ? sanitize_text_field( $raw_client['client_id'] ) : '';
			$name         = isset( $raw_client['name'] ) ? sanitize_text_field( $raw_client['name'] ) : '';
			$secret       = isset( $raw_client['secret'] ) ? trim( (string) $raw_client['secret'] ) : '';
			$redirect_uri = isset( $raw_client['redirect_uri'] ) ? esc_url_raw( trim( (string) $raw_client['redirect_uri'] ) ) : '';
			$scope        = isset( $raw_client['scope'] ) ? sanitize_text_field( $raw_client['scope'] ) : Configuration::DEFAULT_SCOPE;

			if ( '' === $client_id && '' === $name && '' === $secret && '' === $redirect_uri ) {
				continue;
			}

			if ( strlen( $client_id ) < 10 ) {
				$errors[] = __( 'Each client ID must be at least 10 characters long.', 'openid-connect-server' );
				continue;
			}

			if ( isset( $clients[ $client_id ] ) ) {
				$errors[] = sprintf(
					// translators: %s is an OpenID Connect client ID.
					__( 'The client ID %s is duplicated.', 'openid-connect-server' ),
					$client_id
				);
				continue;
			}

			if ( isset( $filtered_clients[ $client_id ] ) ) {
				$errors[] = sprintf(
					// translators: %s is an OpenID Connect client ID.
					__( 'The client ID %s is already defined in code and cannot be saved from the settings page.', 'openid-connect-server' ),
					$client_id
				);
				continue;
			}

			if ( '' === $name ) {
				$errors[] = sprintf(
					// translators: %s is an OpenID Connect client ID.
					__( 'The client %s needs a name.', 'openid-connect-server' ),
					$client_id
				);
				continue;
			}

			if ( '' === $secret ) {
				$errors[] = sprintf(
					// translators: %s is an OpenID Connect client ID.
					__( 'The client %s needs a secret.', 'openid-connect-server' ),
					$client_id
				);
				continue;
			}

			if ( '' === $redirect_uri ) {
				$errors[] = sprintf(
					// translators: %s is an OpenID Connect client ID.
					__( 'The client %s needs a redirect URI.', 'openid-connect-server' ),
					$client_id
				);
				continue;
			}

			if ( ! preg_match( '#^https://#', $redirect_uri ) ) {
				$errors[] = sprintf(
					// translators: %s is an OpenID Connect client ID.
					__( 'The redirect URI for client %s must use HTTPS.', 'openid-connect-server' ),
					$client_id
				);
				continue;
			}

			$clients[ $client_id ] = array(
				'name'             => $name,
				'secret'           => $secret,
				'redirect_uri'     => $redirect_uri,
				'grant_types'      => array( 'authorization_code' ),
				'scope'            => '' === $scope ? Configuration::DEFAULT_SCOPE : $scope,
				'requires_consent' => isset( $raw_client['requires_consent'] ),
			);
		}

		return array(
			'clients' => $clients,
			'errors'  => $errors,
		);
	}

	private function normalize_multiline_secret( $value ): string {
		$value = str_replace( "\r\n", "\n", (string) $value );
		$value = str_replace( "\r", "\n", $value );

		return trim( $value );
	}

	private function render_notices() {
		$notices = get_transient( self::NOTICE_KEY . get_current_user_id() );
		if ( ! is_array( $notices ) ) {
			return;
		}

		delete_transient( self::NOTICE_KEY . get_current_user_id() );

		foreach ( $notices as $notice ) {
			if ( empty( $notice['message'] ) || empty( $notice['type'] ) ) {
				continue;
			}
			?>
			<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
				<p><?php echo esc_html( $notice['message'] ); ?></p>
			</div>
			<?php
		}
	}

	private function redirect_with_notices( array $messages, string $type ) {
		$notices = array();
		foreach ( $messages as $message ) {
			$notices[] = array(
				'type'    => $type,
				'message' => $message,
			);
		}

		set_transient( self::NOTICE_KEY . get_current_user_id(), $notices, 30 );
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) );
		exit;
	}
}
