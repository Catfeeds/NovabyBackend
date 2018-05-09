<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Redirect;
use Session;
use Illuminate\Support\Facades\Input;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\PaymentExecution;
use PayPal\Api\CreditCardToken;
use PayPal\Api\FundingInstrument;
use App\libs\ApiConf;
use Mail;
use PayPal\Api\CreditCard;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

class PaypalController extends Controller
{
    private $info;
    private $envs = [
        'sandbox'=>[
            'ClientID'=>'AVllhPIXpz7kkwURFm-cfwT8RVG94BEPvASRw7sPqUxmfSBd4t08UAN-OqvcMQ4ExhVMp9P-4VAr-S-m',
            'Secret'=>'EM8JVmnmfbn4Ru_Sc7QfNtyIpdNvUOTcf27s7WhM1ahc9-zXXIT8nXyzOscC8sttIWEw73ENM9Lw2eWn',
        ],
        'live'=>[
            'ClientID'=>'AaxLo1BmizU0WsH01Kvv61f4Bxy0m5SGVfA7YS_TU-2Jck2l2Bsepo2x27d8lDdxX5zlMudXmHzeXZAL',
            'Secret'=>'EKfzKgRbH7nfi7QR6EvI4MlnlAUt6PptO7CPOE21MDYiU4Io8Z5bZKMDEH1sLCuyQGFCP26FvWvCBT7w',
        ]
    ];
    private  $mode='sandbox';


    public function __construct(){
        $this->info=Session::get('userInfo',null);
    }
    private function createApiContex(){
        
        $apiContext = new ApiContext(
            new OAuthTokenCredential($this->envs[$this->mode]['ClientID'], $this->envs[$this->mode]['Secret'])
        );

        $apiContext->setConfig(
            [
                'mode' => $this->mode,
                'log.LogEnabled' => true,
                'log.FileName' => '../PayPal.log',
                'log.LogLevel' => 'ALL', // PLEASE USE `INFO` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
                'cache.enabled' => true,
                'http.CURLOPT_CONNECTTIMEOUT' => 30,
                'http.headers.PayPal-Partner-Attribution-Id' => 'mkE2nZHfTGj7ObpH6QPwnn4IXi80py_3O-pqV-5Gf6We2hVDrte-5VMzkbi',
                //'log.AdapterFactory' => '\PayPal\Log\DefaultLogFactory' // Factory class implementing \PayPal\Log\PayPalLogFactory
            ]
        );
        return $apiContext;
    }


    public function creditCardPayforcontract(Request $req){

        $pid = $req->get('pid',0);
        $cardno = $req->get('cardno','');
        $carddate = $req->get('carddate','');
        $ccv = $req->get('ccv','');
        $cardtype = $req->get('cardtype','');
        $securely = $req->get('securely',0);
        if($securely==1){
            $BillingAddress = $req->get('BillingAddress');
            $address1 =$req->get('address1');
            $address2 =$req->get('address2');
            $city = $req->get('city');
            $state = $req->get('state');
            $postcode= $req->get('postcode');
            $v0 = $req->get('v0');
            $_data=[
                'uid'=>$this->info->user_id,
                'billing_address'=>$BillingAddress,
                'address1'=>$address1,
                'address2'=>$address2,
                'city'=>$city,
                'state'=>$state,
                'postcode'=>$postcode,
                'v0'=>$v0,
            ];
            $ck_info = DB::table('billing_address')->where(['uid'=>$this->info->user_id])->first();
            if($ck_info){
                DB::table('billing_address')->where(['id'=>$ck_info->id])->update($_data);
            }else{
                DB::table('billing_address')->insert($_data);
            }
        }


        $suid = DB::table('project')->select('prj_final_uid')->where(['prj_id'=>$pid])->first();
        if(!$suid || !$suid->prj_final_uid){
            exit;
        }
        $bids_info = DB::table('bidding')->where(['bid_pid'=>$pid,'bid_accept'=>1,'bid_uid'=>$suid->prj_final_uid])->first();


        $cardFirstName = 'yang';
        $cardLastName = 'xiong';
        $days = explode("/",$carddate);

        $pay_tot = $bids_info->bid_price;

        $card = new CreditCard();

        $card->setType($cardtype)
            ->setNumber($cardno)
            ->setExpireMonth($days[1])
            ->setExpireYear($days[2])
            ->setCvv2($ccv)
            ->setFirstName($cardFirstName)
            ->setLastName($cardLastName);
        $fi = new FundingInstrument();
        $fi->setCreditCard($card);

        $payer = new Payer();
        $payer->setPaymentMethod("credit_card")
            ->setFundingInstruments(array($fi));

        $item = new Item();
        $item->setName('trial_pay')
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setSku($pid) // Similar to `item_number` in Classic API
            ->setPrice($pay_tot);
        $items[]=$item;

        $itemList = new ItemList();
        $itemList->setItems($items);
        $details = new Details();
        $details->setShipping(0)
            ->setTax(0)
            ->setSubtotal($pay_tot);

        $amount = new Amount();
        $amount->setCurrency("USD")
            ->setTotal($pay_tot)
            ->setDetails($details);


        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setDescription("Payment description");

        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setTransactions(array($transaction));

        $request = clone $card;

        $apiContext = $this->createApiContex();
        try {
            $payment->create($apiContext);
            //echo($payment->state);

            if($payment->state=='approved'){
                $_data=[
                    'pid'=>$pid,
                    'uid'=>$suid->prj_final_uid,
                    'pay_method'=>1,
                    'pay_time'=>time(),
                    'has_pay'=>1,
                    'op_time'=>time(),
                    'receipt'=>$payment->id,
                ];

                $res = DB::table('build_pays')->insertGetId($_data);
                if($res){
                    DB::table('trialsubmits')->where(['ts_pid'=>$pid])->update(['ts_accept'=>1]);
                    echo 1;
                }

            }
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            die($ex);
        } catch (Exception $ex) {
            die($ex);
        }
        exit;


    }
    public function creditCardPayfortrial(Request $req){
        $pid = $req->get('pid',0);
        $cardno = $req->get('cardno','');
        $carddate = $req->get('carddate','');
        $ccv = $req->get('ccv','');
        $cardtype = $req->get('cardtype','');
        $securely = $req->get('securely',0);
        if($securely==1){
            $BillingAddress = $req->get('BillingAddress');
            $address1 =$req->get('address1');
            $address2 =$req->get('address2');
            $city = $req->get('city');
            $state = $req->get('state');
            $postcode= $req->get('postcode');
            $v0 = $req->get('v0');
            $_data=[
                'uid'=>$this->info->user_id,
                'billing_address'=>$BillingAddress,
                'address1'=>$address1,
                'address2'=>$address2,
                'city'=>$city,
                'state'=>$state,
                'postcode'=>$postcode,
                'v0'=>$v0,
            ];
            $ck_info = DB::table('billing_address')->where(['uid'=>$this->info->user_id])->first();
            if($ck_info){
                DB::table('billing_address')->where(['id'=>$ck_info->id])->update($_data);
            }else{
                DB::table('billing_address')->insert($_data);
            }
        }
        $bids_nums = DB::table('bidding')->where(['bid_pid'=>$pid,'bid_accept'=>1])->count();
        $trial_info = DB::table('trial_works')->where(['trial_pid'=>$pid])->first();

        $cardFirstName = 'yang';
        $cardLastName = 'xiong';
        $days = explode("/",$carddate);
        $pay_tot = $trial_info->trial_cost_price*$bids_nums;
        $card = new CreditCard();
        $card->setType($cardtype)
            ->setNumber($cardno)
            ->setExpireMonth($days[1])
            ->setExpireYear($days[2])
            ->setCvv2($ccv)
            ->setFirstName($cardFirstName)
            ->setLastName($cardLastName);
        $fi = new FundingInstrument();
        $fi->setCreditCard($card);

        $payer = new Payer();
        $payer->setPaymentMethod("credit_card")
            ->setFundingInstruments(array($fi));

        $item = new Item();
        $item->setName('trial_pay')
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setSku($pid) // Similar to `item_number` in Classic API
            ->setPrice($pay_tot);
        $items[]=$item;

        $itemList = new ItemList();
        $itemList->setItems($items);
        $details = new Details();
        $details->setShipping(0)
            ->setTax(0)
            ->setSubtotal($pay_tot);

        $amount = new Amount();
        $amount->setCurrency("USD")
            ->setTotal($pay_tot)
            ->setDetails($details);


        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setDescription("Payment description");

        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setTransactions(array($transaction));

        $request = clone $card;

       $apiContext = $this->createApiContex();
        try {
            $payment->create($apiContext);
            //echo($payment->state);

            if($payment->state=='approved'){
                $pay_time = time();
                $res = DB::table('trial_works')->where(['trial_pid'=>$pid])->update(['trial_payway'=>1,'trial_haspay'=>1,'trial_paytime'=>$pay_time]);
                if($res){
                    DB::table('bidding')->where(['bid_pid'=>$pid,'bid_accept'=>1])->update(['bid_pay_no'=>$payment->id]);

                    echo 1;
                }

            }
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            die($ex);
        } catch (Exception $ex) {
            die($ex);
        }
        exit;


    }
    public function creditCard(Request $req){
        $ids = $req->get('ids',"");
        if($ids){
            $ids = explode("|",rtrim($ids,"|"));
        }
        if(!is_array($ids) || count($ids)<1){
            echo "-1";
            exit;

        }
        $cartinfo = $req->get('cardInfo','');




        $card = new CreditCard();
        $card->setType($cartinfo['cardtype'])
            ->setNumber($cartinfo['creditcard'])
            ->setExpireMonth($cartinfo['ExpireMonth'])
            ->setExpireYear($cartinfo['ExpireYear'])
            ->setCvv2($cartinfo['ccv2'])
            ->setFirstName($cartinfo['FirstName'])
            ->setLastName($cartinfo['LastName']);
        $fi = new FundingInstrument();
        $fi->setCreditCard($card);

// Set payer to process credit card
        $payer = new Payer();
        $payer->setPaymentMethod("credit_card")
            ->setFundingInstruments(array($fi));


        $items=[];
        $tot = 0.00;
        foreach($ids AS $k => $v){
            $goods = DB::table('orders')->select('orders.order_id','element.element_name','element.element_price')->leftJoin('element','orders.order_eid','=','element.element_id')->where(['orders.order_id'=>$v])->first();
            $goods->element_price=sprintf('%.2f',$goods->element_price);
            $tot += $goods->element_price;
            $item = new Item();
            $item->setName($goods->element_name)
                ->setCurrency('USD')
                ->setQuantity(1)
                ->setSku($goods->order_id) // Similar to `item_number` in Classic API
                ->setPrice($goods->element_price);
            $items[]=$item;
        }



        $itemList = new ItemList();
        $itemList->setItems($items);
        $details = new Details();
        $details->setShipping(0)
            ->setTax(0)
            ->setSubtotal($tot);

        // ### Amount
        // Lets you specify a payment amount.
        // You can also specify additional details
        // such as shipping, tax.
        $amount = new Amount();
        $amount->setCurrency("USD")
            ->setTotal($tot)
            ->setDetails($details);

// Create transaction object with required data
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setDescription("Payment description");

// Create payment object with required data
        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setTransactions(array($transaction));


        /*0000000*/


        $request = clone $card;

        $apiContext = $this->createApiContex();
        try {
            $payment->create($apiContext);
            //echo($payment->state);
            if($payment->state=='approved'){
                $pay_time = time();
                foreach($ids AS $v){
                    $ck_order = DB::table('orders')->where(['order_id'=>$v])->first();
                    $order_no = date('YmdHis').$v.$ck_order->order_uid;
                    DB::table('orders')->where(['order_id'=>$v])->update(['order_status'=>1,'order_paytime'=>$pay_time,'order_paymethod'=>4,'order_no'=>$order_no]);
                    $ck_auther = DB::table('orders')->where(['order_id'=>$v])->first();
                    $ck_auther_wallet = DB::table('wallet')->where(['uid'=>$ck_auther->order_owner])->first();
                    if($ck_auther_wallet){
                        DB::table('wallet')->where(['uid'=>$ck_auther->order_owner])->increment('dollar',$ck_auther->order_price);
                    }else{
                        DB::table('wallet')->where(['uid'=>$ck_auther->order_owner])->insert(['uid'=>$ck_auther->order_owner,'coupon'=>0,'dollar'=>$ck_auther->order_price,'rmb'=>0]);
                    }
                }
                echo 1;

            }
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            die($ex);
        } catch (Exception $ex) {
            die($ex);
        }
        exit;

    }
    private function checkpay($arr,$uid){
        foreach($arr AS $v){
            $res = DB::table('orders')->where(['order_uid'=>$uid,'order_status'=>0])->first();
            if(!$res) return false;
        }
        return true;
    }
	public function paypal(){
        $ids = Input::get('payids', null);
        if(!$ids) exit;
        $ids = rtrim($ids, '|');
        $ids = explode("|" , $ids);
        if(!is_array($ids)) exit;
        if(!$this->checkpay($ids,$this->info->user_id)){
            exit;
        }
		$payer = new Payer();
        $payer->setPaymentMethod("paypal");
        $items=[];
        $tot = 0.00;
        foreach($ids AS $k => $v){
            $goods = DB::table('orders')->select('orders.order_id','element.element_name','element.element_price')->leftJoin('element','orders.order_eid','=','element.element_id')->where(['orders.order_id'=>$v])->first();
            $goods->element_price=sprintf('%.2f',$goods->element_price);
            $tot += $goods->element_price;
            $item = new Item();
            $item->setName($goods->element_name)
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setSku($goods->order_id) // Similar to `item_number` in Classic API
            ->setPrice($goods->element_price);
            $items[]=$item;
        }

        $itemList = new ItemList();
        $itemList->setItems($items);
        $details = new Details();
        $details->setShipping(0)
        ->setTax(0)
        ->setSubtotal($tot);

        // ### Amount
        // Lets you specify a payment amount.
        // You can also specify additional details
        // such as shipping, tax.
        $amount = new Amount();
        $amount->setCurrency("USD")
        ->setTotal($tot)
        ->setDetails($details);


        $transaction = new Transaction();
        $transaction->setAmount($amount)
        ->setItemList($itemList)
        ->setDescription("Payment description")
        ->setInvoiceNumber(uniqid());

        $baseUrl = $_SERVER['APP_URL'];
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl("$baseUrl/pay/paysuccess?success=true")
        ->setCancelUrl("$baseUrl/product/checkout");


        $payment = new Payment();
        $payment->setIntent("sale")
        ->setPayer($payer)
        ->setRedirectUrls($redirectUrls)
        ->setTransactions(array($transaction));


        $request = clone $payment;
        $apiContext = $this->createApiContex();
        try {
            $payment->create($apiContext);
        } catch (Exception $ex) {
	        exit;
        }

        $approvalUrl = $payment->getApprovalLink();
		header("Location: {$approvalUrl}");
    }
    public function paysuccessfortrial(){

        $apiContext = $this->createApiContex();
        if(!isset($_GET['success'], $_GET['paymentId'], $_GET['PayerID'])){die();}

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
            $pay_time= time();
            $baseUrl = $_SERVER['APP_URL'];

            foreach($result->transactions[0]->item_list->items AS $v){
                echo $v->name."##".$v->sku."<br/>";
                $res = DB::table('trial_works')->where(['trial_pid'=>$v->sku])->update(['trial_payway'=>2,'trial_haspay'=>1,'trial_paytime'=>$pay_time]);
                if($res){
                    $suc = DB::table('bidding')->where(['bid_pid'=>$v->sku,'bid_accept'=>1])->update(['bid_pay_no'=>$payment->id]);
                    if($suc){
                        echo "<a href='".$baseUrl.'//project/progress/payment/'.$v->sku."'>pay successfully click to back</a>";
                    }

                }

            }
        }catch(Exception $e){
            die($e);
        }
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        //return view('pay.paysuccess',['user'=>$this->info,'title'=>'pay successfully!','notices'=>$notices,'cart_info'=>$cart_info]);

    }
    public function paypalresult(){
        $apiContext = $this->createApiContex();
        if(!isset($_GET['success'], $_GET['paymentId'], $_GET['PayerID'])){ die();}

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
            $pay_time= time();
	        foreach($result->transactions[0]->item_list->items AS $v){
                $ck_order = DB::table('orders')->where(['order_id'=>$v->sku])->first();
                $order_no = date('YmdHis').$v->sku.$ck_order->order_uid;
		        DB::table('orders')->where(['order_id'=>$v->sku])->update(['order_status'=>1,'order_paytime'=>$pay_time,'order_paymethod'=>1,'order_no'=>$order_no]);
                $ck_auther = DB::table('orders')->where(['order_id'=>$v->sku])->first();
                $ck_auther_wallet = DB::table('wallet')->where(['uid'=>$ck_auther->order_owner])->first();
		        if($ck_auther_wallet){
                    DB::table('wallet')->where(['uid'=>$ck_auther->order_owner])->increment('dollar',$ck_auther->order_price);
                }else{
                    DB::table('wallet')->where(['uid'=>$ck_auther->order_owner])->insert(['uid'=>$ck_auther->order_owner,'coupon'=>0,'dollar'=>$ck_auther->order_price,'rmb'=>0]);
                }
	        }
        }catch(Exception $e){
            die($e);
        }
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('pay.paysuccess',['user'=>$this->info,'title'=>'pay successfully!','notices'=>$notices,'cart_info'=>$cart_info]);
    }
    public function rechargeresult(){

        $apiContext = $this->createApiContex();
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
            $pay_time= time();
            foreach($result->transactions[0]->item_list->items AS $v){
                //echo $v->name."##".$v->sku."<br/>";
                DB::table('recharge')->where(['id'=>$v->sku])->update(['status'=>1,'pay_time'=>$pay_time]);
                $pay_info = DB::table('recharge')->where(['id'=>$v->sku])->first();
                $ck_wallet=DB::table('wallet')->where(['uid'=>$pay_info->uid])->first();
                if($ck_wallet){
                    $add_coupon_inviter = DB::table('wallet')->where(['uid'=>$pay_info->uid])->increment('dollar',$pay_info->tot);
                }else{
                    $_data = ['uid'=>$pay_info->uid,'coupon'=>0,'dollar'=>$pay_info->tot,'rmb'=>0];
                    DB::table('wallet')->insert($_data);
                }

            }

        }catch(Exception $e){
            die($e);
        }
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('pay.rechargesuccess',['user'=>$this->info,'title'=>'recharge successfully!','notices'=>$notices,'cart_info'=>$cart_info]);




    }

    public function recharge($oid = 0){
        if($oid<=0) exit;
        $uid = $this->info->user_id;
        $condation = ['id'=>$oid,'uid'=>$uid,'status'=>0];
        $ck_order = DB::table('recharge')->where($condation)->first();
        if(!$ck_order) exit;

        $payer = new Payer();
        $payer->setPaymentMethod("paypal");
        $items=[];
        $tot = $ck_order->tot;
        $item = new Item();
        $item->setName('Account recharge')
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setSku($oid)
            ->setPrice($ck_order->tot);
        $items[]=$item;

        $itemList = new ItemList();
        $itemList->setItems($items);
        $details = new Details();
        $details->setShipping(0)
            ->setTax(0)
            ->setSubtotal($tot);

        $amount = new Amount();
        $amount->setCurrency("USD")
            ->setTotal($tot)
            ->setDetails($details);


        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription("Payment description")
            ->setInvoiceNumber(uniqid());

        $baseUrl = 'http://'.$_SERVER['HTTP_HOST'];
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl("$baseUrl/pay/rechargesuccess?success=true")
            ->setCancelUrl("$baseUrl/account/wallet");


        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));


        $request = clone $payment;
        $apiContext = $this->createApiContex();
        try {
            $payment->create($apiContext);
        } catch (Exception $ex) {
            exit;
        }

        $approvalUrl = $payment->getApprovalLink();
        header("Location: {$approvalUrl}");

    }

    public function trialpay($id){
        $trial_info = DB::table('trial')->where(['trial_pid'=>$id])->first();

        if(!$trial_info) exit;
        $trial_info->trial_tot;

        $payer = new Payer();
        $payer->setPaymentMethod("paypal");
        $items=[];
        $tot = $trial_info->trial_tot;
        $item = new Item();
        $item->setName('pay for trial')
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setSku($trial_info->trial_id)
            ->setPrice($trial_info->trial_tot);
        $items[]=$item;

        $itemList = new ItemList();
        $itemList->setItems($items);
        $details = new Details();
        $details->setShipping(0)
            ->setTax(0)
            ->setSubtotal($tot);

        $amount = new Amount();
        $amount->setCurrency("USD")
            ->setTotal($tot)
            ->setDetails($details);


        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription("Payment description")
            ->setInvoiceNumber(uniqid());

        $baseUrl = 'http://'.$_SERVER['HTTP_HOST'];
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl("$baseUrl/pay/paypal/trialresult?success=true")
            ->setCancelUrl("$baseUrl/projects/trial/".$trial_info->trial_pid);


        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));


        $request = clone $payment;
        $apiContext = $this->createApiContex();
        try {
            $payment->create($apiContext);
        } catch (Exception $ex) {
            exit;
        }

        $approvalUrl = $payment->getApprovalLink();
        header("Location: {$approvalUrl}");
    }

    public function buildpay($id=0){
        $pay_info = DB::table('biddings')->leftJoin('build_pay','biddings.bid_pid','=','build_pay.pid')->where(['biddings.bid_pid'=>$id])->first();
        if(!$pay_info) exit;
        $payer = new Payer();
        $payer->setPaymentMethod("paypal");
        $items=[];
        $tot = $pay_info->bid_price;
        $item = new Item();
        $item->setName('pay for build')
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setSku($pay_info->bid_pid)
            ->setPrice($pay_info->bid_price);
        $items[]=$item;
        $apiContext = $this->createApiContex();
        $itemList = new ItemList();
        $itemList->setItems($items);
        $details = new Details();
        $details->setShipping(0)
            ->setTax(0)
            ->setSubtotal($tot);

        $amount = new Amount();
        $amount->setCurrency("USD")
            ->setTotal($tot)
            ->setDetails($details);


        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription("Payment description")
            ->setInvoiceNumber(uniqid());

        $baseUrl = 'http://'.$_SERVER['HTTP_HOST'];
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl("$baseUrl/pay/paypal/buildresult?success=true")
            ->setCancelUrl("$baseUrl/projects/pay/".$id);


        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));


        $request = clone $payment;
        //dd($apiContext);
        try {
            $payment->create($apiContext);
        } catch (Exception $ex) {
            exit;
        }

        $approvalUrl = $payment->getApprovalLink();
        header("Location: {$approvalUrl}");

    }

    public function trialresult(){
        $apiContext = $this->createApiContex();
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
	        $pay_time= time();
            $price = 0;
            $prj_name = '';
            foreach($result->transactions[0]->item_list->items AS $v){
                //echo $v->name."##".$v->sku."<br/>";
                $res = DB::table('trial')->where(['trial_id'=>$v->sku])->update(['trial_payway'=>1,'trial_haspay'=>1,'trial_paytime'=>time()]);
                $prj = DB::table('trial')->select('trial_pid','trial_modlers','trial_fee')->where(['trial_id'=>$v->sku])->first();
                DB::table('projects')->where(['prj_id'=>$prj->trial_pid])->update(['prj_status'=>4]);
                $_prj_name = DB::table('projects')->select('prj_name')->where(['prj_id'=>$prj->trial_pid])->first();
                $prj_name = $_prj_name->prj_name;
                $modlers = explode("," ,$prj->trial_modlers);
                $price = $prj->trial_fee;
                $subject='You are invited to have a trial on Novaby with '.$prj->trial_fee.' USD!';
                $invite_url='http://'.$_SERVER['HTTP_HOST'].'/task/trial/'.$prj->trial_pid;
                foreach($modlers AS $k1=>$v1){
                    $email = DB::table('user')->select('user_email')->where(['user_id'=>$v1])->first();
                    $email = $email->user_email;
                    Mail::send('emailtpl.invite_modler',['url'=>$invite_url, 'name'=>'Novaby','price'=>$price,'prj_name'=>$prj_name],function($message) use ($email,$subject){
                        $to = $email;
                        $message ->to($to)->subject($subject);
                    });
                }

                //$pay_info = DB::table('trial')->where(['trial_id'=>$v->sku])->first();
                //$ck_wallet=DB::table('wallet')->where(['uid'=>$pay_info->uid])->first();


            }

        }catch(Exception $e){
            die($e);
        }
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);
        return view('pay.trialpaysuccess',['user'=>$this->info,'title'=>'pay successfully!','notices'=>$notices,'cart_info'=>$cart_info]);


    }
    public function buildresult(){
        $apiContext = $this->createApiContex();
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
        //dd($payment);
        $_id = 0;

        try{
            $result = $payment->execute($execute, $apiContext);
            $pay_time= time();
            foreach($result->transactions[0]->item_list->items AS $v){
                $_id=$v->sku;
                $res = DB::table('build_pay')->where(['pid'=>$v->sku])->update(['pay_method'=>1,'has_pay'=>1,'pay_time'=>time()]);
                $uid_info = DB::table('build_pay')->leftJoin('user','build_pay.uid','=','user.user_id')->select('user.user_email','user.user_id')->where(['build_pay.pid'=>$v->sku])->first();
                DB::table('projects')->where(['prj_id'=>$v->sku])->update(['prj_status'=>5,'prj_final_modler'=>$uid_info->user_id]);
                $subject='Novaby甲方邀请您参加模型开发';
                $invite_url='http://'.$_SERVER['HTTP_HOST'].'/task/trialresult/'.$v->sku;
                $email = $uid_info->user_email;
                Mail::send('emailtpl.invite_modler1',['url'=>$invite_url, 'name'=>'Novaby','prj_name'=>'','price'=>''],function($message) use ($email,$subject){
                    $to = $email;
                    $message ->to($to)->subject($subject);
                });
            }

        }catch(Exception $e){
            die($e);
        }
        $notices=$this->getNoticesLists($this->info->user_id);
        $cart_info=$this->getCart($this->info->user_id);

        return view('pay.buildpaysuccess',['user'=>$this->info,'id'=>$_id,'title'=>'pay successfully!','notices'=>$notices,'cart_info'=>$cart_info]);


    }

    public function paypalPayfortrial($pid=0){

        $ck_data = DB::table('project')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->first();
        if(!$ck_data) exit;
        $pay_users = DB::table('bidding')->where(['bid_pid'=>$pid,'bid_accept'=>1])->get();
        $pay_users_nums = count($pay_users);
        $trial_info = DB::table('trial_works')->where(['trial_pid'=>$pid])->first();

        $payer = new Payer();
        $payer->setPaymentMethod("paypal");
        $items=[];
        $tot = 0.00;
        foreach($pay_users AS $k => $v){
            $tot += $trial_info->trial_cost_price;
            $item = new Item();
            $item->setName('pay for trial')
                ->setCurrency('USD')
                ->setQuantity(1)
                ->setSku($v->bid_id) // Similar to `item_number` in Classic API
                ->setPrice($trial_info->trial_cost_price);
            $items[]=$item;
        }
        $apiContext = $this->createApiContex();
        $itemList = new ItemList();
        $itemList->setItems($items);
        $details = new Details();
        $details->setShipping(0)
            ->setTax(0)
            ->setSubtotal($tot);
        $amount = new Amount();
        $amount->setCurrency("USD")
            ->setTotal($tot)
            ->setDetails($details);
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription("Payment for trial")
            ->setInvoiceNumber(uniqid());

        $baseUrl = $_SERVER['APP_URL'];
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl("$baseUrl/pay/paypal/paysuccessfortrial?success=true")
            ->setCancelUrl("$baseUrl/project/progress/trial/".$pid);
        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));
        $request = clone $payment;
        try {
            $payment->create($apiContext);
        } catch (Exception $ex) {
            exit;
        }

        $approvalUrl = $payment->getApprovalLink();
        header("Location: {$approvalUrl}");




    }
    public function paypalPayforcontract($pid){

        $prj = DB::table('project')->select('prj_final_uid','prj_process_status')->where(['prj_id'=>$pid,'prj_uid'=>$this->info->user_id])->first();
        if(!$prj) exit;
        $pay_info = DB::table('bidding')->where(['bid_pid'=>$pid,'bid_uid'=>$prj->prj_final_uid])->first();



        $payer = new Payer();
        $payer->setPaymentMethod("paypal");
        $items=[];
        $tot = $pay_info->bid_price;
        $item = new Item();
        $item->setName('pay for build')
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setSku($pay_info->bid_pid)
            ->setPrice($pay_info->bid_price);
        $items[]=$item;

        $itemList = new ItemList();
        $itemList->setItems($items);
        $details = new Details();
        $details->setShipping(0)
            ->setTax(0)
            ->setSubtotal($tot);

        $amount = new Amount();
        $amount->setCurrency("USD")
            ->setTotal($tot)
            ->setDetails($details);
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription("Payment description")
            ->setInvoiceNumber(uniqid());

        $baseUrl = $_SERVER['APP_URL'];
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl("$baseUrl/pay/paypal/paysuccessfortrial?success=true")
            ->setCancelUrl("$baseUrl/project/progress/payment/".$pid);
        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));
        $request = clone $payment;
        $apiContext = $this->createApiContex();
        try {
            $payment->create($apiContext);
        } catch (Exception $ex) {
            exit;
        }

        $approvalUrl = $payment->getApprovalLink();
        header("Location: {$approvalUrl}");




    }
}
