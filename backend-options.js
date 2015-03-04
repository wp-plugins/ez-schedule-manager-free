jQuery(document).ready(function($) {
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

	// datepicker array
	$.datepicker.formatDate = function (format, value) {
	    return moment(value).format(format);
	};

	// default datepicker options
	datepicker_defaults = {
		changeMonth: true,
		changeYear: true,
		dateFormat: "YYYY-MM-DD",
		showOtherMonths: true,
		selectOtherMonths: true,
		showWeek: true,
		firstDay: 1
	};

	// datepicker range
	$(".datepicker-range-wrapper .option-item:not(.option-clone)").each(function() {
		var wrapper = this;

		var datepicker_from = $(this).find(".datepicker-from");
		datepicker_from.datepicker($.extend(true, datepicker_defaults, {
			dateFormat: "YYYY-MM-DD",
			onSelect: function(date) {
				var minDate = datepicker_momentjs_date(date, "YYYY-MM-DD");

				var date_to_element = $(wrapper).find(".datepicker-to");
				var date_to_val = date_to_element.val();

				date_to_element.datepicker("option", "minDate", minDate);
				// due to datepicker's overwritten function formatDate, the value is cleared after selecting
				date_to_element.val(date_to_val);
			}
		}));

		var datepicker_to = $(this).find(".datepicker-to");
		datepicker_to.datepicker($.extend(true, datepicker_defaults, {
			dateFormat: "YYYY-MM-DD",
			onSelect: function(date) {
				var maxDate = datepicker_momentjs_date(date, "YYYY-MM-DD");

				var date_from_element = $(wrapper).find(".datepicker-from");
				var date_from_val = date_from_element.val();

				date_from_element.datepicker("option", "maxDate", maxDate);
				// due to datepicker's overwritten function formatDate, the value is cleared after selecting
				date_from_element.val(date_from_val);
			}
		}));
	});

	function datepicker_momentjs_date(date, date_format) {
    	return moment(date, date_format).toDate();
	}
});