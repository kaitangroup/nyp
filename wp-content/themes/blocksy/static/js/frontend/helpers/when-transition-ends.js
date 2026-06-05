export function whenTransitionEnds(el, cb) {
	let timeoutId = null
	let didEnd = false

	const end = () => {
		clearTimeout(timeoutId)

		el.removeEventListener('transitionend', onEnd)

		if (!didEnd) {
			didEnd = true
			cb()
		}
	}

	const onEnd = (e) => {
		// Very important check.
		//
		// Sometimes transitionend event is propagated from children to parent
		// and the children transition might be shorter than the parent's one
		// and thus the parent's transitionend event is triggered too early.
		if (e.target === el) {
			end()
		}
	}

	const transitionDuration =
		parseFloat(getComputedStyle(el).transitionDuration) * 1000

	el.addEventListener('transitionend', onEnd)

	timeoutId = setTimeout(() => {
		end()
	}, transitionDuration)
}
