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

    // 2. Initialize Sortable
    if ($container.length) {
        $container.sortable({
            items: '.noka-admin-item',
            cursor: 'grabbing',
            placeholder: 'noka-sortable-placeholder',
            forcePlaceholderSize: true,
            opacity: 0.8,
            update: function(event, ui) {
                updateInput();
            }
        });
    }

    // 3. Add Media
    $('#noka-add-media').click(function(e) {
        e.preventDefault();
        if (mediaUploader) { mediaUploader.open(); return; }
        
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Select Images or Videos',
            button: { text: 'Add to Gallery' },
            multiple: 'add', 
            library: { type: [ 'image', 'video' ] } 
        });
        
        mediaUploader.on('select', function() {
            var selection = mediaUploader.state().get('selection');
            var prepend = $('#noka-prepend-check').is(':checked'); // Get Checkbox State
            var newItemsHtml = [];

            selection.map( function( attachment ) {
                attachment = attachment.toJSON();
                var previewUrl = attachment.url;
                
                // Smart Icon Logic
                if ( attachment.type === 'image' && attachment.sizes && attachment.sizes.thumbnail ) {
                    previewUrl = attachment.sizes.thumbnail.url;
                } else if ( attachment.type === 'video' ) {
                    previewUrl = attachment.icon; // Use WP Native Icon
                }

                // Construct HTML with Filename Overlay
                var html = `
                    <div class="noka-admin-item" data-id="${attachment.id}" title="${attachment.filename}" style="position:relative;">
                        <div class="noka-admin-img" style="background-image:url('${previewUrl}')"></div>
                        <div class="noka-filename" style="position:absolute; bottom:0; left:0; right:0; background:rgba(0,0,0,0.7); color:#fff; font-size:10px; padding:2px 4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; pointer-events:none;">${attachment.filename}</div>
                        <div class="noka-remove" style="z-index:10;"><span class="dashicons dashicons-no-alt"></span></div>
                    </div>
                `;
                newItemsHtml.push(html);
            });

            if (prepend) {
                // Prepend reversed array so order matches selection order
                $container.prepend(newItemsHtml.reverse().join(''));
            } else {
                $container.append(newItemsHtml.join(''));
            }
            
            updateInput();
        });
        mediaUploader.open();
    });

    // 4. Item Selection
    $container.on('click', '.noka-admin-item', function(e) {
        if($(e.target).closest('.noka-remove').length) return; 
        $(this).toggleClass('noka-selected');
        toggleBatchButton();
    });

    // 5. Delete Logic
    $container.on('click', '.noka-remove', function(e) {
        e.stopPropagation(); 
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
    }
});