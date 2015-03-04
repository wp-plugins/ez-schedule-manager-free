CREATE TABLE IF NOT EXISTS `__PREFIX__ezscmf_debug` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `msg` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `__PREFIX__ezscmf_entries` (
  `e_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `s_id` int(10) unsigned NOT NULL,
  `time_begin` time NOT NULL,
  `time_end` time NOT NULL,
  `date` date NOT NULL,
  `private` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  `date_submitted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(50) NOT NULL,
  PRIMARY KEY (`e_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `__PREFIX__ezscmf_schedules` (
  `s_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`s_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `__PREFIX__ezscmf_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `description` text NOT NULL,
  `description_long` text NOT NULL,
  `type` varchar(30) NOT NULL,
  `cat` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `__PREFIX__ezscmf_settings` (`id`, `name`, `value`, `description`, `description_long`, `type`, `cat`) VALUES
	(1, 'days_available', '1,2,3,4,5', 'Available days in a week', 'Selected days will be shown.', 'weekdays', 'Schedule'),
	(2, 'time_begin', '08:00', 'Begin time', 'Earliest entry time.', 'hidden', 'Schedule'),
	(3, 'time_end', '16:00', 'End time', 'Latest entry time. Note: guests cannot add entries after this exact time.', 'hidden', 'Schedule'),
	(4, 'time_steps', '01:00', 'Entry duration', 'Duration of a single entry. <b>Note: changing this value can hide certain entries due to different start times.</b>', 'hidden', 'Schedule'),
	(5, 'time_block_start', '12:00', 'Blocked start time', 'Guests cannot add entries during blocked start- and end time.', 'time', 'Schedule'),
	(6, 'time_block_end', '13:00', 'Blocked end time', 'Guests cannot add entries during blocked start- and end time.', 'time', 'Schedule'),
	(7, 'email_recipient', '', 'Email recipient', 'Notifications will be sent to this email. Leave blank for no notifications.', 'input', 'Email'),
	(8, 'show_dates', '1', 'Show dates', 'Show dates in frontend.', 'yesno', 'Schedule'),
	(9, 'show_days', '1', 'Show days', 'Show weekday names in frontend.', 'yesno', 'Schedule'),
	(10, 'days_per_row', '7', 'Days per row', 'Number of days in a row. Depending on the theme, the layout could break. Try a lower number instead.', 'input', 'Schedule'),
	(11, 'browse_weeks', '1', 'Browse weeks', 'Guests are able to browse future weeks.', 'yesno', 'Schedule'),
	(12, 'display_only', '0', 'Display schedule only', 'Only display the schedule. Guests cannot submit anything.', 'yesno', 'Schedule'),
	(13, 'lang_dates', 'en', 'Date language', 'Predefined translations for frontend dates. Default: EN', 'lang', 'Form'),
	(14, 'date_format', 'MM-DD-YYYY', 'Date format', 'default: \'MM-DD-YYYY\', see <a href=\'http://momentjs.com/docs/#/parsing/string-format/\' target=\'_blank\'>momentjs.com</a>', 'hidden', 'Schedule'),
	(15, 'spam_time', '60', 'Spam protection in seconds', 'Every x seconds, a user (identified by IP address) can add an entry. Default: 60.', 'input', 'Form'),
	(16, 'entry_time_ahead', '24', 'Minimum hours ahead', 'Guests can only submit x hours ahead minimum. Default: 24', 'input', 'Schedule'),
	(17, 'submit_message', 'Thank you for your submission.', 'Submission message', 'Frontend message after successful submission.', 'input', 'Form'),
	(18, 'form_elements', 'Name[required],\r\nEmail[required][email],\r\nMessage[required][textarea]', 'Form elements', 'Form elements, separated by comma.<br>Add [required] for required fields.<br>Add [textarea] for large fields (e.g. message field).<br>Add [email] for email field.<br>Default: Name[required],Email[required],Message[required][textarea]', 'textarea', 'Form'),
  (19, 'email_admin_sender', '', 'Sender name', 'Sender name in emails. Use this syntax: Sendername &lt;sender@mail.com&gt;', 'input', 'Email'),
	(20, 'email_admin_subject', 'New submission', 'Admin email subject', '', 'input', 'Email'),
	(21, 'email_admin_text', '<p>You have received a new submission:</p><p>{{result}}</p>', 'Admin email text', 'Email text sent to the admin. Use {{result}} to display form values', 'editor', 'Email'),
	(22, 'email_subject', 'Your submission', 'Email subject', '', 'input', 'Email'),
	(23, 'email_text', '<p>Thank you for your submission, we will contact you soon!</p><p>{{result}}</p>', 'Email text', 'Email text sent to the user. Use {{result}} to display form values', 'editor', 'Email'),
	(24, 'closed_days', '[{"from":"","to":""}]', 'Closed days', 'Timeframes of closed days (for holidays or single days)', 'hidden', 'Schedule'),
	(25, 'time_format', 'HH:mm', 'Time format', 'Time format, see <a href="http://momentjs.com/docs/#/displaying/format/" target="_blank">momentjs.com</a> for time formats.', 'hidden', 'Schedule'),
  (26, 'redirect_url', '', 'Redirect URL', 'Redirect users to this URL upon form submission. Note: URL must start with http://', 'input', 'Form'),
  (27, 'entry_content_backend', 'Name', 'Entry content (backend)', 'Entry data will be displayed directly in the private schedule.<br>This value must be equal to one element from the option "Form elements", e.g. "Name", "Email" etc.<br>Leave blank for no content.', 'input', 'Schedule'),
  (28, 'entry_content_frontend', '', 'Entry content (frontend)', 'Entry data will be displayed directly in the public schedule.<br>This value must be equal to one element from the option "Form elements", e.g. "Name", "Email" etc.<br>Leave blank for no content.', 'input', 'Schedule'),
  (29, 'submit_button_text', 'Submit', 'Submit button text', '', 'input', 'Form'),
  (30, 'submit_button_css', '', 'Submit button CSS class', '', 'input', 'Form'),
  (31, 'show_weeks_amount_backend', '1', 'Amount of weeks to be shown in backend', '', 'hidden', 'Schedule'),
  (32, 'show_weeks_amount_frontend', '1', 'Amount of weeks to be shown in frontend', '', 'hidden', 'Schedule');

CREATE TABLE IF NOT EXISTS `__PREFIX__ezscmf_settings_schedule` (
  `s_id` int(10) unsigned NOT NULL,
  `o_id` int(10) unsigned NOT NULL,
  `value` text,
  PRIMARY KEY (`s_id`,`o_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;