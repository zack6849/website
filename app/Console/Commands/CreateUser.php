<?php

namespace App\Console\Commands;

use App\Role;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $user = new User();
        $fillables = $user->getFillable();
        foreach ($fillables as $key => $fillable){
            if ($fillable == 'password') {
                $user->password = Hash::make($this->secret(($key+1) . "/" . count($fillables) . " User $fillable"));
            } else {
                $user->$fillable = $this->ask(($key+1) . "/" . count($fillables) . " User $fillable");
            }
        }
        $roles = Role::all();
        $role_names = array();
        foreach ($roles as $role){
            array_push($role_names, $role->name);
        }
        $role_name = $this->choice("Role", $role_names, "End User");
        //this feels wrong, but it works?
        $user->role_id = Role::whereName($role_name)->firstOrFail()->id;
        if($this->confirm("Confirm create user with above information?", true)){
            $user->save();
            $this->info("Created user ID {$user->id}");
        }
        return true;
    }
}
