<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_proposals
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function export($rel_id, $rel_type)
    {
        // $readProposalsDir = '';
        // $tmpDir           = get_temp_dir();


        if (!class_exists('proposals_model')) {
            $this->ci->load->model('proposals_model');
        }

        $this->ci->db->where('rel_id', $rel_id);
        $this->ci->db->where('rel_type', $rel_type);

        $proposals = $this->ci->db->get(db_prefix().'proposals')->result_array();

        $this->ci->db->where('show_on_client_portal', 1);
        $this->ci->db->where('fieldto', 'proposal');
        $this->ci->db->order_by('field_order', 'asc');
        $custom_fields = $this->ci->db->get(db_prefix().'customfields')->result_array();
        /*
            if (count($proposals) > 0) {
                $uniqueIdentifier = $tmpDir . $rel_id . time() . '-proposals';
                $readProposalsDir = $uniqueIdentifier;
            }*/
        $this->ci->load->model('currencies_model');
        foreach ($proposals as $proposaArrayKey => $proposal) {

        // $proposal['attachments'] = _prepare_attachments_array_for_export($this->ci->proposals_model->get_attachments($proposal['id']));

            // $proposals[$proposaArrayKey] = parse_proposal_content_merge_fields($proposal);

            $proposals[$proposaArrayKey]['country'] = get_country($proposal['country']);

            $proposals[$proposaArrayKey]['currency'] = $this->ci->currencies_model->get($proposal['currency']);

            $proposals[$proposaArrayKey]['items'] = _prepare_items_array_for_export(get_items_by_type('proposal', $proposal['id']), 'proposal');

            $proposals[$proposaArrayKey]['comments'] = $this->ci->proposals_model->get_comments($proposal['id']);

            $proposals[$proposaArrayKey]['views'] = get_views_tracking('proposal', $proposal['id']);

            $proposals[$proposaArrayKey]['tracked_emails'] = get_tracked_emails($proposal['id'], 'proposal');

            $proposals[$proposaArrayKey]['additional_fields'] = [];
            foreach ($custom_fields as $cf) {
                $proposals[$proposaArrayKey]['additional_fields'][] = [
                    'name'  => $cf['name'],
                    'value' => get_custom_field_value($proposal['id'], $cf['id'], 'proposal'),
                ];
            }

            /*  $tmpProposalsDirName = $uniqueIdentifier;
              if (!is_dir($tmpProposalsDirName)) {
                  mkdir($tmpProposalsDirName, 0755);
              }

              $tmpProposalsDirName = $tmpProposalsDirName . '/' . $proposal['id'];

              mkdir($tmpProposalsDirName, 0755);*/

/*        if (count($proposal['attachments']) > 0 || !empty($proposal['signature'])) {
            $attachmentsDir = $tmpProposalsDirName . '/attachments';
            mkdir($attachmentsDir, 0755);

            foreach ($proposal['attachments'] as $att) {
                xcopy(get_upload_path_by_type('proposal') . $proposal['id'] . '/' . $att['file_name'], $attachmentsDir . '/' . $att['file_name']);
            }

            if (!empty($proposal['signature'])) {
                xcopy(get_upload_path_by_type('proposal') . $proposal['id'] . '/' . $proposal['signature'], $attachmentsDir . '/' . $proposal['signature']);
            }
        }*/

        // unset($proposal['id']);

        // $fp = fopen($tmpProposalsDirName . '/proposal.json', 'w');
        // fwrite($fp, json_encode($proposal, JSON_PRETTY_PRINT));
        // fclose($fp);
        }

        return $proposals;
    }
}
