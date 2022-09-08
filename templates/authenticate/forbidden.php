<?php /** @var stdClass $data */ ?>
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
	<title>OpenID Connect</title>
</head>

<body class="<?php echo esc_attr( $data->body_class_attr ); ?>">

<h1><?php esc_html_e( 'OpenID Connect', 'wp-openid-connect-server' ); ?></h1>
<p><?php esc_html_e( "You don't have permission to use OpenID Connect.", 'wp-openid-connect-server' ); ?></p>

<?php wp_footer(); ?>
</body>
</html>
