import ctEvents from 'ct-events'
import { getCurrentScreen } from '../helpers/current-screen'

const loadMenuEntry = () => import('../header/menu')

const onlyWithSubmenus = (menu) =>
	menu.querySelector('.menu-item-has-children') ||
	menu.querySelector('.page_item_has_children')

export const menuEntryPoints = [
	{
		els: () =>
			[
				...document.querySelectorAll(
					'header [data-device="desktop"] [data-id*="menu"] > .menu'
				),
				...document.querySelectorAll('.ct-header-account > ul'),
			].filter((menu) => onlyWithSubmenus(menu)),
		load: () => import('../header/sub-menu-open-logic'),
		events: ['ct:header:refresh-menu-submenus'],
	},

	{
		els: () => [
			...document.querySelectorAll(
				'header [data-device="desktop"] [data-id^="menu"][data-responsive]'
			),
		],
		load: () => import('../header/responsive-desktop-menu'),
		events: ['ct:header:responsive-menu:refresh'],
		condition: () => {
			if (getCurrentScreen() !== 'desktop') {
				return false
			}

			return [
				...document.querySelectorAll(
					'header [data-device="desktop"] [data-id^="menu"][data-responsive]'
				),
			].some((menu) => {
				if (!menu.firstElementChild) {
					return false
				}

				const menuRect = menu.firstElementChild.getBoundingClientRect()

				const allEls = [
					...menu
						.closest('[data-row]')
						.querySelectorAll('[data-items] > [data-id]'),
				]
					.filter((el) => el !== menu)
					.filter((el) => {
						const elRect = el.getBoundingClientRect()

						return [
							menuRect,

							...[...menu.firstElementChild.children].map(
								(child) => child.getBoundingClientRect()
							),
						].some((rect) => {
							const intersectsLeftEdge =
								elRect.left < rect.left &&
								elRect.right > rect.left

							const intersectsRightEdge =
								elRect.right > rect.right &&
								elRect.left < rect.right

							const isInside =
								elRect.left > rect.left &&
								elRect.right < rect.right

							return (
								intersectsLeftEdge ||
								intersectsRightEdge ||
								isInside
							)
						})
					})

				const parentRect = menu.parentElement.getBoundingClientRect()

				const fitsLeftSide = menuRect.left > parentRect.left
				const fitsRightSide = menuRect.right < parentRect.right

				const fits =
					fitsLeftSide && fitsRightSide && allEls.length === 0

				if (fits) {
					menu.dataset.responsive = 'yes'
				}

				return !fits
			})
		},
	},
]
