<?php

defined('BASEPATH') or exit('No direct script access allowed');

function create_subscription_invoice_data($subscription, $invoice)
{
    $CI     = &get_instance();
    $client = $CI->clients_model->get($subscription->clientid);

    $stripeSubtotal   = is_array($invoice) ? $invoice['subtotal'] : $invoice->subtotal;
    $stripeTotal      = is_array($invoice) ? $invoice['total'] : $invoice->total;
    $stripeTaxPercent = is_array($invoice) ? $invoice['tax_percent'] : $invoice->tax_percent;

    $new_invoice_data                    = [];
    $new_invoice_data['subscription_id'] = $subscription->id;
    $new_invoice_data['clientid']        = $subscription->clientid;
    $new_invoice_data['number']          = get_option('next_invoice_number');
    $new_invoice_data['date']            = _d(date('Y-m-d'));
    $new_invoice_data['duedate']         = null;

    $new_invoice_data['show_quantity_as'] = 1;
    $new_invoice_data['currency']         = $subscription->currency;

    $new_invoice_data['subtotal']         = strcasecmp($subscription->currency_name, 'JPY') == 0 ? $stripeSubtotal : $stripeSubtotal / 100;
    $new_invoice_data['total']            = strcasecmp($subscription->currency_name, 'JPY') == 0 ? $stripeSubtotal : $stripeTotal / 100;
    $new_invoice_data['adjustment']       = 0;
    $new_invoice_data['discount_percent'] = 0;
    $new_invoice_data['discount_total']   = 0;
    $new_invoice_data['discount_type']    = '';

    $new_invoice_data['terms']      = clear_textarea_breaks(get_option('predefined_terms_invoice'));
    $new_invoice_data['sale_agent'] = 0;

    $new_invoice_data['billing_street']           = clear_textarea_breaks($client->billing_street);
    $new_invoice_data['billing_city']             = $client->billing_city;
    $new_invoice_data['billing_state']            = $client->billing_state;
    $new_invoice_data['billing_zip']              = $client->billing_zip;
    $new_invoice_data['billing_country']          = $client->billing_country;
    $new_invoice_data['shipping_street']          = clear_textarea_breaks($client->shipping_street);
    $new_invoice_data['shipping_city']            = $client->shipping_city;
    $new_invoice_data['shipping_state']           = $client->shipping_state;
    $new_invoice_data['shipping_zip']             = $client->shipping_zip;
    $new_invoice_data['shipping_country']         = $client->shipping_country;
    $new_invoice_data['show_shipping_on_invoice'] = 0;
    // $new_invoice_data['include_shipping']         = 0;
    $new_invoice_data['status'] = 1;

    if (!empty($client->shipping_street)) {
        $new_invoice_data['show_shipping_on_invoice'] = 1;
        $new_invoice_data['include_shipping']         = 1;
    }

    $new_invoice_data['clientnote']            = clear_textarea_breaks(get_option('predefined_clientnote_invoice'));
    $new_invoice_data['adminnote']             = '';
    $new_invoice_data['allowed_payment_modes'] = ['stripe'];

    $new_invoice_data['newitems'] = [];
    $key                          = 1;
    $items                        = is_array($invoice) ? $invoice['lines']['data'] : $invoice->lines->data;
    $totalItems                   = count($items);
    foreach ($items as $item) {
        $descCheck1 = $item['quantity'] . ' × ';
        $descCheck2 = $item['quantity'] . ' x ';

        if (strpos($item['description'], $descCheck1) !== false) {
            $item['description'] = strbefore($item['description'], $descCheck1) . ' ' . strafter($item['description'], $descCheck1);
        } elseif (strpos($item['description'], $descCheck2) !== false) {
            $item['description'] = strbefore($item['description'], $descCheck2) . ' ' . strafter($item['description'], $descCheck2);
        }

        $item['description']                        = trim($item['description']);
        $new_invoice_data['newitems'][$key]['rate'] = strcasecmp($subscription->currency_name, 'JPY') == 0
        ? $item['amount'] / $item['quantity']
        : $item['amount'] / $item['quantity'] / 100;

        $new_invoice_data['newitems'][$key]['description']      = $item['description'];
        $new_invoice_data['newitems'][$key]['long_description'] = $subscription->description_in_item == 1 && $key == $totalItems
        ? $subscription->description
        : '';
        $new_invoice_data['newitems'][$key]['qty']     = $item['quantity'];
        $new_invoice_data['newitems'][$key]['unit']    = '';
        $new_invoice_data['newitems'][$key]['taxname'] = [];

        if (!empty($stripeTaxPercent)) {
            array_push($new_invoice_data['newitems'][$key]['taxname'], $subscription->tax_name . '|' . $stripeTaxPercent);
        }

        $new_invoice_data['newitems'][$key]['order'] = $key;
        $key++;
    }

    $new_invoice_data = hooks()->apply_filters('subscription_invoice_data', $new_invoice_data);

    return $new_invoice_data;
}

function subscription_invoice_preview_data($subscription, $upcomingInvoice = null, $stripeSubscription = null)
{
    define('INVOICE_PREVIEW_SUBSCRIPTION', true);
    $CI = &get_instance();

    if (!isset($upcomingInvoice)) {
        $upcomingInvoice = $CI->stripe_subscriptions->get_upcoming_invoice($subscription->stripe_subscription_id);
    }

    $newInvoiceData = create_subscription_invoice_data($subscription, $upcomingInvoice);

    $itemsArray = $newInvoiceData['newitems'];
    $itemsArray = array_values($itemsArray);

    foreach ($itemsArray as $key => $item) {
        $itemsArray[$key]['id']       = 0;
        $itemsArray[$key]['rel_id']   = 0;
        $itemsArray[$key]['rel_type'] = 'invoice';

        if (isset($item['taxname']) && is_array($item['taxname'])) {
            foreach ($item['taxname'] as $keyTax => $tax) {
                $taxArray                                        = explode('|', $tax);
                $itemsArray[$key]['taxname'][$keyTax]            = [];
                $itemsArray[$key]['taxname'][$keyTax]['taxname'] = $tax; // NAME|PERCENT
                $itemsArray[$key]['taxname'][$keyTax]['taxrate'] = $taxArray[1];
            }
        }
    }

    $upcomingInvoice = create_subscription_invoice_data($subscription, $upcomingInvoice);
    $upcomingInvoice = array_to_object($upcomingInvoice);

    $upcomingInvoice->items = $itemsArray;

    // Fake data
    if (isset($stripeSubscription->current_period_end)) {
        $date                  = date('Y-m-d', $stripeSubscription->current_period_end);
        $upcomingInvoice->date = _d($date);

        if (get_option('invoice_due_after') != 0) {
            $upcomingInvoice->duedate = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime($date))));
        }
    }
    $upcomingInvoice->client      = $CI->clients_model->get($subscription->clientid);
    $upcomingInvoice->id          = 0;
    $upcomingInvoice->payments    = [];
    $upcomingInvoice->attachments = [];
    $upcomingInvoice->status      = 2;

    $upcomingInvoice->project_id = $subscription->project_id;
    if ($subscription->project_id != 0) {
        if (class_exists('projects_model')) {
            $CI->load->model('projects_model');
        }
        $upcomingInvoice->project_data = $CI->projects_model->get($subscription->project_id);
    }
    $upcomingInvoice->sale_agent        = 0;
    $upcomingInvoice->total_left_to_pay = $upcomingInvoice->total;
    $upcomingInvoice->discount_percent  = 0;
    $upcomingInvoice->discount_total    = 0;
    $upcomingInvoice->recurring         = 0;
    $upcomingInvoice->is_recurring_from = null;

    $currency                       = get_currency($subscription->currency);
    $upcomingInvoice->symbol        = $currency->symbol;
    $upcomingInvoice->currency_name = $currency->name;

    $GLOBALS['items_preview_transaction'] = $upcomingInvoice;

    return $upcomingInvoice;
}

function get_subscriptions_statuses()
{
    return hooks()->apply_filters('subscription_statuses', [
        [
            'color'          => '#84c529',
            'id'             => 'active',
            'filter_default' => true,
        ],
        [
            'color'          => '#84c529',
            'id'             => 'future',
            'filter_default' => true,
        ],
        [
            'color'          => '#ff6f00',
            'id'             => 'past_due',
            'filter_default' => true,
        ],
        [
            'color'          => '#fc2d42',
            'id'             => 'unpaid',
            'filter_default' => true,
        ],
        [
            'color'          => '#ff6f00',
            'id'             => 'incomplete',
            'filter_default' => true,
        ],
        [
            'color'          => '#777',
            'id'             => 'canceled',
            'filter_default' => false,
        ],
        [
            'color'          => '#777',
            'id'             => 'incomplete_expired',
            'filter_default' => false,
        ],
    ]);
}

function subscriptions_summary()
{
    $statuses            = get_subscriptions_statuses();
    $has_permission_view = has_permission('subscriptions', '', 'view');
    $summary             = [];
    foreach ($statuses as $status) {
        $where = ['status' => $status['id']];
        if (!has_permission('subscriptions', '', 'view')) {
            $where['created_from'] = get_staff_user_id();
        }
        $summary[] = [
            'total' => total_rows(db_prefix() . 'subscriptions', $where),
            'color' => $status['color'],
            'id'    => $status['id'],
        ];
    }

    array_unshift($summary, [
        'total' => total_rows(db_prefix() . 'subscriptions', 'date_subscribed IS NULL' . (!$has_permission_view ? ' AND created_from =' . get_staff_user_id() . '' : '')),
        'color' => '#03a9f4',
        'id'    => 'not_subscribed',
    ]);

    return $summary;
}

function send_email_customer_subscribed_to_subscription_to_staff($subscription)
{
    $CI = &get_instance();

    $CI->db->where('(staffid=' . $subscription->created_from . ' OR admin=1)');
    $CI->db->where('active', 1);
    $members = $CI->db->get(db_prefix() . 'staff')->result_array();

    foreach ($members as $staff) {
        send_mail_template('subscription_customer_subscribed_to_staff', $subscription, $staff['email']);
    }
}

function can_logged_in_contact_view_subscriptions()
{
    if (!is_client_logged_in()) {
        return false;
    }

    return get_option('show_subscriptions_in_customers_area') == '1'
    && $GLOBALS['contact']->is_primary == '1'
    && customer_has_subscriptions($GLOBALS['contact']->userid);
}

function can_logged_in_contact_update_credit_card()
{
    if (!is_client_logged_in()) {
        return false;
    }

    $stripeOption = get_instance()->stripe_gateway->getSetting('allow_primary_contact_to_update_credit_card');

    return $GLOBALS['contact']->is_primary == '1'
                        && !empty($GLOBALS['client']->stripe_id)
                        && $stripeOption == '1';
}

function customer_can_delete_credit_card($client_id = null)
{
    $client_id = $client_id === null ? get_client_user_id() : $client_id;

    if (total_rows('subscriptions', "clientid={$client_id} AND status != 'canceled' AND status != 'incomplete_expired' AND status IS NOT NULL") > 0) {
        return false;
    }

    return true;
}


function is_future_subscription_date($date)
{
    $anchor = strtotime($date);

    if ($date <= date('Y-m-d')) {
        return false;
    }

    return true;
}


function redirect_to_stripe_checkout($session_id)
{
    $CI = &get_instance();

    if (!class_exists('stripe_core', false)) {
        $CI->load->library('stripe_core');
    }

    echo '<script src="https://js.stripe.com/v3/"></script>';

    echo '<script>
        var stripe = Stripe("' . $CI->stripe_core->get_publishable_key() . '");
            stripe.redirectToCheckout({
                sessionId: "' . $session_id . '"
            }).then(function (result) {});
    </script>';
}

function check_stripe_subscription_environment($subscription)
{
    if ($subscription->in_test_environment === '1' && !get_instance()->stripe_gateway->is_test()) {
        echo '<h2>This subscription was created in test environment, now the system has switched to live environment, hence, cannot be viewed.</h4>';
        if (staff_can('delete', 'subscriptions')) {
            echo '<h3>Feel free to delete the subscription from the system.</h4>';
        }
        die;
    }
}
