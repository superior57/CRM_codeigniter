<?php defined('BASEPATH') or exit('No direct script access allowed');
foreach($purposes as $purpose) { ?>
    <div class="col-md-12">
        <div class="gdpr-purpose">
            <div class="row">
                <div class="col-md-9">
                    <h3 class="gdpr-purpose-heading"><?php echo $purpose['name']; ?>
                    <small>
                        <a href="#" onclick="slideToggle('#purposeActionForm-<?php echo $purpose['id']; ?>'); return false;">
                            <?php if(!empty($purpose['consent_given'])) {
                              echo _l('gdpr_consent_opt_out');
                          } else {
                            echo _l('gdpr_consent_opt_in');
                        } ?>
                    </a>
                </small>
            </h3>
        </div>
        <div class="col-md-3 text-right">
            <?php if(!empty($purpose['consent_given'])) { ?>
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
        </div>
        <div class="col-md-12 opt-action hide" id="purposeActionForm-<?php echo $purpose['id']; ?>">
            <hr />
            <?php echo form_open(admin_url($form_url),array('class'=>'consent-form')); ?>
            <input type="hidden" name="action" value="<?php echo !empty($purpose['consent_given']) ? 'opt-out' : 'opt-in'; ?>">
            <input type="hidden" name="purpose_id" value="<?php echo $purpose['id']; ?>">
            <?php if(isset($contact_id)) { ?>
                <input type="hidden" name="contact_id" value="<?php echo $contact_id; ?>">
            <?php } else if(isset($lead_id)) { ?>
                <input type="hidden" name="lead_id" value="<?php echo $lead_id; ?>">
            <?php } ?>
            <?php echo render_textarea('description', 'Additional Description'); ?>
            <?php if($purpose['consent_given'] != '1') { ?>
                <?php echo render_textarea('opt_in_purpose_description', 'Purpose Description', $purpose['description']); ?>
            <?php } ?>
            <button type="submit" class="btn btn-<?php echo !empty($purpose['consent_given']) ? 'danger' : 'success'; ?>">
                <?php echo !empty($purpose['consent_given']) ? _l('gdpr_consent_opt_out') : _l('gdpr_consent_opt_in'); ?>
            </button>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
</div>
<?php } ?>
<div class="clearfix"></div>
<hr />
<div class="col-md-12">
    <h4>History</h4>
    <table class="table dt-table scroll-responsive" data-order-type="asc" data-order-col="2" id="consentHistoryTable">
        <thead>
            <tr>
                <th>Purpose</th>
                <th>Date</th>
                <th>Action</th>
                <th><?php echo _l('view_ip'); ?></th>
                <th>
                    <i class="fa fa-question-circle" data-toggle="tooltip" title="Only used if consent is updated from staff member."></i> <?php echo _l('staff_member'); ?>
                </th>
                <th>Additional Description</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach($consents as $consent) { ?>
                <tr>
                    <td>
                        <b><?php echo $consent['purpose_name']; ?></b>
                    </td>
                    <td><?php echo _dt($consent['date']); ?></td>
                    <td><?php echo $consent['action'] == 'opt-in' ? _l('gdpr_consent_opt_in') : _l('gdpr_consent_opt_out'); ?></td>
                    <td><?php echo $consent['ip']; ?></td>
                    <td><?php echo $consent['staff_name']; ?></td>
                    <td><?php echo $consent['description']; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
