<?php if ( ! defined('ABSPATH') ) exit;

class GFParsi_Post_Content_Merge_Tags {

    public static $_entry = null;

    private static $instance = null;
	
    public static function get_instance( $args = array() ) {

        if( self::$instance == null )
            self::$instance = new self( $args );

        return self::$instance;
    }

    function __construct( $args ) {

        if( ! class_exists( 'GFForms' ) )
            return;

        $this->_args = wp_parse_args( $args, array(
            'auto_append_lead' => true, // true, false or array of form IDs
            'encrypt_lead'     => false,
        ) );

        add_filter( 'the_content', array( $this, 'replace_merge_tags' ), 1 );
        add_filter( 'gform_replace_merge_tags', array( $this, 'replace_encrypt_entry_id_merge_tag' ), 10, 3 );

        if( ! empty( $this->_args['auto_append_lead'] ) ) {
            add_filter( 'gform_confirmation', array( $this, 'append_lead_parameter' ), 20, 3 );
        }

    }

    function replace_merge_tags( $post_content ) {

        $entry = $this->get_entry();
        if( ! $entry )
            return $this->replace_field_label_merge_tags( $post_content, '' );
		
			
		$confirm_time = gform_get_meta( rgar($entry,'id') , 'gform_page_confirm_time');
			
		if ( !empty($confirm_time) && $confirm_time + 90 < time() )
			return $this->replace_field_label_merge_tags( $post_content, '' );
			
		if (empty($confirm_time))
			gform_update_meta($entry['id'], 'gform_page_confirm_time', time() );
		
        $form = GFFormsModel::get_form_meta( $entry['form_id'] );

        $post_content = $this->replace_field_label_merge_tags( $post_content, $form );
        $post_content = GFCommon::replace_variables( $post_content, $form, $entry, false, false, false );

        return $post_content;
    }

    function replace_field_label_merge_tags( $text, $form ) {
	
		if ( !empty($form) ) {
			
			preg_match_all( '/{([^:]+?)}/', $text, $matches, PREG_SET_ORDER );
			if( empty( $matches ) )
				return $text;

			foreach( $matches as $match ) {

				list( $search, $field_label ) = $match;
			
				foreach( $form['fields'] as $field ) {
				
					$full_input_id = false;
					$matches_admin_label = rgar( $field, 'adminLabel' ) == $field_label;
					$matches_field_label = false;

					if( is_array( $field['inputs'] ) ) {
						foreach( $field['inputs'] as $input ) {
							if( GFFormsModel::get_label( $field, $input['id'] ) == $field_label ) {
								$matches_field_label = true;
								$input_id = $input['id'];
								break;
							}
						}
					} else {
						$matches_field_label = GFFormsModel::get_label( $field ) == $field_label;
						$input_id = $field['id'];
					}

					if( ! $matches_admin_label && ! $matches_field_label )
						continue;

					$replace = sprintf( '{%s:%s}', $field_label, (string) $input_id );
					$text = str_replace( $search, $replace, $text );

					break;
				}
			}
        }
		else {
			
			/*
			preg_match_all( '/{[^{]*?:(\d+(\.\d+)?)(:(.*?))?}/mi', $text, $matches, PREG_SET_ORDER );
			if( !empty( $matches ) ) {
				foreach( $matches as $match ) {
					if ( isset($match[0]))
						$text = str_replace( $match[0], '' , $text );
				}
			}
			
			unset($matches);
			
			preg_match_all( '/{([^:]+?)}/', $text, $matches, PREG_SET_ORDER );
			if( !empty( $matches ) ) {
				foreach( $matches as $match ) {
					if ( isset($match[0]))
						$text = str_replace( $match[0], '' , $text );
				}
			}
			*/
			
		}

        return $text;
    }

    function replace_encrypt_entry_id_merge_tag( $text, $form, $entry ) {

        if( strpos( $text, '{encrypted_entry_id}' ) === false ) {
            return $text;
        }

        // $entry is not always a "full" entry
        $entry_id = rgar( $entry, 'id' );
        if( $entry_id ) {
            $entry_id = $this->prepare_lead( $entry['id'], true );
        }

        return str_replace( '{encrypted_entry_id}', $entry_id, $text );
    }

    function append_lead_parameter( $confirmation, $form, $entry ) {

        $is_ajax_redirect = is_string( $confirmation ) && strpos( $confirmation, 'gformRedirect' );
        $is_redirect      = is_array( $confirmation ) && isset( $confirmation['redirect'] );

        if( ! $this->is_auto_lead_enabled( $form ) || ! ( $is_ajax_redirect || $is_redirect ) ) {
            return $confirmation;
        }

        $lead = $this->prepare_lead( $entry['id'] );

        if( $is_ajax_redirect ) {
            preg_match_all( '/gformRedirect.+?(http.+?)(?=\'|")/', $confirmation, $matches, PREG_SET_ORDER );
            list( $full_match, $url ) = $matches[0];
            $redirect_url = add_query_arg( array( 'lead' => $lead ), $url );
            $confirmation = str_replace( $url, $redirect_url, $confirmation );
        } else {
            $redirect_url             = add_query_arg( array( 'lead' => $lead ), $confirmation['redirect'] );
            $confirmation['redirect'] = $redirect_url;
        }

        return $confirmation;
    }

    function prepare_lead( $entry_id, $force_encrypt = false ) {

        $lead = $entry_id;
        $do_encrypt = $force_encrypt || $this->_args['encrypt_lead'];

        if( $do_encrypt && is_callable( array( 'GFCommon', 'encrypt' ) ) ) {
            $lead = rawurlencode( GFCommon::encrypt( $lead ) );
        }

        return $lead;
    }

    function get_entry() {

        if( ! self::$_entry ) {

            $entry_id = $this->get_entry_id();
            if( ! $entry_id )
                return false;

            $entry = GFFormsModel::get_lead( $entry_id );
            if( empty( $entry ) )
                return false;

            self::$_entry = $entry;

        }

        return self::$_entry;
    }

    function get_entry_id() {

        $entry_id = rgget( 'lead' );
        if( $entry_id ) {
            return $this->maybe_decrypt_entry_id( $entry_id );
        }

        $post = get_post();
        if( $post ) {
            $entry_id = get_post_meta( $post->ID, '_gform-entry-id', true );
        }

        return $entry_id ? $entry_id : false;
    }

    function maybe_decrypt_entry_id( $entry_id ) {

	    // if encryption is enabled, 'lead' parameter MUST be encrypted
	    $do_encrypt = $this->_args['encrypt_lead'];

        if( ! $entry_id ) {
            return null;
        } else if( ! $do_encrypt && is_numeric( $entry_id ) && intval( $entry_id ) > 0 ) {
            return $entry_id;
        } else {
            // gEYs6Cqzh1akKc7Y4RGkV8HtcJqQZRmNH+ONxuFEvXM
            // 0FSCGpzzmt+4Y05fFsJ4ipRZfqD/zdi2ecEeMMRKCjc=
            $entry_id = is_callable( array( 'GFCommon', 'decrypt' ) ) ? GFCommon::decrypt( $entry_id ) : $entry_id;
            return intval( $entry_id );
        }

    }

    function is_auto_lead_enabled( $form ) {

        $auto_append_lead = $this->_args['auto_append_lead'];

        if( is_bool( $auto_append_lead ) && $auto_append_lead === true )
            return true;

        if( is_array( $auto_append_lead ) && in_array( $form['id'], $auto_append_lead ) )
            return true;

        return false;
    }

}

function GFParsi_Post_Content_Merge_Tags( $args = array() ) {
    return GFParsi_Post_Content_Merge_Tags::get_instance( $args );
}

GFParsi_Post_Content_Merge_Tags();