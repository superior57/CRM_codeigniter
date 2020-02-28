<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s">
   <div class="panel-body">
      <h4 class="no-margin"><?php echo _l('subscriptions'); ?></h4>
   </div>
</div>
<div class="panel_s">
   <div class="panel-body">
         <table class="table dt-table table-subscriptions" data-order-col="2" data-order-type="desc">
            <thead>
               <tr>
                  <th><?php echo _l('subscription_name'); ?></th>
                  <th><?php echo _l('subscription_status'); ?></th>
                  <th><?php echo _l('next_billing_cycle'); ?></th>
                  <?php if($show_projects) { ?>
                    <th><?php echo _l('project'); ?></th>
                  <?php } ?>
                  <th><?php echo _l('options'); ?></th>
               </tr>
            </thead>
            <tbody>
               <?php foreach($subscriptions as $subscription){
                ?>
               <tr>
                  <td>
                    <?php
                    echo '<b>';
                       if($subscription['quantity'] > 1) {
                            echo $subscription['quantity'] . ' × ';
                        }
                        echo '<a href="'.site_url('subscription/'.$subscription['hash']).'">' . $subscription['name'] . '</a>';
                      echo '</b>';
                        if(!empty($subscription['stripe_subscription_id']) && !empty($subscription['ends_at']) && $subscription['status'] != 'canceled') {
                          echo '<br /><small class="text-info">' . _l('subscription_will_be_canceled_at_end_of_billing_period');
                          echo ' - <a href="'.site_url('clients/resume_subscription/'. $subscription['id']).'">
                             '._l('resume_now').'
                           </a>';
                           echo '</small>';
                        }
                    ?>
                  </td>
                  <td>
                    <?php if(empty($subscription['status'])) {
                        echo _l('subscription_not_subscribed');
                    } else {
                        echo _l('subscription_'.$subscription['status'],'',false);
                    }
                    ?>
                  </td>
                  <td data-order="<?php echo $subscription['next_billing_cycle']; ?>">
                    <?php echo $subscription['next_billing_cycle'] ? _d(date('Y-m-d', $subscription['next_billing_cycle'])) : '-'; ?>
                  </td>
                   <?php if($show_projects) { ?>
                    <td>
                        <a href="<?php echo site_url('clients/project/'.$subscription['project_id']); ?>">
                            <?php echo get_project_name_by_id($subscription['project_id']); ?>
                        </a>
                    </td>
                   <?php } ?>
                  <td>
                    <?php if(empty($subscription['stripe_subscription_id'])){ ?>
                        <a href="<?php echo site_url('subscription/'.$subscription['hash']); ?>" class="btn btn-success btn-xs">
                            <?php echo _l('subscribe'); ?>
                        </a>
                    <?php } elseif($subscription['status'] == 'incomplete') { ?>
                        <a href="<?php echo site_url('subscription/'.$subscription['hash']); ?>?complete=true" class="btn btn-success btn-xs">
                            <?php echo _l('subscription_complete_payment'); ?>
                        </a>
                    <?php } ?>
                      <?php
                     if(!empty($subscription['stripe_subscription_id'])
                          && $subscription['status'] != 'canceled'
                          && empty($subscription['ends_at'])){ ?>
                      <div class="btn-group">
                         <a href="#" class="btn btn-default dropdown-toggle btn-xs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                         <?php echo _l('cancel'); ?> <span class="caret"></span></a>
                         <ul class="dropdown-menu dropdown-menu-right">
                            <li><a href="<?php echo site_url('clients/cancel_subscription/'.$subscription['id'].'?type=immediately'); ?>">
                            <?php echo _l('cancel_immediately'); ?></a></li>
                            <li><a href="<?php echo site_url('clients/cancel_subscription/'.$subscription['id'].'?type=at_period_end'); ?>">
                                <?php echo _l('cancel_at_end_of_billing_period'); ?></a></li>
                         </ul>
                      </div>
                  <?php } ?>
                  </td>
               </tr>
               <?php } ?>
            </tbody>
         </table>
   </div>
</div>
