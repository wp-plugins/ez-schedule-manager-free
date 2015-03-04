<?php

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

global $wpdb;

require_once("class.ezscm_backend.php");
$ezscm = new Ezscm_backend();

if (isset($_POST["submit"])) {
	$ezscm->update_settings($_POST["opt"], $_POST["ezscm-overwrite"]);

	// additional options
	foreach ($_POST["alt_opt"] as $k=>$v) {
		update_option($k, $v);
	}

	$updated = 1;
}

// schedule settings
$settings = $ezscm->get_settings();

// other settings
$settings_alt = array(
	"ezscm_captcha_public"   => array("desc" => "Captcha public key",  "desc_add" => ""),
	"ezscm_captcha_private"  => array("desc" => "Captcha private key", "desc_add" => "")
);

$langs = array('ar'=>'Arabic','ar-ma'=>'Moroccan Arabic','bs'=>'Bosnian','bg'=>'Bulgarian','br'=>'Breton','ca'=>'Catalan','cy'=>'Welsh','cs'=>'Czech','cv'=>'Chuvash','da'=>'Danish','de'=>'German','el'=>'Greek','en'=>'English','en-au'=>'English (Australia)','en-ca'=>'English (Canada)','en-gb'=>'English (England)','eo'=>'Esperanto','es'=>'Spanish','et'=>'Estonian','eu'=>'Basque','fa'=>'Persian','fi'=>'Finnish','fo'=>'Farose','fr-ca'=>'French (Canada)','fr'=>'French','gl'=>'Galician','he'=>'Hebrew','hi'=>'Hindi','hr'=>'Croatian','hu'=>'Hungarian','hy-am'=>'Armenian','id'=>'Bahasa Indonesia','is'=>'Icelandic','it'=>'Italian','ja'=>'Japanese','ka'=>'Georgian','ko'=>'Korean','lv'=>'Latvian','lt'=>'Lithuanian','ml'=>'Malayalam','mr'=>'Marathi','ms-my'=>'Bahasa Malaysian','nb'=>'Norwegian','ne'=>'Nepalese','nl'=>'Dutch','nn'=>'Norwegian Nynorsk','pl'=>'Polish','pt-br'=>'Portuguese (Brazil)','pt'=>'Portuguese','ro'=>'Romanian','ru'=>'Russian','sk'=>'Slovak','sl'=>'Slovenian','sq'=>'Albanian','sv'=>'Swedish','th'=>'Thai','tl-ph'=>'Tagalog (Filipino)','tr'=>'Turkish','tzm-la'=>'Tamaziɣt','uk'=>'Ukrainian','uz'=>'Uzbek','zh-cn'=>'Chinese','zh-tw'=>'Chinese (Traditional)');

?>

<div class="ezscm wrap">
	<?php echo screen_icon('tools') . " <h2>" . __("Global settings", "ezscm") . " - v" . ezscm_get_version() . "</h2>"; ?>

	<?php if (isset($updated)) { ?>
		<div id="message" class="updated"><?php echo __("Settings saved.", "ezscm"); ?></div>
	<?php } ?>

	<form method="POST" name="ezscm-form" class="ezscm-form" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<table class="form-table">
			<tr>

			    <?php

			    $out = array();
			    foreach ($settings as $s) {
			    	$add_class = empty($s->type) ? "" : "ezscm-settings-type-{$s->type}";
			    	$tmp_input = "<input type='text' class='regular-text {$add_class}' id='opt-{$s->name}' name='opt[{$s->name}]' value='{$s->value}' />";

			    	if (!empty($s->type)) {
			    		switch ($s->type) {
			    			case "yesno": {
			    				$selected_no = $selected_yes = "";

			    				if ($s->value == 0) $selected_no = " selected='selected'";
			    				else                $selected_yes = " selected='selected'";

			    				$tmp_input  = "<select class='{$add_class}' id='opt[{$s->name}]' name='opt[{$s->name}]'>";
			    				$tmp_input .= "    <option value='0' {$selected_no}>" . __("No", "ezscm") . "</option>";
			    				$tmp_input .= "    <option value='1' {$selected_yes}>" . __("Yes", "ezscm") . "</option>";
			    				$tmp_input .= "</select>";
			    			} break;

			    			case "weekdays": {
			    				$days_selected = explode(",", $s->value);
			    				$days = array(
			    					__("Sunday", "ezscm"),
			    					__("Monday", "ezscm"),
			    					__("Tuesday", "ezscm"),
			    					__("Wednesday", "ezscm"),
			    					__("Thursday", "ezscm"),
			    					__("Friday", "ezscm"),
			    					__("Saturday", "ezscm")
		    					);

			    				$tmp_input  = "<input type='hidden' class='regular-text {$add_class}' id='opt-{$s->name}' name='opt[{$s->name}]' value='{$s->value}' />";
			    				$tmp_input .= "<div class='buttonset'>";
		    					foreach ($days as $i=>$day) {
		    						$checked = in_array($i, $days_selected) ? "checked" : "";
		    						$tmp_input .= "<input class='{$s->name}' type='checkbox' value='{$i}' id='{$s->name}_{$i}' {$checked} />";
		    						$tmp_input .= "<label for='{$s->name}_{$i}'>";
		    						$tmp_input .= $day;
		    						$tmp_input .= "</label>";
		    					}
		    					$tmp_input .= "</div>";
			    			} break;

			    			case "lang": {
			    				$tmp_input  = "<select class='{$add_class}' id='opt.{$s->name}' name='opt[{$s->name}]'>";
			    				foreach ($langs as $lang=>$langdesc) {
			    					$selected = "";
			    					if ($s->value == $lang) $selected = "selected='selected'";

			    					$tmp_input .= "<option value='{$lang}' {$selected}>[{$lang}] {$langdesc}</option>";	
			    				}
			    				$tmp_input .= "</select>";
			    			}
			    		}
			    	}

			    	$out[] = "
				    	<th scope='row'>
				    		<label for='opt-{$s->name}'>" . __($s->description, "ezscm") . "</label>
				    	</th>
				    	<td>
				    		{$tmp_input}
				    		<p class='description'>" . __($s->description_long, "ezscm") . "</p>
				    	</td>
			    	";
			    }

			    foreach ($settings_alt as $name=>$s) {
			    	$tmp_opt = get_option($name);

			    	$out[] = "
				    	<th scope='row'>
				    		<label for='alt_opt[{$name}]'>" . __($s["desc"], "ezscm") . "</label>
				    	</th>
				    	<td>
				    		<input type='text' class='regular-text' id='alt_opt[{$name}]' name='alt_opt[{$name}]' value='{$tmp_opt}' />
				    		<p class='description'>" . __($s["desc_add"], "ezscm") . "</p>
				    	</td>
			    	";
			    }

			    echo implode("</tr><tr>", $out);
				?>

			</tr>

			<!-- overwrite settings -->
			<tr>
				<th scope='row'>
					Overwrite settings
		    	</th>
		    	<td>
		    		<input type="checkbox" name="ezscm-overwrite" id="ezscm-overwrite" value="1" /><br>
		    		<p class="description">Checking this option will overwrite <b>ALL</b> existing schedule settings!</p>
		    	</td>
		    </tr>

			
		</table>

		<!-- save -->
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __("Save", "ezscm"); ?>" /></p>
	</form>
</div>

<script>
jQuery(function($) {
	$(".ezscm-form").on("submit", function() {
		// confirmation
		if ($("#ezscm-overwrite").prop("checked")) {
			if (!confirm("Really overwrite all schedule settings?")) return false;
		}

		// buttonset -> single value
		var day_values = [];
		$(".days_available:checked").each(function(v) {
			day_values.push($(this).val());
		});
		$("#opt-days_available").val(day_values.join(","));
	});
});
</script>