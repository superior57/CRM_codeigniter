<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" type="text/css" id="roboto-css" href="<?php echo site_url('assets/plugins/roboto/roboto.css'); ?>">
    <style>

        body {
           font-family: Roboto, Geneva, sans-serif;
           font-size:15px;
       }

       .bold, b, strong, h1,h2,h3,h4,h5,h6 {
        font-weight: 500;
    }

    .wrapper {
        margin:0 auto;
        display:block;
        background:#f0f0f0;
        width:700px;
        border:1px solid #e4e4e4;
        padding:20px;
        border-radius:4px;
        margin-top:50px;
        text-align:center;
    }

    .wrapper h1 {
        text-align:center;
        font-size:27px;
        color:red;
        margin-top:0px;
    }

    .wrapper .upgrade_now {
        text-transform:uppercase;
        background:#82b440;
        color:#fff;
        padding: 15px 25px;
        border-radius:3px;
        text-decoration:none;
        text-align:center;
        border:0px;
        outline:0px;
        cursor:pointer;
        font-size: 15px;
    }

    .wrapper .upgrade_now:hover,.wrapper .upgrade_now:active {
        background:#73a92d;
    }

    .wrapper .upgrade_now:disabled {
        cursor:not-allowed;
        pointer-events: none;
        box-shadow: none;
        opacity: .65;
    }

    .upgrade_now_wrapper {
        margin:0 auto;
        width:100%;
        text-align:left;
        margin-top:35px;
    }

    .note {
        color:#636363;
    }
</style>
</head>
<body>
    <div class="wrapper">
     <h1>Database upgrade is required!</h1>
     <p>You need to perform a database upgrade before proceeding. Your <b>files version is <?php echo wordwrap($this->config->item('migration_version'),1,'.',true); ?></b> and <b>database version is <?php echo wordwrap($this->current_db_version,1,'.',true); ?>.</b></p>
     <p class="bold">Make sure that you have backup of your database before performing an upgrade.</p>
     <div class="upgrade_now_wrapper">
        <div style="text-align:center">
            <?php echo form_open($this->config->site_url($this->uri->uri_string()),array('id'=>'upgrade_db_form')); ?>
            <input type="hidden" name="upgrade_database" value="true">
            <button type="submit" id="submit_btn" onclick="upgradeDB(); return false;" class="upgrade_now">Upgrade now</button>
            <?php echo form_close(); ?>
        </div>
        <br />
        <p style="text-align:center;">
         <small class="note">This message may shown if you uploaded files from newer version downloaded from CodeCanyon to your existing installation or you used auto upgrade tool.</small>
     </p>
     <?php
     if($copyData = get_last_upgrade_copy_data()) {
        if($copyData->version == $this->config->item('migration_version')){ ?>
            <hr />
            <h3>A Note of After Upgrade Errors.</h3>
            <p style="line-height:20px;">
                First make sure that you re-check all your custom files, including <b>my_functions_helper.php</b>, <b>my_ prefixed files</b>, <b>custom hooks</b>, <b>custom clients area themes</b> and any <b>third party modules</b>.
            </p>
            <p style="line-height:20px;"><b>Sometimes can happen not all files to be extracted while extracting the files from the upgrade</b> .zip (mostly caused by wrong files permissions), the upgrade files are copied to <b><?php echo $copyData->path; ?></b>,
                you can try to <b>extract them manually</b> for all cases to re-replace the files e.q. via cPanel or command line, use the best method that is suitable for you. <br /></p>

                The copied upgrade zip file will be <b> available for the next <?php echo _delete_temporary_files_older_then() / 60; ?> minutes</b>.

                <p>
                    <b>Remember that</b> that in case you need to extract the files manually, you must extract the contents of the <b><?php echo basename($copyData->path); ?></b> file in <b><?php echo FCPATH; ?></b>
                </p>
                <small class="note">You can copy the text above in case you need to extract the files manually so you can know the location of the upgrade file.</small>
                <?php
            }
        }
        ?>
    </div>
</div>
<script>
    function upgradeDB() {
        document.getElementById('submit_btn').disabled = true;
        document.getElementById('submit_btn').innerHTML = "Please wait...";
        document.getElementById("upgrade_db_form").submit();
    }
</script>
</body>
</html>
