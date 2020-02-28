<?php

namespace app\services\upgrade;

defined('BASEPATH') or exit('No direct script access allowed');

trait Response
{
    protected function failedExtractException($zipFile, $upgradeCopyLocation)
    {
        hooks()->do_action('auto_upgrade_failed_to_extract_zip_file', $zipFile);

        $message = '<h4>Failed to extract downloaded zip file.</h4>';
        $message .= $this->getFileOwnersMessage();

        if ($upgradeCopyLocation) {
            $message .= '<hr /><p>The upgrade files are copied to <b>' . $upgradeCopyLocation . '</b> and will be <b>available for the next ' . (_delete_temporary_files_older_then() / 60) . ' minutes</b> so you can try to <b>extract them manually</b> e.q. via cPanel or command line, use the best method that is suitable for you. <br /><br /><b>Don\'t forget that you must extract the contents of the ' . basename($upgradeCopyLocation) . ' file in ' . $this->config->extract_to . '</b></p>';
        }

        throw new \Exception($message);
    }

    protected function getErrorByStatusCode($statusCode)
    {
        $error = '';
        if ($statusCode == 499) {
            $mailBody = 'Hello. I tried to upgrade to the latest version but for some reason the upgrade failed. Please remove the key from the upgrade log so i can try again. My installation URL is: ' . site_url() . '. Regards.';

            $mailSubject = 'Purchase Key Removal Request - [' . $this->config->purchase_key . ']';

            $error = 'Purchase key already used to download upgrade files for version ' . wordwrap($this->config->latest_version, 1, '.', true) . '. Performing multiple auto updates to the latest version with one purchase key is not allowed. If you have multiple installations you must buy another license.<br /><br /> If you have staging/testing installation and auto upgrade is performed there, <b>you should perform manually upgrade</b> in your production area<br /><br /> <h4 class="bold">Upgrade failed?</h4> The error can be shown also if the update failed for some reason, but because the purchase key is already used to download the files, you won’t be able to re-download the files again.<br /><br />Click <a href="mailto:upgrade@perfexcrm.com?subject=' . $mailSubject . '&body=' . $mailBody . '"><b>here</b></a> to send an mail and get your purchase key removed from the upgrade log.';
        } elseif ($statusCode == 498) {
            $error = 'Invalid Purchase Key.';
        } elseif ($statusCode == 497) {
            $error = 'Purchase Key Empty.';
        }

        return $error;
    }

    protected function getFileOwnersMessage()
    {
        return  '<p>This can happen if your files and folders owner and groups are incorrect, make sure that files belong to the correct web server group and owner, this is usually <b>www-data:www-data</b></p>';
    }
}
