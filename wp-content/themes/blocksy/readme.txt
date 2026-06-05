Blocksy is a fast, modern WordPress theme with advanced WooCommerce support and full compatibility with the block editor.

=== Blocksy ===
Contributors: creativethemeshq
Website: https://creativethemes.com
Tags: accessibility-ready, blog, block-patterns, e-commerce, wide-blocks, block-styles, grid-layout, one-column, two-columns, three-columns, four-columns, right-sidebar, left-sidebar, translation-ready, custom-colors, custom-logo, custom-menu, featured-images, footer-widgets, full-width-template, theme-options, threaded-comments
Requires at least: 5.2
Requires PHP: 7.0
Tested up to: 7.0
Stable tag: trunk
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Blocksy is a fast and lightweight WordPress theme built with the latest web technologies. It was designed from the ground up to be fully compatible with the Gutenberg editor and offers deep integration with popular page builders like Elementor, Brizy, and Beaver Builder. With a modern and modular architecture, Blocksy gives you full control over your site’s layout, colors, typography, and functionality.

It’s ideal for creating any type of website, including blogs, portfolios, business sites, and WooCommerce stores. The theme is responsive, translation-ready, accessibility-ready, and SEO-friendly. It also includes features like advanced header and footer builders, customizable sidebars, global design controls, and extended WooCommerce compatibility including AJAX filters, product galleries, and off-canvas features.

Blocksy is built for performance and flexibility, offering a solid foundation for both beginners and developers who want a reliable, extendable theme that works out of the box.

== Features ==

* Advanced Header & Footer Builder – Drag-and-drop builder with sticky, transparent, and overlapping header options. Create unique headers/footers per page or post with conditional logic.
* Content Blocks System – Create reusable content sections and display them anywhere using hooks, shortcodes, or custom triggers.
* Custom Sidebars & Widget Areas – Assign different sidebars to individual posts, pages, categories, and more.
* Built-in Dark Mode Toggle – Offer users an automatic or manual dark mode switch without third-party plugins.
* Global Color Palette System – Define and manage global color variables for consistent design across your site.
* Custom Layouts per Post Type – Control the structure of archives, single posts, pages, and custom post types with flexible layout presets.
* Performance-First Modular System – Disable unused theme features to reduce bloat and improve load times.
* Developer Friendly – Clean, extendable code with WordPress hooks, filters, and custom code injection for JS/CSS.
* Dynamic Content Controls – Use conditional display rules for headers, content blocks, footers, and widgets based on user roles, pages, devices, and more.
* Modern Design Toolkit – Responsive controls, custom spacing, advanced typography, transparent headers, and more design freedom with no code.

== Installation ==

1. In your admin panel, go to Appearance > Themes and click the Add New button.
2. Type in Blocksy in the search form and press the 'Enter' key on your keyboard.
3. Click Activate to use your new theme right away.

== Build Instructions ==

This theme contains some JavaScript files that need a compilation step in
order to be consumable by the browsers. The compilation is done by the
[`build-process`](https://github.com/creative-Themes/build-process) package
(which is just a preset config over WebPack). We do plan to eventually migrate
over to [`wp-scripts`](https://github.com/WordPress/gutenberg/tree/master/packages/scripts)
as the build pipeline.

So, in order to build the theme files, you need to execute these commands:

1. `npm install` or `yarn install`, both work just fine.
2. `npm run build` -- for a production build, or `npm run dev` for developments builds, which include a proper file watcher
3. The final files will be included in `admin/dashboard/static/bundle` and `static/bundle` directories.

The repeated `BlocksyReact`, `BlocksyReactDOM` and `wp.element` got enqueued
that way for two reasons:

1. We started to use React Hooks in WordPress 5.1, and we needed an actual
version of `wp.element` for that. A version of `wp.element` with hooks got
shipped only with WordPress 5.2. We planned on getting rid of our global version as
soon as 5.2 got released, but now I see a problem with backwards
compatibility.
2. We need to use a global version of React and ReactDOM because we have some
components using hooks both in the theme an din the `blocksy-companion` plugin
(https://creativethemes.com/downloads/blocksy-companion.zip). That way we
avoid breaking the rules of hooks.

== Frequently Asked Questions ==

= Does Blocksy work with page builders? =
Yes, Blocksy is fully compatible with popular page builders like Gutenberg, Elementor, Brizy, and Beaver Builder.

= Is Blocksy optimized for speed? =
Yes, Blocksy is built with performance in mind, featuring clean and efficient code that loads quickly and passes Core Web Vitals.

= Can I use Blocksy for WooCommerce shops? =
Absolutely. Blocksy includes deep WooCommerce integration, offering advanced features for online stores.

= Is Blocksy translation-ready? =
Yes, Blocksy is fully translation-ready and supports RTL languages.

== How can I report security bugs? ==

You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/blocksy)

== Screenshot Licenses ==

Screenshot images are all licensed under CC0 Public Domain
http://streetwill.co/posts/749-az4
http://streetwill.co/posts/788-black-day
http://streetwill.co/posts/205-peaceful
http://streetwill.co/posts/497-coloration
http://streetwill.co/posts/811-grass-flower-on-sunset-background
http://streetwill.co/posts/454-camber-sands-beach-house
http://streetwill.co/posts/350-golden
http://streetwill.co/posts/610-food5
http://streetwill.co/posts/853-aucstp

== Copyright ==
Blocksy WordPress Theme, Copyright 2019 creativethemes.com
Blocksy is distributed under the terms of the GNU GPL

Blocksy bundles the following third-party resources:

@wordpress/element, WordPress - Web publishing software, Copyright 2011-2019 by the contributors
Licenses: GNU GENERAL PUBLIC LICENSE
Source: https://github.com/WordPress/gutenberg/tree/master/packages/element

@wordpress/date, WordPress - Web publishing software, Copyright 2011-2019 by the contributors
Licenses: GNU GENERAL PUBLIC LICENSE
Source: https://github.com/WordPress/gutenberg/tree/master/packages/date

@wordpress/i18n, WordPress - Web publishing software, Copyright 2011-2019 by the contributors
Licenses: GNU GENERAL PUBLIC LICENSE
Source: https://github.com/WordPress/gutenberg/tree/master/packages/i18n

bezier-easing, Copyright (c) 2014 Gaëtan Renaudeau
Licenses: MIT License
Source: https://github.com/gre/bezier-easing

classnames, Copyright (c) 2018 Jed Watson
Licenses: The MIT License (MIT)
Source: https://github.com/JedWatson/classnames

deep-equal
Licenses: MIT. Derived largely from node's assert module.
Source: https://github.com/substack/node-deep-equal

dom-chef, Copyright (c) Vadim Demedes <vdemedes@gmail.com> (github.com/vadimdemedes)
Licenses: The MIT License (MIT)
Source: https://github.com/vadimdemedes/dom-chef

downshift, Copyright (c) 2017 PayPal
Licenses: The MIT License (MIT)
Source: https://github.com/downshift-js/downshift

infinite-scroll
Licenses: GNU GPL license v3
Source: https://github.com/metafizzy/infinite-scroll#commercial-license

intersection-observer, http://www.w3.org/Consortium/Legal/2015/copyright-software-and-document
Licenses: W3C Software and Document License
Source: https://github.com/w3c/IntersectionObserver

nanoid, Copyright 2017 Andrey Sitnik <andrey@sitnik.ru>
Licenses: The MIT License (MIT)
Source: https://github.com/ai/nanoid

objectFitPolyfill, Made by Constance Chen
Licenses: Released under the MIT license
Source: https://github.com/constancecchen/object-fit-polyfill

react-outside-click-handler, Copyright (c) 2018 Airbnb
Licenses: MIT License
Source: https://github.com/airbnb/react-outside-click-handler

react-spring, Copyright (c) 2018 Paul Henschel
Licenses: MIT License
Source: https://github.com/drcmda/react-spring

react-transition-group, Copyright (c) 2018, React Community
Licenses: BSD 3-Clause License
Source: https://github.com/reactjs/react-transition-group

scriptjs, Copyright (c) 2011 - 2015 Dustin Diaz <dustin@dustindiaz.com>
Licenses: The MIT License
Source: https://github.com/ded/script.js

simple-linear-scale
Licenses: The MIT License (MIT)
Source: https://github.com/tmcw-up-for-adoption/simple-linear-scale

use-force-update, Copyright (c) 2018 Charles Stover
Licenses: MIT License
Source: https://github.com/CharlesStover/use-force-update

mobius1-selectr, Copyright 2016 Karl Saunders
Licenses: MIT License
Source: https://github.com/Mobius1/Selectr

rellax.js, Copyright 2016 Moe Amaya
Licenses: MIT License
Source: https://github.com/dixonandmoe/rellax/

