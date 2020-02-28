<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div id="proposal-wrapper">
   <?php
      ob_start();

      $qty_heading = _l('estimate_table_quantity_heading', '', false);

      if ($proposal->show_quantity_as == 2) {
          $qty_heading = _l($this->type . '_table_hours_heading', '', false);
      } elseif ($proposal->show_quantity_as == 3) {
          $qty_heading = _l('estimate_table_quantity_heading', '', false) . '/' . _l('estimate_table_hours_heading', '', false);
      }

      $items = get_items_table_data($proposal, 'proposal')
              ->add_table_class('no-margin')
              ->set_headings('estimate');

              echo $items->table();
              ?>
   <div class="row mtop15">
      <div class="col-md-6 col-md-offset-6">
         <table class="table text-right">
            <tbody>
               <tr id="subtotal">
                  <td><span class="bold"><?php echo _l('estimate_subtotal'); ?></span>
                  </td>
                  <td class="subtotal">
                     <?php echo app_format_money($proposal->subtotal, $proposal->currency_name); ?>
                  </td>
               </tr>
               <?php if(is_sale_discount_applied($proposal)){ ?>
               <tr>
                  <td>
                     <span class="bold"><?php echo _l('estimate_discount'); ?>
                     <?php if(is_sale_discount($proposal,'percent')){ ?>
                     (<?php echo app_format_number($proposal->discount_percent,true); ?>%)
                     <?php } ?></span>
                  </td>
                  <td class="discount">
                     <?php echo '-' . app_format_money($proposal->discount_total, $proposal->currency_name); ?>
                  </td>
               </tr>
               <?php } ?>
               <?php
                  foreach($items->taxes() as $tax){
                   echo '<tr class="tax-area"><td class="bold">'.$tax['taxname'].' ('.app_format_number($tax['taxrate']).'%)</td><td>'.app_format_money($tax['total_tax'], $proposal->currency_name).'</td></tr>';
                  }
                  ?>
               <?php if((int)$proposal->adjustment != 0){ ?>
               <tr>
                  <td>
                     <span class="bold"><?php echo _l('estimate_adjustment'); ?></span>
                  </td>
                  <td class="adjustment">
                     <?php echo app_format_money($proposal->adjustment, $proposal->currency_name); ?>
                  </td>
               </tr>
               <?php } ?>
               <tr>
                  <td><span class="bold"><?php echo _l('estimate_total'); ?></span>
                  </td>
                  <td class="total">
                     <?php echo app_format_money($proposal->total, $proposal->currency_name); ?>
                  </td>
               </tr>
            </tbody>
         </table>
      </div>
   </div>
   <?php
      if(get_option('total_to_words_enabled') == 1){ ?>
   <div class="col-md-12 text-center proposal-html-total-to-words">
      <p class="bold"><?php echo  _l('num_word').': '.$this->numberword->convert($proposal->total,$proposal->currency_name); ?></p>
   </div>
   <?php }
      $items = ob_get_contents();
      ob_end_clean();
      $proposal->content = str_replace('{proposal_items}',$items,$proposal->content);
      ?>
   <div class="mtop15 preview-top-wrapper">
      <div class="row">
         <div class="col-md-3">
            <div class="mbot30">
               <div class="proposal-html-logo">
                  <?php echo get_dark_company_logo(); ?>
               </div>
            </div>
         </div>
         <div class="clearfix"></div>
      </div>
      <div class="top" data-sticky data-sticky-class="preview-sticky-header">
         <div class="container preview-sticky-container">
            <div class="row">
               <div class="col-md-12">
                  <div class="pull-left">
                     <h4 class="bold no-mtop proposal-html-number"># <?php echo format_proposal_number($proposal->id); ?><br />
                        <small class="proposal-html-subject"><?php echo $proposal->subject; ?></small>
                     </h4>
                  </div>
                  <div class="visible-xs">
                     <div class="clearfix"></div>
                  </div>
                  <?php if(($proposal->status != 2 && $proposal->status != 3)){
                     if(!empty($proposal->open_till) && date('Y-m-d',strtotime($proposal->open_till)) < date('Y-m-d')){
                       echo '<span class="warning-bg content-view-status">'._l('proposal_expired').'</span>';
                     } else { ?>
                  <?php if($identity_confirmation_enabled == '1'){ ?>
                  <button type="button" id="accept_action" class="btn btn-success pull-right action-button mleft5">
                  <i class="fa fa-check"></i> <?php echo _l('proposal_accept_info'); ?>
                  </button>
                  <?php } else { ?>
                  <?php echo form_open($this->uri->uri_string()); ?>
                  <button type="submit" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off" class="btn btn-success pull-right action-button mleft5"><i class="fa fa-check"></i> <?php echo _l('proposal_accept_info'); ?></button>
                  <?php echo form_hidden('action','accept_proposal'); ?>
                  <?php echo form_close(); ?>
                  <?php } ?>
                  <?php echo form_open($this->uri->uri_string()); ?>
                  <button type="submit" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off" class="btn btn-default pull-right action-button mleft5"><i class="fa fa-remove"></i> <?php echo _l('proposal_decline_info'); ?></button>
                  <?php echo form_hidden('action','decline_proposal'); ?>
                  <?php echo form_close(); ?>
                  <?php } ?>
                  <!-- end expired proposal -->
                  <?php } else {
                     if($proposal->status == 2){
                       echo '<span class="danger-bg content-view-status">'._l('proposal_status_declined').'</span>';
                     } else if($proposal->status == 3){
                       echo '<span class="success-bg content-view-status">'._l('proposal_status_accepted').'</span>';
                     }
                     } ?>
                  <?php echo form_open($this->uri->uri_string()); ?>
                  <button type="submit" class="btn btn-default pull-right action-button mleft5"><i class="fa fa-file-pdf-o"></i> <?php echo _l('clients_invoice_html_btn_download'); ?></button>
                  <?php echo form_hidden('action','proposal_pdf'); ?>
                  <?php echo form_close(); ?>
                  <?php if(is_client_logged_in() && has_contact_permission('proposals')){ ?>
                  <a href="<?php echo site_url('clients/proposals/'); ?>" class="btn btn-default mleft5 pull-right action-button go-to-portal">
                  <?php echo _l('client_go_to_dashboard'); ?>
                  </a>
                  <?php } ?>
                  <div class="clearfix"></div>
               </div>
            </div>
         </div>
      </div>
   </div>
   <div class="row">
      <div class="col-md-8 proposal-left">
         <div class="panel_s mtop20">
            <div class="panel-body proposal-content tc-content padding-30">
               <?php echo $proposal->content; ?>
            </div>
         </div>
      </div>
      <div class="col-md-4 proposal-right">
         <div class="inner mtop20 proposal-html-tabs">
            <ul class="nav nav-tabs nav-tabs-flat mbot15" role="tablist">
               <li role="presentation" class="<?php if(!$this->input->get('tab') || $this->input->get('tab') === 'summary'){echo 'active';} ?>">
                  <a href="#summary" aria-controls="summary" role="tab" data-toggle="tab">
                  <i class="fa fa-file-text-o" aria-hidden="true"></i> <?php echo _l('summary'); ?></a>
               </li>
               <?php if($proposal->allow_comments == 1){ ?>
               <li role="presentation" class="<?php if($this->input->get('tab') === 'discussion'){echo 'active';} ?>">
                  <a href="#discussion" aria-controls="discussion" role="tab" data-toggle="tab">
                  <i class="fa fa-commenting-o" aria-hidden="true"></i> <?php echo _l('discussion'); ?>
                  </a>
               </li>
               <?php } ?>
            </ul>
            <div class="tab-content">
               <div role="tabpanel" class="tab-pane<?php if(!$this->input->get('tab') || $this->input->get('tab') === 'summary'){echo ' active';} ?>" id="summary">
                  <address class="proposal-html-company-info">
                     <?php echo format_organization_info(); ?>
                  </address>
                  <hr />
                  <p class="bold proposal-html-information">
                     <?php echo _l('proposal_information'); ?>
                  </p>
                  <address class="no-margin proposal-html-info">
                     <?php echo format_proposal_info($proposal, 'html'); ?>
                  </address>
                  <div class="row mtop20">
                     <?php if($proposal->total != 0){ ?>
                     <div class="col-md-12 proposal-html-total">
                        <h4 class="bold mbot30"><?php echo _l('proposal_total_info',app_format_money($proposal->total, $proposal->currency_name)); ?></h4>
                     </div>
                     <?php } ?>
                     <div class="col-md-4 text-muted proposal-status">
                        <?php echo _l('proposal_status'); ?>
                     </div>
                     <div class="col-md-8 proposal-status">
                        <?php echo format_proposal_status($proposal->status,'', false); ?>
                     </div>
                     <div class="col-md-4 text-muted proposal-date">
                        <?php echo _l('proposal_date'); ?>
                     </div>
                     <div class="col-md-8 proposal-date">
                        <?php echo _d($proposal->date); ?>
                     </div>
                     <?php if(!empty($proposal->open_till)){ ?>
                     <div class="col-md-4 text-muted proposal-open-till">
                        <?php echo _l('proposal_open_till'); ?>
                     </div>
                     <div class="col-md-8 proposal-open-till">
                        <?php echo _d($proposal->open_till); ?>
                     </div>
                     <?php } ?>
                  </div>
                  <?php if(count($proposal->attachments) > 0 && $proposal->visible_attachments_to_customer_found == true){ ?>
                  <div class="proposal-attachments">
                     <hr />
                     <p class="bold mbot15"><?php echo _l('proposal_files'); ?></p>
                     <?php foreach($proposal->attachments as $attachment){
                        if($attachment['visible_to_customer'] == 0){continue;}
                        $attachment_url = site_url('download/file/sales_attachment/'.$attachment['attachment_key']);
                        if(!empty($attachment['external'])){
                          $attachment_url = $attachment['external_link'];
                        }
                        ?>
                     <div class="col-md-12 row mbot15">
                        <div class="pull-left"><i class="<?php echo get_mime_class($attachment['filetype']); ?>"></i></div>
                        <a href="<?php echo $attachment_url; ?>"><?php echo $attachment['file_name']; ?></a>
                     </div>
                     <?php } ?>
                  </div>
                  <?php } ?>
               </div>
               <?php if($proposal->allow_comments == 1){ ?>
               <div role="tabpanel" class="tab-pane<?php if($this->input->get('tab') === 'discussion'){echo ' active';} ?>" id="discussion">
                  <?php echo form_open($this->uri->uri_string()) ;?>
                  <div class="proposal-comment">
                     <textarea name="content" rows="4" class="form-control"></textarea>
                     <button type="submit" class="btn btn-info mtop10 pull-right" data-loading-text="<?php echo _l('wait_text'); ?>"><?php echo _l('proposal_add_comment'); ?></button>
                     <?php echo form_hidden('action','proposal_comment'); ?>
                  </div>
                  <?php echo form_close(); ?>
                  <div class="clearfix"></div>
                  <?php
                     $proposal_comments = '';
                     foreach ($comments as $comment) {
                      $proposal_comments .= '<div class="proposal_comment mtop10 mbot20" data-commentid="' . $comment['id'] . '">';
                      if($comment['staffid'] != 0){
                        $proposal_comments .= staff_profile_image($comment['staffid'], array(
                          'staff-profile-image-small',
                          'media-object img-circle pull-left mright10'
                        ));
                      }
                      $proposal_comments .= '<div class="media-body valign-middle">';
                      $proposal_comments .= '<div class="mtop5">';
                      $proposal_comments .= '<b>';
                      if($comment['staffid'] != 0){
                        $proposal_comments .= get_staff_full_name($comment['staffid']);
                      } else {
                        $proposal_comments .= _l('is_customer_indicator');
                      }
                      $proposal_comments .= '</b>';
                      $proposal_comments .= ' - <small class="mtop10 text-muted">' . time_ago($comment['dateadded']) . '</small>';
                      $proposal_comments .= '</div>';
                      $proposal_comments .= '<br />';
                      $proposal_comments .= check_for_links($comment['content']) . '<br />';
                      $proposal_comments .= '</div>';
                      $proposal_comments .= '</div>';
                     }
                     echo $proposal_comments; ?>
               </div>
               <?php } ?>
            </div>
         </div>
      </div>
   </div>
</div>
<?php
   if($identity_confirmation_enabled == '1'){
        get_template_part('identity_confirmation_form',array('formData'=>form_hidden('action','accept_proposal')));
   }
   ?>
<script>
   $(function(){
     new Sticky('[data-sticky]');
     $(".proposal-left table").wrap("<div class='table-responsive'></div>");
         // Create lightbox for proposal content images
         $('.proposal-content img').wrap( function(){ return '<a href="' + $(this).attr('src') + '" data-lightbox="proposal"></a>'; });
     });
</script>
</div>
