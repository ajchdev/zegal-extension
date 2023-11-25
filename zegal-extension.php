<?php
/*
* Plugin Name: Zegal Extension
* Version: 1.0.0
* Plugin URI: 
* Description: Zegal Extension is plugin to list category with filter post
* Author: Ajay
* Requires at least: 4.5
* Tested up to: 6.4
* Text Domain: zegal-extension
*
* @package Zegal Extension
*/
// Exit if accessed directly.

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Zegal_Extension_Class')) {

    class Zegal_Extension_Class{

        function __construct(){

            add_action('init', array($this,'zegal_extension_image_size'));
            add_action('wp_enqueue_scripts', array($this, 'zegal_extension_frontend_scripts'),100);
            add_shortcode('zegal-extension-category-filter', array($this, 'zegal_extension_category_list_filter'));
            add_action('wp_ajax_zegal_extension_filter_posts_by_category', array($this,'zegal_extension_filter_posts_by_category'));
            add_action('wp_ajax_nopriv_zegal_extension_filter_posts_by_category', array($this,'zegal_extension_filter_posts_by_category'));


        }

        
        function zegal_extension_posted_on() {
            $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
            if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
                $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
            }

            $time_string = sprintf(
                $time_string,
                esc_attr( get_the_date( DATE_W3C ) ),
                esc_html( get_the_date() ),
                esc_attr( get_the_modified_date( DATE_W3C ) ),
                esc_html( get_the_modified_date() )
            );

            $posted_on = sprintf(
                /* translators: %s: post date. */
                esc_html_x( 'Posted on %s', 'post date', 'zegal-extension' ),
                '<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time_string . '</a>'
            );

            echo '<span class="posted-on">' . $posted_on . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        }

        function zegal_extension_posted_by() {
            $byline = sprintf(
                /* translators: %s: post author. */
                esc_html_x( 'by %s', 'post author', 'zegal-extension' ),
                '<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>'
            );

            echo '<span class="byline"> ' . $byline . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        }

        function zegal_extension_image_size(){
            add_image_size('zegal-extension-grid', 350, 350, true );
        }
        // Frontend Script
        function zegal_extension_frontend_scripts(){

            wp_enqueue_script('zegal-extension-frontend-script', plugin_dir_url(__FILE__) . 'assets/js/frontend.js', array('jquery'), '', true);
            wp_enqueue_style('zegal-extension-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');
            $ajax_nonce = wp_create_nonce('zegal_extension_ajax_nonce');
            wp_localize_script(
                'zegal-extension-frontend-script',
                'zegal_extension_frontend_script',
                array(
                    'ajax_url' => esc_url(admin_url('admin-ajax.php')),
                    'ajax_nonce' => $ajax_nonce,
                )
            );

        }

        // Category List
        function zegal_extension_category_list_filter(){

            ob_start();
            
            ?>
            <div class="home-filter-posts">

                <div class="filter-tab-wraper">

                    <?php
                    $cat_lists = get_categories(
                        array(
                            'hide_empty' => '0',
                            'exclude' => '1',
                        )
                    );
                    
                    $cat_ids = array();
                    if( $cat_lists ){ ?>

                        <div class="title-tab-wrap">
                            <h2><?php esc_html_e('Post Lists With Category','zegal-extension'); ?></h2>
                            
                            <div class="ta-filter-tabs">
                                <a class="ta-tab-item active-tab" data-id="all" href="javascript:void(0)"><?php esc_html_e('All','zegal-extension'); ?></a>
                                <?php
                                foreach( $cat_lists as $cat_obj ){
                                    $category_title = isset( $cat_obj->name ) ? $cat_obj->name : '';
                                    $cat_slug = isset( $cat_obj->slug ) ? $cat_obj->slug : '';
                                    $cat_count = isset( $cat_obj->count ) ? $cat_obj->count : '';
                                    $cat_ids[] = isset( $cat_obj->term_id ) ? $cat_obj->term_id : ''; ?>
                                    <a class="ta-tab-item" href="javascript:void(0)" data-id="<?php echo esc_attr( $cat_slug ); ?>">
                                        <?php echo esc_html( $category_title ); ?> (<?php echo absint( $cat_count ); ?>)
                                    </a>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="ta-filter-contents-wrap">
                            <div class="ta-filter-content filter-content-all">
                                <?php 
                                $args = array(
                                    'category__in' => $cat_ids,
                                    'posts_per_page' => -1, // Set to -1 to retrieve all posts in the category.
                                    'post_type' => 'post', // Specify the post type (e.g., 'post' for regular posts).
                                );

                                $all_post_query = new WP_Query($args);
                                if( $all_post_query->have_posts() ){
                                    while( $all_post_query->have_posts() ){
                                        $all_post_query->the_post();
                                        $this->zegal_extension_post_content();
                                        }
                                    }

                                    wp_reset_postdata(); ?>
                                    
                            </div>
                        </div>

                    <?php } ?>

                </div>

            </div>
            <?php
            $html = ob_get_contents();
            ob_get_clean();
            return $html;
        }

        // Post Content
        function zegal_extension_post_content(){ ?>
            <div class="loop-posts-blog-recent clearfix">
											
                <?php if( has_post_thumbnail() ){ ?>
                    <div class="recent-post-image">
                    <a class="post-thumbnail"  target="_blank" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                        <?php
                        the_post_thumbnail( 'zegal-extension-grid', array(
                            'alt' => the_title_attribute( array(
                                'echo' => false,
                            ) ),
                        ) );
                        ?>
                    </a>
                    </div>
                <?php } ?>
                    
                <div class="wrap-meta-title">
                    <div class="title-recent-post">
                        <h4 class="entry-title ta-small-font"><a target="_blank" href="<?php the_permalink(); ?>"><?php echo esc_html( wp_trim_words( get_the_title(),10,'...' ) ); ?></a></h4>
                    </div>
                    <div class="entry-meta">
                        <?php
                        $this->zegal_extension_posted_on();
                        $this->zegal_extension_posted_by();
                        ?>
                    </div><!-- .entry-meta -->
                </div>
                    
            </div>
        <?php }

        // Filter Posts by Category
        function zegal_extension_filter_posts_by_category(){
            
            if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'zegal_extension_ajax_nonce' ) && isset( $_POST['catSlug'] ) && $_POST['catSlug'] ) {

                ob_start();
                    $layout = isset( $_POST['layout'] ) ? $_POST['layout'] : '';
                    $catSlug = $_POST['catSlug'];
                    $cat_slugs = array( $catSlug );
                    $cat_ids = array();
                    foreach( $cat_slugs as $cat_slug ){
                        $category_obj = get_term_by('slug', $cat_slug, 'category');
                        if ($category_obj && !is_wp_error($category_obj)) {
                            $category_title = $category_obj->name;
                            $cat_ids[] = $category_obj->term_id;
                        }
                    } ?>

                    <div class="ta-filter-content filter-content-<?php echo esc_attr( $catSlug ); ?>">
                        <?php
                        $args = array(
                            'category__in' => $cat_ids,
                            'posts_per_page' => -1, // Set to -1 to retrieve all posts in the category.
                            'post_type' => 'post', // Specify the post type (e.g., 'post' for regular posts).
                            'post_status' => 'publish',
                        );

                        $all_post_query = new WP_Query($args);
                        if( $all_post_query->have_posts() ){

                            while( $all_post_query->have_posts() ){
                                $all_post_query->the_post();
                                $this->zegal_extension_post_content();
                            }
                        }
                        wp_reset_postdata(); ?>

                    </div>
                <?php
                $output = array();
                $output['content'] = ob_get_contents();
                ob_get_clean();
                wp_send_json_success($output);
            }
            wp_die();
        }

    }

    $GLOBALS['ze_global'] = new Zegal_Extension_Class();

}
