<?php
	
// File Security Check
defined( 'ABSPATH' ) or die( "No script kiddies please!" );


/*
* Custom List Table
*
* @description This class is useful to add Columns, Filters and Views to a Post Type or Taxonomy List Table.
* @author Edir Pedro
* @github https://github.com/edirpedro
*/

class Custom_List_Table {

	private static $views = [];

	private static $filters = [];

	private static $columns = [];
		
	/*
	* Initializing
	*/
	
	public static function admin_init() {
	
		// Columns
		
		foreach ( self::$columns as $object => $columns ) {
			add_filter( "manage_edit-{$object}_columns", [ __CLASS__, 'manage_columns' ], 10, 1 ); // Columns
			add_filter( "manage_edit-{$object}_sortable_columns", [ __CLASS__, 'sortable_columns' ], 10, 1 ); // Make sortable
			add_action( "manage_{$object}_posts_custom_column", [ __CLASS__, 'custom_columns' ], 10, 2 ); // Post Type
			add_filter( "manage_{$object}_custom_column", [ __CLASS__, 'custom_columns' ], 10, 3 ); // Taxonomy
		}
						
		// Ordering
		
		add_filter( 'request', [ __CLASS__, 'orderby_column' ], 10, 1 );
		
		// Filters
		
		add_action( 'restrict_manage_posts', [ __CLASS__, 'manage_filters' ] , 10, 2 );
		add_action( 'pre_get_posts', [ __CLASS__, 'pre_get_posts' ], 1, 1 );
		
		// Views
		
		foreach ( self::$views as $object => $views ) {
			add_filter( "views_edit-{$object}", [ __CLASS__, 'views' ], 10, 1 );
		}
		
	}
	
	/*
	* Get objects from the current screen
	*
	* @param array $var - One of the private variables $views, $columns or $filters has to be passed here
	* @return array
	*/
	
	private static function get_current_screen_objects( $var ) {
		
		$current_screen = get_current_screen();
				
		if ( empty($current_screen) ) {
			
			$current_screen = (object) [ 'id' => '' ];
					
			// Quick edit
			
			if ( wp_doing_ajax() ) {
				
				if ( isset($_REQUEST['screen']) )
					$current_screen->id = $_REQUEST['screen'];
				elseif ( $_REQUEST['action'] == 'inline-save-tax' )
					$current_screen->id = 'edit-' . $_REQUEST['taxonomy'];
			
			}
			
		}
		
		foreach ( $var as $object => $items ) {	
			if ( $current_screen->id == "edit-$object" )
				return $items;
		}
					
		return [];
		
	}
	
	/* Views
	***********************************************************************/
	
	/*
	* Add new view to the list
	*
	* @param array $args - List of arguments
	* @args string object - Post Type or Taxonomy name where you want to render it
	* @args string name - Name of the view
	* @args string url - The link URL
	* @args string label - The label of the link
	* @args mixed count - FALSE to disable, a Number or a Callback function to return a number
	*/
	
	public static function add_view( $args ) {
	
		$defaults = [
			'object' => 'post',
			'name' => 'view',
			'url' => '#',
			'label' => 'Label',
			'count' => false,
		];
		
		$args = wp_parse_args( $args, $defaults );
	
		self::$views[ $args['object'] ][] = $args;
				
	}
	
	/*
	* Views list, hook "views_edit-{object}"
	*/
	
	public static function views( $views ) {
	
		foreach ( self::get_current_screen_objects( self::$views ) as $view ) {
			
			$count = false;
			
			if ( isset($view['count']) && is_callable($view['count']) )
				$count = call_user_func_array( $view['count'], [] );
			
			$views[ $view['name'] ] = sprintf(
				'<a href="%s">%s%s</a>',
				$view['url'],
				$view['label'],
			 	is_numeric($count) ? ' <span class="count">(' . $count . ')</span>' : null
			);
			
		}

		return $views;
		
	}
	
	/* Filters
	***********************************************************************/
	
	/*
	* Add new filter
	*
	* @param array $args - List of arguments
	* @args string object - Post Type or Taxonomy name where you want to render it
	* @args string name - Name of the filter, usually the same name of a custom meta key 
	* @args string/array taxonomy - A single name or an array of Taxonomy names
	* @args array options - An array of options with value and labels
	* @args function query - A callback function to handle the hook "pre_get_posts"
	*/
	
	public static function add_filter( $args ) {
	
		$defaults = [
			'object' => 'post',
			'name' => null,
			'taxonomy' => null,
			'options' => [],
			'query' => null
		];
		
		$args = wp_parse_args( $args, $defaults );
	
		self::$filters[ $args['object'] ][] = $args;
	
	}
	
	/*
	* Creating new filters, hook "restrict_manage_posts"
	*/
	
	public static function manage_filters( $post_type, $which ) {
		
		global $typenow, $wp_query;
				
		foreach ( self::get_current_screen_objects( self::$filters ) as $filter ) {
			
			// Taxonomy
			
			if ( ! empty($filter['taxonomy']) ) {
				
				if ( ! is_array($filter['taxonomy']) )
					$filter['taxonomy'] = [ $filter['taxonomy'] ];
				
				foreach ( $filter['taxonomy'] as $taxonomy ) {
					
					$tax = get_taxonomy($taxonomy);
										
					wp_dropdown_categories( [
						'value_field'     => 'slug',
						'show_option_all' => $tax->labels->all_items,
						'taxonomy'        => $tax->name,
						'name'            => $tax->query_var,
						'orderby'         => 'name',
						'selected'        => array_key_exists( $tax->query_var, $wp_query->query_vars ) ? $wp_query->query_vars[ $tax->query_var ] : null,
						'hierarchical'    => true,
						'show_count'      => false,
						'hide_empty'      => true,
					] );
					
				}
					
			// Options
				
			} elseif ( ! empty($filter['options']) ) {
				
				if ( is_callable($filter['options']) )
					$options = call_user_func_array( $filter['options'], [] );
				else
					$options = $filter['options'];
				
				$name = $filter['name'];
				$current = isset($_GET[$name]) ? $_GET[$name] : null;
			
				echo '<select name="' . $name . '" id="' . $name . '" class="postform">';
				
				foreach ( $options as $key => $value ) {					
					printf(
						"<option %s value='%s'>%s</option>\n",
						selected( $key, $current, false ),
						$key,
						$value
					);
				}
				
				echo '</select>';	
				
			}
					
		}
		
	}
	
	/*
	* Running the custom filter, hook "pre_get_posts"
	*/
	
	public static function pre_get_posts( $query ) {
	
		if ( ! is_admin() || ! $query->is_main_query() )
			return;
			
		foreach ( self::get_current_screen_objects( self::$filters ) as $filter ) {
			
			if ( isset($filter['query']) )
				call_user_func_array( $filter['query'], [ $query ] );
			
		}
		
	}
	
	/*
	* Remove date filter from the list table
	*
	* @param string $post_type - Post Type name
	*/
		
	public static function remove_date_filter( $post_type ) {
		
		eval( 'add_filter( "months_dropdown_results", function( $months, $post_type ) { return $post_type == "' . $post_type . '" ? [] : $months; }, 10, 2 );' );
		
	}
	
	/* Columns
	***********************************************************************/
	
	/*
	* Add new column
	*
	* @param array $args - List of arguments
	* @args string object - Post Type or Taxonomy name where you want to render it
	* @args string name - Name of the column, the same name of a custom meta key if you want to use a built in render
	* @args string label - Title of the column
	* @args string before - Name of the column to position before it or empty if you want after date column
	* @args boolean sort - Enable of disable sorting
	* @args string sort_type - Meta Query Type https://developer.wordpress.org/reference/classes/wp_meta_query/
	* @args string render - A built in render, options are: post_meta, acf_field, terms, thumbnail
	* @args function callback - A callback function to render the field
	*/
	
	public static function add_column( $args ) {
		
		$defaults = [
			'object' => 'post',
			'name' => 'column',
			'label' => 'Label',
			'before' => null,
			'sort' => false,
			'sort_type' => 'CHAR',
			'render' => null,
			'callback' => null,
		];
		
		$args = wp_parse_args( $args, $defaults );
	
		self::$columns[ $args['object'] ][] = $args;
		
	}
	
	/*
	* Creating new columns, hook "manage_edit-{$object}_columns"
	*/
	
	public static function manage_columns( $columns ) {
			
		foreach ( self::get_current_screen_objects( self::$columns ) as $column ) {
			
			if ( ! empty($column['before']) ) {			
				$offset = array_search( $column['before'], array_keys($columns), true );
								
				if ( $offset ) {
					$columns = 	array_slice( $columns, 0, $offset, true ) +
								[ $column['name'] => $column['label'] ] +
								array_slice( $columns, $offset, count($columns) - 1, true );
					continue;
				}
			} else {
				$columns[ $column['name'] ] = $column['label'];
			}
						
		}
						
		return $columns;
		
	}
	
	/*
	* Creating sortable columns, hook "manage_edit-{$object}_sortable_columns"
	*/
	
	public static function sortable_columns( $columns ) {
				
		foreach ( self::get_current_screen_objects( self::$columns ) as $column ) {
				
			if ( $column['sort'] )
				$columns[ $column['name'] ] = $column['name'];	
			
		}
						
		return $columns;
		
	}
	
	/*
	* Ordering, hook "request"
	*/
	
	public static function orderby_column( $vars ) {
				
		global $typenow, $pagenow;
		
		if ( ! is_admin() || $pagenow != 'edit.php' || ! array_key_exists( 'orderby', $vars ) )
			return $vars;

		if ( ! array_key_exists( $typenow, self::$columns ) )
			return $vars;
		
		foreach ( self::$columns[$typenow] as $column ) {
		
			if ( $column['name'] == $vars['orderby'] ) {
				
				$vars['meta_query'] = [
					'relation' => 'OR',
					[ 'key' => $column['name'], 'compare' => 'NOT EXISTS' ],
					[ 'key' => $column['name'], 'type' => $column['sort_type'] ],
				];

			}
			
		}
			
		return $vars;
		
	}
	
	/*
	* Render custom columns, hooks "manage_{$object}_posts_custom_column" and "manage_{$object}_custom_column"
	*/
	
	public static function custom_columns( $name, $post_id, $term_id = null ) {
		
		// When in Taxonomies the first variable is empty, second is the column name
		
		if ( is_int($term_id) ) {
			$name = $post_id;
			$post_id = $term_id;
		}
		
		// Rendering

		foreach ( self::get_current_screen_objects( self::$columns ) as $column ) {
					
			if ( $column['name'] == $name ) {
				
				if ( ! empty($column['callback']) ) {
					call_user_func_array( $column['callback'], [ $post_id ] );
				
				} else {
					
					$render = 'render_' . $column['render'];
										
					if ( method_exists( __CLASS__, $render ) )
						call_user_func_array( __CLASS__ . '::' . $render, [ $name, $post_id ] );
					
				}
					
			}
			
		}
		
	}
	
	// Render type Post Meta
	
	public static function render_post_meta( $name, $post_id ) {
		
		echo get_post_meta( $post_id, $name, true );
		
	}
	
	// Render type ACF Field
	
	public static function render_acf_field( $name, $post_id ) {
		
		the_field( $name, $post_id );
		
	}
	
	// Render type Terms
	
	public static function render_terms( $name, $post_id ) {
		
		$terms = wp_get_post_terms( $post_id, $name, [ 'fields' => 'names' ] );
		if ( empty($terms) || is_wp_error($terms) )
			echo '<span aria-hidden="true">—</span>';
		else
			echo implode( ', ', $terms );
		
	}
	
	// Render type Thumbnail
	
	public static function render_thumbnail( $name, $post_id ) {
		
		if ( has_post_thumbnail( $post_id ) )
			echo get_the_post_thumbnail( $post_id, [ 60, 60 ] );
		
	}
	
}

// Start right after other calls

add_action( 'admin_init', [ 'Custom_List_Table', 'admin_init' ], 11 );