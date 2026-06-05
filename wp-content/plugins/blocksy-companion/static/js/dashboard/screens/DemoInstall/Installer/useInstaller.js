import {
	createElement,
	Component,
	useEffect,
	useState,
	useRef,
	useCallback,
	useMemo,
	createContext,
	useContext,
	Fragment,
} from '@wordpress/element'
import DashboardContext from '../../../DashboardContext'
import { DemosContext } from '../../DemoInstall'

import { sprintf, __ } from 'ct-i18n'
import { getNameForPlugin, getPluginsMap } from '../Wizzard/Plugins'
import {
	prepareUrl,
	performRequestWithExponentialBackoff,
	GENERIC_MESSAGE,
} from './helpers'

const listener = (e) => {
	e.preventDefault()
	e.returnValue = ''
}

export const getStepsForDemoConfiguration = ({
	demoConfiguration,
	pluginsStatus,
	is_child_theme,
	includeMetaSteps = false,
}) => {
	let steps = []

	if (includeMetaSteps) {
		steps.push('register_current_demo')
	}

	if (demoConfiguration.child_theme) {
		if (!is_child_theme) {
			steps.push('child_theme')
		}
	}

	if (
		demoConfiguration.plugins.filter(
			({ enabled, plugin }) => !!enabled && !pluginsStatus[plugin]
		).length > 0
	) {
		steps.push('plugins')
	}

	if (demoConfiguration.content.erase_content) {
		steps.push('erase_content')
	}

	if (demoConfiguration.content.options) {
		steps.push('options')
	}

	if (demoConfiguration.content.widgets) {
		steps.push('widgets')
	}

	if (demoConfiguration.content.content) {
		steps.push('content')
	}

	if (includeMetaSteps) {
		steps.push('install_finish')
	}

	return steps
}

const getInitialStepsDescriptors = (params) => {
	const {
		currentDemoWithVariation,
		demoConfiguration,

		pluginsStatus,
		demoContent,
	} = params
	const pluginsToActivate = demoConfiguration.plugins
		.filter(({ enabled, plugin }) => enabled && !pluginsStatus[plugin])
		.map(({ plugin }) => plugin)

	return {
		register_current_demo: {
			requests: [
				{
					title: __('Preparing data...', 'blocksy-companion'),
					params: {
						action: 'blocksy_demo_register_current_demo',
						demo_name: currentDemoWithVariation,
					},
				},
			],
		},

		child_theme: {
			requests: [
				{
					title: __('Child theme', 'blocksy-companion'),
					params: {
						action: 'blocksy_demo_install_child_theme',
					},
				},
			],
		},

		plugins: {
			requests: pluginsToActivate.map((plugin) => ({
				title: sprintf(
					__('Installing %s', 'blocksy-companion'),
					getNameForPlugin(plugin)
				),

				params: {
					action: 'blocksy_demo_activate_plugins',
					plugins: plugin,
					plugin_name: getPluginsMap()[plugin] || plugin,
				},
			})),
		},

		erase_content: {
			requests: [
				{
					title: __('Preparing content', 'blocksy-companion'),

					params: {
						action: 'blocksy_demo_erase_content',
					},
				},
			],
		},

		options: {
			requests: [
				{
					title: __('Import options', 'blocksy-companion'),

					params: {
						action: 'blocksy_demo_install_options',
						demo_name: currentDemoWithVariation,
					},
				},
			],
		},

		widgets: {
			requests: [
				{
					title: __('Import widgets', 'blocksy-companion'),

					params: {
						action: 'blocksy_demo_install_widgets',
						demo_name: currentDemoWithVariation,
					},

					cleanup: {
						params: {
							action: 'blocksy_demo_erase_content',
							to_erase: 'widgets',
						},
					},
				},
			],
		},

		content: {
			requests: [
				{
					title: __('Import content', 'blocksy-companion'),
					params: {
						action: 'blocksy_demo_install_content',
						demo_name: currentDemoWithVariation,
					},

					cleanup: {
						params: {
							action: 'blocksy_demo_erase_content',
							to_erase: 'content',
						},
					},
				},
			],
		},

		install_finish: {
			requests: [
				{
					title: __('Final touches', 'blocksy-companion'),

					params: {
						action: 'blocksy_demo_install_finish',
					},
				},
			],
		},
	}
}

export const useInstaller = (demoConfiguration) => {
	const {
		demos_list,
		currentDemo,
		setInstallerBlockingReleased,
		setCurrentlyInstalledDemo,
		pluginsStatus,
	} = useContext(DemosContext)

	const { is_child_theme } = useContext(DashboardContext)

	const [isCompleted, setIsCompleted] = useState(false)
	const [lastMessage, setLastMessage] = useState(null)
	const [isError, setIsError] = useState(false)
	const [progress, setProgress] = useState(0)

	const currentDemoWithVariation = useMemo(() => {
		const [properDemoName, _] = (currentDemo || '').split(':')
		const demoVariations = demos_list
			.filter(({ name }) => name === properDemoName)
			.sort((a, b) => {
				if (a.builder < b.builder) {
					return -1
				}

				if (a.builder > b.builder) {
					return 1
				}

				return 0
			})

		return `${currentDemo}:${
			demoConfiguration.builder === null
				? demoVariations[0].builder
				: demoConfiguration.builder
		}`
	}, [currentDemo, demoConfiguration, demos_list])

	const stepsForConfiguration = useMemo(
		() =>
			getStepsForDemoConfiguration({
				demoConfiguration,
				pluginsStatus,
				is_child_theme,
				includeMetaSteps: true,
			}),
		[demoConfiguration, pluginsStatus, is_child_theme]
	)

	const processSteps = useCallback(
		async (demoContent) => {
			const stepsDescriptors = getInitialStepsDescriptors({
				currentDemoWithVariation,
				demoConfiguration,
				pluginsStatus,
				demoContent,
			})

			const totalSteps = stepsForConfiguration.length

			let processedSteps = 0

			let requestsPayload = {}

			const demoInstallFinish = (success, errorMessage = null) => {
				console.timeEnd('Blocksy:Dashboard:DemoInstall')

				if (success) {
					setIsCompleted(true)
				} else {
					setIsError(errorMessage)
				}

				setInstallerBlockingReleased(true)
				window.removeEventListener('beforeunload', listener)
			}

			for (const step of stepsForConfiguration) {
				const stepDescriptor = stepsDescriptors[step]

				if (!stepDescriptor || stepDescriptor.requests.length === 0) {
					continue
				}

				for (const request of stepDescriptor.requests) {
					setLastMessage(request.title)

					const isContentRequest =
						request.params.action === 'blocksy_demo_install_content'

					try {
						const result = await performRequestWithExponentialBackoff(
							request,
							requestsPayload,
							isContentRequest
								? {
										strategy: 'sse',
										onStatus: (statusMessage) => {
											setLastMessage(statusMessage)
										},
									}
								: {}
						)

						requestsPayload = result.requestsPayload
					} catch (e) {
						demoInstallFinish(false, e.message)
						return
					}
				}

				processedSteps++

				setProgress((processedSteps / totalSteps) * 100)

				if (totalSteps === processedSteps) {
					demoInstallFinish(true)
				}
			}
		},
		[stepsForConfiguration, setProgress]
	)

	const prepareData = useCallback(async () => {
		window.addEventListener('beforeunload', listener)

		console.time('Blocksy:Dashboard:DemoInstall')

		setCurrentlyInstalledDemo({
			demo: `${currentDemo}:${demoConfiguration.builder}`,
		})

		setLastMessage(__('Preparing data...', 'blocksy-companion'))

		const response = await fetch(
			prepareUrl({
				action: 'blocksy_demo_get_content_preliminary_data',
				demo_name: currentDemoWithVariation,
			})
		)

		if (response.status === 200) {
			const body = await response.json()

			if (!body.success) {
				setIsError(body.data.message || GENERIC_MESSAGE)
				return
			}

			processSteps(body.data)
		}
	}, [processSteps, currentDemoWithVariation])

	useEffect(() => {
		prepareData()
	}, [])

	return {
		isCompleted,
		isError,
		lastMessage,
		progress,
	}
}
