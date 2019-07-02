<?php

use App\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public $default_permissions = [
        "Manage Users",
        "Manage Permissions",
        "Upload Files",
        "Manage Other Users Files",
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->default_permissions as $default_permission){
            $perm = new Permission();
            $perm->name = $default_permission;
            $perm->save();
        }
    }
}
