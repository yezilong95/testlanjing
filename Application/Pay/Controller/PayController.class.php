<?php

namespace Pay\Controller;

use Common\Model\OrderModel;
use Think\Controller;
use Common\Model\GPayLogModel;

class PayController extends Controller
{
    //商家信息
    public $merchants;
    //网站地址
    public $_site;
    //通道信息
    public $channel;
    //支付日记模型
    protected $payLogModel; //支付日记模型

    //返回的状态码
    const CODE_SUCCESS  = '00'; //成功
    const CODE_PENDING  = '10'; //处理中
    const CODE_FAIL     = '99'; //失败

    public function __construct()
    {
        parent::__construct();
        $this->_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
        $this->payLogModel = DM('GPayLog'); //支付日记模型
    }

    /**
     * 创建订单
     * @param $parameter
     * @return array
     */
    public function orderadd($parameter)
    {

        //通道信息
        $this->channel = $parameter['channel'];
        //$this->merchants = $this->channel['userid'];
        //用户信息
        $usermodel       = D('Member');
        $this->merchants = $usermodel->get_Userinfo($this->channel['userid']);
        // 通道名称
        $PayName = $parameter["code"];
        // 交易金额比例
        $moneyratio = $parameter["exchange"];
        //商户编号
        $userid = $this->merchants['id'] + 10000;
        //费率
        $_userrate = M('Userrate')
            ->where(["userid" => $this->channel['userid'], "payapiid" => $this->channel['pid']])
            ->find();
        //银行通道费率
        $syschannel = M('Channel')
            ->where(['id' => $this->channel['api']])
            ->find();

        $channel_accounts = M('channel_account')->where(['channel_id' => $syschannel['id'], 'status' => '1'])->select();

        if (empty($channel_accounts)) {
            $this->showmessage('未添加通道子账户');
        }

        // 计算权重
        if (count($channel_accounts) == 1) {
            $channel_account = current($channel_accounts);
        } else {
            $channel_account = getWeight($channel_accounts);
        }

        $syschannel['mch_id']    = $channel_account['mch_id'];
        $syschannel['signkey']   = $channel_account['signkey'];
        $syschannel['appid']     = $channel_account['appid'];
        $syschannel['appsecret'] = $channel_account['appsecret'];
        $syschannel['account']   = $channel_account['title'];

        // 定制费率
        if ($channel_account['custom_rate']) {
            $syschannel['defaultrate'] = $channel_account['defaultrate'];
            $syschannel['fengding']    = $channel_account['fengding'];
            $syschannel['fengding']    = $channel_account['fengding'];
            $syschannel['rate']        = $channel_account['rate'];
        }

        //平台通道
        $platform = M('Product')
            ->where(['id' => $this->channel['pid']])
            ->find();

        //回调参数
        $return = [
            "memberid"     => $userid,
            "mch_id"       => $syschannel["mch_id"], //商户号
            "signkey"      => $syschannel["signkey"], // 签名密钥
            "appid"        => $syschannel["appid"], // APPID
            "appsecret"    => $syschannel["appsecret"], // APPSECRET
            "gateway"      => $syschannel["gateway"] ? $syschannel["gateway"] : $parameter["gateway"], // 网关
            "notifyurl"    => $syschannel["serverreturn"] ? $syschannel["serverreturn"] : $this->_site . "Pay_" .
            $PayName . "_notifyurl.html",
            "callbackurl"  => $syschannel["pagereturn"] ? $syschannel["pagereturn"] : $this->_site . "Pay_" .
            $PayName . "_callbackurl.html",
            'unlockdomain' => $syschannel['unlockdomain'] ? $syschannel['unlockdomain'] : '', //防封域名
        ];

        //用户优先通道
        $feilv    = $_userrate['feilv'] ? $_userrate['feilv'] : $syschannel['defaultrate']; // 交易费率
        $fengding = $_userrate['fengding'] ? $_userrate['fengding'] : $syschannel['fengding']; // 封顶手续费
        $fengding = $fengding == 0 ? 9999999 : $fengding; //如果没有设置封顶手续费自动设置为一个足够大的数字

        //金额格式化
        $pay_amount = I("request.pay_amount", 0);
        if (!$pay_amount or !is_numeric($pay_amount) or $pay_amount <= 0) {
            $this->showmessage('金额错误');
        }
        $return["amount"] = floatval($pay_amount) * $moneyratio; // 交易金额
        $pay_sxfamount    = (($pay_amount * $feilv) > ($pay_amount * $fengding)) ? ($pay_amount * $fengding) :
        ($pay_amount * $feilv); // 手续费
        $pay_shijiamount = $pay_amount - $pay_sxfamount; // 实际到账金额
        $cost            = bcmul($syschannel['rate'], $pay_amount, 2); //计算成本

        //商户订单号
        $out_trade_id = $parameter['out_trade_id'];
        //生成系统订单号
        $pay_orderid = $parameter['orderid'] ? $parameter['orderid'] : get_requestord();

        //$pay_maori = '0.00';
        $pay_maori = $pay_sxfamount-$cost;
        //验签
        if ($this->verify()) {
            $Order                       = M("Order");
            $return["bankcode"]          = $this->channel['pid'];
            $return['code']              = $platform['code']; //银行英文代码
            $return["orderid"]           = $pay_orderid; // 系统订单号
            $return["out_trade_id"]      = $out_trade_id; // 外部订单号
            $return["subject"]           = $parameter['body']; // 商品标题
            $data["pay_memberid"]        = $userid;
            $data["pay_orderid"]         = $return["orderid"];
            $data["pay_amount"]          = $pay_amount; // 交易金额
            $data["pay_poundage"]        = $pay_sxfamount; // 手续费
            $data["pay_maori"]           = $pay_maori; // 毛利
            $data["pay_actualamount"]    = $pay_shijiamount; // 到账金额
            $data["pay_applydate"]       = time();
            $data["pay_bankcode"]        = $this->channel['pid'];
            $data["pay_bankname"]        = $platform['name'];
            $data["pay_notifyurl"]       = I("request.pay_notifyurl");
            $data["pay_callbackurl"]     = I("request.pay_callbackurl");
            $data["pay_status"]          = 0;
            $data["pay_tongdao"]         = $syschannel['code'];
            $data["pay_zh_tongdao"]      = $syschannel['title'];
            $data["pay_channel_account"] = $syschannel['account'];
            $data["pay_ytongdao"]        = $parameter["code"];
            $data["pay_yzh_tongdao"]     = $parameter["title"];
            $data["pay_tjurl"]           = $_SERVER["HTTP_REFERER"];
            $data["pay_productname"]     = I("request.pay_productname");
            $data["pay_productnum"]      = I("request.pay_productnum");
            $data["pay_productdesc"]     = I("request.pay_productdesc");
            $data["pay_producturl"]      = I("request.pay_producturl");
            $data["attach"]              = I("request.pay_attach");
            $data["out_trade_id"]        = $out_trade_id;
            $data["ddlx"]                = I("post.ddlx", 0);
            $data["memberid"]            = $return["mch_id"];
            $data["key"]                 = $return["signkey"];
            $data["account"]             = $return["appid"];
            $data["cost"]                = $cost;
            $data["cost_rate"]           = $syschannel['rate'];

            //确保商户订单号唯一: ALTER TABLE `pay`.`pay_order` ADD UNIQUE `UNI_user_out_trade_id` USING BTREE (`pay_memberid`, `out_trade_id`) comment '';
            //查看订单是否重复: select id,pay_orderid,pay_memberid,out_trade_id,if(pay_successdate>0,FROM_UNIXTIME(pay_successdate), pay_successdate) pay_successdate,pay_status from pay_order where out_trade_id='E20180121132609982694'

            //删除无用订单: select count(id) `count`, max(id) id, pay_memberid,out_trade_id,if(pay_successdate>0,FROM_UNIXTIME(pay_successdate), pay_successdate) pay_successdate,pay_status, from_unixtime(pay_applydate) pay_applydate from pay_order where pay_status=0 group by out_trade_id HAVING `count` > 1 order by id desc,pay_successdate desc

            //唯一组合索引, 由于历史原因不能在这里抛出异常给controller捕获,因为文件太多了
            //抛出异常: Duplicate entry '10002-123456' for key 'UNI_user_out_trade_id' [ SQL语句 ] : INSERT INTO `pay_order` (`pay_memberid`,`pay_orderid`,`pay_amount`,`pay_poundage`,`pay_actualamount`,`pay_applydate`,`pay_bankcode`,`pay_bankname`,`pay_notifyurl`,`pay_callbackurl`,`pay_status`,`pay_tongdao`,`pay_zh_tongdao`,`pay_channel_account`,`pay_ytongdao`,`pay_yzh_tongdao`,`pay_tjurl`,`pay_productname`,`pay_productnum`,`pay_productdesc`,`pay_producturl`,`attach`,`out_trade_id`,`ddlx`,`memberid`,`key`,`account`,`cost`,`cost_rate`) VALUES
            //$data["out_trade_id"] = '123456';
            $result = false;
            try{
                $result = $Order->add($data);
            }catch(\Exception $e){
                //添加日记
                $payLog = [
                    'merchantId' => $data["pay_memberid"],
                    'productCode' => $data["pay_bankcode"],
                    'outTradeId' => $data["out_trade_id"],
                    'channelMerchantId' => $data["memberid"],
                    'orderId' => $data["pay_orderid"],
                    'type' => GPayLogModel::$TYPE_REQUEST_CHANNEL,
                    'level' => GPayLogModel::$LEVEL_ERROR,
                    'msg' => '请不要提交重复订单',
                ];
                $this->payLogModel->add($payLog);

                $this->showmessage('请不要提交重复订单, 商户订单号='.$data["out_trade_id"]);
            }

            //添加订单
            if ($result) {
                $return['datetime'] = date('Y-m-d H:i:s', $data['pay_applydate']);
                $return["status"]   = "success";
                return $return;
            } else {
                $this->showmessage('创建订单失败');
            }
        } else {
            $this->showmessage('签名验证失败', $_POST);
        }
    }

    /**
     * 上游通知平台-平台通知商户（或者查询上游订单成功后修改），平台页面回调商户
     * @param $TransID
     * @param string $PayName 目前没用
     * @param int $returntype 0上游通知平台,修改订单状态为成功未返回，1平台页面回调商户,只是回调没有修改订单
     * @param string $channelOrderId 通道订单id
     */
    public function EditMoney($TransID, $PayName='', $returntype = 1, $channelOrderId = null)
    {
        $fp = fopen(__FILE__, "r");
        if( flock($fp, LOCK_EX) ) {
            $Order = DM("Order");
            $list = $Order->where(['pay_orderid' => $TransID])->find();
            $userid = intval($list["pay_memberid"] - 10000); // 商户ID
            $oriStatus = $list["pay_status"];

            $merchantId = $list['pay_memberid'];
            $productCode = $list['pay_bankcode'];
            $channelMerchantId = $list['memberid'];
            $outTradeId = $list['out_trade_id'];
            $orderId = $list['pay_orderid'];
            $payLog = [
                'merchantId' => $merchantId,
                'productCode' => $productCode,
                'outTradeId' => $outTradeId,
                'channelMerchantId' => $channelMerchantId,
                'orderId' => $orderId,
                'type' => null,
                'level' => GPayLogModel::$LEVEL_INFO,
            ];

            if ($returntype == 0 && $oriStatus == 0) { //订单状态从0变为1，修改apimoney表总额，添加moneychang表资金变动记录
                //更新订单状态 1 已成功未返回
                $data = ['pay_status' => 1, 'pay_successdate' => time()];
                if (!empty($channelOrderId)) {
                    $data['channel_order_id'] = $channelOrderId;
                }
                $Order->where(['pay_orderid' => $TransID])->save($data);
                //添加日记
                $payLog['type'] = GPayLogModel::$TYPE_CHANNEL_NOTIFY;
                $payLog['msg'] = '修改订单状态为：成功，未返回';
                $this->payLogModel->add($payLog);

                //支付通道
                $syschannel = DM('Channel')->where(['code' => trim($list['pay_ytongdao'])])->find();
                //通道金额统计
                if ($syschannel) {
                    $Apimoney = DM("Apimoney");
                    $_apimoney = $Apimoney->where("userid=" . $userid . " and payapiid=" . $syschannel['id'])->find();
                    if (!$_apimoney) {
                        $data = array();
                        $data["userid"] = $userid;
                        $data["payapiid"] = $syschannel['id'];
                        $Apimoney->add($data);
                        $ymoney = 0;
                    } else {
                        $ymoney = $_apimoney['money'];
                    }
                    // 通道账户金额
                    $moneymoney = floatval($ymoney) + floatval($list["pay_actualamount"]);
                    $Apimoney->where("userid=" . $userid . " and payapiid=" . $syschannel['id'])->setField("money", $moneymoney);
                }

                //商户余额、冻结余额
                $tikuanconfig = DM('Tikuanconfig')->where(['userid' => $userid])->find();
                if (!$tikuanconfig || $tikuanconfig['tkzt'] != 1) {
                    $tikuanconfig = DM('Tikuanconfig')->where(['issystem' => 1])->find();
                }
                //T+1结算
                if ($tikuanconfig['t1zt'] == 1) {
                    DM('Member')->where(['id' => $userid])->save(['blockedbalance' => array('exp', "blockedbalance+{$list['pay_actualamount']}")]);
                    $rows = [
                        'userid' => $userid,
                        'orderid' => $list['pay_orderid'],
                        'amount' => $list['pay_actualamount'],
                        'thawtime' => (strtotime('tomorrow') + rand(0, 7200)),
                        'pid' => $list['pay_bankcode'],
                        'createtime' => time(),
                        'status' => 0,
                    ];
                    DM('Blockedlog')->add($rows);

                } else {
                    //T+0结算
                    DM('Member')->where(['id' => $userid])->save(['balance' => array('exp', "balance+{$list['pay_actualamount']}")]);
                }
                // 商户充值金额变动
                $arrayField = array(
                    "userid" => $userid,
                    "money" => $list["pay_actualamount"],
                    "datetime" => date("Y-m-d H:i:s"),
                    "tongdao" => $list['pay_bankcode'],
                    "transid" => $TransID,
                    "orderid" => $list["out_trade_id"],
                    'contentstr' => $list['out_trade_id'] . '订单充值',
                    "lx" => 1,
                );
                $this->MoenyChange($arrayField); // 资金变动记录
                // 通道ID
                $arrayStr = array(
                    "userid" => $userid, // 用户ID
                    "transid" => $TransID, // 订单号
                    "money" => $list["pay_amount"], // 金额
                    "tongdao" => $list['pay_bankcode'],
                );
                $this->bianliticheng($arrayStr); // 提成处理

                flock($fp, LOCK_UN);
                fclose($fp);

                //添加日记
                $payLog['msg'] = '金额变动处理完成';
                $this->payLogModel->add($payLog);
            }
        }else{
            fclose($fp);
            $payLogErr = [
                'orderId' => $TransID,
                'type' => '加锁失败',
                'level' => GPayLogModel::$LEVEL_ERROR,
                'msg' => '加锁失败, file：'.__FILE__.', method: '.__METHOD__.', line: '.__LINE__
            ];
            $this->payLogModel->add($payLogErr);
            return false;
        }

        $Md5key      = DM('Member')->where(["id" => $userid])->getField("apikey");
        $returnArray = array( // 返回字段
            "memberid"       => $list["pay_memberid"], // 商户ID
            "orderid"        => $list['out_trade_id'], // 商户订单号
            'transaction_id' => $list["pay_orderid"], // 系统订单号, 支付流水号
            "amount"         => $list["pay_amount"], // 交易金额
            "datetime"       => date("YmdHis"), // 交易时间
            "returncode"     => "00", // 交易状态
        );
        $sign                  = $this->createSign($Md5key, $returnArray);
        $returnArray["sign"]   = $sign;
        $returnArray["attach"] = $list["attach"]; //附加信息

        if ($returntype == 1) {
            //添加日记
            $payLog['type'] = GPayLogModel::$TYPE_CALLBACK_MERCHANT;
            $payLog['msg'] = '页面回调商户地址='.$list["pay_callbackurl"].', 表单数据='.http_build_query($returnArray);
            $this->payLogModel->add($payLog);

            $this->setHtml($list["pay_callbackurl"], $returnArray);
            return true;
        } elseif ($returntype == 0 && $oriStatus == 0) { //订单状态从1变为2
            $notifystr = "";
            foreach ($returnArray as $key => $val) {
                $notifystr = $notifystr . $key . "=" . $val . "&";
            }
            $notifystr = substr($notifystr, 0, -1);
            $ch        = curl_init();
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_URL, $list["pay_notifyurl"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $notifystr);
            $contents = curl_exec($ch);
            curl_close($ch);

            //添加日记
            $payLog['type'] = GPayLogModel::$TYPE_NOTIFY_MERCHANT;
            $payLog['msg'] = "通知商户地址=".$list["pay_notifyurl"] . ", 数据=" . $notifystr . ", 商户服务器返回数据: " . $contents;
            if (empty($contents)){
                $payLog['level'] = GPayLogModel::$LEVEL_WARNING;
            }
            $this->payLogModel->add($payLog);

            if (strstr(strtolower($contents), "ok") != false) {
                //更新交易状态
                $Order->where(['id' => $list['id'], 'pay_orderid' => $list["pay_orderid"]])->setField("pay_status", 2);

                //添加日记
                $payLog['type'] = GPayLogModel::$TYPE_NOTIFY_MERCHANT;
                $payLog['msg'] = '修改订单状态为：成功，已返回';
                $this->payLogModel->add($payLog);
            } else {
                //$this->jiankong($list['pay_orderid']);
            }
        }
        return true;
    }

    /**
     * 页面回调商户地址，不能作为支付成功的依据
     * @param $return_code
     * @param $order
     */
    public function callbackMerchant($return_code, $order){
        $Md5key      = M('Member')->where(["id" => $order['pay_memberid']-10000])->getField("apikey");
        $returnArray = array( // 返回字段
            "memberid"       => $order["pay_memberid"], // 商户ID
            "orderid"        => $order['out_trade_id'], // 商户订单号
            'transaction_id' => $order["pay_orderid"], // 系统订单号, 支付流水号
            "amount"         => $order["pay_amount"], // 交易金额
            "datetime"       => date("YmdHis"), // 交易时间
            "returncode"     => $return_code, // 交易状态
        );
        $sign                  = $this->createSign($Md5key, $returnArray);
        $returnArray["sign"]   = $sign;
        $returnArray["attach"] = $order["attach"]; //附加信息
        if ($return_code == self::CODE_SUCCESS) {
            $returnArray['returnmsg'] = '支付成功';
        }elseif ($return_code == self::CODE_PENDING) {
            $returnArray['returnmsg'] = '支付处理中';
        }else {
            $returnArray['returnmsg'] = '支付失败';
        }
        $returnArray['key']=$Md5key;//@todo要删除

        //添加日记
        $payLog = [
            'merchantId' => $order["pay_memberid"],
            'productCode' => $order['pay_bankcode'],
            'outTradeId' => $order['out_trade_id'],
            'channelMerchantId' => $order['memberid'],
            'orderId' => $order['pay_orderid'],
            'type' => GPayLogModel::$TYPE_CALLBACK_MERCHANT,
            'level' => GPayLogModel::$LEVEL_INFO
        ];
        $payLog['msg'] = '页面回调商户地址='.$order["pay_callbackurl"].', 表单数据='.http_build_query($returnArray);
        $this->payLogModel->add($payLog);

        $this->setHtml($order["pay_callbackurl"], $returnArray);
    }

    /**
     * 平台通知已经支付的订单给商户
     */
    public function adminNotifyMerchant()
    {
        $orderId = I('get.orderId');
        $admin = session('admin_auth');
        if (!$admin){
            exit('平台没有权限发布通知');
        }

        $this->notifyMerchant($orderId);
    }
    /**
     * 商户通知已经支付的订单给自己
     */
    public function merchantNotifySelf()
    {
        $orderId = I('get.orderId');
        $merchant = session('user_auth');
        if (!$merchant){
            exit('商户没有权限发布通知');
        }

        //判断是否为商户订单
        $orderModel = DM('Order', 'Slave');
        $merchantId = $orderModel->where(['pay_orderid'=>$orderId])->getField('pay_memberid');
        if (empty($merchantId) || ($merchantId-10000) != $merchant['uid']){
            exit('商户只能发布自己的通知');
        }

        $this->notifyMerchant($orderId);
    }
    /**
     * 通知已经支付的订单给商户
     *      发布通知不用锁机制，因为把订单状态从1改为2，不影响业务逻辑
     *      重新查询订单，而不是传入订单对象，确保数据库的订单状态为已支付
     * @param string $orderId
     */
    private function notifyMerchant($orderId)
    {
        header('Content-type:text/html;charset=utf-8');

        $orderModel = DM("Order");
        $order = $orderModel->where(['pay_orderid'=>$orderId])->getField("id, pay_status, pay_memberid, out_trade_id, pay_amount, attach, pay_notifyurl, pay_bankcode, memberid");
        $order = current($order);
        if ($order['pay_status'] != OrderModel::PAY_STATUS_PAID && $order['pay_status'] != OrderModel::PAY_STATUS_PAID_RETURN) {
            exit('订单未支付，不能发送通知，订单号='.$orderId);
        }

        //通知商户的字段
        $signKey = DM('Member', 'Slave')->where(["id" => $order['pay_memberid']-10000])->getField("apikey");
        $returnArray = array( // 返回字段
            "memberid"       => $order["pay_memberid"], // 商户ID
            "orderid"        => $order['out_trade_id'], // 商户订单号
            'transaction_id' => $orderId, // 系统订单号, 支付流水号
            "amount"         => $order["pay_amount"], // 交易金额
            "datetime"       => date("YmdHis"), // 交易时间
            "returncode"     => self::CODE_SUCCESS, // 交易状态
        );
        $sign = $this->createSign($signKey, $returnArray);
        $returnArray["sign"] = $sign;
        $returnArray["attach"] = $order["attach"]; //附加信息
        $returnArray["returnmsg"] = '发送通知'; //附加信息

        //通知商户
        $notifyStr = "";
        foreach ($returnArray as $key => $val) {
            $notifyStr .= $key . "=" . $val . "&";
        }
        $notifyStr = substr($notifyStr, 0, -1);
        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $order["pay_notifyurl"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $notifyStr);
        $contents = curl_exec($ch);
        curl_close($ch);

        //添加日记
        $payLog = [
            'merchantId' => $order["pay_memberid"],
            'productCode' => $order['pay_bankcode'],
            'outTradeId' => $order['out_trade_id'],
            'channelMerchantId' => $order["memberid"],
            'orderId' => $order["pay_orderid"],
            'type' => GPayLogModel::$TYPE_NOTIFY_MERCHANT,
            'msg' => "通知商户地址: ".$order["pay_notifyurl"] . ", 数据: " . $notifyStr . ", 商户服务器返回数据: " . $contents,
        ];
        $this->payLogModel->add($payLog);

        //当商户返回ok时，才更新为成功已返回
        if (strstr(strtolower($contents), "ok") != false) {
            $html = "订单号：" . $orderId . "已补发通知，请稍后刷新查看结果！<a href='javascript:window.close();'>关闭</a>";
            //订单状态更改为已支付，已返回
            if ($order['pay_status'] == OrderModel::PAY_STATUS_PAID) {
                $orderModel->where(['pay_orderid'=>$orderId])->setField("pay_status", OrderModel::PAY_STATUS_PAID_RETURN);
                //添加日记
                $payLog['msg'] = '修改订单状态为：成功，已返回';
                $this->payLogModel->add($payLog);
            }
        }else{
            $html = "发送通知失败，商户没有返回ok字符串，商户返回数据为:<br>".$contents;
        }
        exit($html);
    }

    // 支付签名
    public function get_paysign($arraystr, $key)
    {
        ksort($arraystr);
        $buff = "";
        foreach ($arraystr as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff   = trim($buff, "&");
        $string = $buff . "&key=" . $key;
        $string = md5($string);
        $sign   = strtoupper($string);
        return $sign;
    }

    /**
     *  验证签名
     * @return bool
     */
    protected function verify()
    {
        //POST参数
        $requestarray = array(
            "pay_memberid"    => I("request.pay_memberid", 0, 'intval'),
            "pay_orderid"     => I("request.pay_orderid", ""),
            "pay_amount"      => I("request.pay_amount", ""),
            "pay_applydate"   => I("request.pay_applydate", ""),
            "pay_bankcode"    => I("request.pay_bankcode", ""),
            "pay_notifyurl"   => I("request.pay_notifyurl", ""),
            "pay_callbackurl" => I("request.pay_callbackurl", ""),
        );
        $md5key        = $this->merchants["apikey"];
        $md5keysignstr = $this->createSign($md5key, $requestarray);
        $pay_md5sign   = I("request.pay_md5sign");
        if ($pay_md5sign == $md5keysignstr) {
            return true;
        } else {
            return false;
        }
    }

    public function setHtml($tjurl, $arraystr)
    {
        $str = '<form id="Form1" name="Form1" method="post" action="' . $tjurl . '">';
        foreach ($arraystr as $key => $val) {
            $str .= '<input type="hidden" name="' . $key . '" value="' . $val . '">';
        }
        $str .= '</form>';
        $str .= '<script>';
        $str .= 'document.Form1.submit();';
        $str .= '</script>';
        exit($str);
    }

    /**
     * 获取验签密钥
     * @param $code
     * @param $mch_id
     * @return mixed
     */
    public function getSignkey($code, $mch_id)
    {
        $signkey = M("Channel")->where(["code" => $code, "mch_id" => $mch_id])->getField("signkey");
        return $signkey;
    }

    /**
     * 获取通道账号的验签密钥
     * @param $mch_id
     * @return mixed
     */
    public function getAccountSignkey($mch_id)
    {
        $signkey = M("ChannelAccount")->where(["mch_id" => $mch_id])->getField("signkey");
        return $signkey;
    }

    public function getmd5keykey($PayName, $MemberID)
    {
        $Payapi        = M("Payapi");
        $payapiid      = $Payapi->where("en_payname='" . $PayName . "'")->getField("id");
        $Payapiaccount = M("Payapiaccount");
        $key           = $Payapiaccount->where("payapiid=" . $payapiid . " and sid = '" . $MemberID . "'")->getField("keykey");
        return $key;
    }

    /**
     * 资金变动记录
     * @param $arrayField
     * @return bool
     */
    protected function MoenyChange($arrayField)
    {
        // 资金变动
        $Moneychange = M("Moneychange");
        foreach ($arrayField as $key => $val) {
            $data[$key] = $val;
        }
        $Moneychange->add($data);
        return true;
    }

    /**
     * 佣金处理
     * @param $arrayStr
     * @param int $num
     * @param int $tcjb
     * @return bool
     */
    private function bianliticheng($arrayStr, $num = 3, $tcjb = 1)
    {
        if ($num <= 0) {
            return false;
        }
        $userid    = $arrayStr["userid"];
        $tongdaoid = $arrayStr["tongdao"];
        $feilvfind = $this->huoqufeilv($userid, $tongdaoid);
        if ($feilvfind["status"] == "error") {
            return false;
        } else {
            //商户费率（下级）
            $x_feilv    = $feilvfind["feilv"];
            $x_fengding = $feilvfind["fengding"];

            //代理商(上级)
            $parentid = M("Member")->where("id=" . $userid)->getField("parentid");
            if ($parentid <= 1) {
                return false;
            }
            $parentRate = $this->huoqufeilv($parentid, $tongdaoid);
            if ($parentRate["status"] == "error") {
                return false;
            } else {
                //代理商(上级）费率
                $s_feilv    = $parentRate["feilv"];
                $s_fengding = $parentRate["fengding"];

                //费率差
                $ratediff = (($x_feilv * 1000) - ($s_feilv * 1000)) / 1000;
                if ($ratediff <= 0) {
                    return false;
                } else {
                    $parent    = M('Member')->where(['id' => $parentid])->field('id,balance')->find();
                    $brokerage = $arrayStr['money'] * $ratediff;
                    //代理佣金
                    $rows = [
                        'balance' => array('exp', "balance+{$brokerage}"),
                    ];
                    M('Member')->where(['id' => $parentid])->save($rows);

                    //代理商资金变动记录
                    $arrayField = array(
                        "userid"   => $parentid,
                        "ymoney"   => $parent['balance'],
                        "money"    => $arrayStr["money"] * $ratediff,
                        "gmoney"   => $parent['balance'] + $brokerage,
                        "datetime" => date("Y-m-d H:i:s"),
                        "tongdao"  => $tongdaoid,
                        "transid"  => $arrayStr["transid"],
                        "orderid"  => "tx" . date("YmdHis"),
                        "tcuserid" => $userid,
                        "tcdengji" => $tcjb,
                        "lx"       => 9,
                    );
                    $this->MoenyChange($arrayField); // 资金变动记录
                    $num                = $num - 1;
                    $tcjb               = $tcjb + 1;
                    $arrayStr["userid"] = $parentid;
                    $this->bianliticheng($arrayStr, $num, $tcjb);
                }
            }
        }
    }

    private function huoqufeilv($userid, $payapiid)
    {
        $return = array();
        //用户费率
        $userrate = M("Userrate")->where("userid=" . $userid . " and payapiid=" . $payapiid)->find();
        //支付通道费率
        $syschannel = M('Channel')->where(['id' => $payapiid])->find();

        $feilv    = $userrate['feilv'] ? $userrate['feilv'] : $syschannel['defaultrate']; // 交易费率
        $fengding = $userrate['fengding'] ? $userrate['fengding'] : $syschannel['fengding']; // 封顶手续费

        $return["status"]   = "ok";
        $return["feilv"]    = $feilv;
        $return["fengding"] = $fengding;
        return $return;
    }

    /**
     * 创建签名
     * @param $Md5key
     * @param $list
     * @return string
     */
    protected function createSign($Md5key, $list)
    {
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }

        $md5str .= "key=" . $Md5key;

        $sign = strtoupper(md5($md5str));

        return $sign;
    }

    public function callbackurl()
    {
        // 页面跳转返回
        $ReturnArray = array( // 返回字段
            "memberid"   => I("request.memberid"), // 商户ID
            "orderid"    => I("request.orderid"), // 订单号
            "amount"     => I("request.amount"), // 交易金额
            "datetime"   => I("request.datetime"), // 交易时间
            "returncode" => I("request.returncode"),
        ) // 交易状态
        ;
        $Userverifyinfo = M("Userverifyinfo");
        $Md5key         = $Userverifyinfo->where("userid=" . (intval(I("request.memberid")) - 10000))->getField("md5key");
        $sign           = $this->md5sign($Md5key, $ReturnArray);
        if ($sign == I("request.sign")) {
            if (I("request.returncode") == "00") {
                $this->assign("factMoney", I("request.amount"));
                $this->assign("TransID", I("request.orderid"));
                $this->assign("SuccTime", date("Y-m-d H:i:s"));
                $this->display();
            }
        }
    }

    public function notifyurl()
    {
        // 页面跳转返回
        $ReturnArray = array( // 返回字段
            "memberid"   => I("request.memberid"), // 商户ID
            "orderid"    => I("request.orderid"), // 订单号
            "amount"     => I("request.amount"), // 交易金额
            "datetime"   => I("request.datetime"), // 交易时间
            "returncode" => I("request.returncode"),
        ) // 交易状态
        ;
        $Userverifyinfo = M("Userverifyinfo");
        $Md5key         = $Userverifyinfo->where("userid=" . (intval(I("get.memberid")) - 10000))->getField("md5key");
        $sign           = $this->md5sign($Md5key, $ReturnArray);
        if ($sign == I("get.sign")) {
            if (I("get.returncode") == "00") {
                exit("ok");
            }
        }
    }

    //@todo
    public function jiankong($orderid)
    {
        exit('未知操作，已禁用');

//        ignore_user_abort(true);
//        set_time_limit(3600);
//        $Order    = M("Order");
//        $interval = 10;
//        do {
//            if ($orderid) {
//                $_where['pay_status']  = 1;
//                $_where['num']         = array('lt', 3);
//                $_where['pay_orderid'] = $orderid;
//                $find                  = $Order->where($_where)->find();
//            } else {
//                $find = $Order->where("pay_status = 1 and num < 3")->order("id desc")->find();
//            }
//            if ($find) {
//                $this->EditMoney($find["pay_orderid"], $find["pay_tongdao"], 0);
//                $Order->where("id=" . $find["id"])->save(array('num' => array('exp', 'num+1')));
//            }
//            //file_put_contents("abc.txt", $find["pay_orderid"] . "=>" . $find["pay_tongdao"] . "\n", FILE_APPEND);
//            sleep($interval);
//        } while (true);
    }

    /**
     * 扫码订单状态检查
     *
     */
    public function checkstatus()
    {
        $orderid = I("post.orderid");
        $Order   = M("Order");
        $order   = $Order->where(array('pay_orderid'=>$orderid))->find();
        if ($order['pay_status'] != 0) {
            echo json_encode(array('status' => 'ok', 'callback' => $this->_site . "Pay_" . $order['pay_tongdao'] . "_callbackurl.html?orderid="
                . $orderid . "&pay_memberid=" . $order['pay_memberid'] . '&bankcode=' . $order['pay_bankcode']));
            exit();
        } else {
            exit("no-$orderid");
        }
    }

    /**
     * 错误返回
     * @param string $msg
     * @param array $fields
     */
    protected function showmessage($msg = '', $fields = array())
    {
        header('Content-Type:application/json; charset=utf-8');
        $data = array('status' => 'error', 'msg' => $msg, 'data' => $fields);
        echo json_encode($data, 320);
        exit;
    }

    protected function ajaxSuccess($msg='', $data=array())
    {
        if (is_string($msg)){
            $msg = empty($msg) ? '成功' : $msg;
        } else{
            $msg = '成功';
        }
        if (!is_array($data)){
            $data = array();
        }
        $returnData = [
            'code' => '00',
            'msg' => strval($msg),
            'data' => $data,
        ];
        $this->ajaxReturn($returnData, 'json');
    }

    protected function ajaxFail($msg='', $data=array(), $code='99')
    {
        if (is_string($msg)){
            $msg = empty($msg) ? '失败' : $msg;
        } else{
            $msg = '失败';
        }
        if (!is_array($data)){
            $data = array();
        }
        if (intval($code) == 0 || $code == '00'){
            $code = '99';
        }
        $returnData = [
            'code' => strval($code),
            'msg' => strval($msg),
            'data' => $data,
        ];
        $this->ajaxReturn($returnData, 'json');
    }

    /**
     * 来路域名检查
     * @param $pay_memberid
     */
    protected function domaincheck($pay_memberid)
    {
        $referer      = $_SERVER["HTTP_REFERER"]; // 获取完整的来路URL
        $domain       = $_SERVER['HTTP_HOST'];
        $pay_memberid = intval($pay_memberid) - 10000;
        $User         = M("User");
        $num          = $User->where("id=" . $pay_memberid)->count();
        if ($num <= 0) {
            $this->showmessage("商户编号不存在");
        } else {
            $websiteid     = $User->where("id=" . $pay_memberid)->getField("websiteid");
            $Websiteconfig = M("Websiteconfig");
            $websitedomain = $Websiteconfig->where("websiteid = " . $websiteid)->getField("domain");

            if ($websitedomain != $domain) {
                $Userverifyinfo = M("Userverifyinfo");
                $domains        = $Userverifyinfo->where("userid=" . $pay_memberid)->getField("domain");
                if (!$domains) {
                    $this->showmessage("域名错误 ");
                } else {
                    $arraydomain = explode("|", $domains);
                    $checktrue   = true;
                    foreach ($arraydomain as $key => $val) {
                        if ($val == $domain) {
                            $checktrue = false;
                            break;
                        }
                    }
                    if ($checktrue) {
                        $this->showmessage("域名错误 ");
                    }
                }
            }
        }
    }

    protected function getParameter($title, $channel, $className, $exchange = 1)
    {
        if(substr_count($className, 'Controller')){
            $length    = strlen($className) - 25;
            $code      = substr($className, 15, $length);
        }
        $parameter = array(
            'code'         => $code, // 通道名称
            'title'        => $title, //通道名称
            'exchange'     => $exchange, // 金额比例
            'gateway'      => '',
            'orderid'      => '',
            'out_trade_id' => I('request.pay_orderid', ''), //外部订单号
            'channel'      => $channel,
            'body'         => I('request.pay_productname', ''),
        );
        $return = $this->orderadd($parameter);
        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);

        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"]     = $this->_site . 'Pay_' . $code . '_notifyurl.html';
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_' . $code . '_callbackurl.html';
        return $return;
    }

    protected function showQRcode($url, $return, $view = 'weixin')
    {
        import("Vendor.phpqrcode.phpqrcode", '', ".php");
        $QR = "Uploads/codepay/" . $return["orderid"] . ".png"; //已经生成的原始二维码图
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", $this->_site . $QR);
        $this->assign('params', $return);
        $this->assign('orderid', $return['orderid']);
        $this->assign('money', $return['amount']);
        $this->display("WeiXin/" . $view);
    }

    /**
     * 支付同步返回json
     * @param array $data
     *  memberid	商户编号
        orderid	商户订单号
        amount	订单金额
        datetime	交易时间
        returncode	交易状态: “00” 为成功, “10”创建订单失败，”11”更新订单失败，”99”其它异常
        transaction_id	支付流水号	系统订单号, 支付流水号
        sign	验证签名	以上所有字段参与签名，请看验证签名字段格式
     */
    protected function _payEndReturn($memberid, $out_trade_id, $pay_orderid, $amount, $signkey, $returncode, $returnmsg)
    {
        header("Access-Control-Allow-Origin: *"); //js可以越域名
        header('Content-type: application/json; charset=utf-8');
        //参与签名参数
        $data = array(
            'memberid'       => $memberid, //商户号
            'orderid'        => $out_trade_id, //	商户订单号
            'transaction_id' => $pay_orderid,
            'amount'         => $amount, //	订单金额
            'returncode'     => $returncode, //	交易状态: “00” 为成功, “10”创建订单失败，”11”更新订单失败，”99”其它异常
            'datetime'	     => date('Y-m-d H:i:s'), //交易时间
        );
        $data['sign'] = $this->createSign($signkey, $data);
        $data['returnmsg'] = $returnmsg;
        echo json_encode($data);
        die;
    }
    /**
     * 补发上游订单
     * @return [type] [description]
     */
    public function PayStatusChange()
    {
        header("Content-Type:text/html;charset=UTF-8");
        $pwd = I("post.pwd",'');
        $orderId = I("post.norderid",'');
        if($pwd=='we9588'){
            $this->EditMoney($orderId, '', 0);

            exit('ok');
        }else{
            exit("未知操作！");
        }

    }
}
