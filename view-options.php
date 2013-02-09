<?php
/*
Plugin Name: View Blogs Options
Description: View get_option() across all blogs
Author: Kailey Lampert
Author URI: http://kaileylampert.com
*/
$view_blogs_options = new View_Blogs_Options();
class View_Blogs_Options {

	function __construct() {
		add_action('init', array( &$this, 'init' ) );
	}

	function init() {
		if ( ! is_multisite() ) {
			add_action('admin_notices', array( &$this, 'admin_notices' ) );
			return;
		}

		add_action('network_admin_menu', array( &$this, 'menu' ) );
		add_action('wp_ajax_get_blog_options', array( &$this, 'get_blog_options_cb' ) );
	}

	function admin_notices() {
		echo '<div class="error"><p>';
		_e( 'View Blogs Options is for multisite use only.', 'view-blogs-options' );
		echo '</p></div>';
	}

	function menu() {
		$this->hook = add_submenu_page( 'settings.php', __( 'View Blogs Options', 'view-blogs-options' ), __( 'View Blogs Options', 'view-blogs-options' ), 'unfiltered_html', __FILE__, array( &$this, 'page' ) );
		add_action('admin_footer-'. $this->hook, array( &$this, 'admin_footer' ) );
		add_action('admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
	}

	function page() {
		?>
		<div class="wrap"><h2><?php _e( 'View Blogs Options', 'view-blogs-options' ); ?></h2>
			<form id="option_form" method="post">
				<p><label><?php _e( 'Option name:', 'view-blogs-options' ); ?> <input type="text" name="option-name" id="option-name" value="blogdescription" /></label>
					<label><input type="checkbox" name="include-archived" id="include-archived" /> <?php _e( 'Include archived sites?', 'view-blogs-options' ); ?></label>
				<?php submit_button( __( 'Get', 'view-blogs-options' ), 'primary', 'submit', false ); ?></p>
			</form>
			<div id="blog-options-container"></div>
		</div>
		<?php

	}// end page()

	function get_blog_options_cb() {
		$opt = $_POST['option'];
		$archived = $_POST['archived'];

		global $wpdb;
		$query =  "SELECT * FROM {$wpdb->blogs}, {$wpdb->registration_log}
							WHERE site_id = '{$wpdb->siteid}'
							AND {$wpdb->blogs}.blog_id = {$wpdb->registration_log}.blog_id";

		$blog_list = $wpdb->get_results( $query, ARRAY_A ); //get blogs

		//add main site to beginning
		$blog_list[-1] = (array) get_blog_details( 1 );
		ksort($blog_list);
		foreach( $blog_list as $k => $info ) {
			if ( 'false' == $archived )
				if (isset( $info['archived'] ) && $info['archived'] == 1) continue;

			$bid = $info['blog_id'];

			switch_to_blog( $bid );

			echo '<div class="blog-option-group">';
			echo '<h3>'. get_option( 'blogname' );

				$plugins = site_url('/wp-admin/plugins.php');
				$dash = site_url('/wp-admin/');
				$view = home_url();
				$edit = network_admin_url( "site-info.php?id=$bid" );

				$edit_label = __( 'Edit', 'view-blogs-options' );
				$view_label = __( 'View', 'view-blogs-options' );
				$dashboard_label = __( 'Dashboard', 'view-blogs-options' );
				$plugins_label = __( 'Plugins page', 'view-blogs-options' );

				echo " <small class='alignright'>[<a href='$edit'>($bid) $edit_label</a>] [<a href='$view'>$view_label</a>] [<a href='$dash'>$dashboard_label</a>] [<a href='$plugins'>$plugins_label</a>] </small>";
			echo '</h3>';
			printer( get_option( $opt ) );
			echo '</div>';

			restore_current_blog();
		}
		// echo get_num_queries();
		die();
	}

	function admin_enqueue_scripts( $hook ) {
		if ( $hook != $this->hook ) return;
		?><style>
		.blog-option-group {
			padding: 1px 0;
			margin: 0 0 5px;
			background: #f8f8f8;
			border-top: 2px solid #dedede;
			overflow: hidden;
		}
		.blog-option-group pre {
			overflow-x: scroll;
			padding: 0 5px 10px;
			box-shadow: inset 0 0 24px lightgray;
		}
		.blog-option-group:nth-child(even) {
			background: #efefef;
		}
		.blog-option-group:hover {
			background: #f8f8dd;
		}
		.blog-option-group:hover pre {
			background: #fdfdfd;
		}
		</style><?php
	}

	function admin_footer() {
		?><script>
		jQuery(document).ready( function($) {
			$('#option_form').submit( function( ev ) {
				ev.preventDefault();

				$('#blog-options-container').html( '<img src="<?php echo admin_url('images/loading.gif'); ?>" />' );

				$.post( ajaxurl, {
					action: 'get_blog_options',
					option: $('#option-name').val(),
					archived: $('#include-archived').is(':checked')
				}, function( response ) {

					$('#blog-options-container').html( response );

				});
			});
		});
		</script><?php
	}

}
if ( ! function_exists( 'printer') ) {
	function printer( $input ) {
		echo '<pre>' . print_r( $input, true ) . '</pre>';
	}
}