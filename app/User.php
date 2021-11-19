<?php

namespace App;

use App\Notifications\PasswordReset;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

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

    //связь юзера с ролью
    public function roles(){
        return $this->belongsToMany(
            Role::class,
            'role_user',
            'user_id',
            'role_id'
        );
    }

    //устанавливаем роль для пользователя
    public function setRole($id){
        if($id == null){return;}

        $this->roles()->sync($id);
    }

    //получаем название роли
    public function getRoleDisplayName(){

        if(!$this->roles->isEmpty()){
            return implode(', ' ,$this->roles->pluck('display_name')->all());
        }
        return 'Нет роли';
    }

    //получаем роль
    public function getRoleId(){

        if(!$this->roles->isEmpty()){
            return implode(', ' ,$this->roles->pluck('id')->all());
        }
        return null;
    }

    public function sendPasswordResetNotification($token){
       $this->notify(new PasswordReset($token));
    }

}
