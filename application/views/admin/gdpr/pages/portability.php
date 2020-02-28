<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h4 class="no-mtop">
  <?php echo _l('gdpr_right_to_data_portability'); ?>
  <small>
    <a href="https://ico.org.uk/for-organisations/guide-to-the-general-data-protection-regulation-gdpr/individual-rights/right-to-data-portability/" target="_blank"><?php echo _l('learn_more'); ?></a>
  </small>
</h4>
<hr class="hr-panel-heading" />
<h4>Contacts</h4>
<hr class="hr-panel-heading" />
<?php render_yes_no_option('gdpr_data_portability_contacts','Enable contact to export data (JSON)'); ?>
<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <?php
      $valAllowed = get_option('gdpr_contact_data_portability_allowed');
      if(empty($valAllowed)) {
        $valAllowed = array();
      } else {
        $valAllowed = unserialize($valAllowed);
      }
      ?>
      <label for="gdpr_contact_data_portability_allowed">On export, export the following data</label>
      <div class="select-placeholder">
       <select name="settings[gdpr_contact_data_portability_allowed][]" data-actions-box="true" multiple title="None" id="gdpr_contact_data_portability_allowed" class="selectpicker" data-width="100%">
        <option value="profile_data"<?php if(in_array('profile_data', $valAllowed)){echo ' selected';} ?>>Contact Profile Data</option>
        <option value="consent"<?php if(in_array('consent', $valAllowed)){echo ' selected';} ?>>Consent History</option>
        <option value="tickets"<?php if(in_array('tickets', $valAllowed)){echo ' selected';} ?>>Tickets</option>
          <option data-divider="true"></option>
          <option value="" disabled="true">Only applied if contact is primary contact</option>

         <optgroup label="Customer">
          <option value="customer_profile_data"<?php if(in_array('customer_profile_data', $valAllowed)){echo ' selected';} ?>>Customer Profile Data</option>
          <option value="profile_notes"<?php if(in_array('profile_notes', $valAllowed)){echo ' selected';} ?>>Customer Profile Notes</option>
          <option value="contacts"<?php if(in_array('contacts', $valAllowed)){echo ' selected';} ?>>Contacts</option>
        </optgroup>
        <optgroup label="Invoices">
          <option value="invoices"<?php if(in_array('invoices', $valAllowed)){echo ' selected';} ?>>Invoices Data</option>
          <option value="invoices_notes"<?php if(in_array('invoices_notes', $valAllowed)){echo ' selected';} ?>>Invoices Notes</option>
          <option value="invoices_activity_log"<?php if(in_array('invoices_activity_log', $valAllowed)){echo ' selected';} ?>>Activity Log</option>
        </optgroup>
        <optgroup label="Estimates">
          <option value="estimates"<?php if(in_array('estimates', $valAllowed)){echo ' selected';} ?>>Estimates Data</option>
          <option value="estimates_notes"<?php if(in_array('invoices_notes', $valAllowed)){echo ' selected';} ?>>Estimates Notes</option>
          <option value="estimates_activity_log"<?php if(in_array('estimates_activity_log', $valAllowed)){echo ' selected';} ?>>Activity Log</option>
        </optgroup>
        <optgroup label="Projects">
            <option value="projects"<?php if(in_array('projects', $valAllowed)){echo ' selected';} ?>>Projects</option>
            <option value="related_tasks"<?php if(in_array('related_tasks', $valAllowed)){echo ' selected';} ?>>Tasks created from contact and tasks where contact commented</option>
            <option value="related_discussions"<?php if(in_array('related_discussions', $valAllowed)){echo ' selected';} ?>>Discussions created from contact and discussions where contact commented</option>
            <option value="projects_activity_log"<?php if(in_array('projects_activity_log', $valAllowed)){echo ' selected';} ?>>Activity Log</option>
        </optgroup>

        <option value="credit_notes"<?php if(in_array('credit_notes', $valAllowed)){echo ' selected';} ?>>Credit Notes</option>
        <option value="proposals"<?php if(in_array('proposals', $valAllowed)){echo ' selected';} ?>>Proposals</option>
        <option value="subscriptions"<?php if(in_array('subscriptions', $valAllowed)){echo ' selected';} ?>>Subscriptions</option>
        <option value="expenses"<?php if(in_array('expenses', $valAllowed)){echo ' selected';} ?>>Expenses</option>
        <option value="contracts"<?php if(in_array('contracts', $valAllowed)){echo ' selected';} ?>>Contracts</option>


    </select>
  </div>
</div>

</div>
</div>
<hr class="hr-panel-heading" />
<h4>Leads</h4>
<hr class="hr-panel-heading" />
<?php render_yes_no_option('gdpr_data_portability_leads','Enable leads to export data (JSON)'); ?>
<hr />
<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <?php
      $valAllowed = get_option('gdpr_lead_data_portability_allowed');
      if(empty($valAllowed)) {
        $valAllowed = array();
      } else {
        $valAllowed = unserialize($valAllowed);
      }
      ?>
      <label for="gdpr_lead_data_portability_allowed">On export, export the following data</label>
      <div class="select-placeholder">
       <select name="settings[gdpr_lead_data_portability_allowed][]" data-actions-box="true" multiple title="None" id="gdpr_lead_data_portability_allowed" class="selectpicker" data-width="100%">
        <option value=""></option>
        <option value="profile_data"<?php if(in_array('profile_data',$valAllowed)){echo ' selected';} ?>>Profile Data</option>
        <option value="custom_fields"<?php if(in_array('custom_fields',$valAllowed)){echo ' selected';} ?>>Custom Fields</option>
        <option value="notes"<?php if(in_array('notes',$valAllowed)){echo ' selected';} ?>>Notes</option>
        <option value="activity_log"<?php if(in_array('activity_log',$valAllowed)){echo ' selected';} ?>>Activity log</option>
        <option value="proposals"<?php if(in_array('proposals',$valAllowed)){echo ' selected';} ?>>Proposals</option>
        <option value="integration_emails"<?php if(in_array('integration_emails',$valAllowed)){echo ' selected';} ?>>Email integration emails</option>
        <option value="consent"<?php if(in_array('consent',$valAllowed)){echo ' selected';} ?>>Consent History</option>
      </select>
    </div>
  </div>

</div>
</div>
