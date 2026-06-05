<?php

namespace Blocksy\DbVersioning;

class V2094 {
	public function migrate() {
		$this->migrate_comments_label_position();
		$this->migrate_archive_thumbnail_size();
	}

	public function migrate_archive_thumbnail_size() {
		$option_id = 'woocommerce_archive_thumbnail_cropping';

		$value = get_option($option_id, '__empty__');

		if ($value === 'predefined') {
			update_option($option_id, 'custom');
		}
	}

	public function migrate_comments_label_position() {
		$is_fresh_setup = blocksy_manager()->db_versioning->is_fresh_setup();

		if ($is_fresh_setup) {
			return;
		}

		$prefixes = blocksy_manager()->screen->get_single_prefixes();

		foreach ($prefixes as $prefix) {
			$option_id = $prefix . '_comments_label_position';

			$value = get_theme_mod($option_id, '__empty__');

			if ($value !== '__empty__') {
				continue;
			}

			// For existing setups that didn't touch this option, return to
			// the previous default value of "inside".
			set_theme_mod($option_id, 'inside');
		}
	}
}

