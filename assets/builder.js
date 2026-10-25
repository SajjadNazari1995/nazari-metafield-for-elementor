/* Nazari Metafield For Elementor — field group builder */
( function ( $ ) {
	'use strict';

	var cfg = window.MFBE_BUILDER;
	if ( ! cfg ) {
		return;
	}

	var t = cfg.i18n;
	var $list = $( '#mfbe-fields-list' );
	var $json = $( '#mfbe-fields-json' );
	var $empty = $( '#mfbe-builder .mfbe-empty' );
	var $toolbar = $( '#mfbe-toolbar' );

	function slug( value ) {
		return String( value || '' )
			.toLowerCase()
			.replace( /[^a-z0-9_]+/g, '_' )
			.replace( /^_+|_+$/g, '' );
	}

	function esc( value ) {
		return $( '<div>' ).text( value == null ? '' : value ).html().replace( /"/g, '&quot;' );
	}

	function selectHtml( key, choices, current ) {
		var html = '<select data-key="' + key + '">';
		$.each( choices, function ( value, label ) {
			html += '<option value="' + esc( value ) + '"' + ( String( value ) === String( current ) ? ' selected' : '' ) + '>' + esc( label ) + '</option>';
		} );
		return html + '</select>';
	}

	function widthChoices() {
		var out = {};
		$.each( cfg.widths, function ( i, w ) {
			out[ w ] = w + '%';
		} );
		return out;
	}

	function inputHtml( key, value, type ) {
		return '<input type="' + ( type || 'text' ) + '" data-key="' + key + '" value="' + esc( value ) + '">';
	}

	function rowHtml( field ) {
		return (
			'<div class="mfbe-field">' +
				'<div class="mfbe-field-head">' +
					'<span class="dashicons dashicons-menu mfbe-handle"></span>' +
					'<span class="dashicons mfbe-type-icon"></span>' +
					'<strong class="mfbe-field-title"></strong>' +
					'<code class="mfbe-field-key"></code>' +
					'<span class="mfbe-field-type"></span>' +
					'<span class="mfbe-field-actions">' +
						'<button type="button" class="button-link mfbe-toggle">' + esc( t.edit ) + '</button>' +
						'<button type="button" class="button-link mfbe-duplicate">' + esc( t.duplicate ) + '</button>' +
						'<button type="button" class="button-link mfbe-remove">' + esc( t.remove ) + '</button>' +
					'</span>' +
				'</div>' +
				'<div class="mfbe-field-body">' +
					'<div class="mfbe-shortcode-row">' +
						'<span>' + esc( t.shortcode ) + '</span>' +
						'<code class="mfbe-shortcode"></code>' +
						'<button type="button" class="button button-small mfbe-copy">' + esc( t.copy ) + '</button>' +
					'</div>' +
					'<div class="mfbe-grid">' +
						'<p><label>' + esc( t.label ) + inputHtml( 'label', field.label ) + '</label></p>' +
						'<p><label>' + esc( t.name ) + inputHtml( 'name', field.name ) + '<span class="description">' + esc( t.nameHelp ) + '</span></label></p>' +
						'<p><label>' + esc( t.type ) + selectHtml( 'type', cfg.types, field.type ) + '</label></p>' +
						'<p><label>' + esc( t.width ) + selectHtml( 'width', widthChoices(), field.width ) + '</label></p>' +
						'<p class="mfbe-wide"><label>' + esc( t.description ) + inputHtml( 'description', field.description ) + '</label></p>' +
						'<p><label>' + esc( t.placeholder ) + inputHtml( 'placeholder', field.placeholder ) + '</label></p>' +
						'<p><label>' + esc( t.default ) + inputHtml( 'default', field.default ) + '</label></p>' +
						'<p class="mfbe-wide mfbe-opt"><label>' + esc( t.options ) + '<textarea rows="4" data-key="options">' + esc( field.options ) + '</textarea><span class="description">' + esc( t.optionsHelp ) + '</span></label></p>' +
						'<p class="mfbe-num"><label>' + esc( t.min ) + inputHtml( 'min', field.min, 'number' ) + '</label></p>' +
						'<p class="mfbe-num"><label>' + esc( t.max ) + inputHtml( 'max', field.max, 'number' ) + '</label></p>' +
						'<p class="mfbe-num"><label>' + esc( t.step ) + inputHtml( 'step', field.step, 'number' ) + '</label></p>' +
					'</div>' +
				'</div>' +
			'</div>'
		);
	}

	function defaults() {
		return {
			label: '', name: '', type: 'text', description: '', placeholder: '',
			default: '', options: '', width: '100', min: '', max: '', step: ''
		};
	}

	function setOpen( $row, open ) {
		$row.toggleClass( 'is-open', open );
		$row.find( '.mfbe-toggle' ).text( open ? t.close : t.edit );
	}

	function refreshRow( $row ) {
		var label = $row.find( '[data-key="label"]' ).val();
		var name = $row.find( '[data-key="name"]' ).val();
		var type = $row.find( '[data-key="type"]' ).val();

		$row.find( '.mfbe-field-title' ).text( label || t.newField );
		$row.find( '.mfbe-field-key' ).text( name );
		$row.find( '.mfbe-field-type' ).text( cfg.types[ type ] || type );
		$row.find( '.mfbe-type-icon' ).attr( 'class', 'dashicons mfbe-type-icon dashicons-' + ( cfg.icons[ type ] || 'admin-generic' ) );
		$row.find( '.mfbe-opt' ).toggle( $.inArray( type, cfg.optionTypes ) !== -1 );
		$row.find( '.mfbe-num' ).toggle( type === 'number' );

		var key = slug( name );
		$row.find( '.mfbe-shortcode' ).text( key ? '[mfbe field="' + key + '"]' : '—' );
		$row.find( '.mfbe-copy' ).prop( 'disabled', ! key );
	}

	function refreshEmpty() {
		var count = $list.children().length;
		$empty.toggle( count === 0 );
		$toolbar.toggle( count > 0 );
	}

	function addRow( field, open ) {
		var data = $.extend( defaults(), field || {} );
		var $row = $( rowHtml( data ) );
		$row.data( 'auto', ! data.name );
		refreshRow( $row );
		$list.append( $row );
		setOpen( $row, !! open );
		refreshEmpty();
		return $row;
	}

	function collect() {
		var rows = [];
		$list.children().each( function () {
			var row = {};
			$( this ).find( '[data-key]' ).each( function () {
				row[ $( this ).data( 'key' ) ] = $( this ).val();
			} );
			rows.push( row );
		} );
		return rows;
	}

	function usedNames() {
		var names = {};
		$list.find( '[data-key="name"]' ).each( function () {
			names[ slug( $( this ).val() ) ] = true;
		} );
		return names;
	}

	function copyText( text, $button ) {
		var done = function () {
			var original = $button.data( 'label' ) || $button.text();
			$button.data( 'label', original ).text( t.copied );
			window.setTimeout( function () {
				$button.text( original );
			}, 1200 );
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( done );
			return;
		}

		var $tmp = $( '<textarea>' ).val( text ).css( { position: 'fixed', opacity: 0 } ).appendTo( 'body' );
		$tmp[ 0 ].select();
		try {
			document.execCommand( 'copy' );
			done();
		} catch ( e ) {}
		$tmp.remove();
	}

	$list.sortable( { handle: '.mfbe-handle', axis: 'y', tolerance: 'pointer' } );

	$list.on( 'input change', '[data-key]', function () {
		var $row = $( this ).closest( '.mfbe-field' );
		var key = $( this ).data( 'key' );

		if ( key === 'name' ) {
			$row.data( 'auto', false );
		}
		if ( key === 'label' && $row.data( 'auto' ) ) {
			$row.find( '[data-key="name"]' ).val( slug( $( this ).val() ) );
		}
		refreshRow( $row );
	} );

	$list.on( 'blur', '[data-key="name"]', function () {
		$( this ).val( slug( $( this ).val() ) );
		refreshRow( $( this ).closest( '.mfbe-field' ) );
	} );

	$list.on( 'click', '.mfbe-toggle, .mfbe-field-title, .mfbe-field-key, .mfbe-field-type', function () {
		var $row = $( this ).closest( '.mfbe-field' );
		setOpen( $row, ! $row.hasClass( 'is-open' ) );
	} );

	$list.on( 'click', '.mfbe-copy', function () {
		var $row = $( this ).closest( '.mfbe-field' );
		copyText( $row.find( '.mfbe-shortcode' ).text(), $( this ) );
	} );

	$list.on( 'click', '.mfbe-duplicate', function () {
		var $row = $( this ).closest( '.mfbe-field' );
		var data = {};
		$row.find( '[data-key]' ).each( function () {
			data[ $( this ).data( 'key' ) ] = $( this ).val();
		} );

		var base = slug( data.name ) || 'field';
		var names = usedNames();
		var candidate = base + '_copy';
		var n = 2;
		while ( names[ candidate ] ) {
			candidate = base + '_copy' + n++;
		}
		data.name = candidate;
		if ( data.label ) {
			data.label = data.label + ' (copy)';
		}

		var $new = addRow( data, true );
		$new.data( 'auto', false );
		$new.insertAfter( $row );
		$new.find( '[data-key="label"]' ).trigger( 'focus' );
	} );

	$list.on( 'click', '.mfbe-remove', function () {
		if ( window.confirm( t.confirmRemove ) ) {
			$( this ).closest( '.mfbe-field' ).remove();
			refreshEmpty();
		}
	} );

	$( '#mfbe-add-field' ).on( 'click', function () {
		var $row = addRow( null, true );
		$row.find( '[data-key="label"]' ).trigger( 'focus' );
	} );

	$( '#mfbe-expand-all' ).on( 'click', function () {
		$list.children().each( function () {
			setOpen( $( this ), true );
		} );
	} );

	$( '#mfbe-collapse-all' ).on( 'click', function () {
		$list.children().each( function () {
			setOpen( $( this ), false );
		} );
	} );

	$( '#post' ).on( 'submit', function ( event ) {
		var names = {};
		var valid = true;

		$list.children().each( function () {
			var $row = $( this );
			var $name = $row.find( '[data-key="name"]' );
			var name = slug( $name.val() );

			$name.val( name );
			$row.removeClass( 'has-error' );

			if ( ! name || names[ name ] ) {
				valid = false;
				$row.addClass( 'has-error' );
				setOpen( $row, true );
			}
			names[ name ] = true;
		} );

		if ( ! valid ) {
			event.preventDefault();
			window.alert( t.errorKeys );
			window.setTimeout( function () {
				$( '#publish, #save-post' ).removeClass( 'disabled' );
				$( '.spinner' ).removeClass( 'is-active' );
			}, 0 );
			return;
		}

		$json.val( JSON.stringify( collect() ) );
	} );

	$.each( cfg.fields || [], function ( i, field ) {
		addRow( field, false );
	} );
	refreshEmpty();
}( jQuery ) );
