<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class HOCWP_Attachment_Filters_List_Table extends WP_List_Table {
	protected $baseurl_edit;

	function __construct() {
		global $hocwp_plugin;
		$obj = $hocwp_plugin->attachment_filter;

		if ( $obj instanceof HOCWP_Plugin_Core ) {
			$params = array(
				'sub_page' => 'add_new_filter',
				'action'   => 'edit'
			);

			$this->baseurl_edit = add_query_arg( $params, $obj->get_options_page_url() );
		}

		parent::__construct( array(
			'singular' => __( 'list_filter', 'wp-attachment-filter' ),
			'plural'   => __( 'list_filters', 'wp-attachment-filter' ),
			'ajax'     => false
		) );
	}

	function column_default( $item, $column_name ) {
		$value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';

		if ( 'filter_name' == $column_name ) {
			$value = isset( $item['name'] ) ? $item['name'] : '';
			$url   = add_query_arg( 'filter_name', $value, $this->baseurl_edit );
			$value = '<a href="' . $url . '">' . $value . '</a>';
		} elseif ( 'hierarchical' == $column_name ) {
			if ( 0 != $value ) {
				$value = '<span class="dashicons dashicons-yes"></span>';
			} else {
				$value = '';
			}
		}

		echo $value;
	}

	function get_columns() {
		$columns = array(
			'cb'            => '<input type="checkbox">',
			'filter_name'   => _x( 'Name', 'attachment filter', 'wp-attachment-filter' ),
			'singular_name' => _x( 'Singular Name', 'attachment filter', 'wp-attachment-filter' ),
			'plural_name'   => _x( 'Plural Name', 'attachment filter', 'wp-attachment-filter' ),
			'menu_name'     => _x( 'Menu Name', 'attachment filter', 'wp-attachment-filter' ),
			'hierarchical'  => _x( 'Hierarchical', 'attachment filter', 'wp-attachment-filter' )
		);

		return $columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'delete' => _x( 'Delete', 'attachment filter', 'wp-attachment-filter' )
		);

		return $actions;
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="filters[]" value="%s">', $item['name']
		);
	}

	function prepare_items() {
		global $hocwp_plugin;
		$obj = $hocwp_plugin->attachment_filter;

		if ( ! ( $obj instanceof HOCWP_Attachment_Filter ) ) {
			return;
		}

		$filters  = $obj->get_filters();
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$primary  = $this->get_primary_column();

		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );

		$total_items = count( $filters );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => - 1
		) );

		$this->items = $filters;
	}
}