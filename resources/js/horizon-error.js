(function($){
	var market = window.location.origin;
	market = market.replace(/.*?:\/\//g, "");

	var horizonTrack = {
		init: function(){
			gaTracker.track('UA-39848203-3','Sailthru Horizon Check','Bad Setup','horizon.'+ market);
		}
	};

	var gaTracker = {
		track: function(account, category, event, label) {
			if( _gaq !== undefined){
				_gaq.push(
					[ '_setAccount', account ],
					[
						'_trackEvent',
						category,
						event,
						label
					]
				);
			}
		}
	};

	$(document).ready(function(){
		horizonTrack.init();
	});
})( typeof Zepto !== "undefined" ? Zepto : jQuery);