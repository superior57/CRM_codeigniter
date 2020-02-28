<?php

defined('BASEPATH') or exit('No direct script access allowed');

@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', 360);

class App_bulk_pdf_export
{
    /**
     * Codeigniter instance
     * @var object
     */
    private $ci;

    /**
     * This property is used to store the PDF
     * @var object
     */
    public $pdf_zip;

    /**
     * Can view based on the $type property
     * @var boolean
     */
    private $can_view;

    /**
     * Export type
     * Possible values: invoices, estimates, credit_notes, payments, proposal
     * @var string
     */
    private $type;

    /**
     * Filter period from
     * @var string
     */
    private $date_from;

    /**
     * Filter period to
     * @var string
     */
    private $date_to;

    /**
     * Payments export payment mode
     * @var string
     */
    private $payment_mode;

    /**
     * Status for estimates, invoices, credit notes, proposals
     * @var mixed
     */
    private $status;

    /**
     * Required status parameter
     * @var array
     */
    private $status_param_required = ['invoices', 'estimates', 'credit_notes', 'proposals'];

    /**
     * PDF tag
     * @var string
     */
    private $pdf_tag = '';

    /**
     * Zip directory
     * @var string
     */
    private $zip_dir;

    /**
     * Unique years for the data that is exporting
     * Used to create the folders
     * @var array
     */
    private $years = [];

    /**
     * Client ID if exporting for specific client
     * @var mixed
     */
    private $client_id = null;

    /**
     * Client ID Column
     * @var string
     */
    private $client_id_column = 'clientid';

    /**
     * Add the main folder contents in folder
     * e.q. customer-name/YEAR/INVOICE.pdf
     * @var string
     */
    private $in_folder = null;

    /**
     * Redirect user to specific url after error
     * This parameter is required
     * @var string
     */
    private $redirect_on_error = '';

    public function __construct($config)
    {
        $this->type = $config['export_type'];

        if (!isset($config['redirect_on_error'])) {
            show_error('You must set parameter "redirect_on_error"');
        } else {
            $this->redirect_on_error = $config['redirect_on_error'];
        }

        if (in_array($this->type, $this->status_param_required)
            && (!isset($config['status']) || isset($config['status']) && empty($config['status']))) {
            show_error('You must set "status" config if you are exporting data for: ' . implode(', ', $this->status_param_required));
        }

        if (isset($config['status'])) {
            $this->status = $config['status'];
        }

        if (isset($config['client_id'])) {
            $this->set_client_id($config['client_id']);
        }

        if (isset($config['date_from']) && isset($config['date_to'])) {
            $this->date_from = to_sql_date($config['date_from']);
            $this->date_to   = to_sql_date($config['date_to']);
        }

        if (isset($config['payment_mode'])) {
            $this->payment_mode = $config['payment_mode'];
        }

        if (isset($config['tag']) && !empty($config['tag'])) {
            $this->pdf_tag = $config['tag'];
        }

        $this->ci = &get_instance();
        $this->ci->load->library('zip');
        $this->can_view = has_permission($this->type, '', 'view');

        if (!is_really_writable(TEMP_FOLDER)) {
            show_error(TEMP_FOLDER . ' folder is not writable. You need to change the permissions to 0755');
        }

        $this->dir = TEMP_FOLDER . $this->type;

        if (is_dir($this->dir)) {
            $this->clear($this->dir);
        }

        mkdir($this->dir, 0755);
        register_shutdown_function([$this, 'clear'], $this->dir);
    }

    /**
     * The main function for exporting
     * @return mixed
     */
    public function export()
    {
        if (method_exists($this, $this->type)) {
            $data = $this->{$this->type}();
        } else {
            // This may not happend but in all cases :)
            show_error('No export type selected!');
        }

        $this->zip();
    }

    /**
     * Create payment export
     * @return object
     */
    private function payments()
    {
        $this->ci->db->select('' . db_prefix() . 'invoicepaymentrecords.id as paymentid');
        $this->ci->db->from(db_prefix() . 'invoicepaymentrecords');
        $this->ci->db->join(db_prefix() . 'invoices', '' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid', 'left');
        $this->ci->db->join(db_prefix() . 'clients', '' . db_prefix() . 'clients.userid = ' . db_prefix() . 'invoices.clientid', 'left');

        if (!$this->can_view) {
            $whereUser = '';
            $whereUser .= '(invoiceid IN (SELECT id FROM ' . db_prefix() . 'invoices WHERE addedfrom=' . get_staff_user_id() . ')';
            if (get_option('allow_staff_view_invoices_assigned') == 1) {
                $whereUser .= ' OR invoiceid IN (SELECT id FROM ' . db_prefix() . 'invoices WHERE sale_agent=' . get_staff_user_id() . ')';
            }
            $whereUser .= ')';
            $this->ci->db->where($whereUser);
        }
        if ($this->payment_mode) {
            $this->ci->db->where('paymentmode', $this->payment_mode);
        }

        $this->ci->db->order_by($this->get_date_column(), 'desc');

        $data = $this->finalize();

        $this->ci->load->model('payments_model');
        $this->ci->load->model('invoices_model');
        foreach ($data as $payment) {
            $payment_data = $this->ci->payments_model->get($payment['paymentid']);

            $payment_data->invoice_data = $this->ci->invoices_model->get($payment_data->invoiceid);

            $file_name = strtoupper(_l('payment'));
            $file_name .= '-' . strtoupper($payment_data->paymentid) . '.pdf';

            $pdf = payment_pdf($payment_data, $this->pdf_tag);
            $this->save_to_dir($payment_data, $pdf, $file_name);
        }

        return $this;
    }

    /**
     * Create estimates export
     * @return object
     */
    private function estimates()
    {
        $noPermissionQuery = get_estimates_where_sql_for_staff(get_staff_user_id());

        $this->ci->db->select('id');
        $this->ci->db->from(db_prefix() . 'estimates');

        if ($this->status != 'all') {
            $this->ci->db->where('status', $this->status);
        }

        if (!$this->can_view) {
            $this->ci->db->where($noPermissionQuery);
        }

        $this->ci->db->order_by($this->get_date_column(), 'desc');

        $data = $this->finalize();
        $this->ci->load->model('estimates_model');
        foreach ($data as $estimate) {
            $estimate = $this->ci->estimates_model->get($estimate['id']);
            $pdf      = estimate_pdf($estimate, $this->pdf_tag);
            $this->save_to_dir($estimate, $pdf, strtoupper(slug_it(format_estimate_number($estimate->id))) . '.pdf');
        }

        return $this;
    }

    /**
     * Create invoices export
     * @return object
     */
    private function invoices()
    {
        $noPermissionQuery = get_invoices_where_sql_for_staff(get_staff_user_id());
        $notSentQuery = 'sent=0 AND status NOT IN(2,5)' . (!$this->can_view ? ' AND (' . $noPermissionQuery . ')' : '');

        $this->ci->db->select('id');
        $this->ci->db->from(db_prefix() . 'invoices');
        if ($this->status != 'all') {
            if(is_numeric($status)){
                $this->ci->db->where('status', $this->status);
            } else {
                $this->ci->db->where($notSentQuery);
            }
        }

        if (!$this->can_view) {
            $this->ci->db->where($noPermissionQuery);
        }

        $this->ci->db->order_by($this->get_date_column(), 'desc');

        $data = $this->finalize();
        $this->ci->load->model('invoices_model');
        foreach ($data as $invoice) {
            $invoice = $this->ci->invoices_model->get($invoice['id']);
            $pdf     = invoice_pdf($invoice, $this->pdf_tag);
            $this->save_to_dir($invoice, $pdf, strtoupper(slug_it(format_invoice_number($invoice->id))) . '.pdf');
        }

        return $this;
    }

    /**
     * Create proposals export
     * @return object
     */
    public function proposals()
    {
        $noPermissionQuery = get_proposals_sql_where_staff(get_staff_user_id());

        $this->ci->db->select('id');
        $this->ci->db->from(db_prefix() . 'proposals');
        if ($this->status != 'all') {
            $this->ci->db->where('status', $this->status);
        }

        if (!$this->can_view) {
            $this->ci->db->where($noPermissionQuery);
        }

        $this->ci->db->order_by($this->get_date_column(), 'desc');

        $data = $this->finalize();

        $this->ci->load->model('proposals_model');
        foreach ($data as $proposal) {
            $proposal = $this->ci->proposals_model->get($proposal['id']);
            $pdf      = proposal_pdf($proposal, $this->pdf_tag);
            $this->save_to_dir($proposal, $pdf, strtoupper(format_proposal_number($proposal->id)) . '.pdf');
        }

        return $this;
    }

    /**
     * Create credit notes export
     * @return object
     */
    public function credit_notes()
    {
        $this->ci->db->select('id');
        $this->ci->db->from(db_prefix() . 'creditnotes');

        if ($this->status != 'all') {
            $this->ci->db->where('status', $this->status);
        }

        if (!$this->can_view) {
            $this->ci->db->where('addedfrom', get_staff_user_id());
        }

        $this->ci->db->order_by($this->get_date_column(), 'desc');

        $data = $this->finalize();
        $this->ci->load->model('credit_notes_model');
        foreach ($data as $credit_note) {
            $credit_note = $this->ci->credit_notes_model->get($credit_note['id']);
            $pdf         = credit_note_pdf($credit_note, $this->pdf_tag);
            $this->save_to_dir($credit_note, $pdf, strtoupper(slug_it(format_credit_note_number($credit_note->id))) . '.pdf');
        }

        return $this;
    }

    /**
     * Sets the client id column
     * @param string $column
     */
    public function set_client_id_column($column)
    {
        $this->client_id_column = $column;

        return $this;
    }

    /**
     * Set client id
     * @param mixed $client_id
     */
    public function set_client_id($client_id)
    {
        $this->client_id = $client_id;

        return $this;
    }

    /**
     * Set export contents in folder
     * @param  string $folder
     * @return object
     */
    public function in_folder($folder)
    {
        $this->in_folder = $folder;

        return $this;
    }

    /**
     * Used to zip the data in the folder
     * @return null
     */
    private function zip()
    {
        $this->ci->zip->read_dir($this->dir, false);
        $this->ci->zip->download(slug_it(get_option('companyname')) . '-' . $this->type . '.zip');
        $this->ci->zip->clear_data();
    }

    /**
     * Save the PDF to the temporary directory to zip later
     * @param  object $object    the data object, e.q. invoice, estimate
     * @param  mixed $pdf       the actual PDF
     * @param  string file name for thee PDF file
     * @return null
     */
    private function save_to_dir($object, $pdf, $file_name)
    {
        $dir = $this->dir . '/';
        if ($this->in_folder) {
            $dir .= $this->in_folder . '/';
        }
        $dateColumn = str_replace('`', '', $this->get_date_column());

        if (strpos($dateColumn, '.') !== false) {
            $dateColumn = strafter($dateColumn, '.');
        }
        if (!empty($object->{$dateColumn})) {
            $dir .= date('Y', strtotime($object->{$dateColumn})) . '/';
        }

        $dir .= $file_name;

        $this->pdf_zip = $pdf;
        $this->pdf_zip->Output($dir, 'F');
    }

    /**
     * Set date query for the data that is exported
     */
    private function set_date_query()
    {
        if ($this->date_from && $this->date_to) {
            $date_field = $this->get_date_column();
            if ($this->date_from == $this->date_to) {
                $this->ci->db->where($date_field, $this->date_from);
            } else {
                $this->ci->db->where($date_field . ' BETWEEN "' . $this->date_from . '" AND "' . $this->date_to . '"');
            }
        }
    }

    /**
     * Finalize all the query and necessary actions, used for common export options
     * @return array
     */
    private function finalize()
    {
        $this->set_date_query();

        if ($this->client_id) {
            $this->ci->db->where($this->client_id_column, $this->client_id);
        }

        $data          = $this->ci->db->get()->result_array();
        $last_query    = $this->ci->db->last_query();
        $withoutSelect = strafter($last_query, 'FROM');

        $yearSelectQuery = 'SELECT DISTINCT(YEAR(' . $this->get_date_column() . ')) as year FROM' . str_replace('ORDER BY ' . $this->get_date_column() . '', 'ORDER BY year', $withoutSelect);

        $years = $this->ci->db->query($yearSelectQuery)->result_array();

        if (count($data) == 0) {
            set_alert('warning', _l('no_data_found_bulk_pdf_export'));
            redirect($this->redirect_on_error);
        }

        $this->set_years_and_create_directories($years);

        return $data;
    }

    /**
     * Set years property and create years directories
     * @param array $years
     */
    private function set_years_and_create_directories($years)
    {
        $flat = [];
        $dir  = $this->dir . '/';
        if ($this->in_folder) {
            $dir .= $this->in_folder . '/';
        }

        foreach ($years as $year) {
            if (!is_dir($dir . $year['year'])) {
                mkdir($dir . $year['year'], 0755, true);
            }
            $flat[] = $year['year'];
        }

        $this->years = $flat;
    }

    /**
     * Get the date column for the exported feature
     * @return string
     */
    private function get_date_column()
    {
        $date_field = '`date`';
        // Column date is ambiguous in payments
        if ($this->type == 'payments') {
            $date_field = '`' . db_prefix() . 'invoicepaymentrecords`.`date`';
        }

        return $date_field;
    }

    /**
     * Clear the temporary folder
     * @param  string $dir directory to clear
     * @return null
     */
    public function clear($dir)
    {
        if ($dir == TEMP_FOLDER) {
            return true;
        }

        delete_files($dir);
        delete_dir($dir);
    }
}
