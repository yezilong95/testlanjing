<?php
namespace Pay\Controller;

use Common\Model\GPayBindCardModel;
use Common\Model\GPayLogModel;
use Common\Model\OrderModel;
use Common\Model\ProductUserModel;

/**
 * 银生宝-快捷
 * Class YinShengBaoKJController
 * @package Pay\Controller
 * author 黄治华
 */
class YinShengBaoKJController extends PayController
{
    private $CODE = 'YinShengBaoKJ';
    private $TITLE = '银生宝-快捷';

    /**
     * 商户接口调用，打开平台的收银台
     * @param $array
     */
    public function Pay($array)
    {
        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $body = I('request.pay_productname');

        $parameter = array(
            'code' => $this->CODE, // 通道名称
            'title' => $this->TITLE, //通道名称
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid'=>'', //系统订单号
            'out_trade_id' => $out_trade_id, //外部商户订单号
            'channel'=>$array,
            'body'=>$body
        );
        $return = $this->orderadd($parameter);

        if(empty($return)){
            exit('创建支付订单异常');
        }

        //@todo 根据银行卡限额列表，查询最大支付金额
        //必要支付金额最小为10元
//        if($return["amount"] < 10){
//            exit('支付金额最小为10元');
//        }
//        if($return["amount"] > 20000){
//            exit('单笔支付限额20000元');
//        }

        $orderId = $return["orderid"]; //系统订单号
        $memberid = $return["memberid"]; //商户号
        $channelMerchantId = $return['mch_id']; //通道商户号
        $amount = $return["amount"];
        $appid = $return['appid']; //通道账号
        $gateway = $return["gateway"];
        $signkey = $return['signkey'];

        //添加支付日记
        $payLog = [
            'msg' => $this->TITLE,
            'merchantId' => $memberid,
            'productCode' => $productCode,
            'outTradeId' => $out_trade_id,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $orderId,
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
        ];
        $payLog['msg'] = $this->TITLE.'-显示平台的收银台';
        $this->payLogModel->add($payLog);

        $this->assign("outTradeId", $out_trade_id);
        $this->assign("orderId", $orderId);
        $this->assign("body", $body);
        $this->assign("amount", format2Decimal($amount));
        $this->display("Cashier/index");
    }

    /**
     * 提交银行卡账号，查询是否已绑卡
     */
    public function isBindBankCard(){
        $bankCardNo = I("post.bankCardNo");
        $orderId = I('post.orderId');

        if (strlen($bankCardNo) < 10) {
            $this->ajaxFail('请填写正确的银行卡账号');
        }

        $orderModel = D('Order');
        $order = $orderModel->where(['pay_orderid'=>$orderId])->find();
        if (!$order){
            $this->ajaxFail('订单不存在');
        }
        $channelMerchantId = $order['memberid'];

        //查询是否已绑卡
        $bindCardModel = DM('GPayBindCard');
        $bindCard = $bindCardModel->where(['bankCardNo'=>$bankCardNo, 'channelMerchantId'=>$channelMerchantId,
            'status'=>GPayBindCardModel::STATUS_BIND])->find();
        if ($bindCard){
            $this->ajaxSuccess('已绑卡', ['isBind'=>1]);
        }else{
            $this->ajaxSuccess('未绑卡', ['isBind'=>0]);
        }
    }

    // 首次预支付，未绑卡，根据银行卡号绑卡，获取验证码
    public function prePayUnbindCard(){
        $orderId = I("request.orderId"); //平台订单号
        $outTradeId = I('request.outTradeId'); //商户订单号
        $fullname = I("request.fullname");
        $mobile = I("request.mobile");
        $bankCardNo = I("request.bankCardNo");
        $idCardNo = I("request.idCardNo");

        $orderModel = D('Order');
        $order = $orderModel->where(['pay_orderid'=>$orderId, 'out_trade_id'=>$outTradeId])->find();
        if (!$order){
            $this->ajaxFail('订单不存在');
        }
        $merchantId = $order['pay_memberid'];
        $productCode = $order['pay_bankcode'];
        $signKey = $order['key'];
        $amount = $order["pay_amount"];
        $channelMerchantId = $order['memberid'];
        $productName = $order['pay_productname'];

        //添加日记
        $logTitle = $this->TITLE . '-首次预支付-';
        $payLog = [
            'merchantId' => $merchantId,
            'productCode' => $productCode,
            'outTradeId' => $outTradeId,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $orderId,
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
            'level' => GPayLogModel::$LEVEL_INFO,
        ];
        $payLog['msg'] = $logTitle . '平台收银台提交的数据: ' . http_build_query($_POST);
        $this->payLogModel->add($payLog);

        if (empty($orderId) || empty($outTradeId) || empty($fullname)
            || empty($mobile) || empty($bankCardNo) || empty($idCardNo)) {
            $this->ajaxFail('信息填写不完整');
        }

        // 通过商户id和支付类型获取通道信息, 用户用通道, 通道有子账户，通道属于支付产品
        $productUserModel = DM('ProductUser');
        $productUser = $productUserModel->where(['pid'=>$productCode, 'userid'=>$merchantId-10000, 'status'=>ProductUserModel::STATUS_ACTIVE])->find();
        if(!$productUser){
            $this->ajaxFail($_SERVER['HTTP_HOST'].'未分配支付产品给商户, 商户ID='.$merchantId.', 支付产品='.$productCode);
        }
        $channelModel = DM('Channel');
        $channel = $channelModel->where(['id'=>$productUser['channel']])->find();
        $gateway = $channel["gateway"];

        //提交到通道的数据
        $notifyurl = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html';
        $customerId = uniqid();
        $data = [
            'accountId' => $channelMerchantId,//通道商户编号
            'customerId' => $customerId,//用户编号
            'payType' => "0",//支付类型
            'name' => $fullname,//用户姓名
            'phoneNo' => $mobile,//手机号
            'cardNo' => $bankCardNo,//银行卡号
            'idCardNo' => $idCardNo,//身份证号
            'orderId' => $orderId,//订单号
            'purpose' => $productName,//目的
            'amount' => $amount,//金额
            'responseUrl' => $notifyurl
        ];
        $signSource = $this->SignParamsToString($data);
        $sign = md5($signSource."&key=".$signKey);
        $data['mac'] =strtoupper($sign);
        $postData = json_encode($data);

        //添加日记
        $payLog['msg'] = $logTitle . '提交给通道的数据: ' . $postData . '，网关地址=' . $gateway;
        $this->payLogModel->add($payLog);

        $httpResult = $this->http_post_data($gateway, $postData);

        //添加日记
        $payLog['msg'] = $logTitle . '通道返回的数据: http状态码=' . $httpResult[0] . ', 数据=' . $httpResult[1];
        $this->payLogModel->add($payLog);

        $resultData = json_decode($httpResult[1], true);
        if("0000" == $resultData["result_code"]){
            //首次绑卡
            $bindCardModel = DM('GPayBindCard');
            $bindCard = [
                'channelMerchantId' => $channelMerchantId,    //通道商户id
                'memberId'          => $merchantId,             //商户id
                'customerId'        => $customerId,           //商户的用户id，小于等于16位, 平台生成的唯一id, 不同的银行卡号与银行卡号一一对应
                'token'             => $resultData['token'],                //通道授权码
                'channelCode'       => $productCode,          //通道编码
                'firstOrderId'      => $orderId,         //首次绑卡的平台订单id
                'bankCardNo'        => $bankCardNo,           //银行四要素-银行卡号
                'idCardNo'          => $idCardNo,             //银行四要素-身份证号
                'mobile'            => $mobile,               //银行四要素-手机号
                'fullname'          => $fullname,             //银行四要素-开户名
                'id'                => $bindCardModel->genId(),
            ];
            $bindCardModel->add($bindCard);

            //保存绑卡id到订单，确认支付时可以获取
            $orderModel->where(['pay_orderid'=>$orderId])->save(['pay_bind_card_id'=>$bindCard['id']]);
            $this->ajaxSuccess('发送短信成功');
        }else{
            $this->ajaxFail($resultData['result_msg']);
        }
    }

    // 再次预支付，已绑卡，根据银行卡号绑卡，获取验证码
    public function prePayBindCard(){
        $orderId = I("post.orderId"); //平台订单号
        $outTradeId = I('post.outTradeId'); //商户订单号
        $bankCardNo = I("post.bankCardNo");

        //查找订单
        $orderModel = D('Order');
        $order = $orderModel->where(['pay_orderid'=>$orderId, 'out_trade_id'=>$outTradeId])->find();
        if (!$order){
            $this->ajaxFail('订单不存在');
        }
        $merchantId = $order['pay_memberid'];
        $productCode = $order['pay_bankcode'];
        $outTradeId = $order['out_trade_id'];
        $signKey = $order['key'];
        $amount = $order["pay_amount"];
        $channelMerchantId = $order['memberid'];
        $productName = $order['pay_productname'];

        //添加日记
        $logTitle = $this->TITLE . '-再次预支付-';
        $payLog = [
            'merchantId' => $merchantId,
            'productCode' => $productCode,
            'outTradeId' => $outTradeId,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $orderId,
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
            'level' => GPayLogModel::$LEVEL_INFO,
        ];
        $payLog['msg'] = $logTitle . '平台收银台提交的数据: ' . http_build_query($_POST);
        $this->payLogModel->add($payLog);

        if (strlen($bankCardNo) < 10) {
            $this->ajaxFail('请填写正确的银行卡账号');
        }

        //获取绑卡信息
        $bindCardModel = DM('GPayBindCard');
        $bindCard = $bindCardModel->where(['bankCardNo'=>$bankCardNo, 'channelMerchantId'=>$channelMerchantId,
            'status'=>GPayBindCardModel::STATUS_BIND])->find();
        if (!$bindCard){
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $payLog['msg'] = '绑卡记录不存在，data='.http_build_query(I("post"));
            $this->payLogModel->add($payLog);
            $this->ajaxSuccess('不能支付，请联系平台，商户订单号='.$outTradeId);
        }
        $token = $bindCard['token'];
        $customerId = $bindCard['customerId'];
        $payBindCardId = $bindCard['id'];

        // 获取通道网关
        $productUserModel = DM('ProductUser');
        $productUser = $productUserModel->where(['pid'=>$order['pay_bankcode'], 'userid'=>$order['pay_memberid']-10000, 'status'=>1])->find();
        if(!$productUser){
            $this->ajaxFail($_SERVER['HTTP_HOST'].'未分配支付产品给商户, 商户ID='.$order['pay_memberid'].', 支付产品='.$order['pay_bankcode']);
        }
        $channelModel = DM('Channel');
        $channel = $channelModel->where(['id'=>$productUser['channel']])->find();
        $gateway = $channel["gateway"];

        //提交给通道的数据
        $notifyurl = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html';
        $data = [
            'accountId' => $channelMerchantId,//商户编号
            'customerId' => $customerId,//用户编号
            'orderId' => $orderId,//订单号
            'payType' => "1",//支付类型
            'purpose' => $productName,//目的
            'amount' => $amount,//金额
            'responseUrl' => $notifyurl,
            'token' => $token
        ];
        //签名
//        accountId=1120140210111812001& customerId =10001& payType =0& token =AD0346A1E20D18
//157A1791BCB3D762F0& orderId =20150408162102& purpose=学费
//        & amount =0.26& responseUrl =http://www.unspay.com&key=123456
        $signSource = "accountId={$channelMerchantId}&customerId={$customerId}&payType=1&token={$token}"
            . "&orderId={$orderId}&purpose={$productName}&amount={$amount}&responseUrl={$notifyurl}&key={$signKey}";
        $sign = strtoupper(md5($signSource));
        $data['mac'] = $sign;
        $postData = json_encode($data);

        //添加日记
        //$payLog['msg'] = $logTitle . '提交给通道的数据: ' . $postData . '，网关地址=' . $gateway . ', 签名原串=' .$signSource;
        $payLog['msg'] = $logTitle . '提交给通道的数据: ' . $postData;
        $this->payLogModel->add($payLog);

        $httpResult = $this->http_post_data($gateway, $postData);

        //添加日记
        $payLog['msg'] = $logTitle . '通道返回的数据: http状态码=' . $httpResult[0] . ', 数据=' . $httpResult[1];
        $this->payLogModel->add($payLog);

        $resultData = json_decode($httpResult[1], true);
        if("0000" == $resultData["result_code"]){
            //保存绑卡id到订单，确认支付时可以获取
            $orderModel->where(['pay_orderid'=>$orderId])->save(['pay_bind_card_id'=>$payBindCardId]);
            $this->ajaxSuccess('发送短信成功');
        }else{
            $this->ajaxFail($resultData['result_msg']);
        }
    }

    //确认支付
    public function confirmPay(){
        $orderId = I("post.orderId"); //平台订单号
        $outTradeId = I('post.outTradeId'); //商户订单号
        $verifyCode = I("post.verifyCode");

        $orderModel = D('Order');
        $order = $orderModel->where(['pay_orderid'=>$orderId, 'out_trade_id'=>$outTradeId])->find();
        if (!$order){
            $this->ajaxFail('订单不存在');
        }
        $merchantId = $order['pay_memberid'];
        $productCode = $order['pay_bankcode'];
        $signKey = $order['key'];
        $channelMerchantId = $order['memberid'];
        $payBindCardId = $order['pay_bind_card_id'];

        //添加日记
        $logTitle = $this->TITLE . '-确认支付-';
        $payLog = [
            'merchantId' => $merchantId,
            'productCode' => $productCode,
            'outTradeId' => $outTradeId,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $orderId,
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
            'level' => GPayLogModel::$LEVEL_INFO,
        ];
        //添加日记
        $payLog['msg'] = $logTitle . '用户在平台收银台提交的数据: ' . http_build_query($_POST);
        $this->payLogModel->add($payLog);

        if (empty($verifyCode) || strlen($verifyCode) < 6) {
            $this->ajaxFail('请输入正确的验证码');
        }

        // 通过商户id和支付类型获取通道信息, 用户用通道, 通道有子账户，通道属于支付产品
//        $productUserModel = DM('ProductUser');
//        $productUser = $productUserModel->where(['pid'=>$order['pay_bankcode'], 'userid'=>$order['pay_memberid']-10000, 'status'=>1])->find();
//        if(!$productUser){
//            $this->ajaxFail($_SERVER['HTTP_HOST'].'未分配支付产品给商户, 商户ID='.$order['pay_memberid'].', 支付产品='.$order['pay_bankcode']);
//        }
//        $channelModel = DM('Channel');
//        $channel = $channelModel->where(['id'=>$productUser['channel']])->find();
//        $gateway = $channel["gateway"];
        //有两个地址：预支付地址，确认支付地址，暂时这里写死
        $gateway = 'http://114.80.54.75/authPay-front/authPay/confirm';

        //获取绑卡信息
        $bindCardModel = DM('GPayBindCard');
        $bindCard = $bindCardModel->where(['id'=>$payBindCardId])->find();
        if (!$bindCard){
            $this->ajaxFail('请先获取验证码');
        }
        $customerId = $bindCard['customerId'];
        $token = $bindCard['token'];

        $data = [
            'accountId' => $channelMerchantId,//商户编号
            'customerId' => $customerId,//用户编号
            'orderId' => $orderId,
            'vericode' => $verifyCode,
            'token' => $token,
        ];
        //MAC串示例：accountId=112014&customerId=10001&token=CB3D762F0&orderId=8162102&vericode=123456&key=123456
        //验证通道的签名
        $signSource = "accountId={$channelMerchantId}&customerId={$customerId}&token={$token}&orderId={$orderId}&vericode={$verifyCode}";
        $sign = strtoupper(md5($signSource."&key=".$signKey));
        $data['mac'] = $sign;
        $postData = json_encode($data);

        //添加日记
        $payLog['msg'] = $logTitle . '提交给通道的数据: ' . $postData . '，网关地址=' . $gateway;
        $this->payLogModel->add($payLog);

        $httpResult = $this->http_post_data($gateway, $postData);

        //添加日记
        $payLog['msg'] = $logTitle . '通道返回的数据: http状态码=' . $httpResult[0] . ', 数据=' . $httpResult[1];
        $this->payLogModel->add($payLog);

        $resultData = json_decode($httpResult[1], true);
        if("0000" == $resultData["result_code"]){
            if ($resultData['status'] == '00'){ //支付成功
                $return_code = self::CODE_SUCCESS;
                $callbackurl = $this->_site . 'Pay_' . $this->CODE . '_callbackurl.html?orderid='
                    . $orderId . '&returncode=' . $return_code; //页面跳转通知
                $this->ajaxSuccess('处理成功', ['callbackurl'=>$callbackurl]);
            }elseif($resultData['status'] == '10'){ //处理中
                //添加日记
                $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
                $payLog['msg'] = $logTitle . '支付处理中';
                $this->payLogModel->add($payLog);

                $return_code = self::CODE_PENDING;
                $callbackurl = $this->_site . 'Pay_' . $this->CODE . '_callbackurl.html?orderid='
                    . $orderId . '&returncode=' . $return_code; //页面跳转通知
                $this->ajaxSuccess('处理成功', ['callbackurl'=>$callbackurl]);
            }else{ //支付失败
                //添加日记
                $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
                $payLog['msg'] = $logTitle . '支付失败';

                $return_code = self::CODE_FAIL;
                $this->ajaxFail($resultData["result_msg"]);
            }
        }else{ //支付失败
            //添加日记
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $payLog['msg'] = $logTitle . '支付失败';
            $this->payLogModel->add($payLog);

            $return_code = self::CODE_FAIL;
            $this->ajaxFail($resultData["result_msg"]);
        }
    }

    public function callbackurl(){
        $orderid = I('get.orderid');
        $returncode = I('get.returncode');

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid'=>$orderid])->find();
        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 平台订单号='.$orderid.', 返回数据: '.http_build_query($_GET);
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            $returncode = self::CODE_FAIL;
        }

        //页面回调商户地址，不能作为支付成功的依据
        $this->callbackMerchant($returncode, $order);
    }

    /**
     * 通道通知
     * 该通道只通知一次，不需返回给该通道成功或失败标志
     * 该通道的管理后台没有补发通知的机制
     * 上游签名 MAC串示例：
     *   accountId=1120140210111812001&orderId=20150408162102&amount=0.26
     *   &result_code=0000&result_msg=余额不足&key=123456
     *
     * result_code=0000&amount=2.00&result_msg=&mac=A97225AA263369921430DA61A45BA26F&orderId=Z2018032710154264223972
     */
    public function notifyurl(){
        $data = I('post.','');
        $rawData = http_build_query($data);

        $orderId = $data['orderId'];
        $channelAmount = $data['amount'];
        $result_code = $data['result_code'];
        $result_msg = $data['result_msg'];
        $channelMerchantId = '2120180308160916001';
        $channelSign = $data['mac'];

        //添加日记
        $payLog = [
            'merchantId' => null,
            'productCode' => null,
            'outTradeId' => null,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $orderId,
            'level' => GPayLogModel::$LEVEL_INFO,
            'type' => GPayLogModel::$TYPE_CHANNEL_NOTIFY,
        ];
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.$rawData;
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid' => $orderId])->find();

        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 平台订单号='.$orderId.', 返回数据: '.$rawData;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail1');
        }

        $merchantId = $order['pay_memberid'];
        $productCode = $order['pay_bankcode'];
        $outTradeId = $order['out_trade_id'];
        $signKey = $order['key'];
        $amount = $order["pay_amount"];

        $payLog['merchantId'] = $merchantId;
        $payLog['productCode'] = $productCode;
        $payLog['outTradeId'] = $outTradeId;


        /*签名示例：accountId=1120140210111812001& orderId =20150408162102& amount =0.26& result_code =000
0&result_msg=余额不足&key=123456*/
        //验证通道的签名
        $signSource = "accountId={$channelMerchantId}&orderId={$orderId}&amount={$channelAmount}&result_code={$result_code}&result_msg={$result_msg}";
        $sign = strtoupper(md5($signSource."&key=".$signKey));
        if($channelSign != $sign){
            $payLog['msg'] = $this->TITLE.'-验签失败, 通道签名='.$channelSign.', 平台签名='.$sign;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail2');
        }

        //验证是否合法订单
        $channelAmountStr = format2Decimal($channelAmount);
        $amountStr = format2Decimal($amount);
        if ($channelAmountStr != $amountStr) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为元，通道传的金额='.$channelAmount.', 平台的金额='.$amount;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit("fail3");
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit("fail4");
        }

        if($result_code == '0000'){ //支付成功
            /*if ($this->EditMoney($orderId, '', 0)){
                exit('ok');
            }else{
                exit('fail');
            }*/
            //添加日记

            $payLog['msg'] = $this->TITLE.'-notify支付成功, 修改订单状态为: 成功,未返回';
            $this->payLogModel->add($payLog);
            $this->EditMoney($orderId, '', 0);
            die('ok');
        }else {
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付失败，失败信息：' . $result_msg;
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $this->payLogModel->add($payLog);
            exit('fail5');
        }
    }

    /**
     * 发送短信验证码
     */
    public function sendVerifyCode(){
        $orderId = I("post.orderId"); //平台订单号
        $outTradeId = I('post.outTradeId'); //商户订单号

        $orderModel = D('Order');
        $order = $orderModel->where(['pay_orderid'=>$orderId, 'out_trade_id'=>$outTradeId])->find();
        if (!$order){
            $this->ajaxFail('订单不存在');
        }
        $merchantId = $order['pay_memberid'];
        $productCode = $order['pay_bankcode'];
        $signKey = $order['key'];
        $channelMerchantId = $order['memberid'];
        $payBindCardId = $order['pay_bind_card_id'];

        //添加日记
        $logTitle = $this->TITLE . '-发送短信-';
        $payLog = [
            'merchantId' => $merchantId,
            'productCode' => $productCode,
            'outTradeId' => $outTradeId,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $orderId,
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
            'level' => GPayLogModel::$LEVEL_INFO,
        ];
        //添加日记
        $payLog['msg'] = $logTitle . '用户在平台收银台提交的数据: ' . http_build_query($_POST);
        $this->payLogModel->add($payLog);

        //暂时这里写死
        $gateway = 'http://114.80.54.75/authPay-front/authPay/sendVercode';

        //获取绑卡信息
        $bindCardModel = DM('GPayBindCard');
        $bindCard = $bindCardModel->where(['id'=>$payBindCardId])->find();
        if (!$bindCard){
            $this->ajaxFail('发送短信前，请先获取验证码');
        }
        $customerId = $bindCard['customerId'];
        $token = $bindCard['token'];
        $mobile = $bindCard['mobile'];

        $data = [
            'accountId' => $channelMerchantId,//商户编号
            'customerId' => $customerId,//用户编号
            'orderId' => $orderId,
            'phoneNo' => $mobile,
            'token' => $token,
        ];
        //MAC串示例：accountId=112021&customerId=10001&token=AD03B32F0&orderId=20140816&phoneNo=13888888888&key=123456
        //验证通道的签名
        $signSource = "accountId={$channelMerchantId}&customerId={$customerId}&token={$token}&orderId={$orderId}&phoneNo={$mobile}";
        $sign = strtoupper(md5($signSource."&key=".$signKey));
        $data['mac'] = $sign;
        $postData = json_encode($data);

        //添加日记
        $payLog['msg'] = $logTitle . '提交给通道的数据: ' . $postData . '，网关地址=' . $gateway;
        $this->payLogModel->add($payLog);

        $httpResult = $this->http_post_data($gateway, $postData);

        //添加日记
        $payLog['msg'] = $logTitle . '通道返回的数据: http状态码=' . $httpResult[0] . ', 数据=' . $httpResult[1];
        $this->payLogModel->add($payLog);

        $resultData = json_decode($httpResult[1], true);
        if("0000" == $resultData["result_code"]){
            $this->ajaxSuccess('发送短信成功');
        }else{
            $this->ajaxFail($resultData['result_msg']);
        }
    }

    /**
     * 订单状态查询接口
     * @url http://www.zhifujia.cc/Pay_YinShengBaoKJ_query.html?orderid=123
     */
    public function query(){
        $orderId = I('request.orderid');

        //添加日记
        $logTitle = $this->TITLE . '-订单状态查询-';
        $payLog = [
            'merchantId' => null,
            'productCode' => null,
            'outTradeId' => null,
            'channelMerchantId' => null,
            'orderId' => $orderId,
            'level' => GPayLogModel::$LEVEL_INFO,
            'type' => GPayLogModel::$TYPE_CHANNEL_QUERY,
        ];

        //查找订单
        $orderModel = DM("Order");
        $order = $orderModel->where(['pay_orderid' => $orderId])->find();
        if (empty($order)) {
            $payLog['msg'] = $this->TITLE . '-平台订单不存在, 平台订单号=' . $orderId;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            $this->ajaxFail($payLog['msg']);
        }

        $merchantId = $order['pay_memberid'];
        $productCode = $order['pay_bankcode'];
        $outTradeId = $order['out_trade_id'];
        $signKey = $order['key'];
        $amount = $order["pay_amount"];
        $channelMerchantId = $order['memberid'];

        //添加日记
        $payLog['merchantId'] = $merchantId;
        $payLog['productCode'] = $productCode;
        $payLog['outTradeId'] = $outTradeId;
        $payLog['channelMerchantId'] = $channelMerchantId;

        //如果订单已经支付,直接返回查询成功
        if($order['pay_status'] == OrderModel::PAY_STATUS_PAID
            || $order['pay_status'] == OrderModel::PAY_STATUS_PAID_RETURN){
            $this->ajaxSuccess('查询成功, 订单已支付');
        }

        //提交到通道的数据
        $gateway = 'http://114.80.54.75/authPay-front/authPay/queryOrderStatus';
        $data = [
            'accountId' => $channelMerchantId,//通道商户编号
            'orderId' => $orderId,//订单号
        ];
        $signSource = "accountId={$channelMerchantId}&orderId={$orderId}";
        $sign = strtoupper(md5($signSource."&key=".$signKey));
        $data['mac'] = $sign;
        $postData = json_encode($data);

        //添加日记
        $payLog['msg'] = $logTitle . '提交给通道的数据: ' . $postData . '，网关地址=' . $gateway;
        $this->payLogModel->add($payLog);

        $httpResult = $this->http_post_data($gateway, $postData);

        //添加日记
        $payLog['msg'] = $logTitle . '通道返回的数据: http状态码=' . $httpResult[0] . ', 数据=' . $httpResult[1];
        $this->payLogModel->add($payLog);

        $resultData = json_decode($httpResult[1], true);
        $channelAmount = $resultData['amount'];
        if ("0000" == $resultData["result_code"]) {
            if($resultData['status'] == '00'){ //查询支付成功,修改订单状态
                //验证是否合法订单
                $channelAmountStr = format2Decimal($channelAmount);
                $amountStr = format2Decimal($amount);
                if ($channelAmountStr != $amountStr) {
                    $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为元，通道传的金额='.$channelAmount.', 平台的金额='.$amount;
                    $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
                    $this->payLogModel->add($payLog);
                    $this->ajaxFail($payLog['msg']);
                }

                if($this->EditMoney($orderId, '', 0)){
                    $this->ajaxSuccess('查询成功, 修改订单状态');
                }else{
                    $payLog['msg'] = $this->TITLE.'修改订单状态失败';
                    $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
                    $this->payLogModel->add($payLog);
                    $this->ajaxFail('查询失败');
                }
            }elseif($resultData['status'] == '10'){ //支付处理中
                $this->ajaxFail('支付处理中, '.$resultData['desc']);
            }else{//支付失败
                $this->ajaxFail('支付处理中, '.$resultData['desc']);
            }
        } else {
            fclose($fp);
            $this->ajaxFail($resultData['result_msg']);
        }
    }

    private function SignParamsToString($params) {
        $sign_str = '';
        // 排序
       // ksort($params);

        $buff = "";
        foreach ($params as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    private function http_post_data($url, $data_string) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json; charset=utf-8",
                "Content-Length: " . strlen($data_string))
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array($return_code, $return_content);
    }

}
?>