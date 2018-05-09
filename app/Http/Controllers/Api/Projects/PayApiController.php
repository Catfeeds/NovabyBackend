<?php

namespace App\Http\Controllers\Api\Projects;

use App\Events\MailEvent;
use App\Events\NotifyEvent;
use App\Http\Controllers\Api\BaseApiController;
use App\Model\BuildPay;
use App\Model\Notify;
use App\Model\Plan;
use App\Model\PrjApply;
use App\Model\Project;
use App\Model\ProjectUser;
use App\Model\User;
use App\Model\UserPlan;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
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
use PayPal\Api\CreditCard;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

class PayApiController extends BaseApiController
{
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
    private  $mode;
    public function __construct(Request $req)
    {
        parent::__construct($req);
        $this->mode =env('PAYPAL') ? env('PAYPAL') : 'live';

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


    /**
     * creditCard支付
     * @param Request $request
     */
    public function creditCard(Request $request)
    {
        $cartinfo = $request->all();
        $project = Project::where('prj_id',$request->get('id'))->first();
        $apply = PrjApply::where(['prj_id'=>$request->get('id'),'user_id'=>$request->get('b_id')])->first();
        $price = $apply->apply_price;
        if($this->checkPay($request->get('id'),$this->_user->user_id)==1)
        {
            return $this->jsonErr('You have already pay');
        }elseif($cartinfo['price']!=$price){
            return $this->jsonErr('The payment amount is incorrect');
        }else{
            $card = new CreditCard();
            $card->setType($cartinfo['card_type'])
                ->setNumber($cartinfo['card_number'])
                ->setExpireMonth($cartinfo['card_expireMonth'])
                ->setExpireYear($cartinfo['card_expireYear'])
                ->setCvv2($cartinfo['ccv'])
                ->setFirstName($this->_user->user_name)
                ->setLastName($this->_user->user_lastname);
            $fi = new FundingInstrument();
            $fi->setCreditCard($card);


// Set payer to process credit card
            $payer = new Payer();
            $payer->setPaymentMethod("credit_card")
                ->setFundingInstruments(array($fi));

            $item = new Item();
            $item->setName('需求订单')
                ->setCurrency('USD')
                ->setQuantity(1)
                ->setSku($cartinfo['id']) // Similar to `item_number` in Classic API
                ->setPrice($price);

            $details = new Details();
            $details->setShipping(0)
                ->setTax(0)
                ->setSubtotal($price);

            // ### Amount
            // Lets you specify a payment amount.
            // You can also specify additional details
            // such as shipping, tax.
            $amount = new Amount();
            $amount->setCurrency("USD")
                ->setTotal($price)
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

//        $request = clone $card;

            $apiContext = $this->createApiContex();
            try {
                $payment->create($apiContext);
                if($payment->state=='approved'){
                    $order = new BuildPay();
                    $order->pid = $cartinfo['id'];
                    $order->uid = $this->_user->user_id;
                    $order->pay_method = 1;
                    $order->pay_time = time();
                    $order->has_pay = 1;
                    $order->op_time = time();
                    $order->receipt = $payment->id;
                    $result = $order->save();
                    $project = Project::find($cartinfo['id']);
                    $project->prj_progress =2;
                    $project->save();
                    if($result){
                        \Event::fire(new NotifyEvent(6,$this->_user->user_id));
                        \Event::fire(new MailEvent(6,$this->_user));
                        return $this->jsonOk('ok','Pay successful');
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

    }


    /**
     * PayPal支付
     * @return mixed
     */
    public function payPal()
    {
        $id =Input::get('id');
        $b_id =Input::get('b_id');
        if($this->checkPayTerm($id)==1){
            if($this->checkPay($id,$this->_user->user_id)==1) {
                return $this->jsonErr('You have already pay');
            }else{
                $apply = PrjApply::where(['prj_id'=>$id,'user_id'=>$b_id])->first();
                $price = $apply->apply_price;

                $payer = new Payer();
                $payer->setPaymentMethod("paypal");
                $item = new Item();
                $item->setName('需求订单')
                    ->setCurrency('USD')
                    ->setQuantity(1)
                    ->setSku(rand(1,100000))// Similar to `item_number` in Classic API
                    ->setPrice($price);

                $details = new Details();
                $details->setShipping(0)
                    ->setTax(0)
                    ->setSubtotal(0);

                // ### Amount
                // Lets you specify a payment amount.
                // You can also specify additional details
                // such as shipping, tax.
                $amount = new Amount();
                $amount->setCurrency("USD")
                    ->setTotal($price)
                    ->setDetails($details);

                $transaction = new Transaction();
                $transaction->setAmount($amount)
                    ->setDescription("Payment description")
                    ->setInvoiceNumber(uniqid());

                $baseUrl = $_SERVER['APP_URL'];
                $redirectUrls = new RedirectUrls();
                $user_id = $this->_user->user_id;
                $modeler_id = $b_id;
                $redirectUrls->setReturnUrl("$baseUrl/pay/success/$id/$user_id/$modeler_id?success=true")
                    ->setCancelUrl("$baseUrl/pay/error/$id");

                $payment = new Payment();
                $payment->setIntent("sale")
                    ->setPayer($payer)
                    ->setRedirectUrls($redirectUrls)
                    ->setTransactions(array($transaction));
                $apiContext = $this->createApiContex();
//                dd($payment,$apiContext,$payment->create($apiContext));
                try {
                    $payment->create($apiContext);
                } catch (Exception $ex) {
                    return redirect()->to(env('CLIENT_BASE').'novahub/project/'.$id.'/select');
                }
                $approvalUrl = $payment->getApprovalLink();
                header("Location: {$approvalUrl}");
            }
        }else{
            return $this->jsonErr('You not agree Pay Term');
        }
    }


    /**
     * 支付成功回调
     * @param $id
     * @return mixed
     */
    public function paySuccess($id,$uid,$modeler_id){

        $apiContext = $this->createApiContex();
        if(!isset($_GET['success'], $_GET['paymentId'], $_GET['PayerID'])){die();}

        if((bool)$_GET['success']=== 'false'){
            echo 'Transaction cancelled!';
            return redirect()->to(env('CLIENT_BASE').'novahub/project/'.$id.'/select');
        }
        $paymentID = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];
        $payment = Payment::get($paymentID, $apiContext);
        $execute = new PaymentExecution();
        $execute->setPayerId($payerId);
        $payment->execute($execute, $apiContext);
        $project = Project::find($id);
        $project->prj_modeler = $modeler_id;
        $project->save();
        $order = new BuildPay();
        $order->pid = $id;
        $order->uid = $uid;
        $order->pay_method = 2;
        $order->pay_time = time();
        $order->has_pay = 1;
        $order->op_time = time();
        $order->receipt = $this->payment($id);
        $order->save();
        \Event::fire(new NotifyEvent(7,$project->prj_uid));
//        \Event::fire(new MailEvent(7,$this->_user));
        \Event::fire(new NotifyEvent(10,$project->prj_modeler));
        $url = env('CLIENT_BASE').'proposal-b/'.$project->prj_id;
//        Mail::send('emailtpl.select', ['user' =>$project->modeler->user_name,'project'=>$project->prj_name,'url'=>$url], function ($message)use($project){
//            $message->to($project->modeler->user_email)->subject('Congratulations you are selected!');
//        });
        $users = PrjApply::where('prj_id',$project->prj_id)->with('user')->where('user_id','!=',$project->prj_modeler)->get();
        $ids = $users->map(function ($item){
            return $item->user_id;
        })->all();
        $this->Notify($ids,$project);
        //$this->Mail($users->all(),$project);
        $s_user = PrjApply::where('prj_id',$project->prj_id)->where('user_id',$project->prj_modeler)->first();
        if($s_user){
            $s_user->apply_status=3;
            $s_user->prj_status=2;
            $s_user->save();
            $projectUser = new ProjectUser();
            $projectUser->project_id=  $project->prj_id;
            $projectUser->user_id=  $project->prj_modeler;
            $projectUser->user_role = 2;
            $projectUser->save();
            $project->prj_price = $s_user->apply_price;
            $project->prj_progress =2;
            $project->save();
        }
        return redirect()->to(env('CLIENT_BASE').'novahub/project/'.$id.'/payment');

    }

    /**
     * 支付失败回调
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function payError($id)
    {
        return redirect()->to(env('CLIENT_BASE').'novahub/project/'.$id.'/select');
    }
    /**
     * 检测支付状态
     * @param $id
     * @param $uid
     * @return int
     */
    private function checkPay($id,$uid)
    {
        $result = BuildPay::where(['pid'=>$id,'uid'=>$uid])->first();
        if($result) {
            return 1; //已经支付
        }
        else{
            return 0;  //未支付
        }
    }

    /**
     * 检测是否同意支付合同
     * @param $id
     * @return int
     */
    private function checkPayTerm($id)
    {
        $project = Project::find($id);
        if($project->prj_payterm==1) {
            return 1;   //已经同意合同
        }else{
            return 0;   //未同意支付合同
        }
    }

    /**
     * 检测上传状态
     * @param $id
     * @return int
     */
    private  function checkUpload($id)
    {
        $result = Project::where('prj_id',$id)->first()->prj_attachment;
        if($result==null) {
            return 1; //未上传
        }
        else{
            return 0;  //已上传
        }
    }

    /**
     * 给未选中的乙方群发消息
     * @param $ids
     */
    private function  Notify($ids,$project)
    {
        if(is_array($ids) && $ids!=null)
        {
            $notify_template = Notify::where('type',11)->first();
            $notify = new Notify();
            $notify->content = $notify_template->content;
            $notify->content_cn = $notify_template->content_cn;
            $notify->type = 5;
            $notify->title = 'all';
            $notify->save();
            $value  = null;
            $sql1 = 'ALTER TABLE messages ENGINE = MYISAM'; //改变数据表存储引擎，提升存储速度
            DB::statement($sql1);
            foreach($ids as $user_id)
            {
                $value .= '(0,'.$user_id.',3,'.$notify->id.','.time().'),';
            }
            $sql2 = 'insert into messages(msg_from_uid,msg_to_uid,msg_action,msg_rid,msg_time)value'.rtrim($value,',');
            DB::statement($sql2);
            $sql3 = 'ALTER TABLE messages ENGINE = InnoDB';  //恢复数据表存储引擎
            DB::statement($sql3);
        }

    }

    private function Mail($users,$project)
    {
        if (is_array($users))
        {
            foreach ($users as $user)
            {
                $url = env('CLIENT_BASE').'projects';
                Mail::later(10,'emailtpl.unselect', ['user' =>$user->user->user_name,'project'=>$project->prj_name,'url'=>$url], function ($message)use($user){
                    $message->to($user->user->user_email)->subject('Thanks for your bidding!');
                });
            }
        }
    }
    /**
     * 生成账单
     */
    private function payment($id)
    {
        $payment = time().'-'.$id;
        $chars = '0123456789';
        for ( $i = 0; $i <6; $i++ )
        {
            $payment.= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $payment;
    }

}
