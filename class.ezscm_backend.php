<?php

class Ezscm_backend {
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

	/**
		debug
	**/
	function get_debug_log() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["debug"]}");

		if (count($res) < 1) return "No debug logs found.";

		$logs = array();
		foreach ($res as $log) {
			$logs[] = "{$log->time}: {$log->msg}";
		}

		return implode("\n", $logs);
	}

	function clear_debug_log() {
		$this->wpdb->query("TRUNCATE TABLE `{$this->tables["debug"]}`");
	}

	function debug($msg) {
		if (get_option("ezscm_debug_mode", 0) == 0) return;

		$this->wpdb->insert(
			$this->tables["debug"],
			array("msg" => $msg),
			array("%s")
		);
	}


	function entry_delete($id) {
		$id = (int) $id;

		$res = $this->wpdb->delete(
			$this->tables["entries"],
			array(
				"e_id" => $id,
			),
			array(
				"%d",
			)
		);

		return $this->send_message("success", __("Entry deleted.", "ezscm"));
	}


	function get_entry($id) {
		$id = (int) $id;

		$res = $this->wpdb->get_row("SELECT * FROM {$this->tables["entries"]} WHERE e_id={$id}");

		return $res;
	}

	function get_entries($id=-1, $week=null, $limit=-1) {
		$sql_params = array();

		$id = (int) $id;
		$sql_id = "";
		if ($id != -1) {
			$sql_id = "AND s_id={$id}"; 
		}

		$sql_limit = "";
		if ($limit != -1) {
			$limit = (int) $limit;
			$sql_limit = "LIMIT {$limit}";
		}

		if ($week) {
			$options = $this->get_schedule_settings($id);
			
			$week_start = $this->last_monday($week);
			//$week_end   = date("Y-m-d", strtotime("$week next Sunday"));

			$date_end_days = ((int) $options["show_weeks_amount_backend"]->value) * 7;
			$week_end      = date("Y-m-d", strtotime("$week +{$date_end_days} days"));

			$res = $this->wpdb->get_results($this->wpdb->prepare(
				"SELECT * FROM {$this->tables["entries"]} WHERE 1=1 {$sql_id} AND `date` BETWEEN %s AND %s ORDER BY e_id DESC {$sql_limit}",
				$week_start, $week_end
			));
		}
		else {
			$res = $this->wpdb->get_results(
				"SELECT * FROM {$this->tables["entries"]} WHERE 1=1 {$sql_id} ORDER BY e_id DESC {$sql_limit}"
			);
		}
		
		
		return $res;
	}

	function get_schedules() {
		$s = $this->wpdb->get_var("SELECT count(*) as count FROM {$this->tables["schedules"]}");
		if ($s > 1) return $this->send_message("error", __("Only 1 schedule allowed in the free version.", "ezscm"));

		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["schedules"]}");

		return $res;
	}

	function get_schedule_settings($s_id) {
		$s_id = (int) $s_id;
		$query = "
			SELECT s.name, o.o_id, s.id as o_id, o.s_id, o.value, s.description, s.description_long, s.type
			FROM {$this->tables["settings_schedule"]} AS o
			JOIN {$this->tables["settings"]} AS s ON o.o_id=s.id
			WHERE s_id={$s_id}";

		$res = $this->wpdb->get_results($query, OBJECT_K);
		return $res;
	}

	function get_settings($order="id asc") {
		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["settings"]} ORDER BY %s",
			$order
		));
		
		return $res;
	}

	function schedule_rename($id, $name) {
		$res = $this->wpdb->update(
			$this->tables["schedules"],
			array("name" => $name),
			array("s_id" => $id),
			array("%s"),
			array("%d")
		);

		if ($res === false) {
			return $this->send_message("error", $this->wpdb->last_error);
		}

		return $name;
	}


	function save_entry($save_data) {
		$data_details = json_encode($save_data["data"]);

		$sqldata = array(
			"s_id"       => $save_data["details-s_id"],
			"data"       => $data_details,
			"date"       => $save_data["details-date_internal"],
			"private"    => isset($save_data["details-private"]) ? $save_data["details-private"] : 0,
			"time_begin" => $save_data["details-time_internal"],
			"ip"         => $_SERVER["REMOTE_ADDR"]
		);
		$sqlfields = array("%d", "%s", "%s", "%d", "%s", "%s");

		// add entry
		if ($save_data["ezscm-edit_id"] == -1) {
			$res = $this->wpdb->insert(
				$this->tables["entries"],
				$sqldata,
				$sqlfields
			);
		}
		// update entry
		else {
			$res = $this->wpdb->update(
				$this->tables["entries"],
				$sqldata,
				array("e_id" => $save_data["ezscm-edit_id"]),
				$sqlfields,
				array("%d")
			);
		}

		if ($res === false) {
			return $this->send_message("error", $this->wpdb->last_error);
		}

		// return last inserted id or edited id
		$ret_id = $save_data["ezscm-edit_id"]==-1 ? $this->wpdb->insert_id : $save_data["ezscm-edit_id"];

		return $this->send_message("success", __("Entry updated", "ezscm"), $ret_id);
	}


	function schedule_add() {
		$s = $this->wpdb->get_var("SELECT count(*) as count FROM {$this->tables["schedules"]}");
		if ($s >= 1) return $this->send_message("error", __("Only 1 schedule allowed in the free version.", "ezscm"));

		$res = $this->wpdb->insert(
			$this->tables["schedules"],
			array("name" => "New Schedule"),
			array("%s")
		);

		if ($res === false) {
			return $this->send_message("error", $this->wpdb->last_error);
		}

		$ins_id = $this->wpdb->insert_id;

		$ins = $this->wpdb->query("
			INSERT INTO {$this->tables["settings_schedule"]}
				(s_id, o_id, value)
			SELECT
				{$ins_id}, id, value
			FROM
				{$this->tables["settings"]}
		");

		if ($ins === false) {
			return $this->send_message("error", $this->wpdb->last_error);
		}

		return $this->send_message("success", __("Schedule added", "ezscm"), $ins_id);
	}

	function schedule_clear($id) {
		$res = $this->wpdb->delete(
			$this->tables["entries"],
			array("s_id" => $id),
			array("%d")
		);

		if ($res === false) {
			return $this->send_message("error", $this->wpdb->last_error);
		}

		return $this->send_message("success", __("Schedule cleared", "ezscm"));
	}

	function schedule_del($id) {
		$res = $this->wpdb->delete(
			$this->tables["schedules"],
			array("s_id" => $id),
			array("%d")
		);

		if ($res === false) {
			return $this->send_message("error", $this->wpdb->last_error);
		}

		$res_del = $this->wpdb->delete(
			$this->tables["settings_schedule"],
			array("s_id" => $id),
			array("%d")
		);

		if ($res_del === false) {
			return $this->send_message("error", $this->wpdb->last_error);
		}

		$res_entries = $this->wpdb->delete(
			$this->tables["entries"],
			array("s_id" => $id),
			array("%d")
		);

		if ($res_entries === false) {
			return $this->send_message("error", $this->wpdb->last_error);
		}

		return $this->send_message("success", __("Schedule deleted", "ezscm"));
	}

	function update_settings($settings, $overwrite = 0) {
		foreach ($settings as $id => $value) {
			// option has multiple fields
			if (is_array($value)) {
				$tmpvalue = array();

				// remove 'clone'-items - they are required because the option would have no value otherwise
				foreach ($value as $value_array_key => $value_array_value) {
					if ($value_array_key == -1) continue;
					$tmpvalue[] = $value_array_value;
				}

				$value = json_encode($tmpvalue);
			}
			else {
				$value = stripslashes($value);
			}

			$res = $this->wpdb->update(
				$this->tables["settings"],
				array("value" => $value),
				array("id" => $id),
				array("%s"),
				array("%d")
			);

			if ($res === false) {
				return $this->send_message("error", $this->wpdb->last_error);
			}

			// overwrite settings
			if ($overwrite == 1) {
				$res = $this->wpdb->update(
					$this->tables["settings_schedule"],
					array("value" => $value),
					array("o_id" => $id),
					array("%s"),
					array("%d")
				);
			}

			if ($res === false) {
				return $this->send_message("error", $this->wpdb->last_error);
			}
		}

		return $this->send_message("success", __("Settings updated", "ezscm"));
	}

	function update_settings_schedule($settings, $s_id) {
		if (count($settings) < 1) return $this->send_message("success", __("Settings updated", "ezscm"));

		foreach ($settings as $o_id => $value) {
			// option has multiple fields
			if (is_array($value)) {
				$tmpvalue = array();

				// remove 'clone'-items - they are required because the option would have no value otherwise
				foreach ($value as $value_array_key => $value_array_value) {
					if ($value_array_key == -1) continue;
					$tmpvalue[] = $value_array_value;
				}

				$value = json_encode($tmpvalue);
			}
			else {
				$value = stripslashes($value);
			}

			$res = $this->wpdb->replace(
				$this->tables["settings_schedule"],
				array(
					"o_id"  => $o_id,
					"s_id"  => $s_id,
					"value" => $value
				),
				array(
					"%d",
					"%d",
					"%s"
				)
			);

			if ($res === false) {
				return $this->send_message("error", $this->wpdb->last_error);
			}
		}

		return $this->send_message("success", __("Settings updated", "ezscm"));
	}

	// settings / options output
	public function get_settings_table($settings, $options_id="opt", $options_name) {
		$out = array();

		$langs = array('ar'=>'Arabic','ar-ma'=>'Moroccan Arabic','bs'=>'Bosnian','bg'=>'Bulgarian','br'=>'Breton','ca'=>'Catalan','cy'=>'Welsh','cs'=>'Czech','cv'=>'Chuvash','da'=>'Danish','de'=>'German','el'=>'Greek','en'=>'English','en-au'=>'English (Australia)','en-ca'=>'English (Canada)','en-gb'=>'English (England)','eo'=>'Esperanto','es'=>'Spanish','et'=>'Estonian','eu'=>'Basque','fa'=>'Persian','fi'=>'Finnish','fo'=>'Farose','fr-ca'=>'French (Canada)','fr'=>'French','gl'=>'Galician','he'=>'Hebrew','hi'=>'Hindi','hr'=>'Croatian','hu'=>'Hungarian','hy-am'=>'Armenian','id'=>'Bahasa Indonesia','is'=>'Icelandic','it'=>'Italian','ja'=>'Japanese','ka'=>'Georgian','ko'=>'Korean','lv'=>'Latvian','lt'=>'Lithuanian','ml'=>'Malayalam','mr'=>'Marathi','ms-my'=>'Bahasa Malaysian','nb'=>'Norwegian','ne'=>'Nepalese','nl'=>'Dutch','nn'=>'Norwegian Nynorsk','pl'=>'Polish','pt-br'=>'Portuguese (Brazil)','pt'=>'Portuguese','ro'=>'Romanian','ru'=>'Russian','sk'=>'Slovak','sl'=>'Slovenian','sq'=>'Albanian','sv'=>'Swedish','th'=>'Thai','tl-ph'=>'Tagalog (Filipino)','tr'=>'Turkish','tzm-la'=>'Tamaziɣt','uk'=>'Ukrainian','uz'=>'Uzbek','zh-cn'=>'Chinese','zh-tw'=>'Chinese (Traditional)');

		$out[] = "<table class='form-table'>";
		$out[] = "	<tr>";

		$table_out = array();
		foreach ($settings as $i => $s) {
			$is_hidden = false;
			$tmp_id = property_exists($s, "id")    ? $s->id : $i;

			$tmp_value = "";
			if (property_exists($s, "value")) {
				$tmp_value = $s->value;
			}
			else {
				if (property_exists($s, "default")) {
					$tmp_value = get_option("ezscm_{$i}", $s->default);
				}
				else {
					$tmp_value = get_option("ezscm_{$i}");
				}
			}

			$element_id = "{$options_id}-{$tmp_id}";
	    	$add_class  = empty($s->type) ? "" : "ezscm-settings-type-{$s->type}";
	    	$tmp_input  = "";

	    	if (!empty($s->type)) {
	    		$type_array = explode(",", $s->type);

	    		switch ($type_array[0]) {
	    			case "date_formats":
	    				$options = array(
	    					"mm/dd/yy" => date("m/d/Y"),
	    					"dd/mm/yy" => date("d/m/Y"),
	    					"dd.mm.yy" => date("d.m.Y")
	    				);

	    				$tmp_input  = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
	    				foreach ($options as $v => $desc) {
	    					$selected = "";
	    					if ($tmp_value == $v) $selected = "selected='selected'";

	    					$tmp_input .= "<option value='{$v}' {$selected}>" . $desc . "</option>";
	    				}

	    				$tmp_input .= "</select>";
					break;

					case "datepicker_array":
	    				$closed_dates_json = json_decode($tmp_value);

	    				$tmp_input  = "<div id='{$element_id}' class='container-fluid option-wrapper datepicker-range-wrapper' data-option_name='{$options_name}' data-option_id='{$tmp_id}' data-inputnames='from,to'>";
	    				// add business hours button
	    				$tmp_input .= "		<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12 option-controls'>";
	    				$tmp_input .= "			<li class='button option-add'><i class='fa fa-fw fa-plus'></i> " . __("Add closed days", "ezb") . "</li>";
	    				$tmp_input .= "		</div>";

	    				// clone element
	    				$tmp_input .= "		<div class='ezscm-hidden option-clone option-item' data-row='0'>";

	    				// day
	    				$tmp_input .= "			<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>";
	    				$tmp_input .= "				" . __("From" , "ezscm") . " <input class='datepicker-range datepicker-from' type='text' name='{$options_name}[{$tmp_id}][-1][from]' value='' />";
		    			$tmp_input .= "				" . __("To" , "ezscm") . " <input class='datepicker-range datepicker-to' type='text' name='{$options_name}[{$tmp_id}][-1][to]' value='' />";
		    			$tmp_input .= "				<button class='button option-remove' data-ot='" . __("Remove item", "ezscm") . "'><i class='fa fa-fw fa-times'></i></button>";
	    				$tmp_input .= "			</div>";

	    				// clone end
	    				$tmp_input .= "		</div>";

	    				if (count($closed_dates_json) > 0) {
		    				foreach ($closed_dates_json as $d => $closed_date) {
		    					if (!property_exists($closed_date, "from")) {
			    					$closed_date = json_encode(array(
			    						"from" => "",
			    						"to"   => ""
			    					));
			    				}

			    				if (empty($closed_date->from) && empty($closed_date->to)) continue;

			    				$tmp_input .= "<div class='option-item' data-row='{$d}'>";
			    				$tmp_input .= "		<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>";
			    				$tmp_input .= "			" . __("From" , "ezscm") . " <input class='datepicker-range datepicker-from' type='text' name='{$options_name}[{$tmp_id}][{$d}][from]' value='{$closed_date->from}' />";
			    				$tmp_input .= "			" . __("To" , "ezscm") . " <input class='datepicker-range datepicker-to' type='text' name='{$options_name}[{$tmp_id}][{$d}][to]' value='{$closed_date->to}' />";
			    				$tmp_input .= "				<button class='button option-remove' data-ot='" . __("Remove item", "ezscm") . "'><i class='fa fa-fw fa-times'></i></button>";
			    				$tmp_input .= "		</div>";
			    				$tmp_input .= "</div>";
			    			}
			    		}

		    			$tmp_input .= "</div>";
	    			break;

					case "editor":
	    				ob_start();

	    				wp_editor($tmp_value, "editor_{$tmp_id}", array(
	    					"textarea_name" => "{$options_name}[{$tmp_id}]",
	    					"textarea_rows" => 5,
	    					"teeny"         => true
	    				));
	    				$tmp_input = ob_get_contents();

	    				ob_end_clean();
	    			break;

					case "image":
	    				$tmp_input  = "<div class='ezscm-image-upload-wrapper'>";
	    				$tmp_input .= "		<input class='ezscm-image-upload-hidden' type='hidden' name='{$options_name}[{$tmp_id}]' value='{$tmp_value}' />";
	    				$tmp_input .= "		<button class='button ezscm-image-upload'>" . __("Choose image", "ezscm") . "</button>";
						$tmp_input .= "		<br><img src='{$tmp_value}' class='ezscm-image-preview' />";
						$tmp_input .= "</div>";
					break;

					case "lang":
	    				$tmp_input  = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
	    				foreach ($langs as $lang=>$langdesc) {
	    					$selected = "";
	    					if ($tmp_value == $lang) $selected = "selected='selected'";

	    					$tmp_input .= "<option value='{$lang}' {$selected}>[{$lang}] {$langdesc}</option>";	
	    				}
	    				$tmp_input .= "</select>";
	    			break;

					case "numbers":
	    				$type_numbers = explode("-", $type_array[1]);

	    				$tmp_input = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
	    				for ($ti = $type_numbers[0]; $ti <= $type_numbers[1]; $ti++) {
	    					$selected = $tmp_value==$ti ? "selected='selected'" : "";

	    					$tmp_input .= "<option value='{$ti}' {$selected}>{$ti}</option>";
	    				}
	    				$tmp_input .= "</select>";
	    			break;

	    			case "select":
	    				$options = explode("|", $type_array[1]);

	    				$tmp_input  = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
	    				foreach ($options as $v => $desc) {
	    					$selected = "";
	    					if ($tmp_value == $v) $selected = "selected='selected'";

	    					$tmp_input .= "<option value='{$v}' {$selected}>" . $desc . "</option>";
	    				}

	    				$tmp_input .= "</select>";
	    			break;

	    			case "textarea":
	    				$tmp_input  = "<textarea class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
	    				$tmp_input .= $tmp_value;
	    				$tmp_input .= "</textarea>";
	    			break;

	    			case "themes":
	    				$tmp_input = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";

	    				$themes = $this->get_themes();
	    				foreach ($themes as $theme) {
	    					$selected = $tmp_value==$theme->id ? "selected='selected'" : "";

	    					$tmp_input .= "<option value='{$theme->id}' {$selected}>{$theme->description}</option>";
	    				}
	    				$tmp_input .= "</select>";
	    			break;

	    			case "timepicker_array":
	    				$times_json = json_decode($tmp_value);

	    				$tmp_input  = "<div id='{$element_id}' class='container-fluid option-wrapper ezscm-hours' data-option_name='{$options_name}' data-option_id='{$tmp_id}' data-inputnames='from,to'>";
	    				// add business hours button
	    				$tmp_input .= "		<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12 option-controls'>";
	    				$tmp_input .= "			<li class='button option-add'><i class='fa fa-fw fa-plus'></i> " . __("Add business hours", "ezb") . "</li>";
	    				$tmp_input .= "		</div>";

	    				// clone element
	    				$tmp_input .= "		<div class='ezscm-hidden option-clone option-item' data-row='0'>";

	    				// from
	    				$tmp_input .= "			<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>";
	    				$tmp_input .= "				" . __("From" , "ezscm") . " <input class='timepicker timepicker-from' type='text' value='09:00' />";
	    				// to
	    				$tmp_input .= "				" . __("To" , "ezscm") . " <input class='timepicker timepicker-to' type='text' value='17:00' />";
	    				// remove button
	    				$tmp_input .= "				<button class='button option-remove'><i class='fa fa-fw fa-times'></i></button>";
	    				$tmp_input .= "			</div>";

	    				// clone end
	    				$tmp_input .= "		</div>";

	    				foreach ($times_json as $t => $times_array) {
		    				if (!property_exists($times_array, "from") || !property_exists($times_array, "to")) {
		    					$times_array = json_encode(array(
		    						"from" => "09:00",
		    						"to"   => "17:00"
		    					));
		    				}

		    				$tmp_input .= "<div class='option-item' data-row='{$t}'>";
		    				$tmp_input .= "		<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>";
		    				$tmp_input .= "			" . __("From" , "ezscm") . " <input class='timepicker timepicker-from' type='text' name='{$options_name}[{$tmp_id}][{$t}][from]' value='{$times_array->from}' />";
		    				$tmp_input .= "			" . __("To" , "ezscm") . " <input class='timepicker timepicker-to' type='text' name='{$options_name}[{$tmp_id}][{$t}][to]' value='{$times_array->to}' />";
		    				$tmp_input .= "				<button class='button option-remove' data-ot='" . __("Remove item", "ezscm") . "'><i class='fa fa-fw fa-times'></i></button>";
		    				$tmp_input .= "		</div>";
		    				$tmp_input .= "</div>";
		    			}

		    			// option wrapper
		    			$tmp_input .= "</div>";
	    			break;

					case "time_formats":
	    				$options = array(
	    					"H:i"   => "13:00",
	    					"h:i A" => "01:00 AM",
	    					"h:i a" => "01:00 am"
	    				);

	    				$tmp_input  = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
	    				foreach ($options as $v => $desc) {
	    					$selected = "";
	    					if ($tmp_value == $v) $selected = "selected='selected'";

	    					$tmp_input .= "<option value='{$v}' {$selected}>" . $desc . "</option>";
	    				}

	    				$tmp_input .= "</select>";
					break;

					case "weekdays":
	    				$days_selected = explode(",", $tmp_value);
	    				$days = array(
	    					1 => __("Monday", "ezscm"),
	    					2 => __("Tuesday", "ezscm"),
	    					3 => __("Wednesday", "ezscm"),
	    					4 => __("Thursday", "ezscm"),
	    					5 => __("Friday", "ezscm"),
	    					6 => __("Saturday", "ezscm"),
	    					0 => __("Sunday", "ezscm")
    					);

	    				$tmp_input  = "<input type='hidden' class='regular-text {$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]' value='{$tmp_value}' />";
	    				$tmp_input .= "<div class='buttonset'>";

    					foreach ($days as $i => $day) {
    						$checked = in_array($i, $days_selected) ? "checked" : "";
    						$tmp_input .= "<input class='{$s->name}' type='checkbox' value='{$i}' id='{$s->name}_{$i}' {$checked} />";
    						$tmp_input .= "<label for='{$s->name}_{$i}'>";
    						$tmp_input .= $day;
    						$tmp_input .= "</label>";
    					}
    					$tmp_input .= "</div>";
	    			break;

	    			case "yesno":
	    				$selected_no = $selected_yes = "";

	    				if ($tmp_value == 0) $selected_no = " selected='selected'";
	    				else                $selected_yes = " selected='selected'";

	    				$tmp_input  = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
	    				$tmp_input .= "    <option value='0' {$selected_no}>" . __("No", "ezscm") . "</option>";
	    				$tmp_input .= "    <option value='1' {$selected_yes}>" . __("Yes", "ezscm") . "</option>";
	    				$tmp_input .= "</select>";
	    			break;

	    			case "hidden":
	    				$is_hidden = true;
	    				$tmp_value = esc_attr($tmp_value);
	    				
	    				$tmp_input = "<input type='hidden' class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]' value=\"{$tmp_value}\" />";
	    			break;

	    			default:
	    				$tmp_value = esc_attr($tmp_value);
	    				
	    				$tmp_input = "<input type='text' class='regular-text {$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]' value=\"{$tmp_value}\" />";
	    			break;
	    		}
    		}

    		if ($is_hidden) {
    			$table_out[] = "<th scope='row' style='display: none;'></th><td style='display: none; '>{$tmp_input}</td>";
    		}
    		else {
	    		$table_out[] = "
			    	<th scope='row'>
			    		<label for='{$options_name}-{$tmp_id}'>" . __($s->description, "ezscm") . "</label>
			    	</th>
			    	<td>
			    		{$tmp_input}
			    		<p class='description'>" . __($s->description_long, "ezscm") . "</p>
			    	</td>
		    	";
		    }
    	}

    	$out[] = implode("</tr><tr>", $table_out);

		$out[] = "	</tr>";
		$out[] = "</table>";

    	return implode("", $out);
    }

    function last_monday($date) {
		if (!is_numeric($date)) {
		    $date = strtotime($date);
		}

		if (date('w', $date) == 1) {
		    return $date;
		}
		else {
		    return strtotime(
		        'last monday',
		         $date
		    );
		}
	}


	function send_message($type, $msg, $id=0) {
		return array(
			$type 	=> $msg,
			"id"	=> $id
		);
	}


	function setup_db() {
		$query = file_get_contents(dirname(__FILE__) . "/db.sql");
		if (!$query) {
			die("Error opening file 'db.sql'");
		}

		$query_replaced = str_replace("__PREFIX__", $this->wpdb->prefix, $query);
		$this->execute_multiline_sql($query_replaced);
	}

	private function execute_multiline_sql($sql, $delim=";") {
	    global $wpdb;
	    
	    $sqlParts = $this->split_sql_file($sql, $delim);
	    foreach($sqlParts as $part) {
	        $res = $wpdb->query($part);

	        if ($res === false) {
	        	$wpdb->print_error();
	        	return false;
	        }
	    }

	    return true;
	}

	private function split_sql_file($sql, $delimiter) {
	   // Split up our string into "possible" SQL statements.
	   $tokens = explode($delimiter, $sql);

	   // try to save mem.
	   $sql = "";
	   $output = array();

	   // we don't actually care about the matches preg gives us.
	   $matches = array();

	   // this is faster than calling count($oktens) every time thru the loop.
	   $token_count = count($tokens);
	   for ($i = 0; $i < $token_count; $i++)
	   {
	      // Don't wanna add an empty string as the last thing in the array.
	      if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0)))
	      {
	         // This is the total number of single quotes in the token.
	         $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
	         // Counts single quotes that are preceded by an odd number of backslashes,
	         // which means they're escaped quotes.
	         $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

	         $unescaped_quotes = $total_quotes - $escaped_quotes;

	         // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
	         if (($unescaped_quotes % 2) == 0)
	         {
	            // It's a complete sql statement.
	            $output[] = $tokens[$i];
	            // save memory.
	            $tokens[$i] = "";
	         }
	         else
	         {
	            // incomplete sql statement. keep adding tokens until we have a complete one.
	            // $temp will hold what we have so far.
	            $temp = $tokens[$i] . $delimiter;
	            // save memory..
	            $tokens[$i] = "";

	            // Do we have a complete statement yet?
	            $complete_stmt = false;

	            for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++)
	            {
	               // This is the total number of single quotes in the token.
	               $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
	               // Counts single quotes that are preceded by an odd number of backslashes,
	               // which means they're escaped quotes.
	               $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

	               $unescaped_quotes = $total_quotes - $escaped_quotes;

	               if (($unescaped_quotes % 2) == 1)
	               {
	                  // odd number of unescaped quotes. In combination with the previous incomplete
	                  // statement(s), we now have a complete statement. (2 odds always make an even)
	                  $output[] = $temp . $tokens[$j];

	                  // save memory.
	                  $tokens[$j] = "";
	                  $temp = "";

	                  // exit the loop.
	                  $complete_stmt = true;
	                  // make sure the outer loop continues at the right point.
	                  $i = $j;
	               }
	               else
	               {
	                  // even number of unescaped quotes. We still don't have a complete statement.
	                  // (1 odd and 1 even always make an odd)
	                  $temp .= $tokens[$j] . $delimiter;
	                  // save memory.
	                  $tokens[$j] = "";
	               }

	            } // for..
	         } // else
	      }
	   }

	   return $output;
	}
}