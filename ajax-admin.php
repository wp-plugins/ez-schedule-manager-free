<?php

if (!function_exists("get_option")) die("Access denied.");

require_once(plugin_dir_path(__FILE__) . "class.ezscm_backend.php");
$ezscm = new Ezscm_backend();

parse_str($_REQUEST["data"], $data);
$action = $data["action"];
$id 	= isset($data["id"]) ? $data["id"] : null;

// verify nonce
if (!wp_verify_nonce($data["nonce"], "ezscm-nonce")) {
	send_ajax(array("error" => "Could not verify security nonce. Please refresh this page."));
	die();
}

switch ($action) {
	// add schedule
	case "add":
		send_ajax($ezscm->schedule_add());
	break;

	// delete entry
	case "entry_delete":
		send_ajax($ezscm->entry_delete($id));
	break;

	// get single entry
	case "get_entry":
		send_ajax($ezscm->get_entry($id));
	break;

	// get schedule entries
	case "get_entries":
		send_ajax($ezscm->get_entries($id));
	break;

	// get schedule entries and options
	case "get_schedule":
		$ret = array(
			"entries" => $ezscm->get_entries($id, $data["week"]),
			"options" => $ezscm->get_schedule_settings($id)
		);

		send_ajax($ret);
	break;

	// rename schedule
	case "rename":
		send_ajax($ezscm->schedule_rename($id, $data["name"]));
	break;

	// save entry
	case "save_entry":
		$res = $ezscm->save_entry($data);

		// failed to insert entry
		if (isset($res["error"])) {
			send_ajax($res);

			die();
		}

		// return saved entry
		send_ajax($ezscm->get_entry($res["id"]));
	break;

	// clear schedule
	case "schedule_clear":
		send_ajax($ezscm->schedule_clear($id));
	break;

	// delete schedule
	case "schedule_del":
		send_ajax($ezscm->schedule_del($id));
	break;

	// update options
	case "update_options":
		$ezscm->update_settings_schedule($data["opt"], $id);

		$ret = array(
			"entries" => $ezscm->get_entries($id),
			"options" => $ezscm->get_schedule_settings($id)
		);

		send_ajax($ret);
	break;
}

die();


function send_ajax($msg) {
	//error_log(var_export($msg, true));
	if (is_object($msg) && property_exists($msg, "data")) $msg->data = json_decode($msg->data);
	$ret = json_encode($msg);
	//error_log(var_export($ret, true));

	echo $ret;
}

function isJson($string) {
	json_decode($string);
	return (json_last_error() == JSON_ERROR_NONE);
}

?>