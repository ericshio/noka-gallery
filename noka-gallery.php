<?php
/**
 * Plugin Name: Noka Gallery
 * Description: A lightweight, masonry gallery plugin. Native Divi 5 Support.
 * Version: 1.3.5
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * Author: Noka
 * Text Domain: noka-gallery
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// --- CRITICAL CONSTANTS ---
define( 'NOKA_PATH', plugin_dir_path( __FILE__ ) );
define( 'NOKA_URL', plugin_dir_url( __FILE__ ) );
define( 'NOKA_VERSION', '1.3.5' );

// CRITICAL FIX: Load the DependencyInterface file early and conditionally 
$divi_interface_path = ABSPATH . 'wp-content/themes/Divi/includes/builder-5/server/Framework/DependencyManagement/Interfaces/DependencyInterface.php';

if ( file_exists( $divi_interface_path ) ) {
    require_once $divi_interface_path;
} 

// Load Server-Side Logic (Divi 5)
require_once NOKA_PATH . 'server/index.php';

// Load Helper Class
require_once NOKA_PATH . 'includes/noka_gallery_helper.php';

use \ET\Builder\VisualBuilder\Assets\PackageBuildManager;

class Noka_Gallery {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_gallery_data' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        
        // --- FINAL FIX: RELIABLE BUILDER JS ENQUEUE HOOKS ---
        // 1. Use the standard WP hook with the robust conditional check.
        // add_action( 'wp_enqueue_scripts', array( $this, 'force_builder_js_load' ) );
        
        // 2. Add fix for MediaElement conflict that crashes the builder JS.
        add_action( 'admin_enqueue_scripts', array( $this, 'fix_mediaelement_conflict' ), 1 );
        // add_action( 'wp_enqueue_scripts', array( $this, 'fix_mediaelement_conflict' ), 1 ); // <-- NEW HOOK

        // 3. Keep Divi 5 registration method for official hooks (backend registration)
        add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'noka_gallery_register_visual_builder_assets' );
        add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
        add_action( 'wp_footer', array( $this, 'render_lightbox_markup' ) );
        
        // Manual Shortcode
        add_shortcode( 'noka_gallery', array( $this, 'render_shortcode' ) );
        
        // Module Fallback 
        add_shortcode( 'noka_gallery_module', array( $this, 'render_module_fallback' ) );
    }

    // --- START: CORE NOKA GALLERY METHODS ---

    public function register_rest_route() {
        register_rest_route( 'noka/v1', '/gallery/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_gallery_data' ),
            'permission_callback' => '__return_true',
        ));
    }
    
    public function get_gallery_data( $data ) {
        $post_id = $data['id'];
        
        $ids_str = get_post_meta( $post_id, '_noka_gallery_ids', true );
        $images = [];
        if(!empty($ids_str)) {
            $ids = explode(',', $ids_str);
            foreach($ids as $media_id) {
                $url = wp_get_attachment_image_url($media_id, 'large'); 
                if(!$url) continue;
                $images[] = ['id' => $media_id, 'url' => $url];
            }
        }

        $settings = array(
            'cols_d' => get_post_meta( $post_id, '_noka_cols_d', true ) ?: 3,
            'cols_t' => get_post_meta( $post_id, '_noka_cols_t', true ) ?: 2,
            'cols_m' => get_post_meta( $post_id, '_noka_cols_m', true ) ?: 1,
            'gap'    => get_post_meta( $post_id, '_noka_gap', true ) ?: 10,
            'radius' => get_post_meta( $post_id, '_noka_radius', true ) ?: 0,
        );

        return array(
            'images'   => $images,
            'settings' => $settings
        );
    }
    
    public function register_cpt() {
        register_post_type( 'noka_gallery', array(
            'labels' => array( 'name' => 'Noka Galleries', 'singular_name' => 'Gallery', 'add_new' => 'Add Gallery' ),
            'public' => false, 'show_ui' => true, 'supports' => array( 'title' ), 'menu_icon' => 'dashicons-images-alt2',
        ));
    }

    public function add_meta_boxes() {
        add_meta_box( 'noka_gallery_main_box', 'Noka Gallery Builder', array( $this, 'render_main_meta_box' ), 'noka_gallery', 'normal', 'high' );
        add_meta_box( 'noka_shortcode_box', 'Gallery Shortcode', array( $this, 'render_shortcode_meta_box' ), 'noka_gallery', 'side', 'high' );
    }

    public function render_shortcode_meta_box( $post ) {
        echo '<input type="text" readonly value=\'[noka_gallery id="' . $post->ID . '"]\' class="widefat" onclick="this->select()" style="font-weight: bold; text-align: center; padding: 8px;">';
    }

    public function render_main_meta_box( $post ) {
        $gallery_ids = get_post_meta( $post->ID, '_noka_gallery_ids', true );
        $image_size = get_post_meta( $post->ID, '_noka_image_size', true ) ?: 'large';
        $cols_d = get_post_meta( $post->ID, '_noka_cols_d', true ) ?: 3;
        $cols_t = get_post_meta( $post->ID, '_noka_cols_t', true ) ?: 2;
        $cols_m = get_post_meta( $post->ID, '_noka_cols_m', true ) ?: 1;
        $gap    = get_post_meta( $post->ID, '_noka_gap', true ) ?: 10;
        
        $lightbox = get_post_meta( $post->ID, '_noka_lightbox', true );
        if($lightbox === '') $lightbox = '1'; 
        $hover_anim   = get_post_meta( $post->ID, '_noka_hover_anim', true ) ?: 'none';
        $show_overlay = get_post_meta( $post->ID, '_noka_show_overlay', true ) ?: '0';
        $overlay_bg = get_post_meta( $post->ID, '_noka_overlay_bg', true ) ?: 'rgba(0,0,0,0.5)';
        $cursor = get_post_meta( $post->ID, '_noka_cursor', true ) ?: 'default';
        $radius = get_post_meta( $post->ID, '_noka_radius', true ) ?: '0'; 
        $lightbox_bg  = get_post_meta( $post->ID, '_noka_lightbox_bg', true ) ?: 'rgba(0,0,0,0.85)';
        require NOKA_PATH . 'includes/admin-view.php';
    }

    public function save_gallery_data( $post_id ) {
        if ( isset( $_POST['noka_gallery_ids'] ) ) update_post_meta( $post_id, '_noka_gallery_ids', sanitize_text_field( $_POST['noka_gallery_ids'] ) );
        $int_fields = ['noka_cols_d', 'noka_cols_t', 'noka_cols_m', 'noka_gap', 'noka_radius'];
        foreach($int_fields as $f) if(isset($_POST[$f])) update_post_meta( $post_id, "_$f", intval($_POST[$f]) );
        update_post_meta( $post_id, '_noka_lightbox', isset($_POST['noka_lightbox']) ? '1' : '0' );
        update_post_meta( $post_id, '_noka_show_overlay', isset($_POST['noka_show_overlay']) ? '1' : '0' );
        if(isset($_POST['noka_image_size'])) update_post_meta( $post_id, '_noka_image_size', sanitize_text_field($_POST['noka_image_size']) );
        if(isset($_POST['noka_hover_anim'])) update_post_meta( $post_id, '_noka_hover_anim', sanitize_text_field($_POST['noka_hover_anim']) );
        if(isset($_POST['noka_overlay_bg'])) update_post_meta( $post_id, '_noka_overlay_bg', sanitize_text_field($_POST['noka_overlay_bg']) );
        if(isset($_POST['noka_cursor'])) update_post_meta( $post_id, '_noka_cursor', sanitize_text_field($_POST['noka_cursor']) );
        if(isset($_POST['noka_lightbox_bg'])) update_post_meta( $post_id, '_noka_lightbox_bg', sanitize_text_field($_POST['noka_lightbox_bg']) );
    }

    public function admin_scripts( $hook ) {
        global $post;
        if ( ($hook == 'post-new.php' || $hook == 'post.php') && 'noka_gallery' === $post->post_type ) {
            wp_enqueue_media();
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'noka-admin', NOKA_URL . 'includes/assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ), NOKA_VERSION, true ); 
            wp_enqueue_style( 'noka-admin-css', NOKA_URL . 'includes/assets/css/admin.css', array(), NOKA_VERSION );
        }
    }

    public function render_module_fallback($atts) {
        $a = shortcode_atts( array( 'gallery_select' => 0 ), $atts );
        return $a['gallery_select'] ? do_shortcode('[noka_gallery id="' . $a['gallery_select'] . '"]') : '';
    }

    public function render_lightbox_markup() {
        if ( wp_script_is( 'noka-frontend', 'enqueued' ) ) {
            ?>
            <div id="noka-lightbox" class="noka-hidden">
                <span class="noka-close">&times;</span>
                <span class="noka-prev dashicons dashicons-arrow-left-alt2"></span>
                <div class="noka-lightbox-content"><div id="noka-lightbox-media"></div></div>
                <span class="noka-next dashicons dashicons-arrow-right-alt2"></span>
            </div>
            <?php
        }
    }

    public function render_shortcode( $atts ) {
        $a = shortcode_atts( array( 'id' => 0 ), $atts );
        if ( ! $a['id'] ) return '';
        $post_id = $a['id'];

        $saved_cols_d = get_post_meta( $post_id, '_noka_cols_d', true ) ?: 3;
        $saved_cols_t = get_post_meta( $post_id, '_noka_cols_t', true ) ?: 2;
        $saved_cols_m = get_post_meta( $post_id, '_noka_cols_m', true ) ?: 1;
        $saved_gap    = get_post_meta( $post_id, '_noka_gap', true ) ?: 10;
        
        $saved_size   = get_post_meta( $post_id, '_noka_image_size', true ) ?: 'large';
        $saved_lightbox = get_post_meta( $post_id, '_noka_lightbox', true );
        if($saved_lightbox === '') $saved_lightbox = '1';
        $anim   = get_post_meta( $post_id, '_noka_hover_anim', true ) ?: 'none';
        $show_overlay = get_post_meta( $post_id, '_noka_show_overlay', true ) ?: '0';
        $overlay_bg = get_post_meta( $post_id, '_noka_overlay_bg', true ) ?: 'rgba(0,0,0,0.5)';
        $cursor = get_post_meta( $post_id, '_noka_cursor', true ) ?: 'default';
        $radius = get_post_meta( $post_id, '_noka_radius', true ) ?: '0';
        $lightbox_bg = get_post_meta( $post_id, '_noka_lightbox_bg', true ) ?: 'rgba(0,0,0,0.85)';

        $final_atts = shortcode_atts( array( 'cols_d' => $saved_cols_d, 'cols_t' => $saved_cols_t, 'cols_m' => $saved_cols_m, 'gap' => $saved_gap ), $atts );
        $ids = get_post_meta( $post_id, '_noka_gallery_ids', true );
        if ( empty( $ids ) ) return '';
        $id_array = explode( ',', $ids );

        $version = NOKA_VERSION;

        wp_enqueue_script( 
            'noka-frontend', 
            NOKA_URL . 'includes/assets/js/frontend.js', 
            array( 'jquery', 'masonry', 'imagesloaded' ), 
            $version, 
            true 
        );
        wp_enqueue_style( 
            'noka-style', 
            NOKA_URL . 'visual-builder/build/noka-gallery-module.css', 
            array(), 
            $version 
        );

        $gap_value = (int) $final_atts['gap'];

        $style_vars  = "--noka-gap: {$gap_value}px;";
        $style_vars .= "--noka-pad: " . ($gap_value / 2) . "px;"; 
        $style_vars .= "--noka-cols-d: {$final_atts['cols_d']};";
        $style_vars .= "--noka-cols-t: {$final_atts['cols_t']};";
        $style_vars .= "--noka-cols-m: {$final_atts['cols_m']};";
        $style_vars .= "--noka-overlay-bg: {$overlay_bg};";
        $style_vars .= "--noka-cursor: {$cursor};";
        $style_vars .= "--noka-radius: {$radius}px;";
        $style_vars .= "--noka-lightbox-bg: {$lightbox_bg};";

        $container_classes = 'noka-masonry-grid noka-fade-in';
        if($anim !== 'none') $container_classes .= ' noka-anim-' . esc_attr($anim);
        if($show_overlay === '1') $container_classes .= ' noka-has-overlay';

        ob_start();
        ?>
        <div class="noka-gallery-wrapper" style="<?php echo esc_attr($style_vars); ?>">
            <div class="<?php echo esc_attr($container_classes); ?>" style="<?php echo esc_attr($style_vars); ?>">
                <?php foreach ( $id_array as $media_id ) : 
                    $url = wp_get_attachment_url( $media_id );
                    if(!$url) continue;
                    $mime = get_post_mime_type( $media_id );
                    $is_video = strpos( $mime, 'video' ) !== false;
                    $link_class = ($saved_lightbox === '1') ? 'noka-lightbox-trigger' : '';
                    $link_attr  = ($saved_lightbox === '1') ? '' : 'onclick="return false;"';
                    
                    $meta = wp_get_attachment_metadata( $media_id );
                    $w = isset($meta['width']) ? $meta['width'] : 1;
                    $h = isset($meta['height']) ? $meta['height'] : 1;
                    $ratio_css = "aspect-ratio: {$w} / {$h};";
                ?>
                    <div class="noka-item">
                        <div class="noka-item-inner" style="<?php echo esc_attr($ratio_css); ?>">
                            <?php if ( $is_video ) : ?>
                                <video class="noka-video" autoplay loop muted playsinline><source src="<?php echo esc_url($url); ?>"></video>
                            <?php else : 
                                echo wp_get_attachment_image( $media_id, $saved_size, false, array( 'class' => 'noka-img', 'loading' => 'lazy' ) ); 
                            endif; ?>
                            
                            <div class="noka-overlay">
                                <a href="<?php echo esc_url($url); ?>" class="<?php echo esc_attr($link_class); ?>" <?php echo $link_attr; ?> data-type="<?php echo $is_video ? 'video' : 'image'; ?>">
                                    <?php if($show_overlay === '1' && $saved_lightbox === '1') echo '<span class="dashicons dashicons-visibility"></span>'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    /*
    // --- FINAL FIX: FORCE BUILDER JS LOAD METHOD (Correctly inside class) ---
    public function force_builder_js_load() {
        // This acts as a reliable fallback using the stable et_core_is_fb_enabled check.
        if ( is_user_logged_in() && function_exists('et_core_is_fb_enabled') && et_core_is_fb_enabled() ) {
            $handle = 'noka-gallery-module-force'; // Use a unique handle
            $build_url = NOKA_URL . 'visual-builder/build/noka-gallery-module.js';

            $dependencies = ['react', 'jquery', 'divi-module-library', 'wp-hooks'];

            wp_enqueue_script( 
                $handle, 
                $build_url, 
                $dependencies, 
                NOKA_VERSION, 
                false // Load in the head for immediate execution
            );

            if ( class_exists('Noka_Gallery_Helper') ) {
                wp_localize_script( $handle, 'NokaData', array( 'galleries' => Noka_Gallery_Helper::get_galleries() ));
            }
        }
    }
    */
    
    public function fix_mediaelement_conflict() {
        if ( function_exists('et_core_is_fb_enabled') && et_core_is_fb_enabled() ) {
            wp_deregister_script( 'wp-mediaelement' );
            wp_deregister_style( 'wp-mediaelement' );
        
            // Existing: Dequeue just in case (kept for safety)
            wp_dequeue_script( 'wp-mediaelement' );
            wp_dequeue_style( 'wp-mediaelement' );
        }
    }
}

new Noka_Gallery();


// --- DIVI 5 PACKAGE MANAGER REGISTRATION FUNCTION (Required for backend module list) ---
/**
 * Registers the client-side JavaScript bundle using Divi's PackageBuildManager.
 */
function noka_gallery_register_visual_builder_assets() {
    if ( ! function_exists('et_builder_should_load_blocks') || ! et_builder_should_load_blocks() ) {
        return;
    }
    
    if ( ! class_exists( '\ET\Builder\VisualBuilder\Assets\PackageBuildManager' ) ) {
        return;
    }

    $handle = 'noka-gallery-module';
    $build_path = NOKA_PATH . 'visual-builder/build/noka-gallery-module.asset.php';

    // 1. Define required Divi dependencies (CRITICAL)
    $required_deps = ['react', 'divi-module-library', 'divi-registry']; 
    $version = NOKA_VERSION;
    $file_deps = [];

    // 2. Read dependencies from asset file
    if ( file_exists( $build_path ) ) {
        $asset_data = include( $build_path );
        $file_deps = $asset_data['dependencies'] ?? [];
        $version = $asset_data['version'] ?? NOKA_VERSION;
    } 

    // 3. Merge and deduplicate all dependencies
    // This ensures your script is loaded AFTER the Divi registry objects are available.
    $dependencies = array_unique( array_merge( $required_deps, $file_deps ) );

    \ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
        [
            'name'    => $handle,
            'version' => $version,
            'script'  => [
                'src'                => NOKA_URL . 'visual-builder/build/noka-gallery-module.js',
                'deps'               => $dependencies, // Use the merged dependencies
                'enqueue_top_window' => false, 
                'enqueue_app_window' => true, 
            ],
            'style' => [
                'src'                => NOKA_URL . 'visual-builder/build/noka-gallery-module.css',
                'deps'               => [],
                'enqueue_top_window' => false,
                'enqueue_app_window' => true,
            ],
            'data' => [
                'variableName' => 'NokaData',
                'value' => Noka_Gallery_Helper::get_galleries()
            ]
        ]
    );
}