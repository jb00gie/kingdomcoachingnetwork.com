<?php
/*
Section: Contact Bar
Author: Enrique Chavez
Author URI: http://tmeister.net
Version: 1.0
Description: Contact Bar allow you to add information about the site/company/client in two rows, primary Phone and Email, also you can add the links to more than 20 social sites.
Class Name: SOContactBar
Filter: full-width, social
V3: true
*/

class SOContactBar extends PageLinesSection {

	var $domain = 'tm_contact_bar';

	function section_persistent(){
	}
	function section_head(){}

	function section_styles(){
		wp_enqueue_script( 'contact-bar', $this->base_url . '/js/cbar.js', array( 'jquery' ), '1.0', true );
	}

 	function section_template()
 	{
		$first_icon = $this->opt( 'tm_cb_first_icon' ) ? $this->opt( 'tm_cb_first_icon' ) : 'icon-th';
		$first_info = $this->opt( 'tm_cb_first_label') ? $this->opt( 'tm_cb_first_label') : 'Call Us: (001) 030-234-567-890';
		$sec_icon   = $this->opt( 'tm_cb_sec_icon' ) ? $this->opt( 'tm_cb_sec_icon' ) : 'icon-envelope';
		$sec_info   = $this->opt( 'tm_cb_sec_label') ? $this->opt( 'tm_cb_sec_label') : 'your@email.com';
		$socials    = array();
		foreach ($this->get_valid_social_sites() as $key => $social) {
			if( $this->opt( $social . '-url' ) ){
				array_push($socials, array('site' => $social, 'url' => $this->opt( $social . '-url' )));
			}
		}
 	?>
		<div class="pl-content">
			<div class="row cb-container">
				<div class="span3 cb-first-row">
					<div class="cb-holder">
						<i class="icon icon-<?php echo $first_icon ?>">
							<span data-sync="tm_cb_first_label"><?php echo $first_info ?></span>
						</i>
					</div>
				</div>
				<div class="span3 cb-second-row">
					<div class="cb-holder">
						<i class="icon icon-<?php echo $sec_icon ?>">
							<span data-sync="tm_cb_sec_label"><?php echo $sec_info ?></span>
						</i>
					</div>
				</div>
				<div class="span6 cb-icons">
					<div class="social-holder">
						<ul class="cb-menu" style="right: 80px;">
							<?php foreach ($socials as $social): ?>
								<li class="<?php echo $social['site'] ?>">
									<a href="<?php echo $social['url'] ?>" title="<?php echo ucfirst($social['site']) ?>" target="_blank"></a>
								</li>
							<?php endforeach ?>
						</ul>
						<ul class="login-logout pull-right" style="margin-top: 6px;">
							<?php if(is_user_logged_in()): ?>
							<li><a href="<?php echo wp_logout_url( '/account' ); ?> " class="btn btn-info">Log Out</a></li>
						<?php else: ?>
						<li><a href="/account" class="btn btn-info">Login</a></li>
										<?php endif; ?>
						</ul>
						<div class="clear"></div>
					</div>
				</div>
			</div>
		</div>
	<?php
 	}

 	function section_opts()
	{
		$opts = array(
			array(
				'key' => 'tm_cb_phone',
				'type' => 'multi',
				'title'			=> __('Left Information Box', 'sophistique'),
				'shortexp'		=> __('Please fill the follow fields.', 'sophistique'),
				'opts' => array(
					array(
						'key' => 'tm_cb_first_icon',
						'label'   	=> __( 'Select the icon to show beside the text - Icons Preview <a target="_blank" href="http://docs.pagelines.com/tutorials/font-awesome">Font Awesome.</a>', 'sophistique' ),
						'type'         	=> 'select_icon'
					),
					array(
						'key' => 'tm_cb_first_label',
 						'type' => 'text',
						'label' 	=> __( 'Enter the information to show in the information text, eg. "Call Us: (001) 030-234-567-890"', 'sophistique' ),
					),
				)
			),
			array(
				'key' => 'tm_cb_email',
				'type' => 'multi',
				'title'			=> __('Right Information Box', 'sophistique'),
				'shortexp'		=> __('Please fill the follow fields.', 'sophistique'),
				'opts' => array(
					array(
						'key' => 'tm_cb_sec_icon',
						'label'   	=> __( 'Select the icon to show beside the text - Icons Preview <a target="_blank" href="http://docs.pagelines.com/tutorials/font-awesome">Font Awesome.</a>', 'sophistique' ),
						'type'         	=> 'select_icon'
					),
					array(
						'key' => 'tm_cb_sec_label',
 						'type' => 'text',
						'label' 	=> __( 'Enter the information to show in the information text, eg. "youremail@domain.com"', 'sophistique' ),
					),
				)
			),
			array(
				'key' => 'tm_cb_social',
				'type'			=> 'multi',
				'title'			=> __('Social Sites URL - (include http://)', 'sophistique'),
				'label'		=> __('In the follow fields please, enter the social URL, if the URL field is empty, nothing will show.', 'sophistique'),
				'opts'	=> $this->get_social_fields()
			),
		);
		return $opts;
	}


	function get_social_fields()
	{
		$out = array();
		foreach ($this->get_valid_social_sites() as $social => $name)
		{
			$out[$name . '-url'] = array(
				'key' => $name . '-url',
				'label' => __(ucfirst($name)),
				'type' => 'text'
			);
		}
		return $out;
	}

	function get_valid_social_sites()
	{
		return array("digg","dribbble","facebook","flickr","forrst","googleplus","html5","lastfm","linkedin","paypal","picasa","pinterest","rss","skype","stumbleupon","tumblr","twitter","vimeo","wordpress","yahoo","youtube","behance","instagram"
		);
	}


} /* End of section class - No closing php tag needed */