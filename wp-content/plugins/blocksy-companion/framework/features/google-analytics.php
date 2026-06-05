<?php

namespace Blocksy;

if (! defined('ABSPATH')) {
	exit;
}

class GoogleAnalytics {
	public function __construct() {
		add_filter(
			'blocksy_engagement_general_start_customizer_options',
			[$this, 'generate_google_analytics_opts']
		);

		add_filter('blocksy:cookies-consent:scripts-to-load', function ($data) {
			$ga_4_code = $this->get_ga_4_code();

			if (! empty($ga_4_code)) {
				$data[] = $ga_4_code;
			}

			return $data;
		});

		if (is_admin()) return;

		add_action(
			'init',
			function () {
				add_action('wp_print_scripts', function () {
					if (is_admin()) return;

					if (class_exists('BlocksyExtensionCookiesConsent')) {
						if (! \BlocksyExtensionCookiesConsent::has_consent()) {
							return;
						}
					}

					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->get_ga_4_code();
				});
			}
		);
	}

	private function get_ga_4_code() {
		$analytics_v4_id = blocksy_companion_theme_functions()->blocksy_get_theme_mod('analytics_v4_id', '');

		if (empty($analytics_v4_id)) {
			return '';
		}

		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript

		ob_start(); ?>

		<!-- Global site tag (gtag.js) - Google Analytics v4 -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $analytics_v4_id;
		 ?>"></script>
		<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', '<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $analytics_v4_id;
		?>');

		</script>
		<!-- End Google Analytics v4 -->

		<?php
		return ob_get_clean();

		// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}

	public function generate_google_analytics_opts($options) {
		$options[] = [
			'analytics_v4_id' => [
				'label' => __( 'Google Analytics v4', 'blocksy-companion' ),
				'type' => 'text',
				'design' => 'block',
				'value' => '',
				'desc' => blocksy_companion_safe_sprintf(
					// translators: %1$s and %2$s are HTML tags for a link.
					__(
						'Link your Google Analytics 4 tracking ID. More info and instructions can be found %1$shere%2$s.',
						'blocksy-companion'
					),
					'<a href="https://support.google.com/analytics/answer/9744165?hl=en" target="_blank">',
					'</a>'
				),
				'disableRevertButton' => true,
				'setting' => [ 'transport' => 'postMessage' ],
			]
		];

		return $options;
	}
}
