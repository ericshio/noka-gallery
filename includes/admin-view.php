<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="noka-media-manager">
    <h3>Gallery Media</h3>
    <input type="hidden" name="noka_gallery_ids" id="noka_gallery_ids" value="<?php echo esc_attr( $gallery_ids ); ?>">
    
    <div class="noka-toolbar" style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
        <button type="button" class="button button-primary button-large" id="noka-add-media">
            <span class="dashicons dashicons-plus-alt2" style="margin-top: 3px;"></span> Add Media
        </button>
        
        <label style="margin-left: 5px; cursor: pointer; display: flex; align-items: center;">
            <input type="checkbox" id="noka-prepend-check" value="1" style="margin-right: 5px;"> 
            Add new to start
        </label>

        <button type="button" class="button button-secondary button-large" id="noka-delete-selected" style="display:none; margin-left: auto; color: #b32d2e; border-color: #b32d2e;">
            <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span> Delete Selected
        </button>
    </div>

    <div id="noka-preview-container">
        <?php 
        if ( ! empty( $gallery_ids ) ) {
            $ids = explode( ',', $gallery_ids );
            foreach ( $ids as $id ) {
                // 'true' asks WP for the icon if it's not an image (handles videos automatically)
                $img_atts = wp_get_attachment_image_src( $id, 'thumbnail', true );
                $url = $img_atts ? $img_atts[0] : '';
                
                // Get the actual filename for display
                $file_path = get_attached_file( $id );
                $filename = $file_path ? wp_basename( $file_path ) : 'Media #' . $id;

                echo '<div class="noka-admin-item" data-id="' . esc_attr($id) . '" title="' . esc_attr($filename) . '" style="position:relative;">';
                echo '<div class="noka-admin-img" style="background-image:url(' . esc_url($url) . ')"></div>';
                
                // Filename Overlay
                echo '<div class="noka-filename" style="position:absolute; bottom:0; left:0; right:0; background:rgba(0,0,0,0.7); color:#fff; font-size:10px; padding:2px 4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; pointer-events:none;">' . esc_html($filename) . '</div>';
                
                echo '<div class="noka-remove" style="z-index:10;"><span class="dashicons dashicons-no-alt"></span></div>';
                echo '</div>';
            }
        }
        ?>
    </div>
</div>

<div id="noka-gallery-settings" style="margin-top: 30px;">
    <h3>Gallery Settings</h3>
    
    <h2 class="nav-tab-wrapper noka-tabs">
        <a href="#noka-tab-general" class="nav-tab nav-tab-active" data-tab="general">General</a>
        <a href="#noka-tab-style" class="nav-tab" data-tab="style">Hover & Styles</a>
        <a href="#noka-tab-lightbox" class="nav-tab" data-tab="lightbox">Lightbox</a>
    </h2>

    <div class="noka-tab-content" id="noka-tab-general" style="display: block;">
        <div class="noka-setting-row">
            <label>Image Resolution</label>
            <select name="noka_image_size">
                <option value="thumbnail" <?php selected($image_size, 'thumbnail'); ?>>Thumbnail (Low)</option>
                <option value="medium" <?php selected($image_size, 'medium'); ?>>Medium (300px)</option>
                <option value="medium_large" <?php selected($image_size, 'medium_large'); ?>>Medium Large (768px) - RECOMMENDED</option>
                <option value="large" <?php selected($image_size, 'large'); ?>>Large (1024px+)</option>
                <option value="full" <?php selected($image_size, 'full'); ?>>Full (Original Quality)</option>
            </select>
            <p class="description">"Medium Large" is the ideal balance for mobile/desktop galleries.</p>
        </div>

        <div class="noka-setting-row">
            <label>Columns (Desktop)</label>
            <input type="number" name="noka_cols_d" value="<?php echo esc_attr($cols_d); ?>" min="1" max="6" class="small-text">
        </div>
        <div class="noka-setting-row">
            <label>Columns (Tablet)</label>
            <input type="number" name="noka_cols_t" value="<?php echo esc_attr($cols_t); ?>" min="1" max="4" class="small-text">
        </div>
        <div class="noka-setting-row">
            <label>Columns (Mobile)</label>
            <input type="number" name="noka_cols_m" value="<?php echo esc_attr($cols_m); ?>" min="1" max="2" class="small-text">
        </div>
        <div class="noka-setting-row">
            <label>Gap (px)</label>
            <input type="number" name="noka_gap" value="<?php echo esc_attr($gap); ?>" min="0" max="100" class="small-text">
        </div>
    </div>

    <div class="noka-tab-content" id="noka-tab-style" style="display: none;">
        <div class="noka-setting-row">
            <label>Corner Radius (px)</label>
            <input type="number" name="noka_radius" value="<?php echo esc_attr($radius); ?>" min="0" max="100" class="small-text">
            <p class="description">Set to 0 for sharp corners.</p>
        </div>

        <div class="noka-setting-row">
            <label>Animation on Hover</label>
            <select name="noka_hover_anim">
                <option value="none" <?php selected($hover_anim, 'none'); ?>>None (Default)</option>
                <option value="zoom" <?php selected($hover_anim, 'zoom'); ?>>Zoom Image</option>
                <option value="grayscale" <?php selected($hover_anim, 'grayscale'); ?>>Grayscale to Color</option>
                <option value="lift" <?php selected($hover_anim, 'lift'); ?>>Lift Up (Shadow)</option>
            </select>
        </div>

        <div class="noka-setting-row">
            <label>
                <input type="checkbox" name="noka_show_overlay" value="1" <?php checked( $show_overlay, '1' ); ?>>
                Show Overlay on Hover
            </label>
        </div>

        <div class="noka-setting-row">
            <label>Overlay Color</label>
            <input type="text" name="noka_overlay_bg" value="<?php echo esc_attr($overlay_bg); ?>" class="noka-color-picker">
        </div>

        <div class="noka-setting-row">
            <label>Cursor Style</label>
            <select name="noka_cursor">
                <option value="default" <?php selected($cursor, 'default'); ?>>Default Arrow</option>
                <option value="pointer" <?php selected($cursor, 'pointer'); ?>>Pointer (Hand)</option>
                <option value="zoom-in" <?php selected($cursor, 'zoom-in'); ?>>Zoom In (Magnifier)</option>
                <option value="crosshair" <?php selected($cursor, 'crosshair'); ?>>Crosshair</option>
            </select>
        </div>
    </div>
    
    <div class="noka-tab-content" id="noka-tab-lightbox" style="display: none;">
        <div class="noka-setting-row">
            <label>
                <input type="checkbox" name="noka_lightbox" value="1" <?php checked( $lightbox, '1' ); ?>>
                Enable Lightbox
            </label>
        </div>
        <div class="noka-setting-row">
            <label>Lightbox Background Color</label>
            <input type="text" name="noka_lightbox_bg" value="<?php echo esc_attr($lightbox_bg); ?>" class="noka-color-picker">
            <p class="description">Background color for the full-screen view (supports transparency).</p>
        </div>
    </div>
</div>