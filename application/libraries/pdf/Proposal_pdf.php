<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(__DIR__ . '/App_pdf.php');

class Proposal_pdf extends App_pdf
{
    protected $proposal;

    private $proposal_number;

    public function __construct($proposal, $tag = '')
    {
        $proposal                = hooks()->apply_filters('proposal_html_pdf_data', $proposal);
        $GLOBALS['proposal_pdf'] = $proposal;

        parent::__construct();

        $this->tag      = $tag;
        $this->proposal = $proposal;

        $this->proposal_number = format_proposal_number($this->proposal->id);

        if ($proposal->rel_id != null && $proposal->rel_type == 'customer') {
            $this->load_language($proposal->rel_id);
        }

        $this->SetTitle($this->proposal_number);
        $this->SetDisplayMode('default', 'OneColumn');

        # Don't remove these lines - important for the PDF layout
        $this->proposal->content = $this->fix_editor_html($this->proposal->content);
    }

    public function prepare()
    {
        $number_word_lang_rel_id = 'unknown';

        if ($this->proposal->rel_type == 'customer') {
            $number_word_lang_rel_id = $this->proposal->rel_id;
        }

        $this->with_number_to_word($number_word_lang_rel_id);

        $total = '';
        if ($this->proposal->total != 0) {
            $total = app_format_money($this->proposal->total, get_currency($this->proposal->currency));
            $total = _l('proposal_total') . ': ' . $total;
        }

        $this->set_view_vars([
            'number'       => $this->proposal_number,
            'proposal'     => $this->proposal,
            'total'        => $total,
            'proposal_url' => site_url('proposal/' . $this->proposal->id . '/' . $this->proposal->hash),
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'proposal';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_proposalpdf.php';
        $actualPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/proposalpdf.php';

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}
