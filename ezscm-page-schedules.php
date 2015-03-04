<?php

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

global $wpdb;

require_once(plugin_dir_path(__FILE__) . "class.ezscm_backend.php");
$ezscm = new Ezscm_backend();

$settings     = $ezscm->get_settings();
$schedules    = $ezscm->get_schedules("section ASC, id ASC");

$isoweek      = strtotime(date("Y") . "W" . date("W"));
$current_week = date("Y-m-d", $isoweek);

$langs = array('ar'=>'Arabic','ar-ma'=>'Moroccan Arabic','bs'=>'Bosnian','bg'=>'Bulgarian','br'=>'Breton','ca'=>'Catalan','cy'=>'Welsh','cs'=>'Czech','cv'=>'Chuvash','da'=>'Danish','de'=>'German','el'=>'Greek','en'=>'English','en-au'=>'English (Australia)','en-ca'=>'English (Canada)','en-gb'=>'English (England)','eo'=>'Esperanto','es'=>'Spanish','et'=>'Estonian','eu'=>'Basque','fa'=>'Persian','fi'=>'Finnish','fo'=>'Farose','fr-ca'=>'French (Canada)','fr'=>'French','gl'=>'Galician','he'=>'Hebrew','hi'=>'Hindi','hr'=>'Croatian','hu'=>'Hungarian','hy-am'=>'Armenian','id'=>'Bahasa Indonesia','is'=>'Icelandic','it'=>'Italian','ja'=>'Japanese','ka'=>'Georgian','ko'=>'Korean','lv'=>'Latvian','lt'=>'Lithuanian','ml'=>'Malayalam','mr'=>'Marathi','ms-my'=>'Bahasa Malaysian','nb'=>'Norwegian','ne'=>'Nepalese','nl'=>'Dutch','nn'=>'Norwegian Nynorsk','pl'=>'Polish','pt-br'=>'Portuguese (Brazil)','pt'=>'Portuguese','ro'=>'Romanian','ru'=>'Russian','sk'=>'Slovak','sl'=>'Slovenian','sq'=>'Albanian','sv'=>'Swedish','th'=>'Thai','tl-ph'=>'Tagalog (Filipino)','tr'=>'Turkish','tzm-la'=>'Tamaziɣt','uk'=>'Ukrainian','uz'=>'Uzbek','zh-cn'=>'Chinese','zh-tw'=>'Chinese (Traditional)');

// categorize settings
$settings_cat = array();
foreach ($settings as $s) {
	$settings_cat[$s->cat][] = $s;
}

$nonce = wp_create_nonce("ezscm-nonce");
?>

<div class="ezscm wrap">
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12">
				<?php echo "<h2 class=''>" . __("ez Schedule Manager", "ezscm") . " - v" . ezscm_get_version() . " <span class='ezscm-message'></span><span class='spinner'></span></h2>"; ?>

				<?php if (isset($updated)) { ?>
					<div id="message" class="updated"><?php echo __("Settings saved.", "ezscm"); ?></div>
				<?php } ?>
			</div>

			<div class="col-lg-2 ezscm-schedules-list-wrapper">
				<h3><?php echo __("Overview", "ezscm"); ?></h3>

				<ul>
					<li class="button" data-action="add"><i class='fa fa-fw fa-plus-square-o'></i> <?php echo __("Add schedule", "ezscm"); ?></li>
				</ul>

				<hr />

				<ul class="ezscm-schedules-list">
					<li class="button clone" data-action="get_schedule" data-selectgroup="schedule" data-week="<?php echo $current_week; ?>"></li>

					<?php
					foreach ($schedules as $s) {
						echo "<li class='button' data-id='{$s->s_id}' data-action='get_schedule' data-selectgroup='schedule' data-week='{$current_week}'><i class='fa fa-fw fa-list-alt'></i> {$s->s_id} - <span class='schedule-name'>{$s->name}</span></li>";
					}
					?>
				</ul>
			</div>

			<div class="col-lg-8">
				<h3><?php echo __("Calendar", "ezscm"); ?></h3>

				<div class="ezscm-wrapper hidden">
					<div class="ezscm-options">
						<ul>
							<li class="button ezscm-option-button" data-action="get_schedule" data-week="<?php echo $current_week; ?>" data-id=""><i class='fa fa-calendar'></i> <?php echo __("Overview", "ezscm"); ?></li>
							<li class="button ezscm-option-button" data-action="get_options"><i class='fa fa-cogs'></i> <?php echo __("Options", "ezscm"); ?></li>
							<li class="button ezscm-option-button" data-action="rename"><i class='fa fa-edit'></i> <?php echo __("Rename", "ezscm"); ?></li>

							<li class="ezscm-separator"></li>

							<li class="button ezscm-option-button" data-action="schedule_clear" data-ot="<?php _e("Removes all entries from this schedule", "ezscm"); ?>"><i class='fa fa-eraser'></i> <?php echo __("Clear Schedule", "ezscm"); ?></li>

							<li class="ezscm-separator"></li>

							<li class="button ezscm-option-button" data-action="schedule_del" data-ot="<?php _e("Deletes this schedule", "ezscm"); ?>"><i class='fa fa-times'></i> <?php echo __("Delete", "ezscm"); ?></li>

							<!-- next update :)
							<li class="button ezscm-option-button" data-action="get_entries" data-selectgroup="options" data-id=""><?php echo __("Entries", "ezscm"); ?></li>
							-->
						</ul>

						<hr />
					</div>

					<div class="ezscm-browse">
						<ul>
							<li class="button ezscm-browse-prev" data-action="get_schedule" data-week="<?php echo date("Y-m-d", strtotime("last Monday")); ?>"><i class='fa fa-caret-square-o-left'></i> <?php echo __("Previous week", "ezscm"); ?></li>
							<li class="button ezscm-browse-pick" data-ot="<?php _e("Weekpicker", "ezscm"); ?>"><i class='fa fa-table'></i></li>
							<input type="hidden" value="" class="ezscm-browse-pick-dummy" />
							<li class="button ezscm-browse-next" data-action="get_schedule" data-week="<?php echo date("Y-m-d", strtotime("next Monday")); ?>"><?php echo __("Next week", "ezscm"); ?> <i class='fa fa-caret-square-o-right'></i></li>
						</ul>
					</div>

					<div class="ezscm-schedule-wrapper">
						<div class="ezscm-schedule"></div>
					</div>

					<div class="ezscm-legend ezscm-inline-list ezscm-inline-list-autowidth">
						<ul>
							<li class="entry-free"><?php echo __("Free", "ezscm"); ?></li>
							<li class="entry-blocked"><?php echo __("Blocked", "ezscm"); ?></li>
							<li class="entry-private"><?php echo __("Private", "ezscm"); ?></li>
							<li class="entry-public"><?php echo __("Public entry", "ezscm"); ?></li>
						</ul>
					</div>
				</div>
			</div>

			<div class="col-lg-2">
				<div class="shortcode-wrapper">
					<h3><?php echo __("Shortcode", "ezscm"); ?></h3>
					
					<input type="text" id="ezscm_shortcode" disabled="disabled" style="margin-bottom: 7px;" />

					<hr />
				</div>

				<div class="entry-details hidden">
					<h3><?php echo __("Details", "ezscm"); ?></h3>

					<form class="entry-details-form">
						<input type="hidden" name="details-date_internal" id="details-date_internal" />
						<input type="hidden" name="details-time_internal" id="details-time_internal" />
						<input type="hidden" name="details-s_id" id="details-s_id" />
						<input type="hidden" name="ezscm-edit_id" id="ezscm-edit_id" />

						<p>
							<?php echo __("Date", "ezscm"); ?><br />
							<input type="text" class="datepicker" name="details-date" id="details-date" />
						</p>

						<p>
							<?php echo __("Time", "ezscm"); ?><br />
							<input type="text" name="details-time_begin" id="details-time_begin" />
						</p>

						<p>
							<?php echo __("Private", "ezscm"); ?><br />
							<select name="details-private" id="details-private">
								<option value="1"><?php echo __("Yes", "ezscm"); ?></option>
								<option value="0"><?php echo __("No", "ezscm"); ?></option>
							</select>
						</p>

						<p>
							<div class="details-info"></div>
						</p>

						<p>
							<button class="button button-primary" data-action="save_entry"><i class="fa fa-fw fa-save"></i> <?php echo __("Save", "ezscm"); ?></button>
							<button class="button details-delete" data-action="entry_delete"><i class="fa fa-fw fa-eraser"></i> <?php echo __("Delete entry", "ezscm"); ?></button>
						</p>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- options modal dialog -->
	<div class="ezscm-options-dialog ezscm-dialog" title="Form options">
		<form id="form-options" name="ezscm-form-options" action="">
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

			<!-- placeholder for modal buttons -->
			<button class="button button-primary ezscm-option-save hidden" data-action="update_options" data-id=""><?php echo __("Update options", "ezscm"); ?></button>
		</form>
	</div>
</div>

<script>ezscm_nonce = "<?php echo $nonce; ?>";</script>