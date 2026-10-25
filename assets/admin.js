/* Nazari Metafield For Elementor — admin pages */
( function () {
	'use strict';

	var cfg = window.MFBE_ADMIN || {};

	function copy( text, done ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( done );
			return;
		}
		var area = document.createElement( 'textarea' );
		area.value = text;
		area.style.cssText = 'position:fixed;opacity:0';
		document.body.appendChild( area );
		area.select();
		try {
			document.execCommand( 'copy' );
			done();
		} catch ( e ) {}
		document.body.removeChild( area );
	}

	document.addEventListener( 'click', function ( event ) {
		var el = event.target.closest ? event.target.closest( '[data-mfbe-copy]' ) : null;
		if ( ! el ) {
			return;
		}
		event.preventDefault();
		copy( el.getAttribute( 'data-mfbe-copy' ), function () {
			el.classList.add( 'is-copied' );
			el.setAttribute( 'data-copied', cfg.copied || 'Copied!' );
			window.setTimeout( function () {
				el.classList.remove( 'is-copied' );
			}, 1200 );
		} );
	} );

	// Let the toast notices fade away on their own.
	var toast = document.querySelector( '.mfbe-toast.is-success' );
	if ( toast ) {
		window.setTimeout( function () {
			toast.classList.add( 'is-hidden' );
		}, 6000 );
	}
}() );
