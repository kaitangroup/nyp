import {
	useState,
	createElement,
	Component,
	Fragment,
} from '@wordpress/element'
import { sprintf, __ } from 'ct-i18n'
import Overlay from './Overlay'

const ProOverlayBuilderUnknownItem = ({
	username,
	isShowing,
	setIsShowing,
}) => {
	return (
		<Fragment>
			<Overlay
				items={isShowing}
				className="ct-admin-modal ct-onboarding-modal"
				onDismiss={() => setIsShowing(false)}
				render={() => (
					<div className="ct-modal-content">

						<h2 className="ct-modal-title">
							{__('Element is not available!', 'blocksy')}
						</h2>
						<p>
							{__(
								'Unfortunately, this element is not available or registered. It seems that something changed on your site recently.',
								'blocksy'
							)}
						</p>

						<p>
							{__(
								'Please read the doccumentation article to understand what might have caused this.',
								'blocksy'
							)}
						</p>

						<div
							className="ct-modal-actions has-divider">
							<a
								href="https://creativethemes.com/blocksy/docs/troubleshooting/builder-element-not-available/"
								target="_blank"
								className="button button-primary">
								{__('More information', 'blocksy')}
							</a>
						</div>
					</div>
				)}
			/>
		</Fragment>
	)
}

export default ProOverlayBuilderUnknownItem
