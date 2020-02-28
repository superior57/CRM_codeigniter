<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Privacy_policy extends ClientsController
{
    public function index()
    {
        $data['policy'] = get_option('privacy_policy');
        $data['title']  = _l('privacy_policy') . ' - ' . get_option('companyname');
        $this->data($data);
        $this->view('privacy_policy');
        $this->layout();
    }
}
