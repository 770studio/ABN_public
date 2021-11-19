<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    protected $fillable = ['title','display_name','description'];


    //связь с ролями
    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'permission_role',
            'role_id',
            'permission_id'
        );
    }


}
