var Packlink = window.Packlink || {};

document.addEventListener(
	'DOMContentLoaded',
	function () {
		let createDraftEndpoint = document.querySelector('#pl-create-endpoint'),
			checkManualSyncStatusEndpoint = document.querySelector('#pl-check-manual-sync-status'),
			checkDraftStatusEndpoint = document.querySelector('#pl-check-status'),
			draftInProgressMessage = document.querySelector('#pl-draft-in-progress'),
			draftFailedMessage = document.querySelector('#pl-draft-failed'),
			draftButtonTemplate = document.querySelector('#pl-draft-button-template'),
			createDraftTemplate = document.querySelector('#pl-create-draft-template'),
			createDraftButtons = document.getElementsByClassName('pl-create-draft-button'),
			draftsInProgress = document.getElementsByClassName('pl-draft-in-progress');

		for (let createDraftButton of createDraftButtons) {
			createDraftButton.addEventListener('click', function (event) {
				event.preventDefault();

				createDraft(createDraftButton);
			});
		}

		for (let draftInProgress of draftsInProgress) {
			let orderId = draftInProgress.getAttribute('data-order-id'),
				parent = draftInProgress.parentElement;

			checkDraftStatus(parent, orderId);
		}

		function createDraft(createDraftButton) {
			let orderId = parseInt(createDraftButton.getAttribute('data-order-id'));

			Packlink.ajaxService.post(createDraftEndpoint.value, {id: orderId}, function () {
				checkManualSyncStatus(createDraftButton, orderId);
			});
		}

		function checkManualSyncStatus(createDraftButton, orderId) {
			Packlink.ajaxService.get(checkManualSyncStatusEndpoint.value, function (response) {
				if (response.manual_sync_status) {
					let arrayOfParameters = ['packlink-hide-success-notice', '_packlink_success_notice_nonce',
						'packlink-hide-error-notice', '_packlink_error_notice_nonce'];
					location.href = removeParametersFromUrl(arrayOfParameters);
				} else {
					let buttonParent = createDraftButton.parentElement;

					buttonParent.removeChild(createDraftButton);
					buttonParent.innerText = draftInProgressMessage.value;
					checkDraftStatus(buttonParent, orderId);
				}
			});
		}

		function removeParametersFromUrl(arrayOfParameters) {
			let url = new URL(document.location);
			for (let i = 0; i < arrayOfParameters.length; i++) {
				url.searchParams.delete(arrayOfParameters[i]);
			}

			return url.href;
		}

		function checkDraftStatus(parent, orderId) {
			clearTimeout(function () {
				checkDraftStatus(parent, orderId);
			});

			Packlink.ajaxService.get(checkDraftStatusEndpoint.value + '&order_id=' + orderId, function (response) {
				noShippableItemsMessage = document.querySelector('#pl-no-shippable-items').value;

				if (response.status === 'created') {
					let viewDraftButton = draftButtonTemplate.cloneNode(true);

					viewDraftButton.id = '';
					viewDraftButton.href = response.shipment_url;
					viewDraftButton.classList.remove('hidden');
					parent.innerHTML = '';
					parent.appendChild(viewDraftButton);
				} else if (response.status === 'aborted') {
					parent.innerText = noShippableItemsMessage;
					setTimeout(function () {
						displayCreateDraftButton(parent, orderId)
					}, 5000);
				} else if (response.status === 'failed') {
					parent.innerText = draftFailedMessage.value;
					setTimeout(function () {
						displayCreateDraftButton(parent, orderId)
					}, 5000)
				} else {
					setTimeout(function () {
						checkDraftStatus(parent, orderId)
					}, 1000);
				}
			});
		}

		function displayCreateDraftButton(parent, orderId) {
			clearTimeout(function () {
				displayCreateDraftButton(parent, orderId)
			});

			let createDraftButton = createDraftTemplate.cloneNode(true);

			createDraftButton.id = '';
			createDraftButton.classList.remove('hidden');
			createDraftButton.setAttribute('data-order-id', orderId);

			createDraftButton.addEventListener('click', function (event) {
				event.preventDefault();

				createDraft(createDraftButton);
			});

			parent.innerHTML = '';
			parent.appendChild(createDraftButton);
		}
	}
);
