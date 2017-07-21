<?php if ( ! defined( 'ABSPATH' ) ) exit;

class GFParsi_PostPermalink {
    
    function __construct() {
        add_filter('gform_custom_merge_tags', array($this, 'add_custom_merge_tag'), 10, 4);
        add_filter('gform_replace_merge_tags', array($this, 'replace_merge_tag'), 10, 3); 
    }
    
    function add_custom_merge_tag($merge_tags, $form_id, $fields, $element_id) {
        if(!GFCommon::has_post_field($fields))
            return $merge_tags;
        $merge_tags[] = array('label' => __('Post Permalink', 'GF_FA') , 'tag' => '{post_permalink}');
        return $merge_tags;
    }
    
    function replace_merge_tag($text, $form, $entry) {
        
        $custom_merge_tag = '{post_permalink}';
        if(strpos($text, $custom_merge_tag) === false || !rgar($entry, 'post_id'))
            return $text;
        
        $post_permalink = get_permalink(rgar($entry, 'post_id'));
        $text = str_replace($custom_merge_tag, $post_permalink, $text);
        
        return $text;
    }
    
}
new GFParsi_PostPermalink();


add_filter('gform_pre_render', 'gfir_prepopluate_merge_tags');
function gfir_prepopluate_merge_tags($form) {
    
    $filter_names = array();
    
    foreach($form['fields'] as &$field) {
        
        if(!rgar($field, 'allowsPrepopulate'))
            continue;
        
        // complex fields store inputName in the "name" property of the inputs array
        if(is_array(rgar($field, 'inputs')) && $field['type'] != 'checkbox') {
            foreach($field['inputs'] as $input) {
                if(rgar($input, 'name'))
                    $filter_names[] = array('type' => $field['type'], 'name' => rgar($input, 'name'));
            }
        } else {
            $filter_names[] = array('type' => $field['type'], 'name' => rgar($field, 'inputName'));
        }
        
    }
    
    foreach($filter_names as $filter_name) {
        
        $filtered_name = GFCommon::replace_variables_prepopulate($filter_name['name']);
        
        if($filter_name['name'] == $filtered_name)
            continue;
        
        add_filter("gform_field_value_{$filter_name['name']}", create_function("", "return '$filtered_name';"));
    }
    
    return $form;
}


add_filter( 'gform_tabindex', 'gfir_gform_tabindexer', 10, 2 );
function gfir_gform_tabindexer( $tab_index, $form = false ) {
    $starting_index = 1000; // if you need a higher tabindex, update this number
    if( $form )
        add_filter( 'gform_tabindex_' . $form['id'], 'gfir_gform_tabindexer' );
    return GFCommon::$tab_index >= $starting_index ? GFCommon::$tab_index : $starting_index;
}


add_filter( 'gform_confirmation_anchor', '__return_false' );