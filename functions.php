<?php

/*------------------------------------*\
	External Modules/Files
\*------------------------------------*/

// Load any external files you have here

/*------------------------------------*\
	Theme Support
\*------------------------------------*/
/*add_filter('wpcf7_spam', '__return_false');
 
add_filter('wpcf7_skip_spam_check', '__return_true');*/

define('CONCATENATE_SCRIPTS', false);


function debug_to_console($data)
{
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}

if (!function_exists('plugin_cookie')) {
    function plugin_cookie($key, $value = false, $expiration = false)
    {
        if ($value) {
            // Set a cookie
            return setcookie($key, $value, $expiration, COOKIEPATH, COOKIE_DOMAIN);
        }
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : false;
    }
}

function delete_article($data)
{
    $user = get_userdata($data['uid']);
    $post = get_post($data['aid']);

    if (empty($user)) {
        return new WP_Error('no_user', 'Invalid User', array('status' => 404));
    }

    if (empty($post)) {
        return new WP_Error('no_article', 'Invalid Article', array('status' => 404));
    }

    $favourite_articles = get_field("favourite_articles", "user_" . $data['uid'], false);
    if (!$favourite_articles) {
        $favourite_articles = array();
    }
    if (($key = array_search($data['aid'], $favourite_articles)) !== false) {
        unset($favourite_articles[$key]);
    } else {
        return new WP_Error('error_article', 'Could not find favourite article to delete', array('status' => 404));
    }

    $result = update_field('favourite_articles', $favourite_articles, "user_" . $data['uid']);

    if ($result) {
        return "success";
    } else {
        return new WP_Error('error_delete', 'Could not delete favourite article', array('status' => 404));
    }
}

add_action('rest_api_init', function () {
    register_rest_route('custom/delete', '/user/(?P<uid>\d+)/article/(?P<aid>\d+)', array(
        'methods' => 'POST',
        'callback' => 'delete_article',
    ));
});


function add_article($data)
{
    $user = get_userdata($data['uid']);
    $post = get_post($data['aid']);

    if (empty($user)) {
        return new WP_Error('no_user', 'Invalid User', array('status' => 404));
    }

    if (empty($post)) {
        return new WP_Error('no_article', 'Invalid Article', array('status' => 404));
    }

    $favourite_articles = get_field("favourite_articles", "user_" . $data['uid'], false);
    if (!$favourite_articles) {
        $favourite_articles = array();
    }
    if (($key = array_search($data['aid'], $favourite_articles)) !== false) {
        return new WP_Error('already_favourited', 'Article already favourited for this user', array('status' => 404));
    }
    $favourite_articles[] = $data['aid'];

    $result = update_field('favourite_articles', $favourite_articles, "user_" . $data['uid']);

    if ($result) {
        return "success";
    } else {
        return new WP_Error('error_favourite', 'Could not favourite article', array('status' => 404));
    }
}

add_action('rest_api_init', function () {
    register_rest_route('custom/add', '/user/(?P<uid>\d+)/article/(?P<aid>\d+)', array(
        'methods' => 'POST',
        'callback' => 'add_article',
    ));
});


function get_articles($data)
{
    $user = get_userdata($data['uid']);

    if (empty($user)) {
        return new WP_Error('no_user', 'Invalid User', array('status' => 404));
    }


    $favourite_articles = get_field("favourite_articles", "user_" . $data['uid']);

    if ($favourite_articles) {
        $sizes = get_intermediate_image_sizes();
        $images = [];
        foreach ($favourite_articles as $key => $f) {
            foreach ($sizes as $size) {
                $images[$size] = wp_get_attachment_image_src(get_post_thumbnail_id($f->ID), $size)[0];
            }
            $favourite_articles[$key]->post_date_gmt = date("Y-m-d\TH:i:s", strtotime($f->post_date_gmt));
            $favourite_articles[$key]->categories = get_the_category($f->ID);
            $favourite_articles[$key]->images = $images;
        }

        return $favourite_articles;
    } else {
        return "No articles favourited yet.";
    }
}

add_action('rest_api_init', function () {
    register_rest_route('custom/get_favourites', '/user/(?P<uid>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_articles',
    ));
});

function delete_user_api($data)
{

    $user = get_userdata($data['id']);
    $user_roles = $user->roles;
    if ($user_roles) {
        if (count($user_roles) == 1 && $user_roles[0] == 'subscriber') {
            include("./wp-admin/includes/user.php");
            $result = wp_delete_user($data['id'], 2);
            if ($result) {
                return "success";
            } else {
                return new WP_Error('error_request', 'Cannot delete user', array('status' => 404));
            }
        } else {
            return new WP_Error('restricted_user', 'User has also other roles than just subscriber', array('status' => 404));
        }
    }

    if (empty($user)) {
        return new WP_Error('no_user', 'Invalid user', array('status' => 404));
    }
}

add_action('rest_api_init', function () {
    register_rest_route('custom/delete', '/user/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'delete_user_api',
    ));
});





/*EVENT APIS*/

function delete_event($data)
{
    $user = get_userdata($data['uid']);
    $post = get_post($data['eid']);

    if (empty($user)) {
        return new WP_Error('no_user', 'Invalid User', array('status' => 404));
    }

    if ($post->post_type != "event") {
        return new WP_Error('no_event', 'Invalid Event', array('status' => 404));
    }

    if (empty($post)) {
        return new WP_Error('no_article', 'Invalid event', array('status' => 404));
    }

    $favourites = get_field("favourite_events", "user_" . $data['uid'], false);
    if (!$favourites) {
        $favourites = array();
    }
    if (($key = array_search($data['eid'], $favourites)) !== false) {
        unset($favourites[$key]);
    } else {
        return new WP_Error('error_event', 'Could not find favourite event to delete', array('status' => 404));
    }

    $result = update_field('favourite_events', $favourites, "user_" . $data['uid']);

    if ($result) {
        return "success";
    } else {
        return new WP_Error('error_delete', 'Could not delete favourite event', array('status' => 404));
    }
}

add_action('rest_api_init', function () {
    register_rest_route('custom/delete', '/user/(?P<uid>\d+)/event/(?P<eid>\d+)', array(
        'methods' => 'POST',
        'callback' => 'delete_event',
    ));
});


function add_event($data)
{
    $user = get_userdata($data['uid']);
    $post = get_post($data['eid']);

    if (empty($user)) {
        return new WP_Error('no_user', 'Invalid User', array('status' => 404));
    }
    if ($post->post_type != "event") {
        return new WP_Error('no_event', 'Invalid Event', array('status' => 404));
    }
    if (empty($post)) {
        return new WP_Error('no_event', 'Invalid Event', array('status' => 404));
    }

    $favourites = get_field("favourite_events", "user_" . $data['uid'], false);
    if (!$favourites) {
        $favourites = array();
    }
    if (($key = array_search($data['eid'], $favourites)) !== false) {
        return new WP_Error('already_favourited', 'Event already favourited for this user', array('status' => 404));
    }
    $favourites[] = $data['eid'];

    $result = update_field('favourite_events', $favourites, "user_" . $data['uid']);

    if ($result) {
        return "success";
    } else {
        return new WP_Error('error_favourite', 'Could not favourite event', array('status' => 404));
    }
}

add_action('rest_api_init', function () {
    register_rest_route('custom/add', '/user/(?P<uid>\d+)/event/(?P<eid>\d+)', array(
        'methods' => 'POST',
        'callback' => 'add_event',
    ));
});


function get_events($data)
{
    $user = get_userdata($data['uid']);

    if (empty($user)) {
        return new WP_Error('no_user', 'Invalid User', array('status' => 404));
    }


    $favourites = get_field("favourite_events", "user_" . $data['uid']);

    if ($favourites) {
        $sizes = get_intermediate_image_sizes();
        $images = [];
        $categories = [];
        foreach ($favourites as $key => $f) {
            foreach ($sizes as $size) {
                $images[$size] = wp_get_attachment_image_src(get_post_thumbnail_id($f->ID), $size)[0];
            }
            $favourites[$key]->post_date_gmt = date("Y-m-d\TH:i:s", strtotime($f->post_date_gmt));
            $favourites[$key]->images = $images;
        }
        return $favourites;
    } else {
        return "No events favourited yet.";
    }
}

add_action('rest_api_init', function () {
    register_rest_route('custom/get_favourite_events', '/user/(?P<uid>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_events',
    ));
});







if (!function_exists('write_log')) {
    function write_log($log)
    {

        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }
}

function on_post_publish($post_id)
{
    $post = get_post($post_id);
    if ($post->post_type == "post" && $post->post_status == "publish") {
        $url =  'https://fcm.googleapis.com/fcm/send';
        $key = 'AAAAzhdDUUI:APA91bFzLLPC2wJmgtBwzX6ZJkeVWbVpb3rL_REv6NIMUJafWLW8ipLPMGehO02zcNayQNmcPc44usMubA6s-t9hZxRl3d1oBh8cpCrkz_PnCmKLwNfgDfqJTSusmysHO1q8MxXE_0F5';
        $headers = array(
            'Authorization' => 'key=' . $key,
            'Content-Type' => 'application/json'
        );
        $terms = get_the_terms($post->ID, 'category');
        $nsent = get_field("notification_sent", $post->ID);

        if ($nsent) {
            return;
        } else {
            update_field("notification_sent", true, $post->ID);
        }
        $topic = "";
        if ($terms[0]) {
            if ($terms[0]->name == "Press Releases") {
                return;
            }
            $parent = $terms[0]->parent;
            if ($parent) {

                $term = get_term($parent);
                $topic = $term->name;
            } else {
                $topic = $terms[0]->name;
            }
        }

        $topic = str_replace(' ', '', $topic);
        if ($topic == "News") {
            $topic = "BreakingNews";
        }
        if ($topic == "Tech") {
            $topic = "Technology";
        }
        if ($topic == "Telecom") {
            $topic = "Telecoms";
        }
        $body = array();
        $body['to'] = "/topics/" . $topic;
        $body['notification']['title'] = "New Post";
        $body['notification']['body'] = $post->post_title;
        $body['notification']['content-available'] = true;
        $body['notification']['priority'] = 'high';
        $body['data']['id'] = $post->ID;
        $body['data']['type'] = 'article';

        if ($topic == "BreakingNews") {
            $body['notification']['title'] = "Daily News";
            $body['notification']['body'] = $post->post_title;
        }

        $body_json = json_encode($body);




        $response = wp_remote_post(
            $url,
            array(
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => $headers,
                'body'        => $body_json,
                'cookies'     => array()
            )
        );

        //for ios
        if ($topic == "BreakingNews") {
            $topic = "Breaking News";
            $body['to'] = "/topics/" . $topic;
            $body_json = json_encode($body);
            $response = wp_remote_post(
                $url,
                array(
                    'method'      => 'POST',
                    'timeout'     => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => $headers,
                    'body'        => $body_json,
                    'cookies'     => array()
                )
            );
        }
        //for ios
        if ($topic == "ExpertInsights") {
            $topic = "Expert Insights";
            $body['to'] = "/topics/" . $topic;
            $body_json = json_encode($body);
            $response = wp_remote_post(
                $url,
                array(
                    'method'      => 'POST',
                    'timeout'     => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => $headers,
                    'body'        => $body_json,
                    'cookies'     => array()
                )
            );
        }
        //for ios
        if ($topic == "Telecoms") {
            $topic = "Telecom";
            $body['to'] = "/topics/" . $topic;
            $body_json = json_encode($body);
            $response = wp_remote_post(
                $url,
                array(
                    'method'      => 'POST',
                    'timeout'     => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => $headers,
                    'body'        => $body_json,
                    'cookies'     => array()
                )
            );
        }


        /*if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            echo 'Response:<pre>';
            print_r( $response );
            echo '</pre>';
        }*/
    }
}
add_action('acf/save_post', 'on_post_publish');

function add_x_author_field()
{
    register_rest_field(
        'post',
        'x_author', // Add it to the response
        array(
            'get_callback'    => 'register_x_author_field', // Callback function - returns the value
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function register_x_author_field($object, $field_name, $request)
{
    $user = get_user_by("id", $object['author']);
    return $user->data->display_name;
}
add_action('rest_api_init', 'add_x_author_field');

function ws_register_images_field()
{
    register_rest_field(
        'post',
        'images',
        array(
            'get_callback'    => 'ws_get_images_urls',
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

add_action('rest_api_init', 'ws_register_images_field');

function ws_get_images_urls($object, $field_name, $request)
{
    $sizes = get_intermediate_image_sizes();
    $images = [];
    foreach ($sizes as $size) {
        $images[$size] = wp_get_attachment_image_src(get_post_thumbnail_id($object->id), $size)[0];
    }


    return $images;
}

function ws_register_custom_categories_field()
{
    register_rest_field(
        'post',
        'custom_categories',
        array(
            'get_callback'    => 'get_custom_rest_categories',
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

add_action('rest_api_init', 'ws_register_custom_categories_field');

function get_custom_rest_categories($object, $field_name, $request)
{

    $cats = get_the_category($object->id);

    return $cats;
}



function add_reading_field()
{
    register_rest_field(
        'post',
        'reading_time', // Add it to the response
        array(
            'get_callback'    => 'register_reading_field', // Callback function - returns the value
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function register_reading_field($object, $field_name, $request)
{

    // Check if ACF plugin activated
    if (function_exists('get_field')) {
        // Get the value
        $content = $object['content']['rendered'];
        $clean_content = strip_shortcodes($content);
        $clean_content = strip_tags($clean_content);
        $word_count = str_word_count($clean_content);
        $time = ceil($word_count / 300);
        return $time;
    } else {
        return '';
    }
}
add_action('rest_api_init', 'add_reading_field');

function get_reading_time($post)
{
    $content = $post->post_content;

    $clean_content = strip_shortcodes($content);
    $clean_content = strip_tags($clean_content);
    $word_count = str_word_count($clean_content);
    $time = ceil($word_count / 300);
    return $time . ' min';
}

function add_vertical_image_field()
{
    register_rest_field(
        'post',
        'vertical_image', // Add it to the response
        array(
            'get_callback'    => 'register_vertical_image_field', // Callback function - returns the value
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function register_vertical_image_field($object, $field_name, $request)
{
    // Check if ACF plugin activated
    if (function_exists('get_field')) {
        $im = get_field('image_for_mobile_app', $object['id']);
        if ($im) {
            return $im;
        } else {
            return null;
        }
    } else {
        return '';
    }
}
add_action('rest_api_init', 'add_vertical_image_field');

function add_youtube_video_url()
{
    register_rest_field(
        'post',
        'youtube_video_url', // Add it to the response
        array(
            'get_callback'    => 'register_youtube_video_url_field', // Callback function - returns the value
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function register_youtube_video_url_field($object, $field_name, $request)
{
    // Check if ACF plugin activated
    if (function_exists('get_field')) {
        $im = get_field('youtube_video_url', $object['id']);
        if ($im) {
            return $im;
        } else {
            return null;
        }
    } else {
        return '';
    }
}
add_action('rest_api_init', 'add_youtube_video_url');

function add_youtube_video_id()
{
    register_rest_field(
        'post',
        'youtube_video_id', // Add it to the response
        array(
            'get_callback'    => 'register_youtube_video_id', // Callback function - returns the value
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function register_youtube_video_id($object, $field_name, $request)
{
    // Check if ACF plugin activated
    if (function_exists('get_field')) {
        $im = get_field('youtube_video_url', $object['id']);
        $my_array_of_vars = "";
        if ($im) {
            parse_str(parse_url($im, PHP_URL_QUERY), $my_array_of_vars);
            return $my_array_of_vars['v'];
        } else {
            return null;
        }
    } else {
        return null;
    }
}
add_action('rest_api_init', 'add_youtube_video_id');

function add_favourite_field()
{
    register_rest_field(
        'post',
        'favourite', // Add it to the response
        array(
            'get_callback'    => 'register_favourite_field', // Callback function - returns the value
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function register_favourite_field($object, $field_name, $request)
{
    if (isset($request['user_id']) && !empty($request['user_id'])) {
        $favourites = get_field("favourite_articles", "user_" . $request['user_id'], false);
        if (!$favourites) {
            $favourites = array();
        }
        $result = in_array($object['id'], $favourites);
        return $result;
    }
    return "";
}
add_action('rest_api_init', 'add_favourite_field');


function add_favourite_search_field()
{
    register_rest_field(
        'search-result',
        'favourite', // Add it to the response
        array(
            'get_callback'    => 'register_favourite_search_field', // Callback function - returns the value
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function register_favourite_search_field($object, $field_name, $request)
{
    if (isset($request['user_id']) && !empty($request['user_id'])) {
        $favourites = get_field("favourite_articles", "user_" . $request['user_id'], false);
        if (!$favourites) {
            $favourites = array();
        }
        $result = in_array($object['id'], $favourites);
        return $result;
    }
    return "";
}
add_action('rest_api_init', 'add_favourite_search_field');


/**EVENTS**/
function add_favourite_event_field()
{
    register_rest_field(
        'event',
        'favourite', // Add it to the response
        array(
            'get_callback'    => 'register_favourite_event_field', // Callback function - returns the value
            'update_callback' => null,
            'schema'          => null,
        )
    );
}

function register_favourite_event_field($object, $field_name, $request)
{
    if (isset($request['user_id']) && !empty($request['user_id'])) {
        $favourites = get_field("favourite_events", "user_" . $request['user_id'], false);
        if (!$favourites) {
            $favourites = array();
        }
        $result = in_array($object['id'], $favourites);
        return $result;
    }
    return "";
}
add_action('rest_api_init', 'add_favourite_event_field');


add_filter('acf/settings/rest_api_format', function () {
    return 'standard';
});


add_action('rest_api_init', function () {
    // Registers a REST field for the /wp/v2/search endpoint.
    register_rest_field('search-result', 'image', array(
        'get_callback' => function ($post) {
            return get_the_post_thumbnail_url($post['id'], 'thumbnail');
        },
    ));

    register_rest_field('search-result', 'date_gmt', array(
        'get_callback' => function ($post) {
            $p = get_post($post['id']);
            return date("Y-m-d\TH:i:s", strtotime($p->post_date_gmt));
        },
    ));

    register_rest_field('search-result', 'custom_categories', array(
        'get_callback' => function ($post) {
            return get_the_category($post['id']);
        },
    ));
});

add_filter('rest_endpoints', function ($endpoints) {
    if (isset($endpoints['/wp/v2/users'])) {
        unset($endpoints['/wp/v2/users']);
    }
    if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
        unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }
    return $endpoints;
});

if (function_exists('acf_add_options_page')) {

    acf_add_options_page(array(
        'page_title'     => 'Theme General Settings',
        'menu_title'    => 'Theme Settings',
        'menu_slug'     => 'theme-general-settings',
        'capability'    => 'edit_posts',
        'redirect'        => false
    ));

    /*acf_add_options_page(array(
        'page_title'    => 'YOUR_PAGE_TILE Options',
        'menu_title'    => 'YOUR_MENU_TITLE Options',
        'menu_slug'     => 'options_services',
        'capability'    => 'edit_posts',
        'parent_slug'   => 'edit.php?post_type=services',
        'position'      => false,
        'icon_url'      => 'dashicons-images-alt2',
        'redirect'      => false,
    ));*/
}


if (function_exists('add_theme_support')) {
    // Add Menu Support
    add_theme_support('menus');


    // Add Thumbnail Theme Support
    add_theme_support('post-thumbnails');
    set_post_thumbnail_size(1000, 600, true);
    add_image_size('mvp-post-thumb-large', 1400, 600, true);
    add_image_size('mvp-post-thumb', 1000, 600, true);
    add_image_size('mvp-port-thumb', 560, 600, true);
    add_image_size('mvp-large-thumb', 590, 354, true);
    add_image_size('mvp-mid-thumb', 400, 240, true);
    add_image_size('mvp-small-thumb', 80, 80, true);
    add_image_size('medium-thumb', 740);
    add_image_size('small-thumb', 350);

    // Add Support for Custom Backgrounds - Uncomment below if you're going to use
    /*add_theme_support('custom-background', array(
	'default-color' => 'FFF',
	'default-image' => get_template_directory_uri() . '/img/bg.jpg'
    ));*/

    // Add Support for Custom Header - Uncomment below if you're going to use
    /*add_theme_support('custom-header', array(
	'default-image'			=> get_template_directory_uri() . '/img/headers/default.jpg',
	'header-text'			=> false,
	'default-text-color'		=> '000',
	'width'				=> 1000,
	'height'			=> 198,
	'random-default'		=> false,
	'wp-head-callback'		=> $wphead_cb,
	'admin-head-callback'		=> $adminhead_cb,
	'admin-preview-callback'	=> $adminpreview_cb
    ));*/

    // Enables post and comment RSS feed links to head
    //add_theme_support('automatic-feed-links');

    // Localisation Support
    load_theme_textdomain('html5blank', get_template_directory() . '/languages');
}

add_rewrite_rule('^portfolio/page/([0-9]+)', 'index.php?pagename=portfolio&paged=$matches[1]', 'top');



/*------------------------------------*\
	Functions
\*------------------------------------*/

// HTML5 Blank navigation
function html5blank_nav($location)
{
    wp_nav_menu(
        array(
            'theme_location'  => $location,
            'menu'            => '',
            'container'       => 'div',
            'container_class' => 'menu-{menu slug}-container',
            'container_id'    => '',
            'menu_class'      => 'menu',
            'menu_id'         => '',
            'echo'            => true,
            'fallback_cb'     => 'wp_page_menu',
            'before'          => '',
            'after'           => '',
            'link_before'     => '',
            'link_after'      => '',
            'items_wrap'      => '<ul>%3$s</ul>',
            'depth'           => 0,
            'walker'          => ''
        )
    );
}

// Load HTML5 Blank scripts (header.php)
function html5blank_header_scripts()
{
    if ($GLOBALS['pagenow'] != 'wp-login.php' && !is_admin()) {

        wp_register_script('lazysizes-js', get_template_directory_uri() . '/js/lazysizes.min.js', array('jquery'), '1.0.0');
        wp_enqueue_script('lazysizes-js');

        wp_register_script('bootstrap-js', get_template_directory_uri() . '/js/bootstrap.min.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('bootstrap-js');

        wp_register_script('easing-js', get_template_directory_uri() . '/js/easing.min.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('easing-js');

        wp_register_script('owl-carousel-js', get_template_directory_uri() . '/js/owl-carousel.min.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('owl-carousel-js');

        wp_register_script('flickity-pkgd-js', get_template_directory_uri() . '/js/flickity.pkgd.min.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('flickity-pkgd-js');

        wp_register_script('twitterFetcher-js', get_template_directory_uri() . '/js/twitterFetcher_min.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('twitterFetcher-js');

        wp_register_script('jquery-newsTicker-js', get_template_directory_uri() . '/js/jquery.newsTicker.min.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('jquery-newsTicker-js');

        wp_register_script('modernizr-js', get_template_directory_uri() . '/js/modernizr.min.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('modernizr-js');

        wp_register_script('scripts-js', get_template_directory_uri() . '/js/scripts.js', array('jquery'), '2.0.3', true);
        wp_enqueue_script('scripts-js');


        wp_localize_script('scripts', 'ajax_posts', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'noposts' => __('No older posts found', 'twentyfifteen'),
        ));
    }
}


function print_title($post)
{
    return $post->post_title;
    // if (get_field("editors_choice", $post->ID)) {
    //     return "<span class='editors-choice'>Editor's Choice</span>" . $post->post_title;
    // } else {
    //     return $post->post_title;
    // }
}

// Load HTML5 Blank conditional scripts
function html5blank_conditional_scripts()
{
    if (is_page('pagenamehere')) {
        wp_register_script('scriptname', get_template_directory_uri() . '/js/scriptname.js', array('jquery'), '1.0.0'); // Conditional script(s)
        wp_enqueue_script('scriptname'); // Enqueue it!
    }
}

// Load HTML5 Blank styles
function html5blank_styles()
{
    wp_register_style('bootstrap-css', get_template_directory_uri() . '/css/bootstrap.min.css', array(), '1.0', 'all');
    wp_enqueue_style('bootstrap-css'); // Enqueue it!

    wp_register_style('font-icons-css', get_template_directory_uri() . '/css/font-icons.css', array(), '1.0', 'all');
    wp_enqueue_style('font-icons-css'); // Enqueue it!

    wp_register_style('theme-style-css', get_template_directory_uri() . '/css/style.css?v=1.0.1', array(), '1.0', 'all');
    wp_enqueue_style('theme-style-css'); // Enqueue it!

    wp_register_style('my-style', get_template_directory_uri() . '/style.css?v=1.0.9', array(), '5.52', 'all');
    wp_enqueue_style('my-style'); // Enqueue it!
}

// Register HTML5 Blank Navigation
function register_html5_menu()
{
    register_nav_menus(array( // Using array to specify more menus if needed
        'header-menu' => __('Header Menu', 'html5blank'), // Main Navigation
        'footer-menu' => __('Footer Menu', 'html5blank') // Extra Navigation if needed (duplicate as many as you need!)
    ));
}

// Remove the <div> surrounding the dynamic navigation to cleanup markup
function my_wp_nav_menu_args($args = '')
{
    $args['container'] = false;
    return $args;
}

// Remove Injected classes, ID's and Page ID's from Navigation <li> items
function my_css_attributes_filter($var)
{
    return is_array($var) ? array() : '';
}

// Add page slug to body class, love this - Credit: Starkers Wordpress Theme
/*function add_slug_to_body_class($classes)
{
    global $post;
    if (is_home()) {
        $key = array_search('blog', $classes);
        if ($key > -1) {
            unset($classes[$key]);
        }
    } elseif (is_page()) {
        $classes[] = sanitize_html_class($post->post_name);
    } elseif (is_singular()) {
        $classes[] = sanitize_html_class($post->post_name);
    }

    return $classes;
}*/

// Pagination for paged posts, Page 1, Page 2, Page 3, with Next and Previous Links, No plugin
function html5wp_pagination()
{

    /*$big = 999999999;
    echo paginate_links(array(
        'base' => str_replace($big, '%#%', get_pagenum_link($big)),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $query->max_num_pages
    ));*/
}

// Remove Admin bar
function remove_admin_bar()
{
    return false;
}




// Remove thumbnail width and height dimensions that prevent fluid images in the_thumbnail
function remove_thumbnail_dimensions($html)
{
    $html = preg_replace('/(width|height)=\"\d*\"\s/', "", $html);
    return $html;
}

/*------------------------------------*\
	Actions + Filters + ShortCodes
\*------------------------------------*/

// Add Actions
add_action('init', 'html5blank_header_scripts'); // Add Custom Scripts to wp_head
add_action('wp_print_scripts', 'html5blank_conditional_scripts'); // Add Conditional Page Scripts
add_action('wp_enqueue_scripts', 'html5blank_styles'); // Add Theme Stylesheet
add_action('init', 'register_html5_menu'); // Add HTML5 Blank Menu
add_action('init', 'html5wp_pagination'); // Add our HTML5 Pagination

// Remove Actions
remove_action('wp_head', 'feed_links_extra', 3); // Display the links to the extra feeds such as category feeds
remove_action('wp_head', 'feed_links', 2); // Display the links to the general feeds: Post and Comment Feed
remove_action('wp_head', 'rsd_link'); // Display the link to the Really Simple Discovery service endpoint, EditURI link
remove_action('wp_head', 'wlwmanifest_link'); // Display the link to the Windows Live Writer manifest file.
remove_action('wp_head', 'index_rel_link'); // Index link
remove_action('wp_head', 'parent_post_rel_link', 10, 0); // Prev link
remove_action('wp_head', 'start_post_rel_link', 10, 0); // Start link
remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0); // Display relational links for the posts adjacent to the current post.
remove_action('wp_head', 'wp_generator'); // Display the XHTML generator that is generated on the wp_head hook, WP version
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
remove_action('wp_head', 'rel_canonical');
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);

// Add Filters
add_filter('body_class', 'add_slug_to_body_class'); // Add slug to body class (Starkers build)
add_filter('wp_nav_menu_args', 'my_wp_nav_menu_args'); // Remove surrounding <div> from WP Navigation
add_filter('show_admin_bar', 'remove_admin_bar'); // Remove Admin bar

class AWP_Menu_Walker_Mobile extends Walker_Nav_Menu
{
    private $iid = null;
    function start_lvl(&$output, $depth = 0, $args = null)
    {

        if ($args->walker->has_children) {

            $output .= "<ul class='sidenav__menu-dropdown'>";
        } else {
            $output .= "<ul>";
        }
    }

    function end_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= "</ul>";
    }

    function start_el(&$output, $item, $depth = 0, $args = [], $id = 0)
    {

        $output .= "<li>";


        if ($args->walker->has_children && $depth == 0) {

            $output .= '<a class="sidenav__menu-url" href="' . $item->url . '">';
            $output .= $item->title;
            $output .= "</a>";
            $output .= '<button class="sidenav__menu-toggle" aria-haspopup="true" aria-label="Open dropdown"><i class="ui-arrow-down"></i></button>';
        } else {
            $output .= '<a class="sidenav__menu-url" href="' . $item->url . '">';
            $output .= $item->title;
            $output .= "</a>";
        }
    }
}
