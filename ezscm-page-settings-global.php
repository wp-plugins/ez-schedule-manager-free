<?php

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

global $wpdb;

require_once(plugin_dir_path(__FILE__) . "class.ezscm_backend.php");
$ezscm = new Ezscm_backend();

if (isset($_POST["reset"])) {
	$keep_data_option = get_option("ezscm_uninstall_keep_data", 0);
	update_option("ezscm_uninstall_keep_data", 0);

	ezscm_uninstall();
	ezscm_register();

	update_option("ezscm_uninstall_keep_data", $keep_data_option);
	$_POST = array();
}

if (isset($_POST["submit"])) {
	// additional options
	error_log(var_export($_POST["opt"], true));
	foreach ($_POST["opt"] as $k => $v) {
		update_option("ezscm_{$k}", $v);
	}

	$updated = 1;
}

// global settings
$settings_alt = array(
	"Customization" => array(
		"custom_css"    => array("description" => "Custom CSS", "description_long" => "Add your custom styles here.", "type" => "textarea"),
		"required_text" => array("description" => "Required text", "description_long" => "", "type" => "input", "default" => "Required"),
	),

	"Captcha" => array(
		"captcha_public"  => array("description" => "Recaptcha public key",  "description_long" => "", "type" => "input"),
		"captcha_private" => array("description" => "Recaptcha private key", "description_long" => "", "type" => "input")
	),

	"Other" => array(
		"debug_mode"          => array("description" => "Enable debug mode", "description_long" => "This will log certain messages for debugging purposes. You should not enable this in production.", "type" => "yesno"),
		"uninstall_keep_data" => array("description" => "Keep data after uninstall", "description_long" => "The plugin will keep all plugin-related data in the databse when uninstalling. Only select 'Yes' if you want to upgrade the script.", "type" => "yesno")
	)
);
$settings = json_decode(json_encode($settings_alt));

// categorize settings
$settings_cat = array();
foreach ($settings as $cat => $s) {
	$settings_cat[$cat] = $s;
}

?>

<div class="ezscm wrap">
	<?php echo "<h2>" . __("Global settings", "ezscm") . " - v" . ezscm_get_version() . "</h2>"; ?>

	<?php if (isset($updated)) { ?>
		<div id="message" class="updated"><?php echo __("Settings saved.", "ezscm"); ?></div>
	<?php } ?>

	<form method="POST" name="ezscm-form" class="ezscm-form" action="">
		<div id="ezscm-form-options">
			<div id="tabs">
				<ul>
					<?php
					$tabs = array_keys($settings_cat);

					foreach ($tabs as $i => $cat) {
						echo "<li><a href='#tab-{$i}'>{$cat}</a></li>";
					}
					?>
				</ul>

			    <?php

			    $tab_i = 0;
			    foreach ($settings_cat as $cat_name => $cat) {
			    	?>

					<div id="tab-<?php echo $tab_i; ?>">
						<?php
						echo $ezscm->get_settings_table($cat, "opt", "opt");
						?>
					</div>

					<?php

					$tab_i++;
				}
				?>
			</div>
		</div>

		<table class="form-table" style="margin-top: 1em;">
			<!-- reset -->
			<tr>
				<th scope='row'>
					<label for="reset">Reset</label>
		    	</th>
		    	<td>
		    		<input type="checkbox" name="reset" id="reset" value="1" /><br>
		    		<p class="description">Complete reset of this plugin. <strong>This will reset all existing data. Use with caution.</strong></p>
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
		$(".ezscm-settings-type-weekdays").each(function() {
			var day_values = [];

			$(this).siblings(".buttonset").find(":checked").each(function() {
				day_values.push($(this).val());
			});

			$(this).val(day_values.join(","));
		});
	});

	$("#tabs").tabs();
});
</script>