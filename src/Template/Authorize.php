<html <?php language_attributes(); ?> class="no-js no-svg">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style>
		.openid-connect-authentication {
			padding: 4em;
		}
	</style>
</head>

<body class="<?php echo esc_attr( $body_class_attr ); ?>">
<h1><?php esc_html_e( 'OpenID Connect', 'wp-openid-connect-server' ); ?></h1>
<p>
	<?php
	echo esc_html(
		sprintf(
		// translators: %s is a username.
			__( 'Hi %s!', 'wp-openid-connect-server' ),
			wp_get_current_user()->user_nicename
		)
	);
	?>
</p>
<?php if ( ! $has_permission ) : ?>
	<p><?php esc_html_e( "Unfortunately your user doesn't have sufficient permissions to use OpenID Connect on this server.", 'wp-openid-connect-server' ); ?></p>
<?php else : ?>
	<form method="post" action="<?php echo esc_url( $form_url ); ?>">
		<?php wp_nonce_field( 'wp_rest' ); /* The nonce will give the REST call the userdata. */ ?>
		<?php foreach ( $form_fields as $key => $value ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"/>
		<?php endforeach; ?>
		<p>
			<label>
				<?php
				echo wp_kses(
					sprintf(
					// translators: %1$s is the site name, %2$s is the username.
						__( 'Do you want to log in to <em>%1$s</em> with your <em>%2$s</em> account?', 'wp-openid-connect-server' ),
						$client_name,
						$account_name
					),
					array(
						'em' => array(),
					)
				);
				?>
			</label>
		</p>
		<input type="submit" name="authorize" value="<?php esc_attr_e( 'Authorize', 'wp-openid-connect-server' ); ?>"/>
		<a href="<?php echo esc_url( $cancel_url ); ?>" target="_top">
			<?php esc_html_e( 'Cancel', 'wp-openid-connect-server' ); ?>
		</a>
	</form>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
