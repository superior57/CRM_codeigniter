<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(__DIR__ . '/App_pdf.php');

class Invoice_pdf extends App_pdf
{
    protected $invoice;

    private $invoice_number;

    public function __construct($invoice, $tag = '')
    {

        $invoice                = hooks()->apply_filters('invoice_html_pdf_data', $invoice);
        $GLOBALS['invoice_pdf'] = $invoice;

        parent::__construct();

        if (!class_exists('Invoices_model', false)) {
            $this->ci->load->model('invoices_model');
        }

        $this->tag            = $tag;
        $this->invoice        = $invoice;
        $this->invoice_number = format_invoice_number($this->invoice->id);
        $this->load_language($this->invoice->clientid);
        $this->SetTitle($this->invoice_number);
    }

    public function prepare()
    {
        $this->with_number_to_word($this->invoice->clientid);

        $this->set_view_vars([
            'status'         => $this->invoice->status,
            'invoice_number' => $this->invoice_number,
            'payment_modes'  => $this->get_payment_modes(),
            'invoice'        => $this->invoice,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'invoice';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_invoicepdf.php';
        $actualPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/invoicepdf.php';

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }

    private function get_payment_modes()
    {
        $this->ci->load->model('payment_modes_model');
        $payment_modes = $this->ci->payment_modes_model->get();

        // In case user want to include {invoice_number} or {client_id} in PDF offline mode description
        foreach ($payment_modes as $key => $mode) {
            if (isset($mode['description'])) {
                $payment_modes[$key]['description'] = str_replace('{invoice_number}', $this->invoice_number, $mode['description']);
                $payment_modes[$key]['description'] = str_replace('{client_id}', $this->invoice->clientid, $mode['description']);
            }
        }

        return $payment_modes;
    }
}
