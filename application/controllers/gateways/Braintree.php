<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Braintree extends App_Controller
{
    public function complete_purchase($invoice_id, $invoice_hash)
    {
        if ($this->input->post()) {
            check_invoice_restrictions($invoice_id, $invoice_hash);

            $data = $this->input->post();

            $this->load->model('invoices_model');
            $invoice = $this->invoices_model->get($invoice_id);

            load_client_language($invoice->clientid);
            $data['currency'] = $invoice->currency_name;

            $oResponse = $this->paypal_braintree_gateway->finish_payment($data);

            if ($oResponse->isSuccessful()) {
                $transactionid   = $oResponse->getTransactionReference();
                $paymentResponse = $this->paypal_braintree_gateway->fetch_payment($transactionid);
                $paymentData     = $paymentResponse->getData();

                $success = $this->paypal_braintree_gateway->addPayment(
                  [
                        'amount'        => $data['amount'],
                        'invoiceid'     => $invoice->id,
                        'paymentmethod' => $paymentData->paymentInstrumentType,
                        'transactionid' => $transactionid,
                  ]
                );

                set_alert($success ? 'success' : 'danger', _l($success ? 'online_payment_recorded_success' : 'online_payment_recorded_success_fail_database'));
            } else {
                set_alert('danger', $oResponse->getMessage());
            }
        }
    }

    public function make_payment()
    {
        check_invoice_restrictions($this->input->get('invoiceid'), $this->input->get('hash'));
        $this->load->model('invoices_model');
        $invoice = $this->invoices_model->get($this->input->get('invoiceid'));
        load_client_language($invoice->clientid);
        $data['invoice']      = $invoice;
        $data['total']        = $this->input->get('total');
        $data['client_token'] = $this->paypal_braintree_gateway->generate_token();
        echo $this->get_view($data);
    }
    public function get_view($data = [])
    { ?>
  <?php echo payment_gateway_head(_l('payment_for_invoice') . ' ' . format_invoice_number($data['invoice']->id)); ?>
  <body class="gateway-braintree">
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
                <b><?php echo format_invoice_number($data['invoice']->id); ?></b>
              </a>
            </h3>
            <h4><?php echo _l('payment_total', app_format_money($data['total'], $data['invoice']->currency_name)); ?></h4>
            <hr />
              <div class="bt-drop-in-wrapper">
                  <div id="bt-dropin"></div>
              </div>
              <div class="text-center" style="margin-top:15px;">
                  <button class="btn btn-info" type="button" id="submit-button" style="display:none;">
                    <?php echo _l('submit_payment'); ?>
                  </button>
              </div>
          </div>
        </div>
      </div>
    </div>
    <script src="https://js.braintreegateway.com/web/dropin/1.11.0/js/dropin.min.js"></script>
    <?php echo payment_gateway_scripts(); ?>
    <script>

      var invoiceUrl = '<?php echo site_url('invoice/'.$data['invoice']->id.'/'. $data['invoice']->hash); ?>';
      var completePaymentUrl = '<?php echo site_url('gateways/braintree/complete_purchase/'.$data['invoice']->id.'/'. $data['invoice']->hash); ?>';
      var amount = <?php echo number_format($data['total'], 2, '.', ''); ?>;
      var currencyName = "<?php echo $data['invoice']->currency_name; ?>";
      var clientToken = "<?php echo $data['client_token']; ?>";
      var button = document.querySelector('#submit-button');
      var locale = '';
      var paypalEnabled = "<?php echo $this->paypal_braintree_gateway->getSetting('paypal_enabled'); ?>";

      if(typeof(window.navigator.language) != 'undefined') {
          locale = window.navigator.language;
          locale = locale.replace('-','_');
      }

     var dropInOptions = {
        authorization: clientToken,
        container: '#bt-dropin',
        locale: locale,
      };
      if(paypalEnabled == '1') {
          dropInOptions.paypal = {
              flow: 'checkout',
              amount: amount,
              currency: currencyName
          };
      }

     braintree.dropin.create(dropInOptions, function (createErr, instance) {

      button.addEventListener('click', function () {

        instance.requestPaymentMethod(function (requestPaymentMethodErr, payload) {

          if(payload) {
                button.disabled = true;
                button.innerHTML = "<?php echo _l('wait_text'); ?>";

                $.post(completePaymentUrl, {
                  amount: amount,
                  payment_method_nonce:payload.nonce,
                }).done(function(){
                  window.location.href = invoiceUrl;
                });
          }
        });
      });

      instance.on('paymentMethodRequestable', function(){
        button.style.display = '';
      });

      instance.on('noPaymentMethodRequestable', function(){
        button.style.display = 'none';
      });
    });
    </script>
    <?php echo payment_gateway_footer();
    }
}
