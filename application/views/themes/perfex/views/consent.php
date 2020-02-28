<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s mtop25">
    <div class="panel-body">
        <div class="col-md-12">
            <?php if(is_client_logged_in()){ ?>
                <a href="<?php echo site_url('clients/gdpr'); ?>" class="btn btn-default pull-right">
                    <?php echo _l('client_go_to_dashboard'); ?>
                </a>
            <?php } ?>
            <h1 class="mbot20"><?php echo hooks()->apply_filters('consent_public_page_heading', get_option('companyname')); ?></h1>
            <div class="tc-content mbot20">
                <?php echo get_option('gdpr_consent_public_page_top_block'); ?>
            </div>
        </div>
        <?php
        echo form_open();
        foreach($purposes as $purpose) {
           ?>
           <div class="col-md-12">
            <div class="gdpr-purpose">
                <div class="row">
                    <div class="col-md-9">
                        <h3 class="gdpr-purpose-heading"><?php echo $purpose['name']; ?>
                            <?php if(!empty($purpose['consent_last_updated'])){ ?>
                            <small class="text-muted"><?php echo _l('consent_last_updated',_dt($purpose['consent_last_updated'])); ?></small>
                            <?php } ?>
                        </h3>
                    </div>
                    <div class="col-md-3 text-right">
                        <?php if($purpose['consent_given'] == '1') { ?>
                        <i class="fa fa-check fa-2x text-success" aria-hidden="true"></i>
                        <?php } else { ?>
                        <i class="fa fa-remove fa-2x text-danger" aria-hidden="true"></i>
                        <?php } ?>
                    </div>
                    <div class="col-md-12">
                     <?php
                     if(!empty($purpose['opt_in_purpose_description']) && !empty($purpose['consent_given'])) { ?>
                     <p class="no-mbot mtop10">
                        <?php echo $purpose['opt_in_purpose_description']; ?>
                    </p>
                    <?php } else if(!empty($purpose['description']) && empty($purpose['consent_given'])) { ?>
                    <p class="no-mbot mtop10">
                        <?php echo $purpose['description']; ?>
                    </p>
                    <?php } ?>
                    <hr />
                    <div class="mtop15">
                        <?php
                        if(empty($purpose['consent_given'])) { ?>
                        <div class="radio radio-inline">
                            <input type="radio" value="opt-in" id="opt_in_<?php echo $purpose['id']; ?>" name="action[<?php echo $purpose['id']; ?>]">
                            <label for="opt_in_<?php echo $purpose['id']; ?>"><?php echo _l('gdpr_consent_agree'); ?></label>
                        </div>
                        <?php if(empty($purpose['last_action_is_opt_out'])) { ?>
                        <div class="radio radio-inline">
                            <input type="radio" value="opt-out" id="opt_out_<?php echo $purpose['id']; ?>" name="action[<?php echo $purpose['id']; ?>]">
                            <label for="opt_out_<?php echo $purpose['id']; ?>"><?php echo _l('gdpr_consent_disagree'); ?></label>
                        </div>
                        <?php } ?>
                        <?php } else { ?>
                        <div class="radio radio-inline">
                            <input type="radio" value="opt-out" id="opt_out_<?php echo $purpose['id']; ?>" name="action[<?php echo $purpose['id']; ?>]">
                            <label for="opt_out_<?php echo $purpose['id']; ?>"><?php echo _l('gdpr_consent_disagree'); ?></label>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <div class="col-md-12">
        <button type="submit" class="btn btn-info"><?php echo _l('update_consent'); ?></button>
    </div>
    <?php echo form_close(); ?>
</div>
</div>
