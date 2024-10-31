<?php

namespace Outpace;

use Outpace\Lib\Controller;
use Outpace\Lib\QueryBuilder;
use Outpace\Lib\SitemapGenerator;

class Sitemap extends Controller
{

    private $urls = [];
    private $settings;

    /**
     * Generate Sitemap
     */
    public function show_sitemap()
    {
        $sitemap = $this->generate_sitemap();

        try {
            $sitemap->outputSitemap();
        } catch (\Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }

    /**
     * Generate Sitemap
     */
    public function generate_sitemap()
    {
        $sitemap        = new SitemapGenerator(get_home_url());
        $settings = get_outpaceseo_sitemap_settings();
        $this->settings = $settings['sitemap'];

        $this->collect_urls();
        $sitemap->addUrls($this->urls);

        try {
            $sitemap->createSitemap();
        } catch (\Exception $exc) {
            echo $exc->getTraceAsString();
        }
        return $sitemap;
    }

    /**
     * Generate Sitemap XSL Template
     */
    public static function generate_sitemap_xsl()
    {
        SitemapGenerator::outputSitemapXsl();
    }

    /**
     * Generate Sitemap XSL Template
     */
    public function generate_html_sitemap()
    {
        $settings = get_outpaceseo_sitemap_settings();
        $this->settings = $settings['sitemap'];
        $this->collect_urls();

        require_once(OUTPACE_INCLUDES . 'views/outpace-html-sitemap.php');
    }

    /**
     * Collect Sitemap URLs
     */
    public function collect_urls()
    {
        $this->add_home();
        $this->add_posts();
        $this->add_categories();
        $this->add_authors();
        $this->add_archives();
        $this->add_additional_pages();
    }

    /**
     * Add Home Page to Sitemap
     */
    public function add_home()
    {
        $home           = isset($this->settings['homepage']) ? true : false;

        $front_page_id  = get_option('page_on_front');
        $last_modified  = ($front_page_id) ? get_post_modified_time('c') : date('c');

        if ($home) {
            $this->add_url(
                get_bloginfo('url'),
                $last_modified,
                'frontpage',
                get_bloginfo('id')
            );
        }
    }

    /**
     * Add all Posts to Sitemap
     */
    public function add_posts()
    {
        $front_page_id  = get_option('page_on_front');

        $post           = isset($this->settings['posts']) ? true : false;
        $page           = isset($this->settings['pages']) ? true : false;

        if ($post && !$page) {
            $post_types = ['post'];
        } elseif (!$post && $page) {
            $post_types = ['page'];
        } elseif ($post && $page) {
            $post_types = ['page', 'post'];
        }

        foreach ($this->get_cpt() as $cpt) {
            if (!empty($this->settings->{$cpt}) && $this->settings->{$cpt}) {
                $post_types[] = $cpt;
            }
        }

        if ($post || $page) {
            $args = [
                'post_type'         => $post_types,
                'post_status'       => ['publish', 'future'],
                'post__not_in'      => [$front_page_id],
                'has_password'      => false,
                'orderby'           => 'post_modified',
                'order'             => 'DESC',
                'posts_per_page'    => -1,
            ];

            $posts = new \WP_Query($args);

            foreach ($posts->posts as $post) {
                $this->add_url(
                    get_permalink($post),
                    get_post_modified_time('c', false, $post),
                    get_post_type($post->ID),
                    $post->ID,
                );
            }
        }
    }

    /**
     * Add all Categories & Tags
     */
    public function add_categories()
    {
        $taxonomy_types = [];

        if (!isset($this->settings['categories']) || !$this->settings['categories']) {
            return;
        }
        $taxs = $this->get_taxonomy_types();
        foreach ($taxs as $taxonomy_type) {
            if ($this->settings['categories']) {
                $taxonomy_types[] = $taxonomy_type;
            }
        }

        $args = [
            'taxonomy'      => $taxonomy_types,
            'hide_empty'    => false,
            'number'        => false,
            'fields'        => 'all',
        ];

        $terms = get_terms($args);


        foreach ($terms as $term) {
            $args = [
                'post_type'         => 'any',
                'posts_per_page'    => 1,
                'orderby'           => 'date',
                'order'             => 'DESC',
                'tax_query' => array(
                    array(
                        'taxonomy' => $term->taxonomy,
                        'field'    => 'id',
                        'terms'    => array($term->term_id),
                    ),
                ),
            ];

            $latest_posts   = new \WP_Query($args);
            $latest_post    = $latest_posts->posts;

            if (!empty($latest_post[0])) {
                $this->add_url(
                    get_category_link($term),
                    get_post_modified_time('c', false, $latest_post[0]),
                    'category',
                    $term->term_id
                );
            }
        }
    }

    /**
     * Add all Authors
     */
    public function add_authors()
    {
        if (!isset($this->settings['author_pages'])) {
            return;
        }
        if ($this->settings['author_pages']) {
            $args = [
                'who'       => 'authors',
                'orderby'   => 'post_count',
                'order'     => 'DESC',
            ];

            $authors_query  = new \WP_User_Query($args);
            $authors        = $authors_query->get_results();

            if (!empty($authors)) {
                foreach ($authors as $author) {
                    $args = [
                        'author'        => $author->ID,
                        'orderby'       => 'date',
                        'order'         => 'DESC',
                        'numberposts'   => 1
                    ];

                    $latest_posts   = get_posts($args);
                    $modified_time  = (!empty($latest_posts[0])) ?
                        get_post_modified_time('c', false, $latest_posts[0]) :
                        date('c', strtotime($author->user_registered));

                    $this->add_url(
                        get_author_posts_url($author->ID),
                        $modified_time,
                        'author',
                        $author->ID
                    );
                }
            }
        }
    }

    /**
     * Add all Archives
     */
    public function add_archives()
    {
        global $wpdb;

        $sql = sprintf(
            'SELECT DISTINCT YEAR(post_date) AS year, MONTH(post_date) AS month,
				UNIX_TIMESTAMP(MAX(posts.post_modified)) AS modified_time FROM %s as posts
				WHERE post_status = "publish" AND post_type = "post" AND posts.post_password = ""
				GROUP BY YEAR(post_date), MONTH(post_date)',
            $wpdb->posts
        );

        $archives = QueryBuilder::run_query($sql);

        foreach ($archives as $archive) {
            $option = ($archive->month == date('n') && $archive->year == date('Y')) ? 'recent_archive' : 'older_archive';
            if (isset($this->settings['{$option}'])) {
                $this->add_url(
                    get_month_link($archive->year, $archive->month),
                    date('c', $archive->modified_time),
                    $option,
                );
            }
        }
    }

    /**
     * Add Additional Pages
     */
    public function add_additional_pages()
    {
        $pages = isset($this->settings['additional_pages']) ? true : false;

        if (!empty($pages)) {
            foreach ($pages as $page) {
                $this->add_url(
                    $page['url'],
                    date('c'),
                    'add_page',
                    $page['id']
                );
            }
        }
    }

    /**
     * Add Sitemap Url
     *
     * @param $url
     * @param string $last_modified
     */
    public function add_url($url, $last_modified = '', $type = '', $id = '')
    {
        $new_url = [
            $url, // URL
            $last_modified, // Last Modified
            $type,
            $id,
        ];

        array_push($this->urls, $new_url);
    }
}
