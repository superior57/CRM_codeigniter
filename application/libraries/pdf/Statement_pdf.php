<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(__DIR__ . '/App_pdf.php');

class Statement_pdf extends App_pdf
{
    protected $statement;

    public function __construct($statement)
    {
        $GLOBALS['statement_pdf'] = $statement;

        parent::__construct();

        $this->statement        = $statement;
        $this->load_language($this->statement['client_id']);
        $this->SetTitle(_l('account_summary'));
    }

    public function prepare()
    {
        $this->set_view_vars([
            'statement'        => $this->statement,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'statement';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_statementpdf.php';
        $actualPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/statementpdf.php';

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}
