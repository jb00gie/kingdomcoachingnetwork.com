<?php

/*
Section: Easy Pie Chart
Author: Enrique Chavez
Author URI: http://tmeister.net
Version: 1.0
Description: Easy pie chart is a section to show simple pie charts for single values. These charts are highly customizable, very easy to implement, scale to the resolution of the display of the client to provide sharp charts even on retina displays
Class Name: SOPieChart
V3: true
Filter: misc
*/

/**
*
*/
class SOPieChart extends PageLinesSection {

	function section_styles(){
		wp_enqueue_script( 'easy-pie-chart', $this->base_url . '/js/jquery.easy-pie-chart.js', array( 'jquery' ), '1.2.3', true );
	}
	function section_head(){
        $bar_color = ($this->opt('pie_bar_color') ) ? pl_hashify( $this->opt('pie_bar_color')) : '#ef1e25';
        $track_color = ($this->opt('pie_track_color') ) ? pl_hashify( $this->opt('pie_track_color')) : '#f2f2f2';
        $scale_color = ($this->opt('pie_scale_color') ) ? pl_hashify( $this->opt('pie_scale_color')) : '#f2f2f2';
        $pie_line_width = ($this->opt('pie_line_width')) ? $this->opt('pie_line_width') : 10;
        $boxes = $this->opt('pie_boxes');
	?>
		<script type="text/javascript">
            jQuery(document).ready(function($) {
                var easyAnimation = false;
                $(window).bind("scroll", function(event) {
                   if( easyAnimation){return;}
                   jQuery(".section-easy-pie-chart:in-viewport").each(function() {
                        createChart();
                        easyAnimation = true;
                    });
                });



                createChart = function(){
                    jQuery('.percentage').easyPieChart({
                        animate: 1000,
                        size: 200,
                        barColor: '<?php echo pl_hashify( $bar_color ) ?>',
                        trackColor : '<?php echo pl_hashify( $track_color ) ?>',
                        scaleColor : '<?php echo pl_hashify( $scale_color ) ?>',
                        lineWidth : <?php echo $pie_line_width ?>,
                        lineCap: "square",
                        onStep: function(value) {
                            this.$el.find('span').text(Math.ceil( value )) ;
                        },
                        onStop : function(){
                            <?php for($i=0; $i<$boxes; $i++):?>
                                jQuery('#chart-box-<?php echo $i ?> div span').text(jQuery('#chart-box-<?php echo $i ?> div').data('percent'))
                            <?php endfor ?>
                        }
                    });
                }
            });
        </script>
	<?php
	}

    function section_opts(){
        $options = array(
            array(
                'key'           => 'box-setup',
                'type'          => 'multi',
                'title'         => 'Pie Chart Configuration',
                'label'         => 'Pie Chart Configuration',
                'opts' => array(
                    array(
                        'key' => "pie_boxes",
                        'type' => 'count_select',
                        'count_start'   => 1,
                        'count_number'  => 12,
                        'label' => 'Number of chart boxes to configure'
                    ),
                    array(
                        'key' => 'pie_span',
                        'type' => 'count_select',
                        'count_start'   => 1,
                        'count_number'  => 12,
                        'label' => 'Number of Columns for each box (12 Col Grid) '
                    ),
                    array(
                        'key' => 'pie_track_color',
                        'type' => 'color',
                        'default' => '#f2f2f2',
                        'label' => 'The color of the track bar.'
                    ),
                    array(
                        'key' => 'pie_bar_color',
                        'type' => 'color',
                        'default' => '#ef1e25',
                        'label' => 'The color of the curcular bar.'
                    ),
                    array(
                        'key' => 'pie_scale_color',
                        'type' => 'color',
                        'default' => '#f2f2f2',
                        'label' => 'The color of the scale lines.'
                    ),
                    array(
                        'key' => 'pie_line_width',
                        'type' => 'count_select',
                        'count_start'   => 1,
                        'count_number'  => 10,
                        'label' => 'Bar line width'
                    )
                )
            )
        );

        if( PL_CORE_VERSION > '1.0.4' ){
            //unset( $options[1]['opts'][0] );
            $options = $this->create_accordion($options);
        }else{
            $options = $this->create_box_settings($options);
        }

        return $options;
    }

    function create_accordion($options){
        $box = array(
            'key'       => 'pie_array',
            'type'      => 'accordion',
            'col'       => 2,
            'title'     => 'Pie Box Settings',
            'post_type' => 'Pie',
            'opts'   => array(
                array(
                        'key' => 'percent',
                        'type' => 'text',
                        'label' => 'Percent to show 1-100',
                    ),
                    array(
                        'key' => 'label',
                        'type' => 'text',
                        'label' => 'Label to show',
                    ),
            )
        );

        array_push($options, $box);
        return $options;


    }

    function create_box_settings($opts){
        $loopCount = (  $this->opt('pie_boxes') ) ? $this->opt('pie_boxes') : 1;
        for ($i=0; $i < $loopCount; $i++) {
            $box = array(
                'key' => 'pie_box_'.$i,
                'type' =>  'multi',
                'title' => 'Pie Box ' . ($i+1) .' settings',
                'label' => 'Settings',
                'opts' => array(
                    array(
                        'key' => 'box_percent_' .$i,
                        'type' => 'text',
                        'label' => 'Percent to show 1-100',
                    ),
                    array(
                        'key' => 'box_label_' .$i,
                        'type' => 'text',
                        'label' => 'Label to show',
                    ),

                )
            );

            array_push($opts, $box);

        }
        return $opts;
    }

	function section_template(){
        $boxes = $this->opt('pie_boxes');

        if(PL_CORE_VERSION > '1.0.4'){
            $pie_array = $this->opt('pie_array');
            $this->draw_pie($pie_array, $boxes);
        }else{
            $this->draw_old_pie($boxes);
        }



	}

    function draw_pie($pie_array, $boxes){

        $upgrade_mapping = array(
            'percent'      => 'box_percent_%s',
            'label'  => 'box_label_%s',

        );
        $pie_array = $this->upgrade_to_array_format_from_zero('pie_array', $pie_array, $upgrade_mapping, $boxes);


        if( !is_array( $pie_array)){
            echo setup_section_notify($this, __('Please start adding some info.', 'sophistique'));
            return;
        }



        ob_start();
    ?>
        <div class="row">
            <?php
                $i = 1;
                foreach ( $pie_array as $pie ): ?>
                <div class="span<?php echo $this->opt('pie_span') ?>">
                    <div class="chart" id="chart-box-<?php echo $i ?>">
                        <div class="percentage" data-percent="<?php echo $pie['percent']?>">
                            <span><?php echo $pie['percent']?></span>%
                        </div>
                        <div class="pie-label" data-sync="<?php echo 'pie_array_item'.$i++.'_label' ?>">
                            <?php echo $pie['label']?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php
        ob_end_flush();
    }

    function draw_old_pie($boxes){
        ob_start();
    ?>
         <div class="row">
            <?php for ($i=0; $i<$boxes; $i++): ?>
                <div class="span<?php echo $this->opt('pie_span') ?>">
                    <div class="chart" id="chart-box-<?php echo $i ?>">
                        <div class="percentage" data-percent="<?php echo $this->opt('box_percent_' .$i)?>">
                            <span><?php echo $this->opt('box_percent_' .$i) ?></span>%
                        </div>
                        <div class="pie-label" data-sync="<?php echo 'box_label_' .$i ?>">
                            <?php echo $this->opt('box_label_' .$i) ?>
                        </div>
                    </div>
                </div>
            <?php endfor ?>
        </div>
    <?
        ob_end_flush();
    }

    // Custom Upgrate my count start in 0 not in 1.
    function upgrade_to_array_format_from_zero( $new_key, $array, $mapping, $number ){
        $scopes = array('local', 'type', 'global');
        if( ! $number )
        {
            return $array;
        }

        if( !$array || $array == 'false' || empty( $array ) )
        {
            for($i = 0; $i < $number; $i++)
            {
                // Set up new output for viewing
                foreach( $mapping as $new_index_key => $old_option_key ){
                    $old_settings[ $i ][ $new_index_key ] = $this->opt( sprintf($old_option_key, $i) );
                }

                // Load up old values using cascade
                foreach( $scopes as $scope )
                {
                    foreach( $mapping as $new_index_key => $old_option_key ){
                        $upgrade_array[$scope]['item'.($i+1)][ $new_index_key ] = $this->opt( sprintf($old_option_key, $i), array('scope' => $scope) );
                    }
                }
            }
            // Setup in new format & update
            foreach($scopes as $scope)
            {
                $this->opt_update( $new_key, $upgrade_array[$scope], $scope );
            }
            return $old_settings;


        } else{
            return $array;
        }
    }