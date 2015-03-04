<?php

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

require_once(plugin_dir_path(__FILE__) . "class.ezscm_backend.php");
$ezscm = new Ezscm_backend();

if (isset($_POST["submit"])) {
	$overwrite = isset($_POST["ezscm-overwrite"]) ? 1 : 0;
	$ezscm->update_settings($_POST["opt"], $overwrite);

	$updated = 1;
}

// schedule settings
$settings = $ezscm->get_settings("cat ASC, name ASC");

// categorize settings
$settings_cat = array();
foreach ($settings as $s) {
	$settings_cat[$s->cat][] = $s;
}

// other settings
$settings_alt = array(
	"ezscm_captcha_public"   => array("desc" => "Captcha public key",  "desc_add" => ""),
	"ezscm_captcha_private"  => array("desc" => "Captcha private key", "desc_add" => "")
);

$langs = array('ar'=>'Arabic','ar-ma'=>'Moroccan Arabic','bs'=>'Bosnian','bg'=>'Bulgarian','br'=>'Breton','ca'=>'Catalan','cy'=>'Welsh','cs'=>'Czech','cv'=>'Chuvash','da'=>'Danish','de'=>'German','el'=>'Greek','en'=>'English','en-au'=>'English (Australia)','en-ca'=>'English (Canada)','en-gb'=>'English (England)','eo'=>'Esperanto','es'=>'Spanish','et'=>'Estonian','eu'=>'Basque','fa'=>'Persian','fi'=>'Finnish','fo'=>'Farose','fr-ca'=>'French (Canada)','fr'=>'French','gl'=>'Galician','he'=>'Hebrew','hi'=>'Hindi','hr'=>'Croatian','hu'=>'Hungarian','hy-am'=>'Armenian','id'=>'Bahasa Indonesia','is'=>'Icelandic','it'=>'Italian','ja'=>'Japanese','ka'=>'Georgian','ko'=>'Korean','lv'=>'Latvian','lt'=>'Lithuanian','ml'=>'Malayalam','mr'=>'Marathi','ms-my'=>'Bahasa Malaysian','nb'=>'Norwegian','ne'=>'Nepalese','nl'=>'Dutch','nn'=>'Norwegian Nynorsk','pl'=>'Polish','pt-br'=>'Portuguese (Brazil)','pt'=>'Portuguese','ro'=>'Romanian','ru'=>'Russian','sk'=>'Slovak','sl'=>'Slovenian','sq'=>'Albanian','sv'=>'Swedish','th'=>'Thai','tl-ph'=>'Tagalog (Filipino)','tr'=>'Turkish','tzm-la'=>'Tamaziɣt','uk'=>'Ukrainian','uz'=>'Uzbek','zh-cn'=>'Chinese','zh-tw'=>'Chinese (Traditional)');

?>

<div class="ezscm wrap">
	<?php echo "<h2>" . __("Schedule settings", "ezscm") . " - v" . ezscm_get_version() . "</h2>"; ?>

	<p>
		<?php _e("These options can be changed individually in each form. Saving these options will be applied to new schedules only (when you did not check to overwrite settings).", "ezscm"); ?>
	</p>

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