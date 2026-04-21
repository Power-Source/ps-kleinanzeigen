/**
 * cf-tagsinput.js
 * Secure, lightweight jQuery tags-input widget.
 * API-compatible replacement for the old jquery.tagsinput plugin.
 * Uses DOM methods exclusively – no innerHTML/html() with user-supplied data.
 *
 * Usage: $('#myInput').tagsInput({ defaultText: 'add a tag', width: 'auto', height: '150px' });
 */
( function ( $ ) {
	'use strict';

	$.fn.tagsInput = function ( options ) {
		var defaults = {
			defaultText:      'add a tag',
			width:            'auto',
			height:           '40px',
			removeIndicator:  'x',
			delimiter:        ',',
		};

		var opts = $.extend( {}, defaults, options );

		return this.each( function () {
			var $realInput = $( this );

			// Build wrapper using safe DOM construction only
			var wrapper    = document.createElement( 'div' );
			wrapper.className = 'tagsinput';
			wrapper.style.width  = opts.width;
			wrapper.style.height = opts.height;

			var addTagDiv  = document.createElement( 'div' );
			addTagDiv.id  = ( $realInput.attr( 'id' ) || 'tagsinput' ) + '_addTag';

			var fakeInput  = document.createElement( 'input' );
			fakeInput.type  = 'text';
			fakeInput.className = 'tagsinput-new';
			// .setAttribute keeps placeholder safe (attribute value, not HTML)
			fakeInput.setAttribute( 'placeholder', opts.defaultText );
			fakeInput.style.border = '1px solid transparent';
			fakeInput.style.padding = '5px';
			fakeInput.style.margin  = '0 5px 5px 0';
			fakeInput.style.background = 'transparent';
			fakeInput.style.outline = '0';

			var clearDiv   = document.createElement( 'div' );
			clearDiv.className = 'tags_clear';

			addTagDiv.appendChild( fakeInput );
			wrapper.appendChild( addTagDiv );
			wrapper.appendChild( clearDiv );

			$realInput.hide().after( wrapper );

			// --- helpers ---

			function getTags() {
				var raw = $realInput.val();
				if ( ! raw ) return [];
				return raw.split( opts.delimiter ).map( function ( t ) {
					return t.trim();
				} ).filter( Boolean );
			}

			function syncValue() {
				var tags = [];
				wrapper.querySelectorAll( 'span.tag' ).forEach( function ( el ) {
					tags.push( el.dataset.tag );
				} );
				$realInput.val( tags.join( opts.delimiter ) );
			}

			function removeTag( span ) {
				wrapper.removeChild( span );
				syncValue();
			}

			function addTag( text ) {
				text = text.trim();
				if ( ! text ) return;

				// Prevent duplicates (case-insensitive check)
				var lower = text.toLowerCase();
				var existing = wrapper.querySelectorAll( 'span.tag' );
				for ( var i = 0; i < existing.length; i++ ) {
					if ( existing[ i ].dataset.tag.toLowerCase() === lower ) return;
				}

				// Build tag pill with safe DOM methods (no innerHTML with user data)
				var span       = document.createElement( 'span' );
				span.className = 'tag';
				span.dataset.tag = text;         // stored separately from display

				var label      = document.createElement( 'span' );
				label.textContent = text;        // textContent escapes automatically

				var removeLink = document.createElement( 'a' );
				removeLink.href = '#';
				removeLink.textContent = opts.removeIndicator;  // also safe
				removeLink.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					removeTag( span );
				} );

				span.appendChild( label );
				span.appendChild( document.createTextNode( ' ' ) );
				span.appendChild( removeLink );

				// Insert before the addTag-div so new pills appear to the left of the input
				wrapper.insertBefore( span, addTagDiv );
				syncValue();
			}

			// --- event handlers ---

			$( fakeInput ).on( 'keydown', function ( e ) {
				// Enter (13) or comma (188 / ',' keyCode)
				if ( e.which === 13 || e.which === 188 ) {
					e.preventDefault();
					addTag( fakeInput.value );
					fakeInput.value = '';
				}
				// Backspace on empty input removes last tag
				if ( e.which === 8 && fakeInput.value === '' ) {
					var tags = wrapper.querySelectorAll( 'span.tag' );
					if ( tags.length ) {
						removeTag( tags[ tags.length - 1 ] );
					}
				}
			} );

			$( fakeInput ).on( 'blur', function () {
				var v = fakeInput.value.trim();
				if ( v ) {
					addTag( v );
					fakeInput.value = '';
				}
			} );

			$( wrapper ).on( 'click', function () {
				fakeInput.focus();
			} );

			// Import pre-existing tags from the real input's initial value
			getTags().forEach( addTag );
		} );
	};

} )( jQuery );
