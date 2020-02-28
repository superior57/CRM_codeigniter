<?php hooks()->do_action('before_system_info'); ?>
<h4 class="no-mtop">
	<a download="system-info.xls" class="btn btn-default" href="#" onclick="return ExcellentExport.excel(this, 'system-info', 'System Info');">
		<i class="fa fa-file-excel-o"></i>
	</a>
	System/Server Information
</h4>
<hr class="hr-panel-heading" />
<div class="table-responsive">
	<table class="table table-bordered" id="system-info">
		<thead>
			<tr>
				<th>Variable Name</th>
				<th>Value</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="bold">OS</td>
				<td>
					<?php
					echo PHP_OS;
					?>
				</td>
			</tr>
			<tr>
				<td class="bold">Webserver</td>
				<td>
					<?php
					echo isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'N/A';
					?>
				</td>
			</tr>
			<tr>
				<td class="bold">Server Protocol</td>
				<td>
					<?php
					echo isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'N/A';
					?>
				</td>
			</tr>
			<tr>
				<td class="bold">Installation Date</td>
				<td>
					<?php
					$date_installation = get_option('di');
					echo !empty($date_installation) ? date('Y-m-d H:i:s', $date_installation) : 'N/A';
					?>
				</td>
			</tr>
			<tr>
				<td class="bold">PHP Version</td>
				<td>
					<?php
					echo PHP_VERSION;
					?>
				</td>
			</tr>
			<tr>
				<td class="bold">PHP Extension "curl"</td>
				<td>
					<?php
					if (!extension_loaded('curl')) {
						echo "<span class='text-danger'>Not enabled</span>";
					} else {
						$curlVersion = curl_version();
						echo "<span class='text-success'>Enabled (Version: ".$curlVersion['version'].")</span>";
					}
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">PHP Extension "openssl"</td>
				<td>
					<?php
					if (!extension_loaded('openssl')) {
						echo "<span class='text-danger'>Not enabled</span>";
					} else {
						echo "<span class='text-success'>Enabled (Version: ".OPENSSL_VERSION_NUMBER.")</span>";
					}
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">PHP Extension "mbstring"</td>
				<td>
					<?php
					if (!extension_loaded('mbstring')) {
						echo "<span class='text-danger'>Not enabled</span>";
					} else {
						echo "<span class='text-success'>Enabled</span>";
					}
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">PHP Extension "iconv"</td>
				<td>
					<?php
					if (!extension_loaded('iconv') && !function_exists('iconv')) {
						echo "<span class='text-danger'>Not enabled</span>";
					} else {
						echo "<span class='text-success'>Enabled</span>";
					}
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">PHP Extension "IMAP"</td>
				<td>
					<?php
					if (!extension_loaded('imap')) {
						echo "<span class='text-danger'>Not enabled</span>";
					} else {
						echo "<span class='text-success'>Enabled</span>";
					}
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">PHP Extension "GD"</td>
				<td>
					<?php
					if (!extension_loaded('gd')) {
						echo "<span class='text-danger'>Not enabled</span>";
					} else {
						echo "<span class='text-success'>Enabled</span>";
					}
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">PHP Extension "zip"</td>
				<td>
					<?php
					if (!extension_loaded('zip')) {
						echo "<span class='text-danger'>Not enabled</span>";
					} else {
						echo "<span class='text-success'>Enabled</span>";
					}
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">MySQL Version</td>
				<td>
					<?php
					echo $this->db->query('SELECT VERSION() as version')->row()->version;
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">MySQL Max Allowed Connections</td>
				<td>
					<?php
					echo $this->db->query("SHOW VARIABLES LIKE 'max_connections'")->row()->Value;
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">Maximum Packet Size</td>
				<td>
					<?php
					echo bytesToSize('', $this->db->query("SHOW VARIABLES LIKE 'max_allowed_packet'")->row()->Value);
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">sql_mode</td>
				<td>
					<?php
					echo $this->db->query('SELECT @@sql_mode as mode')->row()->mode;
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">bcmath</td>
				<td>
					<?php
					echo extension_loaded('bcmath') ? 'Yes' : 'No';
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">max_input_vars</td>
				<td>
					<?php
					$max_input_vars = ini_get('max_input_vars');
					echo $max_input_vars ? $max_input_vars : 'N/A';
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">upload_max_filesize</td>
				<td>
					<?php
					$upload_max_filesize = ini_get('upload_max_filesize');
					echo $upload_max_filesize ? $upload_max_filesize : 'N/A';
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">post_max_size</td>
				<td>
					<?php
					$post_max_size = ini_get('post_max_size');
					echo $post_max_size ? $post_max_size : 'N/A';
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">max_execution_time</td>
				<td>
					<?php
					$execution_time = ini_get('max_execution_time');
					echo $execution_time ? $execution_time : 'N/A';
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">memory_limit</td>
				<td>
					<?php
					$memory = ini_get('memory_limit');
					echo $memory ? $memory : 'N/A';
					if(floatval($memory) < 128) {
						echo '<br /><span class="text-warning">128M is recommended value (or bigger)</span>';
					}
					?>
				</td>

			</tr>
			<tr>
				<td class="bold">allow_url_fopen</td>
				<td>
					<?php
					$url_f_open = ini_get('allow_url_fopen');
					if ($url_f_open != "1"
						&& strcasecmp($url_f_open,'On') != 0
						&& strcasecmp($url_f_open,'true') != 0
						&& strcasecmp($url_f_open,'yes') != 0) {
						echo "<span class='bold'>Allow_url_fopen is not enabled! (Value: $url_f_open)</span>";
				} else {
					echo "<span class='text-success'>Enabled</span>";
				}
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Suhosin</td>
			<td>
				<?php
				if (!extension_loaded('suhosin')) {
					echo "Not using";
				} else {
					echo "Loaded";
				}
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Environment</td>
			<td>
				<?php
				echo ENVIRONMENT;
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Cloudflare</td>
			<td>
				<?php
					$CloudFlareHeader = $this->input->get_request_header('Cf-Ray');
					echo ($CloudFlareHeader && !empty($CloudFlareHeader) ? 'Yes' : 'No');
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">pipe.php permissions</td>
			<td>
				<?php
					echo octal_permissions(fileperms(FCPATH . 'pipe.php'));
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Customers Theme</td>
			<td>
				<?php
					echo active_clients_theme();
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Available customers themes</td>
			<td>
				<?php
					echo implode(', ', get_all_client_themes());
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Cron has run from CLI</td>
			<td>
				<?php
					echo get_option('cron_has_run_from_cli') == 0 ? 'No' : 'Yes';
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">CSRF Enabled</td>
			<td>
				<?php
				echo defined('APP_CSRF_PROTECTION') && defined('APP_CSRF_PROTECTION') ? 'Yes' : 'No';
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Bad User Agent Block Enabled</td>
			<td>
				<?php
					echo defined('APP_BAD_USER_AGENT_BLOCK') && defined('APP_BAD_USER_AGENT_BLOCK') ? 'Yes' : 'No';
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Using my_functions_helper.php</td>
			<td>
				<?php
					echo file_exists(APPPATH.'helpers/my_functions_helper.php') ? 'Yes': 'No';
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Using custom.css</td>
			<td>
				<?php
					echo file_exists(FCPATH.'assets/css/custom.css') ? 'Yes': 'No';
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Last cron run</td>
			<td>
				<?php
					echo !empty(get_option('last_cron_run')) ? time_ago_specific(date('Y-m-d H:i:s', get_option('last_cron_run'))) : 'N/A';
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Total modules</td>
			<td>
				<?php
					echo count($this->app_modules->get());
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Total active modules</td>
			<td>
				<?php
					echo count($this->app_modules->get_activated());
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Total modules require database upgrade</td>
			<td>
				<?php
					echo $this->app_modules->number_of_modules_that_require_database_upgrade();
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Installation PATH</td>
			<td>
				<?php
					echo FCPATH;
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Temp DIR (get_temp_dir())</td>
			<td>
				<?php
					echo get_temp_dir();
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Base URL</td>
			<td>
				<?php
					echo APP_BASE_URL;
				?>
			</td>
		</tr>
		<tr>
			<td class="bold"><b>my_</b> Prefixed View Files</td>
			<td>
				<?php
					$my_prefixed_files = _get_my_prefixed_files();
					if(count($my_prefixed_files) > 0){
						echo implode('<br />', $my_prefixed_files);
					} else {
						echo 'Not using any';
					}
				?>
			</td>
		</tr>
		<tr>
			<td class="bold">Files Permissions</td>
			<td>
				<?php
				$permissionsIssues = false;
				if (!is_writable(FCPATH.'uploads/estimates')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/estimates writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/proposals')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/proposals writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/ticket_attachments')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/ticket_attachments writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/tasks')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/tasks writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/staff_profile_images')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/staff_profile_images writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/projects')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/projects writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/newsfeed')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/newsfeed writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/leads')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/leads writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/invoices')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/invoices writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/expenses')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/expenses writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/discussions')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/discussions writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/contracts')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/contracts writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/company')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/company writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/clients')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/clients writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/credit_notes')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/credit_notes writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'uploads/client_profile_images')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make uploads/client_profile_images writable) - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'application/config')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make application/config/ writable - Permissions 0755</span><br />";
				}
				if (!is_writable(FCPATH.'application/config/config.php')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make application/config/config.php writable) - Permissions 0644</span><br />";
				}
				if (!is_writable(FCPATH.'application/config/app-config.php')){
					$permissionsIssues = true;
					echo "<span class='text-danger'>No (Make application/config/app-config.php writable) - Permissions - 0644</span><br />";
				}
				if(!is_dir(TEMP_FOLDER)){
					$permissionsIssues = true;
					echo '<span class="text-danger">Temporary folder missing, create empty folder temporary folder. (<b>'.TEMP_FOLDER .'</b>)</span><br />';
				} else {
					if (!is_writable(FCPATH.'temp')){
						$permissionsIssues = true;
						echo "<span class='text-danger'>No (Make ".FCPATH."temp folder writable) - Permissions 0755</span><br />";
					}
				}

				hooks()->do_action('after_system_info_files_permissions');

				$permissionsIssues = hooks()->apply_filters('system_info_files_permissions_issue', $permissionsIssues);

				if(!$permissionsIssues) {
					echo 'No files permission issues found';
				}
				?>
			</td>
		</tr>
		<?php hooks()->do_action('after_system_last_info_row'); ?>
	</tbody>
</table>
</div>
<script src="<?php echo base_url('assets/plugins/excellentexport/excellentexport.min.js'); ?>"></script>
<?php
// Internal function and should be used only here because it takes too much memory
function _get_my_prefixed_files() {

	$ci = get_instance();
	$my_prefixed_files = [];
	$view_files = get_dir_contents(APPPATH.'views');
	$modules = $ci->app_modules->get();

	foreach($modules as $module) {
		if(is_dir($module['path'].'views')){
			$view_files = array_merge($view_files, get_dir_contents($module['path'].'views'));
		}
	}

	foreach($view_files as $file) {
		$basename = basename($file);
		if(startsWith($basename,'my_')) {
			$my_prefixed_files[] = $file;
		}
	}

	return $my_prefixed_files;
}
