<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="horizontal-scrollable-tabs">
  <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
  <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
  <div class="horizontal-tabs">
    <ul class="nav nav-tabs no-margin project-tabs nav-tabs-horizontal" role="tablist">
        <?php
        foreach(filter_project_visible_tabs($tabs, $project->settings->available_features) as $key => $tab){
            $dropdown = isset($tab['collapse']) ? true : false;
            ?>
            <li class="<?php if($key == 'project_overview' && !$this->input->get('group')){echo 'active ';} ?>project_tab_<?php echo $key; ?><?php if($dropdown){echo ' nav-tabs-submenu-parent';} ?>">
                <a
                data-group="<?php echo $key; ?>"
                role="tab"
                <?php if($dropdown){ ?>
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="true"
                    class="dropdown-toggle"
                    href="#"
                    id="dropdown_<?php echo $key; ?>"<?php } else { ?>
                    href="<?php echo admin_url('projects/view/'.$project->id.'?group='.$key); ?>"
                    <?php } ?>>
                    <i class="<?php echo $tab['icon']; ?>" aria-hidden="true"></i>
                    <?php echo $tab['name']; ?>
                    <?php if($dropdown){ ?> <span class="caret"></span> <?php } ?>
                </a>
                <?php if($dropdown){ ?>
                    <?php if(!is_rtl()){ ?>
                        <div class="tabs-submenu-wrapper">
                        <?php } ?>
                        <ul class="dropdown-menu" aria-labelledby="dropdown_<?php echo $key; ?>">
                            <?php
                            foreach($tab['children'] as $d){
                                echo '<li class="nav-tabs-submenu-child"><a href="'.admin_url('projects/view/'.$project->id.'?group='.$d['slug']).'" data-group="'.$d['slug'].'">'.$d['name'].'</a></li>';
                            }
                            ?>
                        </ul>
                        <?php if(!is_rtl()){ ?>
                        </div>
                    <?php } ?>
                <?php } ?>
            </li>
        <?php } ?>
    </ul>
</div>
</div>

