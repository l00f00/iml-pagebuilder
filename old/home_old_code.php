<?php
// stò usando oxygen builder per fare il frontend quindi ho un tutta una parte di templates in oxygen
// tieni presente che questo codice è molto importante e non deve rompersi
// prima di questo codice ho l'animation builder che mi fà output di questo shortcode [image_pairs_json]
//$post_ids = rwmb_meta('post_homepage');
$homepage_id = get_option( 'page_on_front' );
$post_ids = get_post_meta($homepage_id, 'homepage_items_alignment', true);
echo '<div id="grid-wrapper">';
echo '<div id="custom-post-grid">';

foreach ($post_ids as $post_id => $alignment) {
    $post_type = get_post_type($post_id);
    $post_obj = get_post($post_id);
    setup_postdata($post_obj);

    if ($post_type === 'progetto' || $post_type === 'serie' || $post_type === 'portfolio') {
            $categories = get_the_terms( $post_id, 'category' );
            $tags = get_the_terms( $post_id, 'post_tag' );
            $title = get_the_title($post_id);
            $the_thumb = get_the_post_thumbnail($post_id, 'large');
            echo '<a href="' . esc_url(get_permalink($post_id)) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '">';
            echo '<div class="info-overlay">';
            echo '<div class="categories-tags">';
            # echo '<ul>';
            # if ( !empty($categories) && !is_wp_error( $categories ) ) {
            #     foreach ( $categories as $category ) {
            #       $category_link = get_category_link( $category->term_id );
            #       echo '<li href="' . esc_url( $category_link ) . '">' . esc_html( $category->name ) . '</li>';
            #     }
            # } else {
            #     //echo 'No categories found for this post.';
            # };
            # if ( !empty($tags) && !is_wp_error( $tags ) ) {
            #     foreach ( $tags as $tag ) {
            #       $tag_link = get_tag_link( $tag->term_id );
            #       echo '<li href="' . esc_url( $tag_link ) . '">' . esc_html( $tag->name ) . '</li>';
            #     }
            # } else {
            #     //echo 'No tags found for this post.';
            # };
            # echo '</ul>';
            echo '</div>';
            echo '<div class="year-title">';
            echo '<span class="title">' . esc_html($title) . '</span>';
            echo '</div>';
            echo '</div>';
            echo '<div class="image-wrapper">' . $the_thumb . '</div>';
            echo '</a>';
          //echo  $categories;
    } elseif ($post_type === 'attachment'){
            $categories = get_the_terms( $post_id, 'category' );
            $tags = get_the_terms( $post_id, 'post_tag' );
            
            $title = get_the_title($post_id);
            $single_page_true = get_post_meta($post_id, 'has_single_page', true);
            $image_url = wp_get_attachment_url($post_id); // URL dell'immagine a dimensione piena
            $thumbnail = wp_get_attachment_image_url($post_id, 'large'); // O usa una dimensione specifica
            // Se 'has_single_page' è uguale a 1, usa il permalink come href.
            $parent_id = wp_get_post_parent_id($post_id);
            $parent_type = $parent_id ? get_post_type($parent_id) : null;

        // Determina il link corretto
        if ($parent_id) {
            $href = get_permalink($parent_id);
            $title = get_the_title($parent_id);
            //echo 'parent link: ' . $href; 
        } elseif ($single_page_true == '1' && $parent_id == 0) {
            $href = get_permalink($post_id);
        }

        $the_thumb = wp_get_attachment_image($post_id, 'large');
        echo '<a href="' . esc_url($href) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . ' ' . esc_attr($post_type) . ' ' . esc_attr($parent_type) . '" data-id="' . esc_attr($post_id) . '">';
        echo '<div class="info-overlay">';
        echo '<div class="categories-tags">';
        echo '</div>';
        echo '<div class="year-title">';
        echo '<span class="title">' . esc_html($title) . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<div class="image-wrapper">' . $the_thumb . '</div>';
        echo '</a>';
    }

    wp_reset_postdata();
}
echo '</div>';
echo '</div>';
?>
<script>
jQuery(document).ready(function($) {
    jQuery('a[data-lightbox="gallery"]').simpleLightbox({
        className: 'simple-lightbox', // Adds a custom class to the lightbox wrapper
        widthRatio: 1, // Sets the maximum width of the image to 80% of the screen width
        heightRatio: 1, // Sets the maximum height of the image to 90% of the screen height
        scaleImageToRatio: true, // Prevents scaling the image larger than its original size,
        animationSpeed: 005,
        fadeSpeed: 5,
        animationSlide: false,
        enableKeyboard: true,
        preloading: true,
        closeText: '<svg id="Layer_x" data-name="Layer x" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 1080">  <path d="M613.23,522.77l288.62,403.49h-156.52l-209.64-294.36-208.21,294.36h-149.34l284.31-397.75L195.38,153.74h156.52l188.11,265.65,189.54-265.65h146.46l-262.77,369.03Z"/></svg>',
        navText: ['<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 1080"><path d="M230.56,603.18l304.42,304.42-80.41,78.98L7.99,540,454.56,93.43l80.41,80.41L230.56,476.82h841.45v126.36H230.56Z"/></svg>','<svg id="Layer_2" data-name="Layer 2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 1080">  <path d="M849.44,476.82l-304.42-304.42,80.41-78.98,446.57,446.57-446.57,446.57-80.41-80.41,304.42-302.98H7.99v-126.36h841.45Z"/></svg>'],
        spinner: false       
    });
});
</script>