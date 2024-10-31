<?php

/**
 * View: Basic Option
 *
 * @var string $view
 *
 * @package OutpaceSEO
 */

defined('ABSPATH') || exit;
?>

<form id="outpaceseo-settings" action="options.php" method="post" enctype="multipart/form-data">
    <?php settings_fields('outpaceseo_settings_group'); ?>
    <div id="outpaceseo-basic" class="outpaceseo-settings-tab">
        <?php do_settings_sections('outpaceseo_basic_settings_section'); ?>
        <?php submit_button(__('Save Settings', 'outpaceseo')); ?>
    </div>
</form>