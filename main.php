<?php
/**
 * Plugin Name: Attachment Filter by HocWP Team
 * Plugin URI: http://hocwp.net/project/
 * Description: Attachment Filter by HocWP Team lets you create categories to group your media files. You can not only filter media by date and format, but also create any filter you like.
 * Author: HocWP Team
 * Version: 1.0.0
 * Author URI: http://hocwp.net/
 * Text Domain: wp-attachment-filter
 * Domain Path: /languages/
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once dirname( __FILE__ ) . '/hocwp/class-hocwp-plugin.php';

class HOCWP_Attachment_Filter extends HOCWP_Plugin_Core {
	private $load_pages = array( 'upload.php', 'post.php', 'post-new.php', 'widgets.php' );

	public function __construct( $file_path ) {
		global $pagenow;
		$this->set_option_name( 'wpaf' );
		parent::__construct( $file_path );

		$labels = array(
			'action_link_text' => __( 'Settings', 'wp-attachment-filter' ),
			'options_page'     => array(
				'page_title' => __( 'Attachment Filter by HocWP Team', 'wp-attachment-filter' ),
				'menu_title' => __( 'Attachment Filter', 'wp-attachment-filter' )
			)
		);

		$this->set_labels( $labels );
		require $this->basedir . '/custom/class-hocwp-attachment-filters-list-table.php';
		$this->init();
		add_action( 'init', array( $this, 'register_taxonomy' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_head', array( $this, 'admin_head' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

			add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

			add_filter( 'ajax_query_attachments_args', array( $this, 'ajax_query_attachments_args' ) );

			if ( 'upload.php' == $pagenow || 'admin-ajax.php' == $pagenow ) {
				add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_fields_to_edit' ), 20, 2 );
			}

			if ( 'admin-ajax.php' == $pagenow ) {
				add_filter( 'attachment_fields_to_save', array( $this, 'attachment_fields_to_save' ), 20, 2 );
			}

			add_filter( 'bulk_actions-upload', array( $this, 'bulk_actions_upload' ) );
			add_filter( 'handle_bulk_actions-upload', array( $this, 'handle_bulk_actions_upload' ), 10, 3 );
		}
	}

	private function taxonomy_labels( $name, $singular_name = '', $menu_name = '' ) {
		if ( empty( $name ) ) {
			if ( ! empty( $singular_name ) ) {
				$name = $singular_name;
			} elseif ( ! empty( $menu_name ) ) {
				$name = $menu_name;
			}
		}

		if ( empty( $singular_name ) ) {
			$singular_name = $name;
		}

		if ( empty( $menu_name ) ) {
			$menu_name = $name;
		}

		$labels = array(
			'name'                       => $name,
			'singular_name'              => $singular_name,
			'menu_name'                  => $menu_name,
			'search_items'               => sprintf( _x( 'Search %s', 'attachment filter', 'wp-attachment-filter' ), $name ),
			'popular_items'              => sprintf( _x( 'Popular %s', 'attachment filter', 'wp-attachment-filter' ), $name ),
			'all_items'                  => sprintf( _x( 'All %s', 'attachment filter', 'wp-attachment-filter' ), $name ),
			'parent_item'                => sprintf( _x( 'Parent %s', 'attachment filter', 'wp-attachment-filter' ), $singular_name ),
			'parent_item_colon'          => sprintf( _x( 'Parent %s:', 'attachment filter', 'wp-attachment-filter' ), $singular_name ),
			'edit_item'                  => sprintf( _x( 'Edit %s', 'attachment filter', 'wp-attachment-filter' ), $singular_name ),
			'view_item'                  => sprintf( _x( 'View %s', 'attachment filter', 'wp-attachment-filter' ), $singular_name ),
			'update_item'                => sprintf( _x( 'Update %s', 'attachment filter', 'wp-attachment-filter' ), $singular_name ),
			'add_new_item'               => sprintf( _x( 'Add New %s', 'attachment filter', 'wp-attachment-filter' ), $singular_name ),
			'new_item_name'              => sprintf( _x( 'New %s Name', 'attachment filter', 'wp-attachment-filter' ), $singular_name ),
			'separate_items_with_commas' => sprintf( _x( 'Separate %s with commas', 'attachment filter', 'wp-attachment-filter' ), $name ),
			'add_or_remove_items'        => sprintf( _x( 'Add or remove %s', 'attachment filter', 'wp-attachment-filter' ), $name ),
			'choose_from_most_used'      => sprintf( _x( 'Choose from the most used %s', 'attachment filter', 'wp-attachment-filter' ), $name ),
			'not_found'                  => sprintf( _x( 'No %s found.', 'attachment filter', 'wp-attachment-filter' ), $name ),
			'no_terms'                   => sprintf( _x( 'No %s', 'attachment filter', 'wp-attachment-filter' ), $name ),
			'items_list_navigation'      => sprintf( _x( '%s list navigation', 'attachment filter', 'wp-attachment-filter' ), $name ),
			'items_list'                 => sprintf( _x( '%s list', 'attachment filter', 'wp-attachment-filter' ), $name ),
			'most_used'                  => _x( 'Most Used', 'attachment categories', 'wp-attachment-filter' ),
			'back_to_items'              => sprintf( _x( '\'&larr; Back to %s', 'attachment filter', 'wp-attachment-filter' ), $name )
		);

		return $labels;
	}

	public function register_taxonomy() {
		$options = $this->get_options();
		$filters = isset( $options['filters'] ) ? $options['filters'] : '';

		if ( ! is_array( $filters ) ) {
			$filters = array();
		}

		foreach ( $filters as $key => $data ) {
			if ( ! empty( $key ) ) {
				$singular_name = isset( $data['singular_name'] ) ? $data['singular_name'] : '';
				$plural_name   = isset( $data['plural_name'] ) ? $data['plural_name'] : '';
				$menu_name     = isset( $data['menu_name'] ) ? $data['menu_name'] : '';
				$hierarchical  = isset( $data['hierarchical'] ) ? absint( $data['hierarchical'] ) : 1;

				if ( 1 == $hierarchical ) {
					$hierarchical = true;
				} else {
					$hierarchical = false;
				}

				$labels = $this->taxonomy_labels( $plural_name, $singular_name, $menu_name );

				$args = array(
					'labels'                => $labels,
					'description'           => '',
					'public'                => false,
					'publicly_queryable'    => false,
					'hierarchical'          => $hierarchical,
					'show_ui'               => true,
					'show_in_menu'          => true,
					'show_in_nav_menus'     => false,
					'show_tagcloud'         => false,
					'show_in_quick_edit'    => true,
					'show_admin_column'     => true,
					'rewrite'               => false,
					'update_count_callback' => '_update_generic_term_count',
					'show_in_rest'          => false,
					'rest_base'             => false,
					'rest_controller_class' => false,
					'_builtin'              => false
				);

				register_taxonomy( $key, 'attachment', $args );
			}
		}
	}

	public function pre_get_posts( $query ) {
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

		if ( $query instanceof WP_Query && $query->is_main_query() || 'query-attachments' == $action ) {
			if ( 'attachment' == $query->get( 'post_type' ) ) {
				global $hocwp_plugin;
				$plugin = $hocwp_plugin->attachment_filter;

				if ( $plugin instanceof HOCWP_Attachment_Filter ) {
					$filters = $plugin->get_filters();

					$items = array();

					foreach ( $filters as $filter => $data ) {
						$by = isset( $_GET[ $filter ] ) ? $_GET[ $filter ] : '';

						if ( 'query-attachments' == $action ) {
							$by = isset( $_REQUEST['query'][ $filter ] ) ? $_REQUEST['query'][ $filter ] : '';
						}

						if ( ! empty( $by ) && is_numeric( $by ) && 0 < $by ) {
							$item = array(
								'taxonomy' => $filter,
								'field'    => 'id',
								'terms'    => array( $by )
							);

							$items[] = $item;
						} elseif ( ! empty( $by ) && ! is_numeric( $by ) ) {
							$item = array(
								'taxonomy' => $filter,
								'field'    => 'slug',
								'terms'    => array( $by )
							);

							$items[] = $item;
						}

						unset( $query->query[ $filter ], $query->query_vars[ $filter ] );
					}

					if ( 0 < count( $items ) ) {
						$tax_query = $query->get( 'tax_query' );
						$tax_query = (array) $tax_query;
						$tax_query = array_filter( $tax_query );

						$items['relation'] = 'AND';

						$tax_query[][] = $items;

						$query->set( 'tax_query', $tax_query );
					}
				}
			}
		}
	}

	public function restrict_manage_posts( $post_type ) {
		if ( 'attachment' == $post_type ) {
			global $hocwp_plugin;
			$obj = $hocwp_plugin->attachment_filter;

			if ( $obj instanceof HOCWP_Attachment_Filter ) {
				$filters = $obj->get_filters();

				foreach ( $filters as $filter => $data ) {
					$current = isset( $_GET[ $filter ] ) ? $_GET[ $filter ] : '';
					$id      = 'attachment-filter-by-' . $filter;
					$tax     = get_taxonomy( $filter );

					$args = array(
						'id'               => $id,
						'name'             => $filter,
						'show_option_none' => sprintf( _x( 'All %s', 'attachment category', 'wp-attachment-filter' ), $tax->label ),
						'hide_empty'       => false,
						'taxonomy'         => $filter,
						'echo'             => 1,
						'selected'         => $current,
						'class'            => 'text taxonomy-filter'
					);
					?>
					<label for="<?php echo $id; ?>"
					       class="screen-reader-text"><?php printf( _x( 'Filter by %s', 'filter attachment', 'wp-attachment-filter' ), $tax->labels->singular_name ); ?></label>
					<?php
					wp_dropdown_categories( $args );
				}
			}
		}
	}

	private function need_load_style_and_script() {
		global $pagenow, $plugin_page, $hocwp_plugin;

		if ( 'options-general.php' == $pagenow ) {
			$obj = $hocwp_plugin->attachment_filter;

			if ( $obj instanceof HOCWP_Plugin_Core ) {
				if ( $plugin_page == $obj->get_option_name() ) {
					return true;
				}
			}
		}

		return ( in_array( $pagenow, $this->load_pages ) || wp_script_is( 'media-editor' ) );
	}

	public function get_filters() {
		$options = $this->get_options();
		$filters = isset( $options['filters'] ) ? $options['filters'] : '';

		if ( ! is_array( $filters ) ) {
			$filters = array();
		}

		return $filters;
	}

	public function admin_enqueue_scripts() {
		if ( $this->need_load_style_and_script() ) {
			$filters = $this->get_filters();

			if ( 0 < count( $filters ) ) {
				$src = $this->baseurl . '/js/admin' . HP()->js_suffix();

				wp_register_script( 'wpaf-admin', $src, array(
					'jquery',
					'media-editor',
					'media-views'
				), false, true );

				$l10n = array(
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'confirmDelete' => _x( 'Are you sure you want to delete?', 'wp-attachment-filter' )
				);

				$taxonomies = array();

				$none_hierarchical_grid = $this->get_option( 'none_hierarchical_grid' );

				foreach ( $filters as $key => $data ) {
					if ( ! empty( $key ) ) {
						$tax = get_taxonomy( $key );

						if ( 1 != $none_hierarchical_grid && ! $tax->hierarchical ) {
							continue;
						}

						$taxonomy = array(
							'taxonomy'   => $tax,
							'terms'      => '',
							'option_all' => sprintf( _x( 'All %s', 'attachment filter', 'wp-attachment-filter' ), $tax->labels->singular_name ),
							'label'      => sprintf( _x( 'Filter by %s', 'attachment filter', 'wp-attachment-filter' ), $tax->labels->singular_name )
						);

						$args = array(
							'hide_empty' => false,
							'taxonomy'   => $key
						);

						$query = new WP_Term_Query( $args );
						$terms = $query->get_terms();

						if ( 0 < count( $terms ) ) {
							$taxonomy['terms'] = $terms;
						}

						$taxonomies[ $key ] = $taxonomy;
					}
				}

				$l10n['taxonomies'] = $taxonomies;

				wp_localize_script( 'wpaf-admin', 'wpaf', $l10n );
				wp_enqueue_script( 'wpaf-admin' );
			}
		}
	}

	public function ajax_query_attachments_args( $args ) {
		$taxonomies = get_object_taxonomies( 'attachment', 'objects' );
		$items      = array();

		foreach ( $taxonomies as $t ) {
			if ( $t instanceof WP_Taxonomy && $t->query_var && isset( $args[ $t->query_var ] ) ) {
				$slug = $args[ $t->query_var ];

				if ( term_exists( $slug, $t->name ) ) {
					$tax_query = isset( $args['tax_query'] ) ? $args['tax_query'] : '';
					$tax_query = (array) $tax_query;
					$tax_query = array_filter( $tax_query );

					$item = array(
						'taxonomy' => $t->query_var,
						'field'    => 'slug',
						'terms'    => $slug
					);

					$items[] = $item;
				}

				unset( $args[ $t->query_var ] );
			}
		}

		if ( 0 < count( $items ) ) {
			$items['relation'] = 'AND';
			$args['tax_query'] = array( $items );
		}

		return $args;
	}

	private function dropdown_categories( $post_id, $taxonomy, $selected ) {
		$args = array(
			'id'               => 'attachments-' . $post_id . '-' . $taxonomy->name,
			'name'             => 'attachments[' . $post_id . '][' . $taxonomy->name . '_id]',
			'show_option_none' => sprintf( _x( 'Choose %s', 'attachment category', 'wp-attachment-filter' ), $taxonomy->labels->singular_name ),
			'hide_empty'       => false,
			'taxonomy'         => $taxonomy->name,
			'echo'             => 0,
			'selected'         => $selected,
			'class'            => 'text large-text widefat'
		);

		return wp_dropdown_categories( $args );
	}

	public function attachment_fields_to_edit( $form_fields, $post ) {
		if ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( $_POST['nonce'] ) ) {
			return $form_fields;
		}

		global $pagenow, $hocwp_plugin;

		if ( 'upload.php' == $pagenow || 'admin-ajax.php' == $pagenow ) {
			$plugin = $hocwp_plugin->attachment_filter;

			if ( $plugin instanceof HOCWP_Attachment_Filter ) {
				$filters = $plugin->get_filters();
				$post_id = $post->ID;
				$data    = isset( $_POST['attachments'][ $post_id ] ) ? $_POST['attachments'][ $post_id ] : '';

				foreach ( $filters as $filter => $data ) {
					$tax = get_taxonomy( $filter );

					if ( $tax instanceof WP_Taxonomy ) {
						$terms = wp_get_post_terms( $post->ID, $filter, true );

						$value = '';

						if ( isset( $data[ $filter . '_id' ] ) ) {
							$value = $data[ $filter . '_id' ];
						} elseif ( HP()->array_has_value( $terms ) ) {
							$term  = array_shift( $terms );
							$value = $term->term_id;
						}

						$html = $this->dropdown_categories( $post_id, $tax, $value );

						if ( ! empty( $html ) ) {
							$form_fields[ $filter ] = array(
								'label' => $tax->labels->singular_name,
								'input' => 'html',
								'value' => $value,
								'html'  => $html
							);
						}

						$form_fields[ $filter . '_name' ] = array(
							'label' => sprintf( _x( '%s Name', 'attachment category', 'wp-attachment-filter' ), $tax->labels->singular_name ),
							'input' => 'text',
							'value' => '',
							'helps' => __( 'If you want to create new term, please input it here.', 'wp-attachment-filter' )
						);
					}
				}
			}
		}

		return $form_fields;
	}

	public function attachment_fields_to_save( $postdata, $attachment ) {
		global $hocwp_plugin;
		$obj = $hocwp_plugin->attachment_filter;

		if ( $obj instanceof HOCWP_Attachment_Filter ) {
			$filters = $obj->get_filters();
			$post_id = $postdata['ID'];

			foreach ( $filters as $filter => $data ) {
				$tax = get_taxonomy( $filter );

				if ( $tax instanceof WP_Taxonomy ) {
					if ( isset( $attachment[ $filter . '_name' ] ) && ! empty( $attachment[ $filter . '_name' ] ) ) {
						$name = $attachment[ $filter . '_name' ];

						if ( ! is_numeric( $name ) ) {
							wp_set_object_terms( $post_id, array_map( 'trim', preg_split( '/,+/', $name ) ), $filter, false );
						}
					} elseif ( isset( $attachment[ $filter . '_id' ] ) && HP()->is_positive_number( $attachment[ $filter . '_id' ] ) ) {
						wp_set_object_terms( $post_id, absint( $attachment[ $filter . '_id' ] ), $filter, false );
					}
				}
			}
		}

		return $postdata;
	}

	public function bulk_actions_upload( $actions ) {
		$actions['change_category'] = _x( 'Change category', 'attachment category', 'wp-attachment-filter' );

		return $actions;
	}

	public function handle_bulk_actions_upload( $redirect_to, $doaction, $post_ids ) {
		if ( 'change_category' == $doaction ) {
			if ( ! empty( $post_ids ) ) {
				$post_ids = (array) $post_ids;
				global $hocwp_plugin;
				$obj = $hocwp_plugin->attachment_filter;

				if ( $obj instanceof HOCWP_Attachment_Filter ) {
					$filters = $obj->get_filters();

					foreach ( $filters as $filter => $data ) {
						$term_id = isset( $_REQUEST[ $filter . '_top' ] ) ? $_REQUEST[ $filter . '_top' ] : '';

						if ( ! is_numeric( $term_id ) ) {
							$term_id = isset( $_REQUEST[ $filter . '_bottom' ] ) ? $_REQUEST[ $filter . '_bottom' ] : '';
						}

						if ( is_numeric( $term_id ) && $term_id > 0 ) {
							foreach ( $post_ids as $post_id ) {
								$tags = array( $term_id );
								$tags = array_map( 'absint', $tags );
								wp_set_object_terms( $post_id, $tags, $filter );
							}
						}
					}
				}
			}
		}

		return $redirect_to;
	}

	public function admin_notices() {

	}

	public function admin_init() {
		$this->add_settings_field( 'none_hierarchical_grid', __( 'None Hierarchical Grid', 'wp-attachment-filter' ), array(
			$this,
			'none_hierarchical_grid_callback'
		) );
	}

	public function none_hierarchical_grid_callback( $args ) {
		$label_for = $args['label_for'];
		$name      = $args['name'];
		$value     = $args['value'];
		?>
		<fieldset>
			<label>
				<input class="regular-text" name="<?php echo $name; ?>" id="<?php echo $label_for; ?>"
				       type="checkbox"
				       value="1" <?php checked( 1, $value ); ?>> <?php _e( 'Show none hierarchical filters on grid view mode?' ); ?>
			</label>
		</fieldset>
		<?php
	}

	public function admin_head() {
		if ( $this->need_load_style_and_script() ) {
			global $hocwp_plugin;
			$obj = $hocwp_plugin->attachment_filter;

			if ( $obj instanceof HOCWP_Attachment_Filter ) {
				$filters = $obj->get_filters();

				if ( 0 < ( $total = count( $filters ) ) ) {
					$total += 2;
					$per = round( 100 / $total );
					$per .= '% - 12px';
					$min = "480px";

					if ( 1 < $total ) {
						$min = "720px";
					}
					?>
					<style>
						.media-modal-content .media-frame .media-toolbar .media-toolbar-secondary {
							min-width: <?php echo $min; ?>;
						}

						.media-modal-content .media-frame select.attachment-filters {
							max-width: -webkit-calc(<?php echo $per; ?>);
							max-width: calc(<?php echo $per; ?>);
						}
					</style>
					<?php
				}
			}
		}
	}

	public function sanitize_callback( $input ) {
		if ( ! HP()->check_nonce() ) {
			$input = $this->get_options();
		} else {
			$input['filters'] = ( isset( $input['filters'] ) && is_array( $input['filters'] ) ) ? $input['filters'] : '';

			$input['none_hierarchical_grid'] = isset( $input['none_hierarchical_grid'] ) ? 1 : 0;
		}

		return $input;
	}
}

add_action( 'plugins_loaded', function () {
	global $hocwp_plugin;

	if ( ! is_object( $hocwp_plugin ) ) {
		$hocwp_plugin = new stdClass();
	}

	$hocwp_plugin->attachment_filter = new HOCWP_Attachment_Filter( __FILE__ );
} );