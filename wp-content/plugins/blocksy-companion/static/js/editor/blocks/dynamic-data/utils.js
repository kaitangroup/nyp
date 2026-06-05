import { __unstableStripHTML as stripHTML } from '@wordpress/dom'
import { __ } from 'ct-i18n'

export const fieldIsImageLike = (fieldDescriptor) => {
	if (!fieldDescriptor) {
		return false
	}

	if (fieldDescriptor.provider === 'wp') {
		return (
			fieldDescriptor.id === 'featured_image' ||
			fieldDescriptor.id === 'author_avatar' ||
			fieldDescriptor.id === 'archive_image' ||
			fieldDescriptor.id === 'term_image'
		)
	}

	return fieldDescriptor.type === 'image'
}

export const fieldIsCustomField = (fieldDescriptor) => {
	if (!fieldDescriptor) {
		return false
	}

	return (
		fieldDescriptor.provider !== 'wp' && fieldDescriptor.provider !== 'woo'
	)
}

const POSITION_CLASSNAMES = {
	'top left': 'is-position-top-left',
	'top center': 'is-position-top-center',
	'top right': 'is-position-top-right',
	'center left': 'is-position-center-left',
	'center center': 'is-position-center-center',
	center: 'is-position-center-center',
	'center right': 'is-position-center-right',
	'bottom left': 'is-position-bottom-left',
	'bottom center': 'is-position-bottom-center',
	'bottom right': 'is-position-bottom-right',
}

/**
 * Checks of the contentPosition is the center (default) position.
 *
 * @param {string} contentPosition The current content position.
 * @return {boolean} Whether the contentPosition is center.
 */
export function isContentPositionCenter(contentPosition) {
	return (
		!contentPosition ||
		contentPosition === 'center center' ||
		contentPosition === 'center'
	)
}

/**
 * Retrieves the className for the current contentPosition.
 * The default position (center) will not have a className.
 *
 * @param {string} contentPosition The current content position.
 * @return {string} The className assigned to the contentPosition.
 */
export function getPositionClassName(contentPosition) {
	/*
	 * Only render a className if the contentPosition is not center (the default).
	 */
	if (isContentPositionCenter(contentPosition)) {
		return ''
	}

	return POSITION_CLASSNAMES[contentPosition]
}

/**
 * Given a string of HTML representing serialized blocks, returns the plain
 * text extracted after stripping the HTML of any tags and fixing line breaks.
 *
 * @param {string} html Serialized blocks.
 * @return {string} The plain-text content with any html removed.
 */
export function toPlainText(html) {
	// Manually handle BR tags as line breaks prior to `stripHTML` call
	html = html.replace(/<br>/g, '\n')

	const plainText = stripHTML(html).trim()

	// Merge any consecutive line breaks
	return plainText.replace(/\n\n+/g, '\n\n')
}
