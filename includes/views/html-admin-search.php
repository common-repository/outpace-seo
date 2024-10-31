<?php

/**
 * View: Options
 *
 * @var string $view
 *
 * @package OutpaceSEO
 */

defined('ABSPATH') || exit;

if (!current_user_can('manage_options')) {
	return;
}
?>
<div class="wrap op-wrapper">
	<div class="section-wrapper">
		<form id="outpaceseo-settings" action="options.php" method="post" enctype="multipart/form-data">
			<?php settings_fields('outpaceseo_settings_search_group'); ?>
			<div id="outpaceseo-search" class="outpaceseo-search">
				<?php do_settings_sections('outpaceseo_search_settings_section'); ?>
				<?php submit_button(__('Save Settings', 'outpaceseo')); ?>
			</div>
		</form>
	</div>
</div>

<script>
	function insertVariable(name) {
			let currVal = document.getElementById("outpace_meta_title_slot").innerHTML
			document.getElementById("outpace_meta_title_slot").innerHTML = currVal + '<span contentEditable="false">' + name + '</span>&nbsp;'
	}
	function insertVariable1(name) {
			let currVal = document.getElementById("outpace_meta_description_slot").innerHTML
			document.getElementById("outpace_meta_description_slot").innerHTML = currVal + '<span contentEditable="false">' + name + '</span>&nbsp;'
	}
</script>