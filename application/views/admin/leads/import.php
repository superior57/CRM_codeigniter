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
              <div class="col-md-4">
                <?php echo form_open_multipart($this->uri->uri_string(),array('id'=>'import_form')) ;?>
                <?php echo form_hidden('leads_import','true'); ?>
                <?php echo render_input('file_csv','choose_csv_file','','file'); ?>
                <?php
                echo render_leads_status_select($statuses, ($this->input->post('status') ? $this->input->post('status') : get_option('leads_default_status')),'lead_import_status','status', [], true);
                echo render_leads_source_select($sources, ($this->input->post('source') ? $this->input->post('source') : get_option('leads_default_source')),'lead_import_source');
                ?>
                <?php echo render_select('responsible',$members,array('staffid',array('firstname','lastname')),'leads_import_assignee',$this->input->post('responsible')); ?>
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
<?php init_tail(); ?>
<script src="<?php echo base_url('assets/plugins/jquery-validation/additional-methods.min.js'); ?>"></script>
<script>
 $(function(){
    appValidateForm($('#import_form'),{file_csv:{required:true,extension: "csv"},source:'required',status:'required'});
 });
</script>
</body>
</html>
