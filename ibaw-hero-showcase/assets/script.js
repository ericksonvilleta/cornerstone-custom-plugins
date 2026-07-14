jQuery(document).ready(function($) {
    
    // Use delegated events on document to ensure they bind even after Elementor refreshes the DOM
    $(document).on('mouseenter click', '.ibaw-nav-item', function(e) {
        if (e.type === 'click') e.preventDefault();
        
        const $this = $(this);
        const $container = $this.closest('.ibaw-hero-container');
        
        // 1. Update active class state (this handles the scale animation via CSS)
        $container.find('.ibaw-nav-item').removeClass('ibaw-active');
        $this.addClass('ibaw-active');

        // 2. Extract content from the clicked/hovered item
        const newTitle = $this.attr('data-title');
        const newDesc  = $this.attr('data-desc');
        const newImg   = $this.attr('data-img');

        // 3. Target display elements inside THIS specific widget container
        const $titleEl = $container.find('.ibaw-hero-title');
        const $descEl  = $container.find('.ibaw-hero-desc');
        const $imgEl   = $container.find('.ibaw-hero-product-img');

        // 4. Perform synchronized fade-out/fade-in
        if ($imgEl.attr('src') !== newImg) {
            $titleEl.stop(true, true).fadeOut(150, function() {
                $(this).text(newTitle).fadeIn(150);
            });
            $descEl.stop(true, true).fadeOut(150, function() {
                $(this).text(newDesc).fadeIn(150);
            });
            $imgEl.stop(true, true).fadeOut(150, function() {
                $(this).attr('src', newImg).fadeIn(150);
            });
        }
    });
});