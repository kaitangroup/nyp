import { createElement, useRef } from '@wordpress/element'
import { __ } from 'ct-i18n'

import {
	InspectorControls,
	useBlockProps,
	withColors,
} from '@wordpress/block-editor'
import Preview from './Preview'
import ColorsPanel from '../../components/ColorsPanel'
import useBreadcrumbsDataDescriptor from './use-breadcrumbs-data-descriptor'

const Edit = ({
	clientId,
	textColor,
	setTextColor,
	linkColor,
	setLinkColor,
	linkHoverColor,
	setLinkHoverColor,
	className,

	context: { postId, postType },
}) => {
	const { breadcrumbs } = useBreadcrumbsDataDescriptor({
		postId,
		postType,
	})

	const navRef = useRef()

	const blockProps = useBlockProps({
		ref: navRef,
		className: {
			'ct-breadcrumbs': true,
			className,
		},
		style: {
			color: textColor?.color,
			'--theme-link-initial-color': linkColor?.color,
			'--theme-link-hover-color': linkHoverColor?.color,
		},
	})

	return (
		<>
			<div {...blockProps}>
				<Preview {...{ breadcrumbs, postId, postType }} />
				<InspectorControls group="styles">
					<ColorsPanel
						label={__('Text Color', 'blocksy-companion')}
						resetAll={() => {
							setTextColor('')
							setLinkColor('')
							setLinkHoverColor('')
						}}
						panelId={clientId}
						settings={[
							{
								colorValue: textColor.color,
								enableAlpha: true,
								label: __('Text', 'blocksy-companion'),
								onColorChange: setTextColor,
							},
							{
								colorValue: linkColor.color,
								enableAlpha: true,
								label: __('Link Initial', 'blocksy-companion'),
								onColorChange: setLinkColor,
							},
							{
								colorValue: linkHoverColor.color,
								enableAlpha: true,
								label: __('Link Hover', 'blocksy-companion'),
								onColorChange: setLinkHoverColor,
							},
						]}
					/>
				</InspectorControls>
			</div>
		</>
	)
}

export default withColors(
	{ textColor: 'color' },
	{ linkColor: 'color' },
	{ linkHoverColor: 'color' }
)(Edit)
