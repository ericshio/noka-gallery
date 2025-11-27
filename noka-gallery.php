<?php
/*
 * Plugin Name: Noka Gallery
 * Description: A lightweight, masonry gallery plugin. Native Divi 5 Support.
 * Version: 1.3.7
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * Author: Eric Shiozaki
 * Text Domain: noka-gallery
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NOKA_PATH', plugin_dir_path( __FILE__ ) );
define( 'NOKA_URL', plugin_dir_url( __FILE__ ) );
define( 'NOKA_VERSION', '1.3.7' );

// Load Divi Interface if available
$divi_interface_path = ABSPATH . 'wp-content/themes/Divi/includes/builder-5/server/Framework/DependencyManagement/Interfaces/DependencyInterface.php';
if ( file_exists( $divi_interface_path ) ) require_once $divi_interface_path;

require_once NOKA_PATH . 'server/index.php';
require_once NOKA_PATH . 'includes/noka_gallery_helper.php';

use \ET\Builder\VisualBuilder\Assets\PackageBuildManager;

class Noka_Gallery {
    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_gallery_data' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        
        // --- ADMIN COLUMNS ---
        add_filter( 'manage_noka_gallery_posts_columns', array( $this, 'add_shortcode_column' ) );
        add_action( 'manage_noka_gallery_posts_custom_column', array( $this, 'fill_shortcode_column' ), 10, 2 );

        // --- PREVENT WP VIDEO PLAYER HIJACKING ---
        add_action( 'wp_enqueue_scripts', array( $this, 'nuke_mediaelement' ), 100 );
        add_action( 'admin_enqueue_scripts', array( $this, 'nuke_mediaelement' ), 100 );

        // --- DATA INJECTION ---
        add_action( 'wp_head', array( $this, 'inject_noka_data' ) );
        add_action( 'admin_head', array( $this, 'inject_noka_data' ) );

        add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'noka_gallery_register_visual_builder_assets' );
        add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
        add_action( 'wp_footer', array( $this, 'render_lightbox_markup' ) );
        add_shortcode( 'noka_gallery', array( $this, 'render_shortcode' ) );
        add_shortcode( 'noka_gallery_module', array( $this, 'render_module_fallback' ) );
    }

    // --- ADMIN COLUMN LOGIC ---
    public function add_shortcode_column( $columns ) {
        $new_columns = array();
        foreach($columns as $key => $title) {
            $new_columns[$key] = $title;
            if ($key === 'title') {
                $new_columns['noka_shortcode'] = 'Shortcode';
            }
        }
        return $new_columns;
    }

    public function fill_shortcode_column( $column, $post_id ) {
        if ( 'noka_shortcode' === $column ) {
            echo '<input type="text" readonly="readonly" value="[noka_gallery id=&quot;' . $post_id . '&quot;]" style="width:100%; max-width:250px; text-align:center; background:#f9f9f9; border:1px solid #ddd;" onclick="this.select()">';
        }
    }

    public function register_cpt() { 
        register_post_type( 'noka_gallery', array( 
            'labels' => array( 'name' => 'Noka Gallery', 'singular_name' => 'Gallery', 'add_new' => 'Add Gallery', 'all_items' => 'All Galleries' ), 
            'public' => false, 
            'show_ui' => true, 
            'supports' => array( 'title' ), 
            'menu_icon' => 'dashicons-images-alt2', 
        )); 
    }

    public function nuke_mediaelement() {
        if ( function_exists('et_core_is_fb_enabled') && et_core_is_fb_enabled() ) {
            wp_deregister_script( 'mediaelement' );
            wp_deregister_script( 'mediaelement-migrate' );
            wp_deregister_script( 'wp-mediaelement' );
            $empty_js = 'data:text/javascript;base64,Ly8='; 
            wp_register_script( 'mediaelement', $empty_js, [], null );
            wp_register_script( 'mediaelement-migrate', $empty_js, ['mediaelement'], null );
            wp_register_script( 'wp-mediaelement', $empty_js, ['mediaelement'], null );
            wp_enqueue_script( 'mediaelement' );
            wp_enqueue_script( 'mediaelement-migrate' );
            wp_enqueue_script( 'wp-mediaelement' );
        }
    }

    public function inject_noka_data() {
        if ( ( function_exists('et_core_is_fb_enabled') && et_core_is_fb_enabled() ) || is_admin() ) {
            $data = Noka_Gallery_Helper::get_galleries();
            echo '<script>window.NokaData = ' . json_encode($data) . ';</script>';
        }
    }

    // [Rest API & Helpers]
    public function register_rest_route() { register_rest_route( 'noka/v1', '/gallery/(?P<id>\d+)', array('methods' => 'GET', 'callback' => array( $this, 'get_gallery_data' ), 'permission_callback' => '__return_true',)); }
    
    public function get_gallery_data( $data ) { 
        $post_id = $data['id']; 
        $ids_str = get_post_meta( $post_id, '_noka_gallery_ids', true ); 
        $images = []; 
        if(!empty($ids_str)) { 
            $ids = explode(',', $ids_str); 
            foreach($ids as $media_id) { 
                // Enhanced API logic for videos
                $mime = get_post_mime_type( $media_id );
                if ( strpos( $mime, 'video' ) !== false ) {
                    $url = wp_get_attachment_url( $media_id );
                } else {
                    $url = wp_get_attachment_image_url($media_id, 'large'); 
                }
                if(!$url) continue; 
                $images[] = ['id' => $media_id, 'url' => $url]; 
            } 
        } 
        $settings = array( 
            'cols_d' => get_post_meta( $post_id, '_noka_cols_d', true ) ?: 3, 
            'cols_t' => get_post_meta( $post_id, '_noka_cols_t', true ) ?: 2, 
            'cols_m' => get_post_meta( $post_id, '_noka_cols_m', true ) ?: 1, 
            'gap' => get_post_meta( $post_id, '_noka_gap', true ) ?: 10, 
            'radius' => get_post_meta( $post_id, '_noka_radius', true ) ?: 0, 
        ); 
        return array('images' => $images, 'settings' => $settings); 
    }

    public function add_meta_boxes() { add_meta_box( 'noka_gallery_main_box', 'Noka Gallery Builder', array( $this, 'render_main_meta_box' ), 'noka_gallery', 'normal', 'high' ); add_meta_box( 'noka_shortcode_box', 'Gallery Shortcode', array( $this, 'render_shortcode_meta_box' ), 'noka_gallery', 'side', 'high' ); }
    public function render_shortcode_meta_box( $post ) { echo '<input type="text" readonly value=\'[noka_gallery id="' . $post->ID . '"]\' class="widefat" onclick="this->select()" style="font-weight: bold; text-align: center; padding: 8px;">'; }
    
    public function render_main_meta_box( $post ) { 
        $gallery_ids = get_post_meta( $post->ID, '_noka_gallery_ids', true ); 
        $image_size = get_post_meta( $post->ID, '_noka_image_size', true ) ?: 'large'; 
        $cols_d = get_post_meta( $post->ID, '_noka_cols_d', true ) ?: 3; 
        $cols_t = get_post_meta( $post->ID, '_noka_cols_t', true ) ?: 2; 
        $cols_m = get_post_meta( $post->ID, '_noka_cols_m', true ) ?: 1; 
        $gap = get_post_meta( $post->ID, '_noka_gap', true ) ?: 10; 
        $lightbox = get_post_meta( $post->ID, '_noka_lightbox', true ); if($lightbox === '') $lightbox = '1'; 
        $hover_anim = get_post_meta( $post->ID, '_noka_hover_anim', true ) ?: 'none'; 
        $show_overlay = get_post_meta( $post->ID, '_noka_show_overlay', true ) ?: '0'; 
        $overlay_bg = get_post_meta( $post->ID, '_noka_overlay_bg', true ) ?: 'rgba(0,0,0,0.5)'; 
        $cursor = get_post_meta( $post->ID, '_noka_cursor', true ) ?: 'default'; 
        $radius = get_post_meta( $post->ID, '_noka_radius', true ) ?: '0'; 
        $lightbox_bg = get_post_meta( $post->ID, '_noka_lightbox_bg', true ) ?: 'rgba(0,0,0,0.85)'; 
        
        // This file is now using your updated admin-view.php
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

    public function render_module_fallback($atts) { $a = shortcode_atts( array( 'gallery_select' => 0 ), $atts ); return $a['gallery_select'] ? do_shortcode('[noka_gallery id="' . $a['gallery_select'] . '"]') : ''; }
    
    public function render_lightbox_markup() { if ( wp_script_is( 'noka-frontend', 'enqueued' ) ) { ?><div id="noka-lightbox" class="noka-hidden"><span class="noka-close">&times;</span><span class="noka-prev dashicons dashicons-arrow-left-alt2"></span><div class="noka-lightbox-content"><div id="noka-lightbox-media"></div></div><span class="noka-next dashicons dashicons-arrow-right-alt2"></span></div><?php } }

    // --- MAIN FRONTEND RENDER ---
    public function render_shortcode( $atts ) {
        $a = shortcode_atts( array( 'id' => 0 ), $atts ); if ( ! $a['id'] ) return ''; $post_id = $a['id'];
        
        $saved_cols_d = get_post_meta( $post_id, '_noka_cols_d', true ) ?: 3; 
        $saved_cols_t = get_post_meta( $post_id, '_noka_cols_t', true ) ?: 2; 
        $saved_cols_m = get_post_meta( $post_id, '_noka_cols_m', true ) ?: 1; 
        $saved_gap = get_post_meta( $post_id, '_noka_gap', true ) ?: 10; 
        $saved_size = get_post_meta( $post_id, '_noka_image_size', true ) ?: 'large'; 
        
        $saved_lightbox = get_post_meta( $post_id, '_noka_lightbox', true ); if($saved_lightbox === '') $saved_lightbox = '1'; 
        $anim = get_post_meta( $post_id, '_noka_hover_anim', true ) ?: 'none'; 
        $show_overlay = get_post_meta( $post_id, '_noka_show_overlay', true ) ?: '0'; 
        $overlay_bg = get_post_meta( $post_id, '_noka_overlay_bg', true ) ?: 'rgba(0,0,0,0.5)'; 
        $cursor = get_post_meta( $post_id, '_noka_cursor', true ) ?: 'default'; 
        $radius = get_post_meta( $post_id, '_noka_radius', true ) ?: '0'; 
        $lightbox_bg = get_post_meta( $post_id, '_noka_lightbox_bg', true ) ?: 'rgba(0,0,0,0.85)';
        
        $final_atts = shortcode_atts( array( 'cols_d' => $saved_cols_d, 'cols_t' => $saved_cols_t, 'cols_m' => $saved_cols_m, 'gap' => $saved_gap ), $atts );
        $ids = get_post_meta( $post_id, '_noka_gallery_ids', true ); if ( empty( $ids ) ) return ''; $id_array = explode( ',', $ids );
        $version = NOKA_VERSION;

        wp_enqueue_script( 'noka-frontend', NOKA_URL . 'includes/assets/js/frontend.js', array( 'jquery', 'masonry', 'imagesloaded' ), $version, true );
        
        $is_builder = ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) || ( function_exists( 'is_et_pb_preview' ) && is_et_pb_preview() );
        if ( ! $is_builder ) {
            wp_enqueue_style( 'noka-style', NOKA_URL . 'visual-builder/build/noka-gallery-module.css', array(), $version );
        }

        $gap_value = (int) $final_atts['gap'];
        $style_vars  = "--noka-gap: {$gap_value}px; --noka-pad: " . ($gap_value / 2) . "px;"; 
        $style_vars .= "--noka-cols-d: {$final_atts['cols_d']}; --noka-cols-t: {$final_atts['cols_t']}; --noka-cols-m: {$final_atts['cols_m']};";
        $style_vars .= "--noka-overlay-bg: {$overlay_bg}; --noka-cursor: {$cursor}; --noka-radius: {$radius}px; --noka-lightbox-bg: {$lightbox_bg};";
        $container_classes = 'noka-masonry-grid noka-fade-in';
        if($anim !== 'none') $container_classes .= ' noka-anim-' . esc_attr($anim);
        if($show_overlay === '1') $container_classes .= ' noka-has-overlay';

        ob_start();
        ?>
        <div class="noka-gallery-wrapper" style="<?php echo esc_attr($style_vars); ?>">
            <div class="<?php echo esc_attr($container_classes); ?>" style="<?php echo esc_attr($style_vars); ?>">
                <?php foreach ( $id_array as $media_id ) : 
                    // 1. DATA RETRIEVAL
                    $img_src = wp_get_attachment_image_src( $media_id, $saved_size );
                    // Fallback for missing/broken media
                    if ( ! $img_src ) {
                        // Double check if it's a video even if image_src failed
                        $mime = get_post_mime_type( $media_id ); 
                        if ( strpos( $mime, 'video' ) === false ) continue;
                    }
                    
                    // Defaults
                    $url = $img_src ? $img_src[0] : '';
                    $w   = $img_src ? $img_src[1] : 0;
                    $h   = $img_src ? $img_src[2] : 0;
                    
                    // 2. VIDEO DETECTION
                    $mime = get_post_mime_type( $media_id ); 
                    $is_video = strpos( $mime, 'video' ) !== false;
                    
                    if ( $is_video ) {
                         // Videos need direct URL and Metadata for size
                         $url = wp_get_attachment_url( $media_id );
                         $meta = wp_get_attachment_metadata( $media_id );
                         $w = $meta['width'] ?? 16;
                         $h = $meta['height'] ?? 9;
                    }

                    // 3. LAYOUT CALCULATION
                    $ratio_css = ($w && $h) ? "aspect-ratio: {$w} / {$h};" : "aspect-ratio: 16/9;";
                    
                    // Lightbox attributes (Note: clicking a video usually shouldn't trigger lightbox if it's acting as a BG, but keeping logic here)
                    $link_class = ($saved_lightbox === '1') ? 'noka-lightbox-trigger' : ''; 
                    $link_attr  = ($saved_lightbox === '1') ? '' : 'onclick="return false;"';
                ?>
                    <div class="noka-item">
                        <div class="noka-item-inner" style="<?php echo esc_attr($ratio_css); ?>">
                            
                            <?php if ( $is_video ) : ?>
                                <video 
                                    src="<?php echo esc_url($url); ?>" 
                                    autoplay 
                                    loop 
                                    muted 
                                    playsinline 
                                    class="noka-video"
                                    style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                ></video>
                            <?php else : ?>
                                <?php echo wp_get_attachment_image( $media_id, $saved_size, false, array( 
                                    'class' => 'noka-img', 
                                    'loading' => 'lazy',
                                    'style' => 'width: 100%; height: 100%; object-fit: cover; display: block;'
                                ) ); ?>
                            <?php endif; ?>

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
        <?php return ob_get_clean();
    }
}

new Noka_Gallery();

function noka_gallery_register_visual_builder_assets() {
    if ( ! class_exists( '\ET\Builder\VisualBuilder\Assets\PackageBuildManager' ) ) return;

    $handle = 'noka-gallery-module';
    $required_deps = ['wp-hooks', 'wp-element']; 
    
    $build_path = NOKA_PATH . 'visual-builder/build/noka-gallery-module.asset.php';
    $version = NOKA_VERSION;
    $file_deps = [];

    if ( file_exists( $build_path ) ) {
        $asset_data = include( $build_path );
        $file_deps = $asset_data['dependencies'] ?? [];
        $version = $asset_data['version'] ?? NOKA_VERSION;
    } 

    $dependencies = array_unique( array_merge( $required_deps, $file_deps ) );

    \ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
        [
            'name'    => 'noka-gallery-module',
            'version' => $version,
            'script'  => [
                'src'                => NOKA_URL . 'visual-builder/build/noka-gallery-module.js',
                'deps'               => $dependencies,
                'enqueue_top_window' => false, 
                'enqueue_app_window' => true, 
            ],
            'style' => [
                'src'                => NOKA_URL . 'visual-builder/build/noka-gallery-module.css',
                'deps'               => [],
                'enqueue_top_window' => false,
                'enqueue_app_window' => true,
            ]
        ]
    );
}