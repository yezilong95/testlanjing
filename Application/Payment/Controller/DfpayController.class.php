<?php
/*
 * 代付API
 */
namespace Payment\Controller;
use Think\Controller;
use Common\Model\GDaiFuLogModel;

class DfpayController extends Controller
{
    //商家信息
    protected $merchants;
    //网站地址
    protected $_site;
    //通道信息
    protected $channel;

    //代付日记模型
    protected $logModel;



    public function __construct()
    {
        parent::__construct();
        $this->logModel = DM('GDaiFuLog');
        $this->_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
    }
    /**
     * 创建代付申请
     * @return array
     */
    public function add()
    {
        $return = array('status' => 'error', 'msg' => '代付申请失败');

        if (empty($_POST)) {
            $return['msg'] = '参数没有提交过来';
            $this->ajaxReturn($return);
        }
        $siteconfig = M("Websiteconfig")->find();
        if(!$siteconfig['df_api']) {
            $return['msg'] = '代付API未开启';
            $this->ajaxReturn($return);
        }
        $sign = I('request.pay_md5sign');
        if(!$sign) {
            $return['msg'] = '缺少签名参数';
            $this->ajaxReturn($return);
        }
        $mchid = I("post.mchid", 0);
        if(!$mchid) {
            $return['msg'] = '商户ID不能为空';
            $this->ajaxReturn($return);
        }
        $user_id =  $mchid - 10000;
        //用户信息
        $this->merchants = D('Member')->where(array('id'=>$user_id))->find();
        if(empty($this->merchants)) {
            $return['msg'] = '商户不存在';
            $this->ajaxReturn($return);
        }
        if(!$this->merchants['df_api']) {
            $return['msg'] = '商户未开启此功能';
            $this->ajaxReturn($return);
        }
        $money = I("post.money", 0);
        if($money<=0) {
            $return['msg'] = '金额错误';
            $this->ajaxReturn($return);
        }
        $bankname = I("post.bankname", '');
        if(!$bankname) {
            $return['msg'] = '银行名称不能为空';
            $this->ajaxReturn($return);
        }
        $subbranch = I("post.subbranch", '');
        if(!$subbranch) {
            $return['msg'] = '支行名称不能为空';
            $this->ajaxReturn($return);
        }
        $accountname = I("post.accountname", '');
        if(!$accountname) {
            $return['msg'] = '开户名不能为空';
            $this->ajaxReturn($return);
        }
        $cardnumber = I("post.cardnumber", '');
        if(!$cardnumber) {
            $return['msg'] = '银行卡号不能为空';
            $this->ajaxReturn($return);
        }
        $province = I("post.province");
        if(!$province) {
            $return['msg'] = '省份不能为空';
            $this->ajaxReturn($return);
        }
        $city = I("post.city");
        if(!$city) {
            $return['msg'] = '城市不能为空';
            $this->ajaxReturn($return);
        }
        $out_trade_no = I("post.out_trade_no", '');
        if(!$out_trade_no) {
            $return['msg'] = '订单号不能为空';
            $this->ajaxReturn($return);
        }
        //$notifyurl = I("post.notifyurl", '');
        $extends = I("post.extends", '');
        //当前可用代付渠道
        $channel_ids = M('pay_for_another')->where(['status' => 1])->getField('id', true);
        //获取渠道扩展字段
        $fields = M('pay_channel_extend_fields')->where(['channel_id'=>['in',$channel_ids]])->select();
        if(!empty($fields)) {
            if(!$extends) {
                $return['msg'] = '扩展字段不能为空';
                $this->ajaxReturn($return);
            }
            $extend_fields_array = json_decode(base64_decode($extends), true);
            foreach($fields as $k => $v) {
                if(!isset($extend_fields_array[$v['name']]) || !$extend_fields_array[$v['name']]) {
                    $return['msg'] = '扩展字段【'.$v['alias'].'】不能为空';
                    $this->ajaxReturn($return);
                }
            }
        }
        //验签
        if ($this->verify($_POST)) {
            $Order                 = M("df_api_order");
            $data['userid']        = $user_id;
            $data['trade_no']      = $this->getOrderId();
            $data['out_trade_no']  = $out_trade_no;
            $data['money']         = $money;
            $data['bankname']      = $bankname;
            $data['subbranch']     = $subbranch;
            $data['accountname']   = $accountname;
            $data['cardnumber']    = $cardnumber;
            $data['province']      = $province;
            $data['city']          = $city;
            $data['ip']            = get_client_ip();
            $data['status']        = 0;
            $data['extends']       = base64_decode($extends);
            //$data['notifyurl']     = $notifyurl;
            $data['create_time'] = time();

            /* 验证是否合法-start */
            //判断是否设置了节假日不能提现
            $tkHolidayList = M('Tikuanholiday')->limit(366)->getField('datetime', true);
            if ($tkHolidayList) {
                $today = date('Ymd');
                foreach ($tkHolidayList as $k => $v) {
                    if ($today == date('Ymd', $v)) {
                        $return['msg'] = '节假日暂时无法提交代付';
                        $this->ajaxReturn($return);
                    }
                }
            }

            //结算方式
            $Tikuanconfig = M('Tikuanconfig');
            $tkConfig     = $Tikuanconfig->where(['userid' => $user_id, 'tkzt' => 1])->find();
            $defaultConfig = $Tikuanconfig->where(['issystem' => 1, 'tkzt' => 1])->find();
            //判断是否开启提款设置
            if (!$defaultConfig) {
                $return['msg'] = '提款已关闭';
                $this->ajaxReturn($return);
            }
            //判断是否设置个人规则
            if (!$tkConfig || $tkConfig['tkzt'] != 1 || $tkConfig['systemxz'] != 1) {
                $tkConfig = $defaultConfig;
            } else {
                //个人规则，但是提现时间规则要按照系统规则
                $tkConfig['allowstart'] = $defaultConfig['allowstart'];
                $tkConfig['allowend']   = $defaultConfig['allowend'];
            }
            //是否在许可的提现时间
            $hour = date('H');
            //判断提现时间是否合法
            if ($tkConfig['allowend'] != 0) {
                if ($tkConfig['allowstart'] > $hour || $tkConfig['allowend'] <= $hour) {
                    $return['msg'] = '不在提现时间，请换个时间再来';
                    $this->ajaxReturn($return);
                }
            }

            //单笔最小提款金额
            $tkzxmoney = $tkConfig['tkzxmoney'];
            //单笔最大提款金额
            $tkzdmoney = $tkConfig['tkzdmoney'];
            //查询代付表跟提现表的条件
            $map['userid']     = $user_id;
            $map['sqdatetime'] = ['between', [date('Y-m-d', strtotime('yesterday')), date('Y-m-d')]];
            //统计提现表的数据
            $Tklist = M('Tklist');
            $tkNum  = $Tklist->where($map)->count();
            $tkSum  = $Tklist->where($map)->sum('tkmoney');
            //统计代付表的数据
            $Wttklist = M('Wttklist');
            $wttkNum  = $Wttklist->where($map)->count();
            $wttkSum  = $Wttklist->where($map)->sum('tkmoney');
            //判断是否超过当天次数
            $dayzdnum = $tkNum + $wttkNum + 1;
            if ($dayzdnum >= $tkConfig['dayzdnum']) {
                $return['msg'] = '超出当日提款次数';
                $this->ajaxReturn($return);
            }

            //判断提款额度
            $dayzdmoney = bcadd($wttkSum, $tkSum, 2);
            if ($dayzdmoney >= $tkConfig['dayzdmoney']) {
                $return['msg'] = '超出当日提款额度';
                $this->ajaxReturn($return);
            }
            $balance = $this->merchants['balance'];
            if ($balance <= $money) {
                $return['msg'] = '可用余额不足';
                $this->ajaxReturn($return);
            }
            if ($money < $tkzxmoney || $money > $tkzdmoney) {
                $return['msg'] = '提款金额不符合提款额度要求';
                $this->ajaxReturn($return);
            }
            $dayzdmoney = bcadd($money, $dayzdmoney, 2);
            if ($dayzdmoney >= $tkConfig['dayzdmoney']) {
                $return['msg'] = '超出当日提款额度';
                $this->ajaxReturn($return);
            }
            /* 验证是否合法-end */

            //添加订单
            if ($Order->add($data)) {
                header('Content-Type:application/json; charset=utf-8');
                $return = array('status' => 'success', 'msg' => '代付申请成功', 'transaction_id'=>$data['trade_no']);
                $this->ajaxReturn($return);
            } else {
                $return['msg'] = '系统错误';
                $this->ajaxReturn($return);
            }
        } else {
            $return['msg'] = '签名验证失败';
            $this->ajaxReturn($return);
        }
    }

    //代付查询
    public function query()
    {
        $query_data = I('request.','');
        $out_trade_no = I('request.out_trade_no');
        $sign = I('request.pay_md5sign');
        if(!$sign) {
            $this->showmessage("缺少签名参数");
        }
        if(!$out_trade_no){
            $this->showmessage("缺少订单号");
        }
        $mchid = I("request.mchid");
        if(!$mchid) {
            $this->showmessage("缺少商户号");
        }
        $user_id = $mchid - 10000;
        //用户信息
        $this->merchants = D('Member')->where(array('id'=>$user_id))->find();
        if(empty($this->merchants)) {
            $this->showmessage('商户不存在！');
        }
        if(!$this->merchants['df_api']) {
            $this->showmessage('商户未开启此功能！');
        }
        $request = [
            'mchid'=>$mchid,
            'out_trade_no'=>$out_trade_no
        ];

        $signString = $this->createSign($this->merchants['apikey'],$request);
        $signature = strtoupper(md5($signString));
        if($signature != $sign){
            $this->showmessage('验签失败!');
        }
        $order = M('df_api_order')->where(['out_trade_no'=>$out_trade_no,
            'userid'=>$user_id])->find();
        if(!$order){
            $refCode = '7';
            $refMsg = '交易不存在';
        }
        elseif($order['check_status']==0){
            $refCode = '6';
            $refMsg = "待审核";
        }elseif($order['check_status']==2) {
            $refCode = '5';
            $refMsg = "审核驳回";

        }else{
            if($order['df_id'] > 0) {
                $df_order = M('wttklist')->where(['id'=>$order['df_id'],'_logic' => 'AND', 'userid'=>$user_id])->find();
                //$df_order = M('wttklist')->where(['df_id'=>$order['df_id'],'_logic' => 'AND', 'userid'=>$user_id])->select();
                if($df_order['status'] == 0) {
                    $refCode = '4';
                    $refMsg = "待处理";
                } elseif($df_order['status'] == 1) {
                    $refCode = '3';
                    $refMsg = "处理中";
                } elseif($df_order['status'] == 2) {
                    $refCode = '1';
                    $refMsg = "成功";
                } elseif($df_order['status'] == 3) {
                    $refCode = '2';
                    $refMsg = "失败";
                } else {
                    $refCode = '8';
                    $refMsg = "未知状态";
                }
            }
        }
       
       
        $return = [
            'status'=>'success',
            'mchid'=>$mchid,
            'out_trade_no'=>$order['out_trade_no'],
            'amount'=>$order['money'],
            'transaction_id'=>$order['trade_no'],
            'refCode'=>$refCode,
            'refMsg'=>$refMsg,
        ];
        if($refCode == 1) {
            $return['success_time'] = date('Y-m-d H:i:s',$df_order['cldatetime']);
        }
        $return['sign'] = $this->createSign($this->merchants['apikey'],$return);
        //$return['sign'] = $this->createSign($this->apikey,$return);
        // addSyslog('api代付查询-下游参数：'.json_encode($query_data));
        // addSyslog('api代付查询-平台返回数据：'.json_encode($return));
        echo json_encode($return);
    }

    /**
     * 获得订单号
     *
     * @return string
     */
    public function getOrderId()
    {
        $year_code = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $i         = intval(date('Y')) - 2010 - 1;

       /* return $year_code[$i] . date('md') .
            substr(time(), -5) . substr(microtime(), 2, 5) . str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT);*/
            return 'LJAPiDF'.date("YmdHis").rand(10000,99999);
    }


    /**
     *  验证签名
     * @return bool
     */
    protected function verify($param)
    {
        $md5key        = $this->merchants['apikey'];
        $md5keysignstr = $this->createSign($md5key, $param);
        $sign = strtoupper(md5($md5keysignstr));
        $pay_md5sign   = I('request.pay_md5sign');
        if ($pay_md5sign == $sign) {
            return true;
        } else {
            //添加日记
            $logTitle = 'api代付平台签名原串：';
            $log = [
                'merchantId' => null,
                'code' => null,
                'channelMerchantId' => null,
                'orderId' => null,
                'type' => GDaiFuLogModel::TYPE_SUBMIT,
                'level' => GDaiFuLogModel::LEVEL_INFO,
                'msg' => '',
            ];
            $log['msg'] = $logTitle . $md5keysignstr;
            $this->logModel->add($log);
            return false;
        }
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
            if (!empty($val) && $key != 'pay_md5sign') {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $signString = $md5str . "key=" . $Md5key;
        return $signString;
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
}