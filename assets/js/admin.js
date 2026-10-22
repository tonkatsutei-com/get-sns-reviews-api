
( function () {
	'use strict';
	var support = window.getsnsrevSupport || {};
	var STORAGE_PREFIX = 'getsnsrev_keyword_';
	var SERVICE_STORAGE_KEY = 'getsnsrev_service';
	document.addEventListener( 'DOMContentLoaded', function () {
		var apiKeyInputs = document.querySelectorAll( '.getsnsrev-api-key[data-masked="1"]' );
		Array.prototype.forEach.call( apiKeyInputs, function ( input ) {
			input.addEventListener( 'focus', function () {
				input.select();
			} );
		} );
		var service = document.getElementById( 'getsnsrev-reviews-service' );
		var maxRevs = document.getElementById( 'getsnsrev-reviews-max' );
		var noCache = document.getElementById( 'getsnsrev-no-cache' );
		var keywordInputs = document.querySelectorAll( '.getsnsrev-reviews-test__sns-row input[name="getsnsrev_keyword"]' );
		var setValues = function ( selector, value ) {
			var inputs = document.querySelectorAll( selector );
			Array.prototype.forEach.call( inputs, function ( input ) {
				input.value = value;
			} );
		};
		var syncControls = function () {
			if ( service ) {
				setValues( '.getsnsrev-service-sync', service.value );
			}
			if ( maxRevs ) {
				setValues( '.getsnsrev-max-sync', maxRevs.value );
			}
			if ( noCache ) {
				setValues( '.getsnsrev-no-cache-sync', noCache.checked ? '1' : '' );
			}
		};
		var syncDisabled = function () {
			if ( ! service ) {
				return;
			}
			Array.prototype.forEach.call(
				document.querySelectorAll( '.getsnsrev-reviews-test__sns-row' ),
				function ( form ) {
					var snsInput = form.querySelector( 'input[name="getsnsrev_sns"]' );
					var button = form.querySelector( 'button[type="submit"]' );
					if ( ! snsInput || ! button ) {
						return;
					}
					var sns = snsInput.value;
					var map = support[ service.value ];
					var supported = ! map || map[ sns ] !== false;
					button.disabled = ! supported;
					button.title = supported ? '' : 'この組み合わせでは取得できません（対応サービス外です）。';
				}
			);
		};
		var syncKeywords = function () {
			Array.prototype.forEach.call( keywordInputs, function ( input ) {
				var sns = input.getAttribute( 'id' ).replace( 'getsnsrev-reviews-keyword-', '' );
				var hidden = document.querySelector( '.getsnsrev-keyword-sync[data-sns="' + sns + '"]' );
				if ( hidden ) {
					hidden.value = input.value;
				}
				try {
					window.localStorage.setItem( STORAGE_PREFIX + sns, input.value );
				} catch ( e ) {
				}
			} );
		};
		Array.prototype.forEach.call( keywordInputs, function ( input ) {
			var sns = input.getAttribute( 'id' ).replace( 'getsnsrev-reviews-keyword-', '' );
			try {
				var saved = window.localStorage.getItem( STORAGE_PREFIX + sns );
				if ( saved !== null && saved !== '' ) {
					input.value = saved;
				}
			} catch ( e ) {
			}
			input.addEventListener( 'input', syncKeywords );
		} );
		if ( service ) {
			try {
				var savedService = window.localStorage.getItem( SERVICE_STORAGE_KEY );
				if ( savedService !== null && savedService !== '' ) {
					var hasOption = false;
					Array.prototype.forEach.call( service.options, function ( option ) {
						if ( option.value === savedService ) {
							hasOption = true;
						}
					} );
					if ( hasOption ) {
						service.value = savedService;
					}
				}
			} catch ( e ) {
			}
			service.addEventListener( 'change', function () {
				try {
					window.localStorage.setItem( SERVICE_STORAGE_KEY, service.value );
				} catch ( e ) {
				}
			} );
			service.addEventListener( 'change', syncControls );
			service.addEventListener( 'change', syncDisabled );
		}
		if ( maxRevs ) {
			maxRevs.addEventListener( 'change', syncControls );
		}
		if ( noCache ) {
			noCache.addEventListener( 'change', syncControls );
		}
		syncControls();
		syncDisabled();
		syncKeywords();
		var bulkForm = document.querySelector( '.getsnsrev-reviews-test__bulk' );
		if ( bulkForm ) {
			bulkForm.addEventListener( 'submit', function () {
				var button = bulkForm.querySelector( 'button[type="submit"]' );
				if ( ! button ) {
					return;
				}
				button.disabled = true;
				button.classList.add( 'is-loading' );
				button.textContent = button.getAttribute( 'data-fetching-label' ) || '取得中…';
			} );
		}
	} );
} )();
