<?php

/**
 * View: Bulk Uploder
 *
 * @var string $view
 *
 * @package OutpaceSEO
 */

defined('ABSPATH') || exit;
?>

<div class="os-wrapper">
    <h1>Bulk Uploader</h1>
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