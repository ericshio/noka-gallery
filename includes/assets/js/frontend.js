jQuery(function($) {
    
    function initSingleGallery(element) {
        var $grid = $(element);
        
        if ($grid.data('noka-initialized')) return;
        $grid.data('noka-initialized', true);

        // 1. Initialize Masonry ONCE
        var msnry = $grid.masonry({
            itemSelector: '.noka-item',
            percentPosition: true,
            gutter: 0, 
            transitionDuration: '0.4s'
        });

        // 2. The Resize Fix (Standard Mobile Stability)
        var lastWindowWidth = $(window).width();

        $(window).on('resize', function() {
            var newWindowWidth = $(window).width();
            if (newWindowWidth !== lastWindowWidth) {
                lastWindowWidth = newWindowWidth;
                setTimeout(function() {
                    msnry.masonry('layout');
                }, 100);
            }
        });
    }

    // --- INIT ---
    $('.noka-masonry-grid').each(function() { initSingleGallery(this); });
    
    $(window).on('load', function() {
        $('.noka-masonry-grid').masonry('layout');
    });

    // --- Lightbox Logic ---
    var $lightbox = $('#noka-lightbox');
    if ($lightbox.length) {
        var $mediaContainer = $('#noka-lightbox-media');
        var activeGalleryItems = [];
        var activeIndex = 0;

        $(document).on('click', '.noka-lightbox-trigger', function(e) {
            e.preventDefault();
            var $parentGrid = $(this).closest('.noka-masonry-grid');
            var specificBg = $parentGrid.get(0).style.getPropertyValue('--noka-lightbox-bg');
            $lightbox.css('background', specificBg ? specificBg : 'rgba(0,0,0,0.85)');

            activeGalleryItems = [];
            var clickedUrl = $(this).attr('href');
            $parentGrid.find('.noka-lightbox-trigger').each(function(i) {
                activeGalleryItems.push({ url: $(this).attr('href'), type: $(this).data('type') });
                if($(this).attr('href') === clickedUrl) activeIndex = i;
            });
            openLightbox(activeIndex);
        });

        function openLightbox(index) {
            if(index < 0) index = activeGalleryItems.length - 1;
            if(index >= activeGalleryItems.length) index = 0;
            activeIndex = index;
            var item = activeGalleryItems[index];
            $lightbox.removeClass('noka-hidden');
            
            // --- UPDATED VIDEO LOGIC HERE ---
            // Removed 'controls'
            // Added: loop, muted, playsinline
            // Added style: pointer-events: none (prevents clicking to pause)
            $mediaContainer.html(item.type === 'video' 
                ? `<video src="${item.url}" autoplay loop muted playsinline style="max-width:100%; max-height:80vh; pointer-events: none;"></video>` 
                : `<img src="${item.url}" style="max-width:100%; max-height:90vh;">`
            );
        }

        $('.noka-next').click((e) => { e.stopPropagation(); openLightbox(activeIndex + 1); });
        $('.noka-prev').click((e) => { e.stopPropagation(); openLightbox(activeIndex - 1); });
        
        $('#noka-lightbox').on('click', function(e) {
            if ($(e.target).is('img, video, .noka-prev, .noka-next')) return;
            $lightbox.addClass('noka-hidden');
            $mediaContainer.empty();
        });

        $(document).keydown(function(e) {
            if($lightbox.hasClass('noka-hidden')) return;
            if(e.key === "ArrowRight") openLightbox(activeIndex + 1);
            if(e.key === "ArrowLeft")  openLightbox(activeIndex - 1);
            if(e.key === "Escape") { $lightbox.addClass('noka-hidden'); $mediaContainer.empty(); }
        });

        let touchStartX = 0;
        let touchEndX = 0;
        $lightbox.on('touchstart', function(e) { touchStartX = e.changedTouches[0].screenX; });
        $lightbox.on('touchend', function(e) { 
            touchEndX = e.changedTouches[0].screenX; 
            if (touchEndX < touchStartX - 50) openLightbox(activeIndex + 1);
            if (touchEndX > touchStartX + 50) openLightbox(activeIndex - 1);
        });
    }
});