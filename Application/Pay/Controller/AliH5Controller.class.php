<?php
namespace Pay\Controller;

use Common\Model\GPayLogModel;

/**
 * 支付宝H5, 对接支付宝官方新接口
 * @todo
 * Class AliH5Controller
 * @package Pay\Controller
 * @author 黄治华
 */
class AliH5Controller extends PayController
{
    private $CODE = 'AliH5';
    private $TITLE = '支付宝H5';
    /**
     *支付宝网关地址（新）
     */
    private $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';
    /**
     * HTTPS形式消息验证地址
     */
    private $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
    /**
     * HTTP形式消息验证地址
     */
    private $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
    private $alipay_config = [
        //合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
        'partner'		=> '',

        //收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
        'seller_id'	=> '',

        // MD5密钥，安全检验码，由数字和字母组成的32位字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
        'key'			=> '',
        // 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=>123这类自定义参数，必须外网可以正常访问
        'notify_url' => '',

        // 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=>123这类自定义参数，必须外网可以正常访问
        'return_url' => '',

        //签名方式
        'sign_type'    => 'MD5',

        //字符编码格式 目前支持utf-8
        'input_charset'=> 'utf-8',

        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        'cacert'    => './cert/alipay/cacert.pem',

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        'transport'    => 'http',

        // 支付类型 ，无需修改
        'payment_type' => "1",

        // 产品类型，无需修改
        'service' => "alipay.wap.create.direct.pay.by.user",
    ];

    public function __construct()
    {
        parent::__construct();
        $this->alipay_config['notify_url'] = $this->_site . 'Pay_'.$this->CODE.'_notifyurl.html';
        $this->alipay_config['return_url'] = $this->_site . 'Pay_'.$this->CODE.'_callbackurl.html';
    }

    //支付
    public function Pay($array)
    {
        header("Content-Type:text/html;charset=UTF-8");
        date_default_timezone_set("Asia/Shanghai");

        $out_trade_id = I('request.pay_orderid'); //商户订单号
        $productCode = I('request.pay_bankcode'); //支付产品编号
        $body = I('request.pay_productname');

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
//        if($return["amount"] < 15){
//            exit('支付金额最小为15元');
//        }
//        if($return["amount"] > 200000){
//            exit('单笔支付限额200000元');
//        }

        $pay_orderid = $return["orderid"]; //系统订单号
        $memberid = $return["memberid"]; //商户号
        $mch_id = $return['mch_id']; //通道商户号
        $amount = $return["amount"];
        $appid = $return['appid']; //通道账号
        $gateway = $return["gateway"] . "?_input_charset=md5";
        $signkey = $return['signkey'];

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service"       => $this->alipay_config['service'],
            "partner"       => $this->alipay_config['partner'],
            "seller_id"  => $this->alipay_config['seller_id'],
            "payment_type"	=> $this->alipay_config['payment_type'],
            "notify_url"	=> $this->alipay_config['notify_url'],
            "return_url"	=> $this->alipay_config['return_url'],
            "_input_charset"	=> trim(strtolower($this->alipay_config['input_charset'])),
            "out_trade_no"	=> $pay_orderid,
            "subject"	=> $body,
            "total_fee"	=> $amount,
            "show_url"	=> $this->alipay_config['return_url'],
            "app_pay"	=> "Y",//启用此参数能唤起钱包APP支付宝
            "body"	=> $body,
            //其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.2Z6TSk&treeId=60&articleId=103693&docType=1
            //如"参数名"	=> "参数值"   注：上一个参数末尾需要“,”逗号。
        );

        //待请求参数数组
        $para = $this->buildRequestPara($parameter);

        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_gateway_new."_input_charset=".trim(strtolower($this->alipay_config['input_charset']))."' method='get'>";
        while (list ($key, $val) = each ($para)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit'  value='确认' style='display:none;'></form>";

        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

        //通道提供收银台
        echo $sHtml;
    }

    /**
     * 通道回调, 再回调商户
     */
    public function callbackurl()
    {
        //生成签名结果
        $isSign = $this->getSignVeryfy($_GET, $_GET["sign"]);
        //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
        $responseTxt = 'false';
        if (! empty($_GET["notify_id"])) {
            $responseTxt = $this->getResponse($_GET["notify_id"]);
        }

        //添加日记
        $payLog = [
            'type' => GPayLogModel::$TYPE_CHANNEL_CALLBACK,
            'productCode' => '904',
        ];
        $payLog['msg'] = $this->TITLE.'-返回数据: '.http_build_query($_GET);
        $this->payLogModel->add($payLog);

        //验证
        //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
        //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
        if (preg_match("/true$/i",$responseTxt) && $isSign) { //签名验证成功
            //商户订单号
            $out_trade_no = $_GET['out_trade_no'];
            //支付宝交易号
            $trade_no = $_GET['trade_no'];
            //交易状态
            $trade_status = $_GET['trade_status'];

            //查找订单
            $orderModel = M("Order");
            $order = $orderModel->where(['pay_orderid' => $out_trade_no])->find();
            if(empty($order)){
                $payLog['msg'] = $this->TITLE.'-平台订单不存在, 通道订单号='.$trade_no.', 返回数据: '.http_build_query($_GET);
                $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
                $this->payLogModel->add($payLog);
                exit('平台订单不存在, 通道订单号='.$trade_no);
            }

            if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序
                //页面回调商户地址，不能作为支付成功的依据
                $this->callbackMerchant(self::CODE_SUCCESS, $order);
            }
            else {
                $this->callbackMerchant(self::CODE_PENDING, $order);
            }
        } else {
            exit('签名验证失败');
        }
    }

    //异步通知
    public function notifyurl()
    {
        //生成签名结果
        $isSign = $this->getSignVeryfy($_POST, $_POST["sign"]);
        //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
        $responseTxt = 'false';
        if (! empty($_POST["notify_id"])) {
            $responseTxt = $this->getResponse($_POST["notify_id"]);
        }

        //商户订单号
        $orderId = $_POST['out_trade_no'];
        //支付宝交易号
        $trade_no = $_POST['trade_no'];
        //交易状态
        $trade_status = $_POST['trade_status'];
        $total_fee = $_POST['total_fee'];
        $seller_id = $_POST['seller_id'];

        //添加日记
        $payLog = [
            'productCode' => '904',
            'orderId' => $orderId,
            'channelMerchantId' => $seller_id,
            'type' => GPayLogModel::$TYPE_CHANNEL_NOTIFY,
        ];
        $payLog['msg'] = $this->TITLE.'-返回的数据: '.http_build_query($_POST);
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

        //填充日记
        $payLog['merchantId'] = $order['pay_memberid'];
        $payLog['productCode'] = $order['pay_bankcode'];
        $payLog['outTradeId'] = $order['out_trade_id'];

        //验证是否合法订单
        if (format2Decimal($order["pay_amount"]) != format2Decimal($total_fee)) {
            $payLog['msg'] = $this->TITLE.'-订单金额不相等，金额单位为元，通道传的金额='.$total_fee.', 平台的金额='.$order["pay_amount"];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }
        if ($seller_id != $this->alipay_config['seller_id']) {
            $payLog['msg'] = $this->TITLE.'-通道商户号不相等，通道传的通道商户号='.$seller_id.', 平台的通道商户号='.$this->alipay_config['seller_id'];
            $payLog['level'] = GPayLogModel::$LEVEL_ERROR;
            $this->payLogModel->add($payLog);
            exit('fail');
        }

        //验证
        //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
        //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
        if (preg_match("/true$/i",$responseTxt) && $isSign) { //签名验证成功
            if($_POST['trade_status'] == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            }
            else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //付款完成后，支付宝系统发送该交易状态通知

                $this->EditMoney($orderId, $this->CODE, 0);
            }

            echo "success";		//请不要修改或删除
        } else { //签名验证失败
            echo "fail";
        }
    }

    /**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
    function buildRequestPara($para_temp) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //生成签名结果
        $mysign = $this->buildRequestMysign($para_sort);

        //签名结果与签名方式加入请求提交参数组中
        $para_sort['sign'] = $mysign;
        $para_sort['sign_type'] = strtoupper(trim($this->alipay_config['sign_type']));

        return $para_sort;
    }

    function paraFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == "sign" || $key == "sign_type" || $val == "")continue;
            else	$para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 生成签名结果
     * @param $para_sort 已排序要签名的数组
     * return 签名结果字符串
     */
    function buildRequestMysign($para_sort) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        $mysign = "";
        switch (strtoupper(trim($this->alipay_config['sign_type']))) {
            case "MD5" :
                $mysign = md5Sign($prestr, $this->alipay_config['key']);
                break;
            default :
                $mysign = "";
        }

        return $mysign;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    function createLinkstring($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }

    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
    function getSignVeryfy($para_temp, $sign) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        $isSgin = false;
        switch (strtoupper(trim($this->alipay_config['sign_type']))) {
            case "MD5" :
                $isSgin = $this->md5Verify($prestr, $sign, $this->alipay_config['key']);
                break;
            default :
                $isSgin = false;
        }

        return $isSgin;
    }

    /**
     * 签名字符串
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * return 签名结果
     */
    function md5Sign($prestr, $key) {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    /**
     * 验证签名
     * @param $prestr 需要签名的字符串
     * @param $sign 签名结果
     * @param $key 私钥
     * return 签名结果
     */
    function md5Verify($prestr, $sign, $key) {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);

        if($mysgin == $sign) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    function getResponse($notify_id) {
        $transport = strtolower(trim($this->alipay_config['transport']));
        $partner = trim($this->alipay_config['partner']);
        $veryfy_url = '';
        if($transport == 'https') {
            $veryfy_url = $this->https_verify_url;
        }
        else {
            $veryfy_url = $this->http_verify_url;
        }
        $veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
        $responseTxt = $this->getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);

        return $responseTxt;
    }

    /**
     * 远程获取数据，GET模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径
     * return 远程输出的数据
     */
    function getHttpResponseGET($url,$cacert_url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
        curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
        $responseText = curl_exec($curl);
        //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }

    /**
     * 远程获取数据，POST模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径
     * @param $para 请求的数据
     * @param $input_charset 编码格式。默认值：空值
     * return 远程输出的数据
     */
    function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '') {

        if (trim($input_charset) != '') {
            $url = $url."_input_charset=".$input_charset;
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
        curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
        curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl,CURLOPT_POST,true); // post传输数据
        curl_setopt($curl,CURLOPT_POSTFIELDS,$para);// post传输数据
        $responseText = curl_exec($curl);
        //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }
}