<?php
/**
 * Created by PhpStorm.
 * author: 尚军邦
 * Date: 2017-02-01
 */
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 瀚银-快捷
 *  pay_type=015
 *  支持在线支付的银行和限额：https://static.95516.com/static/help/detail_38.html
 * Class HanYinKJController
 * @package Pay\Controller
 * @author 叶子龙
 */
class HanYinKJController extends PayController
{
    private $CODE = 'HanYinKJ';
    private $TITLE = '瀚银-快捷';

    //支付
    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");
        date_default_timezone_set("Asia/Shanghai");

        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $body = I('request.pay_productname');

        $notifyurl = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => $this->CODE, // 通道名称
            'title' => $this->TITLE,
            'exchange' => 1, // 金额比例
            'gateway' => "",
            'orderid' => "", //系统订单号
            'out_trade_id' => $out_trade_id, //外部商户订单号
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        //必要支付金额最小为15元
        if($return["amount"] < 100){
            exit('单笔支付最低金额为100元');
        }
        if($return["amount"] > 20000){
            exit('单笔支付限额20000元');
        }

        $pay_orderid = $return["orderid"]; //系统订单号
        $memberid = $return["memberid"]; //商户号
        $mch_id = $return['mch_id']; //通道商户号
        $amount = $return["amount"]*100;
        $appid = $return['appid'];
        $appsecret = $return['appsecret'];
        $gateway = $return["gateway"];
        $signkey = $return['signkey'];
        $data = array(
            'insCode' => $appid,
            'insMerchantCode' => $appsecret,
            'hpMerCode' => $mch_id,
            'orderNo' => $pay_orderid,
            'orderTime' => date("YmdHis"),
            'currencyCode' => '156',
            'orderAmount' => $amount,
            'productType' => '100000',
            'nonceStr' => 'LJ'.rand(100,999),
            'paymentType' => '2008',
            'frontUrl' => $callbackurl,
            'backUrl' => $notifyurl,
        );
        $this->assign("out_trade_id",$out_trade_id);
        $this->assign("pay_amount",$return["amount"]);
        $this->assign("arraystr",urlencode(http_build_query($data)));
        $this->assign("return",urlencode(http_build_query($return)));
        $this->display('HanYinKJCashier/index');

    }
    public function PayAgain(){
        $out_trade_id = I('request.pay_orderid'); //商户订单号
        //$flag = I('request.flag');
        $arraystr =urldecode(trim(I('request.arraystr')));
        $returnStr =urldecode(trim(I('request.ret')));
        $bankCardNo = I('request.bankCardNo');
        $userName = I('request.userName');
        $idNo = I('request.idNo');
        $phone = I('request.phone');
        parse_str($arraystr,$data);
        parse_str($returnStr,$return);

        //获取请求的url地址
        $url = $return["gateway"];
        $data['name']=$userName;
        $data['idNumber']=$idNo;
        $data['accNo']=$bankCardNo;
        $data['telNo']=$phone;



        //签名
        $hmac = $data['insCode'].'|'.$data['insMerchantCode'].'|'.$data['hpMerCode'].'|'.$data['orderNo'].'|'.$data['orderTime'].'|'.$data['orderAmount'].'|'.$data['name'].'|'.$data['idNumber'].'|'.$data['accNo'].'|'.$data['telNo'].'|'.$data['productType'].'|'.$data['paymentType'].'|'.$data['nonceStr'].'|'.$return['signkey'];

        $md5str = md5($hmac);
        $data["signature"]= $md5str;

        $postdata = json_encode($data,JSON_UNESCAPED_UNICODE);

        //添加日记
        //添加日记
        $payLog = [
            'merchantId' => $return["memberid"],
            'productCode' => $return['bankcode'],
            'outTradeId' => $out_trade_id,
            'channelMerchantId' => $return['mch_id'],
            'orderId' => $return['orderid'],
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
        ];
        $payLog['msg'] = $this->TITLE.'-提交的数据: '.$postdata;
        $this->payLogModel->add($payLog);

        $result = createForm($return['gateway'], $data);
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.$result;
        $this->payLogModel->add($payLog);
        echo $result;
    }

    /**
     * 通道回调, 再回调商户
     */
    public function callbackurl()
    {
        $rawData = http_build_query($_REQUEST);
        $orderid = $_REQUEST["orderNo"];
        //添加日记
        $payLog = [
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
            'productCode' => '908',
        ];
        $payLog['msg'] = $this->TITLE.'-返回数据: '.$rawData;
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid'=>$orderid])->find();
        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 平台订单号='.$orderid.', 返回数据: '.$rawData;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit($payLog['msg']);
        }

        if($order['pay_status'] <> 0){
            //添加日记
            $payLog['msg'] = $this->TITLE.'-成功, 当前订单状态='.$order['pay_status'];
            $this->payLogModel->add($payLog);

            $this->EditMoney($orderid, '', 1);
        }else{
            //添加日记
            $payLog['msg'] = $this->TITLE.'-失败, 当前订单状态='.$order['pay_status'];
            $this->payLogModel->add($payLog);
            exit("error");
        }
    }

    //异步通知
    public function notifyurl()
    {
        /*{"statusCode":"00","transDate":"20180511132941","transAmount":"10","orderNo":"LJCZ2018051113292343080414","hpMerCode":"HBMSDTDIRWK4P@20180508095606","transSeq":"579766621","statusMsg":"[00]\u4ea4\u6613\u6210\u529f","actualAmount":"9","signature":"6394283E6CCBCCCBDA74F92C4BAE315F","transStatus":"00","Pay_HanYinKJ_notifyurl_html":""}*/

        //$data = $GLOBALS['HTTP_RAW_POST_DATA'];


        $data = $_REQUEST;
        $rawData = json_encode($data);
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $data['hpMerCode'];
        $orderId = $data['orderNo'];
        $amount =strval($data['transAmount']);

        //添加日记
        $payLog = [
            'merchantId' => $merchantId,
            'productCode' => $productCode,
            'outTradeId' => $outTradeId,
            'channelMerchantId' => $channelMerchantId,
            'orderId' => $orderId,
            'type' => GPayLogModel::$TYPE_CHANNEL_NOTIFY,
        ];
        $payLog['msg'] = $this->TITLE.'-notify返回的数据: '.$rawData;
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid' => $orderId])->find();
        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在';
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }



        //验证通道的签名


        $signkey = $order['key'];
        $hmac = $data['hpMerCode'].'|'.$data['orderNo'].'|'.$data['transDate'].'|'.$data['transStatus'].'|'.$data['transAmount'].'|'.$data['actualAmount'].'|'.$data['transSeq'].'|'.$data['statusCode'].'|'.$data['statusMsg'].'|'.$signkey;
        $md5str =strtoupper(md5($hmac));
        if($md5str != $data['signature']){
            $payLog['msg'] = $this->TITLE.'-验签失败, 通道签名='.$data['signature'].', 平台签名='.$md5str;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        //填充日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];

        //验证是否合法订单
        $orderAmount = strval($order["pay_amount"]*100);
        if ($orderAmount != $amount) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为分，通道传的金额='.$amount.', 平台的金额='.$orderAmount;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            //exit('fail'); //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        if ($data['transStatus'] == '00'){
            $this->EditMoney($orderId, $this->CODE, 0);

            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付成功, 修改订单状态为: 成功,未返回';
            $this->payLogModel->add($payLog);

            exit('success');
        } else {
            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付失败';
            $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            $this->payLogModel->add($payLog);

            exit('fail');
        }
    }

    /** *利用google api生成二维码图片
     * $content：二维码内容参数
     * $size：生成二维码的尺寸，宽度和高度的值
     * $lev：可选参数，纠错等级
     * $margin：生成的二维码离边框的距离
     */
    private function object_to_array($obj) {
        $obj = (array)$obj;
        foreach ($obj as $k => $v) {
            if (gettype($v) == 'resource') {
                return;
            }
            if (gettype($v) == 'object' || gettype($v) == 'array') {
                $obj[$k] = (array)object_to_array($v);
            }
        }
        return $obj;
    }
    private function SignParamsToString($params) {
        $sign_str = '';
        // 排序
        ksort($params);

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

    private function getBytes($string) {
        $bytes = array();
        for($i = 0; $i < strlen($string); $i++){
            $bytes[] = ord($string[$i]);
        }
        return $bytes;
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

    function create_erweima($content, $size = '200', $lev = 'L', $margin= '0') {
        $content = urlencode($content);
        $image = 'http://chart.apis.google.com/chart?chs='.$size.'x'.$size.'&amp;cht=qr&chld='.$lev.'|'.$margin.'&amp;chl='.$content;
        return $image;
    }

    public function onlinequery(){

        //$orderid = 'P180202959250344481652736';
        $orderid = I('request.orderid');

        if ($orderid==""){
            //不传订单号，就自己取一个
            $model = M("Order");

            $condition['pay_status']=0;
            $condition['pay_tongdao']='XinJieWangGuan';
            $condition['num']=array('lt',10);

            $list = $model->where($condition)->limit(1)->order('num');

            $orderid=$list->getField("pay_orderid");
        }
        $dbnum = M("channel_account")->where(array("id"=>"274"))->field("appid,mch_id,signkey")->find();
        if ($orderid!=""){

            //调用上游查询
            $data = array(
                'version' => 'V001',
                'agre_type' => 'Q',
                'inst_no' => $dbnum['appid'],//机构号
                'merch_id' => $dbnum['mch_id'],//商户号
                'merch_order_no' => $orderid,//订单号
                'query_id' => '0',
                'order_datetime' => date("Y-m-d H:i:s")
            );

            $hmac = $this->SignParamsToString($data);
            $bemd5= $hmac."&key=".$dbnum['signkey'];
            $md5str = md5($bemd5);
            $data["sign"]= $md5str;
            $postdata = json_encode($data,JSON_UNESCAPED_UNICODE);
            $result = $this->http_post_data('http://online.esoonpay.com:28888/gateway/payment', $postdata);
            $json_obj = json_decode($result[1],true);

            if ($json_obj["retcode"]=="00"){ //已支付
                //$cmd = M('Order')->where(['pay_orderid'=>$orderid])->setField("pay_status",1);因为还要改金额，所以不能只简单的改状态
                $this->EditMoney($orderid, $this->CODE, 0);
            }

            $cmd = M('Order')->where(['pay_orderid'=>$orderid])->setInc("num",1);//查一次后加1，最多查10次
        }
    }

}