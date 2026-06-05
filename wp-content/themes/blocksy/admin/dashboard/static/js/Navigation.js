import { createElement, Component, useContext } from '@wordpress/element'
import { sprintf, __ } from 'ct-i18n'
import { Link, NavLink, useLocation } from 'react-router-dom'
import ctEvents from 'ct-events'

const Navigation = () => {
	const location = useLocation()
	const userNavigationLinks = []
	const endUserNavigationLinks = []

	ctEvents.trigger('ct:dashboard:navigation-links', userNavigationLinks)
	ctEvents.trigger(
		'ct:dashboard:end-navigation-links',
		endUserNavigationLinks
	)

	// Filter out @reach/router specific props and handle active states
	const filterLinkProps = (props, path) => {
		const { getProps, ...validProps } = props

		if (getProps && typeof getProps === 'function') {
			const currentPath = location.pathname

			const normalizedPath = path.startsWith('/') ? path : `/${path}`
			const normalizedCurrent = currentPath.startsWith('/')
				? currentPath
				: `/${currentPath}`

			const isPartiallyCurrent =
				normalizedCurrent.startsWith(normalizedPath) ||
				(normalizedPath !== '/' &&
					normalizedCurrent.includes(normalizedPath))

			const isCurrent =
				normalizedCurrent === normalizedPath ||
				(normalizedPath === '/' && normalizedCurrent === '/')

			const activeProps = getProps({ isPartiallyCurrent, isCurrent })

			return { ...validProps, ...activeProps }
		}

		return validProps
	}

	let hasPlugins = !ctDashboardLocalizations.plugin_data.hide_plugins_tab

	return (
		<ul className="dashboard-navigation">
			<li>
				<NavLink
					to="/"
					className={({ isActive }) => (isActive ? 'active' : '')}>
					{__('Home', 'blocksy')}
				</NavLink>
			</li>

			{userNavigationLinks.map(({ path, text, ...props }) => (
				<li key={path}>
					<NavLink
						to={path}
						{...filterLinkProps(props, path)}
						className={({ isActive }) =>
							isActive ? 'active' : ''
						}>
						{text}
					</NavLink>
				</li>
			))}

			{!ctDashboardLocalizations.plugin_data.hide_plugins_tab && (
				<li>
					<NavLink
						to="/plugins"
						className={({ isActive }) =>
							isActive ? 'active' : ''
						}>
						{__('Useful Plugins', 'blocksy')}
					</NavLink>
				</li>
			)}

			{!ctDashboardLocalizations.plugin_data.hide_changelogs_tab && (
				<li>
					<NavLink
						to="/changelog"
						className={({ isActive }) =>
							isActive ? 'active' : ''
						}>
						{__('Changelog', 'blocksy')}
					</NavLink>
				</li>
			)}

			{endUserNavigationLinks.map(({ path, text, ...props }) => (
				<li key={path}>
					<NavLink
						to={path}
						{...filterLinkProps(props, path)}
						className={({ isActive }) =>
							isActive ? 'active' : ''
						}>
						{text}
					</NavLink>
				</li>
			))}
		</ul>
	)
}

export default Navigation
