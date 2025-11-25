jQuery(document).ready(function($){
    var mediaUploader;
    var $container = $('#noka-preview-container');
    var $input = $('#noka_gallery_ids');
    var $batchDeleteBtn = $('#noka-delete-selected');

    // 1. Tab Switching
    $('.noka-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        $('.noka-tabs .nav-tab').removeClass('nav-tab-active');
        $('.noka-tab-content').hide();
        $(this).addClass('nav-tab-active');
        $('#noka-tab-' + $(this).data('tab')).show();
    });

    // 2. Initialize Sortable (The Fix for Reordering)
    if ($container.length) {
        $container.sortable({
            items: '.noka-admin-item',
            cursor: 'grabbing',
            placeholder: 'noka-sortable-placeholder',
            forcePlaceholderSize: true,
            opacity: 0.8,
            update: function(event, ui) {
                // Triggers immediately when you drop an item
                updateInput();
            }
        });
    }

    // 3. Add Media (Native WP Media Frame)
    $('#noka-add-media').click(function(e) {
        e.preventDefault();
        if (mediaUploader) { mediaUploader.open(); return; }
        
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Select Images or Videos',
            button: { text: 'Add to Gallery' },
            // CHANGED: 'add' allows clicking multiple items without holding Cmd/Ctrl
            multiple: 'add', 
            library: { type: [ 'image', 'video' ] }
        });
        
        mediaUploader.on('select', function() {
            var selection = mediaUploader.state().get('selection');
            
            selection.map( function( attachment ) {
                attachment = attachment.toJSON();
                var previewUrl = attachment.url;
                
                // Use thumbnail if available, otherwise icon for video
                if(attachment.sizes && attachment.sizes.thumbnail) {
                    previewUrl = attachment.sizes.thumbnail.url;
                } else if (attachment.type === 'video') {
                    // Fallback icon path (assumes standard WP structure or handled by CSS)
                    previewUrl = includes_url + 'images/media/video.png'; 
                }

                var html = `
                    <div class="noka-admin-item" data-id="${attachment.id}">
                        <div class="noka-admin-img" style="background-image:url('${previewUrl}')"></div>
                        <div class="noka-remove"><span class="dashicons dashicons-no-alt"></span></div>
                    </div>
                `;
                $container.append(html);
            });
            updateInput();
        });
        mediaUploader.open();
    });

    // 4. Item Selection (For Batch Delete)
    $container.on('click', '.noka-admin-item', function(e) {
        if($(e.target).closest('.noka-remove').length) return; // Ignore if clicking X
        $(this).toggleClass('noka-selected');
        toggleBatchButton();
    });

    // 5. Delete Logic
    $container.on('click', '.noka-remove', function(e) {
        e.stopPropagation(); // Stop bubbling
        $(this).closest('.noka-admin-item').remove();
        updateInput();
        toggleBatchButton();
    });

    $batchDeleteBtn.click(function() {
        $('.noka-admin-item.noka-selected').remove();
        updateInput();
        toggleBatchButton();
    });

    // --- Helpers ---
    function toggleBatchButton() {
        if($('.noka-admin-item.noka-selected').length > 0) {
            $batchDeleteBtn.fadeIn(200).css('display', 'inline-flex');
        } else {
            $batchDeleteBtn.fadeOut(200);
        }
    }

    function updateInput() {
        var ids = [];
        $('.noka-admin-item').each(function() {
            ids.push($(this).data('id'));
        });
        $input.val(ids.join(','));
        
        // Visual feedback
        var $btn = $('#noka-add-media');
        var originalText = $btn.html();
    }
});