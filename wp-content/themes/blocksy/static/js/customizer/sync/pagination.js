import {
	getPrefixFor,
	getOptionFor,
	responsiveClassesFor,
	watchOptionsWithPrefix,
	applyPrefixFor,
} from './helpers'

const prefix = getPrefixFor({
	allowed_prefixes: [
		'blog',
		'search',
		'author',
		'categories',
		'woo_categories',
	],
	default_prefix: 'blog',
})

const optionPrefix = ['author', 'categories'].includes(prefix) ? 'blog' : prefix

watchOptionsWithPrefix({
	getPrefix: () => prefix,
	getOptionsForPrefix: () => [
		`${optionPrefix}_load_more_label`,
		`${optionPrefix}_paginationDivider`,
		`${optionPrefix}_numbers_visibility`,
		`${optionPrefix}_arrows_visibility`,
	],

	render: () => {
		if (document.querySelector('.ct-load-more')) {
			document.querySelector('.ct-load-more').innerHTML = getOptionFor(
				'load_more_label',
				optionPrefix
			)
		}

		;[...document.querySelectorAll('.ct-pagination')].map((el) => {
			el.removeAttribute('data-divider')
			;[...el.parentNode.querySelectorAll('nav > a')].map((el) => {
				responsiveClassesFor(
					getOptionFor('arrows_visibility', optionPrefix),
					el
				)
			})
			;[...el.parentNode.querySelectorAll('nav > div')].map((el) => {
				responsiveClassesFor(
					getOptionFor('numbers_visibility', optionPrefix),
					el
				)
			})

			if (
				getOptionFor('paginationDivider', optionPrefix).style === 'none'
			) {
				return
			}

			if (
				getOptionFor('pagination_global_type', optionPrefix) ===
				'infinite_scroll'
			) {
				return
			}

			el.dataset.divider = ''
		})
	},
})

export const getPaginationVariables = () => ({
	[`${optionPrefix}_paginationSpacing`]: {
		selector: applyPrefixFor('.ct-pagination', prefix),
		variable: 'spacing',
		responsive: true,
		unit: '',
	},

	[`${optionPrefix}_paginationDivider`]: {
		selector: applyPrefixFor('.ct-pagination[data-divider]', prefix),
		variable: 'pagination-divider',
		type: 'border',
		skip_none: true,
	},

	[`${optionPrefix}_pagination_border_radius`]: {
		selector: applyPrefixFor('.ct-pagination', prefix),
		type: 'spacing',
		variable: 'theme-button-border-radius',
		emptyValue: 4,
	},

	[`${optionPrefix}_simplePaginationFontColor`]: [
		{
			selector: applyPrefixFor(
				'[data-pagination="simple"], [data-pagination="next_prev"]',
				prefix
			),
			variable: 'theme-text-color',
			type: 'color:default',
		},

		{
			selector: applyPrefixFor(
				'.ct-pagination[data-pagination="simple"]',
				prefix
			),
			variable: 'theme-text-active-color',
			type: 'color:active',
		},

		{
			selector: applyPrefixFor(
				'[data-pagination="simple"], [data-pagination="next_prev"]',
				prefix
			),
			variable: 'theme-link-hover-color',
			type: 'color:hover',
		},
	],

	[`${optionPrefix}_paginationButtonText`]: [
		{
			selector: applyPrefixFor('[data-pagination="load_more"]', prefix),
			variable: 'theme-button-text-initial-color',
			type: 'color:default',
		},

		{
			selector: applyPrefixFor('[data-pagination="load_more"]', prefix),
			variable: 'theme-button-text-hover-color',
			type: 'color:hover',
		},
	],

	[`${optionPrefix}_paginationButton`]: [
		{
			selector: applyPrefixFor('[data-pagination="load_more"]', prefix),
			variable: 'theme-button-background-initial-color',
			type: 'color:default',
		},

		{
			selector: applyPrefixFor('[data-pagination="load_more"]', prefix),
			variable: 'theme-button-background-hover-color',
			type: 'color:hover',
		},
	],
})
