<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<table class="table dt-table table-estimates" data-order-col="1" data-order-type="desc">
    <thead>
        <tr>
            <th class="th-estimate-number"><?php echo _l('clients_estimate_dt_number'); ?></th>
            <th class="th-estimate-date"><?php echo _l('clients_estimate_dt_date'); ?></th>
            <th class="th-estimate-duedate"><?php echo _l('clients_estimate_dt_duedate'); ?></th>
            <th class="th-estimate-amount"><?php echo _l('clients_estimate_dt_amount'); ?></th>
            <th class="th-estimate-reference-number"><?php echo _l('reference_no'); ?></th>
            <th class="th-estimate-status"><?php echo _l('clients_estimate_dt_status'); ?></th>
            <?php
            $custom_fields = get_custom_fields('estimate',array('show_on_client_portal'=>1));
            foreach($custom_fields as $field){ ?>
                <th><?php echo $field['name']; ?></th>
            <?php } ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach($estimates as $estimate){ ?>
            <tr>
                <td data-order="<?php echo $estimate['number']; ?>"><a href="<?php echo site_url('estimate/' . $estimate['id'] . '/' . $estimate['hash']); ?>" class="estimate-number"><?php echo format_estimate_number($estimate['id']); ?></a>
                    <?php
                    if($estimate['invoiceid']){
                        echo '<br /><span class="text-success">' . _l('estimate_invoiced') . '</span>';
                    }
                    ?>
                </td>
                <td data-order="<?php echo $estimate['date']; ?>"><?php echo _d($estimate['date']); ?></td>
                <td data-order="<?php echo $estimate['expirydate']; ?>"><?php echo _d($estimate['expirydate']); ?></td>
                <td data-order="<?php echo $estimate['total']; ?>"><?php echo app_format_money($estimate['total'], $estimate['currency_name']);; ?></td>
                <td><?php echo $estimate['reference_no']; ?></td>
                <td><?php echo format_estimate_status($estimate['status'], 'inline-block', true); ?></td>
                <?php foreach($custom_fields as $field){ ?>
                    <td><?php echo get_custom_field_value($estimate['id'],$field['id'],'estimate'); ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
    </tbody>
</table>
