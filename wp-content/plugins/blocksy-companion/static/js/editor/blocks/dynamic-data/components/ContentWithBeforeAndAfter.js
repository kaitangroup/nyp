import {
	Fragment,
	createElement,
	createPortal,
	useEffect,
	useState,
	useRef,
} from '@wordpress/element'

const ContentWithBeforeAndAfter = ({
	tagName: TagName,
	children,
	before,
	after,
	withLink,
	...rest
}) => {
	const [data, setData] = useState(null)

	const beforeContent = before ? before : ''
	const afterContent = after ? after : ''

	const dynamicContent = useRef(null)

	const hasHtml =
		beforeContent.indexOf('<') !== -1 || afterContent.indexOf('<') !== -1

	useEffect(() => {
		if (hasHtml) {
			setData(Math.random())
		}
	}, [before, after])

	// No portal is needed if there's no HTML in before or after
	if (!hasHtml) {
		if (withLink) {
			return (
				<a href="#" rel="noopener noreferrer">
					{beforeContent}
					{children}
					{afterContent}
				</a>
			)
		}

		return (
			<Fragment>
				{beforeContent}
				{children}
				{afterContent}
			</Fragment>
		)
	}

	if (withLink) {
		return (
			<a href="#" rel="noopener noreferrer">
				<span
					ref={dynamicContent}
					dangerouslySetInnerHTML={{
						__html: `${before}<span></span>${after}`,
					}}
					{...rest}
				/>

				{dynamicContent.current &&
					dynamicContent.current.querySelector('span') &&
					createPortal(
						children,
						dynamicContent.current.querySelector('span')
					)}
			</a>
		)
	}

	return (
		<Fragment>
			<span
				ref={dynamicContent}
				dangerouslySetInnerHTML={{
					__html: `${before}<span></span>${after}`,
				}}
				{...rest}
			/>

			{dynamicContent.current &&
				dynamicContent.current.querySelector('span') &&
				createPortal(
					children,
					dynamicContent.current.querySelector('span')
				)}
		</Fragment>
	)
}

export default ContentWithBeforeAndAfter
