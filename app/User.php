<?php

namespace App;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use App\Models\tbl_branches;
use Auth;

class User extends Authenticatable implements JWTSubject //, MustVerifyEmail
{
    use HasRoles;
 
    protected $guarded = ['id'];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'photo_url','permissionslist','branch_details','roleslist'
    ];

    /**
     * Get the profile photo URL attribute.
     *
     * @return string
     */
    public function getPhotoUrlAttribute()
    {
        return 'https://www.gravatar.com/avatar/'.md5(strtolower($this->email)).'.jpg?s=200&d=mm';
    }

    /**
     * Get the oauth providers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function oauthProviders()
    {
        return $this->hasMany(OAuthProvider::class);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * @return int
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function routeNotificationForNexmo($notification)
    {
        return '';
    }
    public function getPermissionslistAttribute()
    {
        return   Auth::user()->getDirectPermissions()->pluck('name');
    }
    public function getRoleslistAttribute()
    {
        return   Auth::user()->roles;
    }
    public function getBranchDetailsAttribute()
    {
        return    tbl_branches::where("id", $this->branch)->first();
    }


    public static function getUserPermissions($user)
    {
        // $userPermissions = $user->getAllPermissions();
        $userPermissions = DB::table('model_has_roles as a')
        ->select('c.name')
        ->leftJoin('role_has_permissions as b', 'a.role_id', '=', 'b.role_id')
        ->leftJoin('permissions as c', 'b.permission_id', '=', 'c.id')
        ->where('a.model_id', $user->id)
        ->get();
        $permissions = [];
        foreach ($userPermissions as $userPermission) {
            $permissions[$userPermission->name] = 1;
        }

        return $permissions;
    }

    public static function getUserRoles($user)
    {
        // $user_roles = $user->roles;
        $user_roles = DB::table('model_has_roles as a')
        ->leftJoin('roles as b', 'a.role_id', '=', 'b.id')
        ->where('a.model_id', $user->id)
        ->get();
        $roles = [];
        foreach ($user_roles as $user_role) {
            $roles[$user_role->name] = 1;
        }

        return $roles;
    }

    /**
     * retrieve date format
     * of app.
     *
     */
    public static function appDateFormat()
    {
        // $value = System::where('key', 'date_format')->value('value');
        $value = 'm/d/Y';
        if ($value == 'd-m-Y') {
            return ['KEY' => 'd-m-Y', 'VALUE' => 'DD-MM-YYYY'];
        } elseif ($value == 'm-d-Y') {
            return ['KEY' => 'm-d-Y', 'VALUE' => 'MM-DD-YYYY'];
        } elseif ($value == 'd/m/Y') {
            return ['KEY' => 'd/m/Y', 'VALUE' => 'DD/MM/YYYY'];
        } elseif ($value == 'm/d/Y') {
            return ['KEY' => 'm/d/Y', 'VALUE' => 'MM/DD/YYYY'];
        }
    }

    /**
     * retrieve time format
     * of app.
     *
     */
    public static function appTimeFormat()
    {
        // $value = System::where('key', 'time_format')->value('value');
        $value = '12';
        return $value;
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }
}
