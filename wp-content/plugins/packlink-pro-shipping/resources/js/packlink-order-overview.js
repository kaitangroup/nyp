var Packlink = window.Packlink || {};

document.addEventListener(
	'DOMContentLoaded',
	function () {
		let fallbackButtons         = document.querySelectorAll( '.pl-print-label' );
		let downloadButtons         = document.querySelectorAll( '.pl-download-label' );
		let printButtons            = document.querySelectorAll( '.pl-print-label-btn' );
		let bulkActionSelect        = document.querySelector( 'select[name="action"]' );
		let form                    = ( bulkActionSelect && bulkActionSelect.closest( 'form' ) )
			|| document.querySelector( '#posts-filter' );
		let bulkPrintUrlEl          = document.getElementById( 'pl-bulk-print-ajax-url' );
		let downloadTimer, attempts = 30;

		if (fallbackButtons) {
			[].forEach.call(
				fallbackButtons,
				function (button) {
					button.addEventListener(
						'click',
						function (event) {
							let link = button.getAttribute( 'data-pl-label' );
							event.stopPropagation();
							window.open( link, '_blank' );
						}
					);
				}
			);
		}

		[].forEach.call(
			downloadButtons,
			function (button) {
				button.addEventListener(
					'click',
					function (event) {
						event.preventDefault();
						event.stopPropagation();
						let link = button.getAttribute( 'data-pl-label' );
						window.open( link, '_blank' );
						markRowPrinted( button );
					}
				);
			}
		);

		[].forEach.call(
			printButtons,
			function (button) {
				button.addEventListener(
					'click',
					function (event) {
						event.preventDefault();
						event.stopPropagation();
						let printUrl = button.getAttribute( 'data-pl-print-url' );
						if (window.Packlink && Packlink.printService) {
							Packlink.printService.printPdf(
								printUrl,
								function () {
									markRowPrinted( button );
								}
							);
						} else {
							window.open( printUrl, '_blank' );
							markRowPrinted( button );
						}
					}
				);
			}
		);

		if (form) {
			form.addEventListener(
				'submit',
				function (event) {
					let selected = getSelectedBulkAction( form );

					if (selected === 'packlink_download_labels') {
						startCookieCheck( setFormToken( form ) );
						return;
					}

					if (selected === 'packlink_print_labels') {
						event.preventDefault();
						handleBulkPrint( form );
					}
				}
			)
		}

		/**
		 * Returns whichever of the two bulk-action selects has a non-default value, or null.
		 * Direct SELECT lookup avoids the `form.action` / `form.action2` pitfall (those names
		 * collide with HTMLFormElement's own properties).
		 *
		 * @param {HTMLFormElement} form
		 * @returns {string|null}
		 */
		function getSelectedBulkAction(form) {
			let topSelect    = form.querySelector( 'select[name="action"]' );
			let bottomSelect = form.querySelector( 'select[name="action2"]' );
			let topValue     = topSelect ? topSelect.value : '-1';
			let bottomValue  = bottomSelect ? bottomSelect.value : '-1';
			if (topValue && topValue !== '-1') {
				return topValue;
			}
			if (bottomValue && bottomValue !== '-1') {
				return bottomValue;
			}
			return null;
		}

		/**
		 * Adds the printed-state class to the .pl-label-actions wrapper that contains the given button.
		 *
		 * @param {HTMLElement} button
		 */
		function markRowPrinted(button) {
			let wrapper = button.closest( '.pl-label-actions' );
			if (wrapper) {
				wrapper.classList.add( 'pl-label-printed' );
			}
		}

		/**
		 * Intercepts the bulk "Print labels" submit, posts selected order ids to the AJAX endpoint,
		 * loads the response blob into PrintService and reloads the page once the print dialog closes.
		 *
		 * @param {HTMLFormElement} form
		 */
		function handleBulkPrint(form) {
			if (!bulkPrintUrlEl || !bulkPrintUrlEl.value) {
				window.location.reload();
				return;
			}

			// Class-based selector covers both legacy (post[]) and HPOS (id[]) checkbox names.
			let checked  = form.querySelectorAll( 'tbody .check-column input[type="checkbox"]:checked' );
			let orderIds = [];
			[].forEach.call(
				checked,
				function (input) {
					let id = parseInt( input.value, 10 );
					if (!isNaN( id ) && id > 0) {
						orderIds.push( id );
					}
				}
			);

			if (!orderIds.length) {
				window.location.reload();
				return;
			}

			form.style.cursor = 'wait';

			let xhr          = new XMLHttpRequest();
			xhr.open( 'POST', bulkPrintUrlEl.value, true );
			xhr.responseType = 'blob';
			xhr.setRequestHeader( 'Content-Type', 'application/json' );
			xhr.onload  = function () {
				form.style.cursor = 'auto';

				if (xhr.status === 200 && xhr.response && xhr.response.size > 0) {
					let contentType = xhr.getResponseHeader( 'Content-Type' ) || '';
					if (contentType.indexOf( 'application/pdf' ) !== 0) {
						window.location.reload();
						return;
					}

					let blobUrl = URL.createObjectURL( xhr.response );
					if (window.Packlink && Packlink.printService) {
						Packlink.printService.printPdf(
							blobUrl,
							function () {
								URL.revokeObjectURL( blobUrl );
								window.location.reload();
							}
						);
					} else {
						URL.revokeObjectURL( blobUrl );
						window.location.reload();
					}
					return;
				}

				window.location.reload();
			};
			xhr.onerror = function () {
				form.style.cursor = 'auto';
				window.location.reload();
			};
			xhr.send( JSON.stringify( {order_ids: orderIds} ) );
		}

		/**
		 * Sets form hidden input.
		 *
		 * @param form Form element.
		 * @returns {number | *}
		 */
		function setFormToken(form) {
			let downloadToken = document.createElement( 'input' );

			downloadToken.type  = 'hidden';
			downloadToken.name  = 'packlink_download_token';
			downloadToken.value = new Date().getTime();

			form.appendChild( downloadToken );

			return downloadToken.value;
		}

		/**
		 * Returns cookie value.
		 *
		 * @param {string} name Cookie name.
		 * @returns {string} Cookie value.
		 */
		function getCookie(name) {
			let parts = document.cookie.split( name + "=" );
			if (parts.length === 2) {
				return parts.pop().split( ";" ).shift();
			}
		}

		/**
		 * Sets cookie as expired.
		 *
		 * @param {string} cName
		 */
		function expireCookie(cName) {
			document.cookie = encodeURIComponent( cName ) + "=deleted; expires=" + new Date( 0 ).toUTCString();
		}

		/**
		 * Prevents double-submits by waiting for a cookie from the server.
		 *
		 * @param {string} downloadToken
		 */
		function startCookieCheck(downloadToken) {
			form.style.cursor = 'wait';
			downloadTimer     = window.setInterval(
				function () {
					let token = getCookie( "packlink_download_token" );

					if (token === downloadToken || attempts === 0) {
						form.style.cursor = 'auto';
						expireCookie( "packlink_download_token" );
						window.location.reload( true );
					}

					attempts--;
				},
				1000
			);
		}

	}
);
