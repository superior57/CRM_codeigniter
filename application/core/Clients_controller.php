<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @deprecated 2.3.2
 * Use ClientsController instead
 */
class Clients_controller extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        _deprecated_function('Clients_controller', '2.3.2', 'ClientsController');
    }
}
