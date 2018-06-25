<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-04-02
 * Time: 23:01
 */

namespace Admin\Controller;

use Think\Page;

/**
 * 统计控制器
 * Class StatisticsController
 * @package Admin\Controller
 */
class StatisticsController extends BaseController
{
    const TMT = 7776000; //三个月的总秒数
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 订单列表
     */
    public function index()
    {
        //通道
        $tongdaolist = M("Channel")->field('id,code,title')->select();
        $this->assign("tongdaolist", $tongdaolist);

        $where = array(
            'pay_status' => ['gt', 1],
        );
        $memberid = I("request.memberid");
        if ($memberid) {
            $where['pay_memberid'] = array('eq', $memberid);
        }
        $orderid = I("request.orderid");
        if ($orderid) {
            $where['out_trade_id'] = $orderid;
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['pay_tongdao'] = array('eq', $tongdao);
        }

        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime)  = explode('|', $createtime);
            $where['pay_applydate'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime, $setime)    = explode('|', $successtime);
            $where['pay_successdate'] = ['between', [strtotime($sstime), strtotime($setime) ? strtotime($setime) : time()]];
        } else if (!$successtime && !$createtime) {
            $_GET['successtime']      = date('Y-m-d H:i:s', strtotime(date('Y-m', time()))) . " | " . date('Y-m-d H:i:s', time());
            $where['pay_successdate'] = ['between', [strtotime(date('Y-m', time())), time()]];
        }

        $count = M('Order')->where($where)->count();
        $page  = new Page($count, 15);
        $list  = M('Order')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();

        $amount = $rate = $realmoney = 0;
        foreach ($list as $item) {
            if ($item['pay_status'] >= 1) {
                $amount += $item['pay_amount'];
                $rate += $item['pay_poundage'];
                $realmoney += $item['pay_actualamount'];
            }
        }
        //统计订单信息
        $is_month = true;
        //下单时间
        if ($createtime) {
            $cstartTime = strtotime($cstime);
            $cendTime   = strtotime($cetime) ? strtotime($cetime) : time();
            $is_month   = $cendTime - $cstartTime > self::TMT ? true : false;
        }
        //支付时间
        $pstartTime = strtotime($sstime);
        $pendTime   = strtotime($setime) ? strtotime($setime) : time();
        $is_month   = $pendTime - $pstartTime > self::TMT ? true : false;

        $time       = $successtime ? 'pay_successdate' : 'pay_applydate';
        $dateFormat = $is_month ? '%Y年-%m月' : '%Y年-%m月-%d日';
        $field      = "FROM_UNIXTIME(" . $time . ",'" . $dateFormat . "') AS date,SUM(pay_amount) AS amount,SUM(pay_poundage) AS rate,SUM(pay_actualamount) AS total";
        $_mdata     = M('Order')->field($field)->where($where)->group('date')->select();
        $mdata      = [];
        foreach ($_mdata as $item) {
            $mdata['amount'][] = $item['amount'] ? $item['amount'] : 0;
            $mdata['mdate'][]  = "'" . $item['date'] . "'";
            $mdata['total'][]  = $item['total'] ? $item['total'] : 0;
            $mdata['rate'][]   = $item['rate'] ? $item['rate'] : 0;
        }

        $this->assign("list", $list);
        $this->assign("mdata", $mdata);
        $this->assign('page', $page->show());
        $this->assign('strate', $rate);
        $this->assign('strealmoney', $realmoney);
        $this->assign("isrootadmin", is_rootAdministrator());
        C('TOKEN_ON', false);
        $this->display();
    }
    /**
     * 导出交易订单
     * */
    public function exportorder()
    {

        //通道
        $tongdaolist = M("Channel")->field('id,code,title')->select();
        $this->assign("tongdaolist", $tongdaolist);

        $where = array(
            'pay_status' => ['eq', 2],
        );
        $memberid = I("request.memberid");
        if ($memberid) {
            $where['pay_memberid'] = array('eq', $memberid);
        }
        $orderid = I("request.orderid");
        if ($orderid) {
            $where['out_trade_id'] = $orderid;
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['pay_tongdao'] = array('eq', $tongdao);
        }

        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime)  = explode('|', $createtime);
            $where['pay_applydate'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime, $setime)    = explode('|', $successtime);
            $where['pay_successdate'] = ['between', [strtotime($sstime), strtotime($setime) ? strtotime($setime) : time()]];
        }

        $title = array('订单号', '商户编号', '交易金额', '手续费', '实际金额', '提交时间', '成功时间', '通道', '状态');
        $data  = M('Order')->where($where)->select();

        foreach ($data as $item) {
            $list[] = array(
                'pay_orderid'      => "\t" . $item['pay_orderid'],
                'pay_memberid'     => $item['pay_memberid'],
                'pay_amount'       => $item['pay_amount'],
                'pay_poundage'     => $item['pay_poundage'],
                'pay_actualamount' => $item['pay_actualamount'],
                'pay_applydate'    => date('Y-m-d H:i:s', $item['pay_applydate']),
                'pay_successdate'  => date('Y-m-d H:i:s', $item['pay_successdate']),
                'pay_zh_tongdao'   => $item['pay_zh_tongdao'],
                'pay_status'       => '成功，已返回',
            );
        }

        exportCsv($list, $title);
    }

    /**
     * 统计所有用户的订单表，入金表，代付表，提现表的数据
     * @param array $memberLists [用户表的数据]
     * @return array [$memberLists[处理好的数据]，$allSum[总的数据统计]]
     */
    public function countData($memberLists)
    {

        //所有用户的入金，手续费，代付+提现的总额
        $allSum = [
            'allPoundage'  => 0, //平台总收益
            'amount'       => 0, //订单总额
            'poundage'     => 0, //入金手续费
            'actualamount' => 0, //入金总额
            'tkmoney'      => 0, //代付+提现的总额
            'money'        => 0, //实际代付+提现的总额
            'sxfmoney'     => 0, //代付+提现的手续费总额
            'reward'       => 0, //奖励费
            'orderCost'    => 0, //成本费
            'netProfit'    => 0, //净利润
            'wlCost'       => 0, //代付上游成本费
        ];

        //获取认证用户的id和用户的商户id
        foreach ($memberLists as $k => $v) {
            $memberLists[$k]['groupid'] = $this->groupId[$v['groupid']];
            $userids[]                  = $v['id'];
            $memberids[]                = $v['id'] + 10000;
        }

        //查询 流水表，订单表，提现表，代付表的数据

        //--------代付表---------
        $wttkWhere = ['status' => 2, 'userid' => ['in', $userids]];
        $wttkTime  = I('request.wttk_time', '');
        if ($wttkTime) {
            $wttkTime                = explode('|', $wttkTime);
            $wttkWhere['cldatetime'] = ['between', $wttkTime];
        }
        $Wttklist  = M('Wttklist');
        $wttkLists = $Wttklist->where($wttkWhere)->select();
        $wttkField = ['sum(`tkmoney`) wl_tkmoney', 'sum(`sxfmoney`) wl_sxfmoney', 'sum(`money`) wl_money', 'sum(`cost`) wl_cost'];
        $wttkSum   = $Wttklist->field($wttkField)->where(['status' => 2])->find();

        //---------提现表---------
        $tkWhere = ['status' => 2, 'userid' => ['in', $userids]];
        $tkTime  = I('request.tk_time', '');
        if ($tkTime) {
            $tkTime                = explode('|', $tkTime);
            $tkWhere['cldatetime'] = ['between', $tkTime];
        }

        $Tklist  = M('Tklist');
        $tkLists = $Tklist->where($wttkWhere)->select();
        $tkField = ['sum(`tkmoney`) tl_tkmoney', 'sum(`sxfmoney`) tl_sxfmoney', 'sum(`money`) tl_money'];
        $tkSum   = $Tklist->field($tkField)->where(['status' => 2])->find();

        //-----------订单表-------------
        $orderWhere = ['pay_memberid' => ['in', $memberids], 'pay_status' => ['between', [1, 2]]];
        $orderTime  = I('request.order_time', '');
        if ($orderTime) {
            $orderTime                     = explode('|', $orderTime);
            $orderTime[0]                  = strtotime($orderTime[0]);
            $orderTime[1]                  = strtotime($orderTime[1]);
            $orderWhere['pay_successdate'] = ['between', $orderTime];
        }

        $Order      = M('Order');
        $orderLists = $Order->where($orderWhere)->select();
        $orderField = ['sum(`pay_amount`) amount, sum(`pay_poundage`) poundage, sum(`pay_actualamount`) actualamount, sum(`cost`) cost'];
        $orderSum   = $Order->field($orderField)->where(['pay_status' => ['between', [1, 2]]])->find();


        //----------流水表---------------
        $Moneychange = M('Moneychange');
        $moneyLists  = $Moneychange->where(['userid' => ['in', $userids]])->select();
        $reward      = $Moneychange->where(['lx' => 9])->sum('money');

        //-----------总计---------------
        $allSum['amount']       = bcadd($allSum['amount'], $orderSum['amount'], 2); //订单总额
        $allSum['poundage']     = bcadd($allSum['poundage'], $orderSum['poundage'], 2); //入金手续费
        $allSum['actualamount'] = bcadd($allSum['actualamount'], $orderSum['actualamount'], 2); //入金总额
        $allSum['tkmoney']      = bcadd($tkSum['tl_tkmoney'], $wttkSum['wl_tkmoney'], 2); //代付+提现的总额
        $allSum['money']        = bcadd($tkSum['tl_money'], $wttkSum['wl_money'], 2); //实际代付+提现的总额
        $allSum['sxfmoney']     = bcadd($tkSum['tl_sxfmoney'], $wttkSum['wl_sxfmoney'], 2); //代付+提现的手续费总额
        $allSum['allPoundage']  = bcadd($allSum['sxfmoney'], $allSum['poundage'], 2); //平台总收益
        $allSum['orderCost']    += $orderSum['cost']; //上游成本费
        $allSum['wlCost']       += $wttkSum['wl_cost']; //代付成本费
        $allSum['reward']       = $reward; //奖励费

        //计算净利润
        $netProfit           = bcsub($allSum['allPoundage'], $allSum['reward'], 2);
        $netProfit           = bcsub($netProfit, $allSum['orderCost'], 2);
        $allSum['netProfit'] = bcsub($netProfit, $allSum['wlCost'], 2);

        //统计每个用户的流水，入金，提现，代付等数据
        foreach ($memberLists as $k => $v) {
            $memberid                       = $v['id'] + 10000;
            $memberLists[$k]['memberid']    = $memberid;
            $memberLists[$k]['all_balance'] = bcadd($v['balance'], $v['blockedbalance'], 2);

            $sum = [
                'wl_tkmoney'       => 0, //代付的金额
                'wl_sxfmoney'      => 0, //代付的手续费
                'wl_money'         => 0, //代付的实际金额
                'tl_tkmoney'       => 0, //提现的金额
                'tl_sxfmoney'      => 0, //提现的手续费
                'tl_money'         => 0, //提现的设计金额
                'tkmoney'          => 0, //代付+提现 金额
                'sxfmoney'         => 0, //代付+提现 手续费
                'money'            => 0, //代付+提现 实际金额
                'pay_amount'       => 0, //订单的金额
                'pay_poundage'     => 0, //订单的手续费
                'pay_actualamount' => 0, //订单入金总额
                'order_cost'       => 0, //成本费
                'wl_cost'          => 0, //代付成本费
                'lx1'              => 0, //用户的入金总额
                'lx3'              => 0, //手动增加的
                'lx4'              => 0, //手动减少的
                'lx9'              => 0, //奖励
                'pay_count'        => 0, //支付成功的笔数
                'wttk_count'       => 0, //代付笔数
                'tk_count'         => 0, //提现笔数
            ];

            //循环统计查询的代付的数据
            foreach ($wttkLists as $k1 => $v1) {
                if ($v1['userid'] == $v['id']) {
                    $sum['wl_tkmoney']  = bcadd($v1['tkmoney'], $sum['wl_tkmoney'], 2);
                    $sum['wl_money']    = bcadd($v1['money'], $sum['wl_money'], 2);
                    $sum['wl_sxfmoney'] = bcadd($v1['sxfmoney'], $sum['wl_sxfmoney'], 2);
                    $sum['wl_cost']     = bcadd($v1['cost'], $sum['wl_cost'], 2);
                    $sum['wttk_count']++;
                }
            }

            //统计提现的数据
            foreach ($tkLists as $k1 => $v1) {
                if ($v1['userid'] == $v['id']) {
                    $sum['tl_tkmoney']  = bcadd($v1['tkmoney'], $sum['tl_tkmoney'], 2);
                    $sum['tl_money']    = bcadd($v1['money'], $sum['tl_money'], 2);
                    $sum['tl_sxfmoney'] = bcadd($v1['sxfmoney'], $sum['tl_sxfmoney'], 2);
                    $sum['tk_count']++;
                }
            }

            //统计订单的数据
            foreach ($orderLists as $k1 => $v1) {
                if ($v1['pay_memberid'] == $memberid) {
                    $sum['pay_amount']       = bcadd($v1['pay_amount'], $sum['pay_amount'], 2);
                    $sum['pay_poundage']     = bcadd($v1['pay_poundage'], $sum['pay_poundage'], 2);
                    $sum['pay_actualamount'] = bcadd($v1['pay_actualamount'], $sum['pay_actualamount'], 2);
                    $sum['order_cost']       = bcadd($v1['cost'], $sum['order_cost'], 2);
                    $sum['pay_count']++;
                }
            }

            //统计流水账单的数据
            foreach ($moneyLists as $k1 => $v1) {
                if ($v1['userid'] == $v['id']) {
                    switch ($v1['lx']) {
                        case '1':
                            $sum['lx1'] = bcadd($v1['money'], $sum['lx1'], 2);
                            break;
                        case '3':
                            $sum['lx3'] = bcadd($v1['money'], $sum['lx3'], 2);
                            break;
                        case '4':
                            $sum['lx4'] = bcadd($v1['money'], $sum['lx4'], 2);
                            break;
                        case '9':
                            $sum['lx9'] = bcadd($v1['money'], $sum['lx9'], 2);
                            break;
                    }
                }
            }
            //计算每个用户的代付+提现的数据
            $sum['money']        = bcadd($sum['tl_money'], $sum['wl_money'], 2);
            $sum['sxfmoney']     = bcadd($sum['tl_sxfmoney'], $sum['wl_sxfmoney'], 2);
            $sum['tkmoney']      = bcadd($sum['tl_tkmoney'], $sum['wl_tkmoney'], 2);
            $sum['all_poundage'] = bcadd($sum['sxfmoney'], $sum['pay_poundage'], 2);

            $memberLists[$k] = array_merge($memberLists[$k], $sum);
        }
       
        return [$memberLists, $allSum];
    }

    public function userAnalysis()
    {
        //@todo
        exit("暂不运行, 数据量太大会导致服务器崩溃");

        //查询所有的认证的用户
        $memberid = I('request.memberid', '');
        if ($memberid) {
            $where['id'] = $memberid - 10000;
        }
        $where['authorized'] = '1';
        $Member              = M('Member');
        $count               = $Member->where($where)->count();
        $Page                = new Page($count, 15);
        $memberLists         = $Member->field(['id', 'username', 'groupid', 'balance', 'blockedbalance'])->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $page                = $Page->show();
        $export              = U('Admin/Statistics/exportUserAnalysis') . '?memberid=' . $_GET['memberid'] . '&order_time=' . $_GET['order_time'] . '&wtkk_time=' . $_GET['wtkk_time'] . '&tk_time=' . $_GET['tk_time'];
        if ($memberLists) {
            list($memberLists, $allSum) = $this->countData($memberLists);
            $this->assign('export', $export);
            $this->assign($allSum);
            $this->assign('lists', $memberLists);
            $this->assign('page', $page);
        }
        $this->display();
    }

    public function details()
    {
        /**
         *用户每一个入金渠道的总额，
         *代付渠道的总额
         */

        $id = I('request.id', '');

        if ($id) {

            //所有用户的入金，手续费，代付+提现的总额
            $allPoundage  = 0; //平台总收益
            $amount       = 0; //订单总额
            $poundage     = 0; //入金手续费
            $actualamount = 0; //入金总额
            $tkmoney      = 0; //代付+提现的总额
            $money        = 0; //实际代付+提现的总额
            $sxfmoney     = 0; //代付+提现的手续费总额
            $orderCost    = 0; //订单成本
            $wlCost       = 0; //代付成本

            $memberList               = M('Member')->where(['id' => $id])->find();
            $memberList['allbalance'] = bcadd($memberList['blockedbalance'], $memberList['balance'], 2);
            $memberList['groupid']    = $this->groupId[$memberList['groupid']];

            //查询订单表的数据
            $memberList['memberid'] = $memberid = $id + 10000;
            $Order                  = M('Order');
            $orderField             = [
                'sum(`pay_amount`) amount',
                'sum(`pay_poundage`) poundage',
                'sum(`pay_actualamount`) actualamount',
                'sum(`cost`) cost',
                'pay_zh_tongdao',
                'pay_tongdao',
            ];

            $orderWhere = ['pay_memberid' => $memberid, 'pay_status' => ['between', [1, 2]]];
            $orderTime  = I('request.order_time', '');
            if ($orderTime) {
                $orderTime                     = explode('|', $orderTime);
                $orderTime[0]                  = strtotime($orderTime[0]);
                $orderTime[1]                  = strtotime($orderTime[1]);
                $orderWhere['pay_successdate'] = ['between', $orderTime];
            }
            //获取总的订单数据
            $orderLists = $Order->field($orderField)->where($orderWhere)->group('pay_tongdao')->select();
            foreach ($orderLists as $k => $v) {
                $amount       = bcadd($amount, $v['amount'], 2);
                $poundage     = bcadd($poundage, $v['poundage'], 2);
                $actualamount = bcadd($actualamount, $v['actualamount'], 2);
                $orderCost    = bcadd($orderCost, $v['cost'], 2);
            }

            //查询代付表的数据
            $wttkWhere = ['status' => 2, 'userid' => $id];
            $wttkTime  = I('request.wttk_time', '');
            if ($wttkTime) {
                $wttkTime                = explode('|', $wttkTime);
                $wttkWhere['cldatetime'] = ['between', $wttkTime];
            }
            $Wttklist  = M('Wttklist');
            $wttkField = [
                'sum(`tkmoney`) wl_tkmoney',
                'sum(`sxfmoney`) wl_sxfmoney',
                'sum(`money`) wl_money',
                'sum(`cost`) wl_cost',
                'df_name',
                'code',
            ];
            $wttkLists = $Wttklist->field($wttkField)->where($wttkWhere)->group('df_id')->select();
            //获取代付表总的数据
            foreach ($wttkLists as $k => $v) {
                $tkmoney  = bcadd($tkmoney, $v['wl_tkmoney'], 2);
                $sxfmoney = bcadd($sxfmoney, $v['wl_sxfmoney'], 2);
                $money    = bcadd($money, $v['wl_money'], 2);
                $wlCost   = bcadd($wlCost, $v['wl_cost'], 2);
            }

            //查询提现表的数据
            $tkWhere = ['status' => 2, 'userid' => $id];
            $tkTime  = I('request.tk_time', '');
            if ($tkTime) {
                $tkTime                = explode('|', $tkTime);
                $tkWhere['cldatetime'] = ['between', $tkTime];
            }
            $Tklist                                 = M('Tklist');
            $tkField                                = ['sum(`tkmoney`) tl_tkmoney', 'sum(`sxfmoney`) tl_sxfmoney', 'sum(`money`) tl_money'];
            $tkList                                 = $Tklist->field($tkField)->where($tkWhere)->find();
            empty($tkList['tl_tkmoney']) && $tkList = null;

            //获取总提现+代付的数据
            $tkmoney  = bcadd($tkmoney, $tkList['tl_tkmoney'], 2);
            $sxfmoney = bcadd($sxfmoney, $tkList['tl_sxfmoney'], 2);
            $money    = bcadd($money, $tkList['tl_money'], 2);

            //查询流水表的数据
            $Moneychange = M('Moneychange');
            $moneyWhere  = ['userid' => $id];
            $moneyLists  = $Moneychange->where($moneyWhere)->select();
            //处理订单表的数据
            $lx = ['lx1' => 0, 'lx3' => 0, 'lx4' => 0, 'lx9' => 0];
            foreach ($moneyLists as $k => $v) {
                $keyname = 'lx' . $v['lx'];
                $lx[$keyname] += $v['money'];
            }
            $allPoundage = $sxfmoney + $poundage;
            $netProfit   = $allPoundage - $orderCost - $wlCost - $lx['lx9'];
            $this->assign('amount', $amount);
            $this->assign('allPoundage', $allPoundage);
            $this->assign('actualamount', $actualamount);
            $this->assign('tkmoney', $tkmoney);
            $this->assign('money', $money);
            $this->assign('sxfmoney', $sxfmoney);
            $this->assign('orderLists', $orderLists);
            $this->assign('wttkLists', $wttkLists);
            $this->assign('tkLists', $tkLists);
            $this->assign('netProfit', $netProfit);
            $this->assign('orderCost', $orderCost);
            $this->assign('wlCost', $wlCost);
            $this->assign('lx', $lx);
            $this->assign('memberList', $memberList);
        }
        $this->display();
    }

    //导出所有认证的用户的数据
    public function exportUserAnalysis()
    {

        //查询所有的认证的用户
        $memberid = I('request.memberid', '');
        if ($memberid) {
            $where['id'] = $memberid - 10000;
        }

        $where['authorized']        = 1;
        $memberLists                = M('Member')->where($where)->select();
        list($memberLists, $allSum) = $this->countData($memberLists);
        $title                      = ['商户号', '总资金', '订单总额', '订单入金总额', '提现总额', '实际提现总额', '代付总额', '实际代付总额', '代付+提现总额', '代付+提现实际总额', '平台总收益'];
        $lists                      = [];

        foreach ($memberLists as $k => $v) {
            $lists[] = [
                'memberid'         => $v['memberid'],
                'all_balance'      => $v['all_balance'],
                'pay_amount'       => $v['pay_amount'],
                'pay_actualamount' => $v['pay_actualamount'],
                'tl_tkmoney'       => $v['tl_tkmoney'],
                'tl_money'         => $v['tl_money'],
                'wl_tkmoney'       => $v['wl_tkmoney'],
                'wl_money'         => $v['wl_money'],
                'tkmoney'          => $v['tkmoney'],
                'money'            => $v['money'],
                'all_poundage'     => $v['all_poundage'],
            ];
        }

        exportCsv($lists, $title);

    }
}
