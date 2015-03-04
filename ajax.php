<?php

if (!function_exists("get_option")) die("Access denied.");

require_once(plugin_dir_path(__FILE__) . "class.ezscm_frontend.php");
$ezscm_frontend = new Ezscm_frontend();

parse_str($_POST["data"], $data);

$action = $data["action"];

switch ($action) {
	case "submit":
		$res = $ezscm_frontend->save_entry($data["data"]);
		echo $res;
	break;

	case "get_public_schedule":
		$id   = (int) $data["id"];
		$week = isset($data["week"]) ? $data["week"] : -1;

		echo json_encode(array(
			"entries" 	=> $ezscm_frontend->get_entries($id, $week),
			"options"	=> $ezscm_frontend->get_schedule_settings($id)
		));
	break;
}

die();

function send_message($type, $msg, $id=0) {
	return json_encode(array(
		$type 	=> $msg,
		"id"	=> $id
	));
}

?>