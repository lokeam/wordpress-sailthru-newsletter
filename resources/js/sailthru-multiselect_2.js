jQuery(document).ready( function($) {

    $.fn.serializeObject = function()
    {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function() {
            if (o[this.name] !== undefined) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

var stMultiSelect = {

        init : function(){
            var self = this;
            self.clearField();
            self.refreshLists();
            self.onWidgetBootEvent();
            self.onWidgetPageSaveEvent();
        },
        clearField : function(){
        var self = this;
            $('.clear-st-multi-select')
                .on('click', function(e) {
                    var type = $(e.target),
                        thisWrap = type.closest('.st-multi-select-wrapper'),
                        select = thisWrap.find('.st-multi-select');
                    select.val(0).trigger('liszt:updated').trigger('change');
                    return false;
                });
        },
        refreshLists : function() {
                    var self = this;
                    $('.refresh-st-multi-select')
                        .on('click', function(e) {
                            var data = {
                                    'action': 'update_stlists'
                            },
                                type = $(e.target),
                                thisWrap = type.closest('.st-multi-select-wrapper'),
                                select = thisWrap.find('.st-multi-select');

                            jQuery.post(ajaxurl, data, function(response) {
                                response = JSON.parse(response);
                                var apikey = '';
                                for (var key in response) {
                                    apikey = key;
                                }

                                // different treatment of data depending upon filtering selection from
                                // settings page
                                if ('lists' in response[apikey]) {
                                    response = response[apikey]["lists"];
                                } else {
                                    response = response[apikey];
                                }
                                // save the val
                                var cachedChoices = select.val();

                                select.children().remove();

                                var sortedResponse = response.sort(function(a,b){
                                    var alc = a.name.toLowerCase(), blc = b.name.toLowerCase();
                                    return alc > blc ? 1 : alc < blc ? -1 : a.name > b.name ? 1 : a.name < b.name ? -1 : 0;
                                });

                                sortedResponse.forEach(function(element, index) {
                                    var newValue = element.name;
                                    select.append('<option value="'+newValue+'">'+element.name+'</option>');
                                });
                                select.val(cachedChoices);
                                self._callChosenLibrary();
                            });
                    });
                },
        onWidgetBootEvent : function(){
            var self = this;
            $(document).ready(function(){
                self._callChosenLibrary();
            });

        },
        onWidgetPageSaveEvent : function(){
            var self = this;
            $(document).on('widget-added widget-updated', function(e,o){
                $('.st-multi-select-wrapper').off();
                self.clearField();
                self.refreshLists();
                self._callChosenLibrary( o );
            });

        },
        _callChosenLibrary : function( o ){
            var self = this;
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
            var chosenElem = $('#widgets-right .st-multi-select');
            if( typeof o !== "undefined" ){
                chosenElem = $( o ).find( '.st-multi-select' );
            }

            chosenElem.chosen({
                placeholder_text: 'Select Sites'
            }).change( function() {
                toggle_background_image($(this));
            }).each( function() {
                toggle_background_image($(this));
            });
        }
}

$(function(){

    stMultiSelect.init();

});

    window.stMultiSelect = stMultiSelect;


});
