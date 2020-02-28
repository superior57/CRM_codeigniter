<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
     <div class="col-md-12">
      <div class="panel_s">
       <div class="_filters _hidden_inputs">
        <?php
        foreach(get_subscriptions_statuses() as $status) {
          $val = '';
          if(!$this->input->get('status') || $this->input->get('status') && $this->input->get('status') == $status['id']) {
            $val = $status['id'];
          }
          if(!$this->input->get('status') && $status['id'] == 'canceled') {
            $val = '';
          }
          echo form_hidden('subscription_status_'.$status['id'], $val);
        }
        echo form_hidden('not_subscribed',!$this->input->get('status') || $this->input->get('status') && $this->input->get('status') == 'not_subscribed' ?'not_subscribed' : '');
        ?>
      </div>
      <div class="panel-body">
        <div class="_buttons">
          <?php if(has_permission('subscriptions','','create')){ ?>
            <a href="<?php echo admin_url('subscriptions/create'); ?>" class="btn btn-info pull-left display-block">
              <?php echo _l('new_subscription'); ?>
            </a>
            <?php } ?>
            <div class="btn-group pull-right mleft4 btn-with-tooltip-group _filter_data" data-toggle="tooltip" data-title="<?php echo _l('filter_by'); ?>">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-filter" aria-hidden="true"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-right width300">
                <li>
                  <a href="#" data-cview="all" onclick="dt_custom_view('','.table-subscriptions',''); return false;">
                    <?php echo _l('all'); ?>
                  </a>
                </li>
                <li class="divider"></li>
                <li class="<?php if(!$this->input->get('status') || $this->input->get('status') && $this->input->get('status') == 'not_subscribed'){echo 'active';} ?>">
                  <a href="#" data-cview="not_subscribed" onclick="dt_custom_view('not_subscribed','.table-subscriptions','not_subscribed'); return false;">
                    <?php echo _l('subscription_not_subscribed'); ?>
                  </a>
                </li>
                <?php foreach(get_subscriptions_statuses() as $status){ ?>
                  <li class="<?php if($status['filter_default'] == true && !$this->input->get('status') || $this->input->get('status') == $status['id']){echo 'active';} ?>">
                    <a href="#" data-cview="<?php echo 'subscription_status_'.$status['id']; ?>" onclick="dt_custom_view('subscription_status_<?php echo $status['id']; ?>','.table-subscriptions','subscription_status_<?php echo $status['id']; ?>'); return false;">
                      <?php echo _l('subscription_'.$status['id']); ?>
                    </a>
                  </li>
                  <?php } ?>
                </ul>
              </div>
            </div>
            <div class="clearfix"></div>
            <hr class="hr-panel-heading" />

            <h4 class="mbot15"><i class="fa fa-cc-stripe" aria-hidden="true"></i> <?php echo _l('subscriptions_summary'); ?></h4>
            <div class="row">
              <?php foreach(subscriptions_summary() as $summary){ ?>
                <div class="col-md-2 col-xs-6 border-right">
                  <h3 class="bold no-mtop"><?php echo $summary['total']; ?></h3>
                  <p style="color:<?php echo $summary['color']; ?>" class="no-mbot">
                    <?php echo _l('subscription_'.$summary['id']); ?>
                  </p>
                </div>
                <?php } ?>
              </div>
              <hr class="hr-panel-heading" />
              <?php hooks()->do_action('before_subscriptions_table'); ?>
              <?php $this->load->view('admin/subscriptions/table_html',array('url'=>admin_url('subscriptions/table'))); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php init_tail(); ?>
</body>
</html>
