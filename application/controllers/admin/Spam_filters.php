<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Spam_filters extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_admin()) {
            access_denied('Spam Filters');
        }

        $this->load->model('spam_filters_model');
    }

    public function view($rel_type, $filter_type = '')
    {
        if ($this->input->is_ajax_request()) {
            $aColumns = [
                'value',
            ];
            $sIndexColumn = 'id';
            $sTable       = db_prefix().'spam_filters';
            $result       = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [
                'AND type ="' . $filter_type . '" AND rel_type="' . $rel_type . '"',
            ], [
                'id',
            ]);
            $output  = $result['output'];
            $rResult = $result['rResult'];
            foreach ($rResult as $aRow) {
                $row = [];
                for ($i = 0; $i < count($aColumns); $i++) {
                    $_data = $aRow[$aColumns[$i]];
                    $row[] = $_data;
                }
                $options = icon_btn('#', 'pencil-square-o', 'btn-default', [
                    'onclick'    => 'edit_spam_filter(this,' . $aRow['id'] . '); return false;',
                    'data-value' => $aRow['value'],
                    'data-type'  => $filter_type,
                ]);
                $row[]              = $options .= icon_btn('spam_filters/delete/' . $aRow['id'] . '/' . $rel_type, 'remove', 'btn-danger _delete');
                $output['aaData'][] = $row;
            }
            echo json_encode($output);
            die();
        }

        $data['rel_type'] = $rel_type;
        $data['title']    = _l('spam_filters');
        $this->load->view('admin/spam_filters/list', $data);
    }

    public function filter($type)
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            if ($this->input->post('id')) {
                $success = $this->spam_filters_model->edit($this->input->post());
                if ($success == true) {
                    $message = _l('updated_successfully', _l('spam_filter'));
                }
            } else {
                $success = $this->spam_filters_model->add($this->input->post(), $type);
                if ($success == true) {
                    $message = _l('added_successfully', _l('spam_filter'));
                }
            }
        }
        echo json_encode([
                    'success' => $success,
                    'message' => $message,
                ]);
    }

    public function delete($id, $type)
    {
        $success = $this->spam_filters_model->delete($id, $type);
        if ($success) {
            set_alert('success', _l('deleted', _l('spam_filter')));
        }

        redirect(admin_url('spam_filters/view/' . $type));
    }
}
