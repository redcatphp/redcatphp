$js(true,[
	'jquery-ui/core',
	'jquery-ui/widget',
	'jquery-ui/datepicker',
	'jquery-ui/mouse',
	'jquery-ui/slider',
	'jquery-ui/i18n/datepicker-fr',
	'jquery-ui/timepicker'
],function(){
	$.datepicker.setDefaults($.datepicker.regional["fr"]);
	$('input[type=time]').timepicker({
		currentText: 'Maintenant',
		closeText: 'OK',
		timeOnlyTitle: "Définir l'Horaire",
		showTime: false,
		hourText: 'Heure',
		minuteText: 'Minute',
		timeOnly:true,
		showTimezone:false
	});
	//$('input[type="datetime"]').timepicker({timeOnly:false,showTimezone:true});
	//$('input[type="datetime-local"]').timepicker({timeOnly:false,showTimezone:false});
	
	$('[is=daterange]').each(function(){
		var dateStart = $(this).find('input.date-start');
		var dataid = dateStart.attr('data-id');
		if(dataid)
			var dateEnd = $(this).find('input.date-end[data-id="'+dataid+'"]');
		else
			var dateEnd = $(this).find('input.date-end');

		var dates_end_hide = function(){
			dateEnd.closest('fieldset').hide();
			dateStart.closest('fieldset').find('legend').hide();
		};
		var dates_end_show = function(){
			dateEnd.closest('fieldset').show();
			dateStart.closest('fieldset').find('legend').show();
		};

		var checkbox = $(this).find('input[name=date_with_end]');
		if(!checkbox.is(':checked')){
			dates_end_hide();
		}
		checkbox.change(function(e){
			e.preventDefault();
			if($(this).attr('checked')){
				dates_end_hide();
				$(this).removeAttr('checked');
			}
			else{
				dates_end_show();
				$(this).attr('checked','checked');
			}
			return false;
		});
		var dateToday = new Date();
		dateStart.datepicker({
			changeMonth: true,
			minDate: dateToday,
			onSelect: function(selectedDate) {
				var instance = $(this).data("datepicker"),
					date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
				dateEnd.datepicker("option", 'minDate', date);
			}
		});
		dateEnd.datepicker({
			changeMonth: true,
			minDate: dateToday,
			onSelect: function(selectedDate) {
				var instance = $(this).data("datepicker"),
					date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
				dateStart.datepicker("option", 'maxDate', date);
			}
		});
	});
});
