<?php

use App\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $administrator = new Role();
        $administrator->name = "Administrator";
        $administrator->save();
        //By default, administrators get all permissions.
        foreach (\App\Permission::all() as $permission){
            $administrator->permissions()->attach($permission);
        }

        $user = new Role();
        $user->name = "End User";
        $user->save();
        $user->permissions()->attach(\App\Permission::whereName("Upload Files")->firstOrFail()->id);
    }
}
