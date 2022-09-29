<?php /** @var stdClass $data */ ?>

<form method="post" action="<?php echo esc_url( $data->form_url ); ?>" class="wp-core-ui">
	<?php wp_nonce_field( 'wp_rest' ); /* The nonce will give the REST call the userdata. */ ?>
	<?php foreach ( $data->form_fields as $key => $value ) : ?>
		<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"/>
	<?php endforeach; ?>
    <p class="submit">
        <input type="submit" name="authorize" class="button button-primary button-large" value="<?php esc_attr_e( 'Authorize', 'wp-openid-connect-server' ); ?>"/>
        <a href="<?php echo esc_url( $data->cancel_url ); ?>" target="_top">
            <?php esc_html_e( 'Cancel', 'wp-openid-connect-server' ); ?>
        </a>
    </p>
</form>

<style>
    #login { display: none; }
</style>
