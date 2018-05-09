<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Session;
use DB;
use App\libs\ApiConf;

class PayController extends Controller
{
    //
    public function paypalresult(){
        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                ApiConf::ClientID,
                ApiConf::Secret
            )
        );
        if(!isset($_GET['success'], $_GET['paymentId'], $_GET['PayerID'])){
            die();
        }

        if((bool)$_GET['success']=== 'false'){

            echo 'Transaction cancelled!';
            die();
        }

        $paymentID = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];

        $payment = Payment::get($paymentID, $apiContext);

        $execute = new PaymentExecution();
        $execute->setPayerId($payerId);

        try{
            $result = $payment->execute($execute, $apiContext);
        }catch(Exception $e){
            die($e);
        }
        echo '支付成功！感谢支持!';
    }
    public function paypal1(){
        define('SITE_URL', 'http://'.$_SERVER['HTTP_HOST']);
        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                ApiConf::ClientID,
                ApiConf::Secret
            )
        );
        $product = "paypal test1111";
        $price = 9.0;
        $shipping = 0.10; //运费

        $total = $price + $shipping;
        $payer = new \PayPal\Api\Payer();
        $payer->setPaymentMethod('paypal');

        $item = new \PayPal\Api\Item();
        $item->setName($product)
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice($price);


        $itemList = new \PayPal\Api\ItemList();
        $itemList->setItems([$item]);


        $details = new \PayPal\Api\Details();
        $details->setShipping($shipping)
            ->setSubtotal($price);

        $amount = new \PayPal\Api\Amount();
        $amount->setCurrency('USD')
            ->setTotal($total)
            ->setDetails($details);

        $transaction = new \PayPal\Api\Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription("test")
            ->setInvoiceNumber(uniqid());

        $redirectUrls = new \PayPal\Api\RedirectUrls();
        $redirectUrls->setReturnUrl(SITE_URL . '/pay/paypalsuccess')
            ->setCancelUrl(SITE_URL . '/checkout');

        $payment = new \PayPal\Api\Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions([$transaction]);

        try {
            $payment->create($apiContext);
        } catch (PayPalConnectionException $e) {
            echo $e->getData();
            die();
        }

        $approvalUrl = $payment->getApprovalLink();
        header("Location: {$approvalUrl}");


    }
    public function paypal(){
    }
    public function couponPay(Request $req){
        $user = Session::get('userInfo', null);
        if(!$user) exit;
        $ids = $req->get('ids');
        if(!$ids) exit;
        $ids = rtrim($ids, '|');
        $ids = explode("|" , $ids);
        if(!is_array($ids)) exit;
        if(!$this->checkpay($ids,$user->user_id)){
            echo 'error';
            exit;
        }
        $tot = 0.0;
        foreach($ids AS $k => $v){
            $goods = DB::table('orders')->select('orders.order_id','element.element_name','element.element_price')->leftJoin('element','orders.order_eid','=','element.element_id')->where(['orders.order_id'=>$v])->first();
            $goods->element_price=sprintf('%.2f',$goods->element_price);
            $tot += $goods->element_price;

        }
        $coupon = DB::table('wallet')->select('coupon')->where(['uid'=>$user->user_id])->first();
        if($tot > $coupon->coupon){
            echo -1;
            return;
        }
        $pay_time = time();

        foreach($ids AS $k=>$v){
            $order_no = date('YmdHis').$v.$user->user_id;
            DB::table('orders')->where(['order_id'=>$v])->update(['order_status'=>1,'order_paytime'=>$pay_time,'order_paymethod'=>2,'order_no'=>$order_no]);
            $ck_auther = DB::table('orders')->where(['order_id'=>$v])->first();
            $ck_auther_wallet = DB::table('wallet')->where(['uid'=>$ck_auther->order_owner])->first();
            if($ck_auther_wallet){
                DB::table('wallet')->where(['uid'=>$ck_auther->order_owner])->increment('dollar',$ck_auther->order_price);
            }else{
                DB::table('wallet')->where(['uid'=>$ck_auther->order_owner])->insert(['uid'=>$ck_auther->order_owner,'coupon'=>0,'dollar'=>$ck_auther->order_price,'rmb'=>0]);
            }
            DB::table('wallet')->where(['uid'=>$ck_auther->order_uid])->decrement('coupon',$ck_auther->order_price);
        }
        $left = $coupon->coupon-$tot;
        DB::table('wallet')->where(['uid'=>$user->user_id])->update(['coupon'=>$left]);
        echo 1;

    }
    private function checkpay($arr,$uid){
        foreach($arr AS $v){
            $res = DB::table('orders')->where(['order_uid'=>$uid,'order_status'=>0])->first();
            if(!$res) return false;
        }
        return true;
    }

}


