<?php

/**
*
*/
class chavezShopThemeVerifier
{
    var $remote_site = 'http://enriquechavez.co/';
    var $theme_version;
    var $theme_name;
    var $license_key;
    var $theme_key;

    function __construct($theme_name, $theme_version, $license_key)
    {

        if( pl_get_mode() != 'draft' ){
            return;
        }

        if( !class_exists( 'EDD_SL_Theme_Updater' ) ) {
            include( dirname( __FILE__ ) . '/EDD_SL_Theme_Updater.php' );
        }

        $this->license_key   = trim( $license_key );
        $this->theme_name    = trim( $theme_name );
        $this->theme_key     = strtolower( str_replace(' ', '_', $this->theme_name) );
        $this->theme_version = $theme_version;

        if($license_key){
            if( !$this->is_license_active() ){
                $this->active_license();
            }
        }else{
            delete_option( $this->theme_key."_activated");
            delete_option( $this->theme_key.'_license');
            delete_transient( $this->theme_key.'tmp_valid_status');
        }
    }

    function check_for_updates(){
        $edd_updater = new EDD_SL_Theme_Updater( array(
            'remote_api_url'    => $this->remote_site,
            'version'           => $this->theme_version,
            'license'           => $this->license_key,
            'item_name'         => $this->theme_name,
            'author'            => 'Enrique Chavez'
        )
    );
    }

    function check_license(){
        if( get_transient( $this->theme_key.'tmp_valid_status' ) ){
            return get_transient( $this->theme_key.'tmp_valid_status' );
        }
        $api_params = array(
            'edd_action' => 'check_license',
            'license'    => $this->license_key,
            'item_name'  => urlencode( $this->theme_key )
        );

        $response = wp_remote_get( add_query_arg( $api_params, $this->remote_site ), array( 'timeout' => 15, 'sslverify' => false ) );

        if ( is_wp_error( $response ) )
            return false;

        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        if( $license_data->license == 'valid' ) {
            set_transient( $this->theme_key.'tmp_valid_status', 'Valid', DAY_IN_SECONDS );
            return true;
        } else {
            return false;
        }
    }

    function active_license(){
        var_dump('activando Licencia');
        $api_params = array(
            'edd_action'=> 'activate_license',
            'license'   => $this->license_key,
            'item_name' => urlencode( $this->theme_name )
        );

        $response = wp_remote_get( add_query_arg( $api_params, $this->remote_site ), array( 'timeout'  => 15, 'sslverify' => false) );

        if ( is_wp_error( $response ) ){
            return false;
        }

        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        if( $license_data->license == 'valid' ){
            update_option( $this->theme_key."_activated", true);
            update_option( $this->theme_key.'_license', $this->license_key, '', 'yes' );
            set_transient( $this->theme_key.'tmp_valid_status', 'Valid', DAY_IN_SECONDS );
        }
    }

    function is_license_active(){
        return get_option( $this->theme_key."_activated" );
    }

}




