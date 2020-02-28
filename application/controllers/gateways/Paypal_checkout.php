<?php

defined('BASEPATH') or exit('No direct script access allowed');

use PayPalCheckoutSdk\Orders\OrdersGetRequest;

class Paypal_checkout extends App_Controller
{
    public function complete($id, $hash)
    {
        check_invoice_restrictions($id, $hash);

        $client = $this->paypal_checkout_gateway->client();

        $orderId = $this->input->post('orderID');

        try {
            $response      = $client->execute(new OrdersGetRequest($orderId));
            $transactionid = $response->result->purchase_units[0]->payments->captures[0]->id;
            if ($response->result->status == 'COMPLETED') {
                if (total_rows(db_prefix() . 'invoicepaymentrecords', [
                    'transactionid' => $transactionid,
                    'paymentmode' => $this->paypal_checkout_gateway->getId(),
                ]) === 0) {
                    $success = $this->paypal_checkout_gateway->addPayment(
                          [
                            'amount'        => $response->result->purchase_units[0]->amount->value,
                            'invoiceid'     => $id,
                            'transactionid' => $response->result->purchase_units[0]->payments->captures[0]->id,
                        ]
                    );

                    set_alert('success', _l($success ? 'online_payment_recorded_success' : 'online_payment_recorded_success_fail_database'));
                } else {
                    set_alert('warning', 'This transaction/order is already stored in database.');
                }
            }
        } catch (Exception $e) {
            $messageJSON   = $e->getMessage();
            $messageJSON   = json_decode($messageJSON);
            $error_message = false;
            if (isset($messageJSON->error_description)) {
                $error_message = '[' . $messageJSON->error . '] ' . $messageJSON->error_description;
                if ($messageJSON->error == 'invalid_client') {
                    $error_message .= ' - Make sure that you are not using production credentials and have test mode enabled.';
                }
            } elseif (isset($messageJSON->details[0]->description)) {
                $error_message = $messageJSON->details[0]->description;
            }
            if ($error_message) {
                set_alert('warning', $error_message);
            }
        }
    }

    public function payment($id, $hash)
    {
        check_invoice_restrictions($id, $hash);

        $this->load->model('invoices_model');

        $invoice = $this->invoices_model->get($id);

        $language        = load_client_language($invoice->clientid);
        $data['invoice'] = $invoice;

        $data['total']            = $this->input->get('total');
        $data['paypal_client_id'] = $this->paypal_checkout_gateway->getSetting('client_id');
        $data['button_style']     = json_encode($this->paypal_checkout_gateway->get_styling_button_params());
        $data['order']            = $this->paypal_checkout_gateway->get_order_create_data($invoice, $data['total']);

        echo $this->get_view($data);
    }

    public function get_view($data = [])
    {
        ?>
        <?php echo payment_gateway_head(_l('payment_for_invoice') . ' ' . format_invoice_number($data['invoice']->id)); ?>
        <body class="gateway-paypal-checkout">
            <div class="container">
                <div class="col-md-8 col-md-offset-2 mtop30">
                  <div class="mbot30 text-center">
                      <?php echo payment_gateway_logo(); ?>
                  </div>
                  <div class="row">
                    <div class="panel_s">
                        <div class="panel-body">
                         <h3 class="no-margin">
                          <b><?php echo _l('payment_for_invoice'); ?></b>
                          <a href="<?php echo site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash); ?>">
                              <b>
                                <?php echo format_invoice_number($data['invoice']->id); ?>
                              </b>
                          </a>
                      </h3>
                      <h4><?php echo _l('payment_total', app_format_money($data['total'], $data['invoice']->currency_name)); ?></h4>
                    <hr />
                    <div class="row">
                        <div class="col-md-6 col-md-offset-3">
                            <div id="paypal-button-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php echo payment_gateway_scripts(); ?>
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo $data['paypal_client_id']; ?>&currency=<?php echo $data['invoice']->currency_name; ?>"></script>
    <script>
      paypal.Buttons({
            style: <?php echo $data['button_style']; ?>,
            createOrder: function(data, actions) {
                return actions.order.create(<?php echo json_encode($data['order']); ?>);
            },
            onApprove: function(data, actions) {
                     var completeURL = '<?php echo site_url('gateways/paypal_checkout/complete/' . $data['invoice']->id . '/' . $data['invoice']->hash); ?>';
                      // Capture the funds from the transaction
                      return actions.order.capture().then(function(details) {
                        $.post(completeURL, {
                            orderID:data.orderID,
                        }).done(function(response){
                           window.location.href = '<?php echo site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash); ?>';
                       });
                    });
                  }
              }).render('#paypal-button-container');
          </script>
          <?php echo payment_gateway_footer();
    }
}
