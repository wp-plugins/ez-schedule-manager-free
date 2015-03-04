<?php

defined( 'ABSPATH' ) OR exit;
if (!current_user_can('activate_plugins')) return;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
global $wpdb;

$query = file_get_contents(dirname(__FILE__) . "/db.sql");
if (!$query) {
	die("Error opening file.");
}

require_once(plugin_dir_path(__FILE__) . "class.ezscm_backend.php");
$ezscm_backend = new Ezscm_backend();
$ezscm_backend->setup_db();

$current_version = ezscm_get_version();