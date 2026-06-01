var Packlink = window.Packlink || {};

(function () {
	let hookedUpdate = false;
	let modal;
	let closeButton;
	let updateButton;
	let privateData = {
		locations: [],
		endpoint: null,
		selectedLocation: null,
		isCart: false,
		translations: {},
		locale: 'en'
	};

	Packlink.checkout                       	   = {};
	Packlink.checkout.init                  	   = initialize;
	Packlink.checkout.setIsCart             	   = setIsCart;
	Packlink.checkout.setLocations          	   = setLocations;
	Packlink.checkout.setLocale             	   = setLocale;
	Packlink.checkout.setTranslations       	   = setTranslations;
	Packlink.checkout.setSaveEndpoint       	   = setSaveEndpoint;
	Packlink.checkout.setDropOffAddress     	   = setDropOffAddress;
	Packlink.checkout.setSelectedLocationId 	   = setSelectedLocationId;
	Packlink.checkout.setNoDropOffLocationsMessage = setNoDropOffLocationsMessage;

	function initialize() {
		modal        = document.getElementById( 'pl-picker-modal' );
		closeButton  = document.getElementById( 'pl-picker-modal-close' );
		updateButton = document.querySelector( "[name='calc_shipping']" );
		let templates = document.getElementById("packlink-js-templates");
		[].forEach.call(
			document.getElementsByName( 'packlink_show_image' ),
			function (item) {
				let parent     = item.parentElement;
				let showImage  = item.value;
				let imageInput = parent.querySelector( 'input[name="packlink_image_url"]' );
				let button     = parent.querySelector( '#packlink-drop-off-picker' );
				let isDropOff  = parent.querySelector( 'input[name="packlink_is_drop_off"]' );

				let isCOD  = parent.querySelector('input[name="packlink_cash_on_delivery"]');

				if (isCOD && isCOD.value === 'yes') {
					const codFeeInput = parent.querySelector('input[name="packlink_cash_on_delivery_fee"]');
					let codNameInput = parent.querySelector('input[name="packlink_cash_on_delivery_name"]');
					const codName = codNameInput ? codNameInput.value : '';

					const codFee = codFeeInput ? parseFloat(codFeeInput.value) : 0;

					const shippingInput = parent.querySelector('input[name^="shipping_method"]');
					if (shippingInput && shippingInput.checked) {
						addCODMessage(parent, codName, codFee);
					}
				}

				if (showImage === 'yes' && imageInput && parent.querySelector('.pl-checkout-carrier-image') === null) {
					injectImage( imageInput );
				}

				if (isDropOff && button) {
					button.addEventListener('click', handleSelectDropOffLocationAction);
					button.removeAttribute('style');
				}
			}
		);

		if (modal) {
			closeButton.addEventListener(
				'click',
				function () {
					modal.style.display = 'none';
				}
			);

			initLocationPicker();
			let errorMessage = document.getElementById('no-drop-off-locations-message');
			if (privateData.locations.length > 0) {
				errorMessage.style.display = 'none';
			}
		}

		if ( ! hookedUpdate && updateButton && jQuery) {
			jQuery( document.body ).on( 'updated_wc_div', initialize );
			hookedUpdate = true;
		}

		document.addEventListener( 'DOMContentLoaded', setDropOffAddress );
	}

	function setDropOffAddress() {
		if ( ! privateData.selectedLocation || privateData.isCart) {
			return;
		}

		let selected = findLocationById( privateData.selectedLocation );

		if ( ! selected) {
			return;
		}

		setHiddenFields( selected );
		let button  = document.querySelector( '#packlink-drop-off-picker' );
		let element = document.querySelector( 'p.woocommerce-shipping-destination' );
		if ( ! element) {
			element           = document.createElement( 'p' );
			element.className = 'woocommerce-shipping-destination';
		}

		element.innerHTML = '<strong>' + privateData.translations.dropOffTitle + '</strong><br/>'
			+ [selected.name, selected.address, selected.city].join( ', ' );

		if (button) {
			button.parentNode.insertBefore( element, button.nextSibling );
		}
	}

	function setHiddenFields(location) {
		let dropOffId    = document.querySelector('input[name="packlink_drop_off_id"]');
		let dropOffExtra = document.querySelector('input[name="packlink_drop_off_extra"]');

		if (dropOffId && dropOffExtra) {
			dropOffId.value    = location.id;
			dropOffExtra.value = JSON.stringify( location );
		}
	}

	function addCODMessage(dataDiv, codName, codFee) {
		if (!dataDiv) return;
		if (!codName || !codFee || codFee <= 0) return;

		if (dataDiv.querySelector('.packlink-cod-message')) return;

		const messageDiv = document.createElement('div');
		messageDiv.className = 'packlink-cod-message';
		messageDiv.innerHTML = `This service supports <strong>${codName}</strong>. If you choose the <strong>${codName}</strong> payment method, an additional fee of <strong>${codFee}</strong> will be applied.`;
		messageDiv.style.marginTop = '8px';
		messageDiv.style.fontSize = '12px';
		messageDiv.style.color = '#555';

		dataDiv.lastChild.before(messageDiv);
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
	 * @param {HTMLElement} imageSrcInput
	 */
	function injectImage(imageSrcInput) {
		let image       = document.createElement( 'img' );
		image.src       = imageSrcInput.value;
		image.alt       = 'carrier image';
		image.className = 'pl-checkout-carrier-image';

		let label = imageSrcInput.parentElement.querySelector( 'label' );
		if ( label ) {
			label.prepend( image );
		} else {
			imageSrcInput.parentElement.prepend( image );
		}
	}

	function initLocationPicker() {
		Packlink.locationPicker.display(
			privateData.locations,
			function (id) {
				let selected;

				privateData.selectedLocation = id;
				selected                     = findLocationById( id );
				Packlink.ajaxService.post(
					privateData.endpoint,
					selected,
					function () {
						let button = document.querySelector( '#packlink-drop-off-picker' );

						if (button) {
							button.innerHTML = privateData.translations.changeDropOff;
						}

						if ( ! privateData.isCart) {
							setHiddenFields( selected );
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

		let info = document.createElement('div');
		info.className = 'woocommerce-info';
		info.innerHTML = message;
		let noticeWrapper = document.createElement('div');
		noticeWrapper.id = 'no-drop-off-locations-message';
		noticeWrapper.appendChild(info);
		let checkoutElement = document.querySelector('[name="checkout"]');

		if (checkoutElement) {
			checkoutElement.insertAdjacentElement('beforebegin', noticeWrapper);
		}

		noticeWrapper.style.display = 'none';
	}

	function handleSelectDropOffLocationAction() {
		initLocationPicker();
		let errorMessage = document.getElementById('no-drop-off-locations-message');
		if (privateData.locations.length > 0) {
			modal.style.display = 'block';
		}

		if (errorMessage) {
			errorMessage.style.display = privateData.locations.length > 0 ? 'none' : 'block';
		}
	}
})();
