/**
 * Click-Proxy Navigation for ct-has-link-overlay
 *
 * Accessibility Notes:
 * - Keyboard users are unaffected because the actual <a> elements remain focusable
 *   and receive keyboard events normally (Tab, Enter, Space)
 * - Screen readers still announce links correctly because the DOM structure is unchanged
 * - The .ct-link-overlay anchor has pointer-events: none but is still in the DOM
 *   for screen reader discovery
 */

export const mount = (el, { event }) => {
	const overlayAnchor = el.querySelector('.ct-link-overlay')

	if (!overlayAnchor) {
		return
	}

	const href = overlayAnchor.href
	const target = overlayAnchor.target || '_self'

	if (!href) {
		return
	}

	// Ctrl (Windows/Linux) or Cmd (Mac) click should open in new tab
	const isModifierClick = event.ctrlKey || event.metaKey

	if (target === '_blank' || isModifierClick) {
		window.open(href, '_blank', 'noopener')
	} else {
		window.location.href = href
	}
}
