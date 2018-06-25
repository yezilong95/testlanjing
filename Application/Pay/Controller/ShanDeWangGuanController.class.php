<?php
/**
 * Created by PhpStorm.
 * author: 叶子龙
 * Date: 2017-02-01
 */
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 衫德-网关
 *  pay_type=015
 *
 * Class LanChuangWangGuanController
 * @package Pay\Controller
 * @author 叶子龙
 */
class ShanDeWangGuanController extends PayController
{
    private $CODE = 'ShanDeWangGuan';
    private $TITLE = '衫德-网关';
    private $PUB_KEY_PATH = './cert/ShanDe/sand.cer'; //公钥文件
    private $PRI_KEY_PATH = 'cert/ShanDe/ShanDePrivace.pfx'; //私钥文件
    private $CERT_PWD = ',ki89ol.'; //私钥证书密码

    //支付
    public function Pay($array)
    {

        header("Content-Type:text/html;charset=UTF-8");
        date_default_timezone_set("Asia/Shanghai");

        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $body = I('request.pay_productname','链子');

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
        $return["notifyurl"] || $return["notifyurl"] = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html';
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html';
        //必要支付金额最小为15元


        $pay_orderid = $return["orderid"]; //系统订单号
        $memberid = $return["memberid"]; //商户号
        $mch_id = $return['mch_id']; //通道商户号
        $amount = $return["amount"]*100;
        $pay_amount=str_pad($amount,12,"0",STR_PAD_LEFT);
        $gateway = $return["gateway"];
        $signkey = $return['signkey'];
        /*if($return["amount"] < 2){
            exit('支付金额最小为2元');
        }*/
        /*if($return["amount"] > 20000){
            exit('单笔支付限额20000元');
        }*/
        $data = array(
            'head' => array(
                'version' => '1.0',
                'method' => 'sandpay.trade.pay',
                'productId' => '00000007',
                'accessType' => '1',
                'mid' => $mch_id,
                'channelType' => '07',
                'reqTime' => date('YmdHis', time())
            ),
            'body' => array(
                'orderCode' => $pay_orderid,
                'totalAmount' => $pay_amount,
                'subject' => $body,
                'body' => $body,
                'txnTimeOut' => date("YmdHis")+300,
                'payMode' => 'bank_pc',
                'payExtra' => json_encode(array('payType' => '1', 'bankCode' => '01030000')),
                'clientIp' => $_SERVER["REMOTE_ADDR"],
                'notifyUrl' => $return["notifyurl"],
                'frontUrl' => $return['callbackurl'],
                'extend' => ''
            )
        );
        //签名

        $prikey = $this->loadPk12Cert($this->PRI_KEY_PATH, $this->CERT_PWD);

        $sign = $this->sign($data, $prikey);

        // step3: 拼接post数据
        $post = array(
            'charset' => 'utf-8',
            'signType' => '01',
            'data' => json_encode($data),
            'sign' => $sign
        );

        $postdata = json_encode($post,JSON_UNESCAPED_UNICODE);

        //添加日记
        $payLog = [
            'merchantId' => $return["memberid"], //商户id
            'productCode' => $productCode, //支付类型
            'outTradeId' => $out_trade_id, //商户订单号
            'channelMerchantId' => $return['mch_id'], //通道商户id
            'orderId' => $return['orderid'], //平台订单号
            'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL, //日记类型
        ];
        $payLog['msg'] = $this->TITLE.'-提交的数据: '.$postdata;
        $this->payLogModel->add($payLog);

        // step4: post请求
        $result = $this->http_post_json($gateway . '/order/pay', $post);
        $arr =$this-> parse_result($result);

        //添加日记
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.json_encode($arr,JSON_UNESCAPED_UNICODE);
        $this->payLogModel->add($payLog);

        //step5: 公钥验签
//step5: 公钥验签
        $pubkey =$this-> loadX509Cert($this->PUB_KEY_PATH);
        try {
           $this->pub_verify($arr['data'], $arr['sign'], $pubkey);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
        }

        // step6： 获取credential
        $data = json_decode($arr['data'], true);
        if ($data['head']['respCode'] == "000000") {
            $credential = $data['body']['credential'];
            $this->jump($credential);
        } else {
            dump($arr['data']);
        }

    }

    /**
     * 到前端利用衫德官方js进行跳转
     */
    public function jump($data){
        $this->assign("credential",$data);
        $this->display("ShanDe/post");
    }

    /**
     * 通道回调, 再回调商户
     */
    public function callbackurl()
    {
        //返回参数$data:
        // resv=&bizType=000000&txnSubType=01&signature=SwwOhA1au%2BMNVcF00iqS4Q%3D%3D&succTime=&settleAmount=&settleCurrency=&txnType=01&settleDate=20180310&version=1.0.0&merResv1=&accessType=0&respMsg=5Lqk5piT5oiQ5Yqf&txnTime=20180310155639&merId=929040095023494&currency=CNY&respCode=1001&channelId=chinaGpay&txnAmt=0000000000000001&signMethod=MD5&merOrderId=NC41803101961377"

        $data = $_POST;
        $rawData = json_encode($data,JSON_UNESCAPED_UNICODE);
        $dataarr = json_decode($data['data'],true);
        $orderId = $dataarr['body']['orderCode'];
        //$channelMerchantId = $_POST['merId']; //不是通道商户号

        //添加日记
        $payLog = [
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
            'productCode' => '912',
        ];
        $payLog['msg'] = $this->TITLE.'-返回数据: '.$rawData;
        $this->payLogModel->add($payLog);

        //查找订单
        $orderModel = M("Order");
        $order = $orderModel->where(['pay_orderid' => $orderId, 'pay_tongdao' => $this->CODE])->find();
        if(empty($order)){
            $payLog['msg'] = $this->TITLE.'-平台订单不存在, 通道订单号='.$orderId.', 返回数据: '.http_build_query($_POST);
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit($payLog['msg']);
        }

        //添加日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];
        $payLog['orderId'] = $order['pay_orderid'];


        if($order['pay_status'] <> 0){
            //添加日记
            $payLog['msg'] = $this->TITLE.'-成功, 当前订单状态='.$order['pay_status'];
            $this->payLogModel->add($payLog);

            $this->EditMoney($order['pay_orderid'], '', 1);
        }else{;
            //添加日记
            $payLog['msg'] = $this->TITLE.'-失败, 当前订单状态='.$order['pay_status'];
            $this->payLogModel->add($payLog);

            exit("error");
        }
    }

    //异步通知
    public function notifyurl()
    {
        //返回参数$data:
        // {"sign":"wfeWcylM4\/Rzeg\/inErUJDaNzeep\/xDkDFCvERHqYA5aAtiJ0yYqhP+B8lLPzB2leww7Z6uFRpMsoIMTWWaiiRnYQJNFkNIW3\/tUF5cRaZuw+piosseWAtWnBHlqG3206vC2OWrLlk1ATKoOFA6U36rK7Pok7j9gsvIUkvUVbUJh6cy+tJpPerCq2uumJjoCekg80jaXkKmLWkhvOFPnU6jiyhbQIEix3Ecw3g28sE3ZdDCEq1XvpmDsLkFPZby\/53bsfaekXLdVqAz5gvsmKw2FzTBOkv\/ItRYN0d1W+gvVHZF142DiPiq5NHzZSm6h4VyvurayNo4lpJZd4bMZDQ==","extend":"","signType":"01","data":"{&quot;body&quot;:{&quot;orderCode&quot;:&quot;TT2018052317214101693846&quot;,&quot;tradeNo&quot;:&quot;2018052317215909390997627848&quot;,&quot;clearDate&quot;:&quot;20180523&quot;,&quot;orderStatus&quot;:&quot;1&quot;,&quot;payTime&quot;:&quot;20180523172159&quot;,&quot;buyerPayAmount&quot;:&quot;000000000001&quot;,&quot;accNo&quot;:&quot;&quot;,&quot;midFee&quot;:&quot;000000000020&quot;,&quot;totalAmount&quot;:&quot;000000000001&quot;,&quot;mid&quot;:&quot;15898373&quot;,&quot;discAmount&quot;:&quot;000000000000&quot;,&quot;bankserial&quot;:&quot;&quot;},&quot;head&quot;:{&quot;respCode&quot;:&quot;000000&quot;,&quot;respTime&quot;:&quot;20180523172242&quot;,&quot;version&quot;:&quot;1.0&quot;}}","charset":"UTF-8"}

        //$data = $GLOBALS['HTTP_RAW_POST_DATA'];


        $post = $_POST;
        $datainpost =stripslashes($post['data']);
        $dataarr = json_decode($datainpost,true);
        $dbody = $dataarr['body'];

        $rawData = json_encode($post,JSON_UNESCAPED_UNICODE);
        $merchantId = null;
        $productCode = null;
        $outTradeId = null;
        $channelMerchantId = $dbody['mid'];
        $orderId =$dbody['orderCode'];
        $amount =null;

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

        $verify_sign =  $post['sign'];
        $verify_pubkey =$this-> loadX509Cert($this->PUB_KEY_PATH);
        $new_sign = $this-> pub_verify($datainpost, $verify_sign, $verify_pubkey);
        if (!$new_sign) {
            $payLog['msg'] = $this->TITLE.'-验签失败, 通道签名='.$verify_sign.', 平台签名='.$new_sign;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }


        //填充日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];

        //验证是否合法订单
        /*$orderAmount = strval($order["pay_amount"]*100);
        if ($orderAmount != $amount) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为分，通道传的金额='.$amount.', 平台的金额='.$orderAmount;
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            //exit('fail'); //@todo 金额浮点数暂时不比较，有可能不相等，先运行一段时间在打开
        }*/
        if ($order["memberid"] != $channelMerchantId) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$channelMerchantId.', 平台的通道商户号='.$order["memberid"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        if ($dbody['orderStatus'] == '1'){
            $this->EditMoney($orderId, $this->CODE, 0);

            //添加日记
            $payLog['msg'] = $this->TITLE.'-支付成功, 修改订单状态为: 成功,未返回';
            $this->payLogModel->add($payLog);

            exit("respCode=000000");
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
    /**
     * 发送请求
     * @param $url
     * @param $param
     * @return bool|mixed
     * @throws Exception
     */
    private function http_post_json($url, $param)
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $param = http_build_query($param);
        try {

            $ch = curl_init();//初始化curl
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //正式环境时解开注释
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $data = curl_exec($ch);//运行curl
            curl_close($ch);

            if (!$data) {
                throw new \Exception('请求出错');
            }

            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
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

    public function isMobile()
    {

        /*移动端判断*/
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA']))
        {
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT']))
        {
            $clientkeywords = array ('nokia',
                'sony',
                'ericsson',
                'mot',
                'samsung',
                'htc',
                'sgh',
                'lg',
                'sharp',
                'sie-',
                'philips',
                'panasonic',
                'alcatel',
                'lenovo',
                'iphone',
                'ipod',
                'blackberry',
                'meizu',
                'android',
                'netfront',
                'symbian',
                'ucweb',
                'windowsce',
                'palm',
                'operamini',
                'operamobi',
                'openwave',
                'nexusone',
                'cldc',
                'midp',
                'wap',
                'mobile'
            );
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            {
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT']))
        {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
            {
                return true;
            }
        }
        return false;
    }

    /**
     *获取秘钥
     */
    public function loadPk12Cert($path, $pwd)
    {
        try {
            $file = file_get_contents($path);
            if (!$file) {
                throw new \Exception('loadPk12Cert::file
					_get_contents');
            }

            if (!openssl_pkcs12_read($file, $cert, $pwd)) {
                throw new \Exception('loadPk12Cert::openssl_pkcs12_read ERROR');
            }
            return $cert['pkey'];
        } catch (\Exception $e) {
            throw $e;
        }
    }
    /**
     * 私钥签名
     * @param $plainText
     * @param $path
     * @return string
     * @throws Exception
     */
    public function sign($plainText, $path)
    {
        $plainText = json_encode($plainText);
        try {
            $resource = openssl_pkey_get_private($path);
            $result = openssl_sign($plainText, $sign, $resource);
            openssl_free_key($resource);

            if (!$result) {
                throw new \Exception('签名出错' . $plainText);
            }

            return base64_encode($sign);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    public function parse_result($result)
    {
        $arr = array();
        $response = urldecode($result);
        $arrStr = explode('&', $response);
        foreach ($arrStr as $str) {
            $p = strpos($str, "=");
            $key = substr($str, 0, $p);
            $value = substr($str, $p + 1);
            $arr[$key] = $value;
        }

        return $arr;
    }
     /**
     * 获取公钥
     * @param $path
     * @return mixed
     * @throws Exception
     */
    public function loadX509Cert($path)
    {
        try {
            $file = file_get_contents($path);
            if (!$file) {
                throw new \Exception('loadx509Cert::file_get_contents ERROR');
            }

            $cert = chunk_split(base64_encode($file), 64, "\n");
            $cert = "-----BEGIN CERTIFICATE-----\n" . $cert . "-----END CERTIFICATE-----\n";

            $res = openssl_pkey_get_public($cert);
            $detail = openssl_pkey_get_details($res);
            openssl_free_key($res);

            if (!$detail) {
                throw new \Exception('loadX509Cert::openssl_pkey_get_details ERROR');
            }

            return $detail['key'];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 公钥验签
     * @param $plainText
     * @param $sign
     * @param $path
     * @return int
     * @throws Exception
     */
    public function pub_verify($plainText, $sign, $path)
    {
        $resource = openssl_pkey_get_public($path);
        $result = openssl_verify($plainText, base64_decode($sign), $resource);
        openssl_free_key($resource);

        if (!$result) {
            throw new \Exception('签名验证未通过,plainText:' . $plainText . '。sign:' . $sign, '02002');
        }

        return $result;
    }

}