<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Download extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('download');
    }

    public function preview_video()
    {
        $path      = FCPATH . $this->input->get('path');
        $file_type = $this->input->get('type');

        $allowed_extensions = get_html5_video_extensions();

        $pathinfo = pathinfo($path);

        if (!file_exists($path) || !isset($pathinfo['extension']) || !in_array($pathinfo['extension'], $allowed_extensions)) {
            $file_type = 'image/jpg';
            $path      = FCPATH . 'assets/images/preview-not-available.jpg';
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Type: ' . $file_type);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        if(ob_get_contents()) {
             ob_end_clean();
        }

        hooks()->do_action('before_output_preview_video');

        $file = fopen($path, 'rb');
        if ($file !== false) {
            while (!feof($file)) {
                echo fread($file, 1024);
            }
            fclose($file);
        }
    }

    public function preview_image()
    {
        $path      = FCPATH . $this->input->get('path');
        $file_type = $this->input->get('type');

        $allowed_extensions = [
            'jpg',
            'jpeg',
            'png',
            'bmp',
            'gif',
            'tif',
        ];

        $pathinfo = pathinfo($path);

        if (!file_exists($path) || !isset($pathinfo['extension']) || !in_array($pathinfo['extension'], $allowed_extensions)) {
            $file_type = 'image/jpg';
            $path      = FCPATH . 'assets/images/preview-not-available.jpg';
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Type: ' . $file_type);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        if(ob_get_contents()) {
             ob_end_clean();
        }

        hooks()->do_action('before_output_preview_image');
        $file = fopen($path, 'rb');
        if ($file !== false) {
            while (!feof($file)) {
                echo fread($file, 1024);
            }
            fclose($file);
        }
    }

    public function file($folder_indicator, $attachmentid = '')
    {
        $this->load->model('tickets_model');
        if ($folder_indicator == 'ticket') {
            if (is_logged_in()) {
                $this->db->where('id', $attachmentid);
                $attachment = $this->db->get(db_prefix().'ticket_attachments')->row();
                if (!$attachment) {
                    show_404();
                }
                $ticket   = $this->tickets_model->get_ticket_by_id($attachment->ticketid);
                $ticketid = $attachment->ticketid;
                if ($ticket->userid == get_client_user_id() || is_staff_logged_in()) {
                    if ($attachment->id != $attachmentid) {
                        show_404();
                    }
                    $path = get_upload_path_by_type('ticket') . $ticketid . '/' . $attachment->file_name;
                }
            }
        } elseif ($folder_indicator == 'newsfeed') {
            if (is_staff_logged_in()) {
                if (!$attachmentid) {
                    show_404();
                }
                $this->db->where('id', $attachmentid);
                $attachment = $this->db->get(db_prefix().'files')->row();
                if (!$attachment) {
                    show_404();
                }
                $path = get_upload_path_by_type('newsfeed') . $attachment->rel_id . '/' . $attachment->file_name;
            }
        } elseif ($folder_indicator == 'contract') {
            if (!$attachmentid) {
                show_404();
            }

            $this->db->where('attachment_key', $attachmentid);
            $attachment = $this->db->get(db_prefix().'files')->row();
            if (!$attachment) {
                show_404();
            }

            if(!is_staff_logged_in()) {
                $this->db->select('not_visible_to_client');
                $this->db->where('id', $attachment->rel_id);
                $contract = $this->db->get(db_prefix().'contracts')->row();
                if($contract->not_visible_to_client == 1) {
                    show_404();
                }
            }

            $path = get_upload_path_by_type('contract') . $attachment->rel_id . '/' . $attachment->file_name;
        } elseif ($folder_indicator == 'taskattachment') {
            if (!is_logged_in()) {
                show_404();
            }

            $this->db->where('attachment_key', $attachmentid);
            $attachment = $this->db->get(db_prefix().'files')->row();

            if (!$attachment) {
                show_404();
            }
            $path = get_upload_path_by_type('task') . $attachment->rel_id . '/' . $attachment->file_name;
        } elseif ($folder_indicator == 'sales_attachment') {
            if (!is_staff_logged_in()) {
                $this->db->where('visible_to_customer', 1);
            }

            $this->db->where('attachment_key', $attachmentid);
            $attachment = $this->db->get(db_prefix().'files')->row();
            if (!$attachment) {
                show_404();
            }

            $path = get_upload_path_by_type($attachment->rel_type) . $attachment->rel_id . '/' . $attachment->file_name;
        } elseif ($folder_indicator == 'expense') {
            if (!is_staff_logged_in()) {
                show_404();
            }
            $this->db->where('rel_id', $attachmentid);
            $this->db->where('rel_type', 'expense');
            $file = $this->db->get(db_prefix().'files')->row();
            $path = get_upload_path_by_type('expense') . $file->rel_id . '/' . $file->file_name;
        // l_attachment_key is if request is coming from public form
        } elseif ($folder_indicator == 'lead_attachment' || $folder_indicator == 'l_attachment_key') {
            if (!is_staff_logged_in() && strpos($_SERVER['HTTP_REFERER'], 'forms/l/') === false) {
                show_404();
            }

            // admin area
            if ($folder_indicator == 'lead_attachment') {
                $this->db->where('id', $attachmentid);
            } else {
                // Lead public form
                $this->db->where('attachment_key', $attachmentid);
            }

            $attachment = $this->db->get(db_prefix().'files')->row();

            if (!$attachment) {
                show_404();
            }

            $path = get_upload_path_by_type('lead') . $attachment->rel_id . '/' . $attachment->file_name;
        }  elseif ($folder_indicator == 'client') {
            $this->db->where('attachment_key', $attachmentid);
            $attachment = $this->db->get(db_prefix().'files')->row();
            if (!$attachment) {
                show_404();
            }
            if (has_permission('customers', '', 'view') || is_customer_admin($attachment->rel_id) || is_client_logged_in()) {
                $path = get_upload_path_by_type('customer') . $attachment->rel_id . '/' . $attachment->file_name;
            }
        } else {
            die('folder not specified');
        }

        force_download($path, null);
    }
}
