<?php
/**
 * Plugin Name: Contributers
 * Description: Create contributers for a post
 * Version: 1.0 
 * Author: Juned Khatri 
 */

function cd_meta_box_add()
{
    add_meta_box( 'my-meta-box-id', 'Contributers', 'cd_meta_box_cb', 'post', 'normal', 'high' );
}

function cd_meta_box_cb($post)
{
    wp_nonce_field( 'my_meta_box_nonce', 'meta_box_nonce' );
    $users = get_users();

    $values = get_post_custom( $post->ID );

    $contributers = isset($values['contributers']) ? json_decode($values['contributers'][0]) : array();

    $current_user = wp_get_current_user();

    foreach($users as $user) {
        if ($user->has_cap('edit_posts')) {
            $user_login = $user->data->user_login;
            $user_display_name = $user->data->display_name;

            $check = isset($contributers->$user_login->checked) ? $contributers->$user_login->checked : '';

            $user_checkbox = "";
            $user_checkbox .= '<div style="margin-top: 20px">';
            $user_checkbox .= '<input style="vertical-align:top" name="contributer_' . $user_login . '" type="checkbox" ' . checked( $check, 'on', false) . ' ' . disable_current_user($current_user, $user) . ' />';
            $user_checkbox .= '<div style="display: inline-block; margin-left: 10px">';
            $user_checkbox .= $user_display_name . " ($user_login)<br/>";
            $user_checkbox .= '</div></div><br/>';

            echo $user_checkbox;
        }
    }
}

function disable_current_user($current_user, $user) {
    if($current_user->user_login == $user->data->user_login) {
        return "checked disabled";
    }
}

function cd_meta_box_save($post_id) {
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
     
    // if our nonce isn't there, or we can't verify it, bail
    if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'my_meta_box_nonce' ) ) return;
     
    // if our current user can't edit this post, bail
    if( !current_user_can( 'edit_posts' ) ) return;

    $users = get_users();
    $contributers = array();

    foreach($users as $user) {
        $user_login = $user->data->user_login;
        $user_display_name = $user->data->display_name;

        $post_name = 'contributer_' . $user_login;

        $chk = isset( $_POST[$post_name]) && $_POST[$post_name] ? 'on' : 'off';
        $contributers[$user_login] = array(
            'checked' => $chk,
            'name' => $user_display_name
        );
    }

    $current_user = wp_get_current_user();

    update_post_meta($post_id, 'contributers', json_encode($contributers));
}

function add_contributers_content($content) {
    global $post;
    $contributers_text = "<br/>";
    $contributers_text .= '<div style="display: inline-block; border: 1px solid #333333; padding: 10px 10px 0px 10px; border-radius: 10px; box-shadow: 5px 5px 5px grey;">';
    $contributers_text .= "<b>Contributers</b>";
    $contributers_text .= "<br/>";

    $values = get_post_custom( $post->ID );
    $contributers = isset($values['contributers']) ? json_decode($values['contributers'][0]) : NULL;

    $no_contributers_flag = true;
    if(!isset($contributers)) {
        $post_author = get_user_by('ID', $post->post_author);
        $contributers = array();
        $contributers[$post_author->user_login] = array(
            'checked' => 'on',
            'name' => $post_author->display_name
        );
        $contributers = json_decode(json_encode($contributers));
    } else {
        $post_author = get_user_by('ID', $post->post_author);
        $user_login = $post_author->user_login;
        $contributers->$user_login = new stdClass();
        $contributers->$user_login->checked = 'on';
        $contributers->$user_login->name = $post_author->display_name;
    }
    $users = get_users();
    $contributers_text .= '<ul style="margin-left: 20px;">';

    foreach($users as $user) {
        if ($user->has_cap('edit_posts')) {
            $user_login = $user->data->user_login;
            $user_display_name = $user->data->display_name;

            if(isset($contributers->$user_login) && $contributers->$user_login->checked == 'on') {
                $contributers_text .= '<li><a href="/author/' . $user_login . '">' . $contributers->$user_login->name . '<a/></li>';
                $no_contributers_flag = false;
            }
        }
    }
    $contributers_text .= "</ul>";
    $contributers_text .= "</div>";

    return $content . $contributers_text;
}

add_action('add_meta_boxes', 'cd_meta_box_add' );
add_action('save_post', 'cd_meta_box_save');
add_filter('the_content', 'add_contributers_content');
?>