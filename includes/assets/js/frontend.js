jQuery(function($) {
    
    // --- 1. LAZY VIDEO OBSERVER (The Speed Fix) ---
    // This watches videos and only plays them when 25% visible
    var nokaVideoObserver = null;

    if ('IntersectionObserver' in window) {
        nokaVideoObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                var video = entry.target;
                if (entry.isIntersecting) {
                    // Video entered viewport: PLAY
                    var playPromise = video.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(function(error) { 
                            // Auto-play was prevented by browser (low power mode, etc)
                        });
                    }
                } else {
                    // Video left viewport: PAUSE
                    video.pause();
                }
            });
        }, { threshold: 0.25 }); // Trigger when 25% of video is visible
    }

    function initSingleGallery(element) {
        var $grid = $(element);
        
        if ($grid.data('noka-initialized')) return;
        $grid.data('noka-initialized', true);

        // A. Register Videos with Observer
        if (nokaVideoObserver) {
            $grid.find('video.noka-video').each(function() {
                nokaVideoObserver.observe(this);
            });
        }

        // B. Initialize Masonry
        var msnry = $grid.masonry({
            itemSelector: '.noka-item',
            percentPosition: true,
            gutter: 0, 
            transitionDuration: '0.4s'
        });

        // C. Resize Fix
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
            
            // LIGHTBOX VIDEO: 
            // - No controls
            // - Autoplay (It's the focus now)
            // - Pointer events none (Can't click to pause)
            $mediaContainer.html(item.type === 'video' 
                ? `<video src="${item.url}" autoplay loop muted playsinline style="max-width:100%; max-height:80vh; pointer-events: none;"></video>` 
                : `<img src="${item.url}">`
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