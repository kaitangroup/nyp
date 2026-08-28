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

	document.addEventListener('packlink:dropoff-selected', function (e) {
		document.querySelectorAll('#packlink-drop-off-picker').forEach(function (btn) {
			btn.innerHTML = e.detail.buttonText;
		});
		document.querySelectorAll('input[name="packlink_drop_off_id"]').forEach(function (input) {
			input.value = e.detail.location.id;
		});
		document.querySelectorAll('input[name="packlink_drop_off_extra"]').forEach(function (input) {
			input.value = JSON.stringify(e.detail.location);
		});
	});

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

		if ( ! hookedUpdate && jQuery) {
			if (updateButton) {
				jQuery( document.body ).on( 'updated_wc_div', initialize );
			}

			// Re-initialize after every checkout refresh. The inline init scripts inside
			// shipping-rate fragments are not executed when a later fragment replaces an
			// ancestor of an earlier one (CartFlows applies the same shipping-methods HTML
			// under two selectors), so the surviving drop-off button would stay hidden.
			jQuery( document.body ).on( 'updated_checkout', initialize );
			hookedUpdate = true;
		}

		// Restores the saved selection (hidden inputs and address line) into freshly
		// rendered shipping rows. setDropOffAddress defers on its own while the initial
		// HTML is still being parsed, so calling it directly is safe both during page
		// load and when re-initialized after a checkout fragment refresh.
		setDropOffAddress();
	}

	let dropOffAddressScheduled = false;

	function setDropOffAddress() {
		if (document.readyState === 'loading') {
			scheduleDropOffAddress();

			return;
		}

		applyDropOffAddress();
	}

	/**
	 * The inline template script runs while the checkout HTML is still being parsed,
	 * before WooCommerce's destination element exists. Applying as soon as that element
	 * is parsed (instead of waiting for DOMContentLoaded) keeps the destination line
	 * from visibly jumping from its default position to below the drop-off button.
	 */
	function scheduleDropOffAddress() {
		if (dropOffAddressScheduled) {
			return;
		}

		dropOffAddressScheduled = true;

		let observer = new MutationObserver( function () {
			if (document.querySelector( 'p.woocommerce-shipping-destination' )) {
				observer.disconnect();
				applyDropOffAddress();
			}
		} );

		observer.observe( document.documentElement, { childList: true, subtree: true } );

		// Fallback for pages where the destination element never renders.
		document.addEventListener(
			'DOMContentLoaded',
			function () {
				observer.disconnect();
				applyDropOffAddress();
			},
			{ once: true }
		);
	}

	function applyDropOffAddress() {
		if ( ! privateData.selectedLocation || privateData.isCart) {
			return;
		}

		let selected = findLocationById( privateData.selectedLocation );

		if ( ! selected) {
			return;
		}

		setHiddenFields( selected );
		// Only buttons rendered inside a shipping-rate list item: the block-checkout
		// template copy printed into the page footer must never receive the address line.
		let buttons = Array.prototype.filter.call(
			document.querySelectorAll( '#packlink-drop-off-picker' ),
			function (button) {
				return button.closest( 'li' );
			}
		);
		// Populate the destination element with DOM nodes instead of an HTML string,
		// so the carrier-supplied location fields can never be reinterpreted as HTML.
		let renderAddress = function ( target ) {
			let title = document.createElement( 'strong' );
			title.textContent = privateData.translations.dropOffTitle;

			target.textContent = '';
			target.appendChild( title );
			target.appendChild( document.createElement( 'br' ) );
			target.appendChild(
				document.createTextNode( [selected.name, selected.address, selected.city].join( ', ' ) )
			);
		};

		// WooCommerce's own destination line (rendered after the rate list) is reused and
		// moved under a button instead of duplicated. It can be claimed only once — and
		// not at all when a previous run already placed it under a picker button.
		let pageElement = document.querySelector( 'p.woocommerce-shipping-destination' );
		if (pageElement && pageElement.previousElementSibling
			&& pageElement.previousElementSibling.id === 'packlink-drop-off-picker') {
			pageElement = null;
		}

		buttons.forEach( function (button) {
			let element = button.parentNode.querySelector( 'p.woocommerce-shipping-destination' );

			if ( ! element && pageElement) {
				element     = pageElement;
				pageElement = null;
			}

			if ( ! element) {
				element           = document.createElement( 'p' );
				element.className = 'woocommerce-shipping-destination';
			}

			renderAddress( element );
			button.parentNode.insertBefore( element, button.nextSibling );
		});

		if (buttons.length === 0) {
			let element = document.querySelector( 'p.woocommerce-shipping-destination' );
			if (element) {
				renderAddress( element );
			}
		}
	}

	function setHiddenFields(location) {
		let dropOffIds    = document.querySelectorAll('input[name="packlink_drop_off_id"]');
		let dropOffExtras = document.querySelectorAll('input[name="packlink_drop_off_extra"]');

		dropOffIds.forEach( function (input) {
			input.value = location.id;
		});
		dropOffExtras.forEach( function (input) {
			input.value = JSON.stringify( location );
		});
	}

	function addCODMessage(dataDiv, codName, codFee) {
		if (!dataDiv) return;
		if (!codName || !codFee || codFee <= 0) return;

		if (dataDiv.querySelector('.packlink-cod-message')) return;

		const messageDiv = document.createElement('div');
		messageDiv.className = 'packlink-cod-message';
		// Build with text nodes so codName/codFee can never be reinterpreted as HTML.
		appendCODMessageContent(messageDiv, codName, codFee);
		messageDiv.style.marginTop = '8px';
		messageDiv.style.fontSize = '12px';
		messageDiv.style.color = '#555';

		dataDiv.lastChild.before(messageDiv);
	}

	/**
	 * Appends the COD notice text to a container using text nodes only, so the
	 * payment-method name and fee are never interpreted as HTML.
	 *
	 * @param {HTMLElement} container
	 * @param {string} codName
	 * @param {number|string} codFee
	 */
	function appendCODMessageContent(container, codName, codFee) {
		let nameStrong1 = document.createElement( 'strong' );
		nameStrong1.textContent = codName;
		let nameStrong2 = document.createElement( 'strong' );
		nameStrong2.textContent = codName;
		let feeStrong = document.createElement( 'strong' );
		feeStrong.textContent = codFee;

		container.appendChild( document.createTextNode( 'This service supports ' ) );
		container.appendChild( nameStrong1 );
		container.appendChild( document.createTextNode( '. If you choose the ' ) );
		container.appendChild( nameStrong2 );
		container.appendChild( document.createTextNode( ' payment method, an additional fee of ' ) );
		container.appendChild( feeStrong );
		container.appendChild( document.createTextNode( ' will be applied.' ) );
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
		// Only accept http(s) logo URLs; ignore anything else (data:, javascript:, etc.).
		if ( /^https?:\/\//i.test( imageSrcInput.value ) ) {
			image.src = imageSrcInput.value;
		}
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
						document.querySelectorAll( '#packlink-drop-off-picker' ).forEach(
							function (button) {
								button.innerHTML = privateData.translations.changeDropOff;
							}
						);

						if ( ! privateData.isCart) {
							setHiddenFields( selected );
						}

						document.dispatchEvent( new CustomEvent( 'packlink:dropoff-selected', {
							detail: {
								locationId: id,
								location: selected,
								buttonText: privateData.translations.changeDropOff
							}
						}));
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
