import { createElement } from '@wordpress/element'

import { __ } from 'ct-i18n'
import { registerBlockType } from '@wordpress/blocks'
import { addFilter } from '@wordpress/hooks'

import Edit from './Edit'
import { getAttributesFromOptions, getOptionsForBlock } from 'blocksy-options'

import { colorsDefaults } from './colors'

export const options = getOptionsForBlock('newsletter')
export const defaultAttributes = getAttributesFromOptions(options)

addFilter(
	'editor.BlockEdit',
	'custom/removeUnlinkBorderRadius',
	function (BlockEdit) {
		return function (props) {
			if (!props || !props.name || props.name !== 'blocksy/newsletter') {
				return <BlockEdit {...props} />
			}

			if (props.attributes?.style?.border?.radius) {
				const radiusValues = Object.values(
					props.attributes.style.border.radius
				)

				const differentValue = radiusValues.find(
					(value) => value !== radiusValues[0]
				)

				props.attributes.style.border.radius = {
					topLeft: differentValue || radiusValues[0],
					topRight: differentValue || radiusValues[0],
					bottomRight: differentValue || radiusValues[0],
					bottomLeft: differentValue || radiusValues[0],
				}
			}

			return <BlockEdit {...props} />
		}
	}
)

registerBlockType('blocksy/newsletter', {
	apiVersion: 3,
	title: __('Newsletter Controls', 'blocksy-companion'),
	icon: {
		src: (
			<svg
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 24 24"
				className="wc-block-editor-components-block-icon">
				<path d="M19 5H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zM5 6.5h14c.3 0 .5.2.5.5v.9L12 13.6 4.5 7.9V7c0-.3.2-.5.5-.5zm14 11H5c-.3 0-.5-.2-.5-.5V9.8l7.5 5.6 7.5-5.6V17c0 .3-.2.5-.5.5z" />
			</svg>
		),
	},
	category: 'widgets',
	supports: {
		html: false,
		inserter: false,
		lock: false,

		__experimentalBorder: {
			color: false,
			radius: true,
			width: false,
			__experimentalSkipSerialization: true,
			__experimentalDefaultControls: {
				color: false,
				radius: true,
				width: false,
			},
		},
	},
	parent: ['blocksy/widgets-wrapper'],
	attributes: { ...defaultAttributes, ...colorsDefaults },
	edit: (props) => <Edit {...props} />,
	save: () => <div>Blocksy: Newsletter</div>,
})

wp.blocks.registerBlockVariation('blocksy/widgets-wrapper', {
	name: 'blocksy-newsletter',
	title: __('Newsletter', 'blocksy-companion'),
	attributes: {
		heading: __('Newsletter', 'blocksy-companion'),
		block: 'blocksy/newsletter',
		hasDescription: true,
		description:
			defaultAttributes?.newsletter_subscribe_text?.default || '',
	},
	isActive: (attributes) => attributes.block === 'blocksy/newsletter',
	icon: {
		src: (
			<svg
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 24 24"
				className="wc-block-editor-components-block-icon">
				<path d="M19 5H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zM5 6.5h14c.3 0 .5.2.5.5v.9L12 13.6 4.5 7.9V7c0-.3.2-.5.5-.5zm14 11H5c-.3 0-.5-.2-.5-.5V9.8l7.5 5.6 7.5-5.6V17c0 .3-.2.5-.5.5z" />
			</svg>
		),
	},
})
