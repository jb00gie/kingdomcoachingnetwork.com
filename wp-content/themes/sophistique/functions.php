<?php

require_once( dirname(__FILE__) . '/setup.php' );

/******************************************************************************
*  Sophistique Main Class
******************************************************************************/

class Sophistique
{

    var $theme_name      = 'Sophistique';
    var $theme_version   = '1.1.2';
    var $theme_key;
    var $chavezShop;

	function __construct(){
		$this->theme_key = strtolower( str_replace(' ', '_', $this->theme_name) );

		add_filter( 'pagelines_foundry', 			array( &$this, 'google_fonts' ) );
		add_filter( 'pl_activate_url',   			array( &$this, 'activation_url') );
		//add_filter( 'pl_sorted_settings_array',                 array( &$this, 'add_global_panel'));
		//add_filter( 'admin_init',                               array( &$this, 'autoupdate') );
		$this->create_theme_options();
	}

	function autoupdate(){
		if ( !class_exists( 'chavezShopThemeVerifier' ) ) {
			include( dirname( __FILE__ ) . '/inc/chavezShopThemeVerifier.php' );
		}

		$this->chavezShop = new chavezShopThemeVerifier( $this->theme_name, $this->theme_version, pl_setting( 'sophistique_license_key' ) );
		$this->chavezShop->check_for_updates();
	}

	function add_global_panel($settings){
        $valid = "";
        if( get_option( $this->theme_key."_activated" ) ){
            $valid = ( $this->chavezShop->check_license() ) ? ' - Your license is valid' : ' - Your license is invalid';
        }

        if( !isset( $settings['eChavez'] ) ){
            $settings['eChavez'] = array(
                'name' => 'Enrique Chavez Shop',
                'icon' => 'icon-shopping-cart',
                'opts' => array()
            );
        }

        $collapser_opts = array(
            'key'   => 'sophistique_license_key',
            'type'  => 'text',
            'title' => '<i class="icon-shopping-cart"></i> ' . __('Sophistique License Key', 'sophistique') . $valid,
            'label' => __('License Key', 'sophistique'),
            'help'  => __('The theme is fully functional whitout a key license, this license is used only get access to autoupdates within your admin.', 'sophistique')

        );

        array_push($settings['eChavez']['opts'], $collapser_opts);
        return $settings;

    }

	/**
	 * Adding a custom font from Google Fonts
	 * @param type $thefoundry
	 * @return type
	 */
	function google_fonts( $thefoundry ) {

		if ( ! defined( 'PAGELINES_SETTINGS' ) )
			return;

		$fonts = $this->get_fonts();
		return array_merge( $thefoundry, $fonts );
	}

	/**
	 * Parse the external file for the fonts source
	 * @return type
	 */
	function get_fonts( ) {
		$fonts = pl_file_get_contents( dirname(__FILE__) . '/fonts.json' );
		$fonts = json_decode( $fonts );
		$fonts = $fonts->items;
		$fonts = ( array ) $fonts;
		$out = array();
		foreach ( $fonts as $font ) {
			$out[ str_replace( ' ', '_', $font->family ) ] = array(
				'name'		=> $font->family,
				'family'	=> sprintf( '"%s"', $font->family ),
				'web_safe'	=> true,
				'google' 	=> $font->variants,
				'monospace' => ( preg_match( '/\sMono/', $font->family ) ) ? 'true' : 'false',
				'free'		=> true
			);
		}
		return $out;
	}


	function activation_url($url){
		return home_url() . '?tablink=Sophistique&tabsublink=welcome';
	}

	function create_theme_options(){
		$hi = "
			<h4>Thanks for your purchase.</h4>
			<div>Your new and shiny theme is ready to be used. <br/>Please be aware of the instructions for a optimal setup.</div>
		";

		$step1 = "
			<h4>Import the configuration</h4>
			<div>
					<p>
						1. Please click on the \"Import Config\" menu item on the left.<br>
						2. Locate the yellow button \"Load Child  Theme Config\" and click on it.<br>
						3. A popup will show, click on the \"Ok\" button.<br>
						4. Once you've completed this action, you may want to publish these changes to your live site.<br>
					</p>
			</div>
		";

		$step2 = "
			<h4>Import demo content</h4>
			<div>
					<p>
						1. Please <a href=\"" .home_url( "/wp-content/themes/sophistique/sophistique-demo-content.zip")."\">click here</a> to get the demo content file.<br>
						2. Unzip the file. A new file called sophistique-demo-content.xml will be created.<br>
						3. Within your wp admin area, go to the Menu Tool -> Import.<br>
						4. From the list options, click on WordPress.<br>
						5. A popup will show asking for install the \"WordPress Importer\" plugin, click \"Install Now\".<br>
						6. Activate plugin and Run Importer<br>
						7. In the \"Choose a file from your computer: \" choose the file from the point 2.<br>
						8. Click Upload file and import.<br>
						9. In the \"Assign Authors\" check the \"Download and import file attachments\".<br>
						10. Click Submit.
					</p>
			</div>
		";
		$soptions = array();
		$soptions['Sophistique'] = array(
			'pos'   => 1,
		    'name'  => 'Sophistique',
		    'icon'  => 'icon-pagelines',
		    'opts'  => array(
		        array(
		        	'key' => 'welcome',
		        	'type' => 'template',
		        	'template' => $hi,
		        	'title' => 'Hi, Welcome to Sophistique'
		        ),
		        array(
		        	'key' => 'step1',
		        	'type' => 'template',
		        	'template' => $step1,
		        	'title' => 'Step 1 - Child Theme configuration',
		        	'col' => 2
		        ),
		        array(
		        	'key' => 'step2',
		        	'type' => 'template',
		        	'template' => $step2,
		        	'title' => 'Step 2 - Demo content',
		        	'col' => 3
		        )

		    )
		);
		pl_add_theme_tab( $soptions );
	}
}

new Sophistique;
