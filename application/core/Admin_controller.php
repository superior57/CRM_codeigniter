<?php

defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @deprecated 2.3.2
 * Use AdminController instead
 */
class Admin_controller extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        _deprecated_function('Admin_controller', '2.3.2', 'AdminController');
    }
}
