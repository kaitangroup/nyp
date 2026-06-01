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

		function reload() {
			location.reload( true );
		}
	}
);
