import {
	useState,
	createElement,
	useContext,
	Fragment
} from '@wordpress/element'
import { __, sprintf } from 'ct-i18n'
import DashboardContext from '../context'
import ctEvents from 'ct-events'

import SubmitSupport, { useSupportSections } from '../components/SubmitSupport'

import $ from 'jquery'

const DocumentationButton = ({ href = '' }) => {
	if (ctDashboardLocalizations.plugin_data.hide_docs_section) {
		return null
	}

	return (
		<a href={href} target="_blank">
			<svg
				width="14px"
				height="14px"
				viewBox="0 0 24 24"
				fill="currentColor">
				<path d="M23 2.1h-6.6c-1.8 0-3.4.9-4.4 2.3C11 3 9.4 2.1 7.6 2.1H1c-.6 0-1 .4-1 1v16.5c0 .6.4 1 1 1h7.7c1.3 0 2.3 1 2.3 2.3 0 .6.4 1 1 1s1-.4 1-1c0-1.3 1-2.3 2.3-2.3H23c.6 0 1-.4 1-1V3.1c0-.6-.4-1-1-1zM11 19.3c-.7-.4-1.5-.7-2.3-.7H2V4.1h5.6c1.9 0 3.4 1.5 3.4 3.4v11.8zm11-.7h-6.7c-.8 0-1.6.2-2.3.7V7.5c0-1.9 1.5-3.4 3.4-3.4H22v14.5z" />
			</svg>
			{__('Documentation', 'blocksy')}
		</a>
	)
}

const Home = () => {
	const {
		is_companion_active,
		companion_download_link,
		child_download_link
	} = useContext(DashboardContext)

	let beforeContent = { content: null }
	let afterContent = { content: null }

	const [isLoading, setIsLoading] = useState(false)
	const [customStatus, setCustomStatus] = useState(false)

	ctEvents.trigger('ct:dashboard:home:before', beforeContent)
	ctEvents.trigger('ct:dashboard:home:after', afterContent)

	const finalStatus = customStatus || is_companion_active

	const supportSections = useSupportSections()

	return (
		<section>
			{beforeContent.content}

			<div
				className="ct-dashboard-home-container"
				data-columns={supportSections.length === 0 ? '1' : '2'}>
				<section>
					<h4>{__('Customizer Shortcuts', 'blocksy')}</h4>

					<ul className="ct-customizer-shortcuts-list">
						<li>
							<h4>{__('Color Options', 'blocksy')}</h4>

							<p>
								{__(
									'Manage the colour palette, as well as setting colours for different elements of the website.',
									'blocksy'
								)}
							</p>

							<div className="ct-shortcut-actions">
								<DocumentationButton href="https://creativethemes.com/blocksy/docs/general-options/colors/" />

								<a
									href={`${
										ctDashboardLocalizations.customizer_url
									}${encodeURI(`[section]=color`)}`}
									target="_blank">
									<svg
										width="15px"
										height="15px"
										viewBox="0 0 24 24"
										fill="currentColor">
										<path d="M4 11c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v7c0 .6.4 1 1 1zM12 11c-.6 0-1 .4-1 1v9c0 .6.4 1 1 1s1-.4 1-1v-9c0-.6-.4-1-1-1zM20 13c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v9c0 .6.4 1 1 1zM7 13H1c-.6 0-1 .4-1 1s.4 1 1 1h2v6c0 .6.4 1 1 1s1-.4 1-1v-6h2c.6 0 1-.4 1-1s-.4-1-1-1zM15 7h-2V3c0-.6-.4-1-1-1s-1 .4-1 1v4H9c-.6 0-1 .4-1 1s.4 1 1 1h6c.6 0 1-.4 1-1s-.4-1-1-1zM23 15h-6c-.6 0-1 .4-1 1s.4 1 1 1h2v4c0 .6.4 1 1 1s1-.4 1-1v-4h2c.6 0 1-.4 1-1s-.4-1-1-1z" />
									</svg>
									{__('Customize', 'blocksy')}
								</a>
							</div>
						</li>

						<li>
							<h4>{__('Typography Options', 'blocksy')}</h4>

							<p>
								{__(
									'Set the footer type, number of columns, spacing and colors.',
									'blocksy'
								)}
							</p>

							<div className="ct-shortcut-actions">
								<DocumentationButton href="https://creativethemes.com/blocksy/docs/general-options/typography/" />

								<a
									href={`${
										ctDashboardLocalizations.customizer_url
									}${encodeURI('[section]=typography')}`}
									target="_blank">
									<svg
										width="15px"
										height="15px"
										viewBox="0 0 24 24"
										fill="currentColor">
										<path d="M4 11c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v7c0 .6.4 1 1 1zM12 11c-.6 0-1 .4-1 1v9c0 .6.4 1 1 1s1-.4 1-1v-9c0-.6-.4-1-1-1zM20 13c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v9c0 .6.4 1 1 1zM7 13H1c-.6 0-1 .4-1 1s.4 1 1 1h2v6c0 .6.4 1 1 1s1-.4 1-1v-6h2c.6 0 1-.4 1-1s-.4-1-1-1zM15 7h-2V3c0-.6-.4-1-1-1s-1 .4-1 1v4H9c-.6 0-1 .4-1 1s.4 1 1 1h6c.6 0 1-.4 1-1s-.4-1-1-1zM23 15h-6c-.6 0-1 .4-1 1s.4 1 1 1h2v4c0 .6.4 1 1 1s1-.4 1-1v-4h2c.6 0 1-.4 1-1s-.4-1-1-1z" />
									</svg>
									{__('Customize', 'blocksy')}
								</a>
							</div>
						</li>

						<li>
							<h4>{__('Header Options', 'blocksy')}</h4>

							<p>
								{__(
									'Configure the header to your liking with an easy to use drag and drop builder.',
									'blocksy'
								)}
							</p>

							<div className="ct-shortcut-actions">
								<DocumentationButton href="https://creativethemes.com/blocksy/docs/header-elements/header-builder-elements/" />

								<a
									href={`${
										ctDashboardLocalizations.customizer_url
									}${encodeURI(`[section]=header`)}`}
									target="_blank">
									<svg
										width="15px"
										height="15px"
										viewBox="0 0 24 24"
										fill="currentColor">
										<path d="M4 11c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v7c0 .6.4 1 1 1zM12 11c-.6 0-1 .4-1 1v9c0 .6.4 1 1 1s1-.4 1-1v-9c0-.6-.4-1-1-1zM20 13c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v9c0 .6.4 1 1 1zM7 13H1c-.6 0-1 .4-1 1s.4 1 1 1h2v6c0 .6.4 1 1 1s1-.4 1-1v-6h2c.6 0 1-.4 1-1s-.4-1-1-1zM15 7h-2V3c0-.6-.4-1-1-1s-1 .4-1 1v4H9c-.6 0-1 .4-1 1s.4 1 1 1h6c.6 0 1-.4 1-1s-.4-1-1-1zM23 15h-6c-.6 0-1 .4-1 1s.4 1 1 1h2v4c0 .6.4 1 1 1s1-.4 1-1v-4h2c.6 0 1-.4 1-1s-.4-1-1-1z" />
									</svg>
									{__('Customize', 'blocksy')}
								</a>
							</div>
						</li>

						<li>
							<h4>{__('Footer Options', 'blocksy')}</h4>

							<p>
								{__(
									'Arrange your footer in a way that actually makes sense with our drag and drop builder.',
									'blocksy'
								)}
							</p>

							<div className="ct-shortcut-actions">
								<DocumentationButton href="https://creativethemes.com/blocksy/docs/footer-options/footer-introduction/" />

								<a
									href={`${
										ctDashboardLocalizations.customizer_url
									}${encodeURI('[section]=footer')}`}
									target="_blank">
									<svg
										width="15px"
										height="15px"
										viewBox="0 0 24 24"
										fill="currentColor">
										<path d="M4 11c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v7c0 .6.4 1 1 1zM12 11c-.6 0-1 .4-1 1v9c0 .6.4 1 1 1s1-.4 1-1v-9c0-.6-.4-1-1-1zM20 13c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v9c0 .6.4 1 1 1zM7 13H1c-.6 0-1 .4-1 1s.4 1 1 1h2v6c0 .6.4 1 1 1s1-.4 1-1v-6h2c.6 0 1-.4 1-1s-.4-1-1-1zM15 7h-2V3c0-.6-.4-1-1-1s-1 .4-1 1v4H9c-.6 0-1 .4-1 1s.4 1 1 1h6c.6 0 1-.4 1-1s-.4-1-1-1zM23 15h-6c-.6 0-1 .4-1 1s.4 1 1 1h2v4c0 .6.4 1 1 1s1-.4 1-1v-4h2c.6 0 1-.4 1-1s-.4-1-1-1z" />
									</svg>
									{__('Customize', 'blocksy')}
								</a>
							</div>
						</li>

						<li>
							<h4>{__('Blog Options', 'blocksy')}</h4>

							<p>
								{__(
									'Adjust your blog roll options in a single place and make it stand out in the crowd.',
									'blocksy'
								)}
							</p>

							<div className="ct-shortcut-actions">
								<DocumentationButton href="https://creativethemes.com/blocksy/docs/post-types/blog-posts/" />

								<a
									href={`${
										ctDashboardLocalizations.customizer_url
									}${encodeURI(`[section]=blog_posts`)}`}
									target="_blank">
									<svg
										width="15px"
										height="15px"
										viewBox="0 0 24 24"
										fill="currentColor">
										<path d="M4 11c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v7c0 .6.4 1 1 1zM12 11c-.6 0-1 .4-1 1v9c0 .6.4 1 1 1s1-.4 1-1v-9c0-.6-.4-1-1-1zM20 13c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v9c0 .6.4 1 1 1zM7 13H1c-.6 0-1 .4-1 1s.4 1 1 1h2v6c0 .6.4 1 1 1s1-.4 1-1v-6h2c.6 0 1-.4 1-1s-.4-1-1-1zM15 7h-2V3c0-.6-.4-1-1-1s-1 .4-1 1v4H9c-.6 0-1 .4-1 1s.4 1 1 1h6c.6 0 1-.4 1-1s-.4-1-1-1zM23 15h-6c-.6 0-1 .4-1 1s.4 1 1 1h2v4c0 .6.4 1 1 1s1-.4 1-1v-4h2c.6 0 1-.4 1-1s-.4-1-1-1z" />
									</svg>
									{__('Customize', 'blocksy')}
								</a>
							</div>
						</li>

						<li>
							<h4>{__('Posts Options', 'blocksy')}</h4>

							<p>
								{__(
									'Set the footer type, number of columns, spacing and colors.',
									'blocksy'
								)}
							</p>

							<div className="ct-shortcut-actions">
								<DocumentationButton href="https://creativethemes.com/blocksy/docs/post-types/single-posts/" />

								<a
									href={`${
										ctDashboardLocalizations.customizer_url
									}${encodeURI(
										'[section]=single_blog_posts'
									)}`}
									target="_blank">
									<svg
										width="15px"
										height="15px"
										viewBox="0 0 24 24"
										fill="currentColor">
										<path d="M4 11c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v7c0 .6.4 1 1 1zM12 11c-.6 0-1 .4-1 1v9c0 .6.4 1 1 1s1-.4 1-1v-9c0-.6-.4-1-1-1zM20 13c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v9c0 .6.4 1 1 1zM7 13H1c-.6 0-1 .4-1 1s.4 1 1 1h2v6c0 .6.4 1 1 1s1-.4 1-1v-6h2c.6 0 1-.4 1-1s-.4-1-1-1zM15 7h-2V3c0-.6-.4-1-1-1s-1 .4-1 1v4H9c-.6 0-1 .4-1 1s.4 1 1 1h6c.6 0 1-.4 1-1s-.4-1-1-1zM23 15h-6c-.6 0-1 .4-1 1s.4 1 1 1h2v4c0 .6.4 1 1 1s1-.4 1-1v-4h2c.6 0 1-.4 1-1s-.4-1-1-1z" />
									</svg>
									{__('Customize', 'blocksy')}
								</a>
							</div>
						</li>

						<li>
							<h4>{__('Page Options', 'blocksy')}</h4>

							<p>
								{__(
									'Set the page container width, spacing, sidebar and more.',
									'blocksy'
								)}
							</p>

							<div className="ct-shortcut-actions">
								<DocumentationButton href="https://creativethemes.com/blocksy/docs/post-types/pages/" />

								<a
									href={`${
										ctDashboardLocalizations.customizer_url
									}${encodeURI('[section]=single_pages')}`}
									target="_blank">
									<svg
										width="15px"
										height="15px"
										viewBox="0 0 24 24"
										fill="currentColor">
										<path d="M4 11c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v7c0 .6.4 1 1 1zM12 11c-.6 0-1 .4-1 1v9c0 .6.4 1 1 1s1-.4 1-1v-9c0-.6-.4-1-1-1zM20 13c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v9c0 .6.4 1 1 1zM7 13H1c-.6 0-1 .4-1 1s.4 1 1 1h2v6c0 .6.4 1 1 1s1-.4 1-1v-6h2c.6 0 1-.4 1-1s-.4-1-1-1zM15 7h-2V3c0-.6-.4-1-1-1s-1 .4-1 1v4H9c-.6 0-1 .4-1 1s.4 1 1 1h6c.6 0 1-.4 1-1s-.4-1-1-1zM23 15h-6c-.6 0-1 .4-1 1s.4 1 1 1h2v4c0 .6.4 1 1 1s1-.4 1-1v-4h2c.6 0 1-.4 1-1s-.4-1-1-1z" />
									</svg>
									{__('Customize', 'blocksy')}
								</a>
							</div>
						</li>

						<li>
							<h4>{__('Sidebar Options', 'blocksy')}</h4>

							<p>
								{__(
									'Change the sidebar behaviour and style with a nice set of options that come in handy.',
									'blocksy'
								)}
							</p>

							<div className="ct-shortcut-actions">
								<DocumentationButton href="https://creativethemes.com/blocksy/docs/general-options/sidebar/" />

								<a
									href={`${
										ctDashboardLocalizations.customizer_url
									}${encodeURI('[section]=sidebar')}`}
									target="_blank">
									<svg
										width="15px"
										height="15px"
										viewBox="0 0 24 24"
										fill="currentColor">
										<path d="M4 11c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v7c0 .6.4 1 1 1zM12 11c-.6 0-1 .4-1 1v9c0 .6.4 1 1 1s1-.4 1-1v-9c0-.6-.4-1-1-1zM20 13c.6 0 1-.4 1-1V3c0-.6-.4-1-1-1s-1 .4-1 1v9c0 .6.4 1 1 1zM7 13H1c-.6 0-1 .4-1 1s.4 1 1 1h2v6c0 .6.4 1 1 1s1-.4 1-1v-6h2c.6 0 1-.4 1-1s-.4-1-1-1zM15 7h-2V3c0-.6-.4-1-1-1s-1 .4-1 1v4H9c-.6 0-1 .4-1 1s.4 1 1 1h6c.6 0 1-.4 1-1s-.4-1-1-1zM23 15h-6c-.6 0-1 .4-1 1s.4 1 1 1h2v4c0 .6.4 1 1 1s1-.4 1-1v-4h2c.6 0 1-.4 1-1s-.4-1-1-1z" />
									</svg>
									{__('Customize', 'blocksy')}
								</a>
							</div>
						</li>
					</ul>
				</section>

				<SubmitSupport
					placement="sidebar"
					render={(markup) => {
						return (
							<aside>
								<h4>{__('Need help or advice?', 'blocksy')}</h4>
								{markup}
							</aside>
						)
					}}
				/>
			</div>

			{afterContent.content}

			{is_companion_active !== 'active' && (
				<Fragment>
					<div className="ct-dashboard-home-downloads">
						<ul>
							<li>
								<h4>
									<svg
										width="16"
										height="16"
										fill="currentColor"
										viewBox="0 0 20 20">
										<path d="M3.1,0c-0.4,0-0.8,0.2-1,0.6L0.2,3.9C0.1,4.1,0,4.2,0,4.4v13.3C0,19,1,20,2.2,20h15.6c1.2,0,2.2-1,2.2-2.2V4.4c0-0.2-0.1-0.4-0.2-0.6l-1.9-3.3c-0.2-0.3-0.6-0.6-1-0.6H3.1z M3.7,2.2h12.6l1.3,2.2H2.4L3.7,2.2z M2.2,6.7h15.6v11.1H2.2V6.7zM8.9,8.3v3.3H5.6l4.4,4.4l4.4-4.4h-3.3V8.3H8.9z"></path>
									</svg>

									{__('Blocksy Companion', 'blocksy')}
								</h4>

								<p>
									{__(
										'By downloading and installing this plugin you will have access to demo templates, extensions and a lot more stunning features.',
										'blocksy'
									)}
								</p>

								<a
									className="ct-button"
									onClick={(e) => {
										e.stopPropagation()

										setIsLoading(true)

										$.ajax(ajaxurl, {
											type: 'POST',
											data: {
												action: 'blocksy_notice_button_click',
												nonce: ct_localizations.nonce
											}
										}).then(({ success, data }) => {
											if (success) {
												setCustomStatus(data.status)
												if (data.status === 'active') {
													location.assign(
														data.pluginUrl
													)
												}
											}

											setIsLoading(false)
										})
									}}>
									{isLoading
										? __(
												'Installing & activating...',
												'blocksy'
											)
										: finalStatus === 'uninstalled'
											? __(
													'Install Blocksy Companion',
													'blocksy'
												)
											: finalStatus === 'installed'
												? __(
														'Activate Blocksy Companion',
														'blocksy'
													)
												: __(
														'Blocksy Companion active!',
														'blocksy'
													)}
								</a>
							</li>

							<li>
								<h4>
									<svg
										width="16"
										height="16"
										fill="currentColor"
										viewBox="0 0 20 20">
										<path d="M3.1,0c-0.4,0-0.8,0.2-1,0.6L0.2,3.9C0.1,4.1,0,4.2,0,4.4v13.3C0,19,1,20,2.2,20h15.6c1.2,0,2.2-1,2.2-2.2V4.4c0-0.2-0.1-0.4-0.2-0.6l-1.9-3.3c-0.2-0.3-0.6-0.6-1-0.6H3.1z M3.7,2.2h12.6l1.3,2.2H2.4L3.7,2.2z M2.2,6.7h15.6v11.1H2.2V6.7zM8.9,8.3v3.3H5.6l4.4,4.4l4.4-4.4h-3.3V8.3H8.9z"></path>
									</svg>

									{__('Blocksy Child Theme', 'blocksy')}
								</h4>

								<p>
									{__(
										'By using a child theme you can modify any file without the fear of breaking something in the parent theme.',
										'blocksy'
									)}
								</p>

								<a
									className="ct-button"
									href={child_download_link}
									target="_blank">
									{__('Download now', 'blocksy')}
								</a>
							</li>
						</ul>
					</div>
				</Fragment>
			)}
		</section>
	)
}

export default Home
