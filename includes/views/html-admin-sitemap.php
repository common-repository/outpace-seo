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
            <?php settings_fields('outpaceseo_settings_sitemap_group'); ?>
            <div id="outpaceseo-sitemap" class="outpaceseo-sitemap">
                <?php do_settings_sections('outpaceseo_sitemap_settings_section'); ?>
                <?php submit_button(__('Save Settings', 'outpaceseo')); ?>
            </div>
        </form>
    </div>
</div>