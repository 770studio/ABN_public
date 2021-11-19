<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $fillable = ['title','display_name','description'];


    //связь с правами доступа
    public function permissions(){
        return $this->belongsToMany(
            Permission::class,
            'permission_role',
            'role_id',
            'permission_id'
        );
    }

    //берем отображаемое имя разрешения
    public function getPermissionDisplayName(){

        if(!$this->permissions->isEmpty()){
            return implode(', ' ,$this->permissions->sortBy('id')->pluck('display_name')->all());
        }
        return 'Нет разрешений';
    }

    //присвоить роли разрешение
    public function setPermissions($ids)
    {
        if($ids == null){ return;}

        $this->permissions()->sync($ids);
    }

    public function getPermissionIds()
    {

        if (!$this->permissions->isEmpty()) {

            //return $this->permissions->pluck('id')->all();
            return implode(', ', $this->permissions->pluck('id')->all());
        }
        return 'Нет разрешений';
    }

    public function IsAbleToEditSchedule()
    {

        if (!$this->permissions->isEmpty()) {

           $permissions =  implode(', ', $this->permissions->pluck('id')->all());



            if(strpos($permissions, '10') !== false){
                return 'yes';
            }
            else{

                return 'no';
            }

        }
        return 'Нет разрешений';
    }
}
