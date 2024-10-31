<?php

$obj = new stdClass();

foreach ($this->urls as $value) {

    $title = $value['2'];
    if (property_exists($obj, $title)) {
        array_push($obj->$title, $value);
    } else {
        $obj->$title = array();
        array_push($obj->$title, $value);
    }
}

echo '<div class="outpace-html-sitemap-container">';
foreach ($obj as $page_name => $url_arr) {
    if ($page_name === 'frontpage') {
        echo '<h3>Homepage</h3>';
    } else {
        echo '<h3>' . ucfirst($page_name) . '</h3>';
    }
    echo '<ul>';
    foreach ($url_arr as $url) {
        if ($url[2] == $page_name) {
            if ($page_name == 'frontpage') {
                $display_name = $url['3'];
                $front_page_id = get_option('page_on_front');
                if ($front_page_id != 0) {
                    $display_name = get_the_title($front_page_id);
                }
                echo "<a href='" . $url[0] . "'>" . $display_name . "</a></br>";
            } else if ($page_name == 'category') {
                echo "<li><a href='" . $url[0] . "'>" . get_cat_name($url[3]) . "</a></li>";
            } elseif ($page_name == 'author') {
                $author = get_user_by('ID', $url[3]);
                echo "<li><a href='" . $value[0] . "'>" . $author->display_name . "</a></li>";
            } else {
                echo "<li><a href='" . $url[0] . "'>" . get_the_title($url[3]) . "</a></li>";
            }
        }
    }
    echo '</ul>';
}
echo '</div></div>';
