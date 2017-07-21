<?php if ( ! defined( 'ABSPATH' ) ) exit;

class GFParsi_MultipageNavigation {

    public $_args = array();

    private static $script_displayed;

    function __construct() {
		
		if ( is_admin() ) 
			return;
		
        $this->_args = array(
            'activate_on_last_page' => apply_filters( 'gf_mn_activate_on_last_page' , true )
        );

        add_filter( 'gform_pre_render', array( $this, 'output_navigation_script' ), 10, 2 );
    }

    function output_navigation_script( $form, $is_ajax ) {

        // only apply this to multi-page forms
        if( count($form['pagination']['pages']) <= 1 )
            return $form;

        $this->register_script( $form );

        if( ! $this->_args['activate_on_last_page'] || $this->is_last_page( $form ) || $this->is_last_page_reached() ) {
            $input = '<input id="gfir_last_page_reached" name="gfir_last_page_reached" value="1" type="hidden" />';
            add_filter( "gform_form_tag_{$form['id']}", create_function('$a', 'return $a . \'' . $input . '\';' ) );
        }

        // only output the gfirmpn object once regardless of how many forms are being displayed
        // also do not output again on ajax submissions
        if( self::$script_displayed || ( $is_ajax && rgpost('gform_submit') ))
            return $form;

        ?>

        <script type="text/javascript">

            (function($){

                window.gfirmpnObj = function( args ) {

                    this.formId = args.formId;
                    this.formElem = jQuery('form#gform_' + this.formId);
                    this.currentPage = args.currentPage;
                    this.lastPage = args.lastPage;
                    this.activateOnLastPage = args.activateOnLastPage;
   
                    this.init = function() {

                        // if this form is ajax-enabled, we'll need to get the current page via JS
                        if( this.isAjax() )
                            this.currentPage = this.getCurrentPage();

                        if( !this.isLastPage() && !this.isLastPageReached() )
                            return;

                        var gfirmpn = this;
                        var steps = $('form#gform_' + this.formId + ' .gf_step');

                        steps.each(function(){

                            var stepNumber = parseInt( $(this).find('span.gf_step_number').text() );

                            if( stepNumber != gfirmpn.currentPage ) {
                                $(this).html( gfirmpn.createPageLink( stepNumber, $(this).html() ) )
                                    .addClass('gfir-step-linked');
                            } else {
                                $(this).addClass('gfir-step-current');
                            }

                        });

                        $(document).on('click', '#gform_' + this.formId + ' a.gfirmpn-page-link', function(event){
                            event.preventDefault();

                            var hrefArray = $(this).attr('href').split('#');
                            if( hrefArray.length >= 2 ) {
                                var pageNumber = hrefArray.pop();
                                gfirmpn.postToPage( pageNumber, ! $( this ).hasClass( 'gfirmp-default' ) );
                            }

                        });

                    }

                    this.createPageLink = function( stepNumber, HTML ) {
                        return '<a href="#' + stepNumber + '" class="gfirmpn-page-link gfirmpn-default">' + HTML + '</a>';
                    }

                    this.postToPage = function( page ) {
                        this.formElem.append('<input type="hidden" name="gfir_page_change" value="1" />');
                        this.formElem.find( 'input[name="gform_target_page_number_' + this.formId + '"]' ).val( page );
                        this.formElem.submit();
                    }

                    this.getCurrentPage = function() {
                        return this.formElem.find( 'input#gform_source_page_number_' + this.formId ).val();
                    }

                    this.isLastPage = function() {
                        return this.currentPage >= this.lastPage;
                    }

                    this.isLastPageReached = function() {
                        return this.formElem.find('input[name="gfir_last_page_reached"]').val() == true;
                    }

                    this.isAjax = function() {
                        return this.formElem.attr('target') == 'gform_ajax_frame_' + this.formId;
                    }

                    this.init();

                }

            })(jQuery);

        </script>

        <?php
        self::$script_displayed = true;
        return $form;
    }

    function register_script( $form ) {

        $page_number = GFFormDisplay::get_current_page($form['id']);
        $last_page = count($form['pagination']['pages']);

        $args = array(
            'formId' => $form['id'],
            'currentPage' => $page_number,
            'lastPage' => $last_page,
            'activateOnLastPage' => $this->_args['activate_on_last_page'],
        );

        $script = "window.gfirmpn = new gfirmpnObj(" . json_encode( $args ) . ");";
        GFFormDisplay::add_init_script( $form['id'], 'gfirmpn', GFFormDisplay::ON_PAGE_RENDER, $script );

    }

    function is_last_page( $form ) {

        $page_number = GFFormDisplay::get_current_page($form['id']);
        $last_page = count($form['pagination']['pages']);

        return $page_number >= $last_page;
    }

    function is_last_page_reached() {
        return rgpost('gfir_last_page_reached');
    }

}
$gfir_multipage_navigation = new GFParsi_MultipageNavigation( array() );