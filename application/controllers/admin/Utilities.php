<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Utilities extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('utilities_model');
    }

    /* All perfex activity log */
    public function activity_log()
    {
        // Only full admin have permission to activity log
        if (!is_admin()) {
            access_denied('Activity Log');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('activity_log');
        }
        $data['title'] = _l('utility_activity_log');
        $this->load->view('admin/utilities/activity_log', $data);
    }

    /* All perfex activity log */
    public function pipe_log()
    {
        // Only full admin have permission to activity log
        if (!is_admin()) {
            access_denied('Ticket Pipe Log');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('ticket_pipe_log');
        }
        $data['title'] = _l('ticket_pipe_log');
        $this->load->view('admin/utilities/ticket_pipe_log', $data);
    }

    public function clear_activity_log()
    {
        if (!is_admin()) {
            access_denied('Clear activity log');
        }
        $this->db->empty_table(db_prefix() . 'activity_log');
        redirect(admin_url('utilities/activity_log'));
    }

    public function clear_pipe_log()
    {
        if (!is_admin()) {
            access_denied('Clear ticket pipe activity log');
        }
        $this->db->empty_table(db_prefix() . 'tickets_pipe_log');
        redirect(admin_url('utilities/pipe_log'));
    }

    /* Calendar functions */
    public function calendar()
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            $data    = $this->input->post();
            $success = $this->utilities_model->event($data);
            $message = '';
            if ($success) {
                if (isset($data['eventid'])) {
                    $message = _l('event_updated');
                } else {
                    $message = _l('utility_calendar_event_added_successfully');
                }
            }
            echo json_encode([
                'success' => $success,
                'message' => $message,
            ]);
            die();
        }
        $data['google_ids_calendars'] = $this->misc_model->get_google_calendar_ids();
        $data['google_calendar_api']  = get_option('google_calendar_api_key');
        $data['title']                = _l('calendar');
        add_calendar_assets();

        $this->load->view('admin/utilities/calendar', $data);
    }

    public function get_calendar_data()
    {
        if ($this->input->is_ajax_request()) {
            echo json_encode($this->utilities_model->get_calendar_data(
                $this->input->post('start'),
                $this->input->post('end'),
                '',
                '',
                $this->input->post()
            ));
            die();
        }
    }

    public function view_event($id)
    {
        $data['event'] = $this->utilities_model->get_event($id);
        if ($data['event']->public == 1 && !is_staff_member()
            || $data['event']->public == 0 && $data['event']->userid != get_staff_user_id()) {
        } else {
            $this->load->view('admin/utilities/event', $data);
        }
    }

    public function delete_event($id)
    {
        if ($this->input->is_ajax_request()) {
            $event = $this->utilities_model->get_event_by_id($id);
            if ($event->userid != get_staff_user_id() && !is_admin()) {
                echo json_encode([
                    'success' => false,
                ]);
                die;
            }
            $success = $this->utilities_model->delete_event($id);
            $message = '';
            if ($success) {
                $message = _l('utility_calendar_event_deleted_successfully');
            }
            echo json_encode([
                'success' => $success,
                'message' => $message,
            ]);
            die();
        }
    }

    // Moved here from version 1.0.5
    public function media()
    {
        $this->load->helper('url');
        $data['title']     = _l('media_files');
        $data['connector'] = admin_url() . '/utilities/media_connector';

        $mediaLocale = get_media_locale();

        $this->app_scripts->add('media-js', 'assets/plugins/elFinder/js/elfinder.min.js');

        if (file_exists(FCPATH . 'assets/plugins/elFinder/js/i18n/elfinder.' . $mediaLocale . '.js') && $mediaLocale != 'en') {
            $this->app_scripts->add('media-lang-js', 'assets/plugins/elFinder/js/i18n/elfinder.' . $mediaLocale . '.js');
        }

        $this->load->view('admin/utilities/media', $data);
    }

    public function media_connector()
    {
        $media_folder = $this->app->get_media_folder();
        $mediaPath    = FCPATH . $media_folder;

        if (!is_dir($mediaPath)) {
            mkdir($mediaPath, 0755);
        }

        if (!file_exists($mediaPath . '/index.html')) {
            $fp = fopen($mediaPath . '/index.html', 'w');
            if ($fp) {
                fclose($fp);
            }
        }

        $this->load->helper('path');

        $root_options = [
            'driver' => 'LocalFileSystem',
            'path'   => set_realpath($media_folder),
            'URL'    => site_url($media_folder) . '/',
            //'debug'=>true,
            'uploadMaxSize' => get_option('media_max_file_size_upload') . 'M',
            'accessControl' => 'access_control_media',
            'uploadDeny'    => [
                'application/x-httpd-php',
                'application/php',
                'application/x-php',
                'text/php',
                'text/x-php',
                'application/x-httpd-php-source',
                'application/perl',
                'application/x-perl',
                'application/x-python',
                'application/python',
                'application/x-bytecode.python',
                'application/x-python-bytecode',
                'application/x-python-code',
                'wwwserver/shellcgi', // CGI
            ],
            'uploadAllow' => !$this->input->get('editor') ? [] : ['image', 'video'],
            'uploadOrder' => [
                'deny',
                'allow',
            ],
            'attributes' => [
                [
                    'pattern' => '/.tmb/',
                    'hidden'  => true,
                ],
                [
                    'pattern' => '/.quarantine/',
                    'hidden'  => true,
                ],
                [
                    'pattern' => '/public/',
                    'hidden'  => true,
                ],
            ],
        ];

        if (!is_admin()) {
            $this->db->select('media_path_slug,staffid,firstname,lastname')
            ->from(db_prefix() . 'staff')
            ->where('staffid', get_staff_user_id());
            $user = $this->db->get()->row();
            $path = set_realpath($media_folder . '/' . $user->media_path_slug);
            if (empty($user->media_path_slug)) {
                $this->db->where('staffid', $user->staffid);
                $slug = slug_it($user->firstname . ' ' . $user->lastname);
                $this->db->update(db_prefix() . 'staff', [
                    'media_path_slug' => $slug,
                ]);
                $user->media_path_slug = $slug;
                $path                  = set_realpath($media_folder . '/' . $user->media_path_slug);
            }
            if (!is_dir($path)) {
                mkdir($path, 0755);
            }
            if (!file_exists($path . '/index.html')) {
                $fp = fopen($path . '/index.html', 'w');
                if ($fp) {
                    fclose($fp);
                }
            }
            array_push($root_options['attributes'], [
                'pattern' => '/.(' . $user->media_path_slug . '+)/', // Prevent deleting/renaming folder
                'read'    => true,
                'write'   => true,
                'locked'  => true,
            ]);
            $root_options['path'] = $path;
            $root_options['URL']  = site_url($media_folder . '/' . $user->media_path_slug) . '/';
        }

        $publicRootPath      = $media_folder . '/public';
        $public_root         = $root_options;
        $public_root['path'] = set_realpath($publicRootPath);

        $public_root['URL'] = site_url($media_folder) . '/public';
        unset($public_root['attributes'][3]);

        if (!is_dir($publicRootPath)) {
            mkdir($publicRootPath, 0755);
        }

        if (!file_exists($publicRootPath . '/index.html')) {
            $fp = fopen($publicRootPath . '/index.html', 'w');
            if ($fp) {
                fclose($fp);
            }
        }

        $opts = [
            'roots' => [
                $root_options,
                $public_root,
            ],
        ];

        $opts      = hooks()->apply_filters('before_init_media', $opts);
        $connector = new elFinderConnector(new elFinder($opts));
        $connector->run();
    }

    public function bulk_pdf_exporter()
    {
        if (!has_permission('bulk_pdf_exporter', '', 'view')) {
            access_denied('bulk_pdf_exporter');
        }

        if ($this->input->post()) {
            $export_type = $this->input->post('export_type');

            $this->load->library('app_bulk_pdf_export', [
                'export_type'       => $export_type,
                'status'            => $this->input->post($export_type . '_export_status'),
                'date_from'         => $this->input->post('date-from'),
                'date_to'           => $this->input->post('date-to'),
                'payment_mode'      => $this->input->post('paymentmode'),
                'tag'               => $this->input->post('tag'),
                'redirect_on_error' => admin_url('utilities/bulk_pdf_exporter'),
            ]);

            $this->app_bulk_pdf_export->export();
        }

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get();

        $this->load->model('invoices_model');
        $data['invoice_statuses'] = $this->invoices_model->get_statuses();

        $this->load->model('credit_notes_model');
        $data['credit_notes_statuses'] = $this->credit_notes_model->get_statuses();

        $this->load->model('proposals_model');
        $data['proposal_statuses'] = $this->proposals_model->get_statuses();

        $this->load->model('estimates_model');
        $data['estimate_statuses'] = $this->estimates_model->get_statuses();

        $features = [];

        if (has_permission('invoices', '', 'view')
        || has_permission('invoices', '', 'view_own')
        || get_option('allow_staff_view_invoices_assigned') == '1') {
            $features[] = [
                'feature' => 'invoices',
                'name'    => _l('bulk_export_pdf_invoices'),
            ];
        }

        if (has_permission('estimates', '', 'view')
            || has_permission('estimates', '', 'view_own')
            || get_option('allow_staff_view_estimates_assigned') == '1') {
            $features[] = [
                'feature' => 'estimates',
                'name'    => _l('bulk_export_pdf_estimates'),
            ];
        }

        if (has_permission('payments', '', 'view') || has_permission('invoices', '', 'view_own')) {
            $features[] = [
                'feature' => 'payments',
                'name'    => _l('bulk_export_pdf_payments'),
            ];
        }

        if (has_permission('credit_notes', '', 'view') || has_permission('credit_notes', '', 'view_own')) {
            $features[] = [
                'feature' => 'credit_notes',
                'name'    => _l('credit_notes'),
            ];
        }

        if (has_permission('proposals', '', 'view')
            || has_permission('proposals', '', 'view_own')
            || get_option('allow_staff_view_proposals_assigned') == '1') {
            $features[] = [
                'feature' => 'proposals',
                'name'    => _l('bulk_export_pdf_proposals'),
            ];
        }

        $data['bulk_pdf_export_available_features'] = hooks()->apply_filters(
            'bulk_pdf_export_available_features',
            $features
        );

        $data['title'] = _l('bulk_pdf_exporter');
        $this->load->view('admin/utilities/bulk_pdf_exporter', $data);
    }
}
