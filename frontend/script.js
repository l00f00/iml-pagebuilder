jQuery(document).ready(function($) {
    // Initialize SimpleLightbox
    // Note: Ensure that the links intended for lightbox have the attribute data-lightbox="gallery"
    // The current PHP grid generation seems to link to permalinks, not images, so this might not trigger on the grid items themselves
    // but rather on content inside them or other parts of the site.
    jQuery('a[data-lightbox="gallery"]').simpleLightbox({
        className: 'simple-lightbox',
        widthRatio: 1,
        heightRatio: 1,
        scaleImageToRatio: true,
        animationSpeed: 005, // Check if this should be 5 or 500? Original code had 005
        fadeSpeed: 5, // Extremely fast fade?
        animationSlide: false,
        enableKeyboard: true,
        preloading: true,
        closeText: '<svg id="Layer_x" data-name="Layer x" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 1080">  <path d="M613.23,522.77l288.62,403.49h-156.52l-209.64-294.36-208.21,294.36h-149.34l284.31-397.75L195.38,153.74h156.52l188.11,265.65,189.54-265.65h146.46l-262.77,369.03Z"/></svg>',
        navText: ['<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 1080"><path d="M230.56,603.18l304.42,304.42-80.41,78.98L7.99,540,454.56,93.43l80.41,80.41L230.56,476.82h841.45v126.36H230.56Z"/></svg>','<svg id="Layer_2" data-name="Layer 2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 1080">  <path d="M849.44,476.82l-304.42-304.42,80.41-78.98,446.57,446.57-446.57,446.57-80.41-80.41,304.42-302.98H7.99v-126.36h841.45Z"/></svg>'],
        spinner: false       
    });
});
