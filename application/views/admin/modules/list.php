<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="panel_s">
            <div class="panel-body">
              <div class="row">
                <div class="col-md-12">
                    <?php echo form_open_multipart(admin_url('modules/upload'),['id'=>'module_install_form']); ?>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <p class="font-medium">If you have a module in a .zip format, you may install it by uploading it here.</p>
                        </div>
                        <div class="col-md-4 col-md-offset-4">
                            <label for="module" class="control-label">Upload Module</label>
                            <div class="input-group">
                                <input type="file" class="form-control" name="module">
                                <span class="input-group-btn">
                                    <button class="btn btn-default p8" type="submit">Install</button>
                                </span>
                            </div><!-- /input-group -->
                        </div><!-- /.col-md-6 -->
                    </div>
                    <?php echo form_close(); ?>
                    <hr />
                    <div class="table-responsive">
                        <table class="table dt-table" data-order-type="asc" data-order-col="0">
                            <thead>
                                <tr>
                                    <th>
                                        <?php echo _l('module'); ?>
                                    </th>
                                    <th>
                                        <?php echo _l('module_description'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modules as $module) {
                                    $system_name = $module['system_name'];
                                    $database_upgrade_is_required = $this->app_modules->is_database_upgrade_required($system_name);
                                     ?>
                                    <tr class="<?php if($module['activated'] === 1 && !$database_upgrade_is_required){echo 'alert-info';} ?><?php if($database_upgrade_is_required){echo ' alert-warning';} ?>">
                                        <td data-order="<?php echo $system_name; ?>">
                                            <p>
                                                <b>
                                                    <?php echo $module['headers']['module_name']; ?>
                                                </b>
                                            </p>
                                            <?php
                                            $action_links = [];
                                            $versionRequirementMet = $this->app_modules->is_minimum_version_requirement_met($system_name);
                                            $action_links = hooks()->apply_filters("module_{$system_name}_action_links", $action_links);

                                            if($module['activated'] === 0 && $versionRequirementMet) {
                                                array_unshift($action_links, '<a href="' . admin_url('modules/activate/' . $system_name) . '">' . _l('module_activate') . '</a>');
                                            }

                                            if($module['activated'] === 1){
                                                array_unshift($action_links, '<a href="' . admin_url('modules/deactivate/' . $system_name) . '">' . _l('module_deactivate') . '</a>');
                                            }

                                            if($database_upgrade_is_required) {
                                                $action_links[] = '<a href="' . admin_url('modules/upgrade_database/' . $system_name) . '" class="text-success bol">' . _l('module_upgrade_database') . '</a>';
                                            }

                                            if($module['activated'] === 0 && !in_array($system_name, uninstallable_modules())) {
                                                $action_links[] = '<a href="' . admin_url('modules/uninstall/' . $system_name) . '" class="_delete text-danger">' . _l('module_uninstall') . '</a>';
                                            }

                                            echo implode('&nbsp;|&nbsp;', $action_links);

                                            if(!$versionRequirementMet) {
                                                echo '<div class="alert alert-warning mtop5 p7">';
                                                echo 'This module requires at least v' . $module['headers']['requires_at_least'] . ' of the CRM.';
                                                if($module['activated'] === 0) {
                                                    echo ' Hence, cannot be activated';
                                                }
                                                echo '</div>';
                                            }

                                            if($newVersionData = $this->app_modules->new_version_available($system_name)) {
                                                echo '<div class="alert alert-success mtop5 p7">';

                                                    echo 'There is a new version of '.$module['headers']['module_name'].' available. ';
                                                    $version_actions = [];

                                                    if(isset($newVersionData['changelog']) && !empty($newVersionData['changelog'])) {
                                                         $version_actions[] = '<a href="'.$newVersionData['changelog'].'" target="_blank">Release Notes ('.$newVersionData['version'].')</a>';
                                                    }

                                                    if($this->app_modules->is_update_handler_available($system_name)) {
                                                        $version_actions[] = '<a href="'.admin_url('modules/update_version/'.$system_name).'" id="update-module-'.$system_name.'">Update</a>';
                                                    }

                                                    echo implode('&nbsp;|&nbsp;', $version_actions);
                                                echo '</div>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <p>
                                                <?php echo isset($module['headers']['description']) ? $module['headers']['description'] : ''; ?>
                                            </p>
                                            <?php

                                            $module_description_info = [];
                                            hooks()->apply_filters("module_{$system_name}_description_info", $module_description_info);

                                            if (isset($module['headers']['author'])) {
                                                $author = $module['headers']['author'];
                                                if (isset($module['headers']['author_uri'])) {
                                                    $author = '<a href="' . $module['headers']['author_uri'] . '">' . $author . '</a>';
                                                }
                                                array_unshift($module_description_info, _l('module_by', $author));
                                            }

                                            array_unshift($module_description_info, _l('module_version', $module['headers']['version']));
                                            echo implode('&nbsp;|&nbsp;', $module_description_info); ?>
                                        </td>
                                    </tr>
                                    <?php
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<?php init_tail(); ?>
<script>
    $(function(){
        appValidateForm($('#module_install_form'), {module:{required:true,extension:"zip"}});
    });
</script>
</body>
</html>
