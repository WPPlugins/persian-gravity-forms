<?php
if (!defined('ABSPATH')) exit;

class GFParsi
{

    public $author = 'HANNANStd';
    public $version = GF_PARSI_VERSION;

    protected $language;
    protected $is_persian;

    private $notice_ver = 4;
    private $feed = 'http://gravityforms.ir/feed/';

    private $files = array(
        'live-preview',
        'news-letter',
        'post-content-merge-tags',
        'pre-submission',
        'snippets',
        'subtotal-calc',
        'wpp-gravity-forms',
        'iran-cities',
        'multipage-navigation',
        'melli-code',
        'jalali-date',
    );

    public function __construct()
    {

        if (!class_exists('GFCommon')) {
            add_action('admin_notices', array($this, 'GFCommon_admin_notice'));
            return;
        }

        if (!get_option('persian_gf_notice_v' . $this->notice_ver)) {
            add_action('admin_notices', array($this, 'update_admin_notice'));
            add_action('wp_ajax_nopriv_gf_dismiss_admin_notice', array($this, 'gf_dismiss_admin_notice'));
            add_action('wp_ajax_gf_dismiss_admin_notice', array($this, 'gf_dismiss_admin_notice'));
        }

        //# فراخوانی فایل های ترجمه
        add_filter('load_textdomain_mofile', array($this, 'load_translate'), 10, 2);

        $this->language();
        $this->include_files();

        //# ;)
        add_action('admin_head', array($this, 'remove_lic'), 9999);
        add_filter('update_footer', array($this, 'admin_footer'), 11);

        //#اضافه کردن وضعیت خصوصی به وضعیت پست ها
        add_action('gform_post_status_options', array($this, 'add_private_post_status'));

        //# خبر خوان در داشبورد ادمین
        add_action('wp_dashboard_setup', array($this, 'rss_dashboard'), 1);

        //#نمایش وضعیت پرداخت ها
        add_action('gform_entries_first_column', array($this, 'show_payment_status'), 10, 5);

        //# تبدیل فیلد های با ورودی غیر تکراری فقط برای پرداخت های موفق
        if (version_compare(GFCommon::$version, '1.9.19', '>'))
            add_filter('gform_is_duplicate', array($this, 'better_noDuplicate'), 10, 4);
        else
            add_filter('gform_is_duplicate', array($this, 'better_noDuplicate_old'), 10, 4);

        //# کد رهگیری برای همه فرم ها
        add_action('gform_entry_created', array($this, 'transaction_id'), 10, 2);
        add_filter($this->author . '_gf_rand_transaction_id', array($this, 'transaction_id_mask'), 10, 3);

        //# واحد پولی ایران
        add_filter('gform_currencies', array($this, 'iran_currencies'));

        //# اضافه کردن گزینه های ایران به گزینه های آماده
        add_filter('gform_predefined_choices', array($this, 'predefined_choices'), 1);

        //#جداسازی استایل های فارسی ساز از حالت بدون تداخل
        add_filter('gform_noconflict_styles', array($this, 'noconflict_styles'));
        add_filter('gform_noconflict_scripts', array($this, 'noconflict_scripts'));

        //# راستچین سازی اینلاین فرم
        add_action('wp_footer', array($this, 'frontend_rtl'));

        //راستچین سازی استایل پرینت
        add_filter('gform_print_styles', array($this, 'print_rtl'), 10, 1);

        //#استایل و اسکریپت های ادمین
        add_action('admin_enqueue_scripts', array($this, 'admin_rtl'));

        //# اضافه کردن برچسب های ادغام به تاییدیه و اعلان
        add_filter('gform_admin_pre_render', array($this, 'merge_tags_admin'));
        add_filter('gform_replace_merge_tags', array($this, 'replace_merge_tags'), 999, 7);
    }

    public function GFCommon_admin_notice()
    {
        $class = 'notice notice-error';
        $message = sprintf(__('Persian Gravity Forms requires Gravityforms Core. Please install or activate it to continue. %sSee FAQ%s', 'GF_FA'), '<a href="http://gravityforms.ir/11378/" target="_blank">', '</a>');
        printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
    }

    public function update_admin_notice()
    {

        $message = __('<strong>قابل توجه خریداران افزونه "پیامک گرویتی فرم":</strong> هم اکنون میتوانید آپدیت نسخه 2.2.5 افزونه پیامک که شامل رفع باگ، تغییرات IP وبسرویس ها، سازگاری با نسخه های جدید گرویتی فرم و اضافه شدن منطق شرطی چندگانه می باشد را از لینک زیر به صورت آنی دانلود نمایید:', 'GF_FA');
        $message .= '<br>';
        $message .= '<a target="_blank" href="https://gravityforms.ir/dowload-sms-update/">دریافت آپدیت افزونه پیامک</a>';

        printf('<div class="notice notice-success gf_support_notice is-dismissible"><p>%s</p></div>', $message);
        $this->is_dismissible();
    }

    private function is_dismissible()
    {
        ?>
        <script type="text/javascript">
            jQuery(document).on("click", ".gf_support_notice .notice-dismiss", function () {
                jQuery.ajax({
                    url: "<?php echo admin_url('admin-ajax.php') ?>",
                    type: "post",
                    data: {
                        action: "gf_dismiss_admin_notice",
                        security: "<?php echo wp_create_nonce("gf_dismiss_admin_notice"); ?>",
                    },
                    success: function (response) {
                    }
                });
                return false;
            });
        </script>
        <?php
    }

    public function gf_dismiss_admin_notice()
    {
        check_ajax_referer('gf_dismiss_admin_notice', 'security');

        update_option('persian_gf_notice_v' . $this->notice_ver, 'true');

        for ($i = 1; $i < $this->notice_ver; $i++)
            delete_option('persian_gf_notice_v' . $i);

        die();
    }

    public function language()
    {
        $this->language = $this->language == null ? get_locale() : $this->language;
        $wp_lang = defined('WPLANG') ? get_option('WPLANG', WPLANG) : '';

        $this->is_persian = substr($this->language, 0, 2) == 'fa';
        $this->is_persian = $this->is_persian || (!empty($wp_lang) && substr($wp_lang, 0, 2) == 'fa');
        $this->is_persian = $this->is_persian || (defined('ICL_LANGUAGE_CODE') && substr(ICL_LANGUAGE_CODE, 0, 2) == 'fa');
    }


    public function include_files()
    {
        require_once GF_PARSI_DIR . '/lib/functions.php';
        foreach ((array)$this->files as $file) {
            require_once GF_PARSI_DIR . '/includes/class-' . $file . '.php';
        }
    }

    public function is_gravity_page()
    {
        if (!class_exists('RGForms'))
            return false;

        $current_page = trim(strtolower(rgget("page")));
        return (substr($current_page, 0, 2) == 'gf' || stripos($current_page, 'gravity') !== false);
    }


    public function admin_footer($text)
    {
        if ($this->is_gravity_page())
            $text = sprintf(__("Thanks for choosing %sPersian Gravity Forms%s", "GF_FA"), '<a href="http://gravityforms.ir" target="_blank">', "</a>");
        return $text;
    }

    public function remove_lic()
    {
        if (get_option('gform_pending_installation')) {

            update_option('gform_pending_installation', false);
            $current_version = get_option('rg_form_version');

            if ($current_version === false) {
                if (class_exists('GFCommon'))
                    update_option('rg_form_version', GFCommon::$version);
                else
                    update_option('rg_form_version', '2.0.0');
            }
        }

        if ($this->is_gravity_page()) {

            if (is_rtl()) { ?>
                <style type="text/css">
                    .mt-gform_notification_message, .mt-form_confirmation_message {
                        margin-right: -30px !important;
                    }
                </style>
            <?php } ?>

            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    $('img').each(function () {
                        if (this.title.indexOf("licensed") !== -1 || this.alt.indexOf("licensed") !== -1)
                            $(this).hide().parent("a").hide().parent("div").hide();
                    });
                });
            </script>
            <?php
        }
    }

    public function noconflict_scripts($scripts)
    {
        //$scripts[] = '';
        return $scripts;
    }

    public function noconflict_styles($styles)
    {
        $styles[] = "gravity-forms-admin-rtl";
        $styles[] = "gravity-forms-admin-yekan";
        $styles[] = "gravity-forms-admin-droidsans";
        $styles[] = "wp-parsi-fonts";
        $styles[] = "print_entry";
        return $styles;
    }

    public function rss_dashboard()
    {
        if (current_user_can('manage_options'))
            wp_add_dashboard_widget('persiangf_wd_hannanstd', __('Persian Gravity Forms Dashboard', 'GF_FA'), array($this, 'rss_widget'));
    }

    public function rss_widget()
    {

        $rss = fetch_feed($this->feed);

        if (is_wp_error($rss)) {
            if (is_admin())
                printf(__('<strong>RSS Error</strong>', 'GF_FA'));
            return;
        }

        if (!$rss->get_item_quantity()) {
            printf(__('Apparently, There are no updates to show!', 'GF_FA'));
            $rss->__destruct();
            unset($rss);
            return;
        }

        $items = 5;
        $i = 1;

        echo '<div class="rss-widget"><ul>';
        foreach ((array)$rss->get_items(0, $items) as $item) {
            $link = esc_url(strip_tags($item->get_link()));
            $title = esc_html($item->get_title());
            $content = $item->get_content();
            $content = wp_html_excerpt($content, 250) . ' ...';
            echo '<li>';
            if ($i == 1)
                echo "<a class='rsswidget a1' target='_blank' href='$link'>$title</a><div class='rssSummary'>$content</div><hr/>";
            else
                echo "<a class='a2' target='_blank' href='$link'>$title</a>";
            echo '</li>';
            $i++;
        }
        echo '</ul></div>';

        $rss->__destruct();
        unset($rss);
    }


    public function load_translate($mo_file, $domain)
    {

        if (strpos($mo_file, 'fa_IR.mo') !== false) {

            $domains = array(
                'gravityforms' => array(
                    'languages/gravityforms-fa_IR.mo' => 'gravityforms/gravityforms-fa_IR.mo'
                ),
                'gravityformscoupons' => array(
                    'languages/gravityformscoupons-fa_IR.mo' => 'gravityformscoupons/gravityformscoupons-fa_IR.mo'
                ),
                'gravityformsmailchimp' => array(
                    'languages/gravityformsmailchimp-fa_IR.mo' => 'gravityformsmailchimp/gravityformsmailchimp-fa_IR.mo'
                ),
                'gravityformspolls' => array(
                    'languages/gravityformspolls-fa_IR.mo' => 'gravityformspolls/gravityformspolls-fa_IR.mo'
                ),
                'gravityformsquiz' => array(
                    'languages/gravityformsquiz-fa_IR.mo' => 'gravityformsquiz/gravityformsquiz-fa_IR.mo'
                ),
                'gravityformssignature' => array(
                    'languages/gravityformssignature-fa_IR.mo' => 'gravityformssignature/gravityformssignature-fa_IR.mo'
                ),
                'gravityformssurvey' => array(
                    'languages/gravityformssurvey-fa_IR.mo' => 'gravityformssurvey/gravityformssurvey-fa_IR.mo'
                ),
                'gravityformsuserregistration' => array(
                    'languages/gravityformsuserregistration-fa_IR.mo' => 'gravityformsuserregistration/gravityformsuserregistration-fa_IR.mo'
                ),
                'gravityformsauthorizenet' => array(
                    'languages/gravityformsauthorizenet-fa_IR.mo' => 'gravityformsauthorizenet/gravityformsauthorizenet-fa_IR.mo'
                ),
                'gravityformsaweber' => array(
                    'languages/gravityformsaweber-fa_IR.mo' => 'gravityformsaweber/gravityformsaweber-fa_IR.mo'
                ),
                'gravityformscampaignmonitor' => array(
                    'languages/gravityformscampaignmonitor-fa_IR.mo' => 'gravityformscampaignmonitor/gravityformscampaignmonitor-fa_IR.mo'
                ),
                'gravityformsfreshbooks' => array(
                    'languages/gravityformsfreshbooks-fa_IR.mo' => 'gravityformsfreshbooks/gravityformsfreshbooks-fa_IR.mo'
                ),
                'gravityformspaypal' => array(
                    'languages/gravityformspaypal-fa_IR.mo' => 'gravityformspaypal/gravityformspaypal-fa_IR.mo'
                ),
                'gravityformspaypalpro' => array(
                    'languages/gravityformspaypalpro-fa_IR.mo' => 'gravityformspaypalpro/gravityformspaypalpro-fa_IR.mo'
                ),
                'gravityformspaypalpaymentspro' => array(
                    'languages/gravityformspaypalpaymentspro-fa_IR.mo' => 'gravityformspaypalpaymentspro/gravityformspaypalpaymentspro-fa_IR.mo'
                ),
                'gravityformstwilio' => array(
                    'languages/gravityformstwilio-fa_IR.mo' => 'gravityformstwilio/gravityformstwilio-fa_IR.mo'
                ),
                'gravityformsstripe' => array(
                    'languages/gravityformsstripe-fa_IR.mo' => 'gravityformsstripe/gravityformsstripe-fa_IR.mo'
                ),
                'gravityformszapier' => array(
                    'languages/gravityformszapier-fa_IR.mo' => 'gravityformszapier/gravityformszapier-fa_IR.mo'
                ),
                'sticky-list' => array(
                    'languages/sticky-list-fa_IR.mo' => 'gravityformsstickylist/gravityformsstickylist-fa_IR.mo'
                ),
                'gf-limit' => array(
                    'languages/gf-limit-fa_IR.mo' => 'gravityformsquantitylimits/gravityformsquantitylimits-fa_IR.mo'
                )
            );

            if (isset($domains[$domain]) && is_array($domains[$domain])) {
                foreach ($domains[$domain] as $path => $file) {
                    if (substr($mo_file, -strlen($path)) == $path) {
                        $new_file = dirname(__FILE__) . '/languages/' . $file;
                        if (is_readable($new_file))
                            $mo_file = $new_file;
                    }
                }
            }
        }

        return $mo_file;
    }

    public function better_noDuplicate($count, $form_id, $field, $value)
    {
        global $wpdb;

        $lead_detail_table_name = GFFormsModel::get_lead_details_table_name();
        $lead_table_name = GFFormsModel::get_lead_table_name();
        $sql_comparison = 'ld.value = %s';

        switch (GFFormsModel::get_input_type($field)) {
            case 'time':
                $value = sprintf("%02d:%02d %s", $value[0], $value[1], $value[2]);
                break;
            case 'date':
                $value = GFFormsModel::prepare_date($field->dateFormat, $value);
                break;
            case 'number':
                $value = GFCommon::clean_number($value, $field->numberFormat);
                break;
            case 'phone':
                $value = str_replace(array(')', '(', '-', ' '), '', $value);
                $sql_comparison = 'replace( replace( replace( replace( ld.value, ")", "" ), "(", "" ), "-", "" ), " ", "" ) = %s';
                break;
            case 'email':
                $value = is_array($value) ? rgar($value, 0) : $value;
                break;
        }

        $inner_sql_template = "SELECT %s as input, ld.lead_id
                                FROM {$lead_detail_table_name} ld
                                INNER JOIN {$lead_table_name} l ON l.id = ld.lead_id\n";


        $inner_sql_template .= "WHERE l.form_id=%d AND ld.form_id=%d
                                AND ld.field_number between %s AND %s
                                AND status='active' 
                                AND (payment_status IS NULL OR LOWER(payment_status) IN ('', 'approved', 'approve', 'completed', 'complete', 'actived', 'active', 'paid'))
							    AND {$sql_comparison}";

        $sql = "SELECT count(DISTINCT input) AS match_count FROM ( ";

        $inner_sql = '';
        $input_count = 1;
        if (is_array($field->get_entry_inputs())) {
            $input_count = sizeof($field->inputs);
            foreach ($field->inputs as $input) {
                $union = empty($inner_sql) ? '' : ' UNION ALL ';
                $inner_sql .= $union . $wpdb->prepare($inner_sql_template, $input['id'], $form_id, $form_id, $input['id'] - 0.0001, $input['id'] + 0.0001, $value[$input['id']], $value[$input['id']]);
            }
        } else {
            $inner_sql = $wpdb->prepare($inner_sql_template, $field->id, $form_id, $form_id, doubleval($field->id) - 0.0001, doubleval($field->id) + 0.0001, $value, $value);
        }

        $sql .= $inner_sql . "
                ) as count
                GROUP BY lead_id
                ORDER BY match_count DESC";

        $count = gf_apply_filters(array('gform_is_duplicate_better', $form_id), $wpdb->get_var($sql), $form_id, $field, $value);

        return $count != null && $count >= $input_count;
    }

    public function better_noDuplicate_old($count, $form_id, $field, $value)
    {

        global $wpdb;

        $lead_detail_table_name = GFFormsModel::get_lead_details_table_name();
        $lead_table_name = GFFormsModel::get_lead_table_name();
        $lead_detail_long = GFFormsModel::get_lead_details_long_table_name();
        $is_long = !is_array($value) && strlen($value) > GFORMS_MAX_FIELD_LENGTH - 10;

        $sql_comparison = $is_long ? '( ld.value = %s OR ldl.value = %s )' : 'ld.value = %s';

        switch (GFFormsModel::get_input_type($field)) {
            case 'time':
                $value = sprintf("%02d:%02d %s", $value[0], $value[1], $value[2]);
                break;
            case 'date':
                $value = GFFormsModel::prepare_date($field->dateFormat, $value);
                break;
            case 'number':
                $value = GFCommon::clean_number($value, $field->numberFormat);
                break;
            case 'phone':
                $value = str_replace(array(')', '(', '-', ' '), '', $value);
                $sql_comparison = 'replace( replace( replace( replace( ld.value, ")", "" ), "(", "" ), "-", "" ), " ", "" ) = %s';
                break;
            case 'email':
                $value = is_array($value) ? rgar($value, 0) : $value;
                break;
        }

        $inner_sql_template = "SELECT %s as input, ld.lead_id
                                FROM {$lead_detail_table_name} ld
                                INNER JOIN {$lead_table_name} l ON l.id = ld.lead_id\n";

        if ($is_long) {
            $inner_sql_template .= "INNER JOIN {$lead_detail_long} ldl ON ldl.lead_detail_id = ld.id\n";
        }

        $inner_sql_template .= "WHERE l.form_id=%d AND ld.form_id=%d
                                AND ld.field_number between %s AND %s
                                AND status='active'
								AND (payment_status IS NULL OR LOWER(payment_status) IN ('', 'approved', 'approve', 'completed', 'complete', 'actived', 'active', 'paid'))
								AND {$sql_comparison}";

        $sql = "SELECT count(DISTINCT input) AS match_count FROM ( ";

        $inner_sql = '';
        $input_count = 1;
        if (is_object($field) && is_array($field->get_entry_inputs())) {
            $input_count = sizeof($field->inputs);
            foreach ($field->inputs as $input) {
                $union = empty($inner_sql) ? '' : ' UNION ALL ';
                $inner_sql .= $union . $wpdb->prepare($inner_sql_template, $input['id'], $form_id, $form_id, $input['id'] - 0.0001, $input['id'] + 0.0001, $value[$input['id']], $value[$input['id']]);
            }
        } else {
            $inner_sql = $wpdb->prepare($inner_sql_template, $field->id, $form_id, $form_id, doubleval($field->id) - 0.0001, doubleval($field->id) + 0.0001, $value, $value);
        }

        $sql .= $inner_sql . "
                ) as count
                GROUP BY lead_id
                ORDER BY match_count DESC";

        $count = gf_apply_filters('gform_is_duplicate_better', $form_id, $wpdb->get_var($sql), $form_id, $field, $value);

        return $count != null && $count >= $input_count;
    }

    public function add_private_post_status($post_status_options)
    {
        $post_status_options['private'] = __("Private", "GF_FA");
        return $post_status_options;
    }

    public function iran_currencies($currencies)
    {
        unset($currencies['IRR'], $currencies['irr'], $currencies['IRT'], $currencies['irt']);

        $currencies['IRR'] = array(
            'name' => esc_html__('Iranian Rial', 'GF_FA'),
            'symbol_left' => (!is_rtl() ? esc_html__('Rial', 'GF_FA') : ''),
            'symbol_right' => (is_rtl() ? esc_html__('Rial', 'GF_FA') : ''),
            'symbol_padding' => ' ',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'decimals' => 0
        );

        $currencies['IRT'] = array(
            'name' => esc_html__('Iranian Toman', 'GF_FA'),
            'symbol_left' => (!is_rtl() ? esc_html__('Toman', 'GF_FA') : ''),
            'symbol_right' => (is_rtl() ? esc_html__('Toman', 'GF_FA') : ''),
            'symbol_padding' => ' ',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'decimals' => 0
        );

        return $currencies;
    }

    public function predefined_choices($choices)
    {

        $month[__('Iranian Months', 'GF_FA')] = array(
            __('Farvardin', 'GF_FA'),
            __('Ordibehesht', 'GF_FA'),
            __('Khordad', 'GF_FA'),
            __('Tir', 'GF_FA'),
            __('Mordad', 'GF_FA'),
            __('Shahrivar', 'GF_FA'),
            __('Mehr', 'GF_FA'),
            __('Aban', 'GF_FA'),
            __('Azar', 'GF_FA'),
            __('Dey', 'GF_FA'),
            __('Bahman', 'GF_FA'),
            __('Esfand', 'GF_FA')
        );

        return $choices = array_merge($month, $choices);
    }

    public function merge_tags_admin($form)
    {
        $merge_tags = array(
            '{payment_gateway}' => __('Simple Payment Gateway', 'GF_FA'),
            '{payment_status}' => __('Simple Payment Status', 'GF_FA'),
            '{transaction_id}' => __('Simple Transaction ID', 'GF_FA'),
            '{payment_gateway_css}' => __('Styled Payment Gateway', 'GF_FA'),
            '{payment_status_css}' => __('Styled Payment Status', 'GF_FA'),
            '{transaction_id_css}' => __('Styled Transaction ID', 'GF_FA'),
            '{payment_pack}' => __('Styled Payment Pack', 'GF_FA'),
            '{rtl_start}' => __('Start of RTL Div', 'GF_FA'),
            '{rtl_end}' => __('End of RTL Div', 'GF_FA'),
        );
        ?>
        <script type="text/javascript">
            gform.addFilter("gform_merge_tags", "add_merge_tags");
            function add_merge_tags(mergeTags, elementId, hideAllFields, excludeFieldTypes, isPrepop, option) {
                <?php foreach ( (array)$merge_tags as $key => $val ) { ?>
                mergeTags["custom"].tags.push({tag: '<?php echo $key ?>', label: '<?php echo $val ?>'});
                <?php } ?>
                return mergeTags;
            }
        </script>
        <?php
        return $form;
    }


    public function replace_merge_tags($text, $form, $lead, $url_encode, $esc_html, $nl2br, $format)
    {

        $color = '';
        $lead = RGFormsModel::get_lead(rgar($lead, 'id'));

        $table_color = esc_attr(apply_filters('gform_email_background_color_table', '#EAEAEA', '', $lead));
        $label_color = esc_attr(apply_filters('gform_email_background_color_label', '#EAF2FA', '', $lead));
        //$data_color = esc_attr(apply_filters('gform_email_background_color_data', '#FFFFFF', '', $lead));

        $gateway = gform_get_meta(rgar($lead, 'id'), 'payment_gateway');
        $gateway = !empty($gateway) ? $gateway : '';

        $transaction_id = rgar($lead, 'transaction_id');
        $transaction_id = !empty($transaction_id) ? $transaction_id : '';

        $payment_status = strtolower(rgar($lead, 'payment_status'));
        if (!empty($payment_status)) {

            if (in_array($payment_status, array('completed', 'complete', 'paid', 'active', 'actived', 'approved', 'approve'))) {
                $color = 'color:#008000;';
                $payment_status = __('Approved', 'GF_FA');

            } else if ($payment_status == 'failed') {
                $color = 'color:#FF0000;';
                $payment_status = __('Failed', 'GF_FA');

            } else if ($payment_status == 'cancelled') {
                $color = 'color:#FFA500;';
                $payment_status = __('Cancelled', 'GF_FA');

            } else {
                $color = 'color:#3399FF;';
                $payment_status = __('Processing', 'GF_FA');

            }
        }

        $payment_gateway_css = !empty($gateway) ? '
				<tr bgcolor="' . $label_color . '">
					<td colspan="2" style="padding:5px !important">
						<font style="font-family: sans-serif; font-size:12px;"><strong>' . __('Payment Gateway', 'GF_FA') . '</strong></font>
					</td>
				</tr>
				<tr bgcolor="#FFFFFF">
					<td width="20">&nbsp;</td>
					<td style="padding:5px !important">
						<font style="font-family:sans-serif;font-size:12px">' . $gateway . ' </font>
					</td>
				</tr>' : '';

        $transaction_id_css = !empty($transaction_id) ? '
				<tr bgcolor="' . $label_color . '">
					<td colspan="2" style="padding:5px !important">
						<font style="font-family: sans-serif; font-size:12px;"><strong>' . __('Transaction ID', 'GF_FA') . '</strong></font>
					</td>
				</tr>
				<tr bgcolor="#FFFFFF">
					<td width="20">&nbsp;</td>
					<td style="padding:5px !important">
						<font style="font-family:sans-serif;font-size:12px">' . $transaction_id . ' </font>
					</td>
				</tr>' : '';

        $payment_status_css = !empty($payment_status) ? '
				<tr bgcolor="' . $label_color . '">
					<td colspan="2" style="padding:5px !important">
						<font style="font-family: sans-serif; font-size:12px;"><strong>' . __('Payment Status', 'GF_FA') . '</strong></font>
					</td>
				</tr>
				<tr bgcolor="#FFFFFF">
					<td width="20">&nbsp;</td>
					<td style="padding:5px !important">
						<font style="font-family:sans-serif;font-size:12px;' . $color . '">' . $payment_status . ' </font>
					</td>
				</tr>' : '';


        $tags = array(
            '{payment_gateway}',
            '{transaction_id}',
            '{payment_status}',
            '{payment_gateway_css}',
            '{transaction_id_css}',
            '{payment_status_css}',
            '{payment_pack}',
            '{rtl_start}',
            '{rtl_end}',
        );


        $values = array(

            $gateway,
            $transaction_id,
            $payment_status,

            $gateway ? '<table width="99%" border="0" cellpadding="1" cellspacing="0" bgcolor="' . $table_color . '" style="border:1px solid #e9e9e9!important;">'
                . $payment_gateway_css .
                '</table>' : '',

            $transaction_id ? '<table width="99%" border="0" cellpadding="1" cellspacing="0" bgcolor="' . $table_color . '" style="border:1px solid #e9e9e9!important;">'
                . $transaction_id_css .
                '</table>' : '',

            $payment_status ? '<table width="99%" border="0" cellpadding="1" cellspacing="0" bgcolor="' . $table_color . '" style="border:1px solid #e9e9e9!important;">'
                . $payment_status_css .
                '</table>' : '',

            $payment_status || $transaction_id || $gateway ? '<table width="99%" border="0" cellpadding="1" cellspacing="0" bgcolor="' . $table_color . '" style="border:1px solid #e9e9e9!important;">'
                . $payment_status_css . $payment_gateway_css . $transaction_id_css .
                '</table>' : '',

            '<div style="text-align: right !important; direction: rtl !important;">',
            '</div>',
        );

        $text = str_replace($tags, $values, $text);

        return $text;
    }

    public function show_payment_status($form_id, $field_id, $value, $lead, $query_string)
    {
        $url = remove_query_arg(array('s', 'field_id', 'operator'));
        $gateway = gform_get_meta(rgar($lead, 'id'), 'payment_gateway');
        $payment_status = strtolower(rgar($lead, 'payment_status'));

        if (in_array($payment_status, array('completed', 'complete', 'paid', 'active', 'actived', 'approved', 'approve'))) {
            $color = '#008000';
            $status = __('Approved', 'GF_FA');
        } else if ($payment_status == 'failed') {
            $color = '#FF0000';
            $status = __('Failed', 'GF_FA');
        } else if ($payment_status == 'cancelled') {
            $color = '#FFA500';
            $status = __('Cancelled', 'GF_FA');
        } else {
            $color = '#3399FF';
            $status = __('Processing', 'GF_FA');
        }

        if (!empty($payment_status)) {
            $url = add_query_arg(array('s' => UCfirst($payment_status), 'field_id' => 'payment_status', 'operator' => 'is'), $url);
            echo '<a  class="status" href="' . $url . '" style="color:' . $color . ';"> ' . $status . ' </a>';
            if (!empty($gateway))
                echo ' - ';
        }

        if (!empty($gateway)) {
            $url = add_query_arg(array('s' => $gateway, 'field_id' => 'payment_gateway', 'operator' => 'is'), $url);
            echo '<a class="status" href="' . $url . '" style="color:#000000;"> ' . $gateway . ' </a>';
        }
    }

    public function transaction_id($entry, $form)
    {
        $transaction_id = apply_filters($this->author . '_gf_rand_transaction_id', rand(1000000000, 9999999999), $form, $entry);
        GFAPI::update_entry_property($entry['id'], "transaction_id", $transaction_id);
    }

    //تغییر کد رهگیری از این فیلتر
    public function transaction_id_mask($transaction_id, $entry, $form)
    {
        return $this->rand_mask(apply_filters('gform_transaction_id', '9999999999', $entry, $form));
    }

    public function rand_mask($mask)
    {
        if (empty($mask))
            return rand(1000000000, 9999999999);

        $transaction_id = '';
        foreach (str_split($mask) as $str) {
            if (in_array($str, array('*', 'a')))
                $transaction_id .= str_replace($str, $this->rand_str($str), $str);
            else if (in_array($str, array('9', 9)))
                $transaction_id .= str_replace($str, rand(0, 9), $str);
            else
                $transaction_id .= $str;
        }
        return $transaction_id;
    }

    public function rand_str($type = 'a')
    {
        $rand = $type == '*' ? array_map('strval', range(0, 9)) : array();
        $rand = array_merge($rand, range('A', 'Z'), range('a', 'z'));
        shuffle($rand);
        return $rand[rand(0, count($rand))];
    }

    public function frontend_rtl()
    {

        $gform_old = version_compare(GFCommon::$version, '2.0.0', '<');
        $frontend_rtl = is_rtl() && !is_admin();
        $frontend_rtl = apply_filters('gf_persian_frontend_rtl', $frontend_rtl);

        if ($frontend_rtl) { ?>

            <style type="text/css">

                <?php if($gform_old) : ?>

                .gright, .gform_wrapper form, .gform_wrapper ul li:before, .gform_wrapper ul li:after, .gform_wrapper ul.gform_fields {
                    text-align: right !important;
                    direction: rtl !important;
                }

                .gleft, .gform_wrapper input[type="url"], .gform_wrapper input[type="email"],
                .gform_wrapper input[type="tel"], .gform_wrapper input[type="number"],
                .gform_wrapper input[type="password"], body.rtl .gform_wrapper input[type="email"], body.rtl .gform_wrapper input[type="password"],
                body.rtl .gform_wrapper input[type="url"], body.rtl .gform_wrapper input[type="tel"], body .gform_wrapper.gf_rtl_wrapper input[type="email"],
                body .gform_wrapper.gf_rtl_wrapper input[type="password"], body .gform_wrapper.gf_rtl_wrapper input[type="url"], body .gform_wrapper.gf_rtl_wrapper input[type="tel"] {
                    text-align: left !important;
                    direction: ltr !important;
                }

                .gform_wrapper .ginput_complex .ginput_left {
                    float: right !important;
                }

                .gform_wrapper .ginput_complex .ginput_right {
                    float: left !important;
                }

                .gform_wrapper .ginput_complex .address_city {
                    float: left !important;
                }

                .gform_wrapper .ginput_complex .address_state, .gform_wrapper .ginput_complex .address_zip {
                    float: right !important;
                }

                .gform_wrapper .gfield_checkbox li label,
                .gform_wrapper .gfield_radio li label {
                    margin-right: 20px !important;
                }

                .gform_wrapper .ginput_complex.ginput_container.has_first_name.has_middle_name.has_last_name span.name_first,
                .gform_wrapper .ginput_complex.ginput_container.has_first_name.has_middle_name.has_last_name span.name_middle,
                .gform_wrapper .ginput_complex.ginput_container.has_first_name.no_middle_name.has_last_name span.name_first {
                    margin-right: 0px !important;
                    margin-left: 1.3% !important;
                }

                <?php endif; ?>
            </style>
        <?php }
    }

    public function admin_rtl()
    {
        if (is_rtl() && $this->is_gravity_page()) {
            wp_register_style('gravity-forms-admin-rtl', GF_PARSI_URL . 'assets/css/admin.rtl.css');
            wp_enqueue_style('gravity-forms-admin-rtl');
        }
    }

    public function print_rtl($styles)
    {
        if (is_rtl()) {
            wp_register_style('print_entry', GF_PARSI_URL . 'assets/css/print.rtl.css');
            return array('print_entry');
        }
        return $styles;
    }
	
	public static function get_base_url(){
		return plugins_url( '', __FILE__ );
	}
	
    public static function get_mysql_tz_offset()
    {

        $tzb = $tz = get_option('gmt_offset');

        if (intval($tz) < 0)
            $pf = "-";
        else
            $pf = "+";

        $tz = abs($tz) * 3600;
        $tz = gmdate("H:i", $tz);
        $tz = $pf . $tz;

        $today = date('Y-m-d H:i:s');
        $date = new DateTime($today);

        $tzn = abs($tzb) * 3600;
        $tzh = intval(gmdate("H", $tzn));
        $tzm = intval(gmdate("i", $tzn));

        if (intval($tzb) < 0)
            $date->sub(new DateInterval('P0DT' . $tzh . 'H' . $tzm . 'M'));
        else
            $date->add(new DateInterval('P0DT' . $tzh . 'H' . $tzm . 'M'));

        $today = $date->format('Y-m-d H:i:s');
        $today = strtotime($today);
        return array("tz" => $tz, "today" => $today);
    }

}

global $gf_parsi;
$gf_parsi = new GFParsi();