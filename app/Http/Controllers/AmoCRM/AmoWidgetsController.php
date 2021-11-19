<?php

namespace App\Http\Controllers\AmoCRM;

use App\Company;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AmoWidgetsController extends Controller
{
    public function subAgentSearch(Request $request){
        $companies = Company::where('deleted',0)->pluck('name','company_id');

        //

        return response()->json(
            $companies, 200,
            ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'],
            JSON_UNESCAPED_UNICODE
        );
    }
}
