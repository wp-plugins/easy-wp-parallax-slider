<?php
class epsAdminSlider extends epsSliderImageClass {
    /**
     * Register slide type
     */
    private $filename= null;
    public function __construct() {
        session_start();
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

        $font_family_array=array('Georgia, serif','Palatino Linotype, Book Antiqua, Palatino','Times New Roman','Arial, Helvetica','Arial Black, Gadget','Comic Sans MS, cursive','Impact, Charcoal','Lucida Sans Unicode','Tahoma, Geneva','Trebuchet MS','Verdana, Geneva','Courier New, Courier, monospace','Lucida Console, Monaco');

        $font_style_array=array('bold','italic','underline');
        // get some slide settings
        $thumb   = $this->eps_get_thumb();
        $full    = wp_get_attachment_image_src($this->slide->ID, 'full');
        if ($this->settings['load_from_new'] ==  false || !$this->settings['load_from_new']) {
            $url     = get_post_meta($this->slide->ID, 'eps-slider_url', true);
            $readmore     = get_post_meta($this->slide->ID, 'eps-slider_readmore', true);
            $target  = get_post_meta($this->slide->ID, 'eps-slider_new_window', true) ? 'checked=checked' : '';
            $heading = get_post_meta($this->slide->ID, 'eps-slider_heading', true);
            $caption = htmlentities($this->slide->post_excerpt, ENT_QUOTES, 'UTF-8');
        } else {
            $url     = get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_url', true);
            $readmore     = get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore', true);
            $target  = get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_new_window', true) ? 'checked=checked' : '';
            $heading = get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_heading', true);
            $caption = get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_caption', true);
        }
        $flag = get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_flag', true);
        $heading_font_size=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_heading_font_size', true);
        $readmore_font_size=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore_font_size', true);
        $content_font_size=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_content_font_size', true);
        $heading_font_family=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_heading_font_family', true);
        $readmore_font_family=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore_font_family', true);
        $content_font_family=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_content_font_family', true);
        $heading_font_style=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_heading_font_style', true);
        $readmore_font_style=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore_font_style', true);
        $content_font_style=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_content_font_style', true);
        $heading_font_color=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_heading_font_color', true);
        $content_font_color=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_content_font_color', true);
        $content_line_height=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_content_line_height', true);
        $readmore_font_color=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore_font_color', true);
        $readmore_bg_color=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore_bg_color', true);
        $readmore_border_color=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore_border_color', true);
        $image_top=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_image_top', true);
        $image_left=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_image_left', true);
        $image_width=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_image_width', true);
        $image_height=get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_image_height', true);

        // localisation
        $str_heading    = __("Heading", $this->filename);
        $str_content    = __("Content", $this->filename);
        $str_new_window = __("New Window", $this->filename);
        $str_url        = __("Read More Url", $this->filename);
        $str_readmore        = __("Read More Text", $this->filename);
        $str_font_size = __("Font Size", $this->filename);
        $str_font_family = __("Font Family", $this->filename);
        $str_font_style = __("Font Style", $this->filename);
        $str_font_color = __("Font Color", $this->filename);
        $str_bg_color = __("BG Color", $this->filename);
        $str_border_color = __("Border Color", $this->filename);
        $str_top = __("Top", $this->filename);
        $str_left = __("Left", $this->filename);
        $str_width = __("Width", $this->filename);
        $str_height = __("Height", $this->filename);
        $str_line_height = __("Line Height", $this->filename);
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
        $row .= "        <textarea name='attachment[{$this->slide->ID}][caption]' placeholder='{$str_content}'>{$caption}</textarea>";
        $row .= "        <input class='url' type='url' name='attachment[{$this->slide->ID}][url]' placeholder='{$str_url}' value='{$url}' />";
        $row .= "        <div class='new_window'>";
        $row .= "            <label>{$str_new_window}<input type='checkbox' name='attachment[{$this->slide->ID}][new_window]' {$target} /></label>";
        $row .= "        </div>";
        $row .= "        <input class='url' type='text' name='attachment[{$this->slide->ID}][readmore]' placeholder='{$str_readmore}' value='{$readmore}' />";
        $row .= "        <input type='hidden' name='attachment[{$this->slide->ID}][type]' value='image' />";
        $row .= "        <input type='hidden' class='menu_order' name='attachment[{$this->slide->ID}][menu_order]' value='{$this->slide->menu_order}' />";
        $row .= "    <div class='eps-colapsable-slider'>";
        $row.='<h3 class="slide-settings">Slide Settings</h3>';
        $row .= "<div><table class='eps_slide_setting'>";
        $row.='<tr><td><h4 class="slide-settings">Slide Heading Settings</h4></td><td><h4 class="slide-settings">Slide Read More Settings</h4></td></tr>';
        $row .= "<tr><td><label>{$str_font_size}</label><input class='option' type='number' min='1' max='100' step='1' name='attachment[{$this->slide->ID}][heading_font_size]' value='{$heading_font_size}' /></td><td><label>{$str_font_size}</label><input class='option' type='number' min='1' max='100' step='1' name='attachment[{$this->slide->ID}][readmore_font_size]' value='{$readmore_font_size}' /></td></tr>";
        $row .= "<tr><td><label>{$str_font_family}</label> <select name='attachment[{$this->slide->ID}][heading_font_family]'>";
        foreach($font_family_array as $heading_font_family_value){
            $row.='<option value="'.$heading_font_family_value.'" '.($heading_font_family==$heading_font_family_value?'selected="selected"':'').'>'.$heading_font_family_value.'</option>';
        }
        $row.="</select></td><td><label>{$str_font_family}</label> <select name='attachment[{$this->slide->ID}][readmore_font_family]'>";
        foreach($font_family_array as $heading_font_family_value){
            $row.='<option value="'.$heading_font_family_value.'" '.($readmore_font_family==$heading_font_family_value?'selected="selected"':'').'>'.$heading_font_family_value.'</option>';
        }
        $row.="</select></td></tr>";
        $row .= "<tr><td><label>{$str_font_style}</label> <select name='attachment[{$this->slide->ID}][heading_font_style]'>";
        foreach($font_style_array as $font_style_value){
            $row.='<option value="'.$font_style_value.'" '.($heading_font_style==$font_style_value?'selected="selected"':'').'>'.$font_style_value.'</option>';
        }
        $row.="</select></td><td><label>{$str_font_style}</label> <select name='attachment[{$this->slide->ID}][readmore_font_style]'>";
        foreach($font_style_array as $font_style_value){
            $row.='<option value="'.$font_style_value.'" '.($readmore_font_style==$font_style_value?'selected="selected"':'').'>'.$font_style_value.'</option>';
        }
        $row.="</select></td></tr>";
        $row .= "<tr><td><label>{$str_font_color}</label>
                <input  class='option settingColorSelector' type='text' name='attachment[{$this->slide->ID}][heading_font_color]' value='{$heading_font_color}'/>
                </td>";
        $row .= "<td><label>{$str_font_color}</label>
                 <input  class='option settingColorSelector' type='text' name='attachment[{$this->slide->ID}][readmore_font_color]' value='{$readmore_font_color}'/>
                </td><tr>";



        $row.='<tr><td><h4 class="slide-settings">Slide Content Settings</h4></td>';
        $row.="<td><label>{$str_bg_color}</label><input  class='option settingColorSelector' type='text' name='attachment[{$this->slide->ID}][readmore_bg_color]' value='{$readmore_bg_color}'/></td></td></tr>";
        $row .= "<tr><td><label>{$str_font_size}</label><input class='option' type='number' min='1' max='100' step='1' name='attachment[{$this->slide->ID}][content_font_size]' value='{$content_font_size}' /></td><td><label>{$str_border_color}</label>
                 <input  class='option settingColorSelector' type='text' name='attachment[{$this->slide->ID}][readmore_border_color]' value='{$readmore_border_color}'/></td></tr>";
        $row .= "<tr><td><label>{$str_font_family}</label> <select name='attachment[{$this->slide->ID}][content_font_family]'>";
        foreach($font_family_array as $content_font_family_value){
            $row.='<option value="'.$content_font_family_value.'" '.($content_font_family==$content_font_family_value?'selected="selected"':'').'>'.$content_font_family_value.'</option>';
        }
        $row.="</select></td><td><h4 class='slide-settings'>Slide Image Settings</h4></td></tr>";
        $row .= "<tr><td><label>{$str_font_style}</label> <select name='attachment[{$this->slide->ID}][content_font_style]'>";
        foreach($font_style_array as $font_style_value){
            $row.='<option value="'.$font_style_value.'" '.($content_font_style==$font_style_value?'selected="selected"':'').'>'.$font_style_value.'</option>';
        }
        $row.="</select></td><td><label>{$str_top}</label><input class='option' type='number' min='1' max='100' step='1' name='attachment[{$this->slide->ID}][image_top]' value='{$image_top}' />%</td></tr>";
        $row .= "<tr><td><label>{$str_font_color}</label>
                <input  class='option settingColorSelector' type='text' name='attachment[{$this->slide->ID}][content_font_color]' value='{$content_font_color}'/>
                </td>";
        $row .= "<td><label>{$str_left}</label><input class='option' type='number' min='1' max='100' step='1' name='attachment[{$this->slide->ID}][image_left]' value='{$image_left}' />%
                </td></tr>";
        $row .= "<tr><td><label>{$str_line_height}</label>
                <input  class='option' type='text' name='attachment[{$this->slide->ID}][content_line_height]' value='{$content_line_height}'/>
                </td>";
        $row .= "<td><label>{$str_width}</label><input class='option' type='text' name='attachment[{$this->slide->ID}][image_width]' value='{$image_width}' />px &nbsp;<label>{$str_height}</label><input class='option' type='text'  name='attachment[{$this->slide->ID}][image_height]' value='{$image_height}' />px
                </td></tr>";
        $row .= "    </table></div>";
        $row .= "    </div>";
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

        if ($this->settings['load_from_new'] ==  false || !$this->settings['load_from_new']) {
            $url1     = get_post_meta($this->slide->ID, 'eps-slider_url', true);
            $readmore     = get_post_meta($this->slide->ID, 'eps-slider_readmore', true);
            $target  = get_post_meta($this->slide->ID, 'eps-slider_new_window', true) ? 'checked=checked' : '';
            $heading = get_post_meta($this->slide->ID, 'eps-slider_heading', true);
            $caption = htmlentities($this->slide->post_excerpt, ENT_QUOTES, 'UTF-8');
        } else {
            $url1     = get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_url', true);
            $readmore     = get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore', true);
            $target  = get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_new_window', true) ? 'checked=checked' : '';
            $heading = get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_heading', true);
            $caption = get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_caption', true);
        }


        // store the slide details
        $slide = array(
            'thumb' => $url,
            'url' => $url1,
            'readmore' => $readmore,
            'heading' => $heading,
            'alt' => get_post_meta($this->slider->ID, '_wp_attachment_'.$this->slide->ID.'_image_alt', true),
            'target' => $target,
            'content' => $caption,
            'content_raw' =>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_caption', true),
            'heading_font_size'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_heading_font_size', true),
            'readmore_font_size'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore_font_size', true),
            'content_font_size'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_content_font_size', true),
            'heading_font_family'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_heading_font_family', true),
            'readmore_font_family'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore_font_family', true),
            'content_font_family'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_content_font_family', true),
            'heading_font_style'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_heading_font_style', true),
            'readmore_font_style'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore_font_style', true),
            'content_font_style'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_content_font_style', true),
            'heading_font_color'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_heading_font_color', true),
            'content_font_color'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_content_font_color', true),
            'content_line_height'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_content_line_height', true),
            'readmore_font_color'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore_font_color', true),
            'readmore_bg_color'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore_bg_color', true),
            'readmore_border_color'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_readmore_border_color', true),
            'image_top'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_image_top', true),
            'image_left'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_image_left', true),
            'image_width'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_image_width', true),
            'image_height'=>get_post_meta($this->slider->ID, 'eps-slider_'.$this->slide->ID.'_image_height', true)
        );

        // return the slide HTML
        return $this->eps_get_parallax_slider_markup($slide).$this->eps_get_parallax_slider_markup_style($slide);
    }

    private function eps_get_parallax_slider_markup_style($slide){
        $heading_font_size=($slide['heading_font_size']!=false || $slide['heading_font_size']!=0)?'font-size:'.$slide['heading_font_size'].'px;':'';
        $heading_font_family=($slide['heading_font_family']!=false || $slide['heading_font_family']!='')?'font-family:'.$slide['heading_font_family'].';':'';
        $heading_font_color=($slide['heading_font_color']!=false || $slide['heading_font_color']!='')?'color:'.$slide['heading_font_color'].';':'';
        if($slide['heading_font_style']=='italic'){
            $heading_font_style='font-style:'.$slide['heading_font_style'].';';
        }
        if($slide['heading_font_style']=='underline'){
            $heading_font_style='text-decoration:'.$slide['heading_font_style'].';';
        }
        if($slide['heading_font_style']=='bold'){
            $heading_font_style='font-weight:'.$slide['heading_font_style'].';';
        }
        $content_font_size=($slide['content_font_size']!=false || $slide['content_font_size']!='')?'font-size:'.$slide['content_font_size'].'px;':'';
        $content_font_family='font-family:'.$slide['content_font_family'].';';
        $content_font_color=($slide['content_font_color']!=false || $slide['content_font_color']!='')?'color:'.$slide['content_font_color'].';':'';
        $content_line_height=($slide['content_line_height']!=false || $slide['content_line_height']!='')?'line-height:'.$slide['content_line_height'].';':'';
        if($slide['content_font_style']=='italic'){
            $content_font_style='font-style:'.$slide['content_font_style'].';';
        }
        if($slide['content_font_style']=='underline'){
            $content_font_style='text-decoration:'.$slide['content_font_style'].';';
        }
        if($slide['content_font_style']=='bold'){
            $content_font_style='font-weight:'.$slide['content_font_style'].';';
        }
        $readmore_font_size=($slide['readmore_font_size']!=false || $slide['readmore_font_size']!='')?'font-size:'.$slide['readmore_font_size'].'px;':'';
        $readmore_font_family=($slide['readmore_font_family']!=false || $slide['readmore_font_family']!='')?'font-family:'.$slide['readmore_font_family'].';':'';
        $readmore_font_color=($slide['readmore_font_color']!=false || $slide['readmore_font_color']!='')?'color:'.$slide['readmore_font_color'].';':'';
        if($slide['readmore_font_style']=='italic'){
            $readmore_font_style='font-style:'.$slide['readmore_font_style'].';';
        }
        if($slide['readmore_font_style']=='underline'){
            $readmore_font_style='text-decoration:'.$slide['readmore_font_style'].';';
        }
        if($slide['readmore_font_style']=='bold'){
            $readmore_font_style='font-weight:'.$slide['readmore_font_style'].';';
        }
        $readmore_border_color=($slide['readmore_border_color']!=false || $slide['readmore_border_color']!='')?'border-color:'.$slide['readmore_border_color'].';':'';
        $readmore_bg_color=($slide['readmore_bg_color']!=false || $slide['readmore_bg_color']!='')?'background:'.$slide['readmore_bg_color'].';':'';
//        $readmore_hoverbgcolor='background-color:'.$this->colourBrightness($slide['readmore_bg_color'],0.80).';';

        if( $slide['image_top']== 'true'){
            $topPer = "top:0;";
        } elseif($slide['image_top']!= false || $slide['image_top']!= 0){

            $topPer = " top:{$slide['image_top']}%; ";

        } elseif ($this->settings['topPer'] != 'false' || $this->settings['topPer']!=0) {
            $topPer = " top:{$this->settings['topPer']}%; ";
        }
        else {
            $topPer = "top:0;";
        }
        if( $slide['image_left']== 'true'){
            $leftPerval = "left:0;";
            $leftPer=0;
        } elseif($slide['image_left']!= false || $slide['image_left']!= 0){

            $leftPerval = " left:{$slide['image_left']}%; ";
            $leftPer=$slide['image_left'];
        } elseif ($this->settings['leftPer'] != false || $this->settings['leftPer']!=0) {
            $leftPerval = " left:{$this->settings['leftPer']}%; ";
            $leftPer=$this->settings['leftPer'];
        }
        else {
            $leftPerval = "left:0;";
            $leftPer=0;
        }

        if( $slide['image_height']== 'true'){
            $height = " height: auto;";
        } elseif($slide['image_height']!= false || $slide['image_height']!= 0){
            $height = " height:{$slide['image_height']}px; ";
        } elseif ($this->settings['height'] != false || $this->settings['height']!= 0){
            $height = " height:{$this->settings['height']}px; ";
        } else{
            $height = " height: auto;";
        }
        if( $slide['image_width']== 'true'){
            $width = " width: 100%;";
        } elseif($slide['image_width']!= false ||  $slide['image_width']!= 0){
            $width = " width:{$slide['image_width']}px; ";
        } elseif ($this->settings['width']!= false || $this->settings['width']!= 0) {
            $width = " width:{$this->settings['width']}px; ";
        } else{
            $width = " width: 100%;";
        }

        if ($this->settings['navigation_color']!= false || $this->settings['navigation_color']!= '') {
            $navigation_color = $this->settings['navigation_color'];
        } else {
            $navigation_color = '#E4B42D';
        }

        if ($this->settings['pager_color']!= false || $this->settings['pager_color']!= '') {
            $pager_color = $this->settings['pager_color'];
        } else {
            $pager_color = '#E4B42D';
        }

        $leftper5=intval($leftPer)-5;
        $leftper6=intval($leftPer)-10;
        $leftper7=intval($leftPer)+10;
        $css .=<<<EOF

     .eps-custom-{$this->slider->ID} #da-img-{$this->slide->ID}{
        {$height}
        {$width}
        {$leftPerval}
        {$topPer}
        }
        .eps-custom-{$this->slider->ID} #da-slide-heading-{$this->slide->ID} h2{
        {$heading_font_size}
        {$heading_font_family}
        {$heading_font_color}
        {$heading_font_style}
        }
         .eps-custom-{$this->slider->ID} #da-slide-heading-{$this->slide->ID} p{
        {$content_font_size}
        {$content_font_family}
        {$content_font_color}
        {$content_font_style}
        {$content_line_height}
        }
         .eps-custom-{$this->slider->ID} #da-link-{$this->slide->ID}{
        {$readmore_font_size}
        {$readmore_font_family}
        {$readmore_font_color}
        {$readmore_font_style}
        {$readmore_border_color}
        {$readmore_bg_color}
        }
       .eps-custom-{$this->slider->ID} .da-slide-fromright #da-img-{$this->slide->ID}{
	-webkit-animation: fromRightAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.8s both;
	-moz-animation: fromRightAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.8s both;
	-o-animation: fromRightAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.8s both;
	-ms-animation: fromRightAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.8s both;
	animation: fromRightAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.8s both;
}
.eps-custom-{$this->slider->ID} .da-slide-fromleft #da-img-{$this->slide->ID}{
	-webkit-animation: fromLeftAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.6s both;
	-moz-animation: fromLeftAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.6s both;
	-o-animation: fromLeftAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.6s both;
	-ms-animation: fromLeftAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.6s both;
	animation: fromLeftAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.6s both;
}
.eps-custom-{$this->slider->ID} .da-slide-toright #da-img-{$this->slide->ID}{
	-webkit-animation: toRightAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in both;
	-moz-animation: toRightAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in both;
	-o-animation: toRightAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in both;
	-ms-animation: toRightAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in both;
	animation: toRightAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in both;
}
.eps-custom-{$this->slider->ID} .da-slide-toleft #da-img-{$this->slide->ID}{
	-webkit-animation: toLeftAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.6s both;
	-moz-animation: toLeftAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.6s both;
	-o-animation: toLeftAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.6s both;
	-ms-animation: toLeftAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.6s both;
	animation: toLeftAnim{$this->slider->ID}{$this->slide->ID} 0.6s ease-in 0.6s both;
}
         @-webkit-keyframes fromRightAnim{$this->slider->ID}{$this->slide->ID}{
						0%{ left: 110%; opacity: 0; }
						100%{ left: {$leftPer}%; opacity: 1; }
					}
					@-moz-keyframes fromRightAnim{$this->slider->ID}{$this->slide->ID}{
						0%{ left: 110%; opacity: 0; }
						100%{ left: {$leftPer}%; opacity: 1; }
					}
					@-o-keyframes fromRightAnim{$this->slider->ID}{$this->slide->ID}{
						0%{ left: 110%; opacity: 0; }
						100%{ left: {$leftPer}%; opacity: 1; }
					}
					@-ms-keyframes fromRightAnim{$this->slider->ID}{$this->slide->ID}{
						0%{ left: 110%; opacity: 0; }
						100%{ left: {$leftPer}%; opacity: 1; }
					}
					@keyframes fromRightAnim{$this->slider->ID}{$this->slide->ID}{
						0%{ left: 110%; opacity: 0; }
						100%{ left: {$leftPer}%; opacity: 1; }
					}
					@-webkit-keyframes fromLeftAnim{$this->slider->ID}{$this->slide->ID}{
						0%{ left: -110%; opacity: 0; }
						100%{ left: {$leftPer}%; opacity: 1; }
					}
					@-moz-keyframes fromLeftAnim{$this->slider->ID}{$this->slide->ID}{
						0%{ left: -110%; opacity: 0; }
						100%{ left: {$leftPer}%; opacity: 1; }
					}
					@-o-keyframes fromLeftAnim{$this->slider->ID}{$this->slide->ID}{
						0%{ left: -110%; opacity: 0; }
						100%{ left: {$leftPer}%; opacity: 1; }
					}
					@-ms-keyframes fromLeftAnim{$this->slider->ID}{$this->slide->ID}{
						0%{ left: -110%; opacity: 0; }
						100%{ left: {$leftPer}%; opacity: 1; }
					}
					@keyframes fromLeftAnim{$this->slider->ID}{$this->slide->ID}{
						0%{ left: -110%; opacity: 0; }
						100%{ left: {$leftPer}%; opacity: 1; }
					}
					@-webkit-keyframes toRightAnim{$this->slider->ID}{$this->slide->ID}{
					0%{ left: {$leftPer}%;  opacity: 1; }
					30%{ left: {$leftper5}%;  opacity: 1; }
					100%{ left: 100%; opacity: 0; }
				}
				@-moz-keyframes toRightAnim{$this->slider->ID}{$this->slide->ID}{
					0%{ left: {$leftPer}%;  opacity: 1; }
					30%{ left: {$leftper5}%;  opacity: 1; }
					100%{ left: 100%; opacity: 0; }
				}
				@-o-keyframes toRightAnim{$this->slider->ID}{$this->slide->ID}{
					0%{ left: {$leftPer}%;  opacity: 1; }
					30%{ left: {$leftper5}%;  opacity: 1; }
					100%{ left: 100%; opacity: 0; }
				}
				@-ms-keyframes toRightAnim{$this->slider->ID}{$this->slide->ID}{
					0%{ left: {$leftPer}%;  opacity: 1; }
					30%{ left: {$leftper5}%;  opacity: 1; }
					100%{ left: 100%; opacity: 0; }
				}
				@keyframes toRightAnim{$this->slider->ID}{$this->slide->ID}{
					0%{ left: {$leftPer}%;  opacity: 1; }
					30%{ left: {$leftper5}%;  opacity: 1; }
					100%{ left: 100%; opacity: 0; }
				}
				@-webkit-keyframes toLeftAnim{$this->slider->ID}{$this->slide->ID}{
					0%{ left: {$leftPer}%;  opacity: 1; }
					40%{ left: {$leftper7}%;  opacity: 1; }
					90%{ left: 0%;  opacity: 0; }
					100%{ left: -50%; opacity: 0; }
				}
				@-moz-keyframes toLeftAnim{$this->slider->ID}{$this->slide->ID}{
					0%{ left: {$leftPer}%;  opacity: 1; }
					40%{ left: {$leftper7}%;  opacity: 1; }
					90%{ left: 0%;  opacity: 0; }
					100%{ left: -50%; opacity: 0; }
				}
				@-o-keyframes toLeftAnim{$this->slider->ID}{$this->slide->ID}{
					0%{ left: {$leftPer}%;  opacity: 1; }
					40%{ left: {$leftper7}%;  opacity: 1; }
					90%{ left: 0%;  opacity: 0; }
					100%{ left: -50%; opacity: 0; }
				}
				@-ms-keyframes toLeftAnim{$this->slider->ID}{$this->slide->ID}{
					0%{ left: {$leftPer}%;  opacity: 1; }
					40%{ left: {$leftper7}%;  opacity: 1; }
					90%{ left: 0%;  opacity: 0; }
					100%{ left: -50%; opacity: 0; }
				}
				@keyframes toLeftAnim{$this->slider->ID}{$this->slide->ID}{
					0%{ left: {$leftPer}%;  opacity: 1; }
					40%{ left: {$leftper7}%;  opacity: 1; }
					90%{ left: 0%;  opacity: 0; }
					100%{ left: -50%; opacity: 0; }
				}

				#da-slider-eps_{$this->slider->ID} .da-dots span {
				    background: none repeat scroll 0 0 {$pager_color};
				}

				#da-slider-eps_{$this->slider->ID} .da-arrows span {
				    background: none repeat scroll 0 0 {$navigation_color};
				}

EOF;

        if (strlen($css)) {
            return "<style type='text/css'>{$css}\n    </style>";

        }
    }
    private function eps_get_parallax_slider_markup($slide) {


        $html = " <div class='da-slide'>";
        $html .= " <div  id='da-slide-heading-".$this->slide->ID."' class='da-slide-heading-content'>";
        if (strlen($slide['heading'])) {
            $html .= "  <h2>{$slide['heading']}</h2>";
        }
        if (strlen($slide['content'])) {
            $html .= "<p class='da-slide-content'>{$slide['content']}</p>";
        }
        $html .= "</div>";
        if (strlen($slide['url'])) {
            $readmoretext=strlen($slide['readmore']) ? $slide['readmore']:'Read More';
            $html .= "<a href='{$slide['url']}' target='{$slide['target']}' id='da-link-".$this->slide->ID."' class='da-link'>{$readmoretext}</a>";
        }
        $html=trim($html);
        $html .="<div id='da-img-".$this->slide->ID."' class='da-img'><img src='{$slide['thumb']}' alt='{$slide['alt']}' /></div>";
        $html .='</div>';

        return trim($html);
    }

    protected function eps_save($fields) {
        // update the slide
        wp_update_post(array(
            'ID' => $this->slide->ID,
            'post_excerpt' => $fields['post_excerpt'],
            'menu_order' => $fields['menu_order']
        ));

        // store the URL as a meta field against the attachment
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_url', $fields['url']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_readmore', $fields['readmore']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID, $this->slide->ID.'_heading', $fields['heading']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_heading_font_size', $fields['heading_font_size']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_caption', $fields['caption']);

        // store the 'new window' setting
        $new_window = isset($fields['new_window']) && $fields['new_window'] == 'on' ? 'true' : 'false';

        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_new_window', $new_window);

        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_readmore_font_size', $fields['readmore_font_size']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_content_font_size', $fields['content_font_size']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_heading_font_family', $fields['heading_font_family']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_readmore_font_family', $fields['readmore_font_family']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_content_font_family', $fields['content_font_family']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_heading_font_style', $fields['heading_font_style']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_readmore_font_style', $fields['readmore_font_style']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_content_font_style', $fields['content_font_style']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_heading_font_color', $fields['heading_font_color']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_content_font_color', $fields['content_font_color']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_content_line_height', $fields['content_line_height']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_readmore_font_color', $fields['readmore_font_color']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_readmore_bg_color', $fields['readmore_bg_color']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_readmore_border_color', $fields['readmore_border_color']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_image_top', $fields['image_top']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_image_left', $fields['image_left']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_image_width', $fields['image_width']);
        $this->eps_add_or_update_or_delete_meta($this->slider->ID,  $this->slide->ID.'_image_height', $fields['image_height']);


    }
}