<?php
namespace Pay\Controller;

/**
 * 途贝-QQH5支付
 * Class TuBeiQQH5Controller
 * @package Pay\Controller
 */
class TuBeiQQH5Controller extends PayController
{
    private $CODE = 'TuBeiQQH5';
    private $TITLE = '途贝-QQH5';
    private $TRADE_TYPE = 'trade.qqpay.h5pay';

    public function Pay($array)
    {
        $logTitle = time() . ': ' . $this->TITLE;
        addSyslog($logTitle.'-商户提交的参数: '.json_encode($array), 1, 10);

        $out_trade_id = I('request.pay_orderid'); //实际是out_trade_id
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
        $pay_orderid = $return["orderid"]; //系统订单号

        if ($return) {
        	if($return["amount"] > 5000){
        		exit('单笔支付限额5000元');
        	}

            //提交到通道接口的参数
            $notifyurl = $this->_site . 'Pay_' . $this->CODE . '_notifyurl.html'; //异步通知
            $callbackurl = $this->_site . 'Pay_' . $this->CODE . '_callbackurl.html?orderid=' . $pay_orderid; //页面跳转通知
            $data = array(
                'body'              => $body, // 订单描述，可以对交易或商品进行一个详细地描述，比如填写"购买商品2件共15.00元"
                'mch_id'            => $return['mch_id'], // 商户号
                'nonce_str'         => randpw(32, 'NUMBER'), //随机字符串，不长于 32 位
                'notify_url'        => $notifyurl, //接收支付结果异步通知回调地址，PC 网站必填
                'return_url'        => $callbackurl,
                'out_trade_no'      => $pay_orderid, // 对于通道为商户订单号, 对于系统为系统订单号
                'spbill_create_ip'  => get_client_ip(), //调用微信支付 API 的机器 IP
                'total_fee'         => 100 * $return["amount"], // 订单总金额，整形，此处单位为分
                'trade_type'        => $this->TRADE_TYPE, //交易类型   trade.weixin.native 为微信H5支付，更多支付类型请查看技术文档编写
            );
            //签名
            $data['sign'] = $this->_createSign($data, $return['signkey']);

            $xmlstr = arrayToXml($data);

            addSyslog($logTitle.'-提交给通道的参数: '.json_encode($xmlstr), 1, 10);

            list($return_code, $return_content) = $this->_httpPostData($return['gateway'], $xmlstr);
            $result = xmlToArray($return_content);

            if ($result && $result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                $sign_array = $result;
                unset($sign_array['sign']);
                $respSign = $this->_createSign($sign_array, $return['signkey']);
                if($respSign !=  $result['sign']){
                    addSyslog($logTitle.'-验签失败: '.json_encode($result), 3, 10);
                    $this->showmessage('验签失败！');
                }else{
                    //IOS 移动应用: app_name=王者荣耀&bundle_id=com.tencent.wzryIOS&周年纪念版
                    //安卓移动应用: app_name=xxxxx&package_name=xxxx&商户自定义参数
                    //WAP 网站应用: wap_url=xxxxx&wap_name=xxxx&商户自定义参数
                    $prepay_url = $result['prepay_url'] . '&wap_url=http://zhifujia.cc&wap_name=any&商户自定义参数';
                    addSyslog($logTitle.'-成功创建订单并且跳转到支付页面: '.$prepay_url, 1, 10);
                    redirect($prepay_url);
                }
            }else{
                $this->showmessage($result['return_msg']);
            }
        }
    }

    // 通道页面通知返回
    public function callbackurl()
    {
        $pay_orderid = $_REQUEST["orderid"]; //系统订单号

        $logTitle = time() . ': ' . $this->TITLE . '-通道页面通知返回: ';
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

    // 通道服务器通知
    public function notifyurl()
    {
        $data = $GLOBALS['HTTP_RAW_POST_DATA'];

        $logTitle = time() . ': ' . $this->TITLE . '-通道服务器通知: ';
        addSyslog($logTitle.'返回数据: '.$data, 1, 10);

        $data = xmlToArray($data);
        $pay_orderid = $data['out_trade_no']; //系统订单号

        $sign = $data['sign'];
        unset($data['sign']);

        $Order = M("Order");
        $signkey = $Order->where("pay_orderid = '".$pay_orderid."'")->getField("key");
        $respSign = strtoupper($this->_createSign($data, $signkey));

        if($respSign != $sign){
            addSyslog($logTitle.'验签失败', 3, 10);
            exit('fail');
        }

        if ($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
            $this->EditMoney($pay_orderid, $this->CODE, 0);
            addSyslog('确认成功支付, 修改订单状态成功, 系统订单号pay_orderid='.$pay_orderid, 1, 11);
            exit('success');
        } else {
            addSyslog('未确认成功支付, 未修改订单状态, 系统订单号pay_orderid='.$pay_orderid, 3, 11);
            exit('fail');
        }
    }

    /**
     * 生成签名
     * @param $data
     * @param $key
     * @return string
     */
    protected function _createSign($data, $key){
        $sign = '';
        ksort($data);
        foreach( $data as $k => $vo ){
            $sign .= $k . '=' . $vo . '&';
        }

        return  strtoupper( md5($sign  . 'key=' . $key) );
    }

    public function _httpPostData($url, $data_string)
    {
        $cacert = ''; //CA根证书  (目前暂不提供)
        $CA = false ;   //HTTPS时是否进行严格认证
        $TIMEOUT = 30;  //超时时间(秒)
        $SSL = substr($url, 0, 8) == "https://" ? true : false;

        $ch = curl_init ();
        if ($SSL && $CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);   //  只信任CA颁布的证书
            curl_setopt($ch, CURLOPT_CAINFO, $cacert);      //  CA根证书（用来验证的网站证书是否是CA颁布）
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    //  检查证书中是否设置域名，并且是否与提供的主机名匹配
        } else if ($SSL && !$CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //  信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);    //  检查证书中是否设置域名
        }


        curl_setopt ( $ch, CURLOPT_TIMEOUT, $TIMEOUT);
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $TIMEOUT-2);
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml') );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();

        $return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );

        curl_close($ch);
        return array (
            $return_code,
            $return_content
        );
    }
}
?>