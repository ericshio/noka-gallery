<?php

namespace NokaGallery;

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Direct access forbidden.' );
}

// Define the plugin version constant if it's not defined in the main file
if ( ! defined( 'NOKA_VERSION' ) ) {
    define( 'NOKA_VERSION', '1.3.5' );
}

// Ensure the helper is available for module logic
require_once dirname( dirname( __FILE__ ) ) . '/includes/noka_gallery_helper.php';

// !!! CRITICAL FIX: The require_once for DependencyInterface has been MOVED 
// !!! to noka-gallery.php to ensure it loads before the logging system.

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface; 
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * Class that handles "Noka Gallery" module output in frontend.
 */
class NokaGalleryServer implements DependencyInterface {
    
    /**
     * load() function is required by DependencyInterface.
     */
    public function load() {
        // Register module via the new Divi 5 API hook
        add_action( 'init', [ NokaGalleryServer::class, 'register_module' ] );
    }

    public static function register_module() {
        // Path to module metadata (shared between Frontend and Visual Builder)
        $module_json_folder_path = dirname( __DIR__, 1 ) . '/visual-builder/src';

        ModuleRegistration::register_module(
            $module_json_folder_path,
            [
                'render_callback' => [ NokaGalleryServer::class, 'render_callback' ],
            ]
        );
    }
    
    /**
     * Helper to get the module slug.
     */
    public static function get_module_slug() {
        return 'noka_gallery_module';
    }

    /**
     * Render module assets (Styles and Scripts).
     * This method loads assets ONLY when the module is rendered on the public frontend.
     */
    public static function module_assets() {
        $version = NOKA_VERSION;
        
        // 1. Enqueue Frontend JavaScript (Handles Lightbox/Masonry)
        wp_enqueue_script( 
            'noka-frontend', 
            NOKA_URL . 'includes/assets/js/frontend.js', 
            array( 'jquery', 'masonry', 'imagesloaded' ), 
            $version, 
            true 
        );
        
        // 2. Enqueue Stylesheet (Handles Layout/Animations)
        wp_enqueue_style( 
            'noka-style', 
            // FINAL FIX: Pointing to the Webpack-built CSS file for consistency
            NOKA_URL . 'visual-builder/build/noka-gallery-module.css', 
            array(), 
            $version 
        );
    }

    /**
     * Render module HTML output.
     */
    public static function render_callback( $attrs, $content, $block, $elements ) {
        // The ID is now stored deep in the attributes structure
        $gallery_id = $attrs['gallery_select']['innerContent']['desktop']['value'] ?? 0;

        if ( ! $gallery_id ) {
            // Return a placeholder message if no ID is selected
            return '<div style="padding:20px; text-align:center;">Please select a Noka Gallery in the module settings.</div>';
        }
        
        // Execute the shortcode to reuse ALL your existing PHP logic
        return do_shortcode( '[noka_gallery id="' . absint( $gallery_id ) . '"]' );
    }

}

// Register module dependency tree with Divi.
add_action(
    'divi_module_library_modules_dependency_tree',
    function( $dependency_tree ) {
        $dependency_tree->add_dependency( new NokaGalleryServer() );
    }
);

// ADD THIS HOOK: Tells Divi to call your module_assets function
add_action( 'divi_frontend_assets_render_module_' . NokaGalleryServer::get_module_slug(), [ NokaGalleryServer::class, 'module_assets' ] );