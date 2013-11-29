/**
 * Easy Parallax Slider
 */
(function ($) {
	$(function () {
		jQuery('.easy-parallax-slider .add-slide').live('click', function(event){
			event.preventDefault();

			// Create the media frame.
			file_frame = wp.media.frames.file_frame = wp.media({
				multiple: 'add',
				frame: 'post',
				library: {type: 'image'}
			});

			// When an image is selected, run a callback.
			file_frame.on('insert', function() {
				var selection = file_frame.state().get('selection');

				selection.map( function( attachment ) {

					attachment = attachment.toJSON();

					var data = {
						action: 'create_image_slide',
						slide_id: attachment.id,
						slider_id: window.parent.eps_id
					};

					jQuery.post(ajaxurl, data, function(response) {
						jQuery(".easy-parallax-slider .left table.append_slide").append(response);
                        apply_aacordion(jQuery('.easy-parallax-slider').find('.eps-colapsable-slider'));
                     jQuery('.easy-parallax-slider').find('.settingColorSelector').wpColorPicker();
					});
				});
			});

			file_frame.open();
		});
        jQuery('.easy-parallax-slider .add-bg').live('click', function(event){
			event.preventDefault();

			// Create the media frame.
			file_frame = wp.media.frames.file_frame = wp.media({
				frame: 'post',
				library: {type: 'image'}
			});

			// When an image is selected, run a callback.
			file_frame.on('insert', function() {
				var selection = file_frame.state().get('selection');

				selection.map( function( attachment ) {

					attachment = attachment.toJSON();

					var data = {
						action: 'create_bg',
						bg_id: attachment.id,
						slider_id: window.parent.eps_id
					};
                    console.log(eps_id)

					jQuery.post(epsajaxurl, data, function(response) {
                       // console.log(response);
                        if(response!==1){
                            jQuery("#bg-preview img").attr('src',response);
                        }
					});
				});
			});

			file_frame.open();
		});
	});

}(jQuery));