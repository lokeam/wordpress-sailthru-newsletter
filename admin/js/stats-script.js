(function($){
	var sailthruStats = {
		init: function() {
			var _self = this;
			_self.statSubmit();
			_self.datePicker();
		},
		statSubmit: function() {
			$("form").submit(function(){
				$(this).next(".qq-upload-spinner").show();
			})
		},
		datePicker: function() {
			$("#sailthru_date").datetimepicker({
				timepicker: false,
				maxDate: 0,
				format: "Y-m-d"
			});
		}
	};
	$(document).ready(function(){
		sailthruStats.init();
	});
})(jQuery);