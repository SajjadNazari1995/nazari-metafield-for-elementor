/* Meta Fields for Elementor — meta box scripts */
( function ( $ ) {
	'use strict';

	var t = window.MFBE_META || {};

	function sync( $box ) {
		var ids = [];
		$box.find( '.mfbe-thumb' ).each( function () {
			ids.push( $( this ).data( 'id' ) );
		} );
		$box.find( '.mfbe-media-input' ).val( ids.join( ',' ) );
	}

	function thumbHtml( attachment ) {
		var sizes = attachment.get( 'sizes' ) || {};
		var url = sizes.thumbnail ? sizes.thumbnail.url : attachment.get( 'url' );
		var $thumb = $( '<span class="mfbe-thumb"></span>' ).attr( 'data-id', attachment.get( 'id' ) );
		$thumb.append( $( '<img>' ).attr( 'src', url ).attr( 'alt', '' ) );
		$thumb.append( $( '<button type="button" class="mfbe-thumb-remove">&times;</button>' ).attr( 'aria-label', t.remove || 'Remove' ) );
		return $thumb;
	}

	function makeSortable( $box ) {
		if ( $box.data( 'multiple' ) ) {
			$box.find( '.mfbe-media-preview' ).sortable( {
				tolerance: 'pointer',
				update: function () {
					sync( $box );
				}
			} );
		}
	}

	$( function () {
		$( '.mfbe-color' ).wpColorPicker();
		$( '.mfbe-media' ).each( function () {
			makeSortable( $( this ) );
		} );
	} );

	$( document ).on( 'click', '.mfbe-media-add', function ( event ) {
		event.preventDefault();

		var $box = $( this ).closest( '.mfbe-media' );
		var multiple = !! $box.data( 'multiple' );

		var frame = wp.media( {
			title: multiple ? t.selectImages : t.selectImage,
			button: { text: t.use },
			library: { type: 'image' },
			multiple: multiple ? 'add' : false
		} );

		frame.on( 'select', function () {
			var $preview = $box.find( '.mfbe-media-preview' );
			var selection = frame.state().get( 'selection' );

			if ( ! multiple ) {
				$preview.empty();
			}

			selection.each( function ( attachment ) {
				if ( $preview.find( '.mfbe-thumb[data-id="' + attachment.get( 'id' ) + '"]' ).length === 0 ) {
					$preview.append( thumbHtml( attachment ) );
				}
			} );

			sync( $box );
			makeSortable( $box );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.mfbe-thumb-remove', function () {
		var $box = $( this ).closest( '.mfbe-media' );
		$( this ).closest( '.mfbe-thumb' ).remove();
		sync( $box );
	} );

	$( document ).on( 'click', '.mfbe-media-clear', function () {
		var $box = $( this ).closest( '.mfbe-media' );
		$box.find( '.mfbe-media-preview' ).empty();
		sync( $box );
	} );
}( jQuery ) );
