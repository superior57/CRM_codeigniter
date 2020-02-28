<?php

defined('BASEPATH') or exit('No direct script access allowed');

abstract class App_items_table_template
{
    /**
     * Codeigniter instance
     * @var object
     */
    protected $ci;

    /**
     * The transaction items
     * @var array
     */
    protected $items = [];

    /**
     * Invoice, estimate
     * @var object
     */
    protected $transaction;

    /**
     * Whether tax per item should be shown
     * @var boolean
     */
    protected $tax_per_item;

    /**
     * Custom fields for the items
     * @var array
     */
    protected $custom_fields_for_table = [];

    /**
     * All taxes used for the preview
     * This is used to display the taxes on the bottom of the invoice
     * @var array
     */
    protected $taxes = [];

    /**
     * HTML table classes
     * @var array
     */
    private $table_class = [
        'table',
        'items',
        'items-preview',
    ];

    /**
     * Where the items will be shown? pdf or html
     * @var string
     */
    protected $for = 'html';

    /**
     * Preview type, e.q. invoice, estimate etc...
     * @var string
     */
    protected $type;

    /**
     * Is the preview for admin area?
     * @var boolean
     */
    protected $admin_preview = false;

    /**
     * Headings language texts
     * @var array
     */
    protected $headings = [
        'number' => '',
        'item'   => '',
        'qty'    => '',
        'rate'   => '',
        'tax'    => '',
        'amount' => '',
    ];

    /**
     * Helper property to use when initializing the taxes
     * @var array
     */
    private $calculated_taxes = [];

    public function __construct()
    {
        $this->ci                      = &get_instance();
        $this->tax_per_item            = get_option('show_tax_per_item') == 1;
        $this->custom_fields_for_table = get_items_custom_fields_for_table_html($this->transaction->id, $this->type);
        $this->set_headings();
    }

    /**
    * Builds the actual table items rows preview
    * @return string
    */
    abstract public function items();

    /**
    * Html headings preview
    * @return string
    */
    abstract public function html_headings();

    /**
     * PDF headings preview
     * @return string
     */
    abstract public function pdf_headings();

    /**
     * All taxes used for the preview
     * This is used to display the taxes on the bottom of the invoice
     * @var array
     */
    public function taxes()
    {
        foreach ($this->taxes as $tax) {
            $total_tax = array_sum($tax['total']);
            if (isset($this->transaction->discount_percent) && $this->transaction->discount_percent != 0
                && isset($this->transaction->discount_type) && $this->transaction->discount_type == 'before_tax') {
                $total_tax_tax_calculated = ($total_tax * $this->transaction->discount_percent) / 100;
                $total_tax                = ($total_tax - $total_tax_tax_calculated);
            } elseif (isset($this->transaction->discount_total) && $this->transaction->discount_total != 0
                && isset($this->transaction->discount_type) && $this->transaction->discount_type == 'before_tax') {
                $t         = ($this->transaction->discount_total / $this->transaction->subtotal) * 100;
                $total_tax = ($total_tax - $total_tax * $t / 100);
            }

            $this->taxes[$tax['tax_name']]['total_tax'] = $total_tax;
            // Tax name is in format NAME|PERCENT
            $tax_name_array                           = explode('|', $tax['tax_name']);
            $this->taxes[$tax['tax_name']]['taxname'] = $tax_name_array[0];
        }

        return $this->order_taxes($this->taxes);
    }

    /**
     * Order taxes by taxrate
     * Lowest tax rate will be on top (if multiple)
     * @param  array $taxes
     * @return array
     */
    protected function order_taxes($taxes)
    {
        usort($taxes, function ($a, $b) {
            return $a['taxrate'] - $b['taxrate'];
        });

        return $taxes;
    }

    /**
     * Get specific item applied taxes
     * @param  array $item
     * @return mixed
     */
    protected function get_item_taxes($item)
    {
        $item_taxes = [];

        if (defined('INVOICE_PREVIEW_SUBSCRIPTION')) {
            $item_taxes = $item['taxname'];
        } else {

            // Separate functions exists to get item taxes for Invoice, Estimate, Proposal, Credit Note
            $func_taxes = 'get_' . $this->type . '_item_taxes';

            if (function_exists($func_taxes)) {
                $item_taxes = call_user_func($func_taxes, $item['id']);
            }
        }

        return $item_taxes;
    }

    /**
     * Helper method for taxes HTML, because is commonly used for all preview types
     * @param  array $item
     * @return string
     */
    protected function taxes_html($item)
    {
        $itemHTML = '';

        if ($this->show_tax_per_item()) {
            $itemHTML .= '<td align="right">';
            if (count($item['taxes']) > 0) {
                foreach ($item['taxes'] as $tax) {
                    $item_tax = '';
                    if ((count($item['taxes']) > 1 && get_option('remove_tax_name_from_item_table') == false) || get_option('remove_tax_name_from_item_table') == false || multiple_taxes_found_for_item($item['taxes'])) {
                        $tmp      = explode('|', $tax['taxname']);
                        $item_tax = $tmp[0] . ' ' . app_format_number($tmp[1]) . '%<br />';
                    } else {
                        $item_tax .= app_format_number($tax['taxrate']) . '%';
                    }

                    $itemHTML .= hooks()->apply_filters('item_tax_table_row', $item_tax, $item);
                }
            } else {
                $itemHTML .= hooks()->apply_filters('item_tax_table_row', '0%', $item);
            }
            $itemHTML .= '</td>';
        }

        return $itemHTML;
    }

    /**
     * Set the initial items to work with
     * This function also sets the taxes which is required
     * @param array $items
     */
    protected function set_items($items)
    {
        foreach ($items as $key => $item) {
            $items[$key]['taxes'] = $this->get_item_taxes($item);
            $this->set_all_taxes($items[$key]['taxes'], $item);
        }

        $this->items = $items;
    }

    /**
     * Table items row attributes
     * @param  array $item
     * @return string
     */
    protected function tr_attributes($item)
    {
        $trAttributes = '';
        if ($this->admin_preview == true) {
            $trAttributes = ' class="sortable" data-item-id="' . $item['id'] . '"';
        }

        if ($font_size = $this->get_pdf_font_size()) {
            $trAttributes .= ' style="font-size:' . $font_size . 'px;"';
        }

        return $trAttributes;
    }

    /**
     * Table items table data attributes
     * @return string
     */
    protected function td_attributes()
    {
        $tdAttributes = '';

        if ($this->admin_preview == true) {
            $tdAttributes = ' class="dragger item_no"';
        }


        return $tdAttributes;
    }

    /**
     * Get PDF font size if the items are for PDF
     * @return mixed
     */
    protected function get_pdf_font_size()
    {
        if ($this->for === 'pdf') {
            $font_size = get_option('pdf_font_size');
            if ($font_size == '') {
                $font_size = 10;
            }

            return $font_size + 4;
        }

        return '';
    }

    /**
     * Sets all taxes
     * @param array $taxes  $item taxes
     * @param array $item
     */
    protected function set_all_taxes($taxes, $item)
    {
        foreach ($taxes as $tax) {
            $taxNotCalculated = false;

            if (!in_array($tax['taxname'], $this->calculated_taxes)) {
                array_push($this->calculated_taxes, $tax['taxname']);
                $taxNotCalculated = true;
            }
            if ($taxNotCalculated == true) {
                $this->taxes[$tax['taxname']]          = [];
                $this->taxes[$tax['taxname']]['total'] = [];
                array_push($this->taxes[$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                $this->taxes[$tax['taxname']]['tax_name'] = $tax['taxname'];
                $this->taxes[$tax['taxname']]['taxrate']  = $tax['taxrate'];
            } else {
                array_push($this->taxes[$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
            }
        }
    }

    /**
     * Get items custom fields applied on the transaction
     * @return array
     */
    protected function get_custom_fields_for_table()
    {
        return $this->custom_fields_for_table;
    }

    /**
     * Set transaction for the items
     * @param object $rel
     */
    protected function set_transaction($rel)
    {
        // No relation data on preview becuase taxes are not saved in database
        $this->transaction = !defined('INVOICE_PREVIEW_SUBSCRIPTION') ? $rel : $GLOBALS['items_preview_transaction'];

        return $this;
    }

    /**
     * Helper function to build the whole table
     * @return string
     */
    public function table()
    {
        $html = $this->{$this->for . '_table_open'}();
        if ($this->for == 'html') {
            $html .= '<thead>';
        }
        $html .= $this->{$this->for . '_headings'}();
        if ($this->for == 'html') {
            $html .= '</thead>';
        }
        $html .= '<tbody>';
        $html .= $this->items();
        $html .= '</tbody>';
        $html .= $this->table_close();

        return $html;
    }

    /**
     * PDF table opening tag
     * @return string
     */
    public function pdf_table_open()
    {
        return '<table width="100%" bgcolor="#fff" cellspacing="0" cellpadding="8">';
    }

    /**
     * HTML table opening tag
     * @return string
     */
    public function html_table_open()
    {
        $table_class   = array_unique($this->table_class);
        $table_class[] = $this->type . '-items-preview';

        return '<table class="' . implode(' ', $table_class) . '" data-type="' . $this->type . '">';
    }

    /**
     * Add additional table class
     * Only for HTML table
     * @param string $class
     */
    public function add_table_class($class)
    {
        $this->table_class[] = $class;

        return $this;
    }

    /**
     * Closing table tag
     * @return string
     */
    public function table_close()
    {
        return '</table>';
    }

    /**
     * Get number heading
     * @return string
     */
    public function number_heading()
    {
        return $this->headings['number'];
    }

    /**
     * Get item heading
     * @return string
     */
    public function item_heading()
    {
        return $this->headings['item'];
    }

    /**
     * Get quantity heading
     * @return string
     */
    public function qty_heading()
    {
        return $this->headings['qty'];
    }

    /**
     * Get rate heading
     * @return string
     */
    public function rate_heading()
    {
        return $this->headings['rate'];
    }

    /**
     * Get tax heading
     * @return string
     */
    public function tax_heading()
    {
        return $this->headings['tax'];
    }

    /**
     * Get amount heading
     * @return string
     */
    public function amount_heading()
    {
        return $this->headings['amount'];
    }

    /**
     * Set headings for the items
     * Can be used outside this class for example when alias is needed to take the language texts for
     * @param string $alias e.q. estimates and proposals are using the same language text
     */
    public function set_headings($alias = '')
    {
        $langFrom = !$alias ? $this->type : $alias;

        $this->headings['number'] = _l('the_number_sign', '', false);
        $this->headings['item']   = _l($langFrom . '_table_item_heading', '', false);

        $qty_heading = _l($langFrom . '_table_quantity_heading', '', false);

        if (isset($this->transaction->show_quantity_as)) {
            if ($this->transaction->show_quantity_as == 2) {
                $qty_heading = _l($langFrom . '_table_hours_heading', '', false);
            } elseif ($this->transaction->show_quantity_as == 3) {
                $qty_heading = _l($langFrom . '_table_quantity_heading', '', false) . '/' . _l($langFrom . '_table_hours_heading', '', false);
            }
        }

        $this->headings['qty']    = $qty_heading;
        $this->headings['rate']   = _l($langFrom . '_table_rate_heading', '', false);
        $this->headings['tax']    = _l($langFrom . '_table_tax_heading', '', false);
        $this->headings['amount'] = _l($langFrom . '_table_amount_heading', '', false);

        return $this;
    }

    protected function exclude_currency()
    {
        return hooks()->apply_filters('items_table_amounts_exclude_currency_symbol', true, [
            'type'        => $this->type,
            'transaction' => $this->transaction,
        ]);
    }

    protected function show_tax_per_item()
    {
        return $this->tax_per_item && hooks()->apply_filters('show_tax_per_item', true, [
            'type'        => $this->type,
            'transaction' => $this->transaction,
        ]);
    }

    /**
     * Custom __call magic method
     * @param  string $name      the called non existing method
     * @param  array $arguments the arguments passed
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (startsWith($name, 'set_') && endsWith($name, '_heading')) {
            $arr                     = explode('_', $name);
            $this->headings[$arr[1]] = $arguments[0];

            return $this;
        }
    }
}
