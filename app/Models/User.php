<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * App\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property string|null $api_token
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $lookup_limit
 * @property int $lookup_decay_rate
 * @property bool $is_admin
 * @property-read Collection<int, File> $files
 * @property-read int|null $files_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User whereApiToken($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereLookupLimit($value)
 * @method static Builder<static>|User whereLookupDecayRate($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereIsAdmin($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'api_token', 'lookup_limit', 'lookup_decay_rate', 'is_admin'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'api_token'
    ];

    protected $dates = [
        'email_verified_at',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    public function files(){
        return $this->hasMany(File::class);
    }

    public function setApiTokenAttribute(?string $value): void
    {
        $this->attributes['api_token'] = $value === null ? null : self::hashApiToken($value);
    }

    public static function hashApiToken(string $value): string
    {
        return self::isHashedApiToken($value) ? strtolower($value) : hash('sha256', $value);
    }

    public static function isHashedApiToken(string $value): bool
    {
        return preg_match('/\A[0-9a-f]{64}\z/i', $value) === 1;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}
