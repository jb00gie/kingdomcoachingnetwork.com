<?php
/*
Section: Revolution Slider
Author: Enrique Chavez
Author URI: http://tmeister.net
Version: 1.0
Description: Port to the Great Revolution Slider jQuery Plugin for PageLines DMS, Add and animate anything you want: captions, images, videos.
Class Name: TMSORevolution
Filter: full-width, slider

*/

if( ! function_exists('cmb_init') ){
	require_once( 'cmb/custom-meta-boxes.php' );
}

class TMSORevolution extends PageLinesSection
{


	var $domain               = 'tmRevolution';
	/**************************************************************************
	* SLIDES
	**************************************************************************/
	var $tax_id               = 'tm_so_tax';
	var $custom_post_type     = 'tm_so_slider';
	/**************************************************************************
	* CAPTIONS
	**************************************************************************/
	var $tax_cap_id           = 'tm_so_cap_tax';
	var $custom_cap_post_type = 'tm_so_caption';

	var $slides = null;

	function section_persistent()
	{
		$this->post_type_slider_setup();
		$this->post_type_caption_setup();
		//$this->post_meta_setup();
		( PL_CORE_VERSION > '1.0.4' ) ? add_filter( 'cmb_meta_boxes', array(&$this, 'meta_boxes') ) : $this->post_meta_setup();
	}
	function section_styles(){
		wp_enqueue_script( 'common-plugins', $this->base_url . '/js/jquery.plugins.min.js', array( 'jquery' ), '1.0',true );
		wp_enqueue_script( 'trslider', $this->base_url . '/js/jquery.revolution.min.js', array( 'common-plugins' ), '1.0', true );
	}

	function section_head(){
		if( !is_front_page() && !pl_draft_mode() ){
 			return;
 		}
 		$clone_id = null;
		global $post, $pagelines_ID;
		$oset            = array('post_id' => $pagelines_ID, 'clone_id' => $clone_id);
		$tmrv_width      = ( $this->opt('tmrv_width', $oset) ) ? $this->opt('tmrv_width', $oset) : '900';
		$tmrv_height     = ( $this->opt('tmrv_height', $oset) ) ? $this->opt('tmrv_height', $oset) : '350';
		$tmrv_shadow     = ( $this->opt('tmrv_shadow', $oset) == 'on' ) ? '0' : '1';
		$tmrv_touch      = ( $this->opt('tmrv_touch', $oset) == 'on' ) ? 'off' : 'on';
		$tmrv_pause_over = ( $this->opt('tmrv_pause_over', $oset) == 'on' ) ? 'off' : 'on';
		$tmrv_items      = ( $this->opt('tmrv_items', $oset) ) ? $this->opt('tmrv_items', $oset) : '10';
		$tmrv_set        = ( $this->opt('tmrv_set', $oset) ) ? $this->opt('tmrv_set', $oset) : '';
		$tmrv_time       = ( $this->opt('tmrv_time', $oset) ) ? $this->opt('tmrv_time', $oset) : '8000';
		$this->slides    = $this->get_posts($this->custom_post_type, $this->tax_id, $tmrv_set, $tmrv_items);

		if( !count( $this->slides ) ){
			return;
		}

	?>
		<script type="text/javascript">
      		jQuery(document).ready(function() {
      			if (jQuery.fn.cssOriginal!=undefined)
					jQuery.fn.css = jQuery.fn.cssOriginal;

	            jQuery('.banner').revolution(
				{
					delay: <?php echo $tmrv_time; ?>,
					startheight: <?php echo $tmrv_height ?>,
					startwidth: <?php echo $tmrv_width ?>,
					navigationType:"bullet",
					navigationStyle:'navbar',
					navigationArrows:'verticalcentered',
					touchenabled: '<?php echo $tmrv_touch ?>',
					onHoverStop: '<?php echo $tmrv_pause_over ?>',
					shadow: '<?php echo $tmrv_shadow ?>',
					fullWidth: 'off'
                });
           });
		</script>
	<?php
	}

 	function section_template( $clone_id = null ) {
 		if( !is_front_page() && !pl_draft_mode()  ){
 			return;
 		}
		global $post, $pagelines_ID;
		$oset         = array('post_id' => $pagelines_ID, 'clone_id' => $clone_id);
		$tmrv_items   = ( $this->opt('tmrv_items', $oset) ) ? $this->opt('tmrv_items', $oset) : '10';
		$tmrv_set     = ( $this->opt('tmrv_set', $oset) ) ? $this->opt('tmrv_set', $oset) : '';

		$slides = ( $this->slides == null ) ? $this->get_posts($this->custom_post_type, $this->tax_id, $tmrv_set, $tmrv_items) : $this->slides;
		$current_page_post = $post;

		if( !count($slides) ){
			echo setup_section_notify($this, __('Sorry,there are no slides to display.', 'sophistique'), get_admin_url().'edit.php?post_type='.$this->custom_post_type, __('Please create some slides', 'sophistique'));
			return;
		}
 	?>
		<div class="fullwidthbanner-container">
			<div class="banner">
				<ul>
					<?php
						foreach ($slides as $post):
							$io          = array('post_id' => $post->ID);
							$transition  = ( plmeta('tmrv_transition', $io ) )  ? plmeta('tmrv_transition', $io) : 'boxfade';
							$slots       = ( plmeta('tmrv_slots', $io ) )  ? plmeta('tmrv_slots', $io) : '1';
							$use_image   = (plmeta('tmrv_transparent', $io) == 'off') ? true : false;
							$image  	 = $this->find_and_show_image( $post->ID, 'tmrv_background_slider', true);
							$img_src     = ( $image || ($use_image && $image ) ) ? $image : '/wp-content/themes/sophistique/images/transparent.png';
							$masterspeed = ( plmeta('tmrv_masterspeed', $io ) )  ? plmeta('tmrv_masterspeed', $io) : '300';
							$link        = (plmeta('tmrv_link', $io)) ? 'data-link="' . plmeta('tmrv_link', $io). '"' : '';
							$link_target = (plmeta('tmrv_link_target', $io)) ? 'data-target="'. plmeta('tmrv_link_target', $io) . '"' : '';
							/**************************************************
							* CAPTIONS
							**************************************************/
							$caption_set = strlen( trim( plmeta('tmrv_caption_set', $io)) ) ? plmeta('tmrv_caption_set', $io) : 'null';
							$caption_set = ( is_numeric( $caption_set ) ) ? get_term_by( 'id', $caption_set, $this->tax_cap_id)->slug : $caption_set;
							$captions = $this->get_posts($this->custom_cap_post_type, $this->tax_cap_id, $caption_set);
					?>
						<li data-transition="<?php echo $transition ?>" data-slotamount="<?php echo $slots ?>" data-masterspeed="<?php echo $masterspeed ?>" <?php echo $link ?> <?php echo $link_target ?>>
							<img src="<?php echo $img_src ?>">
							<?php if ( count( $captions ) ): ?>
								<?php $current_inner_page_post = $post; ?>
								<?php foreach ( $captions as $post ):
									$ioc               = array('post_id' => $post->ID);
									//Types
									$tmrv_caption_type = ( plmeta('tmrv_caption_type', $ioc) ) ? plmeta('tmrv_caption_type', $ioc) : 'text';
									$tmrv_text         = ( plmeta('tmrv_text', $ioc) ) ? plmeta('tmrv_text', $ioc) : '';
									//$tmrv_image        = ( plmeta('tmrv_image', $ioc) ) ? plmeta('tmrv_image', $ioc) : '';
									$tmrv_image        = $this->find_and_show_image( $post->ID, 'tmrv_image', true);
									// Styles
									$tmrv_c_style      = ( plmeta('tmrv_c_style', $ioc) ) ? plmeta('tmrv_c_style', $ioc) : 'big_white';
									$tmrv_video        = ( plmeta('tmrv_video', $ioc) ) ? plmeta('tmrv_video', $ioc) : '';
									$tmrv_i_animation  = ( plmeta('tmrv_incomming_animation', $ioc) ) ? plmeta('tmrv_incomming_animation', $ioc) : 'sft';
									$tmrv_o_animation  = ( plmeta('tmrv_outgoing_animation', $ioc) ) ? plmeta('tmrv_outgoing_animation', $ioc) : 'stt';
									// Datas
									$tmrv_start_x      = ( plmeta('tmrv_start_x', $ioc) ) ? plmeta('tmrv_start_x', $ioc) : '0';
									$tmrv_start_y      = ( plmeta('tmrv_start_y', $ioc) ) ? plmeta('tmrv_start_y', $ioc) : '0';
									$tmrv_speed_intro  = ( plmeta('tmrv_speed_intro', $ioc) ) ? plmeta('tmrv_speed_intro', $ioc) : '300';
									$tmrv_speed_end    = ( plmeta('tmrv_speed_end', $ioc) ) ? plmeta('tmrv_speed_end', $ioc) : '300';
									$tmrv_start_after  = ( plmeta('tmrv_start_after', $ioc) ) ? plmeta('tmrv_start_after', $ioc) : '0';
									$tmrv_easing_intro = ( plmeta('tmrv_easing_intro', $ioc) ) ? plmeta('tmrv_easing_intro', $ioc) : 'linear';
									$tmrv_easing_out   = ( plmeta('tmrv_easing_out', $ioc) ) ? plmeta('tmrv_easing_out', $ioc) : 'linear';
								?>
									<div
										class="caption <?php echo $tmrv_i_animation; ?> <?php echo $tmrv_o_animation ?> <?php echo $tmrv_c_style ?>"
										data-x="<?php echo $tmrv_start_x ?>"
										data-y="<?php echo $tmrv_start_y ?>"
										data-speed="<?php echo $tmrv_speed_intro ?>"
										data-start="<?php echo $tmrv_start_after ?>"
										data-easing="<?php echo $tmrv_easing_intro ?>"
										data-endspeed="<?php echo $tmrv_speed_end ?>"
										data-endeasing="<?php echo $tmrv_easing_out?>"
									>
										<?php switch ($tmrv_caption_type) {
											case 'text':
												echo $tmrv_text;
												break;
											case 'image':
												echo "<img src='".$tmrv_image."' />";
												break;
											case 'video':
												echo $tmrv_video;
												break;
										} ?>
									</div>
								<?php endforeach; $post = $current_inner_page_post; ?>
							<?php endif ?>
						</li>
					<?php endforeach; $post = $current_page_post; ?>
				</ul>
				<div class="tp-bannertimer"></div>
			</div>
		</div>
 	<?php
	}

	function before_section_template( $clone_id = null ){}

	function after_section_template( $clone_id = null ){}

	function meta_boxes( $meta_boxes ){

		$meta_boxes[] = array(
			'title'  => __('Slider Options', 'sophistique'),
			'pages'  => $this->custom_post_type,
			'fields' => array(
				array(
					'id'   => 'tmrv_background_slider',
					'name' => __('Slide Background', 'sophistique'),
					'type' => 'image',
					'desc' => __('Please select a image to use as a slide background.', 'sophistique'),
					'cols'  => 4

				),
				array(
					'id'   => 'tmrv_transparent',
					'name' => __('Transparent Backgound', 'sophistique'),
					'type' => 'select',
					'desc' => __('With this option youcan choose if you don\'t want to use a background in the slide. If a image is upload this setting is override and will use the image as a background', 'sophistique'),
					'cols'  => 4,
					'options' => array(
						'off' => __('Use the image provided', 'sophistique'),
						'on'  => __('Do not use a background', 'sophistique')
					)
				),
				array(
					'id' => 'tmrv_transition',
					'name' => __('Slide transition effect', 'sophistique'),
					'desc' => __('Every slide can have a different transition you can choose it in this option.', 'sophistique'),
					'type' => 'select',
					'cols' => 4,
					'options' => array(
						'boxslide'             => __('Box Slide', 'sophistique'),
						'boxfade'              => __('Box Fade', 'sophistique'),
						'slotzoom-horizontal'  => __('Slot Zoom Horizontal', 'sophistique'),
						'slotslide-horizontal' => __('Slot Slide Horizontal', 'sophistique'),
						'slotfade-horizontal'  => __('Slot Fade Horizontal', 'sophistique'),
						'slotzoom-vertical'    => __('Slot Zoom Vertical', 'sophistique'),
						'slotslide-vertical'   => __('Slot Slide Vertical', 'sophistique'),
						'slotfade-vertical'    => __('Slot Fade Vertical', 'sophistique'),
						'curtain-1'            => __('Curtain 1', 'sophistique'),
						'curtain-2'            => __('Curtain 2', 'sophistique'),
						'curtain-3'            => __('Curtain 3', 'sophistique'),
						'slideleft'            => __('Slide Left', 'sophistique'),
						'slideright'           => __('Slide Right', 'sophistique'),
						'slideup'              => __('Slide Up', 'sophistique'),
						'slidedown'            => __('Slide Down', 'sophistique'),
						'fade'                 => __('Fade', 'sophistique'),
						'random'               => __('Random', 'sophistique'),
						'slidehorizontal'      => __('Slide Horizontal', 'sophistique'),
						'slidevertical'        => __('Slide Vertical', 'sophistique'),
						'papercut'             => __('Papercut', 'sophistique'),
						'flyin'                => __('Flyin', 'sophistique'),
						'turnoff'              => __('Turnoff', 'sophistique'),
						'cube'                 => __('Cube', 'sophistique'),
						'3dcurtain-vertical'   => __('3d Curtain Vertical', 'sophistique'),
						'3dcurtain-horizontal' => __('3d Curtain Horizontal', 'sophistique'),
					),
				),
				array(
					'id' => 'tmrv_masterspeed',
					'name' => __('Slide Transition Duration', 'sophistique') ,
					'desc' => __('Transition speed.', 'sophistique'),
					'type' => 'select',
					'cols' => 4,
					'default' => '300',
					'options' => $this->getMasterCMBSpeedOptions()
				),
				array(
					'id' => 'tmrv_slots',
					'name' => __('Slot Amount', 'sophistique'),
					'desc' => __('The number of slots or boxes the slide is divided into. If you use Box Fade, over 7 slots can be juggy. please use a number between 1 and 20', 'sophistique'),
					'type' => 'text_small',
					'cols' => 4,
					'deafult' => '5'
				),
				array(
					'id' => 'tmrv_caption_set',
					'name' => __('Caption Set', 'sophistique'),
					'desc' => __('Each slide can have several captions on it, choose a caption set to show on this slide.', 'sophistique'),
					'type' => 'taxonomy_select',
					'taxonomy' => $this->tax_cap_id
				),

			)
		);

		$meta_boxes[] = array(
			'title'  => __('Revolution Caption Options', 'sophistique'),
			'pages'  => $this->custom_cap_post_type,
			'fields' => array(
				array(
					'id' => 'tmrv_caption_type',
					'name' => __('Caption type', 'sophistique'),
					'desc' => __('The "Caption" can be one of three types (Text, Image or Video) please, choose what type of caption you will use, be aware, if you choose "Caption text" only the text\'s field value will be use, if you choose "Caption image" only the image\'s field value will be use and so on.', 'sophistique'),
					'type' => 'select',
					'options' => array(
						'text'  => __('Text', 'sophistique'),
						'image' => __('Image', 'sophistique'),
						'video' => __('Video', 'sophistique'),
					)
				),
				array(
					'id' => 'tmrv_text',
					'name' => __('Caption Text', 'sophistique'),
					'desc' => __('If you chose "Text" in the "Caption type" option, the value on this field will be use, regardless of the value of the image or video fields.', 'sophistique'),
					'cols' => 4,
					'type' => 'text'
				),
				array(
					'id' => 'tmrv_image',
					'name' => __('Caption Image'),
					'desc' => __('If you chose "Image" in the "Caption type" option, the value on this field will be use, regardless of the value of the text or video fields.', 'sophistique'),
					'type' => 'image',
					'cols' => 4,
				),
				array(
					'id' => 'tmrv_video',
					'name' => __('Caption Video'),
					'desc' => __('If you chose "Video" in the "Caption type" option, the value on this field will be use, regardless of the value of the text or image fields.', 'sophistique'),
					'type' => 'textarea',
					'cols' => 4
				),
				array(
					'id' => 'tmrv_incomming_animation',
					'name' => __('Incoming Animation', 'sophistique'),
					'desc' => __('You can set a incoming animation for each of the caption.','sophistique'),
					'cols' => 6,
					'type' => 'select',
					'options' => array(
						'sft'          => __('Short from Top', 'sophistique'),
						'sfb'          => __('Short from Bottom', 'sophistique'),
						'sfr'          => __('Short from Right', 'sophistique'),
						'sfl'          => __('Short from Left', 'sophistique'),
						'lft'          => __('Long from Top', 'sophistique'),
						'lfb'          => __('Long from Bottom', 'sophistique'),
						'lfr'          => __('Long from Right', 'sophistique'),
						'lfl'          => __('Long from Left', 'sophistique'),
						'fade'         => __('Fading', 'sophistique'),
						'randomrotate' => __('Fade in, Rotate from a Random position and Degree')
					)
				),
				array(
					'id' => 'tmrv_outgoing_animation',
					'name' => __('Outgoing Animation', 'sophistique'),
					'desc' => __('You can set a outgoing animation for each of the caption.','sophistique'),
					'cols' => 6,
					'type' => 'select',
					'options' => array(
						'stt'             => __('Short to Top', 'sophistique'),
						'stb'             => __('Short to Bottom', 'sophistique'),
						'str'             => __('Short to Right', 'sophistique'),
						'stl'             => __('Short to Left', 'sophistique'),
						'ltt'             => __('Long to Top', 'sophistique'),
						'ltb'             => __('Long to Bottom', 'sophistique'),
						'ltr'             => __('Long to Right', 'sophistique'),
						'ltl'             => __('Long to Left', 'sophistique'),
						'fadeout'         => __('Fading', 'sophistique'),
						'randomrotateout' => __('Fade in, Rotate from a Random position and Degree', 'sophistique')
					)

				),
				array(
					'id' => 'tmrv_start_x',
					'name' => __('Horizontal Position', 'sophistique'),
					'desc' => __('The horizontal position based on the slider size, in the resposive view this position will be calculated.', 'sophistique'),
					'cols' => 6,
					'type' => 'text',
				),
				array(
					'id' => 'tmrv_start_y',
					'name' => __('Vertical Position', 'sophistique'),
					'desc' => __('The Vertical position based on the slider size, in the resposive view this position will be calculated.', 'sophistique'),
					'cols' => 6,
					'type' => 'text',
				),
				array(
					'id' => 'tmrv_c_style',
					'name' => __('Caption style', 'sophistique'),
					'description' => __('This option will be used only for text captions.', 'sophistique'),
					'cols' => 12,
					'type' => 'select',
					'options' => array(
						'big_white'       =>  __('Big White'),
						'big_orange'      =>  __('Big Orange'),
						'big_black'       =>  __('Big Black'),
						'medium_white'    =>  __('Medium Grey'),
						'medium_text'     =>  __('Medium White'),
						'small_white'     =>  __('Small White'),
						'large_text'      =>  __('Large White'),
						'very_large_text' =>  __('Very Large White'),
						'very_big_white'  =>  __('Very Big White'),
						'very_big_black'  =>  __('Very Big Black'),
					)
				),
				array(
					'id' => 'tmrv_speed_intro',
					'name' => __('Animation duration intro', 'sophistique'),
					'desc' => __('Duration of the intro animation in milliseconds, Take note that 1 second is equal to 1000 milliseconds.', 'sophistique'),
					'cols' => 4,
					'type' => 'text'
				),
				array(
					'id' => 'tmrv_speed_end',
					'name' => __('Animation duration out', 'sophistique'),
					'desc' => __('Duration of the out animation in milliseconds, Take note that 1 second is equal to 1000 milliseconds.', 'sophistique'),
					'cols' => 4,
					'type' => 'text'
				),
				array(
					'id' => 'tmrv_start_after',
					'name' => __('Time to wait to show this caption', 'sophistique'),
					'desc' => __('How many time should this caption start to show in milliseconds, Take note that 1 second is equal to 1000 milliseconds.', 'sophistique'),
					'cols' => 4,
					'type' => 'text'
				),
				array(
					'id' => 'tmrv_easing_intro',
					'name' => __('Easing intro effect', 'sophistique'),
					'desc' => __('You can set a different easing effect for each caption, default is linear', 'sophistique'),
					'cols' => 6,
					'type' => 'select',
					'options' => $this->getCMBEasing()
				),
				array(
					'id' => 'tmrv_easing_out',
					'name' => __('Easing out effect', 'sophistique'),
					'desc' => __('You can set a different easing effect for each caption, default is linear', 'sophistique'),
					'cols' => 6,
					'type' => 'select',
					'options' => $this->getCMBEasing()
				),

			)
		);




		return $meta_boxes;
	}

	function post_meta_setup()
	{
		/**********************************************************************
		* Slider meta options
		**********************************************************************/
		$pt_tab_options = array(

			'tmrv_background_slider' => array(
				'type'       => 'image_upload',
				'inputlabel' => __('Slide Background', 'sophistique'),
				'title'      => __('Slide Background', 'sophistique'),
				'shortexp'   => __('Background Image.', 'sophistique'),
				'exp'        => __('Please select a image to use as a slide background.', 'sophistique')
			),
			'tmrv_transparent' => array(
				'type'         => 'select',
				'inputlabel'   => __('', 'sophistique'),
				'title'        => __('Transparent Backgound', 'sophistique'),
				'shortexp'     => __('Do not use a background image', 'sophistique'),
				'exp'          => __('With this option youcan choose if you don\'t want to use a background in the slide. If a image is upload this setting is override and will use the image as a background', 'sophistique'),
				'selectvalues' => array(
					'off' => array('name' => __('Use the image provided', 'sophistique')),
					'on'  => array('name' => __('Do not use a background', 'sophistique'))
				)
			),
			'tmrv_transition' => array(
				'type' => 'select',
				'inputlabel' => __('Select the slide transition effect', 'sophistique'),
				'title' => __('Slide transition effect', 'sophistique'),
				'shortexp' => __('Transition effect', 'sophistique'),
				'exp' => __('Every slide can have a different transition you can choose it in this option.', 'sophistique'),
				'selectvalues' => array(
					'boxslide'             => array('name' => __('Box Slide', 'sophistique')),
					'boxfade'              => array('name' => __('Box Fade', 'sophistique')),
					'slotzoom-horizontal'  => array('name' => __('Slot Zoom Horizontal', 'sophistique')),
					'slotslide-horizontal' => array('name' => __('Slot Slide Horizontal', 'sophistique')),
					'slotfade-horizontal'  => array('name' => __('Slot Fade Horizontal', 'sophistique')),
					'slotzoom-vertical'    => array('name' => __('Slot Zoom Vertical', 'sophistique')),
					'slotslide-vertical'   => array('name' => __('Slot Slide Vertical', 'sophistique')),
					'slotfade-vertical'    => array('name' => __('Slot Fade Vertical', 'sophistique')),
					'curtain-1'            => array('name' => __('Curtain 1', 'sophistique')),
					'curtain-2'            => array('name' => __('Curtain 2', 'sophistique')),
					'curtain-3'            => array('name' => __('Curtain 3', 'sophistique')),
					'slideleft'            => array('name' => __('Slide Left', 'sophistique')),
					'slideright'           => array('name' => __('Slide Right', 'sophistique')),
					'slideup'              => array('name' => __('Slide Up', 'sophistique')),
					'slidedown'            => array('name' => __('Slide Down', 'sophistique')),
					'fade'                 => array('name' => __('Fade', 'sophistique')),
					'random'               => array('name' => __('Random', 'sophistique')),
					'slidehorizontal'      => array('name' => __('Slide Horizontal', 'sophistique')),
					'slidevertical'        => array('name' => __('Slide Vertical', 'sophistique')),
					'papercut'             => array('name' => __('Papercut', 'sophistique')),
					'flyin'                => array('name' => __('Flyin', 'sophistique')),
					'turnoff'              => array('name' => __('Turnoff', 'sophistique')),
					'cube'                 => array('name' => __('Cube', 'sophistique')),
					'3dcurtain-vertical'   => array('name' => __('3d Curtain Vertical', 'sophistique')),
					'3dcurtain-horizontal' => array('name' => __('3d Curtain Horizontal', 'sophistique')),
				)
			),
			'tmrv_masterspeed' => array(
				'type'         => 'select',
				'inputlabel'   => __('Time', 'sophistique'),
				'title'        => __('Slide Transition Duration', 'sophistique') ,
				'shortexp'     => __('Default: 300', 'sophistique') ,
				'exp'          => __('Transition speed.', 'sophistique'),
				'selectvalues' => $this->getMasterSpeedOptions()
			),
			'tmrv_slots' => array(
				'type'         => 'count_select',
				'inputlabel'   => __('Slot Amount', 'sophistique'),
				'title'        => __('Slot Amount', 'sophistique'),
				'shortexp'     => __('How many slot use in the slide', 'sophistique'),
				'exp'          => __('The number of slots or boxes the slide is divided into. If you use Box Fade, over 7 slots can be juggy', 'sophistique'),
				'count_start'  => 1,
				'count_number' => 20
			),
			'tmrv_caption_set' 	=> array(
				'type' 			=> 'select_taxonomy',
				'taxonomy_id'	=> $this->tax_cap_id,
				'title' 		=> __('Caption Set', 'sophistique'),
				'shortexp'		=> __('Select which <strong>caption set</strong> you want to show over the image.', 'sophistique'),
				'inputlabel'	=> __('Caption Set', 'sophistique'),
				'exp' 			=> __('Each slide can have several captions on it, choose a caption set to show on this slide.', 'sophistique')
			),
			/*'tmrv_link' => array(
				'type'       => 'text',
				'inputlabel' => __('Slide link', 'sophistique'),
				'title'      => __('Slide link', 'sophistique'),
				'shortexp'   => __('Optional link for the slide', 'sophistique'),
				'exp'        => __('A link on the whole slide pic', 'sophistique')
			),
			'tmrv_link_target' => array(
				'type'         => 'select',
				'inputlabel'   => __('Slide link target', 'sophistique'),
				'title'        => __('Slide link target', 'sophistique'),
				'shortexp'     => __('Default: _self', 'sophistique'),
				'exp'          => __('Link Target', 'sophistique'),
				'selectvalues' => array(
					'_blank' => array('name' => '_blank'),
					'_self'  => array('name' => '_self')
				)
			),*/
		);

		$pt_panel = array(
			'id' 		=> $this->id . '-metapanel',
			'name' 		=> __('Slider Options', 'sophistique'),
			'posttype' 	=> array( $this->custom_post_type ),
		);
		$pt_panel =  new PageLinesMetaPanel( $pt_panel );
		$pt_tab = array(
			'id' 		=> $this->id . '-metatab',
			'name' 		=> "Slider Options",
			'icon' 		=> $this->icon,
		);
		$pt_panel->register_tab( $pt_tab, $pt_tab_options );

		/**********************************************************************
		* Captions meta options
		**********************************************************************/
		$pt_tab_options_captions = array(

			'tmrv_caption_type' => array(
				'type'         => 'select',
				'inputlabel'   => __('Caption type', 'sophistique'),
				'title'        => __('Caption type', 'sophistique'),
				'shortexp'     => __('What kind of caption will be?, Default: "Text"', 'sophistique'),
				'exp'          => __('The "Caption" can be one of three types (Text, Image or Video) please, choose what type of caption you will use, be aware, if you choose "Caption text" only the text\'s field value will be use, if you choose "Caption image" only the image\'s field value will be use and so on.', 'sophistique'),
				'selectvalues' => array(
					'text'  => array('name' => __('Text', 'sophistique')),
					'image' => array('name' => __('Image', 'sophistique')),
					'video' => array('name' => __('Video', 'sophistique')),
				)
			),
			'tmrv_text' => array(
				'type'       => 'text',
				'inputlabel' => __('Caption Text', 'sophistique'),
				'title'      => __('Caption Text', 'sophistique'),
				'shortexp'   => __('The caption text value', 'sophistique'),
				'exp'        => __('If you chose "Text" in the "Caption type" option, the value on this field will be use, regardless of the value of the image or video fields.', 'sophistique')
			),
			'tmrv_image' => array(
				'type'       => 'image_upload',
				'inputlabel' => __('Caption Image'),
				'title'      => 'Caption Image',
				'shortexp'   => __('The caption image value', 'sophistique'),
				'exp'        => __('If you chose "Image" in the "Caption type" option, the value on this field will be use, regardless of the value of the text or video fields.', 'sophistique')
 			),
 			'tmrv_video' => array(
 				'type'       => 'textarea',
				'inputlabel' => __('Caption Video'),
				'title'      => 'Caption Video',
				'shortexp'   => __('The caption video value', 'sophistique'),
				'exp'        => __('If you chose "Video" in the "Caption type" option, the value on this field will be use, regardless of the value of the text or image fields.', 'sophistique')
 			),
			'tmrv_incomming_animation' => array(
				'type'         => 'select',
				'inputlabel'   => __('Incoming Animation', 'sophistique'),
				'title'        => __('Incoming Animation', 'sophistique'),
				'shortexp'     => __('Select the incoming animation for the caption.', 'sophistique'),
				'exp'          => __('You can set a incoming animation for each of the caption.','sophistique'),
				'selectvalues' => array(
					'sft'          => array('name' => __('Short from Top', 'sophistique') ),
					'sfb'          => array('name' => __('Short from Bottom', 'sophistique') ),
					'sfr'          => array('name' => __('Short from Right', 'sophistique') ),
					'sfl'          => array('name' => __('Short from Left', 'sophistique') ),
					'lft'          => array('name' => __('Long from Top', 'sophistique') ),
					'lfb'          => array('name' => __('Long from Bottom', 'sophistique') ),
					'lfr'          => array('name' => __('Long from Right', 'sophistique') ),
					'lfl'          => array('name' => __('Long from Left', 'sophistique') ),
					'fade'         => array('name' => __('Fading', 'sophistique') ),
					'randomrotate' => array('name' => __('Fade in, Rotate from a Random position and Degree') )
				)
			),
			'tmrv_outgoing_animation' => array(
				'type'         => 'select',
				'inputlabel'   => __('Outgoing Animation', 'sophistique'),
				'title'        => __('Outgoing Animation', 'sophistique'),
				'shortexp'     => __('Select the outgoing animation for the caption.', 'sophistique'),
				'exp'          => __('You can set a outgoing animation for each of the caption.','sophistique'),
				'selectvalues' => array(
					'stt'             => array('name' => __('Short to Top', 'sophistique')),
					'stb'             => array('name' => __('Short to Bottom', 'sophistique')),
					'str'             => array('name' => __('Short to Right', 'sophistique')),
					'stl'             => array('name' => __('Short to Left', 'sophistique')),
					'ltt'             => array('name' => __('Long to Top', 'sophistique')),
					'ltb'             => array('name' => __('Long to Bottom', 'sophistique')),
					'ltr'             => array('name' => __('Long to Right', 'sophistique')),
					'ltl'             => array('name' => __('Long to Left', 'sophistique')),
					'fadeout'         => array('name' => __('Fading', 'sophistique')),
					'randomrotateout' => array('name' => __('Fade in, Rotate from a Random position and Degree', 'sophistique'))
				)
			),
			'tmrv_start_x' => array(
				'type'       => 'text',
				'inputlabel' => __('Horizontal Position', 'sophistique'),
				'title'      => __('Horizontal Position', 'sophistique'),
				'shortexp'   => __('The initial horizontal position for the caption.', 'sophistique'),
				'exp'        => __('The horizontal position based on the slider size, in the resposive view this position will be calculated.', 'sophistique')
			),
			'tmrv_start_y' => array(
				'type'       => 'text',
				'inputlabel' => __('Vertical Position', 'sophistique'),
				'title'      => __('Vertical Position', 'sophistique'),
				'shortexp'   => __('The initial vertical position for the caption.', 'sophistique'),
				'exp'        => __('The vertical position based on the slider size, in the resposive view this position will be calculated.', 'sophistique')
			),
			'tmrv_c_style' => array(
				'type'         => 'select',
				'inputlabel'   => __('Caption style', 'sophistique'),
				'title'        => __('Caption style', 'sophistique'),
				'shortexp'     => __('Select the caption style'),
				'exp'          => __('This option will be used only for text captions.', 'sophistique'),
				'selectvalues' => array(
					'big_white'       => array('name' => __('Big White')),
					'big_orange'      => array('name' => __('Big Orange')),
					'big_black'       => array('name' => __('Big Black')),
					'medium_white'    => array('name' => __('Medium Grey')),
					'medium_text'     => array('name' => __('Medium White')),
					'small_white'     => array('name' => __('Small White')),
					'large_text'      => array('name' => __('Large White')),
					'very_large_text' => array('name' => __('Very Large White')),
					'very_big_white'  => array('name' => __('Very Big White')),
					'very_big_black'  => array('name' => __('Very Big Black')),
				)
			),
			'tmrv_speed_intro' => array(
				'type'       => 'text',
				'inputlabel' => __('Animation duration intro', 'sophistique'),
				'title'      => __('Animation duration intro', 'sophistique'),
				'shortexp'   => __('Duration of the animation in milliseconds', 'sophistique'),
				'exp'        => __('Take note that 1 second is equal to 1000 milliseconds.', 'sophistique')
			),
			'tmrv_speed_end' => array(
				'type'       => 'text',
				'inputlabel' => __('Animation duration out', 'sophistique'),
				'title'      => __('Animation duration out', 'sophistique'),
				'shortexp'   => __('Duration of the out animation in milliseconds', 'sophistique'),
				'exp'        => __('Take note that 1 second is equal to 1000 milliseconds.', 'sophistique')
			),
			'tmrv_start_after' => array(
				'type'       => 'text',
				'inputlabel' => __('Time to wait', 'sophistique'),
				'title'      => __('Time to wait to show this caption', 'sophistique'),
				'shortexp'   => __('How many time should this caption start to show in milliseconds', 'sophistique'),
				'exp'        => __('Take note that 1 second is equal to 1000 milliseconds.', 'sophistique')
			),
			'tmrv_easing_intro' => array(
				'type'         => 'select',
				'inputlabel'   => __('Easing intro effect', 'sophistique'),
				'title'        => __('Easing intro effect', 'sophistique'),
				'shortexp'     => __('Easing effect of the intro animation', 'sophistique'),
				'exp'          => __('You can set a different easing effect for each caption, default is linear', 'sophistique'),
				'selectvalues' => $this->getEasing()
			),
			'tmrv_easing_out' => array(
				'type'         => 'select',
				'inputlabel'   => __('Easing out effect', 'sophistique'),
				'title'        => __('Easing out effect', 'sophistique'),
				'shortexp'     => __('Easing effect of the out animation', 'sophistique'),
				'exp'          => __('You can set a different easing effect for each caption, default is linear', 'sophistique'),
				'selectvalues' => $this->getEasing()
			),
		);
		$pt_panel_cap = array(
			'id' 		=> $this->id . 'cap-metapanel',
			'name' 		=> __('Revolution Caption Options', 'sophistique'),
			'posttype' 	=> array( $this->custom_cap_post_type ),
		);
		$pt_panel_cap =  new PageLinesMetaPanel( $pt_panel_cap );
		$pt_tab_cap = array(
			'id'   => $this->id . 'cap-metatab',
			'name' => "Caption Options",
			'icon' => $this->icon,
		);
		$pt_panel_cap->register_tab( $pt_tab_cap, $pt_tab_options_captions );

	}

	function section_opts(){
		$opts = array(
			array(
				'key' => 'tmrv_size',
				'type'         => 'multi',
				'title'        => __('Slider Size', 'sophistique') ,
				'help'          => __('Fully resizable, you can set any size.', 'sophistique'),
				'opts' => array(
					array(
						'key' => 'tmrv_width',
						'type' => 'text',
						'label' => 'Width',
					),
					array(
						'key' => 'tmrv_height',
						'type' => 'text',
						'label' => 'Height',
					)
				)
			),
			array(
				'key' => 'tmrv_set',
				'type' 			=> 'select_taxonomy',
				'taxonomy_id'	=> $this->tax_id,
				'title' 		=> __('Sliders Set', 'sophistique'),
				'help'		=> __('Select the set you want to show.', 'sophistique'),
				'ref' 			=> __('If don\'t select a set or you have not created a set, the slider will show all slides', 'sophistique')
			),
			array(
				'key' => 'tmrv_items',
				'type' 			=> 'count_select',
				'label'	=> __('Number of Slides', 'sophistique'),
				'title' 		=> __('Number of Slides', 'sophistique'),
				'help'		=> __('Default value is 10', 'sophistique'),
				'count_start'	=> 2,
 				'count_number'	=> 20,
 				'col' => 2
			),
			array(
				'key' => 'tmrv_time',
				'type' 			=> 'select',
				'label'			=> __('Delay ', 'sophistique'),
				'title' 		=> __('Slide delay time', 'sophistique'),
				'shortexp'		=> __('Default value is 8000', 'sophistique'),
				'help'			=> __('The time one slide stays on the screen in Milliseconds.', 'sophistique'),
				'opts'			=> $this->getMasterSpeedOptions(20, 1000),
				'col' => 2
			),
			array(
				'key' => 'tmrv_shadow',
				'type'       => 'check',
				'label' => __('Disable shadow?', 'sophistique'),
				'title'      => __('Shadow', 'sophistique') ,
				'help'   => __('Set whether to use the shadow of the slider', 'sophistique'),
				'col' => 3
			),
			array(
				'key' => 'tmrv_touch',
				'type'       => 'check',
				'label' => __('Disable touch support for mobiles?', 'sophistique'),
				'title'      => __('Touch Wipe', 'sophistique') ,
				'help'   => __('Set whether to use the touch support for mobiles', 'sophistique'),
				'col' => 3

			),
			array(
				'key' => 'tmrv_pause_over',
				'type'       => 'check',
				'inputlabel' => __('Disable Pause on hover?', 'sophistique'),
				'title'      => __('Pause on hover', 'sophistique') ,
				'help'   => __('Set whether to use the pause on hover feature', 'sophistique'),
				'col' => 3

			)
		);
		return $opts;
	}

	function post_type_slider_setup()
	{
		$args = array(
			'label'          => __('Rev. Slides', 'sophistique'),
			'singular_label' => __('Slide', 'sophistique'),
			'description'    => __('', 'sophistique'),
			'taxonomies'     => array( $this->tax_id ),
			'menu_icon'      => $this->icon,
			'supports'       => array('title', 'editor')
		);
		$taxonomies = array(
			$this->tax_id => array(
				'label'          => __('Revolution Sets', 'sophistique'),
				'singular_label' => __('Revolution Set', 'sophistique'),
			)
		);
		$columns = array(
			"cb"              => "<input type=\"checkbox\" />",
			"title"           => "Title",
			$this->tax_id     => "Revolution Set"
		);
		$this->post_type = new PageLinesPostType( $this->custom_post_type, $args, $taxonomies, $columns, array(&$this, 'column_display') );
	}

	function post_type_caption_setup()
	{
		$args = array(
			'label'          => __('Rev. Captions', 'sophistique'),
			'singular_label' => __('Caption', 'sophistique'),
			'description'    => __('', 'sophistique'),
			'taxonomies'     => array( $this->tax_cap_id ),
			'menu_icon'      => $this->icon,
			'supports'       => array('title', 'editor')
		);
		$taxonomies = array(
			$this->tax_cap_id => array(
				'label'          => __('Caption Sets', 'sophistique'),
				'singular_label' => __('Caption Set', 'sophistique'),
			)
		);
		$columns = array(
			"cb"              => "<input type=\"checkbox\" />",
			"title"           => "Title",
			$this->tax_cap_id => "Caption Set"
		);
		$this->post_type_cap = new PageLinesPostType( $this->custom_cap_post_type, $args, $taxonomies, $columns, array(&$this, 'column_cap_display') );
	}

	function column_display($column){
		global $post;
		switch ($column){
			case $this->tax_id:
				echo get_the_term_list($post->ID, $this->tax_id, '', ', ','');
				break;
		}
	}

	function column_cap_display($column){
		global $post;
		switch ($column){
			case $this->tax_cap_id:
				echo get_the_term_list($post->ID, $this->tax_cap_id, '', ', ','');
				break;
		}
	}

	function get_posts( $custom_post, $tax_id, $set = null, $limit = null){
		$query                 = array();
		$query['orderby']      = 'ID';
		$query['post_type']    = $custom_post;
		$query[ $tax_id ] = $set;

		if(isset($limit)){
			$query['showposts'] = $limit;
		}

		$q = new WP_Query($query);

		if(is_array($q->posts))
			return $q->posts;
		else
			return array();
	}

	function getMasterSpeedOptions($times = 20, $multiple = 100)
	{
		$out = array();
		for ($i=2; $i <= $times ; $i++) {
			$mill = $i * $multiple;
			$out[(string)$mill] = array('name' => $mill);
		}
		return $out;
	}

	function getMasterCMBSpeedOptions($times = 20, $multiple = 100)
	{
		$out = array();
		for ($i=2; $i <= $times ; $i++) {
			$mill = $i * $multiple;
			$out[(string)$mill] = $mill;
		}
		return $out;
	}
	function getCMBEasing()
     {
          return array(
               'linear'   		  => __('Linear', 'sophistique'),
               'easeEasOutBack'   => __('OutBack', 'sophistique'),
               'easeInQuad'       => __('InQuad', 'sophistique'),
               'easeOutQuad'      => __('OutQuad', 'sophistique'),
               'easeInOutQuad'    => __('InOutQuad', 'sophistique'),
               'easeInCubic'      => __('InCubic', 'sophistique'),
               'easeOutCubic'     => __('OutCubic', 'sophistique'),
               'easeInOutCubic'   => __('InOutCubic', 'sophistique'),
               'easeInQuart'      => __('InQuart', 'sophistique'),
               'easeOutQuart'     => __('OutQuart', 'sophistique'),
               'easeInOutQuart'   => __('InOutQuart', 'sophistique'),
               'easeInQuint'      => __('InQuint', 'sophistique'),
               'easeOutQuint'     => __('OutQuint', 'sophistique'),
               'easeInOutQuint'   => __('InOutQuint', 'sophistique'),
               'easeInSine'       => __('InSine', 'sophistique'),
               'easeOutSine'      => __('OutSine', 'sophistique'),
               'easeInOutSine'    => __('InOutSine', 'sophistique'),
               'easeInExpo'       => __('InExpo', 'sophistique'),
               'easeOutExpo'      => __('OutExpo', 'sophistique'),
               'easeInOutExpo'    => __('InOutExpo', 'sophistique'),
               'easeInCirc'       => __('InCirc', 'sophistique'),
               'easeOutCirc'      => __('OutCirc', 'sophistique'),
               'easeInOutCirc'    => __('InOutCirc', 'sophistique'),
               'easeInElastic'    => __('InElastic', 'sophistique'),
               'easeOutElastic'   => __('OutElastic', 'sophistique'),
               'easeInOutElastic' => __('InOutElastic', 'sophistique'),
               'easeInBack'       => __('InBack', 'sophistique'),
               'easeOutBack'      => __('OutBack', 'sophistique'),
               'easeInOutBack'    => __('InOutBack', 'sophistique'),
               'easeInBounce'     => __('InBounce', 'sophistique'),
               'easeOutBounce'    => __('OutBounce', 'sophistique'),
               'easeInOutBounce'  => __('InOutBounce', 'sophistique')
          );
     }

	function getEasing()
	{
		return array(
			'easeEasOutBack'      => array('name' => __('OutBack', 'sophistique')),
			'easeInQuad'       => array('name' => __('InQuad', 'sophistique')),
			'easeOutQuad'      => array('name' => __('OutQuad', 'sophistique')),
			'easeInOutQuad'    => array('name' => __('InOutQuad', 'sophistique')),
			'easeInCubic'      => array('name' => __('InCubic', 'sophistique')),
			'easeOutCubic'     => array('name' => __('OutCubic', 'sophistique')),
			'easeInOutCubic'   => array('name' => __('InOutCubic', 'sophistique')),
			'easeInQuart'      => array('name' => __('InQuart', 'sophistique')),
			'easeOutQuart'     => array('name' => __('OutQuart', 'sophistique')),
			'easeInOutQuart'   => array('name' => __('InOutQuart', 'sophistique')),
			'easeInQuint'      => array('name' => __('InQuint', 'sophistique')),
			'easeOutQuint'     => array('name' => __('OutQuint', 'sophistique')),
			'easeInOutQuint'   => array('name' => __('InOutQuint', 'sophistique')),
			'easeInSine'       => array('name' => __('InSine', 'sophistique')),
			'easeOutSine'      => array('name' => __('OutSine', 'sophistique')),
			'easeInOutSine'    => array('name' => __('InOutSine', 'sophistique')),
			'easeInExpo'       => array('name' => __('InExpo', 'sophistique')),
			'easeOutExpo'      => array('name' => __('OutExpo', 'sophistique')),
			'easeInOutExpo'    => array('name' => __('InOutExpo', 'sophistique')),
			'easeInCirc'       => array('name' => __('InCirc', 'sophistique')),
			'easeOutCirc'      => array('name' => __('OutCirc', 'sophistique')),
			'easeInOutCirc'    => array('name' => __('InOutCirc', 'sophistique')),
			'easeInElastic'    => array('name' => __('InElastic', 'sophistique')),
			'easeOutElastic'   => array('name' => __('OutElastic', 'sophistique')),
			'easeInOutElastic' => array('name' => __('InOutElastic', 'sophistique')),
			'easeInBack'       => array('name' => __('InBack', 'sophistique')),
			'easeOutBack'      => array('name' => __('OutBack', 'sophistique')),
			'easeInOutBack'    => array('name' => __('InOutBack', 'sophistique')),
			'easeInBounce'     => array('name' => __('InBounce', 'sophistique')),
			'easeOutBounce'    => array('name' => __('OutBounce', 'sophistique')),
			'easeInOutBounce'  => array('name' => __('InOutBounce', 'sophistique'))
		);
	}
	function find_and_show_image($postID, $key ,$return_path = false){
        $image = get_post_meta($postID, $key, true);
        if( strstr($image, 'http') ){
            $image_url = $image;
        }else{
            $image_url = wp_get_attachment_url( $image );
        }
        return ( !$return_path ) ? '<img src="'.$image_url.'" />' : $image_url;
    }
}