import { useState } from '@wordpress/element'

import { useSpring } from 'react-spring'
import bezierEasing from 'bezier-easing'

export const useSpringModal = (args = {}) => {
	args = {
		onClosed: () => {},

		...args,
	}

	const [modalOpen, setModalOpen] = useState(false)

	const [modalSprings, modalAnimationApi] = useSpring(() => ({
		from: {
			transform: 'scale3d(0.95, 0.95, 1)',
			opacity: 0,
		},

		config: {
			duration: 100,
			easing: bezierEasing(0.25, 0.1, 0.25, 1.0),
		},
	}))

	// First render modal and make it hidden.
	//
	// Then, after one frame, animate the modal to be visible.
	const openModal = () => {
		if (modalOpen) {
			return
		}

		setModalOpen(true)

		requestAnimationFrame(() => {
			modalAnimationApi.start({
				transform: 'scale3d(1, 1, 1)',
				opacity: 1,
			})
		})
	}

	// Animate the modal to be hidden first. Then fully remove it from the DOM.
	const closeModal = () => {
		if (!modalOpen) {
			return
		}

		modalAnimationApi.start({
			transform: 'scale3d(0.95, 0.95, 1)',
			opacity: 0,

			onRest: () => {
				setModalOpen(false)
				args.onClosed()
			},
		})
	}

	return {
		modalOpen,
		modalStyles: modalSprings,

		openModal,
		closeModal,
	}
}
