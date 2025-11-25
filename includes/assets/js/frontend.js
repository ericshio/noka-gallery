jQuery(function($) {
    
    // 1. Core Gallery Logic
    function initSingleGallery(element) {
        var $grid = $(element);
        
        if ($grid.data('noka-initialized')) return;
        $grid.data('noka-initialized', true);

        // Masonry Init
        var msnry = $grid.masonry({
            itemSelector: '.noka-item',
            percentPosition: true,
            gutter: 0, 
            transitionDuration: '0.4s'
        });
        
        setTimeout(() => msnry.masonry('layout'), 50);

        $(window).on('resize', function() {
            msnry.masonry('layout');
        });
    }

    var bodyObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { 
                    if ($(node).hasClass('noka-masonry-grid')) initSingleGallery(node);
                    $(node).find('.noka-masonry-grid').each(function() { initSingleGallery(this); });
                }
            });
        });
    });
    bodyObserver.observe(document.body, { childList: true, subtree: true });

    $('.noka-masonry-grid').each(function() { initSingleGallery(this); });


    // 2. Lightbox Logic
    var $lightbox = $('#noka-lightbox');
    if ($lightbox.length) {
        var $mediaContainer = $('#noka-lightbox-media');
        var activeGalleryItems = [];
        var activeIndex = 0;

        // Open Lightbox
        $(document).on('click', '.noka-lightbox-trigger', function(e) {
            e.preventDefault();
            var $parentGrid = $(this).closest('.noka-masonry-grid');
            
            var specificBg = $parentGrid.get(0).style.getPropertyValue('--noka-lightbox-bg');
            $lightbox.css('background', specificBg ? specificBg : 'rgba(0,0,0,0.85)');

            activeGalleryItems = [];
            var $links = $parentGrid.find('.noka-lightbox-trigger');
            var clickedUrl = $(this).attr('href');
            $links.each(function(i) {
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
            $mediaContainer.empty();
            $lightbox.removeClass('noka-hidden');
            if(item.type === 'video') {
                $mediaContainer.html(`<video controls autoplay src="${item.url}" style="max-width:100%; max-height:80vh"></video>`);
            } else {
                $mediaContainer.html(`<img src="${item.url}">`);
            }
        }

        // Click Nav
        $('.noka-next').click((e) => { e.stopPropagation(); openLightbox(activeIndex + 1); });
        $('.noka-prev').click((e) => { e.stopPropagation(); openLightbox(activeIndex - 1); });
        
        // Close on Background Click
        $('#noka-lightbox').on('click', function(e) {
            if ($(e.target).is('img, video')) return;
            $lightbox.addClass('noka-hidden');
            $mediaContainer.empty();
        });

        // Keyboard Nav
        $(document).keydown(function(e) {
            if($lightbox.hasClass('noka-hidden')) return;
            if(e.key === "ArrowRight") openLightbox(activeIndex + 1);
            if(e.key === "ArrowLeft")  openLightbox(activeIndex - 1);
            if(e.key === "Escape") { $lightbox.addClass('noka-hidden'); $mediaContainer.empty(); }
        });

        // --- NEW: Swipe Gestures (Mobile Support) ---
        let touchStartX = 0;
        let touchEndX = 0;

        $lightbox.on('touchstart', function(e) {
            // Capture starting X coordinate
            touchStartX = e.changedTouches[0].screenX;
        });

        $lightbox.on('touchend', function(e) {
            // Capture ending X coordinate
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });

        function handleSwipe() {
            // Swipe Left (Next) - Check if moved more than 50px
            if (touchEndX < touchStartX - 50) {
                openLightbox(activeIndex + 1);
            }
            // Swipe Right (Prev) - Check if moved more than 50px
            if (touchEndX > touchStartX + 50) {
                openLightbox(activeIndex - 1);
            }
        }
    }
});