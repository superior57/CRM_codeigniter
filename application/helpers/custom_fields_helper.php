<?php

defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Render custom fields inputs
 * @param  string  $belongs_to             where this custom field belongs eq invoice, customers
 * @param  mixed   $rel_id                 relation id to set values
 * @param  array   $where                  where in sql - additional
 * @param  array $items_cf_params          used only for custom fields for items operations
 * @return mixed
 */
function render_custom_fields($belongs_to, $rel_id = false, $where = [], $items_cf_params = [])
{
    // Is custom fields for items and in add/edit
    $items_add_edit_preview = isset($items_cf_params['add_edit_preview']) && $items_cf_params['add_edit_preview'] ? true : false;

    // Is custom fields for items and in add/edit area for this already added
    $items_applied = isset($items_cf_params['items_applied']) && $items_cf_params['items_applied'] ? true : false;

    // Used for items custom fields to add additional name on input
    $part_item_name = isset($items_cf_params['part_item_name']) ? $items_cf_params['part_item_name'] : '';

    // Is this custom fields for predefined items Sales->Items
    $items_pr = isset($items_cf_params['items_pr']) && $items_cf_params['items_pr'] ? true : false;

    $is_admin = is_admin();

    $CI = & get_instance();
    $CI->db->where('active', 1);
    $CI->db->where('fieldto', $belongs_to);

    if (is_array($where) && count($where) > 0 || is_string($where) && $where != '') {
        $CI->db->where($where);
    }

    $CI->db->order_by('field_order', 'asc');
    $fields = $CI->db->get(db_prefix() . 'customfields')->result_array();

    $fields_html = '';

    if (count($fields)) {
        if (!$items_add_edit_preview && !$items_applied) {
            $fields_html .= '<div class="row custom-fields-form-row">';
        }

        foreach ($fields as $field) {
            if ($field['only_admin'] == 1 && !$is_admin) {
                continue;
            }

            $field['name'] = _maybe_translate_custom_field_name($field['name'], $field['slug']);

            $value = '';
            if ($field['bs_column'] == '' || $field['bs_column'] == 0) {
                $field['bs_column'] = 12;
            }

            if (!$items_add_edit_preview && !$items_applied) {
                $fields_html .= '<div class="col-md-' . $field['bs_column'] . '">';
            } elseif ($items_add_edit_preview) {
                $fields_html .= '<td class="custom_field" data-id="' . $field['id'] . '">';
            } elseif ($items_applied) {
                $fields_html .= '<td class="custom_field">';
            }

            if ($is_admin
                && ($items_add_edit_preview == false && $items_applied == false)
                && (!defined('CLIENTS_AREA') || hooks()->apply_filters('show_custom_fields_edit_link_on_clients_area', false))) {
                $fields_html .= '<a href="' . admin_url('custom_fields/field/' . $field['id']) . '" tabindex="-1" target="_blank" class="custom-field-inline-edit-link"><i class="fa fa-pencil-square-o"></i></a>';
            }

            if ($rel_id !== false) {
                if (!is_array($rel_id)) {
                    $value = get_custom_field_value($rel_id, $field['id'], ($items_pr ? 'items_pr' : $belongs_to), false);
                } else {
                    if (is_custom_fields_smart_transfer_enabled()) {
                        // Used only in:
                        // 1. Convert proposal to estimate, invoice
                        // 2. Convert estimate to invoice
                        // This feature is executed only on CREATE, NOT EDIT
                        $transfer_belongs_to = $rel_id['belongs_to'];
                        $transfer_rel_id     = $rel_id['rel_id'];
                        $tmpSlug             = explode('_', $field['slug'], 2);
                        if (isset($tmpSlug[1])) {
                            $CI->db->where('fieldto', $transfer_belongs_to);
                            $CI->db->where('slug LIKE "' . $rel_id['belongs_to'] . '_' . $tmpSlug[1] . '%" AND type="' . $field['type'] . '" AND options="' . $field['options'] . '" AND active=1');
                            $cfTransfer = $CI->db->get(db_prefix() . 'customfields')->result_array();

                            // Don't make mistakes
                            // Only valid if 1 result returned
                            // + if field names similarity is equal or more then CUSTOM_FIELD_TRANSFER_SIMILARITY%
                            //
                            if (count($cfTransfer) == 1 && ((similarity($field['name'], $cfTransfer[0]['name']) * 100) >= CUSTOM_FIELD_TRANSFER_SIMILARITY)) {
                                $value = get_custom_field_value($transfer_rel_id, $cfTransfer[0]['id'], $transfer_belongs_to, false);
                            }
                        }
                    }
                }
            }

            $_input_attrs = [];

            if ($field['required'] == 1) {
                $_input_attrs['data-custom-field-required'] = true;
            }

            if ($field['disalow_client_to_edit'] == 1 && is_client_logged_in()) {
                $_input_attrs['disabled'] = true;
            }

            $_input_attrs['data-fieldto'] = $field['fieldto'];
            $_input_attrs['data-fieldid'] = $field['id'];

            $cf_name = 'custom_fields[' . $field['fieldto'] . '][' . $field['id'] . ']';

            if ($part_item_name != '') {
                $cf_name = $part_item_name . '[custom_fields][items][' . $field['id'] . ']';
            }

            if ($items_add_edit_preview) {
                $cf_name = '';
            }

            $field_name = $field['name'];

            if ($field['type'] == 'input' || $field['type'] == 'number') {
                $t = $field['type'] == 'input' ? 'text' : 'number';
                $fields_html .= render_input($cf_name, $field_name, $value, $t, $_input_attrs);
            } elseif ($field['type'] == 'date_picker') {
                $fields_html .= render_date_input($cf_name, $field_name, _d($value), $_input_attrs);
            } elseif ($field['type'] == 'date_picker_time') {
                $fields_html .= render_datetime_input($cf_name, $field_name, _dt($value), $_input_attrs);
            } elseif ($field['type'] == 'textarea') {
                $fields_html .= render_textarea($cf_name, $field_name, $value, $_input_attrs);
            } elseif ($field['type'] == 'colorpicker') {
                $fields_html .= render_color_picker($cf_name, $field_name, $value, $_input_attrs);
            } elseif ($field['type'] == 'select' || $field['type'] == 'multiselect') {
                $_select_attrs = [];
                $select_attrs  = '';
                $select_name   = $cf_name;

                if ($field['required'] == 1) {
                    $_select_attrs['data-custom-field-required'] = true;
                }

                if ($field['disalow_client_to_edit'] == 1 && is_client_logged_in()) {
                    $_select_attrs['disabled'] = true;
                }

                $_select_attrs['data-fieldto'] = $field['fieldto'];
                $_select_attrs['data-fieldid'] = $field['id'];

                if ($field['type'] == 'multiselect') {
                    $_select_attrs['multiple'] = true;
                    $select_name .= '[]';
                }

                foreach ($_select_attrs as $key => $val) {
                    $select_attrs .= $key . '=' . '"' . $val . '" ';
                }

                $fields_html .= '<div class="form-group">';
                $fields_html .= '<label for="' . $cf_name . '" class="control-label" style="margin-bottom:9px;">' . $field_name . '</label>';
                $fields_html .= '<select ' . $select_attrs . ' name="' . $select_name . '" class="' . ($items_add_edit_preview == false ? 'select-placeholder ': '') . 'selectpicker form-control' . ($field['type'] == 'multiselect' ? ' custom-field-multi-select' : '') . '" data-width="100%" data-none-selected-text="' . _l('dropdown_non_selected_tex') . '"  data-live-search="true">';

                $fields_html .= '<option value=""' . ($field['type'] == 'multiselect' ? ' class="hidden"' : '') . '></option>';

                $options = explode(',', $field['options']);

                if ($field['type'] == 'multiselect') {
                    $value = explode(',', $value);
                }

                foreach ($options as $option) {
                    $option = trim($option);
                    if ($option != '') {
                        $selected = '';
                        if ($field['type'] == 'select') {
                            if ($option == $value) {
                                $selected = ' selected';
                            }
                        } else {
                            foreach ($value as $v) {
                                $v = trim($v);
                                if ($v == $option) {
                                    $selected = ' selected';
                                }
                            }
                        }

                        $fields_html .= '<option value="' . $option . '"' . $selected . '' . set_select($cf_name, $option) . '>' . $option . '</option>';
                    }
                }
                $fields_html .= '</select>';
                $fields_html .= '</div>';
            } elseif ($field['type'] == 'checkbox') {
                $fields_html .= '<div class="form-group chk">';

                $fields_html .= '<br /><label class="control-label' . ($field['display_inline'] == 0 ? ' no-mbot': '') . '" for="' . $cf_name . '[]">' . $field_name . '</label>' . ($field['display_inline'] == 1 ? ' <br />': '');

                $options = explode(',', $field['options']);

                $value = explode(',', $value);

                foreach ($options as $option) {
                    $checked = '';
                    // Replace double quotes with single.
                    $option = htmlentities($option);
                    $option = trim($option);
                    foreach ($value as $v) {
                        $v = trim($v);
                        if ($v == $option) {
                            $checked = 'checked';
                        }
                    }

                    $_chk_attrs                 = [];
                    $chk_attrs                  = '';
                    $_chk_attrs['data-fieldto'] = $field['fieldto'];
                    $_chk_attrs['data-fieldid'] = $field['id'];

                    if ($field['required'] == 1) {
                        $_chk_attrs['data-custom-field-required'] = true;
                    }

                    if ($field['disalow_client_to_edit'] == 1 && is_client_logged_in()) {
                        $_chk_attrs['disabled'] = true;
                    }
                    foreach ($_chk_attrs as $key => $val) {
                        $chk_attrs .= $key . '=' . '"' . $val . '" ';
                    }

                    $input_id = 'cfc_' . $field['id'] . '_' . slug_it($option) . '_' . app_generate_hash();

                    $fields_html .= '<div class="checkbox' . ($field['display_inline'] == 1 ? ' checkbox-inline': '') . '">';
                    $fields_html .= '<input class="custom_field_checkbox" ' . $chk_attrs . ' ' . set_checkbox($cf_name . '[]', $option) . ' ' . $checked . ' value="' . $option . '" id="' . $input_id . '" type="checkbox" name="' . $cf_name . '[]">';

                    $fields_html .= '<label for="' . $input_id . '" class="cf-chk-label">' . $option . '</label>';
                    $fields_html .= '<input type="hidden" name="' . $cf_name . '[]" value="cfk_hidden">';
                    $fields_html .= '</div>';
                }
                $fields_html .= '</div>';
            } elseif ($field['type'] == 'link') {
                $fields_html .= '<div class="form-group cf-hyperlink" data-fieldto="' . $field['fieldto'] . '" data-field-id="' . $field['id'] . '" data-value="' . html_escape($value) . '" data-field-name="' . html_escape($field_name) . '">';
                $fields_html .= '<label class="control-label" for="custom_fields[' . $field['fieldto'] . '][' . $field['id'] . ']">' . $field_name . '</label></br>';

                $fields_html .= '<a id="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_popover" type="button" href="javascript:">' . _l('cf_translate_input_link_tip') . '</a>';

                $fields_html .= '<input type="hidden" ' . ($field['required'] == 1 ? 'data-custom-field-required="1"' : '') . ' value="" id="custom_fields[' . $field['fieldto'] . '][' . $field['id'] . ']" name="custom_fields[' . $field['fieldto'] . '][' . $field['id'] . ']">';

                $field_template = '';
                $field_template .= '<div id="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_popover-content" class="hide cfh-field-popover-template"><div class="form-group">';
                $field_template .= '<div class="row"><div class="col-md-12"><label class="control-label" for="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_title">' . _l('cf_translate_input_link_title') . '</label>';
                $field_template .= '<input type="text"' . ($field['disalow_client_to_edit'] == 1 && is_client_logged_in() ? ' disabled="true" ' : ' ') . 'id="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_title" value="" class="form-control">';
                $field_template .= '</div>';
                $field_template .= '</div>';
                $field_template .= '</div>';
                $field_template .= '<div class="form-group">';
                $field_template .= '<div class="row">';
                $field_template .= '<div class="col-md-12">';
                $field_template .= '<label class="control-label" for="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_link">' . _l('cf_translate_input_link_url') . '</label>';
                $field_template .= '<div class="input-group"><input type="text"' . ($field['disalow_client_to_edit'] == 1 && is_client_logged_in() ? ' disabled="true" ' : ' ') . 'id="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_link" value="" class="form-control"><span class="input-group-addon"><a href="#" id="cf_hyperlink_open_' . $field['id'] . '" target="_blank"><i class="fa fa-globe"></i></a></span></div>';
                $field_template .= '</div>';
                $field_template .= '</div>';
                $field_template .= '</div>';
                $field_template .= '<div class="row">';
                $field_template .= '<div class="col-md-6">';
                $field_template .= '<button type="button" id="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_btn-cancel" class="btn btn-default btn-md pull-left" value="">' . _l('cancel') . '</button>';
                $field_template .= '</div>';
                $field_template .= '<div class="col-md-6">';
                $field_template .= '<button type="button" id="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_btn-save" class="btn btn-info btn-md pull-right" value="">' . _l('apply') . '</button>';
                $field_template .= '</div>';
                $field_template .= '</div>';
                $fields_html .= '<script>';
                $fields_html .= 'cfh_popover_templates[\'' . $field['id'] . '\'] = \'' . $field_template . '\';';
                $fields_html .= '</script>';
                $fields_html .= '</div>';
            }

            $name = $cf_name;

            if ($field['type'] == 'checkbox' || $field['type'] == 'multiselect') {
                $name .= '[]';
            }

            $fields_html .= form_error($name);
            if (!$items_add_edit_preview && !$items_applied) {
                $fields_html .= '</div>';
            } elseif ($items_add_edit_preview) {
                $fields_html .= '</td>';
            } elseif ($items_applied) {
                $fields_html .= '</td>';
            }
        }

        // close row
        if (!$items_add_edit_preview && !$items_applied) {
            $fields_html .= '</div>';
        }
    }

    return $fields_html;
}

/**
 * Get custom fields
 * @param  string  $field_to
 * @param  array   $where
 * @param  boolean $exclude_only_admin
 * @return array
 */
function get_custom_fields($field_to, $where = [], $exclude_only_admin = false)
{
    $is_admin = is_admin();
    $CI       = & get_instance();
    $CI->db->where('fieldto', $field_to);
    if ((is_array($where) && count($where) > 0) || (!is_array($where) && $where != '')) {
        $CI->db->where($where);
    }
    if (!$is_admin || $exclude_only_admin == true) {
        $CI->db->where('only_admin', 0);
    }
    $CI->db->where('active', 1);
    $CI->db->order_by('field_order', 'asc');

    $results = $CI->db->get(db_prefix() . 'customfields')->result_array();

    foreach ($results as $key => $result) {
        $results[$key]['name'] = _maybe_translate_custom_field_name($result['name'], $result['slug']);
    }

    return $results;
}

function _maybe_translate_custom_field_name($name, $slug)
{
    return _l('cf_translate_' . $slug, '', false) != 'cf_translate_' . $slug ? _l('cf_translate_' . $slug, '', false) : $name;
}

/**
 * Return custom fields checked to be visible to tables
 * @param  string $field_to field relation
 * @return array
 */
function get_table_custom_fields($field_to)
{
    return get_custom_fields($field_to, ['show_on_table' => 1]);
}
/**
 * Get custom field value
 * @param  mixed $rel_id              the main ID from the table, e.q. the customer id, invoice id
 * @param  mixed $field_id_or_slug    field id, the custom field ID or custom field slug
 * @param  string $field_to           belongs to e.q leads, customers, staff
 * @param  string $format             format date values
 * @return string
 */
function get_custom_field_value($rel_id, $field_id_or_slug, $field_to, $format = true)
{
    $CI = & get_instance();

    $CI->db->select(db_prefix() . 'customfieldsvalues.value,' . db_prefix() . 'customfields.type');
    $CI->db->join(db_prefix() . 'customfields', db_prefix() . 'customfields.id=' . db_prefix() . 'customfieldsvalues.fieldid');
    $CI->db->where(db_prefix() . 'customfieldsvalues.relid', $rel_id);
    if (is_numeric($field_id_or_slug)) {
        $CI->db->where(db_prefix() . 'customfieldsvalues.fieldid', $field_id_or_slug);
    } else {
        $CI->db->where(db_prefix() . 'customfields.slug', $field_id_or_slug);
    }
    $CI->db->where(db_prefix() . 'customfieldsvalues.fieldto', $field_to);

    $row = $CI->db->get(db_prefix() . 'customfieldsvalues')->row();

    $result = '';
    if ($row) {
        $result = $row->value;
        if ($format == true) {
            if ($row->type == 'date_picker') {
                $result = _d($result);
            } elseif ($row->type == 'date_picker_time') {
                $result = _dt($result);
            }
        }
    }

    return $result;
}
/**
 * Check for custom fields, update on $_POST
 * @param  mixed $rel_id        the main ID from the table
 * @param  array $custom_fields all custom fields with id and values
 * @return boolean
 */
function handle_custom_fields_post($rel_id, $custom_fields, $is_cf_items = false)
{
    $affectedRows = 0;
    $CI           = & get_instance();

    foreach ($custom_fields as $key => $fields) {
        foreach ($fields as $field_id => $field_value) {
            $CI->db->where('relid', $rel_id);
            $CI->db->where('fieldid', $field_id);
            $CI->db->where('fieldto', ($is_cf_items ? 'items_pr' : $key));
            $row = $CI->db->get(db_prefix() . 'customfieldsvalues')->row();
            if (!is_array($field_value)) {
                $field_value = trim($field_value);
            }
            // Make necessary checkings for fields
            if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                $CI->db->where('id', $field_id);
                $field_checker = $CI->db->get(db_prefix() . 'customfields')->row();
                if ($field_checker->type == 'date_picker') {
                    $field_value = to_sql_date($field_value);
                } elseif ($field_checker->type == 'date_picker_time') {
                    $field_value = to_sql_date($field_value, true);
                } elseif ($field_checker->type == 'textarea') {
                    $field_value = nl2br($field_value);
                } elseif ($field_checker->type == 'checkbox' || $field_checker->type == 'multiselect') {
                    if ($field_checker->disalow_client_to_edit == 1 && is_client_logged_in()) {
                        continue;
                    }
                    if (is_array($field_value)) {
                        $v = 0;
                        foreach ($field_value as $chk) {
                            if ($chk == 'cfk_hidden') {
                                unset($field_value[$v]);
                            }
                            $v++;
                        }
                        $field_value = implode(', ', $field_value);
                    }
                }
            }
            if ($row) {
                $CI->db->where('id', $row->id);
                $CI->db->update(db_prefix() . 'customfieldsvalues', [
                    'value' => $field_value,
                ]);
                if ($CI->db->affected_rows() > 0) {
                    $affectedRows++;
                }
            } else {
                if ($field_value != '') {
                    $CI->db->insert(db_prefix() . 'customfieldsvalues', [
                        'relid'   => $rel_id,
                        'fieldid' => $field_id,
                        'fieldto' => $is_cf_items ? 'items_pr' : $key,
                        'value'   => $field_value,
                    ]);
                    $insert_id = $CI->db->insert_id();
                    if ($insert_id) {
                        $affectedRows++;
                    }
                }
            }
        }
    }
    if ($affectedRows > 0) {
        return true;
    }

    return false;
}

/**
 * Return items custom fields array for table html eq invoice html invoice pdf based on usage
 * @param  mixed $rel_id   rel id eq invoice id
 * @param  string $rel_type relation type eq invoice
 * @return array
 */
function get_items_custom_fields_for_table_html($rel_id, $rel_type)
{
    $whereSQL = 'id IN (SELECT fieldid FROM ' . db_prefix() . 'customfieldsvalues WHERE value != "" AND value IS NOT NULL AND fieldto="items" AND relid IN (SELECT id FROM ' . db_prefix() . 'itemable WHERE rel_type="' . $rel_type . '" AND rel_id="' . $rel_id . '") GROUP BY id HAVING COUNT(id) > 0)';

    $whereSQL = hooks()->apply_filters('items_custom_fields_for_table_sql', $whereSQL);

    return get_custom_fields('items', $whereSQL);
}
/**
 * Render custom fields for table add/edit preview area
 * @return string
 */
function render_custom_fields_items_table_add_edit_preview()
{
    $where = hooks()->apply_filters('custom_fields_where_items_table_add_edit_preview', []);

    return render_custom_fields('items', false, $where, [
        'add_edit_preview' => true,
    ]);
}
/**
 * Render custom fields for items for table which are already applied to eq. Invoice
 * @param  array $item      the $item variable from the foreach loop
 * @param  mixed $part_item_name the input name for items eq. newitems or items for existing items
 * @return string
 */
function render_custom_fields_items_table_in($item, $part_item_name)
{
    $item_id = false;

    // When converting eq proposal to estimate,invoice etc to get tha previous item values for auto populate
    if (isset($item['parent_item_id'])) {
        $item_id = $item['parent_item_id'];
    } elseif (isset($item['id']) && $item['id'] != 0) {
        $item_id = $item['id'];
    }

    return render_custom_fields('items', $item_id, [], [
        'items_applied'  => true,
        'part_item_name' => $part_item_name,
    ]);
}

/**
 * Get manually added company custom fields
 * @since Version 1.0.4
 * @return array
 */
function get_company_custom_fields()
{
    $fields = get_custom_fields('company');
    $i      = 0;
    foreach ($fields as $field) {
        $fields[$i]['label'] = $field['name'];
        $fields[$i]['value'] = get_custom_field_value(0, $field['id'], 'company');
        $i++;
    }

    return $fields;
}
/**
 * Custom helper function to check if custom field is of type date
 * @param  array  $field the custom field in loop
 * @return boolean
 */
function is_cf_date($field)
{
    if ($field['type'] == 'date_picker' || $field['type'] == 'date_picker_time') {
        return true;
    }

    return false;
}
/**
* Custom fields only where show on client portal is checked if:
* Is client logged in
* None is logged in
* The format is for email sending, means that the client will get the format
* The request is coming from clients area
* The request is from cron job
*/
function is_custom_fields_for_customers_portal()
{
    if (is_data_for_customer() || DEFINED('CRON')) {
        return true;
    }

    return false;
}
/**
 * Function used for JS to render custom field hyperlink
 * @return stirng
 */
function get_custom_fields_hyperlink_js_function()
{
    ob_start(); ?>
    <script>
        function custom_fields_hyperlink(){
         var cf_hyperlink = $('body').find('.cf-hyperlink');
         if(cf_hyperlink.length){
             $.each(cf_hyperlink,function(){
                var cfh_wrapper = $(this);
                var cfh_field_to = cfh_wrapper.attr('data-fieldto');
                var cfh_field_id = cfh_wrapper.attr('data-field-id');
                var textEl = $('body').find('#custom_fields_'+cfh_field_to+'_'+cfh_field_id+'_popover');
                var hiddenField = $("#custom_fields\\\["+cfh_field_to+"\\\]\\\["+cfh_field_id+"\\\]");
                var cfh_value = cfh_wrapper.attr('data-value');
                hiddenField.val(cfh_value);

                if($(hiddenField.val()).html() != ''){
                    textEl.html($(hiddenField.val()).html());
                }
                var cfh_field_name = cfh_wrapper.attr('data-field-name');
                textEl.popover({
                    html: true,
                    trigger: "manual",
                    placement: "top",
                    title:cfh_field_name,
                    content:function(){
                        return $(cfh_popover_templates[cfh_field_id]).html();
                    }
                }).on("click", function(e){
                    var $popup = $(this);
                    $popup.popover("toggle");
                    var titleField = $("#custom_fields_"+cfh_field_to+"_"+cfh_field_id+"_title");
                    var urlField = $("#custom_fields_"+cfh_field_to+"_"+cfh_field_id+"_link");
                    var ttl = $(hiddenField.val()).html();
                    var cfUrl = $(hiddenField.val()).attr("href");
                    if(cfUrl){
                        $('#cf_hyperlink_open_'+cfh_field_id).attr('href',(cfUrl.indexOf('://') === -1 ? 'http://' + cfUrl : cfUrl));
                    }
                    titleField.val(ttl);
                    urlField.val(cfUrl);
                    $("#custom_fields_"+cfh_field_to+"_"+cfh_field_id+"_btn-save").click(function(){
                        hiddenField.val((urlField.val() != '' ? '<a href="'+urlField.val()+'" target="_blank">' + titleField.val() + '</a>' : ''));
                        textEl.html(titleField.val() == "" ? "<?php echo _l('cf_translate_input_link_tip'); ?>" : titleField.val());
                        $popup.popover("toggle");
                    });
                    $("#custom_fields_"+cfh_field_to+"_"+cfh_field_id+"_btn-cancel").click(function(){
                        if(urlField.val() == ''){
                            hiddenField.val('');
                        }
                        $popup.popover("toggle");
                    });
                });
            });
         }
     }
 </script>
 <?php
    $contents = ob_get_contents();
    ob_end_clean();

    return $contents;
}

function is_custom_fields_smart_transfer_enabled()
{
    if (!defined('CUSTOM_FIELDS_SMART_TRANSFER')) {
        return true;
    }

    if (defined('CUSTOM_FIELDS_SMART_TRANSFER') && CUSTOM_FIELDS_SMART_TRANSFER) {
        return true;
    }

    return false;
}
