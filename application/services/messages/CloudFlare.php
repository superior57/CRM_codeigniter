<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractMessage;

class CloudFlare extends AbstractMessage
{
	protected $alertClass = 'warning';

	public function isVisible()
	{
		$CI     = &get_instance();
		$header = $CI->input->get_request_header('Cf-Ray');

		return $header && !empty($header) && get_option('show_cloudflare_notice') == '1' && is_admin();
	}

	public function getMessage()
	{
		?>
		<div class="mtop15"></div>
		<h4><strong>Cloudflare usage detected</strong></h4><hr />
		<ul>
			<li>When using Cloudflare with the application <strong>you must disable ROCKET LOADER</strong> feature from Cloudflare options in order everything to work properly. <br /><strong><small>NOTE: The script can't check if Rocket Loader is enabled/disabled in your Cloudflare account. If Rocket Loader is already disabled you can ignore this warning.</small></strong></li>
			<li>
				<br />
				<ul>
					<li><strong>&nbsp;&nbsp;- Disable Rocket Loader for whole domain name</strong></li>
					<li>&nbsp;&nbsp;&nbsp;&nbsp;Login to your Cloudflare account and click on the <strong>Speed</strong> tab from the top dashboard, search for Rocket Loader and <strong>set to Off</strong>.</li>
					<br />
					<li><strong>&nbsp;&nbsp;- Disable Rocket Loader with page rule for application installation url</strong></li>
					<li>
						&nbsp;&nbsp;&nbsp;&nbsp;If you do not want to turn off Rocket Loader for the whole domain you can add <a href="https://support.cloudflare.com/hc/en-us/articles/200168306-Is-there-a-tutorial-for-Page-Rules-" target="_blank">page rule</a> that will disable the Rocket Loader only for the application, follow the steps below in order to achieve this.
						<br /><br />
						<p class="no-margin">&nbsp;&nbsp;- Login to your Cloudflare account and click on the <strong>Page Rules</strong> tab from the top dashboard</p>
						<p class="no-margin">&nbsp;&nbsp;- Click on <strong>Create Page Rule</strong></p>
						<p class="no-margin">&nbsp;&nbsp;- In the url field add the following url: <strong><?php echo rtrim(site_url(), '/') . '/'; ?>*</strong></p>
						<p class="no-margin">&nbsp;&nbsp;- Click <strong>Add Setting</strong> and search for <strong>Rocket Loader</strong></p>
						<p class="no-margin">&nbsp;&nbsp;- After you select Rocket Loader <strong>set value to Off</strong></p>
						<p class="no-margin">&nbsp;&nbsp;- Click <strong>Save and Deploy</strong></p>
					</li>
				</ul>
			</li>
		</ul>
		<br /><br /><a href="<?php echo admin_url('misc/dismiss_cloudflare_notice'); ?>" class="alert-link">Got it! Don't show this message again</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="<?php echo admin_url('misc/dismiss_cloudflare_notice'); ?>" class="alert-link">Rocket loader is already disabled</a>
		<?php
	}
}
