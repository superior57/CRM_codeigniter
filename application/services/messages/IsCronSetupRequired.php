<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractMessage;

class IsCronSetupRequired extends AbstractMessage
{
    private $used_features = [];

    public function isVisible()
    {
        if (get_option('cron_has_run_from_cli') == 1 || !is_admin()) {
            return false;
        }

        $used_features       = [];
        $using_cron_features = 0;
        $feature             = total_rows(db_prefix() . 'reminders');
        $using_cron_features += $feature;
        if ($feature > 0) {
            array_push($used_features, 'Reminders');
        }

        $feature = get_option('email_queue_enabled');
        $using_cron_features += $feature;
        if ($feature == 1) {
            array_push($used_features, 'Email Queue');
        }

        $feature = total_rows(db_prefix() . 'leads_email_integration', [
                'active' => 1,
            ]);
        $using_cron_features += $feature;

        if ($feature > 0) {
            array_push($used_features, 'Auto importing leads from email.');
        }
        $feature = total_rows(db_prefix() . 'invoices', [
                'recurring >' => 0,
            ]);
        $using_cron_features += $feature;
        if ($feature > 0) {
            array_push($used_features, 'Recurring Invoices');
        }
        $feature = total_rows(db_prefix() . 'expenses', [
                'recurring' => 1,
            ]);
        $using_cron_features += $feature;
        if ($feature > 0) {
            array_push($used_features, 'Recurring Expenses');
        }

        $feature = total_rows(db_prefix() . 'tasks', [
                'recurring' => 1,
            ]);
        $using_cron_features += $feature;
        if ($feature > 0) {
            array_push($used_features, 'Recurring Tasks');
        }

        $feature = total_rows(db_prefix() . 'events');
        $using_cron_features += $feature;

        if ($feature > 0) {
            array_push($used_features, 'Custom Calendar Events');
        }

        $feature = total_rows(db_prefix() . 'departments', [
                'host !='     => '',
                'password !=' => '',
                'email !='    => '',
            ]);
        $using_cron_features += $feature;
        if ($feature > 0) {
            array_push($used_features, 'Auto Import Tickets via method IMAP (Setup->Support->Departments)');
        }

        $using_cron_features = hooks()->apply_filters('numbers_of_features_using_cron_job', $using_cron_features);
        $used_features       = hooks()->apply_filters('used_cron_features', $used_features);
        $this->used_features = $used_features;

        return $using_cron_features > 0 && get_option('hide_cron_is_required_message') == 0;
    }

    public function getMessage()
    {
        $html = '';
        $html .= 'You are using some features that requires cron job setup to work properly.';
        $html .= '<br />Please follow the cron <a href="https://help.perfexcrm.com/setup-cron-job/" target="_blank">setup guide</a> in order all features to work well.';
        $html .= '<br /><br /><br />';
        $html .= '<p class="bold">You are using the following features that CRON Job setup is required:</p>';
        $i = 1;
        foreach ($this->used_features as $feature) {
            $html .= '&nbsp;' . $i . '. ' . $feature . '<br />';
            $i++;
        }
        $html .= '<br /><br /><a href="' . admin_url('misc/dismiss_cron_setup_message') . '" class="alert-link">Don\'t show this message again</a>';

        return $html;
    }
}
