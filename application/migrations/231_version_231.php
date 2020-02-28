<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_231 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        if (!table_exists('sales_activity')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'salesactivity RENAME TO ' . db_prefix() . 'sales_activity;');
        }

        if (!table_exists('lead_activity_log')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'leadactivitylog RENAME TO ' . db_prefix() . 'lead_activity_log;');
        }

        if (!table_exists('form_results')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'formresults RENAME TO ' . db_prefix() . 'form_results;');
        }
        if (!table_exists('form_questions')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'formquestions RENAME TO ' . db_prefix() . 'form_questions;');
        }
        if (!table_exists('form_question_box_description')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'formquestionboxesdescription RENAME TO ' . db_prefix() . 'form_question_box_description;');
        }
        if (!table_exists('form_question_box')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'formquestionboxes RENAME TO ' . db_prefix() . 'form_question_box;');
        }

        if (!table_exists('activity_log')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'activitylog RENAME TO ' . db_prefix() . 'activity_log;');
        }
        if (!table_exists('tasks_checklist_templates')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'checkliststemplates RENAME TO ' . db_prefix() . 'tasks_checklist_templates;');
        }
        if (!table_exists('task_checklist_items')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'taskchecklists RENAME TO ' . db_prefix() . 'task_checklist_items;');
        }
        if (!table_exists('web_to_lead')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'webtolead RENAME TO ' . db_prefix() . 'web_to_lead;');
        }
        if (!table_exists('customer_admins')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'customeradmins RENAME TO ' . db_prefix() . 'customer_admins;');
        }
        if (!table_exists('contact_permissions')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'contactpermissions RENAME TO ' . db_prefix() . 'contact_permissions;');
        }
        if (!table_exists('contract_comments')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'contractcomments RENAME TO ' . db_prefix() . 'contract_comments;');
        }
        if (!table_exists('creditnote_refunds')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'creditnoterefunds RENAME TO ' . db_prefix() . 'creditnote_refunds;');
        }
        if (!table_exists('pinned_projects')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'pinnedprojects RENAME TO ' . db_prefix() . 'pinned_projects;');
        }
        if (!table_exists('expenses_categories')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'expensescategories RENAME TO ' . db_prefix() . 'expenses_categories;');
        }
        if (!table_exists('contract_renewals')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'contractrenewals RENAME TO ' . db_prefix() . 'contract_renewals;');
        }
        if (!table_exists('contracts_types')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'contracttypes RENAME TO ' . db_prefix() . 'contracts_types;');
        }
        if (!table_exists('consent_purposes')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'consentpurposes RENAME TO ' . db_prefix() . 'consent_purposes;');
        }
        if (!table_exists('customers_groups')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'customersgroups RENAME TO ' . db_prefix() . 'customers_groups;');
        }
        if (!table_exists('ticket_attachments')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'ticketattachments RENAME TO ' . db_prefix() . 'ticket_attachments;');
        }
        if (!table_exists('tickets_pipe_log')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'ticketpipelog RENAME TO ' . db_prefix() . 'tickets_pipe_log;');
        }
        if (!table_exists('todos')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'todoitems RENAME TO ' . db_prefix() . 'todos;');
        }
        if (!table_exists('user_auto_login')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'userautologin RENAME TO ' . db_prefix() . 'user_auto_login;');
        }
        if (!table_exists('user_meta')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'usermeta RENAME TO ' . db_prefix() . 'user_meta;');
        }
        if (!table_exists('views_tracking')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'viewstracking RENAME TO ' . db_prefix() . 'views_tracking;');
        }
        if (!table_exists('staff_departments')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'staffdepartments RENAME TO ' . db_prefix() . 'staff_departments;');
        }
        if (!table_exists('ticket_replies')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'ticketreplies RENAME TO ' . db_prefix() . 'ticket_replies;');
        }
        if (!table_exists('tickets_predefined_replies')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'predefinedreplies RENAME TO ' . db_prefix() . 'tickets_predefined_replies;');
        }
        if (!table_exists('leads_status')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'leadsstatus RENAME TO ' . db_prefix() . 'leads_status;');
        }
        if (!table_exists('leads_sources')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'leadssources RENAME TO ' . db_prefix() . 'leads_sources;');
        }
        if (!table_exists('proposal_comments')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'proposalcomments RENAME TO ' . db_prefix() . 'proposal_comments;');
        }
        if (!table_exists('payment_modes')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'invoicepaymentsmodes RENAME TO ' . db_prefix() . 'payment_modes;');
        }
        if (!table_exists('gdpr_requests')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'requestsgdpr RENAME TO ' . db_prefix() . 'gdpr_requests;');
        }
        if (!table_exists('dismissed_announcements')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'dismissedannouncements RENAME TO ' . db_prefix() . 'dismissed_announcements;');
        }
        if (!table_exists('leads_email_integration')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'leadsintegration RENAME TO ' . db_prefix() . 'leads_email_integration;');
        }
        if (!table_exists('role_permissions')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'rolepermissions RENAME TO ' . db_prefix() . 'role_permissions;');
        }
        if (!table_exists('knowedge_base_article_feedback')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'knowledgebasearticleanswers RENAME TO ' . db_prefix() . 'knowedge_base_article_feedback;');
        }
        if (!table_exists('staff_permissions')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'staffpermissions RENAME TO ' . db_prefix() . 'staff_permissions;');
        }
        if (!table_exists('tracked_mails')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'emailstracking RENAME TO ' . db_prefix() . 'tracked_mails;');
        }
        if (!table_exists('spam_filters')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'spamfilters RENAME TO ' . db_prefix() . 'spam_filters;');
        }
        if (!table_exists('customer_groups')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'customergroups_in RENAME TO ' . db_prefix() . 'customer_groups;');
        }
        if (!table_exists('project_activity')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'projectactivity RENAME TO ' . db_prefix() . 'project_activity;');
        }
        if (!table_exists('project_notes')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'projectnotes RENAME TO ' . db_prefix() . 'project_notes;');
        }
        if (!table_exists('project_files')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'projectfiles RENAME TO ' . db_prefix() . 'project_files;');
        }
        if (!table_exists('project_settings')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'projectsettings RENAME TO ' . db_prefix() . 'project_settings;');
        }
        if (!table_exists('mail_queue')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'emailqueue RENAME TO ' . db_prefix() . 'mail_queue;');
        }
        if (!table_exists('newsfeed_post_comments')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'postcomments RENAME TO ' . db_prefix() . 'newsfeed_post_comments;');
        }
        if (!table_exists('newsfeed_comment_likes')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'commentlikes RENAME TO ' . db_prefix() . 'newsfeed_comment_likes;');
        }
        if (!table_exists('newsfeed_posts')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'posts RENAME TO ' . db_prefix() . 'newsfeed_posts;');
        }
        if (!table_exists('newsfeed_post_likes')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'postlikes RENAME TO ' . db_prefix() . 'newsfeed_post_likes;');
        }
        if (!table_exists('related_items')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'itemsrelated RENAME TO ' . db_prefix() . 'related_items;');
        }
        if (!table_exists('item_tax')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'itemstax RENAME TO ' . db_prefix() . 'item_tax;');
        }
        if (!table_exists('taggables')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'tags_in RENAME TO ' . db_prefix() . 'taggables;');
        }
        if (!table_exists('itemable')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'items_in RENAME TO ' . db_prefix() . 'itemable;');
        }
        if (!table_exists('task_assigned')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'stafftaskassignees RENAME TO ' . db_prefix() . 'task_assigned;');
        }
        if (!table_exists('task_comments')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'stafftaskcomments RENAME TO ' . db_prefix() . 'task_comments;');
        }
        if (!table_exists('task_followers')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'stafftasksfollowers RENAME TO ' . db_prefix() . 'task_followers;');
        }

        if (!table_exists('shared_customer_files')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'customerfiles_shares RENAME TO ' . db_prefix() . 'shared_customer_files;');
        }

        if (!table_exists('knowledge_base_groups')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'knowledgebasegroups RENAME TO ' . db_prefix() . 'knowledge_base_groups;');
        }

        if (!table_exists('knowledge_base')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'knowledgebase RENAME TO ' . db_prefix() . 'knowledge_base;');
        }

        if (!table_exists('tasks')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'stafftasks RENAME TO ' . db_prefix() . 'tasks;');
        }

        if (!table_exists('lead_integration_emails')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'leadsemailintegrationemails RENAME TO ' . db_prefix() . 'lead_integration_emails;');
        }

        if (!table_exists('tickets_priorities')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'priorities RENAME TO ' . db_prefix() . 'tickets_priorities;');
        }

        if (!table_exists('project_members')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'projectmembers RENAME TO ' . db_prefix() . 'project_members;');
        }

        if (!table_exists('tickets_status')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'ticketstatus RENAME TO ' . db_prefix() . 'tickets_status;');
        }

        $checkFiles = [
            [
                'find'    => 'checkliststemplates',
                'replace' => 'tasks_checklist_templates',
                'path'    => VIEWPATH . 'admin/tasks/checklist_items_template.php',
            ],
             [
                'find'    => "taskchecklists',",
                'replace' => "task_checklist_items',",
                'path'    => VIEWPATH . 'admin/tasks/task.php',
            ],
             [
                'find'    => 'customeradmins WHERE ',
                'replace' => 'customer_admins WHERE ',
                'path'    => VIEWPATH . 'admin/clients/manage.php',
            ],
             [
                'find'    => 'ticketstatus ON',
                'replace' => 'tickets_status ON',
                'path'    => VIEWPATH . 'admin/tables/tickets.php',
            ],
              [
                'find'    => 'ticketstatus.',
                'replace' => 'tickets_status.',
                'path'    => VIEWPATH . 'admin/tables/tickets.php',
            ],
            [
                'find'    => 'customeradmins WHERE ',
                'replace' => 'customer_admins WHERE ',
                'path'    => VIEWPATH . 'admin/tables/clients.php',
            ],
              [
                'find'    => 'projectsettings WHERE',
                'replace' => 'project_settings WHERE',
                'path'    => VIEWPATH . 'admin/tables/tasks.php',
            ],
             [
                'find'    => 'stafftaskassignees',
                'replace' => 'task_assigned',
                'path'    => VIEWPATH . 'admin/tables/tasks.php',
            ],
             [
                'find'    => 'stafftaskassignees',
                'replace' => 'task_assigned',
                'path'    => VIEWPATH . 'admin/tables/tasks_relations.php',
            ],
            [
                'find'    => 'stafftaskassignees',
                'replace' => 'task_assigned',
                'path'    => VIEWPATH . 'admin/tables/timesheets.php',
            ],
            [
                'find'    => 'stafftaskassignees',
                'replace' => 'task_assigned',
                'path'    => VIEWPATH . 'admin/tables/task.php',
            ],
            [
                'find'    => 'stafftasksfollowers',
                'replace' => 'task_followers',
                'path'    => VIEWPATH . 'admin/tasks/task.php',
            ],
             [
                'find'    => 'customergroups_in',
                'replace' => 'customer_groups',
                'path'    => VIEWPATH . 'admin/tables/clients.php',
            ],
            [
                'find'    => 'customeradmins WHERE ',
                'replace' => 'customer_admins WHERE ',
                'path'    => VIEWPATH . 'admin/tables/all_contacts.php',
            ],
            [
                'find'    => "pinnedprojects',",
                'replace' => "pinned_projects',",
                'path'    => VIEWPATH . 'admin/projects/view.php',
            ],
             [
                'find'    => "projectsettings',",
                'replace' => "project_settings',",
                'path'    => VIEWPATH . 'admin/tasks/task.php',
            ],
            [
                'find'    => 'customersgroups',
                'replace' => 'customers_groups',
                'path'    => VIEWPATH . 'admin/tables/clients.php',
            ],
            [
                'find'    => 'customersgroups',
                'replace' => 'customers_groups',
                'path'    => VIEWPATH . 'admin/tables/customers_groups.php',
            ],
             [
                'find'    => "viewstracking',",
                'replace' => "views_tracking',",
                'path'    => VIEWPATH . 'admin/knowledge_base/article.php',
            ],
            [
                'find'    => 'viewstracking WHERE',
                'replace' => 'views_tracking WHERE',
                'path'    => VIEWPATH . 'admin/knowledge_base/articles.php',
            ],
            [
                'find'    => 'staffdepartments WHERE',
                'replace' => 'staff_departments WHERE',
                'path'    => VIEWPATH . 'admin/tables/tickets.php',
            ],
            [
                'find'    => 'staffdepartments WHERE',
                'replace' => 'staff_departments WHERE',
                'path'    => VIEWPATH . 'admin/tickets/summary.php',
            ],
           [
                'find'    => 'leadsstatus.',
                'replace' => 'leads_status.',
                'path'    => VIEWPATH . 'admin/tables/leads.php',
            ],
            [
                'find'    => 'leadsstatus ON',
                'replace' => 'leads_status ON',
                'path'    => VIEWPATH . 'admin/tables/leads.php',
            ],
             [
                'find'    => 'leadssources.',
                'replace' => 'leads_sources.',
                'path'    => VIEWPATH . 'admin/tables/leads.php',
            ],
            [
                'find'    => 'leadssources ON',
                'replace' => 'leads_sources ON',
                'path'    => VIEWPATH . 'admin/tables/leads.php',
            ],
            [
                'find'    => "proposalcomments',",
                'replace' => "proposal_comments',",
                'path'    => VIEWPATH . 'admin/proposals/pipeline/_kanban_card.php',
            ],
            [
                'find'    => 'invoicepaymentsmodes',
                'replace' => 'payment_modes',
                'path'    => VIEWPATH . 'admin/tables/payments.php',
            ],
             [
                'find'    => 'staffpermissions',
                'replace' => 'staff_permissions',
                'path'    => VIEWPATH . 'admin/tables/payments.php',
            ],
               [
                'find'    => "rolepermissions',",
                'replace' => "role_permissions',",
                'path'    => VIEWPATH . 'admin/roles/role.php',
            ],
            [
                'find'    => "knowledgebasearticleanswers',",
                'replace' => "knowedge_base_article_feedback',",
                'path'    => VIEWPATH . 'admin/reports/knowledge_base_articles.php',
            ],
             [
                'find'    => "tblticketsspamcontrol',",
                'replace' => "tblspamfilters',",
                'path'    => VIEWPATH . 'admin/tickets/single.php',
            ],
            [
                'find'    => "'tblspamfilters',",
                'replace' => "db_prefix().'spam_filters',",
                'path'    => VIEWPATH . 'admin/tickets/single.php',
            ],
            [
                'find'    => "spamfilters',",
                'replace' => "spam_filters',",
                'path'    => VIEWPATH . 'admin/tickets/single.php',
            ],
             [
                'find'    => 'priorities',
                'replace' => 'tickets_priorities',
                'path'    => VIEWPATH . 'admin/tables/tickets.php',
            ],
        ];

        $tables = [
            'estimates.php',
            'invoices.php',
            'leads.php',
            'projects.php',
            'proposals.php',
            'proposals_relations.php',
            'staff_timesheets.php',
            'tasks.php',
            'tasks_relations.php',
            'tickets.php',
            'timesheets.php',
        ];

        foreach ($tables as $tags_replace) {
            $checkFiles[] = [
                'find'    => 'tags_in',
                'replace' => 'taggables',
                'path'    => VIEWPATH . 'admin/tables/' . $tags_replace,
            ];
        }

        $tasksTableReplace = [
            VIEWPATH . 'admin/dashboard/widgets/top_stats.php',
            VIEWPATH . 'admin/projects/export_data_pdf.php',
            VIEWPATH . 'admin/projects/project.php',
            VIEWPATH . 'admin/tables/all_reminders.php',
            VIEWPATH . 'admin/tables/includes/tasks_filter.php',
            VIEWPATH . 'admin/tables/milestones.php',
            VIEWPATH . 'admin/tables/staff_reminders.php',
            VIEWPATH . 'admin/tables/staff_timesheets.php',
            VIEWPATH . 'views/admin/tables/tasks.php',
            VIEWPATH . 'admin/tables/tasks_relations.php',
            VIEWPATH . 'admin/tables/timesheets.php',
            VIEWPATH . 'themes/perfex/template_parts/projects/project_tasks.php',
            VIEWPATH . 'themes/' . active_clients_theme() . '/template_parts/projects/project_tasks.php',
        ];

        $tasksTableReplace = array_unique($tasksTableReplace);

        foreach ($tasksTableReplace as $path) {
            $checkFiles[] = [
                'find'    => 'stafftasks',
                'replace' => 'tasks',
                'path'    => $path,
            ];
        }

        $projectMembersReplace = [
            VIEWPATH . 'admin/clients/groups/projects.php',
            VIEWPATH . 'admin/dashboard/widgets/top_stats.php',
            VIEWPATH . 'admin/projects/manage.php',
            VIEWPATH . 'admin/tables/projects.php',
            VIEWPATH . 'admin/tables/staff_projects.php',
            VIEWPATH . 'admin/tasks/view_task_template.php',
        ];
        foreach ($projectMembersReplace as $path) {
            $checkFiles[] = [
                'find'    => 'projectmembers',
                'replace' => 'project_members',
                'path'    => $path,
            ];
        }

        foreach ($checkFiles as $check) {
            $basename   = basename($check['path']);
            $my_name    = 'my_' . $basename;
            $fullPathMy = strbefore($check['path'], $basename) . $my_name;

            // $fullPathMy = $check['path'];

            if (file_exists($fullPathMy)) {
                $success = replace_in_file($fullPathMy, $check['find'], $check['replace']);
            }
        }

        $updatedTables = [
'sales_activity'                 => 'salesactivity',
'lead_activity_log'              => 'leadactivitylog',
'activity_log'                   => 'activitylog',
'tasks_checklist_templates'      => 'checkliststemplates',
'task_checklist_items'           => 'taskchecklists',
'web_to_lead'                    => 'webtolead',
'customer_admins'                => 'customeradmins',
'contact_permissions'            => 'contactpermissions',
'contract_comments'              => 'contractcomments',
'creditnote_refunds'             => 'creditnoterefunds',
'pinned_projects'                => 'pinnedprojects',
'expenses_categories'            => 'expensescategories',
'contract_renewals'              => 'contractrenewals',
'contracts_types'                => 'contracttypes',
'consent_purposes'               => 'consentpurposes',
'customers_groups'               => 'customersgroups',
'ticket_attachments'             => 'ticketattachments',
'tickets_pipe_log'               => 'ticketpipelog',
'todos'                          => 'todoitems',
'user_auto_login'                => 'userautologin',
'user_meta'                      => 'usermeta',
'views_tracking'                 => 'viewstracking',
'staff_departments'              => 'staffdepartments',
'ticket_replies'                 => 'ticketreplies',
'tickets_predefined_replies'     => 'predefinedreplies',
'leads_status'                   => 'leadsstatus',
'leads_sources'                  => 'leadssources',
'proposal_comments'              => 'proposalcomments',
'payment_modes'                  => 'invoicepaymentsmodes',
'gdpr_requests'                  => 'requestsgdpr',
'dismissed_announcements'        => 'dismissedannouncements',
'leads_email_integration'        => 'leadsintegration',
'role_permissions'               => 'rolepermissions',
'knowedge_base_article_feedback' => 'knowledgebasearticleanswers',
'staff_permissions'              => 'staffpermissions',
'tracked_mails'                  => 'emailstracking',
'spam_filters'                   => 'spamfilters',
'customer_groups'                => 'customergroups_in',
'project_activity'               => 'projectactivity',
'project_notes'                  => 'projectnotes',
'project_files'                  => 'projectfiles',
'project_settings'               => 'projectsettings',
'mail_queue'                     => 'emailqueue',
'newsfeed_post_comments'         => 'postcomments',
'newsfeed_comment_likes'         => 'commentlikes',
'newsfeed_posts'                 => 'posts',
'newsfeed_post_likes'            => 'postlikes',
'related_items'                  => 'itemsrelated',
'item_tax'                       => 'itemstax',
'taggables'                      => 'tags_in',
'itemable'                       => 'items_in',
'task_assigned'                  => 'stafftaskassignees',
'task_comments'                  => 'stafftaskcomments',
'task_followers'                 => 'stafftasksfollowers',
'shared_customer_files'          => 'customerfiles_shares',
'knowledge_base_groups'          => 'knowledgebasegroups',
'knowledge_base'                 => 'knowledgebase',
'tasks'                          => 'stafftasks',
'lead_integration_emails'        => 'leadsemailintegrationemails',
'tickets_priorities'             => 'priorities',
'project_members'                => 'projectmembers',
'tickets_status'                 => 'ticketstatus',
'form_results'                   => 'formresults',
'form_questions'                 => 'formquestions',
'form_question_box_description'  => 'formquestionboxesdescription',
'form_question_box'              => 'formquestionboxes',
];
        if (db_prefix() == 'tbl' && file_exists(APPPATH . 'helpers/my_functions_helper.php')) {
            foreach ($updatedTables as $new => $old) {
                @replace_in_file(APPPATH . 'helpers/my_functions_helper.php', 'tbl' . $old, 'tbl' . $new);
            }
        }

        @replace_in_file(APPPATH . 'config/app-config.php', 'tblsessions', 'sessions');
    }
}
