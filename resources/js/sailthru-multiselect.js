jQuery(document).ready( function($) {
	if ( $('.crosspost-blog-select').length < 1 ) {
		return;
	}

	var toggle_background_image = function( select ) {
		var id = select.attr('id');
		var chosen_ul = $('#'+id+'_chzn').find('ul.chzn-results');
		var choice_box = chosen_ul.parents('.chzn-container').find('.chzn-choices');
		if ( select.attr('disabled') || chosen_ul.find('li:not(.result-selected, .group-result)').length < 1 ) {
			choice_box.css({backgroundPosition: '-200px -200px'});
		} else {
			var y_offset = choice_box.height() - 21;
			var x_offset = choice_box.width() - 21;
			choice_box.css({backgroundPosition: x_offset+'px '+y_offset+'px'});
		}
	};

	// $('.crosspost-blog-select[multiple]').not(':disabled').after('<p><a href="#" class="clear-crosspost-blog-select">Clear all</a></p>');

	$('.crosspost-blog-select').chosen({
		placeholder_text: 'Select Sites'
	}).change( function() {
		toggle_background_image($(this));
	}).each( function() {
		toggle_background_image($(this));
	});

	$('.clear-crosspost-blog-select').click( function() {
		var select = $(this).parents('.crosspost-blog-select-wrapper').find('.crosspost-blog-select');
		select.val(0).trigger('liszt:updated').trigger('change');
		return false;
	});

});
