=== Blocksy Companion ===
Tags: widget, widgets
Requires at least: 6.5
Requires PHP: 7.0
Tested up to: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 2.1.44

The official companion plugin for Blocksy theme, packed with starter sites, extra features, and integrations.

== Description ==

Blocksy Companion is the official plugin that extends the power of the [Blocksy WordPress theme](https://wordpress.org/themes/blocksy/). It unlocks extra features, premium integrations, and beautifully designed starter sites that help you build fast, modern, and fully customizable websites with ease.

With Blocksy Companion, you get access to a growing library of starter sites, advanced WooCommerce enhancements, and exclusive extensions that take your website to the next level. Whether you’re creating a personal blog, a business website, or a professional online store, this plugin provides the tools you need to design stunning layouts and improve your workflow.

Built for performance and flexibility, Blocksy Companion integrates seamlessly with popular WordPress plugins like WooCommerce, Elementor, Brizy, Beaver Builder, WPML and ACF. It gives you powerful customization options, dynamic features, and optimized code — all without slowing down your site.

= Key features: =

- **Starter Sites Library** – import professionally designed demo sites with one click and kickstart your project.
- **Extra Extensions** – unlock advanced features such as Cookies Consent, Custom Code Snippets, Trending Posts, and more.
- **WooCommerce Enhancements** – boost your online store with additional customization options and modern layouts.
- **Page Builder Ready** – works perfectly with Elementor, Brizy, Beaver Builder, and other popular WordPress page builders.
- **Advanced Integrations** – seamless compatibility with Mailchimp, Rank Math, WPML, and other top WordPress plugins.
- **Performance Focused** – lightweight, fast, and optimized for speed to ensure a smooth user experience.
- **Developer Friendly** – clean, extendable codebase that makes customization and scaling easy.

= Minimum Requirements =

* WordPress 5.0 or greater
* PHP version 7.0 or greater

== Installation ==

1. Upload `Blocksy-Companion-version_number.zip` to the `/wp-content/plugins/` directory and extract.
2. Activate the plugin by going to **Plugins** page in WordPress admin and clicking on **Activate** link.

== Frequently Asked Questions ==

= Why do I need this plugin? =

You need this plugin if you are using the Blocksy theme. It gives you the
ability to install any starter site and also a lot of additional extensions
and features.

= How do I use the Blocksy Companion? =

After you installed this plugin, just go to the Blocksy dashboard from the top
level menu in the WordPress admin area. When you are there, you will be able
to see all the awesome extensions that the plugin brings and also the starter
sites that are ready to be imported with just a few clicks and get you started
in no time.

= What do I do if the plugin doesn't work? =

If you are using the free version of the plugin, you can post your issue on
the support forum of the plugin. In case you have bought the premium version,
please reach out to the support
[here](https://creativethemes.com/blocksy/support/).

= How can I report security bugs? =

You can report security bugs through the Patchstack Vulnerability Disclosure
Program. The Patchstack team help validate, triage and handle any security
vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/blocksy-companion)

== Changelog ==
2.1.44: 2026-05-29
- Improvement: General fixes and improvements

2.1.43: 2026-05-28
- Improvement: Dynamic data - Ensure lightbox is rendered correctly for fields of type image
- Improvement: Ensure customizer options import work well in all browsers
- Improvement: General accessibility improvements
- Improvement: Improved compatibility with Installatron software and WP CLI config
- Improvement: Refactor search form markup for better accessibility and consistency

2.1.42: 2026-05-14
- Improvement: General fixes and improvements

2.1.41: 2026-05-07
- Improvement: Demo install - detect if a plugin can not be installed during the demo import and show a notice message

2.1.40: 2026-05-01
- Improvement: General fixes and improvements

2.1.39: 2026-04-30
- Improvement: Newsletter subscribe - make sure EmailOctopus integration works properly with the double opt-in feature
- Improvement: Newsletter subscribe - make sure the Gutenberg block saves correctly the custom list

2.1.38: 2026-04-09
- Improvement: Better integration with Nextend Social Login plugin and account header element
- Improvement: UI improvements related to WordPress 7.0 changes
- Fix: Cookies Consent - avoid unnecessary admin-ajax.php calls on every page load

2.1.37: 2026-03-26
- Fix: More reliable backward compatibility with theme

2.1.36: 2026-03-26
- Fix: Sticky header - fix transparent state not restoring on scroll up at fractional DPR screens

2.1.35: 2026-03-12
- Improvement: Make the assets URL to show the correct plugin version

2.1.34: 2026-03-06
- Improvement: Account header element - small accessibility improvement
- Fix: Demo importer - patch transparent conditions post IDs in install finish step

2.1.33: 2026-03-05
- Improvement: Advanced posts block - smarter output of markup attributes when slider mode is enabled
- Improvement: Starter sites - ensure post IDs are referenced correctly in transparent header conditions after starter site install

2.1.32: 2026-02-27
- Improvement: Advanced posts block - make sure grid styles are no applied when slider mode is enabled
- Improvement: Newsletter Subscribe - use real user agent for MailerLite API requests

2.1.31: 2026-02-26
- Improvement: Newsletter extension - better compatibility with MailChimp provider

2.1.30: 2026-02-20
- Improvement: Newsletter extension - apply the "double opt-in" functionality for Klaviyo and Mailerlite integration

2.1.29: 2026-02-18
- Improvement: Newsletter extension - correctly apply the "double opt-in" feature for Mailchimp integration

2.1.28: 2026-02-12
- Improvement: Product Reviews - add "address" field option for "Local Business" entity type
- Improvement: Starter sites importer - more reliable way of installing Elementor starter sites

2.1.27: 2026-01-30
- Improvement: Demo importer - correctly export and import ACF custom taxonomies
- Improvement: Dynamic data image - make sure cover type styles are loaded on demand

2.1.26: 2026-01-29
- Improvement: Advanced Posts Block - display a "no results found" message if posts don't meet the query criteria
- Improvement: Dynamic data - add option to show "Archive Label" if "Archive Title" source is selected

2.1.25: 2026-01-16
- Improvement: General fixes and improvements

2.1.24: 2026-01-15
- Improvement: Account modal - better integration with JetPack's brute force protection feature
- Improvement: Account modal - use template based approach for passing markup of the account content
- Improvement: Advanced Posts block - correctly detect current post for custom fields when rendering cards
- Improvement: Advanced posts & taxonomies blocks - make sure the block spacing option is correctly outputted in backed
- Improvement: Advanced taxonomies block - add pagination functionality
- Improvement: Cloudflare cache integration - update namespace for the CF classes
- Improvement: Dynamic data block - make sure the image width is correctly handled in the block editor
- Improvement: Dynamic data block - option to place "before/after" content inside the link if the link option is enabled
- Improvement: HTTP2 chunked response breaks sse responses in starter site install
- Improvement: Install companion notice - make sure the notice box appears only on specified dashboard pages
- Improvement: Newsletter Subscribe block - improve handling for fields label edit flow in block editor
- Improvement: SVG uploads - ensure all uploaded svg are persisted as fully valid HTML
- Improvement: Social icons block - add horizontal alignment option

2.1.23: 2025-12-11
- Improvement: Account modal - integration with "Sign in with Google" option from Google Site Kit plugin
- Improvement: Account modal - integration with Defender plugin from WPMUDEV
- Improvement: Advanced Posts block - dynamic data image (cover) outputs the correct CSS after WordPress 6.9 update
- Improvement: Demo importer - add compatibility with Fluent Snippets plugin
- Improvement: Demo importer - more resilient flow for installing demo content
- Improvement: Ensure slider assets are loaded correct for Advanced Posts placed in Gutenberg patterns
- Improvement: Newsletter Subscribe extension - improve logic for passing the name in Mailchimp integration
- Improvement: Newsletter subscribe block - make sure the border radius option outputs a linked value all the time

2.1.22: 2025-11-27
- Improvement: Demo importer - smarter and more resilient strategy for importing the content on servers with low resources

2.1.21: 2025-11-13
- Improvement: General fixes and improvements

2.1.20: 2025-11-07
- Improvement: Shop Extra/Filters - correctly clean up query params when filtering by price
- Improvement: Smarter and safer logic for handling SVG upload
- Fix: Improved logic for Blocksy theme detection

2.1.19: 2025-11-06
- Improvement: Account modal - better integration with JetPack plugin
- Improvement: Better detection for Breakdance "theme disabler" option
- Improvement: Demo install - correctly reset the install process after closing the demo import modal
- Improvement: Make sure the SVG upload through customizer works properly

2.1.18: 2025-10-30
- Fix: Site Icon feature does not work with SVGs when Companion plugin is active

2.1.17: 2025-10-17
- Improvement: General fixes and improvements

2.1.16: 2025-10-16
- Improvement: Demo install - reset onboarding steps only when demo install is not in process
- Improvement: Improved filter by product brands and dynamic data display in widget area
- Improvement: Starter sites - correctly convert menu custom links to match the site URL
- Fix: Properly check `customize_theme` param without attempting to sanitize special characters

2.1.15: 2025-10-02
- Improvement: Advanced taxonomies block - correctly respect order for product categories
- Improvement: Dynamic data - smarter and cleaner render of the excerpt in the block editor

2.1.14: 2025-09-25
- Improvement: Demo importer - better handling of importer UI state when the import process is finished
- Improvement: Demo importer - correctly import `WP WXR` when other instances of WordPress importer are present
- Improvement: Dynamic data block - proper detection for MetaBox fields
- Improvement: Dynamic data block - properly detect context when block is placed inside widget area
- Improvement: Properly load slider styles when Advanced Posts in used in Elementor template through a shortcode
- Fix: Advanced posts block - skip non-existing taxonomies in `include_term_ids` check

2.1.13: 2025-09-18
- Improvement: Make sure the demo importer is importing the custom fonts correctly
- Improvement: Options Import - Safer parsing of import files to handle multiline strings with quotes inside

2.1.12: 2025-09-11
- Improvement: General fixes and improvements

2.1.11: 2025-09-11
- Improvement: Advanced Posts block - image alignment issues in Safari when editing the block
- Improvement: Advanced taxonomies block - introduce `blocksy:general:blocks:query:args` filter
- Improvement: Advanced taxonomies block - respect product categories order
- Improvement: Demo importer - properly change media URL's for widget area content when importing the demo
- Improvement: Properly sanitize newsletter subscribe form description

2.1.10: 2025-09-04
- Improvement: Check for file existence in header elements that support SVG upload
- Improvement: Don't load outdated polyfills in the JS files served in modern browsers

2.1.9: 2025-08-21
- Improvement: General fixes and improvements

2.1.8: 2025-08-14
- Improvement: Newsletter block - make sure the name field placeholder is correctly outputted
- Improvement: Trending posts module - accessible improvements

2.1.7: 2025-08-08
- Improvement: General fixes and improvements

2.1.6: 2025-08-07
- Improvement: Newsletter subscribe - update the Kit (ConvertKit) integration to work with latest API version

2.1.5: 2025-07-31
- Improvement: Sticky header - ensure animation speed is always greater than zero

2.1.4: 2025-07-17
- Improvement: Account header element - accessibility improvement

2.1.3: 2025-07-10
- Improvement: Advanced Posts block - properly compute the spacing option
- Improvement: Customizer import/export feature - safely unserialize data on import
- Improvement: Dynamic Data block - small accessibility improvement when the block is used as a featured image
- Improvement: Introduce PHP filter to disable SVG sanitization

2.1.2: 2025-07-03
- Improvement: Correctly respect Loco Translate custom translation locations

2.1.1: 2025-06-26
- Improvement: Sticky header - better calculations for sticky position of rows hidden on mobile devices
- Improvement: Sticky header - proper offset when store notice is enabled

2.1.0: 2025-06-05
- Improvement: Breadcrumbs block - make sure the editor preview respects the context
- Improvement: Newsletter subscribe - integration with Email Octopus
- Improvement: Render HTML tags for the "Dynamic Data" block in the editor preview for before/after options
- Improvement: SVG dimensions feature failsafe on attachments with broken meta
- Improvement: Support for product attributes in the dynamic data block

2.0.99: 2025-05-22
- Improvement: Advanced Search block - make sure the additional CSS classes are outputted in frontend
- Improvement: Sticky header - don't include hidden rows in the calculation
- Improvement: Trending posts/products - make sure the "Product Status" option work properly when used with "Trending From" option

2.0.98: 2025-05-01
- Improvement: Advanced taxonomies block does not display anything if an image has not been assigned to a term

2.0.97: 2025-04-24
- Improvement: Correctly check for Blocksy theme presence in WP CLI context

2.0.96: 2025-04-17
- Improvement: Advanced Posts block - Better generation of unique ID to avoid clashes
- Improvement: Correct handle skip-themes flag in WP CLI when detecting presence of Blocksy theme
- Improvement: Correctly respect terms order from WooCommerce in the Shop Filter block
- Fix: Cookies Consent popup does not render if header is disabled on a certain page

2.0.95: 2025-04-04
- Improvement: Minor accessibility improvements
- Fix: Advanced posts block doesn't apply column CSS on front-end
- Fix: Fix FOUC issues for Advanced Posts and Advanced Taxonomies blocks in some specific situations

2.0.94: 2025-04-03
- Improvement: Ensure permalinks are regenerated properly when WP Toolkit checks for updates
- Fix: Conditions module - ensure stability of post searching

2.0.93: 2025-03-27
- Improvement: Functionality to import/export menus created with the Advanced Menu extension
- Improvement: Make sure advanced search block respects the products taxonomy filter criteria option
- Improvement: Optimize users search retrieval in conditions module on sites with a height amount of users

2.0.92: 2025-03-13
- Improvement: Account drop down custom link url string support
- Improvement: Account header element - bring back the tablet/mobile dropdown functionality only for main header rows
- Improvement: Mare sure Product Reviews archive star rating respect the horizontal alignment option
- Improvement: Safer calling of theme functions in the companion plugin
- Fix: Multiple Advanced Posts blocks on a single page, scoped by category, load incorrect posts when using pagination

2.0.91: 2025-02-27
- Improvement: Ability to change the order of first & last name in the account header element drop down
- Improvement: Better integration with WPMobile app plugin
- Improvement: Starter sites importer proper check for DOM PHP extension presence

2.0.90: 2025-02-21
- Improvement: General fixes and improvements

2.0.89: 2025-02-20
- Improvement: Properly check for is_account_page in account modal

2.0.88: 2025-02-20
- Improvement: Account element - add link option for mobile devices
- Improvement: Account element - correctly positioned dropdown menus
- Improvement: Advanced Taxonomies block - add option to hide/show empty taxonomies
- Improvement: Correctly mount components root for React 18
- Improvement: Product Reviews extension - archive rating formatting issue

2.0.87: 2025-02-06
- Improvement: General fixes and improvements

2.0.86: 2025-01-27
- Improvement: Newsletter Subscribe - Better handling of Custom List ID in the newsletter subscribe block

2.0.85: 2025-01-23
- Improvement: Properly generate the CSS file when copying customizer values from parent theme to child theme
- Improvement: Sticky header - better calculation if margins have been set for specific elements

2.0.84: 2025-01-09
- Improvement: Demo importer - make sure Elementor "Post Types" option is exported properly
- Improvement: Demo importer - properly regenerate taxonomies css file after demo install
- Improvement: Properly install and activate Blocksy theme from dashboard notice in a multi site context
- Improvement: Sticky header - better calculation for shrink row and logo functionality

2.0.83: 2024-12-25
- Improvement: Account modal header element - better integration with MailPoet plugin
- Improvement: Advanced Taxonomy block - smarter check for layout type
- Improvement: Dynamic data block - featured image cover type improvements
- Improvement: Standalone Gutenberg plugin breaks Advanced Posts block grid layout

2.0.82: 2024-12-20
- Improvement: General fixes and improvements

2.0.81: 2024-12-12
- Improvement: Advanced Search block - Correctly handle scroll on iOS

2.0.80: 2024-11-29
- Improvement: Advanced Posts block - make sure inner post template block spacing options is applied on frontend
- Improvement: Advanced Taxonomies block - make sure the column adjustments option for tablet/mobile devices is applied correctly
- Improvement: Correctly compute grid styles in Advanced Posts and Advanced taxonomies

2.0.79: 2024-11-28
- Improvement: Fetch only relevant user fields for the conditions module

2.0.78: 2024-11-21
- Improvement: Improve handling of global CSS file generation when no direct WP file system is used

2.0.77: 2024-11-14
- Improvement: Smarter handling of translations in JS files

2.0.76: 2024-11-07
- Improvement: Account modal - better integration with SecuPress plugin
- Improvement: Make sure uploaded SVG's receive the correct sizes for ratio calculations
- Improvement: Newsletter subscribe extension - deprecate JSONP and use the provided API for Mailchimp integration
- Improvement: Starter sites installation - proper URL mapping

2.0.75: 2024-10-24
- Improvement: General fixes and improvements

2.0.74: 2024-10-24
- Improvement: Demo importer - don't import FluentBooking calendar twice
- Improvement: Demo importer - preselect required builder when importing if demos where filtered by a specific builder

2.0.73: 2024-10-11
- Improvement: General fixes and improvements

2.0.72: 2024-10-10
- Improvement: Better error reporting of missing XML extension in starter sites

2.0.71: 2024-10-04
- Improvement: General fixes and improvements

2.0.70: 2024-10-03
- Improvement: Dont require updates script from Core when installing or updating theme from companion

2.0.69: 2024-09-26
- Improvement: General fixes and improvements

2.0.68: 2024-09-19
- Improvement: Correctly regenerate dynamic CSS after All in One WP Migration import is done

2.0.67: 2024-09-12
- Improvement: General fixes and improvements

2.0.66: 2024-09-06
- Improvement: General fixes and improvements

2.0.65: 2024-09-05
- Improvement: Better integrate demo importer with Fluent Booking plugin
- Improvement: Include custom palettes in customizer export file

2.0.64: 2024-08-29
- Improvement: General fixes and improvements

2.0.63: 2024-08-22
- Improvement: Better integration with FluentBooking plugin in starter sites

2.0.62: 2024-08-15
- Improvement: Improved check for current theme in the customizer preview
- Improvement: Starter sites - more reliable content installation

2.0.61: 2024-08-08
- Improvement: Starter sites installer ensure content type header is passed correctly in all requests
- Fix: Product reviews extension - issue with short description option

2.0.60: 2024-08-01
- Improvement: Correctly load JSON translation files for JS files
- Improvement: Declare text domain correctly so that Loco Translate is able to override translations

2.0.59: 2024-07-25
- Improvement: Removed old Google Analytics V3 option

2.0.58: 2024-07-17
- Improvement: Account module - better integration with Admin Enhancements Pro login redirection plugin
- Improvement: Advanced posts block - add option to change columns number per device
- Improvement: Trending posts module improvements
- Fix: Advanced posts and taxonomies blocks - columns issue in WordPress 6.6

2.0.57: 2024-07-04
- Fix: Multi site crashes with Companion file referenced when Blocksy theme is attempted to be activated
- Fix: Trending Posts extension gets stuck if a selected post type gets un-registered

2.0.56: 2024-06-27
- Fix: Fixed an issue when dashboard bulk select was not working properly

2.0.55: 2024-06-21
- Fix: Account modal drop down - properly stay on the same page after sign out

2.0.54: 2024-06-17
- Improvement: General fixes and improvements

2.0.53: 2024-06-07
- Improvement: General fixes and improvements

2.0.52: 2024-06-06
- New: Taxonomies query block
- Improvement: Regenerate `global.css` when copying settings from parent or child theme

2.0.51: 2024-05-30
- Improvement: Correctly load JS translations for the Blocksy Companion

2.0.50: 2024-05-24
- Improvement: Ensure required theme version is correctly verified within WP CLI
- Improvement: Login to WP dashboard through cPanel WP Toolkit doesn't work

2.0.49: 2024-05-23
- Improvement: General fixes and improvements

2.0.48: 2024-05-16
- Improvement: Sometimes the demo data cannot be persisted in DB due to some restrictions
- Fix: Account dropdown - correctly link Dokan Dashboard item

2.0.47: 2024-05-10
- Improvement: General fixes and improvements

2.0.46: 2024-05-09
- Improvement: Sanitize all SVG images upon upload into media gallery

2.0.45: 2024-05-03
- Improvement: General fixes and improvements

2.0.44: 2024-05-03
- Improvement: General fixes and improvements

2.0.43: 2024-05-02
- Improvement: Account element drop down menu remains open indefinitely if clicked on the element itself
- Improvement: Dont allow URLs in region portion of the Mailchimp API Key
- Improvement: Enforce year structure of uploads during starter site install
- Improvement: Make sure starter sites are correctly installed via WP CLI
- Improvement: Respect login_redirect filter in the account modal
- Improvement: Starter Site content install step dont pass XML in the request body
- Improvement: Starter sites list - better filtering by categories

2.0.42: 2024-04-25
- Improvement: Account header element profile photo support YITH Customize My Account plugin
- Improvement: Starter sites installation process improvements

2.0.41: 2024-04-19
- Improvement: Allow multiple instances of newsletter block in same post
- Fix: `[blocksy_posts]` shortcode type slider has weird spacing for pills

2.0.40: 2024-04-18
- Improvement: Newsletter Subscribe - add option to make the "name" field mandatory

2.0.39: 2024-04-11
- Improvement: General fixes and improvements

2.0.38: 2024-04-04
- Improvement: Introduce a WP CLI command for installing a starter site in one step
- Improvement: Make sure Product Reviews extension post contents is searchable

2.0.37: 2024-03-29
- Improvement: Correctly generate translation files

2.0.36: 2024-03-29
- Improvement: General fixes and improvements

2.0.35: 2024-03-28
- Improvement: General fixes and improvements

2.0.34: 2024-03-28
- Improvement: General fixes and improvements

2.0.33: 2024-03-21
- Improvement: Add filter functionality to starter sites
- Fix: Newsletter block and single subscribe form can't get the custom list ID

2.0.32: 2024-03-15
- Improvement: Correctly escape style attribute in newsletter subscribe block

2.0.31: 2024-03-14
- Fix: `blocksy_posts` shortcode with slider doesn't wrap images inside links

2.0.30: 2024-03-08
- Improvement: General fixes and improvements

2.0.29: 2024-03-07
- Improvement: Ensure all dashboard AJAX actions are protected with nonces

2.0.28: 2024-02-29
- Improvement: General fixes and improvements

2.0.27: 2024-02-29
- Improvement: Account header element - overflow issue when email address is very long
- Improvement: Display proper label for language conditions
- Improvement: Make sure the header account element is getting the correct user role

2.0.26: 2024-02-22
- Fix: PHP Warning when using Multi-Vendor Marketplace Lite plugin and account header element

2.0.25: 2024-02-15
- Improvement: General fixes and improvements

2.0.24: 2024-02-08
- Improvement: Don't output account modal for logged out users if account is disabled for them
- Improvement: Harden the check for blc_maybe_is_ssl() function
- Improvement: Introduce a safe sprintf function to avoid errors on translations

2.0.23: 2024-02-01
- Improvement: General fixes and improvements

2.0.22: 2024-01-25
- Improvement: General fixes and improvements

2.0.21: 2024-01-20
- Improvement: General fixes and improvements

2.0.20: 2024-01-19
- Improvement: General fixes and improvements

2.0.19: 2024-01-18
- New: Header account - option to add a menu inside the dropdown
- Improvement: Header account - user info dropdown options improvements

2.0.18: 2024-01-12
- Improvement: Correctly load Flexy styles when blocksy_posts shortcode is used in the slider mode

2.0.17: 2024-01-11
- Improvement: General fixes and improvements

2.0.16: 2024-01-05
- Improvement: General fixes and improvements

2.0.15: 2024-01-04
- Improvement: General fixes and improvements

2.0.14: 2023-12-26
- Improvement: Dont attempt to unslash post meta data upon starter site installation

2.0.13: 2023-12-22
- Improvement: Demo importer - make sure mega menus are imported correctly

2.0.12: 2023-12-21
- Improvement: General fixes and improvements

2.0.11: 2023-12-15
- Improvement: General fixes and improvements

2.0.10: 2023-12-14
- Improvement: General fixes and improvements

2.0.9: 2023-12-12
- Improvement: More reliable check for extensions preboot

2.0.8: 2023-12-08
- Improvement: General fixes and improvements

2.0.7: 2023-12-07
- Improvement: General fixes and improvements

2.0.6: 2023-12-06
- Fix: Customizer mode issue if child theme is active

2.0.5: 2023-12-06
- Improvement: Account drop down on mobile is placed out of bounds
- Fix: Content Blocks cannot be turned on/off if quick edited

2.0.4: 2023-11-30
- Improvement: Correctly execute pre boot for extensions in the dashboard
- Improvement: Introduce Product Taxonomy ID condition rule

2.0.3: 2023-11-29
- Improvement: Account header element under specific settings makes the Customizer break
- Fix: Trending posts article font size option doesn't work

2.0.2: 2023-11-27
- Improvement: Correctly load styles for the newsletter subscribe shortcode
- Improvement: Ensure extensions pre boot isn't executed on every ajax request

2.0.1: 2023-11-25
- Improvement: General fixes and improvements related to update

2.0.0: 2023-11-24
- New: Convert old legacy widgets to block widgets
- New: Dropdown menu for account widget in header
- New: Footer builder logo element - add lazy loading attribute to image
- New: More integrations for newsletter module (Sendinblue, CampaignMonitor, ConvertKit)
- New: New dashboard extensions screen
- Improvement: Account modal - integration with Two-Factor plugin
- Improvement: Account modal - use core registration if WooCommerce registration is disabled
- Improvement: Automatically load Google analytics on cookies notice approve
- Improvement: Performance - Faster PHP classes autoloader
- Improvement: Performance - Smarter load extensions CSS files (only when extensions is activated and present on a page)
- Improvement: Prefix CSS variables in order to not conflict with other plugins
- Fix: Prevent errors on SVG's size calculation

1.9.11: 2023-11-09
- Improvement: Better compatibility with WordPress 6.4

1.9.10: 2023-11-02
- Fix: Account `blocksy_get_avatar_url()` issue

1.9.9: 2023-11-02
- Improvement: Comments privacy policy link is not inheriting "Privacy page" option from dashboard

1.9.8: 2023-10-12
- Improvement: General fixes and improvements

1.9.7: 2023-09-28
- Fix: Posts widget gets corrupted if we select "no thumbnails"

1.9.6: 2023-09-21
- Improvement: General fixes and improvements

1.9.5: 2023-09-14
- Improvement: Conditions module bump limit of 500 users
- Improvement: Improve rounding logic in sticky shrink logic to better handle fractional header heights

1.9.4: 2023-09-07
- Improvement: General fixes and improvements

1.9.3: 2023-08-31
- Improvement: Demo importer better handle WooCommerce imports

1.9.2: 2023-08-24
- Improvement: Cookies consent button type

1.9.1: 2023-08-17
- Improvement: Trending posts module - more color and typography options
- Improvement: Correctly display of loading spinner in account modal

1.9.0: 2023-08-10
- Improvement: General fixes and improvements

1.8.99: 2023-08-09
- Improvement: Better scoping of taxonomies in blocksy_posts shortcode while filtering
- Improvement: Contact element - shortcode support in link field
- Fix: Import/export options disappear if UI Press Lite plugin is active

1.8.98: 2023-07-27
- Improvement: General fixes and improvements

1.8.97: 2023-07-19
- Improvement: General fixes and improvements

1.8.96: 2023-07-13
- Improvement: General fixes and improvements

1.8.95: 2023-07-05
- Improvement: General fixes and improvements

1.8.94: 2023-06-29
- Improvement: Correctly install and activate Blocksy theme from Companion

1.8.93: 2023-06-22
- Improvement: Ensure account logout action is not broken by WPML

1.8.92: 2023-06-14
- Improvement: General fixes and improvements

1.8.91: 2023-06-12
- Improvement: Don't perform HTTP JSON request in order to retrieve Google Fonts list
- Improvement: Do not allow bypassing comment form if privacy policy is not accepted

1.8.90: 2023-06-03
- Improvement: Proper detection of Blocksy theme in customizer

1.8.89: 2023-06-01
- Improvement: Better minimal theme version check flow

1.8.88: 2023-05-25
- Fix: Socials widget label issue

1.8.87: 2023-05-18
- Improvement: Starter site custom CSS avoid clashes with Elementor default Kit

1.8.86: 2023-05-11
- Improvement: Account  Show Password icon does not work on WooCommerce pages

1.8.85: 2023-05-04
- Improvement: Add autocomplete attribute to account modal input fields

1.8.84: 2023-04-20
- Improvement: Sticky header increased height calculation for widget area
- Improvement: Better display logic for taxonomy IDs condition
- Improvement: Shortcode support in header account label option
- Fix: Account modal -> registration -> eye icon to show password typed doesn't work

1.8.83: 2023-04-13
- Fix: Account modal `login page` string issue

1.8.82: 2023-04-06
- Improvement: Include only public posts for the query in the blocksy_posts shortcode

1.8.81: 2023-03-30
- Improvement: Account modal don't repeat email field ID
- Improvement: Smarter loading of Dokan scripts for the registration modal
- Improvement: Account modal header element shortcode support in "custom link" option

1.8.80: 2023-03-27
- Improvement: Compatibility with WordPress 6.2
- Improvement: Never expose real login url in the account modal
- Improvement: Header account element label not translatable via "String translations" (WPML)
- Fix: Newsletter module text color option not applying correctly

1.8.79: 2023-03-16
- Fix: Account modal some strings where not translatable

1.8.78: 2023-03-09
- Improvement: Prefix XML parser class to not clash with other plugins
- Improvement: Correctly extract SVG sizes in blocksy_image function

1.8.77: 2023-03-02
- Improvement: General fixes and improvements

1.8.76: 2023-02-23
- Improvement: Better detection of user and CPT conditions

1.8.75: 2023-02-15
- Improvement: General fixes and improvements

1.8.74: 2023-02-15
- Improvement: General fixes and improvements

1.8.73: 2023-02-09
- Improvement: Integrate B2B market with account modal
- Improvement: Newsletter subscribe extensions retrieve correctly all subscribers lists
- Improvement: More robust import of XML post data in starter sites

1.8.72: 2023-02-02
- Improvement: General fixes and improvements

1.8.71: 2023-01-26
- Improvement: General fixes and improvements

1.8.70: 2023-01-26
- Improvement: Smarter handling of login links in the account modal
- Improvement: Small improvement for filter argument in posts shortcode
- Improvement: Dont trigger WooCommerce actions in account modal when it is not active
- Improvement: Add image size option to posts widget

1.8.69: 2023-01-20
- Improvement: General fixes and improvements

1.8.68: 2023-01-20
- Improvement: Escape class attr for blocksy_posts shortcode
- Improvement: Introduce filter for controlling obfuscation process in the contacts link item

1.8.67: 2023-01-19
- Improvement: Ensure admin_body_class filter is called correctly
- Improvement: Earlier computation of trending posts results
- Improvement: Pass meta_value and meta_key fields to the blocksy_posts shortcode query

1.8.66: 2023-01-11
- Improvement: Better header sticky calculation with very high elements in the rows
- Fix: Negative margin should not break sticky header calculations

