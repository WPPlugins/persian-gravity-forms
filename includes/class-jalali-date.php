<?php if ( ! defined('ABSPATH') ) exit;

class GFParsi_JalaliDate {

	public function __construct() {	
	
		require_once( GF_PARSI_DIR . '/lib/jdf.php');
		
		add_action('gform_field_standard_settings'	, array( $this, 'jalali_checkbox'), 10, 2);
		add_action('gform_editor_js'				, array( $this, 'jalali_settings'));
		add_filter('gform_field_validation'			, array( $this, 'jalali_validator'), 10, 4);	
		add_filter('gform_tooltips'					, array( $this, 'tooltips'));
		add_filter('gform_date_min_year'			, array( $this, 'jalali_date_min' ), 10, 3);
		add_filter('gform_date_max_year'			, array( $this, 'jalali_date_max' ), 10, 3);
		add_action('gform_enqueue_scripts'			, array( $this, 'add_datepicker_jalali' ), 11, 1 );
	
	//	add_action('admin_enqueue_scripts'			, array( $this, 'admin_scripts' ));
	//	add_action('admin_head'						, array( $this, 'admin_head' ));
	}
	
	public function tooltips( $tooltips ) {
		$tooltips["gform_activate_jalali"] = __('<h6>فعالسازی تاریخ شمسی</h6>در صورتی که از چند فیلد تاریخ استفاده میکنید ، فعالسازی تاریخ شمسی یکی از فیلدها کفایت میکند .<br/>تذکر : با توجه به آزمایشی بودن این قسمت ممکن است تداخل توابع سبب ناسازگاری با برخی قالب ها شود.', 'GF_FA');
		return $tooltips;
	}

	public function jalali_settings(){ ?>
		<script type='text/javascript'>
			fieldSettings["date"] += ", .jalali_setting";
			jQuery(document).bind("gform_load_field_settings", function(event, field, form){
				jQuery("#check_jalali").attr("checked", field["check_jalali"] == true);
			});
		</script>
		<?php
	}
	
	public function jalali_checkbox( $position, $form_id ){
		if( $position == 25 ) { ?>
			<li class="jalali_setting field_setting">
				<input type="checkbox" id="check_jalali" onclick="SetFieldProperty('check_jalali', jQuery(this).is(':checked') ? 1 : 0);"/> 
				<label class="inline gfield_value_label" for="check_jalali" class="inline">
					<?php _e( 'Activate Jalali date', 'GF_FA' ); ?>
					<?php gform_tooltip("gform_activate_jalali") ?>
				</label>
			</li>
		<?php	
		}
	}
	
	public function add_datepicker_jalali( $form ) {
		
		if ( !is_admin() && ( wp_script_is( 'gform_datepicker_init' ) || wp_script_is( 'gform_datepicker_init', 'registered' ) ) ) {
			
			$is_jalali = false;
		
			foreach ( $form['fields'] as $field ) {
			
				if ( $field['type'] == 'date' && gfa_get( 'check_jalali', $field ) ) {
					$is_jalali = true;
					break;
				}
			}
		
			if ( $is_jalali ) {
				
				wp_dequeue_script('jquery-ui-datepicker');
				wp_deregister_script('jquery-ui-datepicker');
			
				wp_register_script('jquery-ui-datepicker', GF_PARSI_URL . 'assets/js/jalali.date.picker.js', array( 'jquery', 'jquery-migrate', 'jquery-ui-core', 'gform_gravityforms' ), GF_PARSI_VERSION, true );
				wp_enqueue_script( 'jquery-ui-datepicker' );
			}
					
		}	
	}

	public function admin_head() {
	?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {

				jQuery('.datepicker').datepicker({
					showOn: 'both',
					buttonImage: "<?php echo GFCommon::get_base_url() ?>/images/calendar.png",
					buttonImageOnly: true,
					changeMonth: true,
					changeYear: true,		
					showButtonPanel: true,
					onSelect: function(dateStr, inst) {
						var selectedJalaliDate = new JalaliDate(inst.selectedYear, inst.selectedMonth, inst.selectedDay);
						var date = selectedJalaliDate.getGregorianDate();
						var month= date.getMonth()+1;
						var day = date.getDate() < 10 ? '0'+date.getDate() : date.getDate();
						var months = date.getMonth() < 9 ? '0'+month : month;
						$(this).val(months + '/' + day + '/' + date.getFullYear());
					}
				});

				jQuery('#export_date_start').datepicker({
					changeMonth: true,
					changeYear: true,		
					showButtonPanel: true,
					onSelect: function(dateStr, inst) {
						var selectedJalaliDate = new JalaliDate(inst.selectedYear, inst.selectedMonth, inst.selectedDay);
						var date = selectedJalaliDate.getGregorianDate();
						var month= date.getMonth()+1;
						var day = date.getDate() < 10 ? '0'+date.getDate() : date.getDate();
						var months = date.getMonth() < 9 ? '0'+month : month;
						$(this).val(date.getFullYear() + '-' + months + '-' + day);
					}
				});

				jQuery('#export_date_end').datepicker({
					changeMonth: true,
					changeYear: true,		
					showButtonPanel: true,
					onSelect: function(dateStr, inst) {
						var selectedJalaliDate = new JalaliDate(inst.selectedYear, inst.selectedMonth, inst.selectedDay);
						var date = selectedJalaliDate.getGregorianDate();
						var month= date.getMonth()+1;
						var day = date.getDate() < 10 ? '0'+date.getDate() : date.getDate();
						var months = date.getMonth() < 9 ? '0'+month : month;
						$(this).val(date.getFullYear() + '-' + months + '-' + day);
					}
				});

			});

		</script>
	<?php
	}

	public function admin_scripts() {
			
		if ( $this->is_gravity_page() && ( wp_script_is( 'jquery-ui-datepicker' ) || wp_script_is( 'jquery-ui-datepicker', 'registered' ) ) ) {
		
			wp_dequeue_script('jquery-ui-datepicker');
			wp_deregister_script('jquery-ui-datepicker');
		
			if ( ! wp_script_is( 'jquery-ui-core' ))
				wp_enqueue_script('jquery-ui-core');
			
			wp_register_script('gform_datepicker_jalali_admin', GF_PARSI_URL . 'assets/js/jalali.date.picker.js', array( 'jquery', 'jquery-ui-core' ), GF_PARSI_VERSION, true );
			wp_enqueue_script( 'gform_datepicker_jalali_admin' );
		}
	}
	
	public function jalali_date_min( $min_year, $form, $field ){
		
		if ( $field->type == 'date' && !empty($field->check_jalali) ) {
			$min_year = GF_gregorian_to_jalali($min_year,03,21);
			$min_year = $min_year[0]+1;
		}
		
		return apply_filters( 'jalali_date_min', $min_year, $form, $field );
	}
	
	public function jalali_date_max($max_year, $form, $field ){
		
		if ( $field->type == 'date' && !empty($field->check_jalali) ) {
			$max_year = GF_gregorian_to_jalali($max_year,03,21);
			$max_year = $max_year[0]+20;
		}
		
		return apply_filters( 'jalali_date_max', $max_year, $form, $field );
	}
			
	public function jalali_validator($result, $value, $form, $field){	
		
		if ( $field["type"] == "date" ) {
			
			if ( gfa_get("check_jalali", $field) ) {
				
				if( is_array($value) && rgempty(0, $value) && rgempty(1, $value)&& rgempty(2, $value))
					$value = null;
				
				if( !empty($value) ) {
					
					$format = empty($field["dateFormat"]) ? "mdy" : $field["dateFormat"];
					$date = GFCommon::parse_date($value, $format);
					
					if (!empty($date) ){
						
						if ( intval($date["month"]) >= 1 && intval($date["month"]) <=12 ) {
							
							$min = 1;
							if ( intval($date["month"]) >= 1 && intval($date["month"]) <=6  )
								$max = 31;
							
							if ( intval($date["month"]) >= 7 && intval($date["month"]) <=12 ) 
								$max = 30;
							
							if ( intval($date["month"]) == 12 && intval($date["day"]) >= 1 && intval($date["day"]) <= 30 ) {
								$gregorian = GF_jalali_to_gregorian($date["year"],$date["month"],$date["day"]);
								$day = $gregorian[2];
								$month = $gregorian[1];
								$year = $gregorian[0];
								$target = new DateTime("$year-$month-$day 09:00:00");
								$target = $target->format('Y-m-d H:i:s');
								$target = strtotime ($target);
								$leap_year = GF_jdate('L',$target,'','','en');
								if ( $leap_year != 1 )
									$max = 29;
							}
							
							if ( intval($date["day"]) >= $min && intval($date["day"]) <= $max  ) {
								$gregorian = GF_jalali_to_gregorian($date["year"],$date["month"],$date["day"]);
								$day = $gregorian[2];
								$month = $gregorian[1];
								$year = $gregorian[0];
								$result["is_valid"] = $this->jalali_checkdate($month, $day, $year);
							}
							else 
								$result["is_valid"] = false;
						
						}
						else 
							$result["is_valid"] = false;
					}
					else 
						$result["is_valid"] = false;
					
					if( empty($date) || !$result["is_valid"] ) {
						
						$format_name = '';
						switch($format) {
							
							case "mdy" :
								$format_name = "mm/dd/yyyy";
								break;
							
							case "dmy" :
								$format_name = "dd/mm/yyyy";
								break;
							case "dmy_dash" :
								$format_name = "dd-mm-yyyy";
								break;
							case "dmy_dot" :
								$format_name = "dd.mm.yyyy";
								break;
							
							case "ymd_slash" :
								$format_name = "yyyy/mm/dd";
								break;
								
							case "ymd_dash" :
								$format_name = "yyyy-mm-dd";
								break;
							case "ymd_dot" :
								$format_name = "yyyy.mm.dd";
								break;
						}
						
						$result["is_valid"] = false;
						$message = $field["dateType"] == "datepicker" ? sprintf(__("Please enter a valid date in the format (%s).", "gravityforms"), $format_name) : __("Please enter a valid date.", "gravityforms");
						$result["message"] = empty($field["errorMessage"]) ? $message : $field["errorMessage"];
					}
					else
						$result["is_valid"] = true;
				}
			}
		}
		
		return $result;
	}

	public function jalali_checkdate($month, $day, $year){
        if( empty($month) || !is_numeric($month) || empty($day) || !is_numeric($day) || empty($year) || !is_numeric($year) || strlen($year) != 4 )
            return false;
        return checkdate($month, $day, $year);
    }	
	
	public function is_gravity_page() {
		
		if ( !class_exists('RGForms') )
			return false;
		
		$current_page = trim(strtolower(RGForms::get("page")));
		return ( substr( $current_page , 0 , 2) == 'gf' || stripos( $current_page, 'gravity' ) !== false );	
	}
}

new GFParsi_JalaliDate();