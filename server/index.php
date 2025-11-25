<?php namespace NokaGallery;

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Direct access forbidden.' );
}

// Define the plugin version constant if it's not defined in the main file
if ( ! defined( 'NOKA_VERSION' ) ) {
    define( 'NOKA_VERSION', '1.3.5' );
}

// Ensure the helper is available for module logic
require_once dirname( dirname( __FILE__ ) ) . '/includes/noka_gallery_helper.php';

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface; 
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;
// IMPORT CRITICAL FOR WRAPPER:
use ET\Builder\Packages\Module\Module;

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
     * Render module HTML output.
     */
    public static function render_callback( $attrs, $content, $block, $elements ) {
        // Divi 5 automatically resolves Dynamic Content (like @{post_id}) into the actual value 
        // before passing it here, so $gallery_id will be the real ID (e.g. "123").
        $gallery_id = $attrs['gallery_select']['innerContent']['desktop']['value'] ?? 0;

        // Generate the inner content (Your Shortcode)
        // using absint() ensures safety if the dynamic value is empty or invalid.
        if ( ! $gallery_id ) {
            $output = '<div style="padding:20px; text-align:center;">Please select a Noka Gallery in the module settings.</div>';
        } else {
            $output = do_shortcode( '[noka_gallery id="' . absint( $gallery_id ) . '"]' );
        }
        
        // WRAPPER FIX: Return the content wrapped in the standard Divi 5 Module container.
        // This ensures your module gets the correct ID, Order Class, and Divi styling hooks.
        return Module::render(
            [
                // Frontend Props (Required for proper ordering/rendering)
                'orderIndex'          => $block->parsed_block['orderIndex'],
                'storeInstance'       => $block->parsed_block['storeInstance'],

                // Visual Builder Props
                'attrs'               => $attrs,
                'elements'            => $elements,
                'id'                  => $block->parsed_block['id'],
                
                // IMPORTANT: These must match your module.json
                'moduleClassName'     => 'noka_gallery_module', 
                'name'                => $block->block_type->name,
                'moduleCategory'      => $block->block_type->category,
                
                // Your actual HTML output goes here
                'children'            => $output,
            ]
        );
    }

}

// Register module dependency tree with Divi.
add_action(
    'divi_module_library_modules_dependency_tree',
    function( $dependency_tree ) {
        $dependency_tree->add_dependency( new NokaGalleryServer() );
    }
);