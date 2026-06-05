import { createElement } from '@wordpress/element'
import classnames from 'classnames'

import { __experimentalUseGradient } from '@wordpress/block-editor'

export const DEFAULT_FOCAL_POINT = { x: 0.5, y: 0.5 }

export function mediaPosition({ x, y } = DEFAULT_FOCAL_POINT) {
	return `${Math.round(x * 100)}% ${Math.round(y * 100)}%`
}

const CoverPreview = ({ attributes, url }) => {
	const { hasParallax, focalPoint, alt } = attributes

	const backgroundImage = url ? `url(${url})` : undefined
	const backgroundPosition = mediaPosition(focalPoint)

	if (!url) {
		return (
			<div className={classnames('ct-dynamic-data-placeholder', {})}>
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

	if (hasParallax) {
		return (
			<div
				role={alt ? 'img' : undefined}
				aria-label={alt ? alt : undefined}
				className={classnames('wp-block-cover__image-background', {
					'has-parallax': hasParallax,
				})}
				style={{ backgroundImage, backgroundPosition }}
			/>
		)
	}

	return (
		<img
			alt={alt}
			className={classnames('wp-block-cover__image-background', {
				'has-parallax': hasParallax,
			})}
			src={url}
			style={{
				objectPosition: focalPoint
					? mediaPosition(focalPoint)
					: undefined,
			}}
		/>
	)
}

export default CoverPreview
