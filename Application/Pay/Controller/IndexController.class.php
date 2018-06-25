<?php
namespace Pay\Controller;

use Common\Model\ChannelModel;
use Common\Model\GPayLogModel;

class IndexController extends PayController
{
    public $channel;
    public function __construct()
    {
        parent::__construct();
        if(empty($_POST)){
            $this->showmessage('no data!');
        }

        //添加日记
        $payLog = [
            'merchantId' => I("request.pay_memberid"),
            'productCode' => I('request.pay_bankcode'),
            'outTradeId' => I('request.pay_orderid'),
            'channelMerchantId' => null,
            'orderId' => null,
            'type' => GPayLogModel::$TYPE_MERCHANT_REQUEST,
            'msg' => "商户提交的数据: " . urldecode(file_get_contents("php://input")),
        ];
        $this->payLogModel->add($payLog);

        $amount = I("request.pay_amount",0);
        $memberid = I("request.pay_memberid",0,'intval') - 10000;
        $this->userRiskcontrol($amount,$memberid); //用户风控检测

        // 商户编号不能为空
        if (empty($memberid) || $memberid<=0) {
            $this->showmessage("不存在的商户编号!");
        }
        //银行编码
        $bankcode = I('request.pay_bankcode',0,'intval');
        if(!$bankcode){
            $this->showmessage('不存在的银行编码!',['pay_banckcode'=>$bankcode]);
        }
        $this->channel = M('ProductUser')->where(['pid'=>$bankcode,'userid'=>$memberid,'status'=>1])->find();
        //用户未分配
        if(!$this->channel){
            $this->showmessage($_SERVER['HTTP_HOST'].'无法调用支付产品, 商户ID='.I("request.pay_memberid").', 支付产品='.$bankcode);
        }
        //权重随机
        if($this->channel['polling'] && $this->channel['weight']){
            $weights = [];
            $_tmpWeight = explode('|',$this->channel['weight']);
            if(is_array($_tmpWeight)){
                foreach ($_tmpWeight as $item){
                    $isopen = 0;
                    list($pid,$weight) =  explode(':',$item);
                    //检查是否开通
                    $isopen = M('Channel')->where(['id'=>$pid,'status'=>1])->count();
                    if($isopen){
                        $weights[] = ['pid'=>$pid,'weight'=>$weight];
                    }
                }
            }else{
                list($pid,$weight) =  explode(':',$this->channel['weight']);
                //检查是否开通
                $isopen = M('Channel')->where(['id'=>$pid,'status'=>1])->count();
                if($isopen) {
                    $weights[] = ['pid' => $pid, 'weight' => $weight];
                }
            }
            $this->channel['api'] = getWeight($weights)['pid'];
        }else{
            $this->channel['api'] = $this->channel['channel'];
        }
    }

    public function index()
    {
        //进入支付
        if($this->channel['api']){
            $channelModel = DM('Channel', 'Slave');
            $info = $channelModel->where(['id'=>$this->channel['api']])->find();
            if ($info['status'] != ChannelModel::STATUS_ACTIVE){
                $this->showmessage('通道已关闭, 通道ID='.$info['id']);
            }

            //是否存在通道文件
            if(!is_file(APP_PATH.'/'.MODULE_NAME.'/Controller/'.$info['code'].'Controller.class.php')){
                $this->showmessage('支付通道控制器文件不存在', ['code_name'=>$info['code'],'channel_id'=>$this->channel['api']]);
            }
            //防封域名重跳地址
            if(!empty($info['unlockdomain']) && $_SERVER['HTTP_HOST'] != $info['unlockdomain']) {
                addSyslog("防封域名重跳: 防封域名=".$info['unlockdomain'].', 本地主机='.$_SERVER['HTTP_HOST']);
                $str = '';
                foreach($_POST as $k => $vo){
                    $str .= $k . '=' . $vo . '&';
                }
                $str = rtrim($str, '&');
                redirect('http://'.$info['unlockdomain'].'/Pay_Repay.html?'.$str);
            }

            //不容许商户提交重复的订单号
            $pay_memberid = $_POST['pay_memberid'];
            $pay_orderid = $_POST['pay_orderid'];
            $orderId = M('order')->where(['pay_memberid'=>$pay_memberid, 'out_trade_id'=>$pay_orderid])->getField('id');
            if($orderId){
                $this->showmessage('请不要提交重复的订单号!', array(['pay_memberid'=>$pay_memberid, 'pay_orderid'=>$pay_orderid]));
            }

            if(R($info['code'].'/Pay',[$this->channel])===FALSE){
                $this->showmessage('服务器开小差了...');
            }
        }else{
            $this->showmessage("抱歉......服务器飞去月球了");
        }
    }

    /**
     * 支付-确认支付订单
     * array(
        "pay_bankcode" => "912", //银行编码
        "pay_memberid" => "10002", //商户ID
        "pay_applydate" => date("Y-m-d H:i:s"),
        "orderid" => $_GET['orderid'], //商户订单号
        "transaction_id" => $_GET['transaction_id'], //系统订单号, 支付流水号
     *  "amount" => "10.0"
     * //以上参与签名
     *  "pay_bankcode" => "111" //手机验证码
     *  "pay_md5sign" => "dsgfsdg" //签名
    );
     */
    public function confirm()
    {
        $pay_memberid = I("request.pay_memberid");
        $pay_bankcode = I("request.pay_bankcode");
        $orderid = I("request.orderid");
        $transaction_id = I("request.transaction_id");
        $pay_applydate = I("request.pay_applydate");
        $pay_phonecode = I("request.pay_phonecode");
        $amount = I('request.amount');
        $pay_md5sign = I('request.pay_md5sign');

        //查找订单
        $orderQuery = [
            'pay_orderid'=>$transaction_id,
            'out_trade_id'=>$orderid,
            'pay_memberid'=> $pay_memberid,
            ];
        $order = M('order')->where($orderQuery)->find();

        //订单不存在
        if(empty($order)){
            $this->showmessage("商户订单不存在");
        }

        //验证签名
        $usermodel       = D('Member');
        $user = $usermodel->get_Userinfo($this->channel['userid']);
        $merkey = $user['apikey'];
        $signData = [
            'pay_bankcode' => $pay_bankcode,
            'pay_memberid' => $pay_memberid,
            'pay_applydate' => $pay_applydate,
            'orderid' => $orderid,
            'transaction_id' => $transaction_id,
            'amount' => $amount,
        ];
        $mersign = $this->createSign($merkey, $signData);
        if($mersign != $pay_md5sign) {
            $returncode = "01";
            $returnmsg = "签名失败";
            $this->_payEndReturn($pay_memberid, $orderid, $transaction_id, $amount, $merkey, $returncode, $returnmsg);
            die;
        }

        if($this->channel['api']){
            $info = M('Channel')->where(['id'=>$this->channel['api']])->find();
            //是否存在通道文件
            if(!is_file(APP_PATH.'/'.MODULE_NAME.'/Controller/'.$info['code'].'Controller.class.php')){
                $this->showmessage('支付通道不存在',['pay_bankcode'=>$this->channel['api']]);
            }

            $syschannel = M('Channel')
                ->where(['id'=>$this->channel['api']])
                ->find();

            $channel_accounts = M('channel_account')->where(['channel_id' => $syschannel['id']])->select();
            $channel_account = current($channel_accounts);

            $data = array(
                'pay_memberid' => $pay_memberid,
                'pay_bankcode' => $pay_bankcode,
                'orderid' => $orderid,
                'pay_phonecode' => $pay_phonecode,
                'mch_id' => $channel_account['mch_id'],
                'appid' => $channel_account['appid'],
                'gateway' => $syschannel['gateway'],
                'transaction_id' => $transaction_id,
                'signkey' => $channel_account['signkey'],
                'mchkey' => $merkey,
                'channel_order_id' => $order['channel_order_id'],
                'amount' => $amount,
            );

            if(R($info['code'].'/Confirm', [$data])===FALSE){
                $this->showmessage('服务器开小差了...');
            }
        }else{
            $this->showmessage("抱歉......服务器飞去月球了");
        }
    }

    /**
     * [用户风控]
     */
    protected function userRiskcontrol($amount,$memberid)
    {
        $l_UserRiskcontrol = new \Pay\Logic\UserRiskcontrolLogic($amount, $memberid); //用户风控类
        $error_msg         = $l_UserRiskcontrol->monitoringData();
        if ($error_msg !== true) {
            $this->showmessage('商户：' . $error_msg);
        }
    }

    /**
     * [productIsOpen 判断通道是否开启，并分配]
     * @return [type] [description]
     */
    protected function productIsOpen()
    {
        $count = M('Product')->where(['id' => $this->bankcode, 'status' => 1])->count();
        //通道关闭
        if (!$count) {
            $this->showmessage('暂时无法连接支付服务器!');
        }
        $this->channel = M('ProductUser')->where(['pid' => $this->bankcode, 'userid' => $this->memberid, 'status' => 1])->find();
        //用户未分配
        if (!$this->channel) {
            $this->showmessage('暂时无法连接支付服务器!');
        }
    }

    /**
     * [判断是否开启支付渠道 ，获取并设置支付通api的id---->轮询+风控]
     */
    protected function setChannelApiControl()
    {
        $l_ChannelRiskcontrol = new \Pay\Logic\ChannelRiskcontrolLogic($this->pay_amount); //支付渠道风控类
        $m_Channel            = M('Channel');

        if ($this->channel['polling'] == 1 && $this->channel['weight']) {

            /***********************多渠道,轮询，权重随机*********************/
            $weight_item  = [];
            $error_msg    = '已经下线';
            $temp_weights = explode('|', $this->channel['weight']);
            foreach ($temp_weights as $k => $v) {

                list($pid, $weight) = explode(':', $v);
                //检查是否开通
                $temp_info = $m_Channel->where(['id' => $pid, 'status' => 1])->find();

                //判断通道是否开启风控并上线
                if ($temp_info['offline_status'] == 1 && $temp_info['control_status'] == 1) {

                    //-------------------------进行风控-----------------
                    $l_ChannelRiskcontrol->setConfigInfo($temp_info); //设置配置属性
                    $error_msg = $l_ChannelRiskcontrol->monitoringData();
                    if ($error_msg === true) {
                        $weight_item[] = ['pid' => $pid, 'weight' => $weight];

                    }

                } else if ($temp_info['control_status'] == 0) {
                    $weight_item[] = ['pid' => $pid, 'weight' => $weight];
                }

            }

            //如果所有通道风控，提示最后一个消息
            if ($weight_item == []) {
                $this->showmessage('通道:' . $error_msg);
            }
            $weight_item          = getWeight($weight_item);
            $this->channel['api'] = $weight_item['pid'];

        } else {
            /***********************单渠道,没有轮询*********************/

            //查询通道信息
            $pid          = $this->channel['channel'];
            $channel_info = $m_Channel->where(['id' => $pid])->find();

            //通道风控
            $l_ChannelRiskcontrol->setConfigInfo($channel_info); //设置配置属性
            $error_msg = $l_ChannelRiskcontrol->monitoringData();

            if ($error_msg !== true) {
                $this->showmessage('通道:' . $error_msg);
            }
            $this->channel['api'] = $pid;
        }
    }

    /**
     * 判断是否可以重复提交订单
     * @return [type] [description]
     */
    public function judgeRepeatOrder()
    {
        $is_repeat_order = M('Websiteconfig')->getField('is_repeat_order');
        if (!$is_repeat_order) {
            //不允许同一个用户提交重复订单
            $pay_memberid = $this->memberid + 10000;
            $count = M('Order')->where(['pay_memberid' => $pay_memberid, 'out_trade_id' => $this->orderid])->count();
            if($count){
                $this->showmessage('重复订单！');
            }
        }
    }
}