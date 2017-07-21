<?php
/**
 * Makes GravityForms compatible with wp-parsidate plugin
 *
 * @package                 Persian-Gravity-Forms
 * @author                  HANNANStd
 */
class WPP_GravityForms {
	
    public static $instance = null;
	
    public $font = 'yekan';

    /**
     * Returns an instance of class
     *
     * @return          WPP_GravityForms
     */
    public static function getInstance() {
        if ( self::$instance == null )
            self::$instance = new WPP_GravityForms();

        return self::$instance;
    }

    /**
     * Hooks required tags
     */
    public function __construct() {
        		
		if ( ! class_exists('GFCommon') ) {
            return;
		}
		
		global $wpp_settings;
		
		if ( !empty($wpp_settings) && $wpp_settings ) {
			
			if ( ! isset( $wpp_settings[ 'droidsans_admin' ] ) || $wpp_settings[ 'droidsans_admin' ] != 'disable' ) {
				
				add_filter( 'wpp_plugins_compability_settings', array( $this, 'add_settings' ) );
	
				if ( ! isset( $wpp_settings['gravityforms_droidsans_admin'] ) || $wpp_settings['gravityforms_droidsans_admin'] != 'disable' ) {
					$this->font = 'droidsans';
				}
			}
			
			
		}
		
		add_action('admin_enqueue_scripts', array( $this, 'admin_script' ));
    }
	
	/**
     * Adds desired fonts
     */
    public function admin_script() {
		
		if ( is_rtl() && $this->is_gravity_page() ) {
			wp_register_style( 'gravity-forms-admin-' . $this->font , GF_PARSI_URL . 'assets/css/font.' . $this->font . '.css' );
			wp_enqueue_style( 'gravity-forms-admin-' . $this->font );	
		}
	}
	
	
    /**
     * Checks is gravityforms page
     *
     */
	public function is_gravity_page() {
		
		if ( !class_exists('RGForms') )
			return false;
		
		$current_page = trim(strtolower(RGForms::get("page")));
		return ( substr( $current_page , 0 , 2) == 'gf' || stripos( $current_page, 'gravity' ) !== false );	
	}
	
	
    /**
     * Adds settings for toggle fixing
     *
     * @param           array $old_settings Old settings
     * @return          array New settings
     */
    public function add_settings( $old_settings ) {
		
		$options = array(
            'enable'		=>	__( 'Enable', 'wp-parsidate' ),
            'disable'		=>	__( 'Disable', 'wp-parsidate' )
        );
		
        $settings = array(
            'gravityforms'       =>  array(
                'id'            =>  'gravityforms',
                'name'          =>  __( 'GravityForms', 'GF_FA' ),
                'type'          =>  'header'
            ),
            'gravityforms_droidsans_admin'     =>  array(
                'id'            =>  'gravityforms_droidsans_admin',
                'name'          =>  __( 'Use Droid Sans font for GravityForms Admin', 'GF_FA' ),
                'type'          =>  'radio',
                'options'       =>  $options,
                'std'           =>  'enable',
				'desc'          =>  __( 'By GravityForms.ir', 'GF_FA' )
            ),
        );

        return array_merge( $old_settings, $settings );
    }
}

add_action('plugins_loaded', 'wpp_gravity_forms', 0);
function wpp_gravity_forms() {
	WPP_GravityForms::getInstance();
}