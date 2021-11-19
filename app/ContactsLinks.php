<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class ContactsLinks extends Model
{

    protected $table = 'links_report_portal';
   // protected $primaryKey = 'lead_id';



    public function Contact()
    {  //
        return $this->hasOne('App\Contact', 'contact_id', 'contact_id')  ;
    }


}
