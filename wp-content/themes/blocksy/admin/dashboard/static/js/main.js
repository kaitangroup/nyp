import { createElement, createRoot } from '@wordpress/element'
import Dashboard from './Dashboard'

document.addEventListener('DOMContentLoaded', () => {
	if (!ctDashboardLocalizations.plugin_data) {
		return
	}

	if (document.getElementById('ct-dashboard')) {
		const root = createRoot(document.getElementById('ct-dashboard'))
		root.render(<Dashboard />)
	}
})
