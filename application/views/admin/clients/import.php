<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <?php echo $this->import->downloadSampleFormHtml(); ?>
            <?php echo $this->import->maxInputVarsWarningHtml(); ?>
            <?php if(!$this->import->isSimulation()) { ?>
              <?php echo $this->import->importGuidelinesInfoHtml(); ?>
              <?php echo $this->import->createSampleTableHtml(); ?>
            <?php } else { ?>
              <?php echo $this->import->simulationDataInfo(); ?>
              <?php echo $this->import->createSampleTableHtml(true); ?>
            <?php } ?>
            <div class="row">
              <div class="col-md-4 mtop15">
                <?php echo form_open_multipart($this->uri->uri_string(),array('id'=>'import_form')) ;?>
                <?php echo form_hidden('clients_import','true'); ?>
                <?php echo render_input('file_csv','choose_csv_file','','file'); ?>
                <?php
                if(is_admin() || get_option('staff_members_create_inline_customer_groups') == '1'){
                  echo render_select_with_input_group('groups_in[]',$groups,array('id','name'),'customer_groups',($this->input->post('groups_in') ? $this->input->post('groups_in') : array()),'<a href="#" data-toggle="modal" data-target="#customer_group_modal"><i class="fa fa-plus"></i></a>',array('multiple'=>true,'data-actions-box'=>true),array(),'','',false);
                } else {
                  echo render_select('groups_in[]',$groups,array('id','name'),'customer_groups',($this->input->post('groups_in') ? $this->input->post('groups_in') : array()),array('multiple'=>true,'data-actions-box'=>true),array(),'','',false);
                }
                echo render_input('default_pass_all','default_pass_clients_import',$this->input->post('default_pass_all')); ?>
                <div class="form-group">
                  <button type="button" class="btn btn-info import btn-import-submit"><?php echo _l('import'); ?></button>
                  <button type="button" class="btn btn-info simulate btn-import-submit"><?php echo _l('simulate_import'); ?></button>
                </div>
                <?php echo form_close(); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('admin/clients/client_group'); ?>
<?php init_tail(); ?>
<script src="<?php echo base_url('assets/plugins/jquery-validation/additional-methods.min.js'); ?>"></script>
<script>
 $(function(){
   appValidateForm($('#import_form'),{file_csv:{required:true,extension: "csv"},source:'required',status:'required'});
 });
</script>
</body>
</html>
