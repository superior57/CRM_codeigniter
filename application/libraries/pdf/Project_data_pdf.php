<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(__DIR__ . '/App_pdf.php');

class Project_data_pdf extends App_pdf
{
    protected $project_id;

    public function __construct($project_id)
    {
        parent::__construct();

        $this->project_id = $project_id;
    }

    public function prepare()
    {
        $project = $this->ci->projects_model->get($this->project_id);
        $this->SetTitle($project->name);
        $members                = $this->ci->projects_model->get_project_members($project->id);
        $project->currency_data = $this->ci->projects_model->get_currency($project->id);

        // Add <br /> tag and wrap over div element every image to prevent overlaping over text
        $project->description = preg_replace('/(<img[^>]+>(?:<\/img>)?)/i', '<br><br><div>$1</div><br><br>', $project->description);

        $data['project']    = $project;
        $data['milestones'] = $this->ci->projects_model->get_milestones($project->id);
        $data['timesheets'] = $this->ci->projects_model->get_timesheets($project->id);

        $data['tasks']             = $this->ci->projects_model->get_tasks($project->id, [], false);
        $data['total_logged_time'] = seconds_to_time_format($this->ci->projects_model->total_logged_time($project->id));
        if ($project->deadline) {
            $data['total_days'] = round((human_to_unix($project->deadline . ' 00:00') - human_to_unix($project->start_date . ' 00:00')) / 3600 / 24);
        } else {
            $data['total_days'] = '/';
        }
        $data['total_members'] = count($members);
        $data['total_tickets'] = total_rows(db_prefix().'tickets', [
                'project_id' => $project->id,
            ]);
        $data['total_invoices'] = total_rows(db_prefix().'invoices', [
                'project_id' => $project->id,
            ]);

        $this->ci->load->model('invoices_model');

        $data['invoices_total_data'] = $this->ci->invoices_model->get_invoices_total([
                'currency'   => $project->currency_data->id,
                'project_id' => $project->id,
            ]);

        $data['total_milestones']     = count($data['milestones']);
        $data['total_files_attached'] = total_rows(db_prefix().'project_files', [
                'project_id' => $project->id,
            ]);
        $data['total_discussion'] = total_rows(db_prefix().'projectdiscussions', [
                'project_id' => $project->id,
            ]);
        $data['members'] = $members;

        $this->set_view_vars($data);

        return $this->build();
    }

    protected function type()
    {
        return 'project-data';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/admin/projects/my_export_data_pdf.php';
        $actualPath = APPPATH . 'views/admin/projects/export_data_pdf.php';

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }

    public function get_format_array()
    {
        return  [
            'orientation' => 'L',
            'format'      => 'landscape',
        ];
    }
}
