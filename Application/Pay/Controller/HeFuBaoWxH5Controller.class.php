<?php
namespace Pay\Controller;
use Common\Model\GPayLogModel;

/**
 * 合付宝-微信H5支付
 *  //注意: 支付金额最小1元, 最大200元
 *  //正式网关:http://www.dulpay.com/api/pay/h5
 *  //测试网关:http://39.108.113.220/api/pay/h5
 * Class HeFuBaoWxH5Controller
 * @package Pay\Controller
 * @author 黄治华
 */
class HeFuBaoWxH5Controller extends PayController
{
    private $CODE = 'HeFuBaoWxH5';
    private $TITLE = '合付宝-微信H5';
    private $TRADE_TYPE = '8';

    public function Pay($array)
    {
        $out_trade_id = I('request.pay_orderid'); //商户订单号
        //$body = I('request.pay_productname');
        $body = '购买钻石点卡';
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
        $pay_orderid = $return["orderid"]; //系统订单号

        if ($return) {
//            if($return["amount"] < 1){
//                exit('支付金额最小为1元');
//            }
//            if($return["amount"] > 200){
//                exit('单笔支付限额200元');
//            }

            //提交到通道接口的参数
            $notifyurl = $this->_site . 'Pay_' . $this->CODE . '_notifyurl.html'; //异步通知
            $callbackurl = $this->_site . 'Pay_' . $this->CODE . '_callbackurl.html?orderid=' . $pay_orderid; //页面跳转通知
            $data = array(
                'amount' => 100 * $return["amount"], // 订单总金额，整形，此处单位为分
                'payType' => $this->TRADE_TYPE, //交易类型
                'notifyUrl' => $notifyurl,
                'YUL1' => $callbackurl,
                'YUL2' => 'WAP', //当 payType = 8,10,12时，该字段必输。固定值：IOS或ANDROID或WAP,注释：IOS移动应用：IOS, 安卓移动应用：ANDROIDWAP, WAP网站应用：WAP
                'bindId' => $return['appid'],
                'bizType' => '01',
                'goodsName' => $body,
                'buyerName' => $this->encryptData("龙讯", './cert/HeFuBao/'.$return['mch_id'].'.cer'),
                'buyerId' => $return["memberid"],
                'tranCode' => 'YS1003',
                'merchantNo' => $return['mch_id'],
                'version' => 'v1',
                'channelNo' => '05',
                'tranFlow' => $pay_orderid, // 对于通道为商户订单号, 对于系统为系统订单号
                'tranDate' => date("Ymd"),
                'tranTime' => date("His"),
                'remark' => 'remark',
                'ext2' => './cert/HeFuBao/'.$return['mch_id'].'.cer', //商户扩展
            );

            //签名
            $data['sign'] = $this->sign($data, './cert/HeFuBao/'.$return['mch_id'].'.pfx', $return['signkey']);

            addSyslog($logTitle.'提交给通道的参数: '.http_build_query($data), 1, 10);

            $result = $this->post ( $data, $return['gateway']);

            addSyslog($logTitle.'通道返回的参数: '.$result, 1, 10);

            $resultData = $this->convertStringToArray($result);

            if ($resultData['rtnCode'] != '0000') {
                $this->showmessage('通道支付失败: rtnCode='.$resultData['rtnCode'].', rtnMsg='.$resultData['rtnMsg']);
            }

            $flag = $this->channelVerify($resultData, './cert/HeFuBao/'.$return['mch_id'].'.cer');

            if($flag){
                header('Location:' . $resultData['qrCodeURL']);
            }else{
                $this->showmessage('通道验签失败');
            }
        }
    }

    // 通道页面通知返回
    public function callbackurl()
    {
        $data = $_POST;
        $pay_orderid = $data['tranFlow']; //系统订单号

        $logTitle = time() . ': ' . $this->TITLE . '-通道页面通知返回-';
        addSyslog($logTitle.'系统订单号pay_orderid='.$pay_orderid, 1, 10);

        $Order = M("Order");
        $pay_status = $Order->where("pay_orderid = '".$pay_orderid."'")->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($pay_orderid, '', 1);
            addSyslog($logTitle.'修改订单状态成功, 系统订单号pay_orderid='.$pay_orderid, 1, 10);
        }else{
            addSyslog($logTitle.'未修改订单状态, 系统订单号pay_orderid='.$pay_orderid, 3, 10);
            exit("error");
        }
    }

    /**
     * 通道服务器通知
     *  响应报文字段:
     *      YUL1=http%3A%2F%2Fhuapay.cc%2FPay_HeFuBaoWxH5_callbackurl.html%3Forderid%3D20180210122023555649
     *      &YUL2=null&YUL3=null&channelNo=05&ext1=null
     *      &ext2=.%2Fcert%2FHeFuBao%2FS20170907011890.cer
     *      &merchantNo=S20170907011890
     *      &qrCodeURL=http%3A%2F%2Fpaygate.hefupal.cn%2Fpaygate%2Fredirect%2FODAyMTA4NzM3MTA1MzcxNDcxODcy&rtnCode=0000&rtnMsg=null&sign=eLX6C9F%2B7ExZxj3LvY6f%2F2F3qEp8x8jHJrqMmo%2F6McxWK9HsuUN14JZVXNsYOlAy51RZ3CmuCSwXLlpb5sRnuGlwKR%2F5LOfI1hFaeOCIGqPtx9mjwf02mWLycQIk6UwTATO6%2FkPpxJ9e5m6FRKzdxZfLNd1UF%2F%2FhgZMzoa%2Fs%2FOk%3D
     *      &tranCode=YS1003&tranFlow=20180210122023555649&version=v1
     */
    public function notifyurl()
    {
        $data = $_POST;

        $logTitle = time() . '-' . $this->TITLE . '-通道服务器通知-';
        addSyslog($logTitle.'通道返回数据: '.http_build_query($data), 1, 10);

        //验签
        $cert_path = $data['ext2'];
        $flag = $this->channelVerify($data, $cert_path);
        if(!$flag){
            addSyslog($logTitle.'通道验签失败@todo', 3, 10);
            //exit('NNNNNN');
        }

        $pay_orderid = $data['tranFlow']; //系统订单号
        addSyslog($logTitle.'系统订单号='.$pay_orderid, 1, 10);

        if ($data['rtnCode'] == '0000') {
            $this->EditMoney($pay_orderid, $this->CODE, 0);
            addSyslog('确认成功支付, 修改订单状态成功, 系统订单号pay_orderid='.$pay_orderid, 1, 11);
            exit('YYYYYY');
        } else {
            addSyslog('未确认成功支付, 通道返回错误='.$data['rtnMsg'].', 系统订单号pay_orderid='.$pay_orderid, 3, 11);
            exit('NNNNNN');
        }
    }

    function sign($params, $priv_cert_path, $password) {
        if(isset($params['sign'])){
            unset($params['sign']);
        }
        // 转换成key=val&串
        $params_str = $this->createLinkString ( $params, false );

        $private_key = $this->getPrivateKey ( $priv_cert_path, $password );
        // 签名
        $sign_falg = openssl_sign ( $params_str, $sign, $private_key, OPENSSL_ALGO_SHA1 );
        if ($sign_falg) {
            $sign_base64 = base64_encode ( $sign );
            return $sign_base64;
        } else {
            return '';
        }
    }

    /**
     * 验签
     *
     * @param String $params_str
     * @param String $sign_str
     */
    function channelVerify($params, $cert_path) {
        // 公钥
        $public_key = $this->getPublicKey ( $cert_path );
        // 签名串
        $sign_str = $params ['sign'];
        $sign_str=str_replace(" ","+",$sign_str);
        // 转码

        unset ( $params ['sign'] );
        $params_str = $this->createLinkString ( $params, false );
        $sign = base64_decode ( $sign_str );
        $isSuccess = openssl_verify ( $params_str, $sign, $public_key);
        if($isSuccess=='1'){
            return $params_str;
        }else{
            $params_str=$params_str.'&sign='.$sign_str.'&msg=验签失败';
            return false;
        }
    }


    /**
     * 取证书公钥 -验签
     *
     * @return string
     */
    function getPublicKey($cert_path) {
        return file_get_contents ( $cert_path );
    }
    /**
     * 返回(签名)证书私钥 -
     *
     * @return unknown
     */
    function getPrivateKey($cert_path, $password) {
        $pkcs12 = file_get_contents ( $cert_path );
        openssl_pkcs12_read ( $pkcs12, $certs, $password );
        return $certs ['pkey'];
    }

    /**
     * 加密数据
     * @param string $data数据
     * @param string $cert_path 证书配置路径
     * @return unknown
     */
    function encryptData($data, $cert_path) {
        $public_key = $this->getPublicKey ( $cert_path );
        openssl_public_encrypt ( $data, $crypted, $public_key);
        return base64_encode ( $crypted );
    }


    /**
     * 解密数据
     * @param string $data数据
     * @param string $cert_path 证书配置路径
     * @return unknown
     */
    function decryptData($data, $cert_path, $password) {
        $data = base64_decode ( $data );
        $private_key = $this->getPrivateKey ( $cert_path, $password );
        openssl_private_decrypt ( $data, $crypted, $private_key );
        return $crypted;
    }

    /**
     * 字符串转换为 数组
     *
     * @param unknown_type $str
     * @return multitype:unknown
     */
    function convertStringToArray($str) {
        $result = array ();

        if (! empty ( $str )) {
            $temp = preg_split ( '/&/', $str );
            if (! empty ( $temp )) {
                foreach ( $temp as $key => $val ) {
                    $arr = preg_split ( '/=/', $val, 2 );
                    if (! empty ( $arr )) {
                        $k = $arr ['0'];
                        $v = $arr ['1'];
                        $result [$k] = $v;
                    }
                }
            }
        }
        return $result;
    }


    /**
     * 构造自动提交表单
     *
     * @param unknown_type $params
     * @param unknown_type $action
     * @return string
     */
    function create_html($params, $action) {
        $encodeType = isset ( $params ['encoding'] ) ? $params ['encoding'] : 'UTF-8';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$action}" method="post">

eot;
        foreach ( $params as $key => $value ) {
            $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        }
        $html .= <<<eot
   <!-- <input type="submit" type="hidden">-->
    </form>
</body>
</html>
eot;
        return $html;
    }


    /**
     * 讲数组转换为string
     *
     * @param $para 数组
     * @param $encode 是否需要URL编码
     * @return string
     */
    function createLinkString($para, $encode) {
        ksort($para);   //排序
        $linkString = "";
        while ( list ( $key, $value ) = each ( $para ) ) {
            if ($encode) {
                $value = urlencode ( $value );
            }
            $linkString .= $key . "=" . $value . "&";
        }
        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );

        return $linkString;
    }


    /**
     * 后台交易 HttpClient通信
     *
     * @param unknown_type $params
     * @param unknown_type $url
     * @return mixed
     */
//    function post($params, $url) {
//        $opts = $this->createLinkString ( $params, false, true );
//
//        $logTitle = time() . ': ' . $this->TITLE . '-curl提交-';
//        addSyslog($logTitle.'提交给通道的原文: '.$opts);
//
//        $ch = curl_init ();
//        curl_setopt ( $ch, CURLOPT_URL, $url );
//        curl_setopt ( $ch, CURLOPT_POST, 1 );
//        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false ); // 不验证证书
//        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false ); // 不验证HOST
//        curl_setopt ( $ch, CURLOPT_SSLVERSION, 3 );
//        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
//            'Content-type:application/x-www-form-urlencoded;charset=UTF-8'
//        ) );
//        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $opts );
//        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
//        $html = curl_exec ( $ch );
//        if(curl_errno($ch)){
//            $errmsg = curl_error($ch);
//            curl_close ( $ch );
//            addSyslog($logTitle.'curl返回错误1: '.$errmsg);
//            return false;
//        }
//        if( curl_getinfo($ch, CURLINFO_HTTP_CODE) != "200"){
//            $errmsg = "http状态=" . curl_getinfo($ch, CURLINFO_HTTP_CODE);
//            addSyslog($logTitle.'curl返回错误2: '.$errmsg);
//            curl_close ( $ch );
//            return false;
//        }
//        curl_close ( $ch );
//        return $html;
//    }

    function post($params, $url) {
        $opts = $this->createLinkString ( $params, false, true );

        $logTitle = time() . ': ' . $this->TITLE . '-curl提交-';
        addSyslog($logTitle.'提交给通道的原文: '.$opts);



        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $opts); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        curl_setopt ( $curl, CURLOPT_HTTPHEADER, array (
            'Content-type:application/x-www-form-urlencoded;charset=UTF-8'
        ) );
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            $errmsg = curl_error($curl);//捕抓异常
            addSyslog($logTitle.'curl返回错误1: '.$errmsg);
            curl_close($curl); // 关闭CURL会话
            return false;
        }
        if( curl_getinfo($curl, CURLINFO_HTTP_CODE) != "200"){
            $errmsg = "http状态=" . curl_getinfo($curl, CURLINFO_HTTP_CODE);
            addSyslog($logTitle.'curl返回错误2: '.$errmsg);
            curl_close ( $curl );
            return false;
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }

    /**
     * 打印请求应答
     *
     * @param
     *        	$url
     * @param
     *        	$req
     * @param
     *        	$resp
     */
    function printResult($url, $req, $resp) {
        echo "=============<br>\n";
        echo "地址：" . $url . "<br>\n";
        echo "请求：" . str_replace ( "\n", "\n<br>", htmlentities ( $this->createLinkString ( $req, false, true ) ) ) . "<br>\n";
        echo "应答：" . str_replace ( "\n", "\n<br>", htmlentities ( $resp ) ) . "<br>\n";
        echo "=============<br>\n";
    }
}
?>