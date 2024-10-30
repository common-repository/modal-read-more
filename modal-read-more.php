<?php
/**
 * Plugin Name: Modal Read More
 * Plugin URI: 
 * Description: This plugin uses the read more link and opens the post in modal window.
 * Version: 0.0.9
 * Author: George Lazarou
 * Author URI: http://georgelazarou.info
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

/*  Copyright 2014  George Lazarou  (email : info@georgelazarou.info)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/* Load the textdomain */

function modalReadmore_loadTextdomain() {
    
	load_plugin_textdomain('modal_readmore', false, 
                           dirname(plugin_basename(__FILE__)));
    
}
add_action('init', 'modalReadmore_loadTextdomain');


/* On activation */

function modalReadmore_activate() {

    // For Single site
    if (!is_multisite()) {
        add_option('modalReadmoreBootstrapModal', '1');
        add_option('modalReadmoreModalWidth', '600');
    }
    // For Multisite
    else {
        // For regular options.
        global $wpdb;
        $blog_ids         = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        $original_blog_id = get_current_blog_id();
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            add_option('modalReadmoreBootstrapModal', '1');
            add_option('modalReadmoreModalWidth', '600');
        }
        switch_to_blog($original_blog_id);

        // For site options.
        add_site_option('modalReadmoreBootstrapModal', '1');
        add_site_option('modalReadmoreModalWidth', '600');
    }
    
}
register_activation_hook(__FILE__, 'modalReadmore_activate');


/* On deactivation */

function modalReadmore_deactivate() {

    wp_dequeue_script('modal-bootstrapJS');
    wp_dequeue_style('modal-bootstrapCSS');
    
}
register_deactivation_hook(__FILE__, 'modalReadmore_deactivate');


/* On uninstallation */

function modalReadmore_uninstall() {

    // For Single site
    if (!is_multisite()) {
        delete_option('modalReadmoreBootstrapModal');
        delete_option('modalReadmoreModalWidth');
    } 
    // For Multisite
    else {
        // For regular options.
        global $wpdb;
        $blog_ids         = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        $original_blog_id = get_current_blog_id();
        foreach($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            delete_option('modalReadmoreBootstrapModal');
            delete_option('modalReadmoreModalWidth');
        }
        switch_to_blog($original_blog_id);

        // For site options.
        delete_site_option('modalReadmoreBootstrapModal');
        delete_site_option('modalReadmoreModalWidth');
        
    }
}
register_uninstall_hook(__FILE__, 'modalReadmore_uninstall');


/* Register scripts */

function modalReadmore_loadScripts() {
    
    $jQueryEnqueued              = wp_script_is('jquery', $list = 'enqueued');
    if($jQueryEnqueued == false)
        wp_enqueue_script('jquery');
    
    $modalReadmoreBootstrapModal = get_option('modalReadmoreBootstrapModal');
    $bootstrapModalJSEnqueued    = wp_script_is('modal-bootstrapJS', $list = 'enqueued');
    $bootstrapModalCSSEnqueued   = wp_style_is('modal-bootstrapCSS', $list = 'enqueued');
    if($modalReadmoreBootstrapModal == '1') {
        if($bootstrapModalJSEnqueued == false) {
            wp_enqueue_script(
                'modal-bootstrapJS', 
                plugins_url('bootstrap_modal.js', __FILE__), 
                array('jquery'), '3.1.1', true 
            );
        }
        if($bootstrapModalCSSEnqueued == false) {
            wp_enqueue_style(
                'modal-bootstrapCSS', 
                plugins_url('bootstrap_modal.css', __FILE__), 
                '', '3.1.1', 'all' 
            );
        }
    } else {
        wp_dequeue_script('modal-bootstrapJS');
        wp_dequeue_style('modal-bootstrapCSS');
    }
    
}
add_action('wp_enqueue_scripts', 'modalReadmore_loadScripts');


/* Ajax call */

function modalReadmore_ajaxGetPost_javascript() { ?>

<script type="text/javascript" >
jQuery(function(){
    
    jQuery('a.more-link').on('click', function(e){
        
        e.preventDefault();

        jQuery.ajax({
            type     : 'POST', 
            url      : '<?php echo admin_url('admin-ajax.php'); ?>', 
            data     : {
                     action: 'modalReadmore_ajaxGetPost', 
                     modalReadmore_postURL: jQuery(this).attr('href')
            }, 
            dataType : 'json'}).done(function(data) {
                jQuery('#modalReadmore')
                .find('.modal-header .entry-title')
                .html(data.postTitle)
                .end()
                .find('.modal-body .entry-content p')
                .html(data.postContent)
                .end()
                .fadeIn();
            });
        
    });

    jQuery('#modalReadmore button[data-dismiss="modal"]').on('click', function(e){
        
        e.preventDefault();
        jQuery('#modalReadmore').fadeOut();
        
    });
    
});
</script>

<?php }
add_action('wp_footer', 'modalReadmore_ajaxGetPost_javascript');


/* Ajax callback */

function modalReadmore_ajaxGetPost_callback() {

    global $shortcode_tags;
        
    $permalink         = explode('/', $_POST['modalReadmore_postURL']);
    $permalinkEnd      = end($permalink);
    $permalinkStart    = $permalink[0];
    $permalinkSettings = get_option('permalink_structure', '');
    $permalinkSize     = count($permalink);
    
    if(empty($permalinkEnd))
        array_pop($permalink);
    
    if(empty($permalinkStart))
        array_shift($permalink);

    switch($permalinkSettings) {
        // Default
        case '':
            $url       = end($permalink);
            break;
        // Day and name
        case '/%year%/%monthnum%/%day%/%postname%/':
            $permalink = array_splice($permalink, $permalinkSize-5, 4);
            $url       = implode('/', $permalink);
            break;
        // Month and name
        case '/%year%/%monthnum%/%postname%/':
            $permalink = array_splice($permalink, $permalinkSize-4, 3);
            $url       = implode('/', $permalink);
            break;
        // Numeric
        case '/archives/%post_id%':
            $permalink = array_splice($permalink, $permalinkSize-2);
            $url       = implode('/', $permalink);
            break;
        // Post name
        case '/%postname%/':
            end($permalink);
            $url       = prev($permalink);
            break;
        default:
            $url       = false;
            break;
    }
    
    if($url) {
        $id            = url_to_postid($url);
        $postType      = get_post_type($id);
    }

    switch($postType) {
        // Post
        case 'post':
            $post        = get_post($id); 
            $postTitle   = $post->post_title;
            $postContent = apply_filters('the_content', $post->post_content);
            $postContent = do_shortcode($postContent);
            $data      = array('postTitle'   => $postTitle,
                               'postContent' => $postContent);
            break;
        // Page
        case 'page':
            $page      = get_page($id);
            $pageTitle = $page->post_title;
            $pageContent = apply_filters('the_content', $page->post_content);
            $pageContent = do_shortcode($pageContent);
            $data      = array('postTitle'   => $pageTitle,
                               'postContent' => $pageContent);
            break;
        // Attachment
        case 'attachment':
            $data      = array('postTitle'   => __('Error', 
                                                   'modal_readmore'),
                               'postContent' => __('Modal Read More plugin works for posts and pages only!', 
                                                   'modal_readmore'));
            break;
        // Revision
        case 'revision':
            $data      = array('postTitle'   => __('Error', 
                                                   'modal_readmore'),
                               'postContent' => __('Modal Read More plugin works for posts and pages only!', 
                                                   'modal_readmore'));
            break;
        // Navigation Menu Item
        case 'nav_menu_item':
            $data      = array('postTitle'   => __('Error', 
                                                   'modal_readmore'),
                               'postContent' => __('Modal Read More plugin works for posts and pages only!', 
                                                   'modal_readmore'));
            break;
        case 'custom_permalinks':
            $data      = array('postTitle'   => __('Error', 
                                                   'modal_readmore'),
                               'postContent' => __('Modal Read More plugin is NOT working with custom permalinks', 
                                                   'modal_readmore'));
            break;
        default:
            $data      = array('postTitle'   => __('Error', 
                                                   'modal_readmore'),
                               'postContent' => __('Something is going wrong here... Please check in mind that <br />Modal Read More plugin works for posts and pages only! and that <br /> Modal Read More plugin is NOT working with custom permalinks', 
                                                   'modal_readmore'));
            break;
    }

    echo json_encode($data);
	die();
}
add_action('wp_ajax_modalReadmore_ajaxGetPost', 
           'modalReadmore_ajaxGetPost_callback');
add_action('wp_ajax_nopriv_modalReadmore_ajaxGetPost', 
           'modalReadmore_ajaxGetPost_callback');


/* Add modal markup */

function modalReadmore_modalMarkup() {
    
    echo '
    <div id="modalReadmore" class="modal fade" tabindex="-1" 
    role="dialog" aria-labelledby="myLargeModalLabel" 
    aria-hidden="true">
        <div class="modal-dialog" style="width:'.get_option("modalReadmoreModalWidth").'px">
            <div class="modal-content" style="width:inherit">
                <div class="modal-header">
                    <h1 class="entry-title"></h1>
                </div>
                <div class="modal-body">
                    <div class="entry-content">
                        <p></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" 
                    data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    ';
    
}
add_action('wp_footer', 'modalReadmore_modalMarkup');


/* Add settings link */

function modalReadmore_setLink($links, $file) {
    
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '
        <a href="'.
        get_bloginfo('wpurl').
        '/wp-admin/admin.php?page=Modal_Readmore">Settings</a>
        ';
        array_unshift($links, $settings_link);
    }

    return $links;
    
}
add_filter('plugin_action_links', 'modalReadmore_setLink', 10, 2);


/* Add menu in admin settings */

function modalReadmore_admin_actions() {
    
    // ADD THE MODAL READ MORE SUBMENU IN SETTINGS
    add_options_page("Modal Read More", "Modal Read More", 
                     1, "Modal_Readmore", "modalReadmore_admin");
    
}
add_action('admin_menu', 'modalReadmore_admin_actions');


/* Include admin file */

function modalReadmore_admin() {
    
    // INCLUDE THE FORM
    include('modal-read-more-admin.php');
    
}
