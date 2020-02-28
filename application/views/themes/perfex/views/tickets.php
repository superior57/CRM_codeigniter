<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s section-heading section-tickets">
  <div class="panel-body">
    <h4 class="no-margin section-text"><?php echo _l('clients_tickets_heading'); ?></h4>
  </div>
</div>
<div class="panel_s">
  <div class="panel-body">
    <div class="row">
      <div class="col-md-12">
        <h3 class="text-success pull-left no-mtop tickets-summary-heading"><?php echo _l('tickets_summary'); ?></h3>
        <a href="<?php echo site_url('clients/open_ticket'); ?>" class="btn btn-info new-ticket pull-right">
          <?php echo _l('clients_ticket_open_subject'); ?>
        </a>
        <div class="clearfix"></div>
        <hr />
      </div>
      <?php foreach(get_clients_area_tickets_summary($ticket_statuses) as $status){ ?>
        <div class="col-md-2 list-status ticket-status">
         <a href="<?php echo $status['url']; ?>" class="<?php if(in_array($status['ticketstatusid'], $list_statuses)){echo 'active';} ?>">
            <h3 class="bold ticket-status-heading">
              <?php echo $status['total_tickets'] ?>
            </h3>
            <span style="color:<?php echo $status['statuscolor']; ?>">
              <?php echo $status['translated_name']; ?>
            </span>
        </a>
      </div>
    <?php } ?>
  </div>
  <div class="clearfix"></div>
  <hr />
  <div class="clearfix"></div>
  <?php get_template_part('tickets_table'); ?>
</div>
</div>
</div>
