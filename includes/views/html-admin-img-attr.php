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

$default_tab = null;
$tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

?>
<div class="wrap op-wrapper">
	<div class="section-wrapper">
		<form id="outpaceseo-settings" action="options.php" method="post" enctype="multipart/form-data">
			<?php settings_fields('outpaceseo_settings_group'); ?>
			<div id="outpaceseo-basic" class="outpaceseo-settings-tab">
				<?php do_settings_sections('outpaceseo_basic_settings_section'); ?>
				<?php submit_button(__('Save Settings', 'outpaceseo')); ?>
			</div>
		</form>
	</div>

	<div class="section-wrapper">
		<div class="os-wrapper">
			<h2>Bulk Uploader</h2>
			<p class="submit">
				<input class="button-primary outpaceseo_run" type="submit" name="Run Bulk Updater" value="<?php _e('Run Bulk Updater', 'outpaceseo') ?>" />

				<input class="button-secondary outpaceseo_test" type="submit" name="Test Bulk Updater" value="<?php _e('Test Bulk Updater', 'outpaceseo') ?>" />

				<input class="button-secondary outpaceseo_stop" type="submit" name="Stop Bulk Updater" value="<?php _e('Stop Bulk Updater', 'outpaceseo') ?>" disabled />
			</p>

			<p class="submit">
				<input class="button-secondary outpaceseo_reset" type="submit" name="Reset Counter" value="<?php _e('Reset Counter', 'outpaceseo') ?>" />
			</p>
			<div id="bulk-updater-results">
				<fieldset id="outpaceseo-log-wrapper">
					<legend><span class="dashicons dashicons-welcome-write-blog"></span>&nbsp;<strong><?php _e('Event Log', 'outpaceseo'); ?></strong>&nbsp;<div class="outpaceseo-spinner is-active" style="margin-top:0px;"></div>
					</legend>
					<div id="outpaceseo-log">
						<p id="outpaceseo_remaining_images_text"><?php _e('Number of Images Remaining: ', 'outpaceseo') ?><?php echo outpaceseo_count_remaining_images(); ?></p>

						<p><?php _e('Number of Images Updated: ', 'outpaceseo') ?><?php echo outpaceseo_updated_image_count(); ?></p>
					</div>
				</fieldset>
			</div>
		</div>
	</div>

</div>