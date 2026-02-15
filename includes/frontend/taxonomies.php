<?php
/**
 * Shortcodes for Tags and Categories lists
 * [iml_tags_list]
 * [iml_categories_list]
 * [iml_taxonomies_list]
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Tags List
add_shortcode('iml_tags_list', 'iml_render_tags_list');
function iml_render_tags_list($atts) {
    ob_start();
    ?>
    <div class="iml-taxonomy-wrapper">
        <h3>Tags</h3>
        <?php
        $tags = get_terms(array(
            'taxonomy' => 'post_tag',
            'hide_empty' => true,
        ));

        if (!empty($tags) && !is_wp_error($tags)) {
            echo '<ul class="tag-archive-list">';
            foreach ($tags as $tag) {
                echo '<li>';
                echo '<a href="' . esc_url(get_term_link($tag)) . '">' . esc_html($tag->name) . '</a> ';
                echo '<span>(' . esc_html($tag->count) . ')</span>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Nessun tag trovato.</p>';
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}

// 2. Categories List
add_shortcode('iml_categories_list', 'iml_render_categories_list');
function iml_render_categories_list($atts) {
    ob_start();
    ?>
    <div class="iml-taxonomy-wrapper">
        <h3>Categorie</h3>
        <?php
        $categories = get_terms(array(
            'taxonomy' => 'category',
            'hide_empty' => true,
        ));

        if (!empty($categories) && !is_wp_error($categories)) {
            echo '<ul class="tag-archive-list">'; // Reusing same class for consistent styling
            foreach ($categories as $cat) {
                echo '<li>';
                echo '<a href="' . esc_url(get_term_link($cat)) . '">' . esc_html($cat->name) . '</a> ';
                echo '<span>(' . esc_html($cat->count) . ')</span>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Nessuna categoria trovata.</p>';
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}

// 3. Both (Taxonomies) List
add_shortcode('iml_taxonomies_list', 'iml_render_taxonomies_list');
function iml_render_taxonomies_list($atts) {
    // Combine both outputs
    // Order: Categories then Tags (standard practice), or as requested?
    // User said "one for both that output exactly this layout".
    // I'll stack them.
    
    $output = '';
    $output .= do_shortcode('[iml_categories_list]');
    $output .= do_shortcode('[iml_tags_list]');
    
    return $output;
}
