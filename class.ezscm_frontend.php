<?php

class Ezscm_frontend {
	function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->tables = array(
			"debug"             => "{$this->wpdb->prefix}ezscmf_debug",
			"entries"			=> "{$this->wpdb->prefix}ezscmf_entries",
			"schedules"			=> "{$this->wpdb->prefix}ezscmf_schedules",
			"settings"			=> "{$this->wpdb->prefix}ezscmf_settings",
			"settings_schedule"	=> "{$this->wpdb->prefix}ezscmf_settings_schedule"
		);
	}

	function debug($msg) {
		if (get_option("ezscm_debug_mode", 0) == 0) return;

		$this->wpdb->insert(
			$this->tables["debug"],
			array("msg" => $msg),
			array("%s")
		);
	}

	function get_entries($id, $week=-1) {
		$id         = (int) $id;
		$options    = $this->get_schedule_settings($id, false);

		$isoweek    = strtotime(date("Y")."W".date("W"));
		$week       = $week==-1 ? date("Y-m-d", $isoweek) : $week;
		$date_start = $week;

		$date_end_days = ((int) $options["show_weeks_amount_backend"]->value) * 7;
		$date_end      = date("Y-m-d", strtotime("$week +{$date_end_days} days"));

		$sql_range  = "AND date BETWEEN %s AND %s";
		$sql_params = array(
			$id,
			$date_start,
			$date_end
		);

		// add content field to schedule
		if (!empty($options["entry_content_frontend"]->value)) {
			$res = $this->wpdb->get_results($this->wpdb->prepare(
				"SELECT time_begin, date, data FROM {$this->tables["entries"]} WHERE s_id=%d {$sql_range}",
				$sql_params
			));

			if (!empty($options["entry_content_frontend"]->value) && count($res) > 0) {
				$content_field = $options["entry_content_frontend"]->value;

				foreach ($res as &$entry) {
					$entry_data = json_decode($entry->data);

					// just overwrite the data field if
					if (property_exists($entry_data, $content_field)) {
						$entry->data = $entry_data->$content_field;
					}
				}
			}
		}
		// schedule only
		else {
			$res = $this->wpdb->get_results($this->wpdb->prepare(
				"SELECT time_begin, date FROM {$this->tables["entries"]} WHERE s_id=%d {$sql_range}",
				$sql_params
			));
		}
		
		return $res;
	}

	function get_schedule($id, $name=null) {
		$s = $this->wpdb->get_var("SELECT count(*) as count FROM {$this->tables["schedules"]}");
		if ($s > 1) return $this->send_message("error", __("Only 1 schedule allowed in the free version.", "ezscm"));

		if (!$id && !$name) return $this->send_message("error", __("No id or name found.", "ezscm"));

		if ($id) {
			$res = $this->wpdb->get_row($this->wpdb->prepare(
				"SELECT * FROM {$this->tables["schedules"]} WHERE id=%d",
				$id
			));
		}

		if ($name) {
			$res = $this->wpdb->get_row($this->wpdb->prepare(
				"SELECT * FROM {$this->tables["schedules"]} WHERE name=%s",
				$name
			));
		}

		return $res;
	}


	function get_schedule_settings($s_id, $remove_options = true) {
		$s_id = (int) $s_id;
		$sql_add = "";

		// remove some options due to security purposes
		if ($remove_options) {
			$exclude_options_array = explode(",", "email_recipient,spam_time,email_admin_subject,email_admin_text,email_subject,email_text,entry_content_backend,show_weeks_amount_backend");
			$exclude_options       = "'" . implode("', '", $exclude_options_array) . "'";
			
			$sql_add = " AND s.name NOT IN ({$exclude_options})";
		}

		$query = "
			SELECT s.name, o.o_id, s.id as o_id, o.s_id, o.value, s.type
			FROM {$this->tables["settings_schedule"]} AS o
			JOIN {$this->tables["settings"]} AS s ON o.o_id=s.id
			WHERE s_id={$s_id} {$sql_add}";

		$res = $this->wpdb->get_results($query, OBJECT_K);

		return $res;
	}


	/**
		save public entry
	**/
	function save_entry($save_data) {
		$options = $this->get_schedule_settings($save_data["s_id"], false);

		if ($options["display_only"]->value == 1) {
			return $this->send_message("error", __("You are not allowed to submit anything.", "ezscm"));
		}

		// look for existing entry
		$existing = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT 1 FROM {$this->tables["entries"]} WHERE time_begin='%s' AND date='%s'",
			$save_data["time_internal"], $save_data["date_internal"]
		));

		if ($existing) {
			return $this->send_message("error", __("An entry for the requested time already exists. Someone was probably quicker than you.", "ezscm"));
		}

		// spam protection
		$spam_time = $options["spam_time"]->value;

		$spam = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT 1 FROM {$this->tables["entries"]} WHERE ip='%s' AND date_submitted>=DATE_ADD(NOW(), INTERVAL -{$spam_time} SECOND)",
			$_SERVER["REMOTE_ADDR"]
		));

		if ($spam) {
			return $this->send_message("error", __("Spam protection: you need to wait {$spam_time} seconds before you can add another entry.", "ezscm"));
		}

		// validate form fields
		$elements      = $this->get_element_fields($options["form_elements"]->value);
		$elements_name = $this->get_element_fields($options["form_elements"]->value, true);

		$error     = "";
		$user_mail = "";
		$valid     = true;

		if (count($elements > 0)) {
			foreach ($elements as $i => $element) {
				$element_name = $elements_name[$i];

				// invalid form data
				if (!isset($save_data["form"][$element_name])) {
					$valid = false;
					return $this->send_message("error", __("Invalid form data. Please reload the page.", "ezscm"));
				}

				$element_value = $save_data["form"][$element_name];

				// required field
				if (strstr($element, "[required]") && empty($element_value)) {
					$valid = false;
					$error = __("Please fill out all required fields.", "ezscm");
				}

				// email
				if (strstr($element, "[email]")) {
					if (!filter_var($element_value, FILTER_VALIDATE_EMAIL)) {
						$valid = false;
						$error = __("Please enter a valid email address.", "ezscm");
					}
					// found user mail
					else {
						$user_mail = $element_value;
					}
				}
			}
		}

		// invalid form
		if (!$valid) {
			return $this->send_message("error", $error);
		}

		$sql_data = json_encode($save_data["form"]);

		$res = $this->wpdb->insert(
			$this->tables["entries"],
			array(
				"s_id"       => $save_data["s_id"],
				"date"       => $save_data["date_internal"],
				"private"    => 0,
				"time_begin" => $save_data["time_internal"],
				"data"		 => $sql_data,
				"ip"         => $_SERVER["REMOTE_ADDR"]
			),
			array(
				"%d",
				"%s",
				"%d",
				"%s",
				"%s",
				"%s"
			)
		);

		if ($res === false) {
			return $this->send_message("error", __("An error has occured.", "ezscm"));
		}

		$this->send_mails($save_data, $options, $user_mail);

		return $this->send_message("success", __($options["submit_message"]->value, "ezscm"), $this->wpdb->insert_id);
	}

	/**
		send mails (obviously)
	**/
	function send_mails($save_data, $options, $user_mail="") {
		$this->debug("Preparing to send mail(s)...");
		$this->debug("Target email: $user_mail");

		// generate email content from submission
		$mail_output = $this->get_mail_output($save_data, $options);

		// sender name
		$mail_from = !empty($options["email_admin_sender"]->value) ? $options["email_admin_sender"]->value : get_bloginfo("name");

		// admin mail
		if (!empty($options["email_recipient"]->value)) {
			$mail_admin_headers   = array();
			$mail_admin_headers[] = "Content-type: text/html";
			$mail_admin_headers[] = "From: {$mail_from}";

			if (!empty($user_mail)) {
				$mail_admin_headers[] = "Reply-to: \"{$user_mail}\"";
			}

			$res = wp_mail(
				$options["email_recipient"]->value,
				__($options["email_admin_subject"]->value, "ezscm"),
				$mail_output["text_admin"],
				$mail_admin_headers
			);

			$this->debug("Email delivery to admin: $res");
			$this->debug(var_export($mail_admin_headers, true));
		}
		else {
			$this->debug("No admin email recipient found.");
		}

		// user mail
		if (!empty($user_mail)) {
			$mail_subject = $options["email_subject"]->value;

			// headers
			$mail_headers   = array();
			$mail_headers[] = "Content-type: text/html";
			$mail_headers[] = "From: {$mail_from}";

			$res = wp_mail(
				$user_mail,
				__($mail_subject, "ezscm"),
				$mail_output["text_user"],
				$mail_headers
			);

			$this->debug("Email delivery to user: $res");
			$this->debug(var_export($mail_headers, true));
		}
		else {
			$this->debug("No user email found.");
		}
	}

	/**
		get email output
	**/
	function get_mail_output($save_data, $options) {
		error_log(var_export($save_data, true));
		$out = array();

		// output prefix
		$out_pre = "
		<html>
		<head>
			<meta charset='utf-8' />
			<style type='text/css'>
			table { width: 100%; max-width: 800px; border-collapse: collapse; }
			tr, td { padding: 10px 5px; vertical-align: top; }
			</style>
		</head>
		<body>";

		// output suffix
		$out_suf = "
		</body>
		</html>";

		// result output
		$out[] = "<table>";

		// fields
		$form_elements = $this->get_element_fields($options["form_elements"]->value, true);
		$out = array();
		foreach ($form_elements as $i => $label) {
			$out[] = __($label, "ezscm") . ": {$save_data["form"][$label]}";
		}

		$out[] = "</table>";

		// implode content
		$result_content = nl2br(implode("\n", $out));

		// put email text into vars
		$mail_content_replace       = $options["email_text"]->value;
		$mail_admin_content_replace = $options["email_admin_text"]->value;

		// replace other values
		$replaces = array(
			"result" => $result_content
		);

		foreach ($replaces as $replace => $replace_value) {
			$mail_content_replace       = str_ireplace("{{" . $replace . "}}", $replace_value, $mail_content_replace);
			$mail_admin_content_replace = str_ireplace("{{" . $replace . "}}", $replace_value, $mail_admin_content_replace);
		}

		// put together email contents for user
		$mail_content  = $out_pre;
		$mail_content .= $mail_content_replace;
		$mail_content .= $out_suf;

		// put together email contents for admin
		$mail_admin_content  = $out_pre;
		$mail_admin_content .= $mail_admin_content_replace;
		$mail_admin_content .= $out_suf;

		return array(
			"result"     => $result_content,
			"text_user"  => $mail_content,
			"text_admin" => $mail_admin_content
		);
	}


	/**
		output
	**/
	function get_output($id, $name=null) {
		if (!$id && !$name) return __("No id or name found. Correct syntax: [ezscm id='1' /] or [ezscm name='form-name' /]");

		if ($id) {
			$options = $this->get_schedule_settings($id);

			if (!$options) return __("No schedule found (ID: {$id}).", "ezscm");
		}

		if ($name) {
			$schedule = $this->get_schedule(null, $name);

			if (!$schedule) return __("No schedule found (Name: {$name}).", "ezscm");
			
			$id = $schedule->s_id;
		}

		$options       = $this->get_schedule_settings($id);
		$form_elements = $this->get_element_fields($options["form_elements"]->value);

		// html output
		$html  = "<div class='ezscm ezscm-schedule-{$id}' id='ezscm-schedule-{$id}' data-id='{$id}'>";
		// loading
		$html .= "	<div class='ezscm-loading'>" . __('Loading', 'ezscm') . "</div>";
		// container
		$html .= "	<div class='ezscm-container'>";
		$html .= "		<div class='ezscm-box'>";
		
		// browse weeks
		if ($options['browse_weeks']->value == 1) {
			$week_prev = date('Y-m-d', strtotime('last Monday'));
			$week_next = date('Y-m-d', strtotime('next Monday'));

			$html .= "
				<div class='ezscm-browse'>
					<div class='alignleft inline'>
						<button class='btn alignleft ezscm-browse-prev' data-action='get_schedule' data-browseweek='{$week_prev}'>&lt; " . __('Previous week', 'ezscm') . "</button>
					</div>
					<div class='alignright inline'>
						<button class='btn alignright ezscm-browse-next' data-action='get_schedule' data-browseweek='{$week_next}'>" . __('Next week', 'ezscm') . " &gt;</button>
					</div>
				</div>

				<div class='clear'></div>
			";
		}

		// schedule
		$html .= "<div class='ezscm-schedule-wrapper'></div>";

		// message
		$html .= "	<div class='ezscm-message'></div>";

		// form
		$html .= "<div class='ezscm-details'>";
		$html .= "	<form class='ezscm-form' name='ezscm-form[{$id}]' action='' data-id='ezscm-form[{$id}]'>";
		$html .= "		<input class='s_id' name='data[s_id]' type='hidden' value='{$id}' />";
		$html .= "		<input class='date_internal' name='data[date_internal]' type='hidden' />";
		$html .= "		<input class='time_internal' name='data[time_internal]' type='hidden' />";

		$el_pre = "<div class='ezscm-element'>";
		$el_suf = "</div>";

		// callback date
		$html .= "<div class='ezscm-details-element ezscm-details-element-date'>
			<label>" . __('Date', 'ezscm') . "</label><input type='text' name='data[form][callbackdate]' class='callbackdate' disabled='disabled' value='' />
		</div>";
		// callback hour
		$html .= "<div class='ezscm-details-element ezscm-details-element-time'>
			<label>" . __('Time', 'ezscm') . "</label><input type='text' name='data[form][callbackhour]' class='callbackhour' disabled='disabled' value='' />
		</div>";

		foreach ($form_elements as $i => $element) {
			$required = "";
			$required_char = "";
			// required field
			if (strstr($element, "[required]")) {
				$required = "required";
				$required_char = " *";
				$element = str_replace("[required]", "", $element);
			}

			// input field
			$el_text = "<input class='ezscm-element-input' type='text' name='data[form][{$element}]' id='ezscm-form-{$id}-{$element}' {$required} />";

			// email field
			if (strstr($element, "[email]")) {
				$element = str_replace("[email]", "", $element);

				$el_text = "<input class='ezscm-element-email ezscm-element-email' type='email' name='data[form][{$element}]' id='ezscm-form-{$id}-{$element}' {$required} />";
			}
			// textarea
			elseif (strstr($element, "[textarea]")) {
				$element = str_replace("[textarea]", "", $element);

				$el_text = "<textarea class='ezscm-element-textarea' type='text' name='data[form][{$element}]' id='ezscm-form-{$id}-{$element}' {$required}></textarea>";
			}

			$el_label = "<label class='ezscm-label' for='ezscm-form-{$id}-{$element}'>{$element}{$required_char}</label>";

			$html .= $el_pre.$el_label.$el_text.$el_suf;
		}

		// recaptcha
		$html .= "<div class='ezscm-details-submit'>";
		$html .= "    <label>&nbsp;</label>";

		if (!empty($privatekey) && !empty($publickey)) {
			$html .= "<h3>" . __('Confirmation', 'ezscm') . "</h3>";
			echo recaptcha_get_html($publickey);
		}

		// submit
		$html .= "    <input class='ezscm-submit {$options["submit_button_css"]->value}' type='submit' value='" . __($options["submit_button_text"]->value, 'ezscm') . "' />";
		$html .= "</div>";

		// required char
		$html .= "		<div class='ezscm-required-notification'><span class='ezscm-required-char'>*</span> " . __(get_option("ezscm_required_text", "Required"), "ezscm") . "</div>";

		$html .= "	</form>";
		$html .= "</div>";

		// success text
		if (isset($options["success_text"])) {
			$html .= "<div class='ezscm-success-text'>{$options["success_text"]->value}</div>";
		}

		$html .= "</div></div></div>";

		// js output
		$form_options_js = json_encode(array(
			"date_format"  => $options["date_format"]->value,
			"debug_mode"   => get_option("ezscm_debug_mode", 0),
			"redirect_url" => trim($options["redirect_url"]->value),
			"time_format"  => $options["time_format"]->value
		));

		$html .= "<script>ezscm_form_vars[{$id}] = {$form_options_js};</script>";

		return $html;
	}

	public function get_element_fields($text, $replace = false) {
		$return_array = explode(",", str_replace(array("\r", "\n"), "", $text));

		if ($replace) {
			// removes all brackets, e.g.
			// value: Text[email][required]
			// result: Text
			$regex = "/\[.+?\]/i";

			foreach ($return_array as &$v) {
				$v = preg_replace($regex, "", $v);
			}
		}

		return $return_array;
	}

	function send_message($type, $msg, $id=0) {
		return json_encode(array(
			$type 	=> $msg,
			"id"	=> $id
		));
	}
}