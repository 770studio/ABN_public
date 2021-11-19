<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    //показать профиль
    public function index(){

        $user = Auth::user();

        return view('profile.index',[
            'user'=> $user,
        ]);
    }

    //смена пароля в профиле
    public function changePassword(Request $request){
        $user = Auth::user();

        $rules =  [
            'password'=>'required|string|min:8|confirmed',
            'password_confirmation'=>'required'
        ];

        $this->validate($request, $rules);
        $user->password =  Hash::make($request->get('password'));
        $user->save();

        return redirect()->back()->with('ok','Пароль изменен');

    }
}
