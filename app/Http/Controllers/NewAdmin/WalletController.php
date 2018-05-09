<?php

namespace App\Http\Controllers\NewAdmin;

use App\Model\ApplyCash;
use App\Model\Wallet;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 提现申请列表
     * @return
     */
    public function index()
    {
        $cashes = ApplyCash::orderBy('id','desc')->paginate(15);
//        if ($handle = opendir(storage_path('logs/'))) {
//            while (false !== ($file = readdir($handle))) {
//                if ($file != "." && $file != "..") {
//                        unlink(storage_path('logs/').$file);
//
//                }
//            }
//            closedir($handle);
//        }
        return view('newadmin.cash.index')->with('cashes',$cashes);
    }

    /**
     * 修改提现状态
     * @param $id
     * @param $status 0待处理 1成功 2失败
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function status($id,$status)
    {
        $cash = ApplyCash::where('id',$id)->first();
        $cash->status = $status;
        $cash->save();
        if($status ==2)
        {
            $wallet = Wallet::where('uid',$cash->u_id)->first();
            $wallet->USD = $wallet->USD+$cash->amount;
            $wallet->save();
        }
        $url = env('CLIENT_BASE').'walletList';
        Mail::send('emailtpl.withdraw', ['user' =>$cash->user->user_name,'url'=>$url,'status'=>$status], function ($message)use($cash){
            $message->to($cash->user->user_email)->subject('You have made a new progress in the application, please login to view');
        });
        return redirect('/admin/cash/index');
    }

    /**
     * 查询
     * @param Request $request
     * @return
     */
    public function search(Request $request)
    {
        $search = $request->get('email');
        //查询为空
        if ($search == '') {
            return redirect('/admin/cash/index');
        }
        //查询全部
        else {
            $cashes = ApplyCash::where('paypal_email', 'like', '%' . trim($search) . '%')->paginate(15);
            return view('newadmin.cash.search')->with(['cashes'=>$cashes,'email'=>$search]);
        }

    }

}
