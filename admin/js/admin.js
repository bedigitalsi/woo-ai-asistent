/**
 * Admin JavaScript
 *
 * @package AI_Store_Assistant
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize WordPress color picker
		if ($.fn.wpColorPicker) {
			$('.asa-color-picker').wpColorPicker({
				change: function(event, ui) {
					// Color picker change handler
				},
				clear: function() {
					// Reset to default color
					$(this).wpColorPicker('color', '#0073aa');
				}
			});
		}
	});
})(jQuery);


