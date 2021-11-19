<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KvitokController extends Controller
{
    public function index(Request $request){
        if (!$request->lead_id){
            return view('admin.kvitok.index',['error'=>'Need parameter lead_id']);
        }
//        if (!$request->token || $request->token != 'asfk87TTbjjjkHjJJ1qa'){
//            return view('admin.kvitok.index',['error'=>'Need token or token is wrong']);
//        }

        try{
            $lead_id = $request->lead_id;

            $data = DB::table('leads')
                ->select(
                    'leads.object_id',
                    'leads.object_zhk',
                    'leads.object_building_number',
                    'leads.object_number',
                    'leads.property_type',
                    'leads.payment_type',
                    'leads.contract_sum',
                    'leads.object_price_psm',
                    'leads.object_floor',
                    'leads.object_entrance',
                    'leads.object_rooms_quantity',
                    'leads.object_square',
                    'leads.mortgage_bank',
                    'leads.installment_period',
                    'object_params.living_space',
                    'object_params.room_1_area',
                    'object_params.room_2_area',
                    'object_params.room_3_area',
                    'object_params.room_4_area',
                    'object_params.kitchen_space',
                    'object_params.bathroom_1_area',
                    'object_params.bathroom_2_area',
                    'object_params.storeroom_area',
                    'object_params.hall_area',
                    'object_params.balcony_area',
                    'object_params.loggia_area',
                    'leads.sale',
                    'leads.consultant',
                    'leads.clerk',
                    'leads.booking_date',
                    'object_params.number_of_loggias',
                    'object_params.number_of_balconies',
                    'leads.subsidies',
                    'leads.individual_conditions',
                    'leads.special_payment_conditions'

                )
                ->join('object_params','object_params.object_id','=','leads.object_id')
                ->where('leads.lead_id','=',$lead_id)
                ->first();



            $contacts = DB::table('links')
                ->select(
                    'links.contact_id',
                    'contacts.name',
                    'contacts.phone',
                    'contacts.email',
                    'contacts.company_id'

                )
                ->join('contacts','contacts.contact_id','=','links.contact_id')
                ->where('links.lead_id','=',$lead_id)
                ->whereNull('links.deleted')
                ->whereNull('contacts.company_id')
                ->get();


            return view('admin.kvitok.index',['data'=>$data,'contacts'=>$contacts]);
        }
        catch (\Exception $e){
            return view('admin.kvitok.index',['error'=>$e->getMessage()]);
        }



    }
}
