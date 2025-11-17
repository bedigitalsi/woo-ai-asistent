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

		// Image uploader
		var mediaUploader;
		var imageInput = $('#asa_chat_widget_image');
		var imagePreview = $('#asa_image_preview');
		var uploadButton = $('#asa_upload_image_button');
		var removeButton = $('#asa_remove_image_button');

		// Upload button click
		uploadButton.on('click', function(e) {
			e.preventDefault();

			// If the uploader object has already been created, reopen it
			if (mediaUploader) {
				mediaUploader.open();
				return;
			}

			// Create the media uploader
			mediaUploader = wp.media({
				title: 'Choose Assistant Avatar Image',
				button: {
					text: 'Use this image'
				},
				multiple: false,
				library: {
					type: ['image']
				}
			});

			// When an image is selected, run a callback
			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				imageInput.val(attachment.url);
				imagePreview.html('<img src="' + attachment.url + '" style="max-width: 100px; max-height: 100px; display: block; margin-bottom: 10px;" />');
				uploadButton.text('Change Image');
				removeButton.show();
			});

			// Open the uploader
			mediaUploader.open();
		});

		// Remove button click
		removeButton.on('click', function(e) {
			e.preventDefault();
			imageInput.val('');
			imagePreview.html('');
			uploadButton.text('Upload Image');
			removeButton.hide();
		});

		// Show remove button if image exists on page load
		if (imageInput.val()) {
			removeButton.show();
		}
	});
})(jQuery);


