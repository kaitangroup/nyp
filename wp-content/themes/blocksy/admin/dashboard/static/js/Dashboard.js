import {
	useRef,
	createElement,
	useState,
	useEffect,
	useMemo,
	Component,
} from '@wordpress/element'
import DashboardContext, { Provider, getDefaultValue } from './context'
import Heading from './Heading'
import {
	createHashRouter,
	RouterProvider,
	Link,
	useLocation,
	useNavigate,
	useMatch,
	useParams,
	Outlet,
	createRoutesFromElements,
	Route,
} from 'react-router-dom'
import ctEvents from 'ct-events'

window.ctDashboardLocalizations.DashboardContext = DashboardContext

import Navigation from './Navigation'
import Home from './screens/Home'
import RecommendedPlugins from './screens/RecommendedPlugins'
import Changelog from './screens/Changelog'

class ErrorBoundary extends Component {
	state = { hasError: false, error: null }

	static getDerivedStateFromError(error) {
		return { hasError: true, error }
	}

	componentDidCatch(error, errorInfo) {
		console.error('Dashboard Route Error:', error, errorInfo)
		if (this.props.onError) {
			this.props.onError(error, errorInfo)
		}
	}

	render() {
		if (this.state.hasError) {
			return null
		}

		return this.props.children
	}
}

const RouteComponentWrapper = ({ Component, ...props }) => {
	const navigate = useNavigate()
	const location = useLocation()
	const params = useParams()
	const match = useMatch('*')

	return (
		<ErrorBoundary>
			<Component
				{...props}
				{...params}
				navigate={navigate}
				location={location}
				match={match}
			/>
		</ErrorBoundary>
	)
}

const AnimatedOutlet = () => {
	return (
		<div>
			<Outlet />
		</div>
	)
}

const DashboardLayout = () => {
	const navigate = useNavigate()
	const location = useLocation()
	const match = useMatch

	return (
		<Provider
			value={{
				...getDefaultValue(),
				...ctDashboardLocalizations,
				Link,
				useLocation,
				navigate,
				useMatch: match,

				// Add compatibility props that might be expected
				history: { push: navigate, location },
				location,
			}}>
			<header>
				<Heading />
				<Navigation />
			</header>

			<section>
				<AnimatedOutlet />
			</section>
		</Provider>
	)
}

const createDashboardRouter = () => {
	const userRoutes = []

	ctEvents.trigger('ct:dashboard:routes', userRoutes)

	return createHashRouter(
		createRoutesFromElements(
			<Route path="/" element={<DashboardLayout />}>
				<Route index element={<Home />} />
				<Route path="plugins" element={<RecommendedPlugins />} />
				<Route path="changelog" element={<Changelog />} />

				{userRoutes.map(({ Component, key, path, ...props }) => (
					<Route
						key={key || path}
						path={path}
						element={
							<RouteComponentWrapper
								Component={Component}
								{...props}
							/>
						}
					/>
				))}
			</Route>
		)
	)
}

const Dashboard = () => {
	const router = useMemo(() => createDashboardRouter(), [])

	return (
		<RouterProvider
			router={router}
			future={{
				v7_startTransition: true,
			}}
		/>
	)
}

export default Dashboard
