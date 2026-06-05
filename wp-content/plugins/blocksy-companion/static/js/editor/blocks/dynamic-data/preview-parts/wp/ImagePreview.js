import { createElement } from '@wordpress/element'
import {
	useBlockProps,
	useInnerBlocksProps,
	__experimentalUseBorderProps as useBorderProps,
	useSettings,
	getGradientValueBySlug,
	__experimentalGetGradientClass,
} from '@wordpress/block-editor'

import classnames from 'classnames'
import CoverPreview from './CoverPreview'
import { getPositionClassName } from '../../utils'

import { useBlockSupportsCustom } from '../../hooks/use-block-supports-custom'

const VideoIndicator = () => (
	<span className="ct-video-indicator">
		<svg width="40" height="40" viewBox="0 0 40 40" fill="#fff">
			<path
				className="ct-play-path"
				d="M20,0C8.9,0,0,8.9,0,20s8.9,20,20,20s20-9,20-20S31,0,20,0z M16,29V11l12,9L16,29z"></path>
		</svg>
	</span>
)

const ImagePreview = ({
	media,
	url,

	attributes,
	attributes: {
		aspectRatio,
		imageFit,
		width,
		height,
		imageAlign,
		// has_field_link,
		image_hover_effect,
		videoThumbnail,
		minimumHeight,
		contentPosition,

		// cover
		viewType,
		hasParallax,

		gradient,
		customGradient,
	},
}) => {
	const borderProps = useBorderProps(attributes)

	const [gradients] = useSettings('color.gradients', 'color.customGradient')
	const gradientValue =
		customGradient || getGradientValueBySlug(gradients, gradient)

	const gradientClass = __experimentalGetGradientClass(gradient)

	const blockProps = useBlockProps({
		className: classnames('ct-dynamic-media', {
			[`align${imageAlign}`]: imageAlign,
			'wp-block-cover': viewType === 'cover',
			'has-parallax': viewType === 'cover' && hasParallax,
			[getPositionClassName(contentPosition)]:
				viewType === 'cover' && contentPosition,
			'has-custom-content-position':
				viewType === 'cover' && contentPosition,
		}),

		style: {
			width,
			height,
		},
	})

	const uniqueClass = blockProps.className
		.split(' ')
		.find((c) => c.startsWith('wp-elements-'))

	const previewData = useBlockSupportsCustom({
		fieldType: 'image',
		attributes,
		uniqueClass,
	})

	const imageStyles = {
		height: aspectRatio ? '100%' : height,
		width: !!aspectRatio && '100%',
		objectFit: imageFit,
		aspectRatio,
	}
	const {
		allowCustomContentAndWideSize: isBoxedLayout,
		contentSize,
		wideSize,
	} = attributes

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: classnames('wp-block-cover__inner-container', {
				'is-layout-constrained': isBoxedLayout,
				'wp-block-cover-is-layout-constrained': isBoxedLayout,

				'is-layout-flow': !isBoxedLayout,
				'wp-block-cover-is-layout-flow': !isBoxedLayout,
			}),
		},
		{
			template: [
				[
					'core/paragraph',
					{
						align: 'center',
						placeholder: 'Add text…',
					},
				],
			],
			templateInsertUpdatesSelection: false,
		}
	)

	const hasInnerContent =
		(media?.has_video && videoThumbnail === 'yes') ||
		image_hover_effect !== 'none'

	if (viewType === 'cover') {
		return (
			<div
				{...blockProps}
				style={{
					...(blockProps.style || {}),
					...(borderProps.style || {}),

					...(aspectRatio !== 'auto' ? { aspectRatio } : {}),
					minHeight:
						minimumHeight ||
						(aspectRatio !== 'auto' ? 'unset' : undefined),
				}}
				className={classnames(
					blockProps.className,
					borderProps.className,
					previewData.className
				)}>
				<style>
					{`
							${
								contentSize
									? `#${blockProps.id} > .wp-block-cover__inner-container > :where(:not(.alignleft):not(.alignright):not(.alignfull)) {
										max-width: ${contentSize};
									}`
									: ''
							}

							${
								wideSize
									? `#${blockProps.id} > .wp-block-cover__inner-container > .alignwide {
											max-width: ${wideSize};
										}`
									: ''
							}
						`}

					{previewData.css}
				</style>

				<CoverPreview attributes={attributes} url={url} />
				<span
					aria-hidden="true"
					className={classnames('wp-block-cover__background', {
						'wp-block-cover__gradient-background': !!gradientValue,
						'has-background-gradient': !!gradientValue,
						[gradientClass]: !!gradientClass,
					})}
					style={{
						background: gradientValue,
					}}
				/>

				<div {...innerBlocksProps} />
			</div>
		)
	}

	let content = (
		<img
			className={!hasInnerContent ? borderProps.className : ''}
			style={{
				...(!hasInnerContent ? borderProps.style : {}),
				...imageStyles,
			}}
			src={url}
			loading="lazy"
		/>
	)

	if (!url) {
		content = (
			<div
				className={classnames('ct-dynamic-data-placeholder', {
					[borderProps.className]: !hasInnerContent,
				})}
				style={{
					...(!hasInnerContent ? borderProps.style : {}),
					...imageStyles,
				}}>
				<svg
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 60 60"
					preserveAspectRatio="none"
					className="ct-dynamic-data-placeholder-illustration"
					aria-hidden="true"
					focusable="false">
					<path
						vectorEffect="non-scaling-stroke"
						d="M60 60 0 0"></path>
				</svg>
			</div>
		)
	}

	if (hasInnerContent) {
		content = (
			<span
				data-hover={image_hover_effect}
				className={`ct-dynamic-media-inner ${borderProps.className}`}
				style={{
					...borderProps.style,
				}}>
				{content}

				{media?.has_video && videoThumbnail === 'yes' ? (
					<VideoIndicator />
				) : null}
			</span>
		)
	}

	return (
		<figure {...blockProps}>
			{content}

			{media?.has_video && videoThumbnail === 'yes' ? (
				<VideoIndicator />
			) : null}
		</figure>
	)
}

export default ImagePreview
