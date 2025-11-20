<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use BackedEnum;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;
use XetaSuite\Http\Controllers\Api\V1\Auth\Traits\MustSetupPassword;
use XetaSuite\Models\Presenters\UserPresenter;
use XetaSuite\Observers\UserObserver;

#[ObservedBy([UserObserver::class])]
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use HasFactory;
    use MustSetupPassword;
    use HasRoles;
    use Notifiable;
    use UserPresenter;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'password',
        'first_name',
        'last_name',
        'email',
        'office_phone',
        'cell_phone',
        'end_employment_contract',
        'current_site_id'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'avatar',
        'full_name',

        // Session Model
        'online',

        // Role Model
        'level'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'password_setup_at' => 'datetime',
            'password' => 'hashed',
            'last_login_date' => 'datetime',
            'end_employment_contract' => 'datetime'
        ];
    }

    /**
     * Get the cleanings created by the user.
     *
     * @return HasMany
     */
    public function cleanings(): HasMany
    {
        return $this->hasMany(Cleaning::class);
    }

    /**
     * Get the incidents created by the user.
     *
     * @return HasMany
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /**
     * Get the maintenances created by the user.
     *
     * @return HasMany
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    /**
     * Get the maintenances assigned to user thought operators
     *
     * @return BelongsToMany
     */
    public function maintenancesOperators(): BelongsToMany
    {
        return $this->belongsToMany(Maintenance::class)
            ->withTimestamps();
    }

    /**
     * Get the settings for the user.
     *
     * @return MorphMany
     */
    public function settings(): MorphMany
    {
        return $this->morphMany(Setting::class, 'model');
    }

    /**
     * Get the sites assigned to users.
     *
     * @return BelongsToMany
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class)
            ->withTimestamps();
    }

    /**
     * Get the user that deleted the user.
     *
     * @return HasOne
     */
    public function deletedUser(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'deleted_user_id')->withTrashed();
    }

    /**
     * Return the first site id of the user or null if no site.
     *
     * @return null|int
     */
    public function getFirstSiteId(): ?int
    {
        return $this->sites()->value('id');
        /*$id = $this->sites()->first()?->id;

        if (is_null($id)) {
            return null;
        }
        return $id;*/
    }

    /**
     * Get the notifications for the user.
     *
     * @return MorphMany
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->orderBy('read_at', 'asc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Function to assign the given roles to the given sites
     *
     * @param BackedEnum|int|array|string|Collection|Role $roles
     * @param array|int $sites
     *
     * @return User
     */
    public function assignRolesToSites(BackedEnum|Collection|int|array|string|Role $roles, array|int $sites): self
    {
        if (! app(PermissionRegistrar::class)->teams) {
            return $this;
        }

        $sites = Arr::wrap($sites);
        $teamId = getPermissionsTeamId();

        foreach ($sites as $site) {
            setPermissionsTeamId($site);
            $this->assignRole($roles);
        }

        setPermissionsTeamId($teamId);

        return $this;
    }

    /**
     * Function to assign the given roles to the given sites
     *
     * @param BackedEnum|int|array|string|Collection|Permission $permissions
     * @param array|int $sites
     *
     * @return User
     */
    public function assignPermissionsToSites(BackedEnum|Collection|int|array|string|Permission $permissions, array|int $sites): self
    {
        if (! app(PermissionRegistrar::class)->teams) {
            return $this;
        }

        $sites = Arr::wrap($sites);
        $teamId = getPermissionsTeamId();

        foreach ($sites as $site) {
            setPermissionsTeamId($site);
            $this->givePermissionTo($permissions);
        }

        setPermissionsTeamId($teamId);

        return $this;
    }
}
