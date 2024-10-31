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
$tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : $default_tab;

?>
<div class="wrap op-main-container">
    <div class="op-main-container__nav">
        <nav class="nav-tab-wrapper">
            <img src="<?php echo plugin_dir_url(dirname(dirname(__FILE__))) . '/assets/images/outpaceseo.png' ?>" />
            <h2>Outpace SEO</h2>
            <a href="?page=outpaceseo" class="nav-tab <?php if ($tab === null) : ?>nav-tab-active<?php endif; ?>"><?php _e('Image Attributes', 'outpaceseo'); ?></a>
            <a href="?page=outpaceseo&tab=search" class="nav-tab <?php if ($tab === 'search') : ?>nav-tab-active<?php endif; ?>"><?php _e('Search', 'outpaceseo'); ?></a>
            <a href="?page=outpaceseo&tab=sitemap" class="nav-tab <?php if ($tab === 'sitemap') : ?>nav-tab-active<?php endif; ?>"><?php _e('Site Map', 'outpaceseo'); ?></a>
        </nav>
    </div>

    <div class="tab-content">
        <?php switch ($tab):
            case 'search':
                OPSEO_Admin_Menus::search_page();
                break;
            case 'sitemap':
                OPSEO_Admin_Menus::sitemap_page();
                break;
            default:
                OPSEO_Admin_Menus::img_attr_page();
                break;
        endswitch; ?>
    </div>
</div>