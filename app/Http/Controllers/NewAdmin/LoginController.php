<?php

namespace App\Http\Controllers\NewAdmin;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;


class LoginController extends Controller
{
    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->middleware('admin',['except' => ['getlogin','logout','postlogin']]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getlogin()
    {
        return view('newadmin.login');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|string
     */
    public function postlogin(Request $request)
    {
        if($request->get('uname')=='admin' && $request->get('password') =='nova2017') {
            $request->session()->put('admin','admin');
            return redirect('admin/index');
        }else
        {
            return redirect('admin/login');
        }
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin');
        return redirect('admin/login');
    }
}
