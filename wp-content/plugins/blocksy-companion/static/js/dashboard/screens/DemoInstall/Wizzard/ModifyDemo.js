import {
	createElement,
	Component,
	useEffect,
	useState,
	useContext,
	createContext,
	Fragment,
} from '@wordpress/element'
import { __ } from 'ct-i18n'
import classnames from 'classnames'
import { DemosContext } from '../../DemoInstall'
import DashboardContext from '../../../DashboardContext'

import { performRequestWithExponentialBackoff } from '../Installer/helpers'

import { animated } from 'blocksy-options'

const ModifyDemo = ({ style, nextStep }) => {
	const { is_child_theme } = useContext(DashboardContext)
	const {
		setCurrentlyInstalledDemo,
		setCurrentDemo,
		currentDemo,
		demos_list,

		pluginsStatus,
		setPluginsStatus,
	} = useContext(DemosContext)

	// idle | loading | done
	const [runningState, setRunningState] = useState('idle')

	const [properDemoName, _] = (currentDemo || '').split(':')

	const demoVariations = demos_list.filter(
		({ name }) => name === properDemoName
	)

	const executeRemoveSteps = async () => {
		const stepsDescriptors = {
			erase_content: {
				requests: [
					{
						title: __('Erase content', 'blocksy-companion'),
						params: {
							action: 'blocksy_demo_erase_content',
							wp_customize: 'on',
						},
					},
				],
			},

			deactivate_demo_plugins: {
				requests: [
					{
						title: __(
							'Deactivate demo plugins',
							'blocksy-companion'
						),
						params: {
							action: 'blocksy_demo_deactivate_plugins',
							plugins: demoVariations[0].plugins.join(':'),
						},
					},
				],
			},

			deregister_current_demo: {
				requests: [
					{
						title: __('Erase content', 'blocksy-companion'),
						params: {
							action: 'blocksy_demo_deregister_current_demo',
						},
					},
				],
			},
		}

		const stepsForConfiguration = [
			'erase_content',
			'deactivate_demo_plugins',
			'deregister_current_demo',
		]

		for (const step of stepsForConfiguration) {
			const stepDescriptor = stepsDescriptors[step]

			if (!stepDescriptor || stepDescriptor.requests.length === 0) {
				continue
			}

			for (const request of stepDescriptor.requests) {
				try {
					await performRequestWithExponentialBackoff(request, {})
				} catch (e) {
					console.error(`[${request.title}] Failed:`, e.message)
				}

				if (step === 'deactivate_demo_plugins') {
					setPluginsStatus({
						...pluginsStatus,

						...demoVariations[0].plugins.reduce((acc, plugin) => {
							return {
								...acc,
								[plugin]: false,
							}
						}, {}),
					})
				}
			}
		}

		setRunningState('done')
	}

	return (
		<animated.div className="ct-modify-demo" style={style}>

			{runningState === 'loading' && 
				<Fragment>
					<i className="ct-demo-icon">
						<svg width="40" height="40" viewBox="0 0 50 50">
							<path
								className="g1"
								d="M47,38.8c0.3-1,0.5-2,0.5-3.1c0-1.1-0.2-2.1-0.5-3.1l0.2-0.1l1.8-1.7l-1.8-3.1l-2.3,0.7l-0.2,0.1c-1.4-1.5-3.3-2.7-5.4-3.1V25l-0.6-2.4h-3.5L34.5,25v0.3c-2.1,0.5-4,1.6-5.4,3.1l-0.2-0.1l-2.3-0.7l-1.8,3.1l1.7,1.7l0.2,0.1c-0.3,1-0.5,2-0.5,3.1c0,1.1,0.2,2.1,0.5,3.1l-0.2,0.1l-1.8,1.7l1.8,3.1l2.3-0.7l0.2-0.1c1.4,1.5,3.3,2.7,5.4,3.1v0.3l0.6,2.4h3.5l0.6-2.4V46c2.1-0.5,4-1.6,5.4-3.1l0.2,0.1l2.3,0.7l1.8-3.1l-1.7-1.7L47,38.8z M36.9,41.5c-3.3,0-5.9-2.6-5.9-5.9s2.6-5.9,5.9-5.9s5.9,2.6,5.9,5.9S40.1,41.5,36.9,41.5z"
							/>
							<path
								className="g2"
								d="M21.2,32.2c0.2-0.8,0.4-1.7,0.4-2.5c0-0.9-0.1-1.7-0.4-2.5l0.3-0.2l1.7-1.7l-1.8-3.1L19.1,23l-0.3,0.2c-1.2-1.2-2.7-2.1-4.4-2.5v-0.3l-0.6-2.4h-3.5l-0.6,2.4v0.3c-1.7,0.4-3.2,1.3-4.4,2.5L5.1,23l-2.3-0.7L1,25.4L2.7,27L3,27.2c-0.2,0.8-0.4,1.7-0.4,2.5c0,0.9,0.1,1.7,0.4,2.5l-0.3,0.1L1,34.1l1.8,3.1l2.3-0.7l0.3-0.1c1.2,1.2,2.7,2.1,4.4,2.5v0.3l0.6,2.4h3.5l0.6-2.4v-0.3c1.7-0.4,3.2-1.3,4.4-2.5l0.3,0.1l2.3,0.7l1.8-3.1l-1.7-1.7L21.2,32.2z M12.1,34.4c-2.6,0-4.7-2.1-4.7-4.7S9.5,25,12.1,25s4.7,2.1,4.7,4.7S14.7,34.4,12.1,34.4z"
							/>
							<path
								className="g3"
								d="M37.7,15.7c0.2-0.8,0.4-1.7,0.4-2.5c0-0.9-0.1-1.7-0.4-2.5l0.3-0.2l1.7-1.7l-1.8-3.1l-2.3,0.7l-0.3,0.2c-1.2-1.2-2.7-2.1-4.4-2.5V3.8l-0.6-2.4h-3.5l-0.6,2.4v0.3c-1.7,0.4-3.2,1.3-4.4,2.5l-0.3-0.2l-2.3-0.7l-1.8,3.1l1.7,1.7l0.3,0.2c-0.2,0.8-0.4,1.7-0.4,2.5c0,0.9,0.1,1.7,0.4,2.5l-0.3,0.1l-1.7,1.7l1.8,3.1l2.3-0.7l0.3-0.1c1.2,1.2,2.7,2.1,4.4,2.5v0.3l0.6,2.4h3.5l0.6-2.4v-0.3c1.7-0.4,3.2-1.3,4.4-2.5l0.3,0.1l2.3,0.7l1.8-3.1L38,15.9L37.7,15.7z M28.6,17.9c-2.6,0-4.7-2.1-4.7-4.7s2.1-4.7,4.7-4.7s4.7,2.1,4.7,4.7S31.2,17.9,28.6,17.9z"
							/>
						</svg>
					</i>

					<h2>{__('Removing starter site...', 'blocksy-companion')}</h2>

					<p>
						{__(
							'Please wait and avoid closing this window while the starter site removal process is in progress.',
							'blocksy-companion'
						)}
					</p>
				</Fragment>
			}
			{runningState === 'done' && (
				<Fragment>
					<i className="ct-demo-icon">
						<svg width="36" height="36" viewBox="0 0 40 40">
							<path
								d="M5.71,40a1,1,0,0,1-1-1V21.59a1,1,0,0,1,1.91,0V39.05A1,1,0,0,1,5.71,40Zm1-31.83V1.07A1,1,0,0,0,5.71,0a1,1,0,0,0-1,1.07v7.1a1,1,0,0,0,1,1.07A1,1,0,0,0,6.67,8.17ZM21,39.05V34.29a1,1,0,1,0-1.9,0v4.76a1,1,0,1,0,1.9,0Zm0-18.14V1a1,1,0,1,0-1.9,0V20.91a1,1,0,1,0,1.9,0ZM35.24,39.05V26.35a1,1,0,0,0-1.91,0v12.7a1,1,0,0,0,1.91,0Zm0-26.25V1a1,1,0,1,0-1.91,0V12.8a1,1,0,1,0,1.91,0Z"
								transform="translate(-0.71)"
								fill="#dae3e8"
							/>
							<path
								d="M5.71,18.06a5,5,0,1,1,5-5A5,5,0,0,1,5.71,18.06ZM20,30.76a5,5,0,1,1,5-5A5,5,0,0,1,20,30.76Zm14.29-7.93a5,5,0,1,1,5-5A5,5,0,0,1,34.29,22.83Z"
								transform="translate(-0.71)"
								fill="#0c7ab3"
							/>
						</svg>
					</i>

					<h2>{__('Starter Site Removed', 'blocksy-companion')}</h2>

					<p>
						{__(
							'The starter site has been successfully removed. You may now close this window.',
							'blocksy-companion'
						)}
					</p>

					<div className="ct-modify-actions">
						<button
							className="ct-demo-btn ct-dismiss"
							onClick={(e) => {
								e.preventDefault()
								setCurrentDemo(`${properDemoName}:hide`)
							}}>
							{__('Close Now', 'blocksy-companion')}
						</button>
					</div>
				</Fragment>
			)}

			{runningState === 'idle' && (
				<Fragment>
					<i className="ct-demo-icon">
						<svg width="36" height="36" viewBox="0 0 40 40">
							<path
								d="M5.71,40a1,1,0,0,1-1-1V21.59a1,1,0,0,1,1.91,0V39.05A1,1,0,0,1,5.71,40Zm1-31.83V1.07A1,1,0,0,0,5.71,0a1,1,0,0,0-1,1.07v7.1a1,1,0,0,0,1,1.07A1,1,0,0,0,6.67,8.17ZM21,39.05V34.29a1,1,0,1,0-1.9,0v4.76a1,1,0,1,0,1.9,0Zm0-18.14V1a1,1,0,1,0-1.9,0V20.91a1,1,0,1,0,1.9,0ZM35.24,39.05V26.35a1,1,0,0,0-1.91,0v12.7a1,1,0,0,0,1.91,0Zm0-26.25V1a1,1,0,1,0-1.91,0V12.8a1,1,0,1,0,1.91,0Z"
								transform="translate(-0.71)"
								fill="#dae3e8"
							/>
							<path
								d="M5.71,18.06a5,5,0,1,1,5-5A5,5,0,0,1,5.71,18.06ZM20,30.76a5,5,0,1,1,5-5A5,5,0,0,1,20,30.76Zm14.29-7.93a5,5,0,1,1,5-5A5,5,0,0,1,34.29,22.83Z"
								transform="translate(-0.71)"
								fill="#0c7ab3"
							/>
						</svg>
					</i>
					
					<h2>
						{__(
							'This starter site is already installed',
							'blocksy-companion'
						)}
					</h2>

					<p>
						{__(
							'What steps do you want to perform next?',
							'blocksy-companion'
						)}
					</p>

					<div className="ct-modify-actions">
						<button
							className="ct-demo-btn demo-remove"
							onClick={(e) => {
								setRunningState('loading')

								executeRemoveSteps()

								e.preventDefault()
								setCurrentlyInstalledDemo()
							}}>
							{__('Remove', 'blocksy-companion')}
						</button>

						<button
							className="ct-demo-btn"
							onClick={(e) => {
								e.preventDefault()
								nextStep()
							}}>
							{__('Reinstall', 'blocksy-companion')}
						</button>
					</div>
				</Fragment>
			)}
		</animated.div>
	)
}

export default ModifyDemo
