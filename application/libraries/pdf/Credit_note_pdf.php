<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(__DIR__ . '/App_pdf.php');

class Credit_note_pdf extends App_pdf
{
    protected $credit_note;

    private $credit_note_number;

    public function __construct($credit_note, $tag = '')
    {
        $GLOBALS['credit_note_pdf'] = $credit_note;

        parent::__construct();

        $this->tag                = $tag;
        $this->credit_note        = $credit_note;
        $this->credit_note_number = format_credit_note_number($this->credit_note->id);
        $this->load_language($this->credit_note->clientid);
        $this->SetTitle($this->credit_note_number);
    }

    public function prepare()
    {
        $this->with_number_to_word($this->credit_note->clientid);

        $this->set_view_vars([
            'status'             => $this->credit_note->status,
            'credit_note_number' => $this->credit_note_number,
            'credit_note'        => $this->credit_note,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'credit_note';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_credit_note_pdf.php';
        $actualPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/credit_note_pdf.php';

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}
