<?php

namespace Blocksy;

class Colors {
	public function __construct() {
		add_filter('blocksy:options:colors:palette:palettes', [ $this, 'register_color_palettes' ]);

		add_action('wp_ajax_blocksy_get_custom_palettes', function () {
			if (! current_user_can('manage_options')) {
				wp_send_json_error();
			}

			$option = get_option('blocksy_custom_palettes', []);

			if (empty($option)) {
				$option = [];
			}

			if (! isset($option['palettes'])) {
				$option['palettes'] = [];
			}

			wp_send_json_success([
				'palettes' => $option['palettes']
			]);
		});

		add_action('wp_ajax_blocksy_sync_custom_palettes', function () {
			if (! current_user_can('manage_options')) {
				wp_send_json_error();
			}

			$body = json_decode(file_get_contents('php://input'), true);

			if (! isset($body['palettes'])) {
				wp_send_json_error();
			}

			update_option('blocksy_custom_palettes', [
				'palettes' => $body['palettes']
			]);

			wp_send_json_success($body);
		});
	}

	public function register_color_palettes() {
		$palettes = [
			[
				'id' => 'palette-1',

				'color1' => [
					'color' => '#2872fa',
				],

				'color2' => [
					'color' => '#1559ed',
				],

				'color3' => [
					'color' => '#3A4F66',
				],

				'color4' => [
					'color' => '#192a3d',
				],

				'color5' => [
					'color' => '#e1e8ed',
				],

				'color6' => [
					'color' => '#f2f5f7',
				],

				'color7' => [
					'color' => '#FAFBFC',
				],

				'color8' => [
					'color' => '#ffffff',
				],
			],

			[
				'id' => 'palette-2',

				'color1' => [
					'color' => '#007f5f',
				],

				'color2' => [
					'color' => '#55a630',
				],

				'color3' => [
					'color' => '#365951',
				],

				'color4' => [
					'color' => '#192c27',
				],

				'color5' => [
					'color' => '#E6F0EE',
				],

				'color6' => [
					'color' => '#F2F7F6',
				],

				'color7' => [
					'color' => '#FBFCFC',
				],

				'color8' => [
					'color' => '#ffffff',
				],
			],

			[
				'id' => 'palette-3',

				'color1' => [
					'color' => '#ff6310',
				],

				'color2' => [
					'color' => '#fd7c47',
				],

				'color3' => [
					'color' => '#687279',
				],

				'color4' => [
					'color' => '#111518',
				],

				'color5' => [
					'color' => '#E9EBEC',
				],

				'color6' => [
					'color' => '#F4F5F6',
				],

				'color7' => [
					'color' => '#ffffff',
				],

				'color8' => [
					'color' => '#ffffff',
				],
			],

			[
				'id' => 'palette-4',

				'color1' => [
					'color' => '#a8977b',
				],

				'color2' => [
					'color' => '#7f715c',
				],

				'color3' => [
					'color' => '#3f4245',
				],

				'color4' => [
					'color' => '#111518',
				],

				'color5' => [
					'color' => '#eaeaec',
				],

				'color6' => [
					'color' => '#f4f4f5',
				],

				'color7' => [
					'color' => '#ffffff',
				],

				'color8' => [
					'color' => '#ffffff',
				],
			],

			[
				'id' => 'palette-5',

				'color1' => [
					'color' => '#84a98c',
				],

				'color2' => [
					'color' => '#52796f',
				],

				'color3' => [
					'color' => '#cad2c5',
				],

				'color4' => [
					'color' => '#84a98c',
				],

				'color5' => [
					'color' => '#384b56',
				],

				'color6' => [
					'color' => '#212b31',
				],

				'color7' => [
					'color' => '#29363d',
				],

				'color8' => [
					'color' => '#314149',
				],
			],

			[
				'id' => 'palette-6',

				'color1' => [
					'color' => '#7456f1',
				],

				'color2' => [
					'color' => '#5e3fde',
				],

				'color3' => [
					'color' => '#4d5d6d',
				],

				'color4' => [
					'color' => '#102136',
				],

				'color5' => [
					'color' => '#E7EBEE',
				],

				'color6' => [
					'color' => '#F3F5F7',
				],

				'color7' => [
					'color' => '#FBFBFC',
				],

				'color8' => [
					'color' => '#ffffff',
				],
			],

			[
				'id' => 'palette-7',

				'color1' => [
					'color' => '#98c1d9',
				],

				'color2' => [
					'color' => '#E84855',
				],

				'color3' => [
					'color' => '#475671',
				],

				'color4' => [
					'color' => '#293241',
				],

				'color5' => [
					'color' => '#E7E9EF',
				],

				'color6' => [
					'color' => '#f3f4f7',
				],

				'color7' => [
					'color' => '#FBFBFC',
				],

				'color8' => [
					'color' => '#ffffff',
				],
			],

			[
				'id' => 'palette-8',

				'color1' => [
					'color' => '#ffcd05',
				],

				'color2' => [
					'color' => '#fcb424',
				],

				'color3' => [
					'color' => '#504e4a',
				],

				'color4' => [
					'color' => '#0a0500',
				],

				'color5' => [
					'color' => '#edeff2',
				],

				'color6' => [
					'color' => '#f9fafb',
				],

				'color7' => [
					'color' => '#FDFDFD',
				],

				'color8' => [
					'color' => '#ffffff',
				],
			],

			[
				'id' => 'palette-9',

				'color1' => [
					'color' => '#006466',
				],

				'color2' => [
					'color' => '#065A60',
				],

				'color3' => [
					'color' => '#7F8C9A',
				],

				'color4' => [
					'color' => '#ffffff',
				],

				'color5' => [
					'color' => '#1e2933',
				],

				'color6' => [
					'color' => '#0F141A',
				],

				'color7' => [
					'color' => '#141b22',
				],

				'color8' => [
					'color' => '#1B242C',
				],
			],

			[
				'id' => 'palette-10',

				'color1' => [
					'color' => '#00509d',
				],

				'color2' => [
					'color' => '#003f88',
				],

				'color3' => [
					'color' => '#828487',
				],

				'color4' => [
					'color' => '#28292a',
				],

				'color5' => [
					'color' => '#e8ebed',
				],

				'color6' => [
					'color' => '#f4f5f6',
				],

				'color7' => [
					'color' => '#FBFBFC',
				],

				'color8' => [
					'color' => '#ffffff',
				],
			],

			[
				'id' => 'palette-11',

				'color1' => [
					'color' => '#3eaf7c',
				],

				'color2' => [
					'color' => '#33a370',
				],

				'color3' => [
					'color' => '#415161',
				],

				'color4' => [
					'color' => '#2c3e50',
				],

				'color5' => [
					'color' => '#E2E7ED',
				],

				'color6' => [
					'color' => '#edeff2',
				],

				'color7' => [
					'color' => '#f8f9fb',
				],

				'color8' => [
					'color' => '#ffffff',
				],
			],

			[
				'id' => 'palette-12',

				'color1' => [
					'color' => '#FB7258',
				],

				'color2' => [
					'color' => '#F74D67',
				],

				'color3' => [
					'color' => '#6e6d76',
				],

				'color4' => [
					'color' => '#0e0c1b',
				],

				'color5' => [
					'color' => '#DFDFE2',
				],

				'color6' => [
					'color' => '#F4F4F5',
				],

				'color7' => [
					'color' => '#FBFBFB',
				],

				'color8' => [
					'color' => '#ffffff',
				],
			],
		];

		return $palettes;
	}

	public function get_color_palette($args = []) {
		$args = wp_parse_args(
			$args,
			[
				'id' => 'colorPalette',
				'default' => [
					'color1' => [
						'color' => '#2872fa',
					],

					'color2' => [
						'color' => '#1559ed',
					],

					'color3' => [
						'color' => '#3A4F66',
					],

					'color4' => [
						'color' => '#192a3d',
					],

					'color5' => [
						'color' => '#e1e8ed',
					],

					'color6' => [
						'color' => '#f2f5f7',
					],

					'color7' => [
						'color' => '#FAFBFC',
					],

					'color8' => [
						'color' => '#ffffff',
					]
				]
			]
		);

		$colorPalette = blocksy_get_theme_mod($args['id'], $args['default']);

		$result = [];

		foreach ($colorPalette as $key => $value) {
			if (strpos($key, 'color') === false) {
				continue;
			}

			$variableName = str_replace('color', 'theme-palette-color-', $key);

			if (isset($value['variable']) && ! empty($value['variable'])) {
				$variableName = $value['variable'];
			}

			if (! $value) {
				$value = $args['default'][$key];
			}

			$title = blocksy_safe_sprintf(
				__('Color %s', 'blocksy'),
				str_replace('color', '', $key)
			);

			if (isset($value['title'])) {
				$title = $value['title'];
			}

			$result[$key] = [
				'id' => $key,
				'slug' => 'palette-color-' . str_replace('color', '', $key),
				'color' => $value['color'],
				'variable' => $variableName,
				'title' => $title
			];
		}

		return $result;
	}
}
