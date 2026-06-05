<?php

if (empty($class)) {
	$class = 'mobile-menu-inline menu-container';
}

$class .= ' ' . blocksy_visibility_classes(blocksy_default_akg(
	'inline_menu_visibility',
	$atts,
	[
		'tablet' => true,
		'mobile' => true,
	]
));

$stretch_output = '';

if (blocksy_default_akg('inline_menu_stretch_menu', $atts, 'no') === 'yes') {
	$stretch_output = 'data-stretch';
}

$menu_args = [
	'container' => false,
	'menu_class' => 'menu',
	'depth' => 1,
	'fallback_cb' => 'blocksy_main_menu_fallback',
	'blocksy_advanced_item' => true,
	'theme_location' => $location
];

$menu = blocksy_default_akg('menu', $atts, 'blocksy_location');

if (! wp_get_nav_menu_object($menu) && $menu !== 'blocksy_location') {
	$menu = 'blocksy_location';
}

$menu_object = null;

if ($menu === 'blocksy_location') {
	$theme_locations = get_nav_menu_locations();

	$menu_object = wp_get_nav_menu_object('');

	if (isset($theme_locations[$location])) {
		$menu_object = get_term($theme_locations[$location], 'nav_menu');
	}
} else {
	$menu_args['menu'] = $menu;

	$menu_object = wp_get_nav_menu_object($menu);
}

ob_start();
wp_nav_menu($menu === 'blocksy_location' ? [
	'container' => false,
	'menu_class' => 'menu',
	'depth' => 1,
	'fallback_cb' => 'blocksy_main_menu_fallback',
	'blocksy_advanced_item' => true,
	'theme_location' => $location
] : array_merge([
	'container' => false,
	'menu_class' => 'menu',
	'depth' => 1,
	'fallback_cb' => 'blocksy_main_menu_fallback',
	'blocksy_advanced_item' => true,
], $menu_args));
$menu_content = ob_get_clean();

$aria_label = '';

if ($menu_object && isset($menu_object->name)) {
	$aria_label = 'aria-label="' . esc_attr($menu_object->name) . '"';
}

?>

<nav
	class="<?php echo esc_attr($class) ?>"
	<?php echo blocksy_attr_to_html($attr) ?>
	<?php echo $stretch_output ?>
	<?php echo blocksy_schema_org_definitions('navigation') ?>
	<?php echo $aria_label ?>>

	<?php echo $menu_content; ?>
</nav>

