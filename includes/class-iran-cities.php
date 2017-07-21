<?php if (!defined('ABSPATH')) exit;

class GFParsi_IranCities
{

    public $province = array();

    public function __construct()
    {

        add_action('gform_editor_js', array($this, 'iran_cities'));
        add_action('gform_field_standard_settings', array($this, 'iran_cities_option'), 10, 2);
        add_filter('gform_address_types', array($this, 'iran_address'));
        add_filter('gform_predefined_choices', array($this, 'predefined_choices'), 1);

        add_filter('gform_field_content', array($this, 'city_select'), 10, 5);
        add_action('gform_register_init_scripts', array($this, 'init_script'), 10, 1);
        add_action('gform_enqueue_scripts', array($this, 'external_js'), 10, 2);

    }


    public function get_province()
    {

        $this->province = array(
            __('Azarbaijan - East', 'GF_FA'),
            __('Azarbaijan - West', 'GF_FA'),
            __('Ardabil', 'GF_FA'),
            __('Isfahan', 'GF_FA'),
            __('Alborz', 'GF_FA'),
            __('Ilam', 'GF_FA'),
            __('Bushehr', 'GF_FA'),
            __('Tehran', 'GF_FA'),
            __('Chahar Mahaal and Bakhtiari', 'GF_FA'),
            __('Khorasan - South', 'GF_FA'),
            __('Khorasan - Razavi', 'GF_FA'),
            __('Khorasan - North', 'GF_FA'),
            __('Khuzestan', 'GF_FA'),
            __('Zanjan', 'GF_FA'),
            __('Semnan', 'GF_FA'),
            __('Sistan and Baluchistan', 'GF_FA'),
            __('Fars', 'GF_FA'),
            __('Qazvin', 'GF_FA'),
            __('Qom', 'GF_FA'),
            __('Kurdistan', 'GF_FA'),
            __('Kerman', 'GF_FA'),
            __('Kermanshah', 'GF_FA'),
            __('Kohgiluyeh and Boyer-Ahmad', 'GF_FA'),
            __('Golestan', 'GF_FA'),
            __('Guilan', 'GF_FA'),
            __('Lorestan', 'GF_FA'),
            __('Mazandaran', 'GF_FA'),
            __('Markazi', 'GF_FA'),
            __('Hormozgān', 'GF_FA'),
            __('Hamadan', 'GF_FA'),
            __('Yazd', 'GF_FA')
        );
    }

    public function iran_address($address_types)
    {
        $this->get_province();
        $address_types['iran'] = array(
            'label' => __('Iran', 'GF_FA'),
            'country' => __('Iran', 'GF_FA'),
            'zip_label' => __('Postal Code', 'GF_FA'),
            'state_label' => __('Province', 'GF_FA'),
            'states' => array_merge(array(''), $this->province)
        );
        return $address_types;
    }

    public function predefined_choices($choices)
    {
        $this->get_province();
        $states[__('Provinces of Iran', 'GF_FA')] = $this->province;
        return $choices = array_merge($states, $choices);
    }

    public function iran_cities()
    { ?>

        <script type='text/javascript'>
            jQuery(document).ready(function ($) {
                fieldSettings["address"] += ", .iran_cities";
                $(document).bind("gform_load_field_settings", function (event, field, form) {
                    $("#iran_cities").attr("checked", field["iran_cities"] == true);
                    var $address_type = $('#field_address_type');
                    if (!$('#iran_cities_div').length) {
                        var $iran_cities = $(".iran_cities");
                        var $iran_cities_input = $iran_cities.html();
                        $iran_cities.remove();
                        $address_type.after('<div id="iran_cities_div"><br>' + $iran_cities_input + '</div>');
                    }
                    var $iran_cities_div = $('#iran_cities_div');
                    $iran_cities_div.hide();
                    if ($address_type.val() === 'iran')
                        $iran_cities_div.show();
                    $address_type.change(function () {
                        if ($(this).val() === 'iran')
                            $("#iran_cities_div").slideDown();
                        else
                            $("#iran_cities_div").slideUp();
                    });
                });
            });
        </script>

        <?php
    }

    public function iran_cities_option($position, $form_id)
    {
        if ($position == 25) { ?>
            <li class="iran_cities field_setting">
                <input type="checkbox" id="iran_cities"
                       onclick="SetFieldProperty('iran_cities', jQuery(this).is(':checked') ? 1 : 0);"/>
                <label class="inline gfield_value_label" for="iran_cities" class="inline">
                    <?php _e('Activate Iran Cities', 'GF_FA'); ?>
                </label>
            </li>
            <?php
        }
    }

    public function city_select($content, $field, $value, $lead_id, $form_id)
    {

        if ($this->is_iran_cities($field)) {

            $id = absint($field['id']);

            preg_match('/<input(?:.*?)(name=["\']input_' . $id . '.3["\'].*?)(?:\/?)>/i', $content, $match);

            if (!empty($match[0]) && !empty($match[1])) {
                $city_input = preg_replace('/value=["\'](?:.*?)["\']/', "", trim($match[1]));
                $content = str_replace($match[0], "<select {$city_input}><option value='' selected='selected'>&nbsp;&nbsp;</option></select>", $content);
            }
        }

        return $content;
    }

    public function external_js($form, $ajax)
    {

        $fields = GFCommon::get_fields_by_type($form, array('address'));

        foreach ((array)$fields as $field) {

            if ($this->is_iran_cities($field)) {

                wp_dequeue_script('gform_iran_citeis');
                wp_deregister_script('gform_iran_citeis');

                wp_register_script('gform_iran_citeis', GF_PARSI_URL . 'assets/js/iran-cities-sorted.min.js', array(), GF_PARSI_VERSION, false);
                wp_enqueue_script('gform_iran_citeis');


                add_action('wp_footer', array($this, 'frontend_rtl'));

                break;
            }
        }
    }


    public function frontend_rtl()
    {

        $frontend_rtl = apply_filters('gf_persian_frontend_rtl', is_rtl() && !is_admin());
        if (!$frontend_rtl)
            return;
        ?>
        <style type="text/css">
            html[dir="rtl"] .gform_wrapper .ginput_complex.ginput_container_address .address_city.ginput_left {
                float: left !important;
                padding-right: 16px !important;
                padding-left: 0px !important;
            }

            html[dir="rtl"] .gform_wrapper .ginput_complex.ginput_container_address .ginput_left:nth-of-type(2n),
            html[dir="rtl"] .gform_wrapper .ginput_complex.ginput_container_address .ginput_left:nth-of-type(2n+1)
            {
                padding-left: 0px !important;
            }

            html[dir="rtl"] .gform_wrapper .ginput_complex.ginput_container_address .address_state.ginput_right {
                float: right !important;
                padding-right: 0px !important;
            }

        </style>
        <?php
    }

    public function init_script($form)
    {

        foreach ($form['fields'] as &$field) {

            if ($this->is_iran_cities($field)) {

                $field_id = $field['id'];
                $form_id = $form['id'];
                $id = $form_id . '_' . $field_id;

                $script = 'jQuery().ready(function($){' .
                    '$(".has_city #input_' . $id . '_3").empty();' .
                    '$(".has_city #input_' . $id . '_3").append(gform_iranCities(""+$(".has_city #input_' . $id . '_4").val()));' .
                    '$(document.body).on("change", ".has_city #input_' . $id . '_4" ,function(){' .
                    '$(".has_city #input_' . $id . '_3").empty();' .
                    '$(".has_city #input_' . $id . '_3").append(gform_iranCities(""+$(".has_city #input_' . $id . '_4").val()));' .
                    '});' .
                    '})';
                GFFormDisplay::add_init_script($form['id'], 'iran_address_city_' . $id, GFFormDisplay::ON_PAGE_RENDER, $script);
            }
        }
    }

    private function is_iran_cities($field)
    {
        return $field['type'] == 'address' && $field['addressType'] == 'iran' && gfa_get("iran_cities", $field) && !is_admin();
    }

}

new GFParsi_IranCities();