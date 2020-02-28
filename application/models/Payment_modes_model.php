<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Payment_modes_model extends App_Model
{
    /**
     * @deprecated 2.3.4
     * @see gateways
     * @var array
     */
    private $payment_gateways = [];

    /**
     * New variable because the app_payment_gateways hook is moved in the method get_payment_gateways and the gateways be duplicated
     * After the deprecated filters are removed and access to $payment_gateways is removed, this should work fine.
     * @since 2.3.4
     * @var array
     */
    private $gateways = null;

    public function __construct()
    {
        parent::__construct();

        /**
         * @deprecated 2.3.0 use app_payment_gateways
         * @var array
         */

        $this->payment_gateways = apply_filters_deprecated('before_add_online_payment_modes', [[]], '2.3.0', 'app_payment_gateways');

        /**
         * @deprecated 2.3.2 use app_payment_gateways
         * @var array
         */
        $this->payment_gateways = apply_filters_deprecated('before_add_payment_gateways', [$this->payment_gateways], '2.3.0', 'app_payment_gateways');
    }

    /**
     * Get payment mode
     * @param  string  $id    payment mode id
     * @param  array   $where additional where only for offline modes
     * @param  boolean $include_inactive   whether to include inactive too
     * @param  boolean $force force if it's inactive to return it back
     * @return array
     */
    public function get($id = '', $where = [], $include_inactive = false, $force = false)
    {
        $this->db->where($where);

        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'payment_modes')->row();
        } elseif (!empty($id)) {
            foreach ($this->get_payment_gateways(true) as $gateway) {
                if ($gateway['id'] == $id) {

                    if ($gateway['active'] == 0 && $force == false) {
                        continue;
                    }

                    // The instance is already object and array_to_object is messing up
                    $instance = $gateway['instance'];
                    unset($gateway['instance']);

                    $mode = array_to_object($gateway);

                    // Add again the instance
                    $mode->instance    = $instance;
                    $mode->show_on_pdf = 0;

                    return $mode;
                }
            }

            return false;
        }

        if ($include_inactive !== true) {
            $this->db->where('active', 1);
        }

        $modes = $this->db->get(db_prefix() . 'payment_modes')->result_array();
        $modes = array_merge($modes, $this->get_payment_gateways($include_inactive));

        return $modes;
    }

    /**
     * Add new payment mode
     * @param array $data payment mode $_POST data
     */
    public function add($data)
    {
        if (isset($data['id'])) {
            unset($data['id']);
        }

        foreach (['active', 'show_on_pdf', 'selected_by_default', 'invoices_only', 'expenses_only'] as $check) {
            $data[$check] = !isset($data[$check]) ? 0 : 1;
        }

        $this->db->insert(db_prefix() . 'payment_modes', [
            'name'                => $data['name'],
            'description'         => nl2br_save_html($data['description']),
            'active'              => $data['active'],
            'expenses_only'       => $data['expenses_only'],
            'invoices_only'       => $data['invoices_only'],
            'show_on_pdf'         => $data['show_on_pdf'],
            'selected_by_default' => $data['selected_by_default'],
        ]);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Payment Mode Added [ID: ' . $insert_id . ', Name:' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Update payment mode
     * @param  array $data payment mode $_POST data
     * @return boolean
     */
    public function edit($data)
    {
        $id = $data['paymentmodeid'];

        unset($data['paymentmodeid']);

        foreach (['active', 'show_on_pdf', 'selected_by_default', 'invoices_only', 'expenses_only'] as $check) {
            $data[$check] = !isset($data[$check]) ? 0 : 1;
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'payment_modes', [
            'name'                => $data['name'],
            'description'         => nl2br_save_html($data['description']),
            'active'              => $data['active'],
            'expenses_only'       => $data['expenses_only'],
            'invoices_only'       => $data['invoices_only'],
            'show_on_pdf'         => $data['show_on_pdf'],
            'selected_by_default' => $data['selected_by_default'],
        ]);

        if ($this->db->affected_rows() > 0) {
            log_activity('Payment Mode Updated [ID: ' . $id . ', Name:' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete payment mode from database
     * @param  mixed $id payment mode id
     * @return mixed / if referenced array else boolean
     */
    public function delete($id)
    {
        // Check if the payment mode is using in the invoiec payment records table.
        if (is_reference_in_table('paymentmode', db_prefix() . 'invoicepaymentrecords', $id)
            || is_reference_in_table('paymentmode', db_prefix() . 'expenses', $id)) {
            return [
                'referenced' => true,
            ];
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'payment_modes');
        if ($this->db->affected_rows() > 0) {
            log_activity('Payment Mode Deleted [' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * @since  2.3.0
     * Get payment gateways
     * @param  boolean $includeInactive whether to include the inactive ones too
     * @return array
     */
    public function get_payment_gateways($includeInactive = false)
    {
        if (is_null($this->gateways)) {

            /**
             * Used for autoloading the payment gateways in App_gateway
             * @since  2.3.4
             */
            hooks()->do_action('before_get_payment_gateways');

            /**
              * Moved here in 2.3.4
              * When remove $this->payment_gateways, change filter parameter below $this->payment_gateways to empty array ([])
              * @since 2.3.2
              * @var array
            */
            $this->gateways = hooks()->apply_filters('app_payment_gateways', $this->payment_gateways);
        }

        $modes = [];
        foreach ($this->gateways as $mode) {
            if ($includeInactive !== true && $mode['active'] == 0) {
                continue;
            }

            // The the gateways unique in case duplicate ID's are found.
            if (!value_exists_in_array_by_key($modes, 'id', $mode['id'])) {
                $modes[] = $mode;
            } else {
                if (ENVIRONMENT != 'production') {
                    trigger_error(sprintf('Payment Gateway ID "%1$s" already exists, ignoring duplicate gateway ID...', $mode['id']));
                }
            }
        }

        return $modes;
    }

    /**
     * Get all online payment modes
     * @deprecated 2.3.0 use get_payment_gateways instead
     * @since   1.0.1
     * @return array payment modes
     */
    public function get_online_payment_modes($all = false)
    {
        return $this->get_payment_gateways($all);
    }

    /**
     * @since  Version 1.0.1
     * @param  integer ID
     * @param  integer Status ID
     * @return boolean
     * Update payment mode status Active/Inactive
     */
    public function change_payment_mode_status($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'payment_modes', [
            'active' => $status,
        ]);

        if ($this->db->affected_rows() > 0) {
            log_activity('Payment Mode Status Changed [ModeID: ' . $id . ' Status(Active/Inactive): ' . $status . ']');

            return true;
        }

        return false;
    }

    /**
     * @since  Version 1.0.1
     * @param  integer ID
     * @param  integer Status ID
     * @return boolean
     * Update payment mode show to client Active/Inactive
     */
    public function change_payment_mode_show_to_client_status($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'payment_modes', [
            'showtoclient' => $status,
        ]);

        if ($this->db->affected_rows() > 0) {
            log_activity('Payment Mode Show to Client Changed [ModeID: ' . $id . ' Status(Active/Inactive): ' . $status . ']');

            return true;
        }

        return false;
    }

    /**
     * Inject custom payment gateway into the payment gateways array
     * @param string $gateway_name payment gateway name, should equal like the libraries/classname
     * @param string $module       module name to load the gateway if not already loaded
     */
    public function add_payment_gateway($gateway, $module = null)
    {
        if (is_string($gateway)) {
            $gateway = strtolower($gateway);

            // Perhaps is in subfolder e.q. gateways/Example_gateway?
            $basename = basename($gateway);

            if (!$this->load->is_loaded($basename) && $module) {
                $this->load->library($module . '/' . $gateway);
            }

            $class = $this->{$basename};
        } else {
            // register_payment_gateway(new Example_gateway(), '[module_name]');
            $class = $gateway;
            $name  = get_class($class);

            if (!$class instanceof App_gateway) {
                throw new \Exception($name . ' must be an instance of "App_gateway"');
            }
        }

        if (hooks()->has_filter('app_payment_gateways', [ $class, 'initMode']) === false) {
            hooks()->add_filter('app_payment_gateways', [$class, 'initMode']);
        }
    }
}
