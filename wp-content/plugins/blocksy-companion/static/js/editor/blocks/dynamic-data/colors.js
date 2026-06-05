import { getColorsDefaults } from 'blocksy-options'

export const colors = {
	textColor: '',
	customTextColor: '',

	backgroundColor: '',
	customBackgroundColor: '',

	linkColor: '',
	customLinkColor: '',
	linkHoverColor: '',
	customLinkHoverColor: '',

	overlayColor: '',
	customOverlayColor: '#000000',
}

export const colorsDefaults = getColorsDefaults(colors)
