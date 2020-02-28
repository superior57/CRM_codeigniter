<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if(isset($client)){ ?>
<h4 class="customer-profile-group-heading"><?php echo is_empty_customer_company($client->userid) ? _l('contact') : _l('customer_contacts'); ?></h4>
<?php if($this->session->flashdata('gdpr_delete_warning')){ ?>
    <div class="alert alert-warning">
     [GDPR] The contact you removed has associated proposals using the email address of the contact and other personal information. You may want to re-check all proposals related to this customer and remove any personal data from proposals linked to this contact.
   </div>
<?php } ?>
<?php if((has_permission('customers','','create') || is_customer_admin($client->userid)) && $client->registration_confirmed == '1'){
   $disable_new_contacts = false;
   if(is_empty_customer_company($client->userid) && total_rows(db_prefix().'contacts',array('userid'=>$client->userid)) == 1){
      $disable_new_contacts = true;
   }
   ?>
<div class="inline-block new-contact-wrapper" data-title="<?php echo _l('customer_contact_person_only_one_allowed'); ?>"<?php if($disable_new_contacts){ ?> data-toggle="tooltip"<?php } ?>>
   <a href="#" onclick="contact(<?php echo $client->userid; ?>); return false;" class="btn btn-info new-contact mbot25<?php if($disable_new_contacts){echo ' disabled';} ?>"><?php echo _l('new_contact'); ?></a>
</div>
<?php } ?>
<?php
   $table_data = array(_l('clients_list_full_name'));
   if(is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1'){
         array_push($table_data, array(
            'name'=>_l('gdpr_consent') .' ('._l('gdpr_short').')',
            'th_attrs'=>array('id'=>'th-consent', 'class'=>'not-export')
         ));
   }
  $table_data = array_merge($table_data, array(_l('client_email'),_l('contact_position'),_l('client_phonenumber'),_l('contact_active'),_l('clients_list_last_login')));
   $custom_fields = get_custom_fields('contacts',array('show_on_table'=>1));
   foreach($custom_fields as $field){
      array_push($table_data,$field['name']);
   }
   echo render_datatable($table_data,'contacts'); ?>
<?php } ?>
<div id="contact_data"></div>
<div id="consent_data"></div>
