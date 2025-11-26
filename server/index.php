<?php namespace NokaGallery;

if ( ! defined( 'ABSPATH' ) ) die( 'Direct access forbidden.' );
if ( ! defined( 'NOKA_VERSION' ) ) define( 'NOKA_VERSION', '1.3.5' );

require_once dirname( dirname( __FILE__ ) ) . '/includes/noka_gallery_helper.php';

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface; 
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;
use ET\Builder\Packages\Module\Module;

class NokaGalleryServer implements DependencyInterface {
    public function load() { add_action( 'init', [ NokaGalleryServer::class, 'register_module' ] ); }

    public static function register_module() {
        $module_json_folder_path = dirname( __DIR__, 1 ) . '/visual-builder/src';
        ModuleRegistration::register_module( $module_json_folder_path, [ 'render_callback' => [ NokaGalleryServer::class, 'render_callback' ] ] );
    }
    public static function get_module_slug() { return 'noka_gallery_module'; }

    public static function render_callback( $attrs, $content, $block, $elements ) {
        // Read from NESTED structure
        $gallery_id = $attrs['gallery_select']['innerContent']['desktop']['value'] ?? 0;

        if ( ! $gallery_id || $gallery_id === 'none' ) {
            $output = '<div style="padding:20px; text-align:center;">Please select a Noka Gallery.</div>';
        } else {
            $output = do_shortcode( '[noka_gallery id="' . absint( $gallery_id ) . '"]' );
        }
        
        return Module::render( [
            'orderIndex' => $block->parsed_block['orderIndex'],
            'storeInstance' => $block->parsed_block['storeInstance'],
            'attrs' => $attrs,
            'elements' => $elements,
            'id' => $block->parsed_block['id'],
            'moduleClassName' => 'noka_gallery_module', 
            'name' => $block->block_type->name,
            'moduleCategory' => $block->block_type->category,
            'children' => $output,
        ] );
    }
}

add_action( 'divi_module_library_modules_dependency_tree', function( $dependency_tree ) {
    $dependency_tree->add_dependency( new NokaGalleryServer() );
});