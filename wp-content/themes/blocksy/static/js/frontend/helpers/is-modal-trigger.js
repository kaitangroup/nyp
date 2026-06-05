const modalsTriggers = [
	'.ct-offcanvas-trigger',
	'.ct-header-account[href*="account-modal"]',
	'[href="#ct-compare-modal"][data-behaviour="modal"]',
	'[data-shortcut="compare"][data-behaviour="modal"]',
]

export const isModalTrigger = (element) => {
	return modalsTriggers.some((selector) => element.matches(selector))
}
