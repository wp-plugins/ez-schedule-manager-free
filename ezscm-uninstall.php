<?php

defined( 'ABSPATH' ) OR exit;
if (!current_user_can('activate_plugins')) return;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// do not delete data
if (get_option("ezscm_uninstall_keep_data") == 1) return;

global $wpdb;
$query = array();

$tables = array(
	"ezscmf_debug",
	"ezscmf_entries",
	"ezscmf_settings",
	"ezscmf_settings_schedule",
	"ezscmf_schedules"
);

$options = array(
	"captcha_private",
	"captcha_public",
	"custom_css",
	"debug_mode",
	"required_text",
	"uninstall_keep_data",
	"version"
);

foreach ($tables as $t) {
	$wpdb->query("DROP TABLE `{$wpdb->prefix}{$t}`");
}

foreach ($options as $o) {
	delete_option("ezscm_{$o}");
}