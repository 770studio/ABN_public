<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected function authenticated(Request $request, $user){
        if($user->getRoleId() == 1 || $user->getRoleId() == 2){
            return redirect()->route('contracts');
        }
        elseif ($user->getRoleId() == 3){
            return redirect()->route('subagents');
        }
        elseif ($user->getRoleId() == 4){
            return redirect()->route('payments');
        }
        elseif ($user->getRoleId() == 5){
            return redirect()->route('consultants_schedule.index');
        }
    }



    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
