<?php

namespace App;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\UserRole
 *
 * @method static Builder|Role newModelQuery()
 * @method static Builder|Role newQuery()
 * @method static Builder|Role query()
 * @mixin \Eloquent
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Permission[] $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 * @method static Builder|Role whereCreatedAt($value)
 * @method static Builder|Role whereId($value)
 * @method static Builder|Role whereName($value)
 * @method static Builder|Role whereUpdatedAt($value)
 */
class Role extends Model
{
    protected $table = "user_role";
    protected $fillable = ['name'];

    public function permissions(){
        return $this->belongsToMany(Permission::class, 'user_role_permission','role_id', 'permission_id')->withTimestamps();
    }

    public function users(){
        return $this->belongsToMany(User::class);
    }

    public function hasPermission($permission){
        foreach ($this->permissions as $existing_permission){
            if($permission === $existing_permission->name){
                return true;
            }
        }
        return false;
    }
}
