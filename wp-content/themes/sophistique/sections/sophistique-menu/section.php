<?php
/*
Section: Sophistique Menu
Author: Enrique Chavez
Author URI: http://tmeister.net
Version: 1.0
Description: Main navigation menu for Sophistique, show the site logo (Retina Ready) and a 3 levels menu, with a Mobile fallback.
Class Name: TMSOMenu
Filter: full-width, nav
*/

class TMSOMenu extends PageLinesSection {

	function section_persistent(){
	}

	function section_styles(){
		wp_enqueue_script( 'mobile-menu', $this->base_url . '/js/jquery.mobile-menu.js', array( 'jquery' ), '1.0',true );
	}

	function section_head(){
		$label = ( $this->opt('so_mobile_menu') ) ? $this->opt('so_mobile_menu') : 'Navigate to...'
	?>
		<script>
			jQuery(document).ready(function($) {
				jQuery('.nav-sophis').tmMobileMenu({
					label : '<?php echo $label ?>',
					menuBg: 'transparent',
					menuColor: '#000000',
					subMenuBg: 'rgba(255, 255, 255, .2)',
					subMenuItemHover: 'rgba(255, 255, 255, .2)',
					subMenuItemColor: '#000000'
				});
			});
		</script>
	<?php
	}

 	function section_template()
 	{
	    ?>
	    	<div class="pl-content">
		    	<div class="row somenu-container">
		    		<div class="span3">
		    			<a href="<?php echo get_site_url(); ?>" class="so_logo">
		    				<img src="<?php echo $this->opt('so_logotype') ?>" alt="" data-sync="so_logotype">
		    			</a>
		    		</div>
		    		<div class="span9">
		    			<nav class="nav-sophis">
				            <?php
				            	if ( $this->opt( 'so_main_menu' ) ) {
					                wp_nav_menu(
					                    array(
					                        'menu_class'  => 'menu-sophis',
					                        'container' => 'div',
					                        'container_class' => 'nav-sophis-holder',
					                        'depth' => 3,
					                        'menu' => $this->opt('so_main_menu'),
					                        'walker' => new Sophistique_walker
					                    )
					                );
					            }else{
					           		$this->so_nav_fallback( 'menu-sophis', 3 );
								}
				            ?>
				        </nav>
		    		</div>
		    	</div>
		    </div>

	    <?php
	}

	function section_opts()
	{

		$opts = array(
			array(
				'type'  => 'select_menu',
				'title' => 'Main Menu',
				'key'   => 'so_main_menu',
				'label' => __('Select the main menu', 'sophistique')
			),
			array(
				'type'  => 'image_upload',
				'title' => 'Site Logotype',
				'key'   => 'so_logotype',
				'label' => 'Please select the site logotype.',
				'help'  => 'For better visualitation in a retina display devices, please use a 450x145px logo, the section will resize the logo according to the device.'
	        ),
	        array(
				'type'  => 'text',
				'title' => __('Mobile menu text', 'sophistique'),
				'key'   => 'so_mobile_menu',
				'label' => 'Please enter a text for mobile menu.',
				'help'  => 'Default: Navigate to..'
 	        ),
		);
		return $opts;
	}

	function so_nav_fallback($class = '', $limit = 6){

		$pages = wp_list_pages('echo=0&title_li=&sort_column=menu_order&depth=1');

		$pages_arr = explode("\n", $pages);

		$pages_out = '';
		for($i=0; $i < $limit; $i++){

			if(isset($pages_arr[$i]))
				$pages_out .= $pages_arr[$i];

		}

		printf('<div class="nav-sophis-holder"><ul class="%s">%s</ul></div>', $class, $pages_out);
	}


} /* End of section class - No closing php tag needed */

/**
* Walker Class for build Delicone Menu
*/
class Sophistique_walker extends Walker_Nav_Menu
{
 	function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		global $wp_query;
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$class_names = $value = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );
		$class_names = ' class="'. esc_attr( $class_names ) . '"';

		$output .= $indent . '<li id="menu-item-'. $item->ID . '"' . $value . $class_names .'>';

		$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
		$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
		$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
		$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';
		$description  = ! empty( $item->description ) ? '<span>'.esc_attr( $item->description ).'</span>' : '';

		if($depth != 0) {
			$description = $append = $prepend = "";
		}

		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		$item_output .= $args->link_before .apply_filters( 'the_title', $item->title, $item->ID );
		$item_output .= $description.$args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
}



