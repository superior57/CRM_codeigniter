<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Stripe Credit Cards UPDATE
 */
?>
<div class="panel_s section-heading section-credit-card">
    <div class="panel-body">
        <h4 class="no-margin section-text"><?php echo _l('update_credit_card'); ?></h4>
    </div>
</div>
<div class="panel_s">
    <div class="panel-body credit-card">
        <?php if(!empty($payment_method)){ ?>
            <h4><?php echo _l('credit_card_update_info'); ?></h4>
            <a href="<?php echo site_url('clients/update_credit_card'); ?>" class="btn btn-info">
                <?php echo _l('update_card_btn'); ?> (<?php echo $payment_method->card->brand; ?> <?php echo $payment_method->card->last4; ?>
                </a>
                <div<?php if(!customer_can_delete_credit_card()){ ?> data-toggle="tooltip" title="<?php echo _l('delete_credit_card_info'); ?>"<?php } ?>
                class="inline-block">
                <a class="btn btn-danger<?php if(!customer_can_delete_credit_card()){ ?> disabled<?php } ?>"
                    href="<?php echo site_url('clients/delete_credit_card'); ?>">
                    <?php echo _l('delete_credit_card'); ?>
                </a>
            </div>
        <?php } else { ?>
            <?php echo _l('no_credit_card_found'); ?>
        <?php } ?>
    </div>
</div>

