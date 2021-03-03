<?php // Register Custom Post Type
function gallery_custom_post_type() {

	$labels = array(
		'name'                  => _x( 'Galleries', 'Post Type General Name', 'myfacemask' ),
		'singular_name'         => _x( 'Gallery', 'Post Type Singular Name', 'myfacemask' ),
		'menu_name'             => __( 'Gallery', 'myfacemask' ),
		'name_admin_bar'        => __( 'Gallery', 'myfacemask' ),
		'archives'              => __( 'Gallery Archives', 'myfacemask' ),
		'attributes'            => __( 'Gallery Attributes', 'myfacemask' ),
		'parent_item_colon'     => __( 'Parent Gallery:', 'myfacemask' ),
		'all_items'             => __( 'All Galleries', 'myfacemask' ),
		'add_new_item'          => __( 'Add New Gallery', 'myfacemask' ),
		'add_new'               => __( 'Add New', 'myfacemask' ),
		'new_item'              => __( 'New Gallery', 'myfacemask' ),
		'edit_item'             => __( 'Edit Gallery', 'myfacemask' ),
		'update_item'           => __( 'Update Gallery', 'myfacemask' ),
		'view_item'             => __( 'View Gallery', 'myfacemask' ),
		'view_items'            => __( 'View Galleries', 'myfacemask' ),
		'search_items'          => __( 'Search Gallery', 'myfacemask' ),
		'not_found'             => __( 'Not found', 'myfacemask' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'myfacemask' ),
		'featured_image'        => __( 'Featured Image', 'myfacemask' ),
		'set_featured_image'    => __( 'Set featured image', 'myfacemask' ),
		'remove_featured_image' => __( 'Remove featured image', 'myfacemask' ),
		'use_featured_image'    => __( 'Use as featured image', 'myfacemask' ),
		'insert_into_item'      => __( 'Insert into Gallery', 'myfacemask' ),
		'uploaded_to_this_item' => __( 'Uploaded to this Gallery', 'myfacemask' ),
		'items_list'            => __( 'Galleries list', 'myfacemask' ),
		'items_list_navigation' => __( 'Galleries list navigation', 'myfacemask' ),
		'filter_items_list'     => __( 'Filter Galleries list', 'myfacemask' ),
	);
	$args = array(
		'label'                 => __( 'Gallery', 'myfacemask' ),
		'description'           => __( 'Galleries', 'myfacemask' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'thumbnail' ),
		'taxonomies'            => array( 'topics' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-images-alt2',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true,
	);
	register_post_type( 'gallery', $args );

}
add_action( 'init', 'gallery_custom_post_type', 0 );

// Register Custom Taxonomy
function topics_custom_taxonomy() {

	$labels = array(
		'name'                       => _x( 'Topics', 'Taxonomy General Name', 'myfacemask' ),
		'singular_name'              => _x( 'Topic', 'Taxonomy Singular Name', 'myfacemask' ),
		'menu_name'                  => __( 'Topics', 'myfacemask' ),
		'all_items'                  => __( 'All Topics', 'myfacemask' ),
		'parent_item'                => __( 'Parent Topic', 'myfacemask' ),
		'parent_item_colon'          => __( 'Parent Topic:', 'myfacemask' ),
		'new_item_name'              => __( 'New Topic Name', 'myfacemask' ),
		'add_new_item'               => __( 'Add New Topic', 'myfacemask' ),
		'edit_item'                  => __( 'Edit Topic', 'myfacemask' ),
		'update_item'                => __( 'Update Topic', 'myfacemask' ),
		'view_item'                  => __( 'View Topic', 'myfacemask' ),
		'separate_items_with_commas' => __( 'Separate Topics with commas', 'myfacemask' ),
		'add_or_remove_items'        => __( 'Add or remove Topics', 'myfacemask' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'myfacemask' ),
		'popular_items'              => __( 'Popular Topics', 'myfacemask' ),
		'search_items'               => __( 'Search Topics', 'myfacemask' ),
		'not_found'                  => __( 'Not Found', 'myfacemask' ),
		'no_terms'                   => __( 'No Topics', 'myfacemask' ),
		'items_list'                 => __( 'Topics list', 'myfacemask' ),
		'items_list_navigation'      => __( 'Topics list navigation', 'myfacemask' ),
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
	);
	register_taxonomy( 'topics', array( 'gallery' ), $args );

}
add_action( 'init', 'topics_custom_taxonomy', 0 );