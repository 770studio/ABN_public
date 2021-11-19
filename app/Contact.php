<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Contact extends Model
{

    protected $table = 'contacts';
    protected $primaryKey = 'contact_id';


    public function ContactsLink()
    {
        return $this->belongsTo('App\ContactsLinks', 'contact_id', 'contact_id');
    }
}
