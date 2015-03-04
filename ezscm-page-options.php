<?php

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

global $wpdb;

require_once(plugin_dir_path(__FILE__) . "class.ezscm_backend.php");
$ezscm = new Ezscm_backend();

$entries = $ezscm->get_entries();
?>

<div class="ezscm wrap">
    <?php
    echo screen_icon('users') . " <h2>" . __("Public entries", "ezscm") . "</h2>";

	if (count($entries) < 1) {
		echo __("No entries found.", "ezscm");
	}
	else {
	?>
		<table class="widefat">
			<tr>
				<th>ID</th>
				<th><?php echo __("Schedule", "ezscm"); ?></th>
				<th><?php echo __("Date", "ezscm"); ?></th>
				<th><?php echo __("Time", "ezscm"); ?></th>
			</tr>

			<?php
			$tr = array();

			foreach ($entries as $entry) {
				$td = array(
					$entry->e_id,
					$entry->s_id,
					$entry->date,
					"{$entry->time_begin} - {$entry->time_end}"
				);

				$tr[] = "<td>" . implode("</td><td>", $td) . "</td>";
			}

			echo "<tr>" . implode("</tr><tr>", $tr) . "</tr>";
			?>

		</table>
	<?php } ?>
</div>