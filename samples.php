<?php
	
add_action( 'admin_init', function() {
	
	
	/* Post Type Columns
	***********************************************************************/
	
	// Custom column
	
	Custom_List_Table::add_column( [
		'object' => 'post',
		'name' => 'custom',
		'label' => 'Custom',
		'before' => 'date',
		'callback' => function( $post_id ) {
			echo 'Hello world!';	
		}
	] );
	
	// External callback render
	
	Custom_List_Table::add_column( [
		'object' => 'post',
		'name' => 'external',
		'label' => 'External callback',
		'before' => 'date',
		'callback' => 'render_function',
	] );
	
	// Render Post meta
	
	Custom_List_Table::add_column( [
		'object' => 'post',
		'name' => '_edit_lock', // meta_key
		'label' => 'Meta',
		'before' => null, // after date column
		'render' => 'post_meta',
		'sort' => true,
	] );
	
	// Render Thumbnail
	
	Custom_List_Table::add_column( [
		'object' => 'post',
		'name' => 'thumbnail',
		'label' => 'Thumbnail',
		'before' => 'date',
		'render' => 'thumbnail',
	] );
	
	// Render Terms
	
	Custom_List_Table::add_column( [
		'object' => 'post',
		'name' => 'post_tag',
		'label' => 'Terms',
		'before' => 'date',
		'render' => 'terms',
	] );
	

	/* Filters
	***********************************************************************/
	
	// Taxonomy filter
	
	Custom_List_Table::add_filter( [
		'object' => 'post',
		'taxonomy' => [ 'post_tag' ],
	] );
	
	// Custom filter
	
	Custom_List_Table::add_filter( [
		'object' => 'post',
		'name' => 'client',
		'options' => [
			0 => 'All clients',
			1 => 'David',
			2 => 'Loren',
			3 => 'Cris',
		],
		/*
		'options' => function() {
			
			// Callback and Multi Level options
			
			$separator = '&nbsp;&nbsp;&nbsp;';
			
			return [
				0 => 'All clients',
				1 => str_repeat( $separator, 0 ) . 'David',
				2 => str_repeat( $separator, 1 ) . 'Loren',
				3 => str_repeat( $separator, 0 ) . 'Cris',
			];

		},
		*/
		'query' => function( $query ) {

			if ( ! empty($_GET['client']) ) {
				$query->set( 'meta_key', 'client' );
				$query->set( 'meta_value', $_GET['client'] );
			}
			
		}
	] );
	
	// Remove date filter
	
	Custom_List_Table::remove_date_filter('post');
	
	
	/* Views
	***********************************************************************/
	
	Custom_List_Table::add_view( [
		'object' => 'post',
		'name' => 'custom_view',
		'label' => 'Custom view',
		'url' => admin_url('edit.php?post_type=post'),
		'count' => function() {
			
			// Query something to get the count
			$count = 5;
			
			return $count;	
		},
	] );
	
	/* Taxonomy Columns
	***********************************************************************/
		
	Custom_List_Table::add_column( [
		'object' => 'post_tag',
		'name' => 'custom',
		'label' => 'Custom',
		'before' => 'posts',
		'callback' => function( $post_id ) {
			echo 'Hello world!';	
		}
	] );
	

} );

// External render

function render_function( $post_id ) {
	echo 'Render function';
}