<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $hocwp_plugin;
$plugin = $hocwp_plugin->attachment_filter;

if ( ! ( $plugin instanceof HOCWP_Plugin_Core ) && ! ( $plugin instanceof HOCWP_Attachment_Filter ) ) {
	return;
}

$options  = $plugin->get_options();
$filters  = $plugin->get_filters();
$errors   = false;
$messages = array();
$base_url = $plugin->get_options_page_url();
$tab      = isset( $_GET['tab'] ) ? $_GET['tab'] : '';

$tabs = array(
	'general_settings' => __( 'General Settings', 'wp-attachment-filter' ),
	'list_filters'     => __( 'List Filters', 'wp-attachment-filter' )
);

if ( ! array_key_exists( $tab, $tabs ) ) {
	reset( $tabs );
	$tab = key( $tabs );
}

$headline = __( 'Attachment Filter by HocWP Team', 'wp-attachment-filter' );
$sub_page = isset( $_GET['sub_page'] ) ? $_GET['sub_page'] : '';
$table    = new HOCWP_Attachment_Filters_List_Table();
$doaction = $table->current_action();

$deleted_filters = 0;

if ( 'delete' == $doaction && HP()->check_nonce() ) {
	$list_filters = isset( $_POST['filters'] ) ? $_POST['filters'] : '';

	if ( is_array( $list_filters ) && 0 < count( $list_filters ) ) {
		foreach ( $list_filters as $filter ) {
			unset( $filters[ $filter ] );
			$deleted_filters ++;
		}

		$options['filters'] = $filters;
		$obj->update_option( $options );
	}
}
?>
<div class="wrap">
	<?php
	switch ( $sub_page ) {
		case 'add_new_filter';
			$filter_name = isset( $_GET['filter_name'] ) ? $_GET['filter_name'] : '';
			$action = isset( $_GET['action'] ) ? $_GET['action'] : '';

			if ( 'edit' == $action && ! empty( $filter_name ) ) {
				$headline = __( 'Edit Attachment Filter', 'wp-attachment-filter' );
			} else {
				$headline = __( 'Add New Attachment Filter', 'wp-attachment-filter' );
			}

			$name          = '';
			$singular_name = '';
			$plural_name   = '';
			$menu_name     = '';
			$hierarchical  = 1;

			if ( 'edit' == $action ) {
				if ( isset( $filters[ $filter_name ] ) ) {
					$item          = $filters[ $filter_name ];
					$name          = isset( $item['name'] ) ? $item['name'] : '';
					$singular_name = isset( $item['singular_name'] ) ? $item['singular_name'] : '';
					$plural_name   = isset( $item['plural_name'] ) ? $item['plural_name'] : '';
					$menu_name     = isset( $item['menu_name'] ) ? $item['menu_name'] : '';
					$hierarchical  = isset( $item['hierarchical'] ) ? absint( $item['hierarchical'] ) : 1;
				} else {
					if ( ! is_wp_error( $errors ) ) {
						$errors = new WP_Error();
					}

					$errors->add( 'not_exists', sprintf( __( '<strong>Error:</strong> The filter name <strong>%s</strong> does not exist.', 'wp-attachment-filter' ), $filter_name ) );
				}
			}

			if ( isset( $_POST['add_new_filter'] ) ) {
				$nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';

				if ( ! wp_verify_nonce( $nonce ) ) {
					if ( ! is_wp_error( $errors ) ) {
						$errors = new WP_Error();
					}

					$errors->add( 'invalid_nonce', __( '<strong>Error:</strong> You are trying to submit form with invalid nonce.', 'wp-attachment-filter' ) );
				} else {
					$data = isset( $_POST['add_new_filter'] ) ? $_POST['add_new_filter'] : '';
					$name = isset( $data['name'] ) ? $data['name'] : '';

					if ( empty( $name ) ) {
						if ( ! is_wp_error( $errors ) ) {
							$errors = new WP_Error();
						}

						$errors->add( 'empty_name', __( '<strong>Error:</strong> Please enter filter name.', 'wp-attachment-filter' ) );
					} else {
						$name = esc_html( $name );
						$name = sanitize_title( $name );
						$name = str_replace( '-', '_', $name );
					}

					$singular_name = isset( $data['singular_name'] ) ? $data['singular_name'] : '';
					$plural_name   = isset( $data['plural_name'] ) ? $data['plural_name'] : '';
					$menu_name     = isset( $data['menu_name'] ) ? $data['menu_name'] : '';
					$hierarchical  = isset( $data['hierarchical'] ) ? 1 : 0;

					if ( empty( $singular_name ) && empty( $plural_name ) && empty( $menu_name ) ) {
						if ( ! is_wp_error( $errors ) ) {
							$errors = new WP_Error();
						}

						$errors->add( 'empty_label_name', __( '<strong>Error:</strong> Please enter at least one of the name fields for using in label.', 'wp-attachment-filter' ) );
					} else {
						if ( 'edit' != $action && isset( $filters[ $name ] ) ) {
							if ( ! is_wp_error( $errors ) ) {
								$errors = new WP_Error();
							}

							$errors->add( 'name_exists', sprintf( __( '<strong>Error:</strong> The filter with name <strong>%s</strong> already exists.', 'wp-attachment-filter' ), $name ) );
						} else {
							$filters[ $name ] = array(
								'name'          => $name,
								'singular_name' => $singular_name,
								'plural_name'   => $plural_name,
								'menu_name'     => $menu_name,
								'hierarchical'  => $hierarchical
							);

							foreach ( $filters as $key => $filter ) {
								if ( is_numeric( $key ) ) {
									unset( $filters[ $key ] );
								}
							}

							$options['filters'] = $filters;
							$obj->update_option( $options );
							$url = add_query_arg( 'tab', 'list_filters', $base_url );

							if ( 'edit' == $action ) {
								$messages[] = sprintf( __( 'The filter has been updated successfully. Click <a href="%s">here</a> to view the full lists.', 'wp-attachment-filter' ), $url );
							} else {
								$messages[] = sprintf( __( 'The filter has been created successfully. Click <a href="%s">here</a> to view the full lists.', 'wp-attachment-filter' ), $url );
							}

							$name          = '';
							$singular_name = '';
							$plural_name   = '';
							$menu_name     = '';
							$hierarchical  = 1;
						}
					}
				}
			}

			$url = add_query_arg( 'tab', 'list_filters', $base_url );
			ob_start();
			?>
			<form class="add-new-filter-form" method="post" novalidate="novalidate" autocomplete="off">
				<?php wp_nonce_field(); ?>
				<table class="form-table">
					<tbody>
					<tr class="name">
						<th scope="row">
							<label for="filter_name">
								<?php _ex( 'Base Name', 'attachment filter', 'wp-attachment-filter' ); ?>
							</label>
						</th>
						<td>
							<input class="regular-text" id="filter_name"
							       name="add_new_filter[name]" type="text" value="<?php echo esc_html( $name ); ?>">

							<p class="description"><?php _e( 'The slug for attachment filter.', 'wp-attachment-filter' ); ?></p>
						</td>
					</tr>
					<tr class="singular_name">
						<th scope="row">
							<label for="singular_name">
								<?php _ex( 'Singular Name', 'attachment filter', 'wp-attachment-filter' ); ?>
							</label>
						</th>
						<td>
							<input class="regular-text" id="singular_name"
							       name="add_new_filter[singular_name]" type="text"
							       value="<?php echo esc_html( $singular_name ); ?>">
						</td>
					</tr>
					<tr class="plural_name">
						<th scope="row">
							<label for="plural_name">
								<?php _ex( 'Plural Name', 'attachment filter', 'wp-attachment-filter' ); ?>
							</label>
						</th>
						<td>
							<input class="regular-text" id="plural_name"
							       name="add_new_filter[plural_name]" type="text"
							       value="<?php echo esc_html( $plural_name ); ?>">
						</td>
					</tr>
					<tr class="menu_name">
						<th scope="row">
							<label for="menu_name">
								<?php _ex( 'Menu Name', 'attachment filter', 'wp-attachment-filter' ); ?>
							</label>
						</th>
						<td>
							<input class="regular-text" id="menu_name"
							       name="add_new_filter[menu_name]" type="text"
							       value="<?php echo esc_html( $menu_name ); ?>">
						</td>
					</tr>
					<tr class="hierarchical">
						<th scope="row">
							<label for="hierarchical">
								<?php _ex( 'Hierarchical', 'attachment filter', 'wp-attachment-filter' ); ?>
							</label>
						</th>
						<td>
							<fieldset>
								<input class="regular-text" id="hierarchical"
								       name="add_new_filter[hierarchical]" type="checkbox"
								       value="1" <?php checked( 1, $hierarchical ); ?>> <?php _e( 'Display filter as category?', 'wp-attachment-filter' ); ?>
							</fieldset>
						</td>
					</tr>
					</tbody>
				</table>
				<p class="submit">
					<a class="button button-default"
					   href="<?php echo esc_url( $url ); ?>"><?php _ex( 'Manage Lists Filters', 'attachment filter', 'wp-attachment-filter' ); ?></a>
					<?php submit_button( _x( 'Submit', 'attachment-filter', 'wp-attachment-filter' ), 'primary', 'submit', false ); ?>
				</p>
			</form>
			<?php
			$html        = ob_get_clean();
			break;
		default:
			ob_start();

			if ( 'list_filters' == $tab ) {
				$table->prepare_items();
				$url = add_query_arg( 'sub_page', 'add_new_filter', $base_url );
				?>
				<form method="post" class="filter-form">
					<?php
					$table->display();
					?>
				</form>
				<div class="clear clearfix"></div>
				<p>
					<a href="<?php echo esc_url( $url ); ?>"
					   class="page-title-action"
					   style="margin-left:0;position: static;display: inline-block;"><?php _ex( 'Add New Filter', 'attachment filter', 'wp-attachment-filter' ); ?></a>
				</p>
				<?php
			} else {
				?>
				<form method="post" action="options.php" novalidate="novalidate" autocomplete="off">
					<?php
					settings_fields( $obj->get_option_name() );
					$filters = ( is_array( $filters ) ) ? $filters : array();

					foreach ( $filters as $filter => $data ) {
						if ( is_array( $data ) ) {
							foreach ( $data as $key => $value ) {
								?>
								<input type="hidden"
								       name="<?php echo $obj->get_option_name(); ?>[filters][<?php echo $filter; ?>][<?php echo $key; ?>]"
								       value="<?php echo $value; ?>">
								<?php
							}
						}
					}
					?>
					<table class="form-table">
						<?php
						do_settings_fields( $obj->get_option_name(), 'default' );
						do_settings_sections( $obj->get_option_name() );
						?>
					</table>
					<?php submit_button(); ?>
				</form>
				<?php
			}
			$html = ob_get_clean();
	}
	?>
	<h1><?php echo esc_html( $headline ); ?></h1>
	<hr class="wp-header-end">
	<?php
	if ( 0 < $deleted_filters ) {
		?>
		<div class="notice fade hocwp-theme notice-success is-dismissible">
			<p>
				<?php printf( __( '<strong>Notice:</strong> %s filter(s) has been deleted.' ), $deleted_filters ); ?>
			</p>
		</div>
		<?php
	}

	if ( is_wp_error( $errors ) ) {
		foreach ( $errors->get_error_messages() as $message ) {
			?>
			<div class="notice fade hocwp-theme notice-error is-dismissible">
				<?php echo wpautop( $message ); ?>
			</div>
			<?php
		}
	}

	if ( count( $messages ) > 0 ) {
		foreach ( $messages as $message ) {
			?>
			<div class="notice fade hocwp-theme notice-success is-dismissible">
				<?php echo wpautop( $message ); ?>
			</div>
			<?php
		}
	}

	if ( empty( $sub_page ) ) {
		?>
		<div id="nav">
			<h2 class="nav-tab-wrapper">
				<?php
				foreach ( $tabs as $key => $value ) {
					$class = 'nav-tab';
					if ( $key == $tab ) {
						$class .= ' nav-tab-active';
					}
					$url = $base_url;
					$url = add_query_arg( 'tab', $key, $url );
					?>
					<a class="<?php echo $class; ?>"
					   href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $value ); ?></a>
					<?php
				}
				?>
			</h2>
		</div>
		<?php

	}
	echo $html;
	?>
</div>