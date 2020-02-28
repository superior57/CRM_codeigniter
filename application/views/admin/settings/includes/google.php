<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo render_input('settings[google_api_key]','settings_google_api',get_option('google_api_key')); ?>
<?php echo render_input('settings[google_client_id]','google_api_client_id',get_option('google_client_id')); ?>
<hr />
<h4><?php echo _l('re_captcha'); ?></h4>
<?php echo render_input('settings[recaptcha_site_key]','recaptcha_site_key',get_option('recaptcha_site_key')); ?>
<?php echo render_input('settings[recaptcha_secret_key]','recaptcha_secret_key',get_option('recaptcha_secret_key')); ?>
<?php echo render_yes_no_option('use_recaptcha_customers_area','use_recaptcha_customers_area'); ?>
<hr />
<h4>
	<?php echo _l('calendar'); ?>
	<?php if(get_option('google_api_key') != ''){ ?>
		<small>
			<a href="<?php echo admin_url('departments'); ?>" class="mbot10 display-block"><?php echo _l('setup_calendar_by_departments'); ?></a>
		</small>
	<?php } ?>
</h4>
<?php echo render_input('settings[google_calendar_main_calendar]','settings_gcal_main_calendar_id',get_option('google_calendar_main_calendar'),'text',array('data-toggle'=>'tooltip','title'=>'settings_gcal_main_calendar_id_help')); ?>
<hr />
<h4><?php echo _l('google_picker'); ?></h4>
<?php echo render_yes_no_option('enable_google_picker','enable_google_picker'); ?>

