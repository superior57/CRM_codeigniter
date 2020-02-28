<?php

defined('BASEPATH') or exit('No direct script access allowed');

function check_contract_restrictions($id, $hash)
{
    $CI = & get_instance();
    $CI->load->model('contracts_model');

    if (!$hash || !$id) {
        show_404();
    }

    if (!is_client_logged_in() && !is_staff_logged_in()) {
        if (get_option('view_contract_only_logged_in') == 1) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }
    }

    $contract = $CI->contracts_model->get($id);
    if (!$contract || ($contract->hash != $hash)) {
        show_404();
    }
    // Do one more check
    if (!is_staff_logged_in()) {
        if (get_option('view_contract_only_logged_in') == 1) {
            if ($contract->client != get_client_user_id()) {
                show_404();
            }
        }
    }
}

/**
 * Function that will search possible contracts templates in applicaion/views/admin/contracts/templates
 * Will return any found files and user will be able to add new template
 * @return array
 */
function get_contract_templates()
{
    $contract_templates = [];
    if (is_dir(VIEWPATH . 'admin/contracts/templates')) {
        foreach (list_files(VIEWPATH . 'admin/contracts/templates') as $template) {
            $contract_templates[] = $template;
        }
    }

    return $contract_templates;
}

function send_contract_signed_notification_to_staff($contract_id)
{
    $CI = &get_instance();
    $CI->db->where('id', $contract_id);
    $contract = $CI->db->get(db_prefix().'contracts')->row();

    if (!$contract) {
        return false;
    }

    // Get creator
    $CI->db->select('staffid, email');
    $CI->db->where('staffid', $contract->addedfrom);
    $staff_contract = $CI->db->get(db_prefix().'staff')->result_array();

    $notifiedUsers = [];
    foreach ($staff_contract as $member) {
        $notified = add_notification([
                        'description'     => 'not_contract_signed',
                        'touserid'        => $member['staffid'],
                        'fromcompany'     => 1,
                        'fromuserid'      => null,
                        'link'            => 'contracts/contract/' . $contract->id,
                        'additional_data' => serialize([
                            '<b>' . $contract->subject . '</b>',
                        ]),
                    ]);

        if ($notified) {
            array_push($notifiedUsers, $member['staffid']);
        }

        send_mail_template('contract_signed_to_staff', $contract, $member);
    }

    pusher_trigger_notification($notifiedUsers);
}
