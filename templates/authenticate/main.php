<?php /** @var stdClass $data */ ?>
<html <?php language_attributes(); ?> class="no-js no-svg">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<?php $data->templates->partial( 'authenticate/style' ); ?>
	<title>OpenID Connect</title>
</head>

<body class="<?php echo esc_attr( $data->body_class_attr ); ?>">
<div class="openid-connect-authenticate">
	<h1><?php esc_html_e( 'OpenID Connect', 'wp-openid-connect-server' ); ?></h1>
	<p>
		<?php
		echo esc_html(
			sprintf(
			// translators: %s is a username.
				__( 'Hi %s!', 'wp-openid-connect-server' ),
				$data->user->user_nicename
			)
		);
		?>
	</p>
	<p>
		<label>
			<?php
			echo wp_kses(
				sprintf(
				// translators: %1$s is the site name, %2$s is the username.
					__( 'Do you want to log in to <em>%1$s</em> with your <em>%2$s</em> account?', 'wp-openid-connect-server' ),
					$data->client_name,
					get_bloginfo( 'name' )
				),
				array(
					'em' => array(),
				)
			);
			?>
		</label>
	</p>
	<?php $data->templates->partial( 'authenticate/form' ); ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
