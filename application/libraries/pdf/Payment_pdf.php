<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(__DIR__ . '/App_pdf.php');

class Payment_pdf extends App_pdf
{
    protected $payment;

    public function __construct($payment, $tag = '')
    {
        $GLOBALS['payment_pdf'] = $payment;

        parent::__construct();

        if (!class_exists('Invoices_model', false)) {
            $this->ci->load->model('invoices_model');
        }

        $this->payment = $payment;
        $this->tag     = $tag;

        $this->load_language($this->payment->invoice_data->clientid);
        $this->SetTitle(_l('payment') . ' #' . $this->payment->paymentid);
    }

    public function prepare()
    {
        $amountDue = ($this->payment->invoice_data->status != Invoices_model::STATUS_PAID && $this->payment->invoice_data->status != Invoices_model::STATUS_CANCELLED ? true : false);

        $this->set_view_vars([
            'payment'   => $this->payment,
            'amountDue' => $amountDue,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'payment';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_paymentpdf.php';
        $actualPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/paymentpdf.php';

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}
