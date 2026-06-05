import { sprintf, __ } from 'ct-i18n'
export const customItemsSeparator = () => '~'
export const getOriginalId = (id) => id.split('~')[0]

export const shortenItemId = (id) => {
	let components = id.split(customItemsSeparator())

	if (components.length === 1) {
		return components[0]
	}

	return components[1].substring(0, 6)
}

export const formatUnknownElement = (itemId) => {
	const itemsTitles = {
		'content-block': __('Content Block', 'blocksy'),
		contacts: __('Contacts', 'blocksy'),
		divider: __('Divider', 'blocksy'),
		'language-switcher': __('Languages', 'blocksy'),
		'menu-tertiary': __('Menu 3', 'blocksy'),
		'mobile-menu-secondary': __('Mobile Menu 2', 'blocksy'),
		'search-input': __('Search Box', 'blocksy'),
		'widget-area-1': __('Widget Area', 'blocksy'),
		'menu-secondary': __('Footer Menu 2', 'blocksy'),
		account: __('Account', 'blocksy'),
		'color-mode-switcher': __('Color Switch', 'blocksy'),
		'wish-list': __('Wishlist', 'blocksy'),
		compare: __('Compare', 'blocksy'),
		logo: __('Logo', 'blocksy'),
	}

	if (itemsTitles[itemId]) {
		return itemsTitles[itemId]
	}

	return itemId
}
