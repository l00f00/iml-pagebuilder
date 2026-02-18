<?php
/**
 * Attachment fields logic.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter( 'attachment_fields_to_edit', 'wpdude_add_custom_attachment_fields', 10, 2 );
function wpdude_add_custom_attachment_fields( $fields, $post ) {
    // Determine image orientation
    $orientation = wpdude_get_image_orientation( $post->ID );

    // Field for image alignment (Allineamento) with conditional options
    $fields['image_allineamento'] = array(
        'label' => 'Allineamento singolo',
        'input' => 'html',
        'html'  => wpdude_create_allineamento_select( $post->ID, $orientation ),
        'helps' => 'Seleziona l\'allineamento dell\'immagine'
    );
    // Checkbox for 'Has Single Page'
    $fields['has_single_page'] = array(
        'label' => 'Ha Pagina Singola',
        'input' => 'html',
        'html'  => wpdude_create_checkbox( $post->ID, 'has_single_page', 'Check if this image has a single page' ),
    );

    return $fields;
}

function wpdude_create_allineamento_select( $post_id, $orientation ) {
    $selected_value = get_post_meta( $post_id, 'image_allineamento', true );

    // Options based on image orientation
    $options = $orientation === 'vertical' ? array('destra', 'sinistra') : array('alto', 'basso');
    
    $html = '<select name="attachments[' . $post_id . '][image_allineamento]" id="attachments-' . $post_id . '-image_allineamento">';
    foreach ( $options as $option ) {
        $selected = selected( $selected_value, $option, false );
        $html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( ucfirst($option) ) . '</option>';
    }
    $html .= '</select>';
    return $html;
}

function wpdude_create_checkbox( $post_id, $meta_key, $description ) {
    $value = get_post_meta( $post_id, $meta_key, true );
    $checked = checked( 1, $value, false );
    $html = '<label><input type="checkbox" name="attachments[' . $post_id . '][' . $meta_key . ']" value="1" ' . $checked . '> ' . $description . '</label>';
    return $html;
}

add_filter( 'attachment_fields_to_save', 'wpdude_save_custom_attachment_fields', 10, 2 );
function wpdude_save_custom_attachment_fields( $post, $attachment ) {
    // Save 'image_allineamento' field
    if ( isset( $attachment['image_allineamento'] ) ) {
        update_post_meta( $post['ID'], 'image_allineamento', $attachment['image_allineamento'] );
    }

    // Save 'has_single_page' checkbox
    if ( isset( $attachment['has_single_page'] ) ) {
        update_post_meta( $post['ID'], 'has_single_page', '1' );
    } else {
        delete_post_meta( $post['ID'], 'has_single_page' );
    }

    return $post;
}

function wpdude_get_image_orientation( $post_id ) {
    $image_data = wp_get_attachment_metadata( $post_id );
    if ( !empty( $image_data ) && isset( $image_data['width'] ) && isset( $image_data['height'] ) ) {
        $width = $image_data['width'];
        $height = $image_data['height'];
        return $width > $height ? 'horizontal' : 'vertical';
    }
    return 'square';
}

// Aggiungi una nuova colonna alla lista degli attachment
add_filter('manage_media_columns', 'wpdude_add_has_single_page_column');
function wpdude_add_has_single_page_column($columns) {
    $columns['has_single_page'] = 'Ha Pagina Singola';
    return $columns;
}

// Popola la nuova colonna con i dati
add_action('manage_media_custom_column', 'wpdude_show_has_single_page_status', 10, 2);
function wpdude_show_has_single_page_status($column_name, $post_id) {
    if ('has_single_page' === $column_name) {
        $has_single = get_post_meta($post_id, 'has_single_page', true);
        echo $has_single ? 'Sì' : 'No';
    }
}

// Add Taxonomy Filter to Media Library List View
add_action( 'restrict_manage_posts', 'iml_add_media_taxonomy_filter' );
function iml_add_media_taxonomy_filter( $post_type ) {
    // restrict_manage_posts passes $post_type as argument
    // For media library, $post_type should be 'attachment'
    
    if ( 'attachment' !== $post_type ) {
        return;
    }

    $taxonomy = 'category'; // You can change this to 'post_tag' or other custom taxonomies
    $term = isset( $_GET[$taxonomy] ) ? $_GET[$taxonomy] : '';
    
    wp_dropdown_categories( array(
        'show_option_all' => 'All Categories',
        'taxonomy'        => $taxonomy,
        'name'            => $taxonomy,
        'orderby'         => 'name',
        'selected'        => $term,
        'hierarchical'    => true,
        'show_count'      => true,
        'hide_empty'      => false,
        'value_field'     => 'slug', // Use slug for filtering
    ) );
    
    // Also add Tags filter if needed
    $taxonomy_tag = 'post_tag';
    $term_tag = isset( $_GET[$taxonomy_tag] ) ? $_GET[$taxonomy_tag] : '';
    
    wp_dropdown_categories( array(
        'show_option_all' => 'All Tags',
        'taxonomy'        => $taxonomy_tag,
        'name'            => $taxonomy_tag,
        'orderby'         => 'name',
        'selected'        => $term_tag,
        'hierarchical'    => false,
        'show_count'      => true,
        'hide_empty'      => false,
        'value_field'     => 'slug', // Use slug for filtering
    ) );
}
