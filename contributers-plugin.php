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


    foreach($users as $user) {
        $user_login = $user->data->user_login;
        $user_display_name = $user->data->display_name;

        $check = isset($contributers->$user_login->checked) ? $contributers->$user_login->checked : '';
        
        $user_checkbox = "";
        $user_checkbox .= '<div style="margin-top: 20px">';
        $user_checkbox .= '<input style="vertical-align:top" name="contributer_' . $user_login . '" type="checkbox" ' . checked( $check, 'on', false) . ' />';
        $user_checkbox .= '<div style="display: inline-block; margin-left: 10px">';
        $user_checkbox .= $user_display_name . " ($user_login)<br/>";
        //$user_checkbox .= $user_login . '<br/>';
        $user_checkbox .= '</div></div><br/>';

        echo $user_checkbox;
    }
}

function cd_meta_box_save($post_id) {
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
     
    // if our nonce isn't there, or we can't verify it, bail
    if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'my_meta_box_nonce' ) ) return;
     
    // if our current user can't edit this post, bail
    if( !current_user_can( 'edit_post' ) ) return;

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
    if(isset($contributers)) {

        $users = get_users();
        $contributers_text .= '<ul style="margin-left: 20px;">';

        foreach($users as $user) {
            $user_login = $user->data->user_login;
            $user_display_name = $user->data->display_name;

            if(isset($contributers->$user_login) && $contributers->$user_login->checked == 'on') {
                $contributers_text .= '<li>' . $contributers->$user_login->name . '</li>';
                $no_contributers_flag = false;
            }
        }
        $contributers_text .= "</ul>";
    }
    if($no_contributers_flag) {
        $contributers_text .= "no contributers<br/>";
    }

    $contributers_text .= "</div>";

    return $content . $contributers_text;
}

add_action('add_meta_boxes', 'cd_meta_box_add' );
add_action('save_post', 'cd_meta_box_save');
add_filter('the_content', 'add_contributers_content');
?>