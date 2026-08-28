var Packlink = window.Packlink || {};

document.addEventListener(
	'DOMContentLoaded',
	function () {
		let createDraftButton   = document.querySelector( '#pl-create-draft' );
		let createDraftEndpoint = document.querySelector( '#pl-create-endpoint' );

		if (createDraftButton && createDraftEndpoint) {
			createDraftButton.addEventListener(
				'click',
				function () {
					let orderId = parseInt( createDraftButton.value );

					createDraftButton.disabled = true;
					Packlink.ajaxService.post( createDraftEndpoint.value, {id: orderId}, reload, reload );
				}
			);
		}

		// Documents dropdown — event delegation so it works regardless of when the meta-box renders.
		document.addEventListener(
			'click',
			function (event) {
				let trigger = event.target.closest( '.pl-doc-btn' );
				if (trigger) {
					event.preventDefault();
					event.stopPropagation();

					let menu = trigger.parentNode.querySelector( '.pl-dropdown-menu' );
					if (!menu) {
						return;
					}

					let wasOpen = menu.classList.contains( 'pl-open' );
					closeAllDropdowns();
					if (!wasOpen) {
						menu.classList.add( 'pl-open' );
					}
					return;
				}

				let printAction = event.target.closest( '.pl-print-action' );
				if (printAction) {
					event.preventDefault();
					event.stopPropagation();

					let printUrl = printAction.getAttribute( 'data-print-url' );
					closeAllDropdowns();

					if (window.Packlink && Packlink.printService && printUrl) {
						Packlink.printService.printPdf( printUrl );
					} else if (printUrl) {
						window.open( printUrl, '_blank' );
					}
					return;
				}

				// Click landed outside any open dropdown — close them.
				if (!event.target.closest( '.pl-document-dropdown' )) {
					closeAllDropdowns();
				}
			}
		);

		function closeAllDropdowns() {
			let openMenus = document.querySelectorAll( '.pl-dropdown-menu.pl-open' );
			[].forEach.call(
				openMenus,
				function (menu) {
					menu.classList.remove( 'pl-open' );
				}
			);
		}

		function reload() {
			location.reload( true );
		}
	}
);
