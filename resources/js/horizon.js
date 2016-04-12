<!-- Sailthru Horizon -->
(function($){
	(function() {
		function loadHorizon() {
			var s = document.createElement('script');
			s.type = 'text/javascript';
			s.async = true;
			s.src = location.protocol + '//ak.sail-horizon.com/horizon/v1.js';
			var x = document.getElementsByTagName('script')[0];
			x.parentNode.insertBefore(s, x);
		}
		loadHorizon();
		var oldOnLoad = window.onload;
		window.onload = function() {
			if (typeof oldOnLoad === 'function') {
				oldOnLoad();
			}
			var market = window.location.origin;
			market = market.replace(/.*?:\/\//g, "");
			Sailthru.setup({
				domain: 'horizon.'+ market
			});
		};
	})();
})( typeof Zepto !== "undefined" ? Zepto : jQuery);