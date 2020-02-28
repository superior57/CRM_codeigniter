<?php defined('BASEPATH') or exit('No direct script access allowed');

render_datatable([
    _l('payments_table_number_heading'),
    _l('payments_table_invoicenumber_heading'),
    _l('payments_table_mode_heading'),
    _l('payment_transaction_id'),
    [
        'name'     => _l('payments_table_client_heading'),
        'th_attrs' => ['class' => (isset($client) ? 'not_visible' : '')],
    ],
    _l('payments_table_amount_heading'),
    _l('payments_table_date_heading'),
], (isset($class) ? $class : 'payments'), [], [
    'data-last-order-identifier' => 'payments',
    'data-default-order'         => get_table_last_order('payments'),
]);
