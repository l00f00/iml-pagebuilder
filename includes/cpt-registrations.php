<?php
/**
 * Register Custom Post Types and Meta Fields.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Registrazione Post Types
add_action( 'init', 'iml_register_custom_post_types' );
function iml_register_custom_post_types() {
    // 1. Portfolio
    $labels_portfolio = [
        'name'                     => esc_html__( 'Portfolio', 'iml-textdomain' ),
        'singular_name'            => esc_html__( 'Portfolio', 'iml-textdomain' ),
        'add_new'                  => esc_html__( 'Add New', 'iml-textdomain' ),
        'add_new_item'             => esc_html__( 'Add New Portfolio', 'iml-textdomain' ),
        'edit_item'                => esc_html__( 'Edit Portfolio', 'iml-textdomain' ),
        'new_item'                 => esc_html__( 'New Portfolio', 'iml-textdomain' ),
        'view_item'                => esc_html__( 'View Portfolio', 'iml-textdomain' ),
        'view_items'               => esc_html__( 'View Portfolio', 'iml-textdomain' ),
        'search_items'             => esc_html__( 'Search Portfolio', 'iml-textdomain' ),
        'not_found'                => esc_html__( 'No portfolio found.', 'iml-textdomain' ),
        'not_found_in_trash'       => esc_html__( 'No portfolio found in Trash.', 'iml-textdomain' ),
        'parent_item_colon'        => esc_html__( 'Parent Portfolio:', 'iml-textdomain' ),
        'all_items'                => esc_html__( 'All Portfolio', 'iml-textdomain' ),
        'archives'                 => esc_html__( 'Portfolio Archives', 'iml-textdomain' ),
        'attributes'               => esc_html__( 'Portfolio Attributes', 'iml-textdomain' ),
        'insert_into_item'         => esc_html__( 'Insert into portfolio', 'iml-textdomain' ),
        'uploaded_to_this_item'    => esc_html__( 'Uploaded to this portfolio', 'iml-textdomain' ),
        'featured_image'           => esc_html__( 'Featured image', 'iml-textdomain' ),
        'set_featured_image'       => esc_html__( 'Set featured image', 'iml-textdomain' ),
        'remove_featured_image'    => esc_html__( 'Remove featured image', 'iml-textdomain' ),
        'use_featured_image'       => esc_html__( 'Use as featured image', 'iml-textdomain' ),
        'menu_name'                => esc_html__( 'Portfolio', 'iml-textdomain' ),
        'filter_items_list'        => esc_html__( 'Filter portfolio list', 'iml-textdomain' ),
        'filter_by_date'           => esc_html__( '', 'iml-textdomain' ),
        'items_list_navigation'    => esc_html__( 'Portfolio list navigation', 'iml-textdomain' ),
        'items_list'               => esc_html__( 'Portfolio list', 'iml-textdomain' ),
        'item_published'           => esc_html__( 'Portfolio published.', 'iml-textdomain' ),
        'item_published_privately' => esc_html__( 'Portfolio published privately.', 'iml-textdomain' ),
        'item_reverted_to_draft'   => esc_html__( 'Portfolio reverted to draft.', 'iml-textdomain' ),
        'item_scheduled'           => esc_html__( 'Portfolio scheduled.', 'iml-textdomain' ),
        'item_updated'             => esc_html__( 'Portfolio updated.', 'iml-textdomain' ),
        'text_domain'              => esc_html__( 'iml-textdomain', 'iml-textdomain' ),
    ];
    $args_portfolio = [
        'label'               => esc_html__( 'Portfolio', 'iml-textdomain' ),
        'labels'              => $labels_portfolio,
        'description'         => '',
        'public'              => true,
        'hierarchical'        => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'show_in_rest'        => true,
        'query_var'           => true,
        'can_export'          => true,
        'delete_with_user'    => false,
        'has_archive'         => false,
        'rest_base'           => '',
        'show_in_menu'        => true,
        'menu_position'       => '',
        'menu_icon'           => 'dashicons-analytics',
        'capability_type'     => 'page',
        'supports'            => ['title', 'thumbnail'],
        'taxonomies'          => ['category', 'post_tag'],
        'rewrite'             => [
            'slug'       => 'portfolio',
            'with_front' => false,
        ],
    ];
    register_post_type( 'portfolio', $args_portfolio );

    // 2. Serie
    $labels_serie = [
        'name'                     => esc_html__( 'Serie', 'iml-textdomain' ),
        'singular_name'            => esc_html__( 'Serie', 'iml-textdomain' ),
        'add_new'                  => esc_html__( 'Add New', 'iml-textdomain' ),
        'add_new_item'             => esc_html__( 'Add New Serie', 'iml-textdomain' ),
        'edit_item'                => esc_html__( 'Edit Serie', 'iml-textdomain' ),
        'new_item'                 => esc_html__( 'New Serie', 'iml-textdomain' ),
        'view_item'                => esc_html__( 'View Serie', 'iml-textdomain' ),
        'view_items'               => esc_html__( 'View Serie', 'iml-textdomain' ),
        'search_items'             => esc_html__( 'Search Serie', 'iml-textdomain' ),
        'not_found'                => esc_html__( 'No serie found.', 'iml-textdomain' ),
        'not_found_in_trash'       => esc_html__( 'No serie found in Trash.', 'iml-textdomain' ),
        'parent_item_colon'        => esc_html__( 'Parent Serie:', 'iml-textdomain' ),
        'all_items'                => esc_html__( 'All Serie', 'iml-textdomain' ),
        'archives'                 => esc_html__( 'Serie Archives', 'iml-textdomain' ),
        'attributes'               => esc_html__( 'Serie Attributes', 'iml-textdomain' ),
        'insert_into_item'         => esc_html__( 'Insert into serie', 'iml-textdomain' ),
        'uploaded_to_this_item'    => esc_html__( 'Uploaded to this serie', 'iml-textdomain' ),
        'featured_image'           => esc_html__( 'Featured image', 'iml-textdomain' ),
        'set_featured_image'       => esc_html__( 'Set featured image', 'iml-textdomain' ),
        'remove_featured_image'    => esc_html__( 'Remove featured image', 'iml-textdomain' ),
        'use_featured_image'       => esc_html__( 'Use as featured image', 'iml-textdomain' ),
        'menu_name'                => esc_html__( 'Serie', 'iml-textdomain' ),
        'filter_items_list'        => esc_html__( 'Filter serie list', 'iml-textdomain' ),
        'filter_by_date'           => esc_html__( '', 'iml-textdomain' ),
        'items_list_navigation'    => esc_html__( 'Serie list navigation', 'iml-textdomain' ),
        'items_list'               => esc_html__( 'Serie list', 'iml-textdomain' ),
        'item_published'           => esc_html__( 'Serie published.', 'iml-textdomain' ),
        'item_published_privately' => esc_html__( 'Serie published privately.', 'iml-textdomain' ),
        'item_reverted_to_draft'   => esc_html__( 'Serie reverted to draft.', 'iml-textdomain' ),
        'item_scheduled'           => esc_html__( 'Serie scheduled.', 'iml-textdomain' ),
        'item_updated'             => esc_html__( 'Serie updated.', 'iml-textdomain' ),
        'text_domain'              => esc_html__( 'iml-textdomain', 'iml-textdomain' ),
    ];
    $args_serie = [
        'label'               => esc_html__( 'Serie', 'iml-textdomain' ),
        'labels'              => $labels_serie,
        'description'         => '',
        'public'              => true,
        'hierarchical'        => false,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'show_in_rest'        => true,
        'query_var'           => true,
        'can_export'          => true,
        'delete_with_user'    => true,
        'has_archive'         => true,
        'rest_base'           => '',
        'show_in_menu'        => true,
        'menu_position'       => 2,
        'menu_icon'           => 'dashicons-cover-image',
        'capability_type'     => 'post',
        'supports'            => ['title', 'thumbnail'],
        'taxonomies'          => ['category', 'post_tag'],
        'rewrite'             => [
            'slug'       => 'serie',
            'with_front' => false,
        ],
    ];
    register_post_type( 'serie', $args_serie );

    // 3. Progetti
    $labels_progetti = [
        'name'                     => esc_html__( 'Progetti', 'iml-textdomain' ),
        'singular_name'            => esc_html__( 'Progetto', 'iml-textdomain' ),
        'add_new'                  => esc_html__( 'Add New', 'iml-textdomain' ),
        'add_new_item'             => esc_html__( 'Add New Progetto', 'iml-textdomain' ),
        'edit_item'                => esc_html__( 'Edit Progetto', 'iml-textdomain' ),
        'new_item'                 => esc_html__( 'New Progetto', 'iml-textdomain' ),
        'view_item'                => esc_html__( 'View Progetto', 'iml-textdomain' ),
        'view_items'               => esc_html__( 'View Progetti', 'iml-textdomain' ),
        'search_items'             => esc_html__( 'Search Progetti', 'iml-textdomain' ),
        'not_found'                => esc_html__( 'No progetti found.', 'iml-textdomain' ),
        'not_found_in_trash'       => esc_html__( 'No progetti found in Trash.', 'iml-textdomain' ),
        'parent_item_colon'        => esc_html__( 'Parent Progetto:', 'iml-textdomain' ),
        'all_items'                => esc_html__( 'All Progetti', 'iml-textdomain' ),
        'archives'                 => esc_html__( 'Progetto Archives', 'iml-textdomain' ),
        'attributes'               => esc_html__( 'Progetto Attributes', 'iml-textdomain' ),
        'insert_into_item'         => esc_html__( 'Insert into progetto', 'iml-textdomain' ),
        'uploaded_to_this_item'    => esc_html__( 'Uploaded to this progetto', 'iml-textdomain' ),
        'featured_image'           => esc_html__( 'Featured image', 'iml-textdomain' ),
        'set_featured_image'       => esc_html__( 'Set featured image', 'iml-textdomain' ),
        'remove_featured_image'    => esc_html__( 'Remove featured image', 'iml-textdomain' ),
        'use_featured_image'       => esc_html__( 'Use as featured image', 'iml-textdomain' ),
        'menu_name'                => esc_html__( 'Progetti', 'iml-textdomain' ),
        'filter_items_list'        => esc_html__( 'Filter progetti list', 'iml-textdomain' ),
        'filter_by_date'           => esc_html__( '', 'iml-textdomain' ),
        'items_list_navigation'    => esc_html__( 'Progetti list navigation', 'iml-textdomain' ),
        'items_list'               => esc_html__( 'Progetti list', 'iml-textdomain' ),
        'item_published'           => esc_html__( 'Progetto published.', 'iml-textdomain' ),
        'item_published_privately' => esc_html__( 'Progetto published privately.', 'iml-textdomain' ),
        'item_reverted_to_draft'   => esc_html__( 'Progetto reverted to draft.', 'iml-textdomain' ),
        'item_scheduled'           => esc_html__( 'Progetto scheduled.', 'iml-textdomain' ),
        'item_updated'             => esc_html__( 'Progetto updated.', 'iml-textdomain' ),
        'text_domain'              => esc_html__( 'iml-textdomain', 'iml-textdomain' ),
    ];
    $args_progetti = [
        'label'               => esc_html__( 'Progetti', 'iml-textdomain' ),
        'labels'              => $labels_progetti,
        'description'         => '',
        'public'              => true,
        'hierarchical'        => false,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'show_in_rest'        => true,
        'query_var'           => true,
        'can_export'          => true,
        'delete_with_user'    => true,
        'has_archive'         => false,
        'rest_base'           => '',
        'show_in_menu'        => true,
        'menu_position'       => 3,
        'menu_icon'           => 'dashicons-camera',
        'capability_type'     => 'post',
        'supports'            => ['title', 'thumbnail'],
        'taxonomies'          => ['category', 'post_tag'],
        'rewrite'             => [
            'slug'       => 'progetto',
            'with_front' => false,
        ],
    ];
    register_post_type( 'progetto', $args_progetti );
}

// Registrazione Meta Boxes per Meta Box plugin
add_filter( 'rwmb_meta_boxes', 'iml_register_meta_boxes' );
function iml_register_meta_boxes( $meta_boxes ) {
    $prefix = '';

    // 1. Abilita3Colonne per Progetti
    $meta_boxes[] = [
        'title'      => __( 'Abilita3Colonne', 'iml-text-domain' ),
        'id'         => 'abilita3colonne',
        'post_types' => ['progetto'],
        'context'    => 'side',
        'fields'     => [
            [
                'name'          => __( 'Abilita3Colonne', 'iml-text-domain' ),
                'id'            => $prefix . 'abilita3colonne',
                'type'          => 'checkbox',
                'admin_columns' => [
                    'position' => 'after title',
                    'title'    => 'Abilita 3 Colonne',
                ],
            ],
            [
                'name'          => __( 'Abilita Spaziatura orizzontale  2em', 'iml-text-domain' ),
                'id'            => $prefix . 'abilitaSpazio',
                'type'          => 'checkbox',
                'admin_columns' => [
                    'position' => 'after title',
                    'title'    => 'Abilita Spazio Orizzontale',
                ],
            ],
            [
                'name'          => __( 'Abilita Spaziatura verticale  2em', 'iml-text-domain' ),
                'id'            => $prefix . 'abilitaSpazioVert',
                'type'          => 'checkbox',
                'admin_columns' => [
                    'position' => 'after title',
                    'title'    => 'Abilita Spazio Verticale',
                ],
            ],
            [
                'name'    => __( 'Spazio immagine principale', 'iml-text-domain' ),
                'id'      => $prefix . 'featuredImageSpacer',
                'type'    => 'radio',
                'options' => [
                    0     => __( 'Zero', 'iml-text-domain' ),
                    '2em' => __( '2em', 'iml-text-domain' ),
                ],
                'std'     => '2em',
            ],
        ],
    ];

    // 2. Campi Custom per Attachments
    $meta_boxes[] = [
        'title'       => __( 'attachments', 'iml-text-domain' ),
        'id'          => 'attachments',
        'post_types'  => ['attachment'],
        'media_modal' => true,
        'style'       => 'seamless',
        'fields'      => [
            [
                'name'          => __( 'Anno', 'iml-text-domain' ),
                'id'            => $prefix . 'anno',
                'type'          => 'text',
                'placeholder'   => 1999,
                'admin_columns' => [
                    'position' => 'after title',
                    'sort'     => 'numeric',
                ],
            ],
        ],
    ];

    return $meta_boxes;
}
