<?php if ( ! defined('ABSPATH') ) exit;

class GFParsi_Newsletter {

    private static $instance = null;

    public static function get_instance() {
        if( null == self::$instance )
            self::$instance = new self;
        return self::$instance;
    }

    private function __construct() {
	    add_filter( 'gform_notification_events', array( $this, 'add_manual_notification_event' ) );
	    add_filter( 'gform_before_resend_notifications', array( $this, 'add_notification_filter' ) );
    }

	public function add_notification_filter( $form ) {
		add_filter( 'gform_notification', array( $this, 'evaluate_notification_conditional_logic' ), 10, 3 );
		return $form;
	}

	public function add_manual_notification_event( $events ) {
		$events['manual'] = __( 'Newsletter', 'GF_FA' );
		return $events;
	}

	public function evaluate_notification_conditional_logic( $notification, $form, $entry ) {

		// if it fails conditional logic, suppress it
		if( $notification['event'] == 'manual' && ! GFCommon::evaluate_conditional_logic( rgar( $notification, 'conditionalLogic' ), $form, $entry ) ) {
			add_filter( 'gform_pre_send_email', array( $this, 'abort_next_notification' ) );
		}

		return $notification;
	}

	public function abort_next_notification( $args ) {
		remove_filter( 'gform_pre_send_email', array( $this, 'abort_next_notification' ) );
		$args['abort_email'] = true;
		return $args;
	}

}

function GFParsi_Newsletter() {
    return GFParsi_Newsletter::get_instance();
}

GFParsi_Newsletter();