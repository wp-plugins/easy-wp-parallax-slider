<?php
/*
  Plugin Name: Easy Parallax Slider
  Plugin URI: http://www.oscitasthemes.com
  Description: Easy Parallax Slider provides layered slider feature.
  Version: 1.6.3
  Author: oscitas
  Author URI: http://www.oscitasthemes.com
  License: Under the GPL v2 or later
 */

define('EPS_VERSION', '1.6.3');
define('EPS_BASE_URL', plugins_url('',__FILE__));
define('EPS_ASSETS_URL', EPS_BASE_URL . '/assets/');
define('EPS_BASE_DIR_LONG', dirname(__FILE__));
define('EPS_INC_DIR', EPS_BASE_DIR_LONG . '/inc/');

require_once('classes/epsSliderClass.php');
require_once('classes/epsSliderContentClass.php');
require_once('classes/slider/epsSliderImageClass.php');
require_once('classes/slider/epsAdminSliderClass.php');
require_once('classes/image/epsImageHelperClass.php');



class easyParallaxSlider {

    public $slider = null;
	private $filename= null;

    /**
     * Constructor
     */
    public function __construct() {
	    $pluginmenu=explode('/',plugin_basename(__FILE__));
	    $this->filename=$pluginmenu[0];
        // create the admin menu/page
	    add_action('init', array($this, 'eps_register_post_type'));
	    add_action('init', array($this, 'eps_register_taxonomy'));
        add_action('admin_menu', array($this, 'eps_register_admin_menu'));
	    add_action('admin_head', array($this,'ajaxurl'));

        add_shortcode('epsshortcode', array($this, 'eps_register_eps_shortcode'));
	    add_shortcode('eps-slider', array($this, 'eps_register_eps_shortcode'));
	    $this->eps_register_slide_types();
    }
	public function ajaxurl() {
		?>
		<script type="text/javascript">
			var epsajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		</script>

	<?php
	}

    /**
     * Add the menu page
     */
	private function eps_register_slide_types() {
		$image = new epsAdminSlider();
	}

	public function eps_register_admin_menu() {
        $title = apply_filters('eps_menu_title', "EPS Settings");

        $page = add_menu_page(
            $title,
            $title,
            'edit_others_posts',
            $this->filename,
            array( $this, 'eps_render_admin_page'),
            EPS_ASSETS_URL . 'images/osc-icon.png'
        );

        // ensure our JavaScript is only loaded on the easy Slider admin page
        add_action('admin_print_scripts-' . $page, array($this, 'eps_register_admin_scripts'));
        add_action('admin_print_styles-' . $page, array($this, 'eps_register_admin_styles'));
    }

    function eps_render_admin_page() {

	    $this->eps_admin_process();
        include (dirname(__FILE__)."/templates/eps_admin_page.php");

    }
	/**
	 * Handle slide uploads/changes
	 */
	public function eps_admin_process() {

		// default to the latest slider
		 $slider_id = $this->eps_find_slider('modified', 'DESC');

		// delete a slider
		if (isset($_GET['delete'])) {
			$this->eps_delete_slider(intval($_GET['delete']));
			 $slider_id = $this->eps_find_slider('date', 'DESC');
		}

		// create a new slider
		if (isset($_GET['add'])) {
			$this->eps_add_slider();
			$slider_id = $this->eps_find_slider('date', 'DESC');
		}

		if (isset($_REQUEST['id'])) {
			$slider_id = $_REQUEST['id'];
		}

		$this->eps_set_slider($slider_id);
	}
	/**
	 * Create a new slider
	 */
	private function eps_add_slider() {

		$defaults = array();

		// if possible, take a copy of the last edited slider settings in place of default settings
		if ($last_modified = $this->eps_find_slider('modified', 'DESC')) {
			$defaults = get_post_meta($last_modified, 'eps-slider_settings', true);
		}

		// insert the post
		$id = wp_insert_post(array(
				'post_title' => __("New Slider", $this->filename),
				'post_status' => 'publish',
				'post_type' => 'eps-slider'
			));

//		 use the default settings if we can't find anything more suitable.
		if (empty($defaults)) {
			$slider = new epsSliderClass($id);
			$defaults = $slider->_default_settings();
		}


		// insert the post meta
		add_post_meta($id, 'eps-slider_settings', $defaults, true);

		// create the taxonomy term, the term is the ID of the slider itself
		wp_insert_term($id, 'eps-slider');
	}

	/**
	 * Delete a slider (send it to trash)
	 */
	private function eps_delete_slider($id) {
		$slide = array(
			'ID' => $id,
			'post_status' => 'trash'
		);

		wp_update_post($slide);
	}

	/**
	 * Find a single slider ID. For example, last edited, or first published.
	 *
	 * @param string $orderby field to order.
	 * @param string $order direction (ASC or DESC).
	 * @return int slider ID.
	 */
	private function eps_find_slider($orderby, $order) {
		$args = array(
			'force_no_custom_order' => true,
			'post_type' => 'eps-slider',
			'num_posts' => 1,
			'post_status' => 'publish',
			'orderby' => $orderby,
			'order' => $order
		);

		$the_query = new WP_Query($args);

		while ($the_query->have_posts()) {
			$the_query->the_post();
			return $the_query->post->ID;
		}

		return false;
	}
	/**
	 * Set the current slider
	 */
	public function eps_set_slider($id) {
		$this->slider = $this->eps_create_slider($id);
	}

	/**
	 * Create a new slider based on the sliders type setting
	 */
	private function eps_create_slider($id) {

		return new epsSliderContentClass($id);
	}

	private function eps_all_easy_sliders() {
		$sliders = false;

		// list the tabs
		$args = array(
			'post_type' => 'eps-slider',
			'post_status' => 'publish',
			'orderby' => 'date',
			'order' => 'ASC',
			'posts_per_page' => -1
		);

		$the_query = new WP_Query($args);

		while ($the_query->have_posts()) {
			$the_query->the_post();

			$active = $this->slider->id == $the_query->post->ID ? true : false;

			$sliders[] = array(
				'active' => $active,
				'title' => get_the_title(),
				'id' => $the_query->post->ID
			);
		}

		return $sliders;
	}
	function eps_register_admin_scripts() {
		wp_enqueue_media();

		// plugin dependencies

        wp_enqueue_script('wp-color-picker');
		wp_enqueue_script('jquery-ui-core', array('jquery'));
		wp_enqueue_script('jquery-ui-sortable', array('jquery', 'jquery-ui-core'));
		wp_enqueue_script('eps-tipsy', EPS_ASSETS_URL . 'js/jquery.tipsy.js', array('jquery'), EPS_VERSION);
//		wp_enqueue_script('eps-colorpicker', EPS_ASSETS_URL . 'js/colorpicker.js', array('jquery', 'jquery-ui-core'), EPS_VERSION);
//		wp_enqueue_script('eps-cslider', EPS_ASSETS_URL . 'js/jquery.cslider.js', array('jquery', 'jquery-ui-core'), EPS_VERSION);
		wp_enqueue_script('eps-admin-script', EPS_ASSETS_URL . 'js/admin.js', array('jquery', 'eps-tipsy', 'media-upload'), EPS_VERSION);
		wp_enqueue_script('eps-admin-addslide', EPS_ASSETS_URL . 'images/image.js', array('eps-admin-script'), EPS_VERSION);
		wp_enqueue_script('eps-colorbox', EPS_ASSETS_URL . 'js/jquery.colorbox-min.js', array('jquery'), EPS_VERSION);
        wp_enqueue_script('eps-accordion', EPS_ASSETS_URL . 'js/accordion.js', array('jquery'), EPS_VERSION);

		// localise the JS
		wp_localize_script( 'eps-admin-script', 'epsscript', array(
				'url' => __("URL", $this->filename),
				'heading' => __("Heading", $this->filename),
				'content' => __("Content", $this->filename),
				'new_window' => __("New Window", $this->filename),
				'confirm' => __("Are you sure?", $this->filename),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'iframeurl' => plugins_url('',__FILE__) . '/templates/eps_preview.php',
				'useWithCaution' => __("Caution: This setting is for advanced developers only. If you're unsure, leave it checked.", $this->filename)
			));
    }

    function eps_register_admin_styles() {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('eps-admin-styles', EPS_ASSETS_URL . 'css/admin.css', false, EPS_VERSION);
//        wp_enqueue_style('eps-colorbox-styles', EPS_ASSETS_URL . 'colorbox/colorbox.css', false, EPS_VERSION);
//        wp_enqueue_style('eps-colorpicker', EPS_ASSETS_URL . 'css/colorpicker.css', false, EPS_VERSION);
        wp_enqueue_style('eps-tipsy-styles', EPS_ASSETS_URL . 'css/tipsy.css', false, EPS_VERSION);
	    wp_enqueue_style('eps-colorbox', EPS_ASSETS_URL . 'css/colorbox.css', false, EPS_VERSION);
        wp_enqueue_style('eps-accordion', EPS_ASSETS_URL . 'css/accordion.css', false, EPS_VERSION);

        do_action('eps_register_admin_styles');

    }

    /**
     * Get sliders. Returns a nicely formatted array of currently
     * published sliders.
     *
     * @return array all published sliders
     */
    private function eps_all_sliders() {
        $sliders = false;

        // list the tabs
        $args = array(
            'post_type' => 'eps-slider',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'ASC',
            'posts_per_page' => -1
        );

        $the_query = new WP_Query($args);

        while ($the_query->have_posts()) {
            $the_query->the_post();
            $active = $this->slider->id == $the_query->post->ID ? true : false;

            $sliders[] = array(
                'active' => $active,
                'title' => get_the_title(),
                'id' => $the_query->post->ID
            );
        }

        return $sliders;
    }

    /**
     * Register EPS post type
     */
    public function eps_register_post_type() {
        register_post_type('eps-slider', array(
            'query_var' => false,
            'rewrite' => false
        ));
    }

    /**
     * Register taxonomy to store slider => slides relationship
     */
    public function eps_register_taxonomy() {
        register_taxonomy( 'eps-slider', 'attachment', array(
            'hierarchical' => true,
            'public' => false,
            'query_var' => false,
            'rewrite' => false
        ));
    }

    /**
     * Initialise translations
     */
    public function eps_load_plugin_textdomain() {
        load_plugin_textdomain('eps', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    function eps_register_eps_shortcode($atts) {

	    extract(shortcode_atts(array('id' => null), $atts));

	    if ($id == null) return;

	    // we have an ID to work with
	    $slider = get_post($id);

	    // check the slider is published
	    if ($slider->post_status != 'publish') return false;

	    // lets go
	    $this->eps_set_slider($id);
	    $this->slider->eps_enqueue_scripts();
	    return $this->slider->eps_render_public_slides();
    }

}

$easyParallaxSlider = new easyParallaxSlider();