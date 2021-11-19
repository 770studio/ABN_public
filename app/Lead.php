<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class Lead extends Model
{

    protected $table = 'lead_params';
    protected $primaryKey = 'lead_id';

    function  scopeGetWithJoins($q, $where = [], $fields = false) {


        $q ->select(DB::raw('lead_params.*,
                                    leads.contract_sum,
                                    lead_stages_codes.stage_name ,
                                    object_params.address, object_params.complex, object_params.owner, object_params.total_area,
                                    abned_users.user_name as manager_name, abned_users.email  as manager_email, abned_users.department as manager_department,
                                    instalments.comments, instalments.ttl_area, instalments.nds, IF(instalments.instalment_sum, instalments.instalment_sum, leads.contract_sum)  as instalment_sum,
                                    instalments.add_cost, instalments.total_sum,
                                    instalments.penalty_type,  instalments.penalty_value,
                                    contacts.name as client_name
                                 ')
            )
            ->join('leads', 'leads.lead_id', '=' , 'lead_params.lead_id')
            ->leftjoin('lead_stages_codes', 'lead_stages_codes.stage_code', '=' , 'leads.stage')
            ->leftjoin('object_params', 'object_params.object_id', '=' , 'lead_params.object_id')
            ->leftjoin('abned_users', 'abned_users.id', '=' , 'lead_params.employee_id')
            ->leftjoin('instalments', 'instalments.lead_id', '=' , 'lead_params.lead_id')
            ->leftjoin('contacts', 'contacts.contact_id', '=' , 'lead_params.client_id');

            //->orderBy('lead_params.contract_date', 'desc');

            if($where) $q ->where( $where );
            else $q ->where( 'lead_params.lead_id',  $this->lead_id );

    }


    public function  GetOne( ) {

        $data = $this->GetWithJoins()->first();

       // dd( Str::replaceArray('?', $this->GetWithJoins()->getBindings(), $this->GetWithJoins()->toSql() ) );

        $json = json_decode( $data->comments );
        // dd( Str::replaceArray('?', $this->GetWithJoins()->getBindings(), $this->GetWithJoins()->toSql() ) );
        if($json && is_object($json)  ) {

            $data->comments = $json->comment . ' ' . $json->additional_comment;

        }


      //  $clientNames =  $data->client_name ? [ $data->client_name] : [];
        $clients = [];


        if($this->ContactsLinks->IsNotEmpty()) {

            foreach($this->ContactsLinks->where('deleted', '<>', 1 ) as $link ) {


               // if(!in_array( $link->Contact->name , $clientNames))  $clientNames[] = $link->Contact->name;

                if(isset($link->contact_id)  ) $clients[$link->contact_id] = ['prs'=>null, 'name' => @$link->Contact->name];

            }

           // $data->client_name = implode(',' ,  $clientNames);
        }


        $prs = [
            'personal' =>  'единоличная',
            'together' =>  'совместная',
        ];

        if($json = json_decode( $data->ownership_percent )) {

            if($json) {
                switch($json->type) {
                    case 'personal' :
                    case 'together' :
                        foreach($clients as $k=>$client) {
                            $clients[$k]['prs'] = $prs[$json->type];
                        }

                    break;
                    case 'part' :

                        if(@$json->part && is_object($json->part)){
                            $_clients = $clients ;
                            $clients = [];
                            foreach($json->part as $contact_id => $part ) {
                                $clients[$contact_id] = ['prs'=>(float)$part, 'name'=>@$_clients[$contact_id]['name']  ]  ;
                               /* if(isset($clients[$contact_id]) ) {
                                    $clients[$contact_id]['prs'] = (float)$part;
                                }  */
                            }
                        }
                        break;
                }

            }
            // $data->ownership_percent =  $json->type;

        }

        $data->clients =  collect($clients)->unique();
       // dd(3333333, $data  );


        return $data;

    }
    public function  More( ) {

        //if(!is_array($where)) $where = ['lead_params.lead_id'=>   $where ];
        //$lead = $this->GetWithJoins(   $where ) ->first() ;

       // dd($this->Instalment ->get()->except(['bailout' ]) );

        //DB::connection()->enableQueryLog();
        // dd(   DB::getQueryLog() );

      //  dd( $this->Contacts()->Contact()->pluck('contact_id', 'name')  );

        return $this->lead_id ? [
            'lead' => $this->GetOne(),
            'inst'=>  $this->Instalment ? collect($this->Instalment)->except(['bailout' ]) : null,
            'sch'=> array_values($this->Schedule->sortBy('n')->toArray()) ,
            'sch_history'=>   $this->ScheduleHistory->IsNotEmpty() ? $this->ScheduleHistory->sortBy('n'): null ,
            'penalty' =>$this->Penalty,
            'assignment'=> $this->Assignment,
            'bailouts'=>  Schedule::getBailouts(),

            //'penalty_statuses' => Penalty::$statuses

        ] : [];
    }
    public function Instalment()
    {

        return $this->hasOne('App\Instalment', 'lead_id', 'lead_id');
    }
    public function Schedule()
    {

        return $this->hasMany('App\Schedule', 'lead_id', 'lead_id');
    }
    public function ScheduleHistory()
    {  //
        return $this->hasMany('App\ScheduleHistory', 'lead_id', 'lead_id')->with('user')  ;
    }

    public function ContactsLinks()
    {


       return $this->hasMany('App\ContactsLinks', 'lead_id', 'lead_id')->with('Contact');


    }
    public function Contact()
    {
        return $this->hasManyThrough('App\Contact', 'App\ContactsLinks', 'lead_id', 'contact_id', 'lead_id', 'contact_id');
    }


    public function Penalty()
    {

        return $this->hasMany('App\Penalty', 'lead_id', 'lead_id')->orderBy('id');
    }
    public function PenaltyNoCorrection()
    {

        return $this->hasMany('App\PenaltyNoCorrection', 'lead_id', 'lead_id')->orderBy('id');
    }



    public function PenaltyDaily()
    {

        return $this->hasMany('App\PenaltyDaily', 'lead_id', 'lead_id')->orderBy('id');
    }
    public function PenaltyGrouped()
    {

        $pnlts = $this->hasMany('App\PenaltyDaily', 'lead_id', 'lead_id')->orderBy('id')->get();
        foreach( $pnlts as $pnlty) {
            dd(55555, $pnlty);
        }

        dd(66666777777);
    }
    //переуступка
    public function Assignment()
    {

        return $this->hasMany('App\Assignment', 'lead_id', 'lead_id')->orderBy('id');
    }

}
