<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s section-heading section-announcement">
    <div class="panel-body">
        <h4 class="bold no-margin announcement-heading section-text"><?php echo $announcement->name; ?></h4>
        <div class="mtop5 announcement-date"><?php echo _l('announcement_date',_d($announcement->dateadded)); ?></div>
        <?php if($announcement->showname == 1){ echo _l('announcement_from') . ' ' . $announcement->userid; } ?>
    </div>
</div>
<div class="panel_s">
    <div class="panel-body tc-content announcement-content">
        <?php echo $announcement->message; ?>
    </div>
</div>
