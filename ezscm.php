<?php
/*
Plugin Name: ez Schedule Manager Free
Plugin URI: http://www.mials.de/mials/ezscm/
Description: ez Schedule Manager is a WordPress plugin which allows you to manage incoming schedule requests. In case you offer contact requests or simple booking on specific dates or times, this is the right plugin for you.
Version: 1.0
Author: Michael Schuppenies
Author URI: http://www.ezplugins.de/
*/

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// actions
add_action("admin_menu", "ezscm_setup", 999);
add_action("wp_head", "ezscm_wp_head");
add_action("init", "ezscm_load_language");
add_action("wp_ajax_ezscm_backend", "ezscm_ajax");
add_action("wp_ajax_ezscm_frontend", "ezscm_ajax_frontend");
add_action("wp_ajax_nopriv_ezscm_frontend", "ezscm_ajax_frontend");

// hooks
register_activation_hook(__FILE__, "ezscm_register");
register_uninstall_hook(__FILE__, "ezscm_uninstall");

// multisite
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

function ezscm_get_version() {
	return "1.0";
}

/**
	setup
**/
// register plugin
function ezscm_register() {
	require_once(plugin_dir_path(__FILE__) . "ezscm-register.php");
}
// uninstall plugin
function ezscm_uninstall() {
	require_once(plugin_dir_path(__FILE__) . "ezscm-uninstall.php");
}

/**
	admin pages
**/
function ezscm_setup() {
	add_menu_page(__("ezSCM", "ezscm"), __("ez Schedule Manager Free", "ezscm"), "manage_options", "ezscm", "ezscm_page_schedules");
	add_submenu_page("ezscm", __("Schedule settings", "ezscm"), __("Schedule settings", "ezscm"), "manage_options", "ezscm-settings-schedule", "ezscm_page_settings_schedule");
	add_submenu_page("ezscm", __("Global settings", "ezscm"), __("Global settings", "ezscm"), "manage_options", "ezscm-settings-global", "ezscm_page_settings_global");
	add_submenu_page("ezscm", __("Premium", "ezscm"), __("Premium", "ezscm"), "manage_options", "ezscm-premium", "ezscm_page_premium");
}

function ezscm_page_schedules() {
	if (!current_user_can('manage_options')) {
	    wp_die(__('You do not have sufficient permissions to access this page.', "ezscm"));
	}

	ezscm_load_scripts("backend");
	require_once(plugin_dir_path(__FILE__) . "ezscm-page-schedules.php");
}

function ezscm_page_entries() {
	if (!current_user_can('manage_options')) {
	    wp_die(__('You do not have sufficient permissions to access this page.', "ezscm"));
	}

	ezscm_load_scripts("backend");
	require_once(plugin_dir_path(__FILE__) . "ezscm-page-entries.php");
}

function ezscm_page_settings_schedule() {
	if (!current_user_can('manage_options')) {
	    wp_die(__('You do not have sufficient permissions to access this page.', "ezscm"));
	}

	ezscm_load_scripts("backend-options");
	require_once(plugin_dir_path(__FILE__) . "ezscm-page-settings-schedule.php");
}

function ezscm_page_settings_global() {
	if (!current_user_can('manage_options')) {
	    wp_die(__('You do not have sufficient permissions to access this page.', "ezscm"));
	}

	ezscm_load_scripts("backend-options");
	require_once(plugin_dir_path(__FILE__) . "ezscm-page-settings-global.php");
}

function ezscm_page_premium() {
	if (!current_user_can('manage_options')) {
	    wp_die(__('You do not have sufficient permissions to access this page.', "ezscm"));
	}

	ezscm_load_scripts("backend-options");
	require_once(plugin_dir_path(__FILE__) . "ezscm-page-premium.php");
}


/**
	scripts
**/
function ezscm_load_scripts($end="frontend") {
	wp_enqueue_script("jquery");
	wp_enqueue_script("jquery-scrollto", plugins_url("assets/js/jquery.scrollTo.min.js", __FILE__));

	if ($end == "backend" || $end == "backend-options") {
		wp_enqueue_media();

		// styles
		wp_enqueue_style("thickbox");
		wp_enqueue_style("bootstrap-grid", plugins_url("assets/css/bootstrap-grid.min.css", __FILE__));
		wp_enqueue_style("jquery-ui-smoothness", plugins_url("assets/css/jquery-ui-smoothness.min.css", __FILE__));
		wp_enqueue_style("jquerytimepicker-css", plugins_url("assets/css/jquery.timepicker.css", __FILE__));
		wp_enqueue_style("jquery-opentip", plugins_url("assets/css/opentip.css", __FILE__));
		wp_enqueue_style("ezscm-font-awesome", plugins_url("assets/css/font-awesome.min.css", __FILE__));

		wp_enqueue_style("ezscm-css-backend", plugins_url("style-backend.css", __FILE__), array(), ezscm_get_version());

		// scripts
		wp_enqueue_script("jquery-ui-core");
		wp_enqueue_script("jquery-ui-button");
		wp_enqueue_script("jquery-ui-datepicker");
		wp_enqueue_script("jquery-ui-mouse");
		wp_enqueue_script("jquery-ui-widget");
		wp_enqueue_script("jquery-ui-dialog");
		wp_enqueue_script("jquery-ui-draggable");
		wp_enqueue_script("jquery-ui-droppable");
		wp_enqueue_script("jquery-ui-selectable");
		wp_enqueue_script("jquery-ui-sortable");
		wp_enqueue_script("jquery-ui-spinner");
		wp_enqueue_script("jquery-ui-tabs");
		wp_enqueue_script("momentjs", plugins_url("assets/js/moment.min.js", __FILE__));
		wp_enqueue_script("jquerytimepicker", plugins_url("assets/js/jquery.timepicker.min.js", __FILE__), array("jquery"));
		wp_enqueue_script("jquery-opentip", plugins_url("assets/js/opentip-jquery.min.js", __FILE__), array("jquery"));
		wp_enqueue_script("ezscm-backend", plugins_url("backend.min.js", __FILE__), array("jquery"), ezscm_get_version());

		if ($end == "backend-options") {
			wp_enqueue_script("ezscm-backend-options", plugins_url("backend-options.js", __FILE__), array("jquery"), ezscm_get_version());
		}

		wp_localize_script("ezscm-backend", "ezscm_vars", array(
			"contact" => array(
				"name"    => __("Name", "ezscm"),
				"phone"   => __("Phone number", "ezscm"),
				"email"   => __("Email", "ezscm"),
				"message" => __("Message", "ezscm")
			),
			"entry_delete"    => __("Really delete the selected entry?", "ezscm"),
			"schedule_clear"  => __("Really clear all entries in the selected schedule?", "ezscm"),
			"schedule_delete" => __("Really delete the selected schedule?", "ezscm"),
			"yes_no" => array(
				"no"  => __("No", "ezscm"),
				"yes" => __("Yes", "ezscm")
			)
		));
	}

	if ($end == "frontend") {
		wp_enqueue_style("ezscm-css-frontend");
		wp_add_inline_style("ezscm-css-frontend", get_option("ezscm_custom_css"));

		// momentjs
		//wp_enqueue_script("momentjs", plugins_url("assets/js/moment.min.js", __FILE__));
		// momentjs + locales
		wp_enqueue_script("momentjs", plugins_url("assets/js/moment.locales.min.js", __FILE__));

		wp_enqueue_script("ezscm-frontend", plugins_url("frontend.min.js", __FILE__), array("jquery"), ezscm_get_version());	
		//wp_enqueue_script("ezscm-frontend", plugins_url("frontend.js", __FILE__), array("jquery"), microtime());

		wp_localize_script("ezscm-frontend", "ezscm_vars", array(
			"contact" => array(
				"name"    => __("Name", "ezscm"),
				"phone"   => __("Phone number", "ezscm"),
				"email"   => __("Email", "ezscm"),
				"message" => __("Message", "ezscm")
			),
			"noid"          => __("No schedule with the given ID found."),
			"yes_no" => array(
				"no"  => __("No", "ezscm"),
				"yes" => __("Yes", "ezscm")
			)
		));
	}
}

/**
	ajax
**/
function ezscm_ajax_frontend() {
	require_once(plugin_dir_path(__FILE__) . "ajax.php");
}

// backend
function ezscm_ajax() {
	require_once(plugin_dir_path(__FILE__) . "ajax-admin.php");
}

function ezscm_wp_head() {
	?>
		<script type="text/javascript">
		ezscm_ajaxurl = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
		ezscm_form_vars = [];
		</script>
	<?php
}

/**
	language domain
**/
function ezscm_load_language() {
	load_plugin_textdomain("ezscm", false, dirname(plugin_basename(__FILE__)) . '/languages/');
}


/**
	shortcode
**/
class Ezscm_Shortcode {
	static $add_script;

	static function init() {
		add_shortcode('ezscm', array(__CLASS__, 'handle_shortcode'));

		add_action('init', array(__CLASS__, 'register_script'));
		add_action('wp_footer', array(__CLASS__, 'print_script'));
	}

	static function handle_shortcode($atts) {
		self::$add_script = true;

		extract(shortcode_atts(array(
			"id"   => null,
			"name" => null
		), $atts));

		$id = (int) $id;

		require_once(plugin_dir_path(__FILE__) . "class.ezscm_frontend.php");
		$ezscm = new Ezscm_frontend();

		return $ezscm->get_output($id, $name);
	}

	static function register_script() {
		wp_register_style('ezscm-css-frontend', plugins_url("style-frontend.css", __FILE__));
	}

	static function print_script() {
		if ( ! self::$add_script )
			return;

		ezscm_load_scripts("frontend");
	}
}
Ezscm_Shortcode::init();