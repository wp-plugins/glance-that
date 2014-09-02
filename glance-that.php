<?php
/**
 * Plugin Name: Glance That
 * Plugin URI: http://vandercar.net/wp/glance-that
 * Description: Adds content control to At a Glance on the Dashboard
 * Version: 1.6
 * Author: UaMV
 * Author URI: http://vandercar.net
 *
 * The Glance That plugin was created to extend At A Glance.
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU 
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume 
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package Glance That
 * @version 1.6
 * @author UaMV
 * @copyright Copyright (c) 2013, UaMV
 * @link http://vandercar.net/wp/glance-that
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/**
 * Define plugins globals.
 */

define( 'GT_VERSION', '1.6' );
define( 'GT_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'GT_DIR_URL', plugin_dir_url( __FILE__ ) );
! defined( 'GT_SHOW_ALL' ) ? define( 'GT_SHOW_ALL', TRUE ) : FALSE;

/**
 * Get instance of class if in admin.
 */

is_admin() ? Glance_That::get_instance() : FALSE;

/**
 * Glance That Class
 *
 * Extends functionality of the Dashboard's At a Glance metabox
 *
 * @package Glance That
 * @author  UaMV
 */
class Glance_That {

	/*---------------------------------------------------------------------------------*
	 * Attributes
	 *---------------------------------------------------------------------------------*/

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0
	 *
	 * @var     string
	 */
	protected $version = GT_VERSION;

	/**
	 * Instance of this class.
	 *
	 * @since    1.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Notices.
	 *
	 * @since    1.0
	 *
	 * @var      array
	 */
	protected $notices;

	/*---------------------------------------------------------------------------------*
	 * Consturctor
	 *---------------------------------------------------------------------------------*/

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0
	 */
	private function __construct() {

		// Retrieve current admin page
		global $pagenow;

		// Restrict calls to dashboard
		if ( 'index.php' == $pagenow ) {

			// Load the administrative Stylesheets and JavaScript
			add_action( 'admin_enqueue_scripts', array( $this, 'add_stylesheets_and_javascript' ) );

			// Process the form
			add_action( 'admin_init', array( $this, 'process_form' ) );

			// Load up an administration notice to guide users to the next step
			add_action( 'admin_notices', array( $this, 'show_notices' ) );

			// Add post statuses to native types
			add_action( 'admin_footer', array( $this, 'add_statuses') );

		}

		// Add custom post types to end of At A Glance table
		add_filter( 'dashboard_glance_items', array( $this, 'customize_items' ), 10, 1 );

		// Add form activation to end of At A Glance table
		add_filter( 'dashboard_glance_items', array( $this, 'add_form_activation_link' ), 20, 1 );

		// Add form to end of At A Glance
		add_action( 'activity_box_end', array( $this, 'add_form' ) );

	} // end constructor

	/*---------------------------------------------------------------------------------*
	 * Public Functions
	 *---------------------------------------------------------------------------------*/

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		} // end if

		return self::$instance;

	} // end get_instance

	/**
	 * Registers the plugin's administrative stylesheets and JavaScript
	 *
	 * @since    1.0
	 */
	public function add_stylesheets_and_javascript() {
		global $pagenow;

		if ( 'index.php' == $pagenow ) {
			wp_enqueue_style( 'glance', GT_DIR_URL . 'glance.css', array(), GT_VERSION );
			wp_enqueue_script( 'glance-that', GT_DIR_URL . 'glance.js', array(), GT_VERSION );
		}

	} // end add_stylesheets_and_javascript

	/**
	 * Adds statuses to the end of native post type items
	 *
	 * @since    1.4
	 */
	public function add_statuses() {

		if ( GT_SHOW_ALL ) { ?>
		
			<script type="text/javascript" language="javascript">
				jQuery(document).ready(function($) {

					<?php foreach ( array( 'post', 'page' ) as $item ) {

						$num_posts = wp_count_posts( $item );

						$moderation = intval( $num_posts->pending ) > 0 ? 'gt-moderate' : '';
						
						$statuses = '<div id="gt-statuses-' . $item . '" class="gt-statuses">';
						if ( current_user_can( get_post_type_object( $item )->cap->publish_posts ) ) {
							$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=future" class="gt-future">' . $num_posts->future . '</a></div>';
						}
						if ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) {
							$statuses .= '<div class="gt-status ' . $moderation . '"><a href="edit.php?post_type=' . $item . '&post_status=pending" class="gt-pending">' . $num_posts->pending . '</a></div>';
						}
						if ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) {
							$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=draft" class="gt-draft">' . $num_posts->draft . '</a></div>';
						}
						if ( current_user_can( get_post_type_object( $item )->cap->edit_private_posts ) ) {
							$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=private" class="gt-private">' . $num_posts->private . '</a></div>';
						}
						if ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) && current_user_can( get_post_type_object( $item )->cap->delete_posts ) ) {
							$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=trash" class="gt-trash">' . $num_posts->trash . '</a></div>';
						}
						$statuses .= '</div>'; ?>

						$('.<?php echo $item; ?>-count').append('<?php echo $statuses; ?>');
				
					<?php }

					if ( current_user_can( 'moderate_comments' ) ) {

						$num_comments = wp_count_comments();

						$moderation = intval( $num_comments->moderated ) > 0 ? 'gt-moderate' : '';

						$statuses = '<div id="gt-statuses-comments" class="gt-statuses">';
						$statuses .= '<div class="gt-status ' . $moderation . '"><a href="edit-comments.php?comment_status=moderated" class="gt-pending">' . $num_comments->moderated . '</a></div>';
						$statuses .= '<div class="gt-status"><a href="edit-comments.php?comment_status=spam" class="gt-spam">' . $num_comments->spam . '</a></div>';
						$statuses .= '<div class="gt-status"><a href="edit-comments.php?comment_status=trash" class="gt-trash">' . $num_comments->trash . '</a></div>';
						$statuses .= '</div>'; ?>

						$('.comment-count').append('<?php echo $statuses; ?>');

					<?php } ?> 

				});
			</script>
		<?php }

	} // end add_statuses

	/**
	 * Adds custom post types to the end of At a Glance table
	 *
	 * @since    1.0
	 */
	public function customize_items( $elements ) {

		// Get the current users activated glances
		$glances = $this->users_glance_that();

		// If not empty, add items
		if ( '' != $glances ) {
			
			foreach ( $glances as $item => $options ) {

				switch ( $item ) {
					case 'revision':
						$num_posts = wp_count_posts( $item );
						if ( $num_posts && $num_posts->inherit && current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) {
							$text = _n( '%s ' . get_post_type_object( $item )->labels->singular_name, '%s ' . get_post_type_object( $item )->labels->name, $num_posts->inherit );
						
							$text = sprintf( $text, number_format_i18n( $num_posts->inherit ) );

							ob_start();
								printf( '<style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><a data-gt="%1$s" href="#" class="glance-that" style="pointer-events:none;color:#444;">%2$s</a>', $item, $text );
							$elements[] = ob_get_clean();
						}
						break;

					case 'attachment':
						$num_posts = wp_count_posts( $item );
						$unattached = get_posts( array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => NULL, 'post_parent' => 0 ) );
						$unattached = count( $unattached );

						if ( $num_posts && $num_posts->inherit && current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) {
							$text = _n( '%s ' . get_post_type_object( $item )->labels->singular_name, '%s ' . get_post_type_object( $item )->labels->name, $num_posts->inherit );
						
							$text = sprintf( $text, number_format_i18n( $num_posts->inherit ) );

							if ( GT_SHOW_ALL ) {
								$statuses = '<div class="gt-statuses">';
								$statuses .= '<div class="gt-status"><a href="upload.php?detached=1" class="gt-unattached">' . $unattached . '</a></div>';
								$statuses .= '</div>';
							}

							ob_start();
								printf( '<style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><a data-gt="%1$s" href="upload.php" class="glance-that">%2$s</a>%3$s', $item, $text, $statuses );
							$elements[] = ob_get_clean();
						}
						break;

					case 'plugin':
						$plugins = get_plugins();
						$num_plugins = count( $plugins );
						$num_plugins_active = 0;

						$plugin_updates = get_plugin_updates();
						$num_plugin_updates = count( $plugin_updates );
						
						foreach ( $plugins as $plugin => $data ) {
							is_plugin_active( $plugin ) ? $num_plugins_active++ : FALSE;
						}

						if ( $num_plugins && current_user_can( 'activate_plugins' ) ) {
							$text = _n( '%s Plugin', '%s Plugins', $num_plugins );
						
							$text = sprintf( $text, number_format_i18n( $num_plugins ) );

							if ( GT_SHOW_ALL ) {
								$statuses = '<div class="gt-statuses">';
									$statuses .= '<div class="gt-status"><a href="plugins.php?plugin_status=active" class="gt-active">' . $num_plugins_active . '</a></div>';
									$moderation = intval( $num_plugin_updates ) > 0 ? 'gt-moderate' : '';
									$statuses .= '<div class="gt-status ' . $moderation . '"><a href="plugins.php?plugin_status=upgrade" class="gt-update">' . $num_plugin_updates . '</a></div>';
									$statuses .= '<div class="gt-status"><a href="plugins.php?plugin_status=inactive" class="gt-inactive">' . ( $num_plugins - $num_plugins_active ) . '</a></div>';
								$statuses .= '</div>';
							}

							ob_start();
								printf( '<style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><div class="gt-published"><a data-gt="%1$s" href="plugins.php" class="glance-that">%2$s</a></div>%3$s', $item, $text, $statuses );
							$elements[] = ob_get_clean();
						}

						break;

					case 'user':
						$num_users = count_users();
						if ( current_user_can( 'list_users' ) ) {
							$text = _n( '%s User', '%s Users', $num_users['total_users'] );
						
							$text = sprintf( $text, number_format_i18n( $num_users['total_users'] ) );

							ob_start();
								printf( '<style type="text/css">#dashboard_right_now li a[data-gt="user"]:before{content:\'\\' . $options['icon'] . '\';}</style><a data-gt="user" href="users.php" class="glance-that">%1$s</a>', $text );
							$elements[] = ob_get_clean();
						}
						break;

					default:
						if ( post_type_exists( $item ) ) {
							$num_posts = wp_count_posts( $item );
							if ( $num_posts && $num_posts->publish && current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) {
								$text = _n( '%s ' . get_post_type_object( $item )->labels->singular_name, '%s ' . get_post_type_object( $item )->labels->name, $num_posts->publish );
							
								$text = sprintf( $text, number_format_i18n( $num_posts->publish ) );

								if ( GT_SHOW_ALL ) {
									$statuses = '<div class="gt-statuses">';
									if ( current_user_can( get_post_type_object( $item )->cap->publish_posts ) ) {
										$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=future" class="gt-future">' . $num_posts->future . '</a></div>';
									}
									if ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) {
										$moderation = intval( $num_posts->pending ) > 0 ? 'gt-moderate' : '';
										$statuses .= '<div class="gt-status ' . $moderation . '"><a href="edit.php?post_type=' . $item . '&post_status=pending" class="gt-pending">' . $num_posts->pending . '</a></div>';
									}
									if ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) {
										$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=draft" class="gt-draft">' . $num_posts->draft . '</a></div>';
									}
									if ( current_user_can( get_post_type_object( $item )->cap->edit_private_posts ) ) {
										$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=private" class="gt-private">' . $num_posts->private . '</a></div>';
									}
									if ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) && current_user_can( get_post_type_object( $item )->cap->delete_posts ) ) {
										$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=trash" class="gt-trash">' . $num_posts->trash . '</a></div>';
									}
									$statuses .= '</div>';
								}

								ob_start();
									printf( '<style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><div class="gt-published"><a data-gt="%1$s" href="edit.php?post_type=%1$s" class="glance-that">%2$s</a></div>%3$s', $item, $text, $statuses );
								$elements[] = ob_get_clean();
							}
						}
						break;
				}
			}
		}

		return $elements;
	}

	/**
	 * Adds a link to the At a Glance to show Add/Remove form
	 *
	 * @since    1.0
	 */
	public function add_form_activation_link( $elements ) {

		// Define a link handled by jquery to show the form
		$html = '<a href="#" id="show-gt-form"';
		$html .= ( isset( $_GET['action'] ) && ( 'add-gt-item' == $_GET['action'] || 'remove-gt-item' == $_GET['action'] ) ) ? ' style="display:none;">' : '>';
		$html .= 'Add/Remove Item</a>';

		// Add it to the At A Glance elements array and return results
		$elements[] = $html;
		return $elements;

	}

	/**
	 * Adds a form for adding/removing custom post types from the At A Glance
	 *
	 * @since    1.2
	 */
	public function add_form() {

		// Define dashicon fields allowable icons
		$iconset = array(
			'admin-site',
			'dashboard',
			'admin-post',
			'admin-media',
			'admin-links',
			'marker',
			'admin-page',
			'admin-comments',
			'admin-plugins',
			'admin-users',
			'admin-network',
			'admin-home',
			'welcome-write-blog',
			'welcome-view-site',
			'welcome-widgets-menus',
			'welcome-learn-more',			
			'format-aside',
			'format-image',
			'format-gallery',
			'format-video',
			'format-status',
			'format-quote',
			'format-chat',
			'format-audio',
			'camera',
			'images-alt',
			'images-alt2',
			'video-alt',
			'video-alt2',
			'video-alt3',
			'playlist-audio',
			'playlist-video',
			'editor-help',
			'lock',
			'calendar',
			'visibility',
			'post-status',
			'edit',
			'share',
			'share-alt',
			'share-alt2',
			'twitter',
			'rss',
			'email',
			'email-alt',
			'facebook',
			'googleplus',
			'networking',
			'art',
			'performance',
			'universal-access',
			'tickets',
			'nametag',
			'clipboard',
			'heart',
			'megaphone',
			'schedule',
			'wordpress',
			'pressthis',
			'update',
			'screenoptions',
			'info',
			'cart',
			'feedback',
			'cloud',
			'translation',
			'tag',
			'category',
			'archive',
			'marker',	
			'star-filled',
			'flag',
			'location',
			'location-alt',
			'shield',
			'shield-alt',
			'sos',
			'search',
			'slides',
			'analytics',
			'chart-pie',
			'chart-bar',
			'chart-area',
			'groups',
			'businessman',
			'id-alt',
			'products',
			'awards',
			'forms',
			'testimonial',
			'portfolio',
			'book-alt',
			'download',
			'backup',
			'lightbulb',
			'microphone',
		);

		// Get the current users activated glances
		$glances = $this->users_glance_that();

		// Assemble a form for adding/removing post types
		$html = '<form id="gt-form" method="post" action="index.php?action=add-gt-item"';

			// Keep form visible if submission has just been made
			$html .= ( isset( $_GET['action'] ) && ( 'add-gt-item' == $_GET['action'] || 'remove-gt-item' == $_GET['action'] ) ) ? '>' : ' style="display:none;">';
			
			// Build up the list of post types
			$post_types = get_post_types( array(), 'objects' );

			// Apply filters to available post types
			$post_types = apply_filters( 'gt_post_type_selection', $post_types );

			// Get the dashicon field
			$html .= $this->get_dashicon_field( 'gt-item-icon', 'marker', $iconset );

			$html .= ' <select id="gt-item" name="gt-item">';
				$html .= '<option value""></option>';
				foreach( $post_types as $index => $post_type ) {

					// Set data-glancing attribute
					$glancing = isset( $glances[ $post_type->name ] ) ? 'data-glancing="shown"' : 'data-glancing="hidden"';

					// Only show revisions to admininstrators
					if ( 'revision' == $post_type->name && current_user_can( 'edit_dashboard' ) ) {
						$html .= '<option value="' . esc_attr( $post_type->name ) . '" data-dashicon="backup" ' . $glancing . '>' . esc_html( $post_type->labels->name ) . '</option>';
					}

					// Only show post types on which user has edit permissions
					elseif ( current_user_can( $post_type->cap->edit_posts ) && 'post' != $post_type->name && 'page' != $post_type->name && 'nav_menu_item' != $post_type->name ) {
						$html .= '<option value="' . esc_attr( $post_type->name ) . '" data-dashicon="';
						// add default dashicons for post types
						if ( 'attachment' == $post_type->name ) {
							$html .= 'admin-media';
						} elseif ( ! empty( $post_type->menu_icon  ) ) {
							$html .= esc_attr( str_replace( 'dashicons-', '', $post_type->menu_icon ) );
						} else {
							$html .= 'marker';
						}
						$html .= '" ' . $glancing . '>' . esc_html( $post_type->labels->name ) . '</option>';
					}

				}

				// Set data-glancing attribute
				$glancing = isset( $glances['user'] ) ? 'data-glancing="shown"' : 'data-glancing="hidden"';

				// Only show users option if user can list users
				current_user_can( 'list_users' ) ? $html .= '<option value="user" data-dashicon="admin-users" ' . $glancing . '>Users</options>' : FALSE;

				// Set data-glancing attribute
				$glancing = isset( $glances['plugin'] ) ? 'data-glancing="shown"' : 'data-glancing="hidden"';

				// Only show plugins optino if user can activate plugins
				current_user_can( 'activate_plugins' ) ? $html .= '<option value="plugin" data-dashicon="admin-plugins" ' . $glancing . '>Plugins</options>' : FALSE;

			$html .= '</select>';
			
			// Set the submission buttons which are handled via jquery
			$html .= '<span style="float: right;">';
				$html .= '<input type="submit" class="button-primary" value="Add" id="add-gt-item" />';
				$html .= '<input type="submit" class="button-primary" value="Remove" id="remove-gt-item" />';
			$html .= '</span>';

		$html .= '</form>';

		echo $html;

	}

	/**
	 * Process any responses to the displayed notices.
	 *
	 * @since    2.1.0
	 */
	public function process_form() {
		
		// Check if in admin and user has submitted the form
		if ( is_admin() && isset( $_GET['action'] ) && ( 'add-gt-item' == $_GET['action'] || 'remove-gt-item' == $_GET['action'] ) ) {

			// Get current user
			$current_user = wp_get_current_user();

			// Get the current users activated glances
			$glances = $this->users_glance_that();

			// Get the submitted post type glance
			$glance = isset( $_POST['gt-item'] ) ? $_POST['gt-item'] : '';

			// Get all post types
			$post_types = get_post_types();

			// If adding an item
			if ( 'add-gt-item' == $_GET['action'] ) {

				// If no item is selected
				if ( '' == $glance ) {
					$this->notices[] = array( 'message' => 'You must select an item to add.', 'class' => 'error' );
				}
				// Otherwise, add submitted item
				else {

					// Add item to glance_that user meta
					$glances[ $glance ] = array( 'icon' => $_POST['gt-item-icon'] );

					// Alphabetize the items
					ksort( $glances );

					// Update the meta
					update_user_meta( $current_user->ID, 'glance_that', $glances );

					// Display notices
					if ( in_array( $glance, $post_types ) ) {
						$this->notices[] = array( 'message' => '<strong>' . esc_html( get_post_type_object( $glance )->labels->name ) . '</strong> were successfully added to your glances.', 'class' => 'updated' );
					} elseif ( 'user' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Users</strong> were successfully added to your glances.', 'class' => 'updated' );
					} elseif ( 'plugin' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Plugins</strong> were successfully added to your glances.', 'class' => 'updated' );
					}
				}

			// If removing item
			} elseif ( 'remove-gt-item' == $_GET['action'] ) {

				// If no item is selected
				if ( '' == $glance ) {
					$this->notices[] = array( 'message' => 'You must select an item to remove.', 'class' => 'error' );
				}
				// Otherwise, remove submitted item
				else {

					// Remove item from glance_that user meta
					unset( $glances[ $glance ] );

					// Update the option
					update_user_meta( $current_user->ID, 'glance_that', $glances );
					
					// Display notices
					if ( in_array( $glance, $post_types ) ) {
						$this->notices[] = array( 'message' => '<strong>' . esc_html( get_post_type_object( $glance )->labels->name ) . '</strong> were successfully removed from your glances.', 'class' => 'updated' );
					} elseif ( 'user' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Users</strong> were successfully removed from your glances.', 'class' => 'updated' );
					} elseif ( 'plugin' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Plugins</strong> were successfully removed from your glances.', 'class' => 'updated' );
					}
				}

			}

		}
	} // end process_notice_response

	/**
	 * Process any responses to the displayed notices.
	 *
	 * @since    1.0
	 */
	public function show_notices() {

		if ( ! empty( $this->notices ) ) {
			foreach ( $this->notices as $key => $notice ) {
				if ( 'error' == $notice['class'] )
					_e( '<div class="error"><p><strong>' . $notice['message'] . '</strong></p></div>' );
				elseif ( 'update-nag' == $notice['class'] )
					_e( '<div class="update-nag">' . $notice['message'] . '</div>' );
				else
					_e( '<div class="updated fade"><p>' . $notice['message'] . '</p></div>' );
			}
		}

	}

	/**
	 * Assembles a form field for dashicon selection.
	 *
	 * @since    1.2
	 */

	public function get_dashicon_field( $id = 'dashicon', $default = 'marker', $options = array() ) {
		
		// Define all dashicons in associate array with code and class id excluding dashicon- prefix
		$dashicons = array(
			'f333' => 'menu',
			'f319' => 'admin-site',
			'f226' => 'dashboard',
			'f109' => 'admin-post',
			'f104' => 'admin-media',
			'f103' => 'admin-links',
			'f105' => 'admin-page',
			'f101' => 'admin-comments',
			'f100' => 'admin-appearance',
			'f106' => 'admin-plugins',
			'f110' => 'admin-users',
			'f107' => 'admin-tools',
			'f108' => 'admin-settings',
			'f112' => 'admin-network',
			'f102' => 'admin-home',
			'f111' => 'admin-generic',
			'f148' => 'admin-collapse',	
			
			// welcome screen
			'f119' => 'welcome-write-blog',
			'f133' => 'welcome-add-page',
			'f115' => 'welcome-view-site',
			'f116' => 'welcome-widgets-menus',
			'f117' => 'welcome-comments',
			'f118' => 'welcome-learn-more',			

			// post formats
			'f123' => 'format-aside',
			'f128' => 'format-image',
			'f161' => 'format-gallery',
			'f126' => 'format-video',
			'f130' => 'format-status',
			'f122' => 'format-quote',
			'f125' => 'format-chat',
			'f127' => 'format-audio',
			'f306' => 'camera',
			'f232' => 'images-alt',
			'f233' => 'images-alt2',
			'f234' => 'video-alt',
			'f235' => 'video-alt2',
			'f236' => 'video-alt3',

			// media
			'f501' => 'media-archive',
			'f500' => 'media-audio',
			'f499' => 'media-code',
			'f498' => 'media-default',
			'f497' => 'media-document',
			'f496' => 'media-interactive',
			'f495' => 'media-spreadsheet',
			'f491' => 'media-text',
			'f490' => 'media-video',
			'f492' => 'playlist-audio',
			'f493' => 'playlist-video',
			
			// image editing
			'f165' => 'image-crop',
			'f166' => 'image-rotate-left',
			'f167' => 'image-rotate-right',
			'f168' => 'image-flip-vertical',
			'f169' => 'image-flip-horizontal',
			'f171' => 'undo',
			'f172' => 'redo',
			
			// tinymce
			'f200' => 'editor-bold',
			'f201' => 'editor-italic',	
			'f203' => 'editor-ul',
			'f204' => 'editor-ol',
			'f205' => 'editor-quote',
			'f206' => 'editor-alignleft',
			'f207' => 'editor-aligncenter',
			'f208' => 'editor-alignright',
			'f209' => 'editor-insertmore',
			'f210' => 'editor-spellcheck',
			'f211' => 'editor-expand',
			'f506' => 'editor-contract',
			//'f211' => 'editor-distractionfree', duplicate
			'f212' => 'editor-kitchensink',
			'f213' => 'editor-underline',
			'f214' => 'editor-justify',
			'f215' => 'editor-textcolor',
			'f216' => 'editor-paste-word',
			'f217' => 'editor-paste-text',
			'f218' => 'editor-removeformatting',
			'f219' => 'editor-video',
			'f220' => 'editor-customchar',
			'f221' => 'editor-outdent',
			'f222' => 'editor-indent',
			'f223' => 'editor-help',
			'f224' => 'editor-strikethrough',
			'f225' => 'editor-unlink',
			'f320' => 'editor-rtl',
			'f464' => 'editor-break',
			'f475' => 'editor-code',
			'f476' => 'editor-paragraph',
			
			// posts
			'f135' => 'align-left',
			'f136' => 'align-right',	
			'f134' => 'align-center',
			'f138' => 'align-none',
			'f160' => 'lock',
			'f145' => 'calendar',
			'f177' => 'visibility',
			'f173' => 'post-status',
			'f327' => 'edit',
			'f182' => 'trash',
			
			// sorting
			'f142' => 'arrow-up',
			'f140' => 'arrow-down',
			'f139' => 'arrow-right',
			'f141' => 'arrow-left',
			'f342' => 'arrow-up-alt',
			'f346' => 'arrow-down-alt',
			'f344' => 'arrow-right-alt',
			'f340' => 'arrow-left-alt',
			'f343' => 'arrow-up-alt2',
			'f347' => 'arrow-down-alt2',
			'f345' => 'arrow-right-alt2',
			'f341' => 'arrow-left-alt2',
			'f156' => 'sort',
			'f229' => 'leftright',
			'f503' => 'randomize',
			'f163' => 'list-view',
			'f164' => 'exerpt-view',
			
			// social
			'f237' => 'share',
			'f240' => 'share-alt',
			'f242' => 'share-alt2',
			'f301' => 'twitter',
			'f303' => 'rss',
			'f465' => 'email',
			'f466' => 'email-alt',
			'f304' => 'facebook',
			'f305' => 'facebook-alt',
			'f462' => 'googleplus',
			'f325' => 'networking',
								
			// jobs			
			'f308' => 'hammer',
			'f309' => 'art',
			'f310' => 'migrate',
			'f311' => 'performance',
			'f483' => 'universal-access',
			'f507' => 'universal-access-alt',
			'f486' => 'tickets',
			'f484' => 'nametag',
			'f481' => 'clipboard',
			'f487' => 'heart',
			'f488' => 'megaphone',
			'f489' => 'schedule',
			
			// internal/products
			'f120' => 'wordpress',
			'f324' => 'wordpress-alt',
			'f157' => 'pressthis',
			'f463' => 'update',
			'f180' => 'screenoptions',
			'f348' => 'info',
			'f174' => 'cart',
			'f175' => 'feedback',
			'f176' => 'cloud',
			'f326' => 'translation',
			
			// taxonomies
			'f323' => 'tag',
			'f318' => 'category',

			// widgets
			'f478' => 'archive',
			'f479' => 'tagcloud',
			'f480' => 'text',
			
			// alerts/notifications/flags
			'f147' => 'yes',
			'f158' => 'no',
			'f335' => 'no-alt',
			'f132' => 'plus',
			'f502' => 'plus-alt',
			'f460' => 'minus',
			'f153' => 'dismiss',
			'f159' => 'marker',	
			'f155' => 'star-filled',
			'f459' => 'star-half',
			'f154' => 'star-empty',	
			'f227' => 'flag',
			
			// misc/cpt
			'f230' => 'location',
			'f231' => 'location-alt',	
			'f178' => 'vault',
			'f332' => 'shield',
			'f334' => 'shield-alt',
			'f468' => 'sos',
			'f179' => 'search',
			'f181' => 'slides',
			'f183' => 'analytics',
			'f184' => 'chart-pie',
			'f185' => 'chart-bar',
			'f238' => 'chart-line',
			'f239' => 'chart-area',
			'f307' => 'groups',
			'f338' => 'businessman',
			'f336' => 'id',
			'f337' => 'id-alt',
			'f312' => 'products',
			'f313' => 'awards',
			'f314' => 'forms',
			'f473' => 'testimonial',
			'f322' => 'portfolio',
			'f330' => 'book',
			'f331' => 'book-alt',
			'f316' => 'download',
			'f317' => 'upload',
			'f321' => 'backup',
			'f469' => 'clock',
			'f339' => 'lightbulb',
			'f482' => 'microphone',
			'f472' => 'desktop',
			'f471' => 'tablet',
			'f470' => 'smartphone',
			'f328' => 'smiley',
			);

		// Allow users to filter available iconset
		$options = apply_filters( $id . '_selection', $options );

		// Add registered post type dashicons, if defined
		$post_types = get_post_types( array(), 'objects' );

		// Loop through registered post types
		foreach ( $post_types as $post_type => $data ) {
			
			// If dashicon isset
			if ( ! is_null( $data->menu_icon ) ) {
				
				// If not included in options array, add it
				! in_array( str_replace( 'dashicons-', '', $data->menu_icon ), $options ) ? $options[] = str_replace( 'dashicons-', '', $data->menu_icon ) : FALSE;

			}

		}

		// Set the default icon code from default icon name
		foreach ( $dashicons as $code => $icon ) {
			$default == $icon ? $default_code = $code : FALSE;
		}

		// if dashicon set has been provided by user, replace the default dashicon set
		if ( ! empty( $options ) ) {

			// initialize limited icon array
			$limited_icons = array();

			// Loop through all available dashicons
			foreach ( $dashicons as $code => $icon ) {

				// Loop through user provided iconset
				foreach ( $options as $option ) {

					// If dashicon is in set, add it to the limited icon array
					$option == $dashicons[ $code ] ? $limited_icons[ $code ] = $dashicons[ $code ] : FALSE;
				
				}
			
			}

			// Reset the dashicons that will be used
			$dashicons = $limited_icons;
		
		}

		// Add styling for iconset
		$html = '<style type="text/css">
			.dashicon{display:inline-block;}
			.dashicon:before{
				font: normal 20px/1 \'dashicons\';
				padding: 6px;
				left: -1px;
				position: relative;
				vertical-align: top;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
				text-decoration: none !important;}
			#iconlist{
				display:none;
				position:absolute;
				padding:12px 10px;
				margin:5px 15px 0 0;
				z-index:999;
			}
			</style>';

		// Set the visible icon according to default icon
		$html .= '<div id="visible-icon" alt="' . esc_attr( $default_code ) . '" class="dashicon dashicons-' . esc_attr( $default ) . '"></div>';

		// Set the hidden form field according to provided id and default icon
		$html .= '<input id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" type="hidden" data-dashicon="selected" value="' . esc_attr( $default_code ) . '" />';

		// Container div for iconset
		$html .= '<div id="iconlist" class="postbox">';

			// Show available icons (selection currently handled by external jquery)
			foreach ( $dashicons as $code => $icon ) {
				$html .= '<div alt="' . $code . '" class="dashicon dashicons-' . $icon . ' dashicon-option" data-dashicon="' . $icon . '" style="padding-top:6px;"></div>';
			}
	
		$html .= '</div>';

		return $html;

	}

	/**
	 * Process any responses to the displayed notices.
	 *
	 * @since    1.0
	 */
	public function users_glance_that() {

		// Get the current users activated glances
		$current_user = wp_get_current_user();
		return get_user_meta( $current_user->ID, 'glance_that', TRUE );

	}

} // end class


?>