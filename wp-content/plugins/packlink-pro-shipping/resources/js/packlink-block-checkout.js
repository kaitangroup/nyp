var Packlink = window.Packlink || {};
window.onload = () => {
	const maxAttempts = 20;
	let attempts = 0;

	const waitForShippingOptions = setInterval(() => {
		const container = document.querySelector('.wc-block-components-shipping-rates-control');
		if (container) {
			clearInterval(waitForShippingOptions);
			Packlink.blockCheckout.init();
		}

		if (++attempts > maxAttempts) {
			clearInterval(waitForShippingOptions);
		}
	}, 500);
};
(function () {
	let modal;
	let closeButton;
	let methodDetails;
	let privateData = {
		locations: [],
		endpoint: null,
		selectedLocation: null,
		isCart: false,
		translations: {},
		locale: 'en',
		methodDetails: [],
		isSingleShippingMethod: false,
		isObserverSet: false,
		isObserverExecuted: false
	};
	Packlink.blockCheckout = {};
	Packlink.blockCheckout.init = initialize;

	/**
	 * Initialize Packlink shipping methods on block checkout.
	 */
	function initialize() {
		const shippingOptions = document.getElementsByClassName('wc-block-components-shipping-rates-control')
			.item(0).children[0].children[0];
		if (!shippingOptions) {
			return;
		}

		if (!privateData.isObserverSet) {
			privateData.isObserverSet = true;
			addMutationObserverToCheckoutBlock(shippingOptions?.parentElement?.parentElement?.parentElement?.parentElement?.parentElement);
		}

		const initializeBlockCheckout = document.getElementById('pl-block-checkout-initialize-endpoint').value;
		const saveEndpoint = document.getElementById('pl-block-checkout-save-selected').value;

		if (!initializeBlockCheckout || !saveEndpoint) {
			return;
		}

		setSaveEndpoint(saveEndpoint);
		const shippingMethodsIds = getShippingMethodIds(shippingOptions);
		Packlink.ajaxService.post(
			initializeBlockCheckout,
			shippingMethodsIds,
			function (response) {
				setLocale(response['locale'] || 'en');
				setSelectedLocationId(response['selected_shipping_method']);
				setTranslations({...response['translations']});
				setNoDropOffLocationsMessage(response['no_drop_off_locations_message']);
				privateData.offlinePaymentName = response['offline_payment_name'] || null;
				privateData.methodDetails = Object.entries(response['method_details']);
				Array.from(privateData.methodDetails).forEach(details => {
					let option, dataDiv;
					if (privateData.methodDetails.length > 1) {
						privateData.isSingleShippingMethod = false;
						const inputValue = 'packlink_shipping_method:' + details[0];
						option = document.querySelector("input[value='" + inputValue + "']");
						dataDiv = option.parentElement.querySelector("div[class='wc-block-components-radio-control__label-group']")
					} else {
						privateData.isSingleShippingMethod = true;
						dataDiv = shippingOptions.querySelector("div[class='wc-block-components-radio-control__label-group']");
						option = dataDiv;
					}

					if (option === null) {
						return;
					}

					if (details[1]['packlink_show_image']) {
						injectImage(option, details[1]['packlink_image_url']);
					}

					if (option.checked || privateData.isSingleShippingMethod) {
						addCODMessage(dataDiv, details[1]);
					}

					if ((option.checked || privateData.isSingleShippingMethod) && details[1]['packlink_is_drop_off']) {
						addDropOffButton(dataDiv, details[1]);
					}

					if (!privateData.isSingleShippingMethod) {
						option.addEventListener('click', () => {
							document.querySelectorAll('.packlink-cod-message').forEach(el => el.remove());

							privateData.selectedLocation = null;
							const dropOff = addDropOffButton(dataDiv, details[1]);
							const dropOffButton = document.getElementById('packlink-drop-off-picker');
							const dropOffAddress = document.querySelector('div.woocommerce-shipping-destination');
							if (dropOffAddress) {
								dropOffAddress.remove();
							}

							if (details[1]['packlink_is_drop_off']) {
								dataDiv.lastChild.before(dropOff);
								dropOffButton.innerHTML = privateData.translations.pickDropOff;
								dropOffButton.removeAttribute('style');
							} else {
								dropOffButton.setAttribute('style', 'display: none;');
							}

							addCODMessage(dataDiv, details[1]);

							//unset old drop off location
							Packlink.ajaxService.post(
								privateData.endpoint,
								{'id' : null},
								function () {},
								function () {}
							);
						})
					}

				});
			},
			function () {
			}
		);

		modal = document.getElementById('pl-picker-modal');
		closeButton = document.getElementById('pl-picker-modal-close');

		if (modal) {
			closeButton.addEventListener(
				'click',
				function () {
					modal.style.display = 'none';
				}
			);
		}

	}

	function addCODMessage(dataDiv, details) {
		const codName = privateData.offlinePaymentName;
		const codPrice = details['packlink_cash_on_delivery_fee'];

		if (!codName || !codPrice || codPrice <= 0) {
			return;
		}

		if (dataDiv && details['packlink_cash_on_delivery'] &&
			!dataDiv.querySelector('.packlink-cod-message')) {

			const messageDiv = document.createElement('div');
			messageDiv.className = 'packlink-cod-message';

			messageDiv.innerHTML = `This service supports <strong>${codName}</strong>. If you choose the <strong>${codName}</strong> payment method, an additional fee of <strong>${codPrice}</strong> will be applied.`;

			messageDiv.style.marginTop = '8px';
			messageDiv.style.fontSize = '12px';
			messageDiv.style.color = '#555';

			dataDiv.lastChild.before(messageDiv);
		}
	}

	function addDropOffButton(dataDiv, details) {
		let dropOffButton = document.getElementById('packlink-drop-off-picker');
		if (dropOffButton === null) {
			dropOffButton = document.createElement('button');
			dropOffButton.id = 'packlink-drop-off-picker';
			dropOffButton.className = 'button';
			dropOffButton.type = 'button';
			dropOffButton.innerHTML = privateData.translations.pickDropOff;
		}

		const buttonDiv = document.createElement('div');
		buttonDiv.id = 'packlink-drop-off';
		buttonDiv.appendChild(dropOffButton);
		dataDiv.lastChild.before(buttonDiv);
		dropOffButton.removeAttribute('style');
		methodDetails = details;
		dropOffButton.addEventListener('click', handleSelectDropOffLocationAction);

		return buttonDiv;
	}

	function addMutationObserverToCheckoutBlock(element) {
		const config = {childList: true};
		const callback = function (mutationsList) {
			for (const mutation of mutationsList) {
				if (mutation.type === 'childList') {
					// Check if nodes have been added
					if (!privateData.isObserverExecuted) {
						// Check if the added node is the target div
						Packlink.blockCheckout.init();
						privateData.isObserverExecuted = true;
					}
				}
			}

			privateData.isObserverExecuted = false;
		};

		const observer = new MutationObserver(callback);
		observer.observe(element, config);
	}

	/**
	 * Get IDs of shipping methods which are rendered on checkout
	 * @param shippingMethodOptions
	 * @returns {*[]}
	 */
	function getShippingMethodIds(shippingMethodOptions) {
		var ids = [];
		if (shippingMethodOptions.children.length > 1) {
			Array.from(shippingMethodOptions.children).forEach(option => {
				if (option.children.length === 0 || option.children[0].tagName !== 'INPUT') {
					return [];
				}

				if (option.children[0].value.includes('packlink_shipping_method')) {
					ids.push(parseInt(option.children[0].value.split(':')[1]));
				} else {
					option.addEventListener('click', () => {
						let dropOff = document.getElementById('packlink-drop-off');
						if (dropOff) {
							dropOff.remove();
						}
					});
				}
			});
		}

		return ids;
	}

	/**
	 * Render drop-off address.
	 */
	function setDropOffAddress() {
		if (!privateData.selectedLocation || privateData.isCart) {
			return;
		}

		let selected = findLocationById(privateData.selectedLocation);

		if (!selected) {
			return;
		}

		let button = document.querySelector('#packlink-drop-off-picker');
		let element = document.querySelector('div.woocommerce-shipping-destination');
		if (!element) {
			element = document.createElement('div');
			element.className = 'woocommerce-shipping-destination';
			element.style.fontSize = '12px';
			element.style.maxWidth = '200px';
		}

		element.innerHTML = '<strong>' + privateData.translations.dropOffTitle + '</strong><br/>'
			+ [selected.name, selected.address, selected.city].join(', ');

		if (button) {
			button.style.marginLeft = '0px';
			button.after(element);
		}
	}

	/**
	 * Sets locations.
	 *
	 * @param {array} locations
	 */
	function setLocations(locations) {
		privateData.locations = locations;
	}

	/**
	 * Sets save selected endpoint.
	 *
	 * @param {string} endpoint
	 */
	function setSaveEndpoint(endpoint) {
		privateData.endpoint = endpoint;
	}

	/**
	 * Sets save selected endpoint.
	 *
	 * @param {string} endpoint
	 */
	function setInitializeBlockCheckoutEndpoint(endpoint) {
		privateData.initializeBlockCheckoutEndpoint = endpoint;
	}

	/**
	 * Sets selected drop-off id.
	 *
	 * @param {int} locationId
	 */
	function setSelectedLocationId(locationId) {
		privateData.selectedLocation = '' + locationId;
	}

	/**
	 * Sets is cart flag.
	 *
	 * @param {boolean} isCart
	 */
	function setIsCart(isCart) {
		privateData.isCart = isCart;
	}

	/**
	 * Sets package delivery translations.
	 *
	 * @param {object} translations
	 */
	function setTranslations(translations) {
		privateData.translations = translations;
	}

	/**
	 * Sets locale.
	 *
	 * @param {string} locale
	 */
	function setLocale(locale) {
		privateData.locale = locale;
	}

	/**
	 * Returns location with provided id.
	 *
	 * @param {int|string} id
	 *
	 * @returns {object}
	 */
	function findLocationById(id) {
		id = '' + id;

		return privateData.locations.find(
			function (a) {
				return a.id === id;
			}
		);
	}

	/**
	 * Inject image into shipping method on checkout
	 *
	 * @param option
	 * @param imageSrcInput
	 */
	function injectImage(option, imageSrcInput) {
		let imageDiv = document.createElement('div');
		imageDiv.className = 'pl-image-wrapper';
		let image = document.createElement('img');
		image.src = imageSrcInput;
		image.alt = 'carrier image';
		image.className = 'pl-checkout-carrier-image';
		imageDiv.appendChild(image);

		if (privateData.isSingleShippingMethod === true && !option.firstChild.classList.contains('pl-image-wrapper')) {
			option.insertBefore(imageDiv, option.firstChild);
			return;
		}

		let label = option.nextSibling?.children[0];
		if (label && !label.children[0].classList.contains('pl-image-wrapper')) {
			label.insertBefore(imageDiv, label.children[0]);
		}
	}

	/**
	 * Initialize location picker.
	 */
	function initLocationPicker() {
		Packlink.locationPicker.display(
			privateData.locations,
			function (id) {
				let selected;

				privateData.selectedLocation = id;
				selected = findLocationById(id);
				Packlink.ajaxService.post(
					privateData.endpoint,
					selected,
					function () {
						let button = document.querySelector('#packlink-drop-off-picker');

						if (button) {
							button.innerHTML = privateData.translations.changeDropOff;
						}
					},
					function () {
					}
				);

				setDropOffAddress();

				modal.style.display = 'none';
			},
			privateData.selectedLocation,
			privateData.locale
		);
	}

	function setNoDropOffLocationsMessage(message) {
		if (document.getElementById('no-drop-off-locations-message')) {
			return;
		}

		let messageDiv = document.createElement('div');
		messageDiv.className = 'woocommerce-info';
		messageDiv.innerHTML = message;
		let noticeWrapper = document.createElement('div');
		noticeWrapper.id = 'no-drop-off-locations-message';
		noticeWrapper.appendChild(messageDiv);
		let checkoutBlockElement = document.querySelector('[data-block-name="woocommerce/checkout"]');

		if (checkoutBlockElement) {
			checkoutBlockElement.insertAdjacentElement('beforebegin', noticeWrapper);
		}

		noticeWrapper.style.display = 'none';
	}

	function handleSelectDropOffLocationAction() {
		privateData.locations = methodDetails['packlink_drop_off_locations'];
		initLocationPicker();
		let messageElement = document.getElementById('no-drop-off-locations-message');
		if (privateData.locations.length > 0) {
			modal.style.display = 'block';
		}

		if (messageElement) {
			messageElement.style.display = privateData.locations.length > 0 ? 'none' : 'block';
		}
	}
})();
