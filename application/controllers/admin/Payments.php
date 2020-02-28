<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Payments extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('payments_model');
    }

    /* In case if user go only on /payments */
    public function index()
    {
        $this->list_payments();
    }

    public function list_payments()
    {
        if (!has_permission('payments', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('payments');
        }

        $data['title'] = _l('payments');
        $this->load->view('admin/payments/manage', $data);
    }

    public function table($clientid = '')
    {
        if (!has_permission('payments', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            ajax_access_denied();
        }

        $this->app->get_table_data('payments', [
            'clientid' => $clientid,
        ]);
    }

    /* Update payment data */
    public function payment($id = '')
    {
        if (!has_permission('payments', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('payments');
        }

        if (!$id) {
            redirect(admin_url('payments'));
        }

        if ($this->input->post()) {
            if (!has_permission('payments', '', 'edit')) {
                access_denied('Update Payment');
            }
            $success = $this->payments_model->update($this->input->post(), $id);
            if ($success) {
                set_alert('success', _l('updated_successfully', _l('payment')));
            }
            redirect(admin_url('payments/payment/' . $id));
        }

        $payment = $this->payments_model->get($id);

        if (!$payment) {
            show_404();
        }

        $this->load->model('invoices_model');
        $payment->invoice = $this->invoices_model->get($payment->invoiceid);
        $template_name    = 'invoice_payment_recorded_to_customer';

        $data = prepare_mail_preview_data($template_name, $payment->invoice->clientid);

        $data['payment'] = $payment;
        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [], true, true);

        $i = 0;
        foreach ($data['payment_modes'] as $mode) {
            if ($mode['active'] == 0 && $data['payment']->paymentmode != $mode['id']) {
                unset($data['payment_modes'][$i]);
            }
            $i++;
        }

        $data['title'] = _l('payment_receipt') . ' - ' . format_invoice_number($data['payment']->invoiceid);
        $this->load->view('admin/payments/payment', $data);
    }

    /**
     * Generate payment pdf
     * @since  Version 1.0.1
     * @param  mixed $id Payment id
     */
    public function pdf($id)
    {
        if (!has_permission('payments', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('View Payment');
        }

        $payment = $this->payments_model->get($id);

        if (!has_permission('payments', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && !user_can_view_invoice($payment->invoiceid)) {
            access_denied('View Payment');
        }

        $this->load->model('invoices_model');
        $payment->invoice_data = $this->invoices_model->get($payment->invoiceid);

        try {
            $paymentpdf = payment_pdf($payment);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $paymentpdf->Output(mb_strtoupper(slug_it(_l('payment') . '-' . $payment->paymentid)) . '.pdf', $type);
    }

    /**
     * Send payment manually to customer contacts
     * @since  2.3.2
     * @param  mixed $id payment id
     * @return mixed
     */
    public function send_to_email($id)
    {
        if (!has_permission('payments', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('Send Payment');
        }

        $payment = $this->payments_model->get($id);

        if (!has_permission('payments', '', 'view')
            && !has_permission('invoices', '', 'view_own')
            && !user_can_view_invoice($payment->invoiceid)) {
            access_denied('Send Payment');
        }

        $this->load->model('invoices_model');
        $payment->invoice_data = $this->invoices_model->get($payment->invoiceid);
        set_mailing_constant();

        $paymentpdf = payment_pdf($payment);
        $filename   = mb_strtoupper(slug_it(_l('payment') . '-' . $payment->paymentid), 'UTF-8') . '.pdf';

        $attach = $paymentpdf->Output($filename, 'S');

        $sent    = false;
        $sent_to = $this->input->post('sent_to');

        if (is_array($sent_to) && count($sent_to) > 0) {
            foreach ($sent_to as $contact_id) {
                if ($contact_id != '') {
                    $contact = $this->clients_model->get_contact($contact_id);

                    $template = mail_template('invoice_payment_recorded_to_customer', (array) $contact, $payment->invoice_data, false, $payment->paymentid);

                    $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $filename,
                            'type'       => 'application/pdf',
                        ]);

                    if ($template->send()) {
                        $sent = true;
                    }
                }
            }
        }

        // In case client use another language
        load_admin_language();
        set_alert($sent ? 'success' : 'danger', _l($sent ? 'payment_sent_successfully' : 'payment_sent_failed'));

        redirect(admin_url('payments/payment/' . $id));
    }

    /* Delete payment */
    public function delete($id)
    {
        if (!has_permission('payments', '', 'delete')) {
            access_denied('Delete Payment');
        }
        if (!$id) {
            redirect(admin_url('payments'));
        }
        $response = $this->payments_model->delete($id);
        if ($response == true) {
            set_alert('success', _l('deleted', _l('payment')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('payment_lowercase')));
        }
        redirect(admin_url('payments'));
    }
}
