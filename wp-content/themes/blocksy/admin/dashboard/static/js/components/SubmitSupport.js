import {
	createElement,
	Component,
	useEffect,
	useState,
	Fragment
} from '@wordpress/element'
import { __ } from 'ct-i18n'
import classnames from 'classnames'

export const useSupportSections = () => {
	const pluginData = ctDashboardLocalizations.plugin_data || {}

	const facebook = pluginData.facebook || {}
	const videoTutorials = pluginData.video_tutorials || {}
	const knowledgeBase = pluginData.knowledge_base || {}
	const support = pluginData.support || {}

	const sections = [
		{
			key: 'facebook',
			hide: pluginData.hide_support_facebook_section,
			icon: (
				<svg
					width="12"
					height="12"
					viewBox="0 0 24 24"
					fill="currentColor">
					<path d="M8.6 6.7c0-1.9 1.5-3.5 3.4-3.5s3.4 1.5 3.4 3.5-1.5 3.5-3.4 3.5-3.4-1.6-3.4-3.5zM22 11.3h-3.9c.5.5.7 1.2.7 2v5.9c0 .6-.2 1.2-.3 1.7H22c1.2-.8 2-2.2 2-3.8v-3.7c0-1.2-.9-2.1-2-2.1zm.8-4.1c0-1.6-1.3-2.9-2.9-2.9-1.6 0-2.9 1.3-2.9 2.9 0 1.6 1.3 2.9 2.9 2.9 1.7 0 2.9-1.3 2.9-2.9zM5.2 13c.1-.7.3-1.3.7-1.8H1.8c-1 .1-1.8.9-1.8 2V17c0 1.6.8 2.9 2 3.8h3.5c-.2-.6-.3-1.3-.3-2.1V13zM4 4.4c-1.6 0-2.9 1.3-2.9 2.9 0 1.6 1.3 2.9 2.9 2.9s2.9-1.3 2.9-2.9c0-1.7-1.3-2.9-2.9-2.9zm11.2 6.9H8.8c-1.1 0-2 .9-2 2v5.5c0 .7.2 1.4.4 2.1h9.5c.3-.6.4-1.3.4-2.1v-5.5c.1-1.1-.8-2-1.9-2z" />
				</svg>
			),
			title: facebook.title || __('Facebook Community', 'blocksy'),
			description:
				facebook.description ||
				__(
					'Share ideas, help others, ask questions and discuss your next project in our friendly community.',
					'blocksy'
				),
			link:
				facebook.link ||
				'https://www.facebook.com/groups/blocksy.community',
			buttonText:
				facebook.buttonText || __('Join Our Community', 'blocksy')
		},
		{
			key: 'video_tutorials',
			hide: pluginData.hide_support_video_section,
			icon: (
				<svg
					width="10"
					height="10"
					viewBox="0 0 24 24"
					fill="currentColor">
					<path d="M13.3 3.7 8.5.4C7.4-.3 5.9-.1 5.2 1c-.3.4-.5.9-.5 1.4v19.1c0 1.3 1.1 2.4 2.4 2.4.5 0 1-.2 1.4-.4l4.7-3.3 9-6.3c1.1-.8 1.4-2.3.6-3.4l-.6-.6-8.9-6.2z" />
				</svg>
			),
			title: videoTutorials.title || __('Video Tutorials', 'blocksy'),
			description:
				videoTutorials.description ||
				__(
					'Learn how to do just about anything within Blocksy by following our byte-sized video tutorials.',
					'blocksy'
				),
			link:
				videoTutorials.link ||
				'https://creativethemes.com/blocksy/video-tutorials/',
			buttonText:
				videoTutorials.buttonText || __('Watch Tutorials', 'blocksy')
		},
		{
			key: 'knowledge_base',
			hide: pluginData.hide_support_docs_section,
			icon: (
				<svg
					width="12"
					height="12"
					viewBox="0 0 24 24"
					fill="currentColor">
					<path d="M24 4.1v13.8c0 .7-.5 1.2-1.2 1.3-2 .1-6.1.5-8.9 1.9-.4.2-.9-.1-.9-.6V5.7c0-.2.1-.4.3-.5 2.7-1.7 7.2-2.1 9.4-2.3.7-.1 1.3.5 1.3 1.2zM1.4 2.9C.6 2.8 0 3.4 0 4.1v13.8c0 .7.5 1.2 1.2 1.3 2 .1 6.1.5 8.9 1.9.4.2.9-.1.9-.5V5.7c0-.2-.1-.4-.3-.5C8.1 3.5 3.6 3 1.4 2.9z" />
				</svg>
			),
			title: knowledgeBase.title || __('Knowledge Base', 'blocksy'),
			description:
				knowledgeBase.description ||
				__(
					'Dive in with our documentation and learn tips and tricks about Blocksy and its components.',
					'blocksy'
				),
			link:
				knowledgeBase.link ||
				'https://creativethemes.com/blocksy/docs/',
			buttonText:
				knowledgeBase.buttonText || __('View Documentation', 'blocksy')
		},
		{
			key: 'support',
			hide: pluginData.hide_support_section,
			icon: (
				<svg
					width="14"
					height="14"
					viewBox="0 0 24 24"
					fill="currentColor">
					<path d="M8.3 7.3 4.1 3C6.2 1.1 9 0 12 0s5.8 1.1 7.9 3l-4.3 4.3C14.6 6.5 13.4 6 12 6s-2.6.5-3.7 1.3zM12 18c-1.4 0-2.6-.5-3.7-1.3L4.1 21c2.1 1.9 4.9 3 7.9 3s5.8-1.1 7.9-3l-4.3-4.3c-1 .8-2.2 1.3-3.6 1.3zm9-13.9-4.3 4.3c.8 1 1.3 2.3 1.3 3.7s-.5 2.6-1.3 3.7l4.3 4.3c1.9-2.1 3-4.9 3-7.9s-1.1-6-3-8.1zM6 12c0-1.4.5-2.6 1.3-3.7L3 4.1C1.1 6.2 0 9 0 12s1.1 5.8 3 7.9l4.3-4.3C6.5 14.6 6 13.4 6 12z" />
				</svg>
			),
			title: support.title || __('Support', 'blocksy'),
			description:
				support.description ||
				__(
					'If your questions have not been answered by documentation or video tutorials, drop us a line.',
					'blocksy'
				),
			link: support.link || ctDashboardLocalizations.support_url,
			buttonText: support.buttonText || __('Submit a Ticket', 'blocksy')
		}
	].filter((section) => !section.hide)

	return sections
}

const SubmitSupport = ({
	placement = 'bottom',
	render = (markup) => (
		<div class="ct-support-container-wrapper">{markup}</div>
	)
}) => {
	const sections = useSupportSections()

	if (sections.length === 0) {
		return null
	}

	let result = (
		<ul className="ct-support-container" data-placement={placement}>
			{sections.map(
				({ key, icon, title, description, link, buttonText }) => (
					<li key={key}>
						<h4>
							<span>{icon}</span>
							{title}
						</h4>

						<p>{description}</p>

						<a
							href={link}
							className="ct-button"
							data-hover="blue"
							target="_blank">
							{buttonText}
						</a>
					</li>
				)
			)}
		</ul>
	)

	return render(result)
}

export default SubmitSupport
