( function () {
	var rows = document.getElementById( 'oidc-client-rows' );
	var template = document.getElementById( 'oidc-client-row-template' );
	var add = document.getElementById( 'oidc-add-client' );

	function randomString( length ) {
		var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		var values = new Uint32Array( length );
		var output = '';
		var i;

		window.crypto.getRandomValues( values );

		for ( i = 0; i < length; i++ ) {
			output += chars[ values[ i ] % chars.length ];
		}

		return output;
	}

	function enableFields( row ) {
		row.querySelectorAll( '[disabled]' ).forEach( function ( field ) {
			field.disabled = false;
		} );
	}

	if ( rows && template && add ) {
		var nextIndex = parseInt( rows.dataset.nextIndex, 10 ) || 0;

		add.addEventListener( 'click', function ( event ) {
			var wrapper;
			var row;

			event.preventDefault();

			wrapper = document.createElement( 'tbody' );
			wrapper.innerHTML = template.innerHTML.replace( /__INDEX__/g, String( nextIndex++ ) ).trim();
			row = wrapper.firstElementChild;

			enableFields( row );
			row.querySelector( '[data-field="client_id"]' ).value = randomString( 32 );
			row.querySelector( '[data-field="secret"]' ).value = randomString( 48 );

			rows.appendChild( row );
			row.querySelector( '[data-field="name"]' ).focus();
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.oidc-remove-client,.oidc-generate-client-id,.oidc-generate-secret' );
		var row;

		if ( ! button ) {
			return;
		}

		event.preventDefault();

		row = button.closest( 'tr' );
		if ( ! row ) {
			return;
		}

		if ( button.classList.contains( 'oidc-remove-client' ) ) {
			row.remove();
			return;
		}

		if ( button.classList.contains( 'oidc-generate-client-id' ) ) {
			row.querySelector( '[data-field="client_id"]' ).value = randomString( 32 );
			return;
		}

		if ( button.classList.contains( 'oidc-generate-secret' ) ) {
			row.querySelector( '[data-field="secret"]' ).value = randomString( 48 );
		}
	} );
}() );
