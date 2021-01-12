# Custom List Table

This class is useful to add Columns, Filters and Views to a Post Type or Taxonomy List Table.

Usage example:

```php
add_action( 'admin_init', function() {
	
	Custom_List_Table::add_column( [
		'object' => 'post',
		'name' => 'custom',
		'label' => 'Custom',
		'before' => 'date',
		'callback' => function( $post_id ) {
			echo 'Hello world!';	
		}
	] );
	
} );
```

More examples you find reading the `samples.php` file.