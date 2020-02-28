<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_233 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        $this->db->query('ALTER TABLE `' . db_prefix() . 'customer_admins` CHANGE `date_assigned` `date_assigned` DATETIME NOT NULL;');

        if (table_exists('role_permissions')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'roles` ADD `permissions` LONGTEXT NULL AFTER `name`;');

            $updateRolesPermissions = [];
            $rolesPermissions       = $this->db->get('role_permissions')->result_array();
            foreach ($rolesPermissions as $permission) {
                if (!isset($updateRolesPermissions[$permission['roleid']])) {
                    $updateRolesPermissions[$permission['roleid']] = [];
                }
                $this->db->where('permissionid', $permission['permissionid']);
                $dbPermission = $this->db->get('permissions')->row();

                if (!isset($updateRolesPermissions[$permission['roleid']][$dbPermission->shortname])) {
                    $updateRolesPermissions[$permission['roleid']][$dbPermission->shortname] = [];
                }

                $newPermissions = [];
                if ($permission['can_view'] == 1) {
                    $newPermissions[] = 'view';
                }

                if ($permission['can_view_own'] == 1) {
                    $newPermissions[] = 'view_own';
                }

                if ($permission['can_edit'] == 1) {
                    $newPermissions[] = 'edit';
                }
                if ($permission['can_create'] == 1) {
                    $newPermissions[] = 'create';
                }

                if ($permission['can_delete'] == 1) {
                    $newPermissions[] = 'delete';
                }

                if (count($newPermissions)) {
                    $updateRolesPermissions[$permission['roleid']][$dbPermission->shortname] = $newPermissions;
                } else {
                    unset($updateRolesPermissions[$permission['roleid']][$dbPermission->shortname]);
                }
            }

            foreach ($updateRolesPermissions as $role_id => $permissions) {
                $this->db->where('roleid', $role_id);
                $this->db->update('roles', ['permissions' => serialize($permissions)]);
            }

            $this->dbforge->drop_table('role_permissions', true);
        }

        if (table_exists('permissions')) {
            $this->db->join('permissions', db_prefix() . 'permissions.permissionid=' . db_prefix() . 'staff_permissions.permissionid');
            $staff_permissions = $this->db->get('staff_permissions')->result_array();

            $newPermissions = [];
            foreach ($staff_permissions as $permission) {
                $array = [];

                if ($permission['can_view'] == 1) {
                    $array[] = ['capability' => 'view', 'feature' => $permission['shortname'], 'staff_id' => $permission['staffid']];
                }

                if ($permission['can_view_own'] == 1) {
                    $array[] = ['capability' => 'view_own', 'feature' => $permission['shortname'], 'staff_id' => $permission['staffid']];
                }

                if ($permission['can_edit'] == 1) {
                    $array[] = ['capability' => 'edit', 'feature' => $permission['shortname'], 'staff_id' => $permission['staffid']];
                }

                if ($permission['can_create'] == 1) {
                    $array[] = ['capability' => 'create', 'feature' => $permission['shortname'], 'staff_id' => $permission['staffid']];
                }

                if ($permission['can_delete'] == 1) {
                    $array[] = ['capability' => 'delete', 'feature' => $permission['shortname'], 'staff_id' => $permission['staffid']];
                }

                if (count($array) > 0) {
                    $newPermissions[] = $array;
                }
            }

            $this->dbforge->drop_table('staff_permissions', true);

            $this->db->query('CREATE TABLE `' . db_prefix() . 'staff_permissions` (
              `staff_id` int(11) NOT NULL,
              `feature` varchar(40) NOT NULL,
              `capability` varchar(100) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $this->db->char_set . ';');

            foreach ($newPermissions as $insert) {
                $this->db->insert_batch('staff_permissions', $insert);
            }

            $this->dbforge->drop_table('permissions', true);
        }
    }
}
