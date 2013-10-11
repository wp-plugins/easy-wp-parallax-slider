<?php
class epsAdminSlider extends epsSliderImageClass {
	/**
	 * Register slide type
	 */
	private $filename= null;
	public function __construct() {
		$pluginmenu=explode('/',plugin_basename(__FILE__));
		$this->filename=$pluginmenu[0];
		add_filter('eps_get_image_slide', array($this, 'eps_get_slide'), 10, 2);
		add_action('eps_save_image_slide', array($this, 'eps_save_slide'), 5, 3);
		add_action('wp_ajax_create_image_slide', array($this, 'eps_ajax_create_slide'));
		add_action('wp_ajax_create_bg', array($this, 'eps_ajax_create_background'));
	}

	/**
	 * Create a new slide and echo the admin HTML
	 */
	public function eps_ajax_create_slide() {
		$slide_id = intval($_POST['slide_id']);
		$slider_id = intval($_POST['slider_id']);

		$this->eps_set_slide($slide_id);
		$this->eps_set_slider($slider_id);
		$this->eps_tag_slide_to_slider();

		$this->eps_add_or_update_or_delete_meta($slide_id, 'type', 'image');

		echo $this->eps_get_admin_slide();
		die();
	}
		public function eps_ajax_create_background() {
		$bg_id = intval($_POST['bg_id']);
		$slider_id = intval($_POST['slider_id']);

		$full = wp_get_attachment_image_src($bg_id, 'full');
		if(count($full)){
			$this->eps_add_or_update_or_delete_bg_meta($slider_id, 'bg', $full[0]);
			//echo $url= get_post_meta($slider_id, 'eps-slider_bg', true); exit;
			echo $full[0];
		}else{
			echo 1;
		}
		die();
	}
	/**
	 * Return the HTML used to display this slide in the admin screen
	 *
	 * @return string slide html
	 */
	protected function eps_get_admin_slide() {
		// get some slide settings
		$thumb   = $this->eps_get_thumb();
		$full    = wp_get_attachment_image_src($this->slide->ID, 'full');
		$url     = get_post_meta($this->slide->ID, 'eps-slider_url', true);
		$target  = get_post_meta($this->slide->ID, 'eps-slider_new_window', true) ? 'checked=checked' : '';
		$heading = get_post_meta($this->slide->ID, 'eps-slider_heading', true);
		$caption = htmlentities($this->slide->post_excerpt, ENT_QUOTES, 'UTF-8');

		// localisation
		$str_heading    = __("Heading", $this->filename);
		$str_content    = __("Content", $this->filename);
		$str_new_window = __("New Window", $this->filename);
		$str_url        = __("Read More Url", $this->filename);

		// slide row HTML
		$row  = "<tr class='slide'>";
		$row .= "    <td class='col-1'>";
		$row .= "        <div class='thumb' style='background-image: url({$thumb})'>";
		$row .= "            <a class='delete-slide confirm' href='?page=".$this->filename."&id={$this->slider->ID}&deleteSlide={$this->slide->ID}'>x</a>";
		$row .= "            <span class='slide-details'>Image {$full[1]} x {$full[2]}</span>";
		$row .= "        </div>";
		$row .= "    </td>";
		$row .= "    <td class='col-2'>";
		$row .= "        <input class='url' type='text' name='attachment[{$this->slide->ID}][heading]' placeholder='{$str_heading}' value='{$heading}'/>";
		$row .= "        <textarea name='attachment[{$this->slide->ID}][post_excerpt]' placeholder='{$str_content}'>{$caption}</textarea>";
		$row .= "        <input class='url' type='url' name='attachment[{$this->slide->ID}][url]' placeholder='{$str_url}' value='{$url}' />";
		$row .= "        <div class='new_window'>";
		$row .= "            <label>{$str_new_window}<input type='checkbox' name='attachment[{$this->slide->ID}][new_window]' {$target} /></label>";
		$row .= "        </div>";
		$row .= "        <input type='hidden' name='attachment[{$this->slide->ID}][type]' value='image' />";
		$row .= "        <input type='hidden' class='menu_order' name='attachment[{$this->slide->ID}][menu_order]' value='{$this->slide->menu_order}' />";
		$row .= "    </td>";
		$row .= "</tr>";

		return $row;
	}
	protected function eps_get_public_slide() {
		// get the image url (and handle cropping)
		$imageHelper = new epsImageHelperClass(
			$this->slide->ID,
			$this->settings['width'],
			$this->settings['height'],
			isset($this->settings['smartCrop']) ? $this->settings['smartCrop'] : 'false'
		);

		$url = $imageHelper->eps_get_image_url();

		if (is_wp_error($url)) {
			return ""; // bail out here. todo: look at a way of notifying the admin
		}

		// store the slide details
		$slide = array(
			'thumb' => $url,
			'url' => get_post_meta($this->slide->ID, 'eps-slider_url', true),
			'heading' => get_post_meta($this->slide->ID, 'eps-slider_heading', true),
			'alt' => get_post_meta($this->slide->ID, '_wp_attachment_image_alt', true),
			'target' => get_post_meta($this->slide->ID, 'eps-slider_new_window', true) ? '_blank' : '_self',
			'content' => html_entity_decode($this->slide->post_excerpt, ENT_NOQUOTES, 'UTF-8'),
			'content_raw' => $this->slide->post_excerpt
		);

		// return the slide HTML
		return $this->eps_get_parallax_slider_markup($slide);
	}
	private function eps_get_parallax_slider_markup($slide) {
		if ($this->settings['topPer'] != 'false') {
			$topPer = $this->settings['topPer'];
		} else {
			$topPer = "12";
		}

		$html = " <div class='da-slide'>";
		$html .= " <div class='da-slide-heading-content'>";
		if (strlen($slide['heading'])) {
			$html .= "  <h2>{$slide['heading']}</h2>";
		}
		if (strlen($slide['content'])) {
			$html .= "<p>{$slide['content']}</p>";

		}
		$html .= " </div>";
		if (strlen($slide['url'])) {
			$html .= "<a href='{$slide['url']}' target='{$slide['target']}' class='da-link'>Read More</a>";
		}
		if ($this->settings['height']) {
			$height = " height='{$this->settings['height']}px' ";
		}

		if ($this->settings['width']) {
			$width = " width='{$this->settings['width']}px' ";
		}

		$html .="<div class='da-img' style='top:{$topPer}%;line-height: 0;'><img {$height} {$width} src='{$slide['thumb']}' alt='{$slide['alt']}' /></div>";
		$html .='</div>';

		return $html;
	}

	protected function eps_save($fields) {
		// update the slide
		wp_update_post(array(
				'ID' => $this->slide->ID,
				'post_excerpt' => $fields['post_excerpt'],
				'menu_order' => $fields['menu_order']
			));

		// store the URL as a meta field against the attachment
		$this->eps_add_or_update_or_delete_meta($this->slide->ID, 'url', $fields['url']);
		$this->eps_add_or_update_or_delete_meta($this->slide->ID, 'heading', $fields['heading']);

		// store the 'new window' setting
		$new_window = isset($fields['new_window']) && $fields['new_window'] == 'on' ? 'true' : 'false';

		$this->eps_add_or_update_or_delete_meta($this->slide->ID, 'new_window', $new_window);
	}
}