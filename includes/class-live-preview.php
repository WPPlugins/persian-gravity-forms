<?php if ( ! defined('ABSPATH') ) exit;

class GFParsi_LivePreview {
    
    var $post_type = 'gf_live_preview';
    
    public function __construct( $args = array() ) {
        
        if( ! property_exists( 'GFCommon', 'version' ) || ! version_compare( GFCommon::$version, '1.8', '>=' ) )
            return;
    	
    	$this->_args = wp_parse_args( $args, array( 
    		'id' => 0,
    		'title' => true,
    		'description' => true,
    		'ajax' => true
    	) );
    	
    	add_action( 'init', array( $this, 'register_preview_post_type' ) );
    	add_action( 'wp', array( $this, 'maybe_load_preview_functionality' ) );
        add_action( 'admin_footer', array( $this, 'display_preview_link' ) );
        
        add_action( 'admin_footer', array( $this, 'display_preview_link' ) );
		add_filter( 'gform_form_actions', array( $this, 'gform_form_actions' ), 10, 2 );
    }
    
    public function register_preview_post_type() {
		
		$args = array(
			'label' => __( 'Form Preview', 'GF_FA' ),
			'description' => __( 'A post type created for previewing Gravity Forms forms on the frontend.', 'GF_FA' ),
			'public' => false,
			'publicly_queryable' => true,
			'has_archive' => true,
			'can_export' => false,
			'supports' => false
		);
		
		register_post_type( $this->post_type, $args );
		
		// create preview post
		$preview_post = get_posts( array( 'post_type' => $this->post_type ) );
		if( empty( $preview_post ) ) {
			$post_id = wp_insert_post( array( 
				'post_type' => $this->post_type,
				'post_title' => __( 'Form Preview', 'GF_FA' ),
				'post_status' => 'publish'
			) );
		}
		
    }
    
    public function maybe_load_preview_functionality() {
    	global $wp_query;
    	
		if( ! $this->is_live_preview() )
			return;
		
		$this->live_preview_hooks();
		
		// quick hack for GF to ensure scripts/styles are loaded correctly
		foreach( $wp_query->posts as &$post ) {
			$post->post_content = $this->get_shortcode();
		}
		
    }
    
    public function live_preview_hooks() {
		
	    add_filter( 'template_include', array( $this, 'load_preview_template' ) );
		add_filter( 'the_content', array( $this, 'modify_preview_post_content' ) );
		
    }
    
    public function gform_form_actions(  $form_actions , $form_id ) {
	
	
		if ( !empty($_GET['trash']) && $_GET['trash'] == 1 )
			return $form_actions;
			
		$capabilities = array( 'gravityforms_view_entries', 'gravityforms_edit_entries', 'gravityforms_delete_entries' );
		
        $ajax_true_url = get_bloginfo("wpurl") . '/?post_type='.$this->post_type.'&id=' . $form_id;
        $ajax_false_url = get_bloginfo("wpurl") . '/?post_type='.$this->post_type.'&ajax=false&id=' . $form_id;
		
		$sub_menu_items = array();
		
		$sub_menu_items[] = array(
			'url'          => $ajax_true_url,
			'label'        => __( 'Ajax option enabled' , 'GF_FA'),
			'capabilities' => $capabilities
		);
			
		$sub_menu_items[] = array(
			'url'          => $ajax_false_url,
			'label'        => __( 'Ajax option disabled' , 'GF_FA'),
			'capabilities' => $capabilities
		);
		
		$form_actions['live_preview'] = array(
			'label'          => __( 'Live Preview', 'GF_FA' ),
			'icon'           => '<i class="fa fa-cogs fa-lg"></i>',
			'title'          => __( 'Live Preview', 'GF_FA' ),
			'url'            => '',
			'menu_class'     => 'gf_form_toolbar_settings',
			'link_class'     => 'gf_toolbar_active',
			'sub_menu_items' => $sub_menu_items,
			'capabilities'   => $capabilities,
			'priority'       => 650,
		);
	
		return $form_actions;
	}
	
    public function display_preview_link() {
        
        if( ! $this->is_applicable_page() || apply_filters( 'gf_live_preview_page' , false) )
            return;
        
        $form_id = apply_filters( 'gf_live_preview_id' , rgget( 'id' ) );
        $ajax_true_url = get_bloginfo("wpurl") . '/?post_type='.$this->post_type.'&id=' . $form_id;
        $ajax_false_url = get_bloginfo("wpurl") . '/?post_type='.$this->post_type.'&ajax=false&id=' . $form_id;
        ?>
        
        <script type="text/javascript">
        (function($){
            $(  '<li class="gf_form_toolbar_preview">'+
					'<a style="position:relative" id="gf-live-preview" target="_blank" href="<?php echo $ajax_true_url; ?>" class="" >' +
						'<i class="fa fa-eye" style="position: absolute; text-shadow: 0px 0px 5px rgb(255, 255, 255); z-index: 99; line-height: 7px; left: 0px font-size: 9px; top: 6px; background-color: rgb(243, 243, 243);"></i>' +
						'<i class="fa fa-file-o" style="margin-left: 5px; line-height: 12px; font-size: 18px; position: relative; top: 2px;"></i>' +
						'<?php _e( 'Live Preview', 'GF_FA' ); ?>' +
					'</a>'+
					'<div class="gf_submenu"><ul>'+
						'<li class=""><a target="_blank" href="<?php echo $ajax_true_url; ?>"><?php _e( 'Ajax option enabled' , 'GF_FA'); ?></a></li>'+
						'<li class=""><a target="_blank" href="<?php echo $ajax_false_url; ?>"><?php _e( 'Ajax option disabled', 'GF_FA' ); ?></a></li>'+
					'</ul></div>'+
				'</li>' )
                .insertAfter( 'li.gf_form_toolbar_preview' );
        })(jQuery);
        </script>
        <?php
    }
    
    public function is_applicable_page() {
        return in_array( rgget( 'page' ), array( 'gf_edit_forms', 'gf_entries' ) ) && rgget( 'id' );
    }
	
	public function load_preview_template( $template ) {
	    return get_page_template();
	}
    
    public function modify_preview_post_content( $content ) {
		return $this->get_shortcode();
    }
    
    public function get_shortcode( $args = array() ) {
    	
    	if( ! is_user_logged_in() )
    		return '<p>' . __( 'You need to log in to preview forms.', 'GF_FA') . '</p>' . wp_login_form( array( 'echo' => false ) );
    	
    	if( ! GFCommon::current_user_can_any( 'gravityforms_preview_forms' ) )
    		return __( 'Oops! It doesn\'t look like you have the necessary permission to preview this form.', 'GF_FA' );
    	
    	if( empty( $args ) )
    		$args = $this->get_shortcode_parameters_from_query_string();
    	
    	extract( wp_parse_args( $args, $this->_args ) );
    	
    	$title = $title === true ? 'true' : 'false';
    	$description = $description === true ? 'true' : 'false';
    	$ajax = $ajax === true ? 'true' : 'false';
    	
		return "[gravityform id='$id' title='$title' description='$description' ajax='$ajax']";
    }
    
    public function get_shortcode_parameters_from_query_string() {
		return array_filter( array(
			'id'          => rgget( 'id' ),
			'title'       => rgget( 'title' ),
			'description' => rgget( 'description' ),
			'ajax'        => rgget( 'ajax' )
		) );
    }
    
    public function is_live_preview() {
		return is_post_type_archive( $this->post_type );
    }
    
}
new GFParsi_LivePreview();