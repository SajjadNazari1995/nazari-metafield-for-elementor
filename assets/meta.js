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

	/* ---- File field ---------------------------------------------------- */
	$( document ).on( 'click', '.mfbe-file-add', function ( event ) {
		event.preventDefault();

		var $box = $( this ).closest( '.mfbe-file' );
		var frame = wp.media( {
			title: t.selectFile,
			button: { text: t.use },
			multiple: false
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first();
			$box.find( '.mfbe-file-input' ).val( attachment.get( 'id' ) ).trigger( 'change' );
			$box.find( '.mfbe-file-name' ).text( attachment.get( 'filename' ) || attachment.get( 'title' ) );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.mfbe-file-clear', function () {
		var $box = $( this ).closest( '.mfbe-file' );
		$box.find( '.mfbe-file-input' ).val( '' ).trigger( 'change' );
		$box.find( '.mfbe-file-name' ).text( '' );
	} );

	/* ---- Range field --------------------------------------------------- */
	$( document ).on( 'input', '.mfbe-range input', function () {
		$( this ).next( 'output' ).text( this.value );
	} );

	/* ---- Conditional logic -------------------------------------------- */
	function isNumber( value ) {
		return value !== '' && ! isNaN( parseFloat( value ) ) && isFinite( value );
	}

	function currentValue( key ) {
		var base = 'mfbe[' + key + ']';
		var $link = $( '[name="' + base + '[url]"]' );
		if ( $link.length ) {
			return $link.val();
		}

		var $list = $( '[name="' + base + '[]"]' );
		if ( $list.length ) {
			return $list.filter( ':checked' ).map( function () {
				return this.value;
			} ).get();
		}

		var $all = $( '[name="' + base + '"]' );
		var $radio = $all.filter( '[type="radio"]' );
		if ( $radio.length ) {
			return $radio.filter( ':checked' ).val() || '';
		}

		var $check = $all.filter( '[type="checkbox"]' );
		if ( $check.length ) {
			return $check.is( ':checked' ) ? 'true' : 'false';
		}

		return $all.last().val() || '';
	}

	function evaluate( actual, op, expected ) {
		var isList = $.isArray( actual );
		var text = isList ? '' : String( actual );

		switch ( op ) {
			case 'empty':
				return isList ? actual.length === 0 : $.trim( text ) === '';
			case 'not_empty':
				return ! evaluate( actual, 'empty', expected );
			case 'eq':
				return isList ? $.inArray( expected, actual ) !== -1 : text === expected;
			case 'neq':
				return ! evaluate( actual, 'eq', expected );
			case 'contains':
				if ( isList ) {
					return $.inArray( expected, actual ) !== -1;
				}
				return expected !== '' && text.toLowerCase().indexOf( expected.toLowerCase() ) !== -1;
			case 'gt':
				return isNumber( text ) && isNumber( expected ) && parseFloat( text ) > parseFloat( expected );
			case 'lt':
				return isNumber( text ) && isNumber( expected ) && parseFloat( text ) < parseFloat( expected );
		}
		return false;
	}

	function applyConditions() {
		$( '.mfbe-row[data-cond]' ).each( function () {
			var $row = $( this );
			var rule = $row.data( 'cond' );
			if ( ! rule || ! rule.field ) {
				return;
			}

			var visible = evaluate( currentValue( rule.field ), rule.op, String( rule.value ) );
			$row.prop( 'hidden', ! visible );

			// A hidden required input can never be focused, so the browser would block saving.
			$row.find( ':input' ).each( function () {
				var $input = $( this );
				if ( ! visible && $input.prop( 'required' ) ) {
					$input.attr( 'data-mfbe-required', '1' ).prop( 'required', false );
				} else if ( visible && $input.attr( 'data-mfbe-required' ) ) {
					$input.prop( 'required', true ).removeAttr( 'data-mfbe-required' );
				}
			} );
		} );
	}

	$( document ).on( 'input change', '.mfbe-fields :input', applyConditions );
	$( applyConditions );
}( jQuery ) );
