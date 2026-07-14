document.addEventListener("DOMContentLoaded", function() {
    const initialize = () => {
        document.querySelectorAll('.ibaw-glass-carousel-container').forEach(slider => {
            const track = slider.querySelector('.ibaw-glass-track');
            slider.querySelector('.ibaw-glass-next').addEventListener('click', () => track.scrollBy({ left: 340, behavior: 'smooth' }));
            slider.querySelector('.ibaw-glass-prev').addEventListener('click', () => track.scrollBy({ left: -340, behavior: 'smooth' }));
        });
    };
    initialize();
    if (window.elementorFrontend) elementorFrontend.hooks.addAction('frontend/element_ready/ibaw-glass-slider.default', initialize);
});