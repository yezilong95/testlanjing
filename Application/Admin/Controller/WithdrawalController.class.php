<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-04-02
 * Time: 23:01
 */
namespace Admin\Controller;

use Common\Model\MoneychangeModel;
use Common\Model\WttklistModel;
use Think\Page;

/**
 * 提现控制器
 * Class WithdrawalController
 * @package Admin\Controller
 */
class WithdrawalController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 提款记录
     */
    public function index()
    {
        //通道
        $banklist = M("Product")->field('id,name,code')->select();
        $this->assign("banklist", $banklist);

        $where = array();
        $memberid = I("get.memberid");
        if ((intval($memberid) - 10000)>0) {
            $where['userid'] = array('eq',$memberid-10000);
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['payapiid'] = array('eq',$tongdao);
        }
        $T = I("request.T");
        if ($T != "") {
            $where['t'] = array('eq',$T);
        }
        $status = I("request.status",0,'intval');
        if ($status) {
            $where['status'] = array('eq',$status);
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime,$cetime) = explode('|',$createtime);
            $where['sqdatetime'] = ['between',[$cstime,$cetime?$cetime:date('Y-m-d')]];
        }
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime,$setime) = explode('|',$successtime);
            $where['cldatetime'] = ['between',[$sstime,$setime?$setime:date('Y-m-d')]];
        }
        $count = M('Tklist')->where($where)->count();
        $size  = 15;
        $rows  = I('get.rows', $size);
        if (!$rows) {
            $rows = $size;
        }
        $page = new Page($count, $rows);
        $list = M('Tklist')
            ->where($where)
            ->limit($page->firstRow.','.$page->listRows)
            ->order('id desc')
            ->select();
			
		//查询提款金额合计
        $countWhere = $where;
        $field = ['sum(`tkmoney`) tkmoney'];
        $sum = M('Tklist')->field($field)->where($countWhere)->find();
		foreach($sum as $k => $v){
            $sum[$k] += 0;
        }
		
		$this->assign('tkmoneysum',$sum['tkmoney']);
		
        $this->assign("list", $list);
        $this->assign("page", $page->show());
        C('TOKEN_ON',false);
        $this->display();
    }

    /**
     * 提款设置
     */
    public function setting()
    {
        $configs = M("Tikuanconfig")->where("issystem=1")->find();
        $this->assign("tikuanconfiglist", $configs);

        //排除日期
        $holiday = M('Tikuanholiday')->select();
        $this->assign("configs", $configs);
        $this->assign("holidays", $holiday);
        $this->display();
    }

    /**
     * 保存系统提款设置
     */
    public function saveWithdrawal()
    {
        if(IS_POST){
            $uid = session('admin_auth')['uid'];
            $id = I('post.id',0,'intval') ? I('post.id',0,'intval') : 0;

            $_rows = I('post.u');
            $_rows['userid'] = $uid;
            if($id ){
                addSyslog('保存系统提款设置: id='.$id.', 数据='.json_encode($_rows));
                $TikuanconfigM = M("Tikuanconfig");
                $res = $TikuanconfigM->where(['id'=>$id])->save($_rows);
                addSyslog('保存系统提款设置sql: '.$TikuanconfigM->getLastSql());
            }else{
                $res = M("Tikuanconfig")->add($_rows);

            }
            $this->ajaxReturn(['status'=>$res]);
        }
    }

    /**
     * 编辑提款时间
     */
    public function settimeEdit()
    {
        if(IS_POST){
            $id = I('post.id',0,'intval');
            $rows = I('post.u');
            if($id){
                $res = M('Tikuanconfig')->where(['id'=>$id,'issystem'=>1])->save($rows);
            }
            $this->ajaxReturn(['status'=>$res]);
        }
    }

    public function addHoliday()
    {
        if(IS_POST){
            $datetime = I("post.datetime");
            if($datetime){
                $count = M('Tikuanholiday')->where(['datetime'=>strtotime($datetime)])->count();
                if($count){
                    $this->ajaxReturn(['status'=>0,'msg'=>$datetime.'已存在!']);
                }
                $res = M('Tikuanholiday')->add(['datetime'=>strtotime($datetime)]);
                $this->ajaxReturn(['status'=>$res]);
            }
        }
    }

    public function tjjjradd()
    {
        $datetime = I("post.datetime", "");
        $shuoming = I("post.shuoming", "");
        if ($datetime == "") {
            exit("a");
        } else {
            $Tjjjr = M("Tjjjr");
            $count = $Tjjjr->where("websiteid=" . session("admin_websiteid") . " and datetime = '" . $datetime . "'")->count();
            if ($count > 0) {
                exit("b");
            } else {
                $data["websiteid"] = session("admin_websiteid");
                $data["datetime"] = $datetime;
                $data["shuoming"] = $shuoming;
                $id = $Tjjjr->add($data);
                exit($id);
            }
        }
    }

    public function delHoliday()
    {
        if(IS_POST){
            $id = I("post.id",0,'intval');
            if ($id) {
                $res = M('Tikuanholiday')->where("id=" . $id)->delete();
                $this->ajaxReturn(['status'=>$res]);
            }
        }
    }

    public function tjjjrdel()
    {
        $id = I("post.id", "");
        if ($id == "") {
            exit("no");
        } else {
            $Tjjjr = M("Tjjjr");
            $Tjjjr->where("id=" . $id)->delete();
            exit("ok");
        }
    }


    /**
     * 导出提款记录
     */
    public function exportorder()
    {
        $where = array();
        $memberid = I("get.memberid");
        if ($memberid) {
            $where['userid'] = array('eq',$memberid-10000);
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['payapiid'] = array('eq',$tongdao);
        }
        $T = I("request.T");
        if ($T != "") {
            $where['t'] = array('eq',$T);
        }
        $status = I("request.status",0,'intval');
        if ($status) {
            $where['status'] = array('eq',$status);
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime,$cetime) = explode('|',$createtime);
            $where['sqdatetime'] = ['between',[$cstime,$cetime?$cetime:date('Y-m-d')]];
        }
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime,$setime) = explode('|',$successtime);
            $where['cldatetime'] = ['between',[$sstime,$setime?$setime:date('Y-m-d')]];
        }

        $title = array('类型','商户编号','结算金额','手续费','到账金额','银行名称','支行名称','银行卡号','开户名','所属省','所属市','申请时间','处理时间','状态',"备注");
        $data = M('Tklist')->where($where)->select();

        foreach ($data as $item){
            switch ($item['status']){
                case 0:
                    $status = '未处理';
                    break;
                case 1:
                    $status = '处理中';
                    break;
                case 2:
                    $status = '已打款';
                    break;
                case 3:
                    $status="已驳回";
                    break;
            }
            switch ($item['t']){
                case 0:
                    $tstr = 'T + 0';
                    break;
                case 1:
                    $tstr = 'T + 1';
                    break;
            }

            $list[] = array(
                't'=>$tstr,
                'memberid'=>$item['userid']+10000,
                'tkmoney'=>$item['tkmoney'],
                'sxfmoney'=>$item['sxfmoney'],
                'money'=>$item['money'],
                'bankname'=>$item['bankname'],
                'bankzhiname'=>$item['bankzhiname'],
                'banknumber'=>"\t".$item['banknumber'],
                'bankfullname'=>$item['bankfullname'],
                'sheng'=>$item['sheng'],
                'shi'=>$item['shi'],
                'sqdatetime'=>"\t".$item['sqdatetime'],
                'cldatetime'=>"\t".$item['cldatetime'],
                'status'=>$status,
                "memo"=>$item["memo"]
            );
        }
        exportCsv($list,$title);
    }

    /**
     * 代付记录
     */
    public function payment()
    {
        //通道
        $banklist = M("Product")->field('id,name,code')->select();
        $this->assign("banklist", $banklist);

        $where = array();
        $memberid = I("get.memberid");
        if ((intval($memberid) - 10000)>0) {
            $where['userid'] = array('eq',$memberid-10000);
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['payapiid'] = array('eq',$tongdao);
        }
        $T = I("request.T");
        if ($T != "") {
            $where['t'] = array('eq',$T);
        }
        $status = I("request.status",'');
        if ($status<>'') {
            $where['status'] = array('eq',$status);
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime,$cetime) = explode('|',$createtime);
            $where['sqdatetime'] = ['between',[$cstime,$cetime?$cetime:date('Y-m-d')]];
        }
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime,$setime) = explode('|',$successtime);
            $where['cldatetime'] = ['between',[$sstime,$setime?$setime:date('Y-m-d')]];
        }
        $count = M('Wttklist')->where($where)->count();
        $size  = 15;
        $rows  = I('get.rows', $size);
        if (!$rows) {
            $rows = $size;
        }
        $page = new Page($count, $rows);
        $list = M('Wttklist')
            ->where($where)
            ->limit($page->firstRow.','.$page->listRows)
            ->order('id desc')
            ->select();

        $pfa_lists = M('PayForAnother')->where(['status'=>1])->select();
		
		//查询提款金额合计
        $countWhere = $where;
        $status == "" ?  $countWhere['status'] = array('in',array('1','2')) : $countWhere['status'] =$status;
        $field = ['sum(`tkmoney`) tkmoney','sum(`sxfmoney`) sxfmoney'];
        $sum = M('Wttklist')->field($field)->where($countWhere)->find();
		foreach($sum as $k => $v){
            $sum[$k] += 0;
        }

		$this->assign('tkmoneysum',formatMoneyZh($sum['tkmoney']));
		$this->assign('sxfmoney',formatMoneyZh($sum['sxfmoney']));
        $this->assign("pfa_lists", $pfa_lists);
        $this->assign("list", $list);
        $this->assign("page", $page->show());
        C('TOKEN_ON',false);
        $this->display();
    }

    //导出委托提款记录
    public function exportweituo()
    {
        $where = array();
        $memberid = I("get.memberid");
        if ($memberid) {
            $where['userid'] = array('eq',$memberid-10000);
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['payapiid'] = array('eq',$tongdao);
        }
        $T = I("request.T");
        if ($T != "") {
            $where['t'] = array('eq',$T);
        }
        $status = I("request.status",0,'intval');
        if ($status) {
            $where['status'] = array('eq',$status);
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime,$cetime) = explode('|',$createtime);
            $where['sqdatetime'] = ['between',[$cstime,$cetime?$cetime:date('Y-m-d')]];
        }
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime,$setime) = explode('|',$successtime);
            $where['cldatetime'] = ['between',[$sstime,$setime?$setime:date('Y-m-d')]];
        }

        $title = array('类型','商户编号','结算金额','手续费','到账金额','银行名称','支行名称','银行卡号','开户名','所属省','所属市','申请时间','处理时间','状态',"备注");
        $data = M('Wttklist')->where($where)->select();

        foreach ($data as $item){
            switch ($item['status']){
                case 0:
                    $status = '未处理';
                    break;
                case 1:
                    $status = '处理中';
                    break;
                case 2:
                    $status = '已打款';
                    break;
                case 3:
                    $status="已驳回";
                    break;
            }
            switch ($item['t']){
                case 0:
                    $tstr = 'T + 0';
                    break;
                case 1:
                    $tstr = 'T + 1';
                    break;
            }

            $list[] = array(
                't'=>$tstr,
                'memberid'=>$item['userid']+10000,
                'tkmoney'=>$item['tkmoney'],
                'sxfmoney'=>$item['sxfmoney'],
                'money'=>$item['money'],
                'bankname'=>$item['bankname'],
                'bankzhiname'=>$item['bankzhiname'],
                'banknumber'=>"\t".$item['banknumber'],
                'bankfullname'=>$item['bankfullname'],
                'sheng'=>$item['sheng'],
                'shi'=>$item['shi'],
                'sqdatetime'=>"\t".$item['sqdatetime'],
                'cldatetime'=>"\t".$item['cldatetime'],
                'status'=>$status,
                "memo"=>$item["memo"]
            );
        }
        exportCsv($list,$title);
    }
    
    public function dftikuanlist()
    {
        $Payapi = M("Payapi");
        $tongdaolist = $Payapi->select();
        $this->assign("tongdaolist", $tongdaolist); // 通道列表
    
        $Systembank = M("Systembank");
        $banklist = $Systembank->select();
        $this->assign("banklist", $banklist); // 银行列表
    
        $where = array();
        $memberid = I("get.memberid");
        $i = 0;
        if ($memberid) {
            $where[$i] = "userid = " . ($memberid - 10000);
            $i ++;
        }
    
        $tongdao = I("get.tongdao");
        if ($tongdao) {
            $where[$i] = "payapiid = " . $tongdao;
            $i ++;
        }
    
        $bank = I("get.bank");
        if ($bank) {
            $where[$i] = "bankname = '" . $bank . "'";
            $i ++;
        }
    
        $T = I("get.T", "");
        if ($T != "") {
            $where[$i] = "t = " . $T;
            $i ++;
        }
    
        $status = I("get.status");
        if ($status) {
            $where[$i] = "status = " . $status;
            $i ++;
        }
    
        $tjdate_ks = I("get.tjdate_ks");
        if ($tjdate_ks) {
            $where[$i] = " DATEDIFF('" . $tjdate_ks . "',sqdatetime) <= 0";
            ;
            $i ++;
        }
    
        $tjdate_js = I("get.tjdate_js");
        if ($tjdate_js) {
            $where[$i] = " DATEDIFF('" . $tjdate_js . "',sqdatetime) >= 0";
            ;
            $i ++;
        }
    
        $cgdate_ks = I("get.cgdate_ks");
        if ($cgdate_ks) {
            $where[$i] = " DATEDIFF('" . $cgdate_ks . "',cldatetime) <= 0";
            ;
            $i ++;
        }
    
        $cgdate_js = I("get.cgdate_js");
        if ($cgdate_js) {
            $where[$i] = " DATEDIFF('" . $cgdate_js . "',cldatetime) >= 0";
            ;
            $i ++;
        }
    
        $list = $this->lists("dflist", $where);
        $this->assign("list", $list);
        $this->display();
    }


    //代付结算
    public function editStatus()
    {
        $id = I("request.id", 0, 'intval');
        if (IS_POST) {
            $status  = I("post.status", 0, 'intval');
            $userid  = I('post.userid', 0, 'intval');
            $tkmoney = I('post.tkmoney');
            if (!$id) {
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败']);
            }
            $map['id'] = $id;
            //开启事务
            M()->startTrans();
            $Tklist = M("Tklist");
            $map['id'] = $id;
            $withdraw = $Tklist->where($map)->lock(true)->find();
            if(empty($withdraw)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '提款申请不存在']);
            }
            $data           = [];
            $data["status"] = $status;

            //判断状态
            switch ($status) {
                case '2':
                    $data["cldatetime"] = date("Y-m-d H:i:s");
                    break;
                case '3':
//                    if($withdraw['status'] == 1){
//                        $this->ajaxReturn(['status' => 0, 'msg' => '提款申请处理中，不能驳回']);
//                    }
                    if($withdraw['status'] == 2) {
                        $this->ajaxReturn(['status' => 0, 'msg' => '提款申请已打款，不能驳回']);
                    } elseif($withdraw['status'] == 3) {
                        $this->ajaxReturn(['status' => 0, 'msg' => '提款申请已驳回，不能驳回']);
                    }
                    //驳回操作
                    //1,将金额返回给商户
                    $Member     = M('Member');
                    $memberInfo = $Member->where(['id' => $userid])->lock(true)->find();
                    $res        = $Member->where(['id' => $userid])->save(['balance' => array('exp', "balance+{$tkmoney}")]);
                    if (!$res) {
                        M()->rollback();
                        $this->ajaxReturn(['status' => 0]);
                    }
                    //2,记录流水订单号
                    $arrayField = array(
                        "userid"     => $userid,
                        "ymoney"     => $memberInfo['balance'],
                        "money"      => $tkmoney,
                        "gmoney"     => $memberInfo['balance'] + $tkmoney,
                        "datetime"   => date("Y-m-d H:i:s"),
                        "tongdao"    => 0,
                        "transid"    => MoneychangeModel::genTransId(),
                        "orderid"    => $withdraw['orderid'],
                        "lx"         => 11,
                        'contentstr' => '结算驳回',
                    );
                    $res = M('Moneychange')->add($arrayField);
                    if (!$res) {
                        M()->rollback();
                        $this->ajaxReturn(['status' => 0]);
                    }
                    $data["cldatetime"] = date("Y-m-d H:i:s");
                    $data["memo"] = I('post.memo');
                    break;
                default:
                    # code...
                    break;
            }
            //修改结算的数据
            $res    = $Tklist->where($map)->save($data);
            if ($res) {
                M()->commit();
                $this->ajaxReturn(['status' => $res]);
            }

            M()->rollback();
            $this->ajaxReturn(['status' => 0]);

        } else {
            $info = M('Tklist')->where(['id' => $id])->find();
            $this->assign('info', $info);
            $this->display();
        }
    }

    /**
     *  委托提现
     */
    public function editwtStatus()
    {
        $id = I("request.id", 0, 'intval');
        if (IS_POST) {
            $status  = I("post.status", 0, 'intval');
            $userid  = I('post.userid', 0, 'intval');
            $memo    = I('post.memo', '');
            $tkmoney = I('post.tkmoney');

            if (!$id) {
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败']);
            }
            //开启事务
            M()->startTrans();
            $Wttklist = M("Wttklist");
            $map['id'] = $id;
            $withdraw = $Wttklist->where($map)->lock(true)->find();
            if(empty($withdraw)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '提款申请不存在']);
            }
            $data           = [];
            $data["status"] = $status;
            $data["memo"] = $memo;
            //判断状态
            switch ($status) {
                case '2':
                    $data["cldatetime"] = date("Y-m-d H:i:s");
                    break;
                case '3':
//                    if($withdraw['status'] == 1){
//                        $this->ajaxReturn(['status' => 0, 'msg' => '提款申请处理中，不能驳回']);
//                    }
                    if($withdraw['status'] == 2) {
                        $this->ajaxReturn(['status' => 0, 'msg' => '提款申请已打款，不能驳回']);
                    } elseif($withdraw['status'] == 3) {
                        $this->ajaxReturn(['status' => 0, 'msg' => '提款申请已驳回，不能驳回']);
                    }
                    //驳回操作
                    //1,将金额返回给商户
                    $Member     = M('Member');
                    $memberInfo = $Member->where(['id' => $userid])->lock(true)->find();
                    $res        = $Member->where(['id' => $userid])->save(['balance' => array('exp', "balance+{$tkmoney}")]);

                    if (!$res) {
                        M()->rollback();
                        $this->ajaxReturn(['status' => 0, 'msg'=>'金额返回给商户失败，回退所有操作']);
                    }

                    //2,记录流水订单号
                    $arrayField = array(
                        "userid"     => $userid,
                        "ymoney"     => $memberInfo['balance'],
                        "money"      => $tkmoney,
                        "gmoney"     => $memberInfo['balance'] + $tkmoney,
                        "datetime"   => date("Y-m-d H:i:s"),
                        "tongdao"    => 0,
                        "transid"    => MoneychangeModel::genTransId(),
                        "orderid"    => $withdraw['orderid'],
                        "lx"         => 12,
                        'contentstr' => '代付驳回',
                    );
                    $res = M('Moneychange')->add($arrayField);

                    if (!$res) {
                        M()->rollback();
                        $this->ajaxReturn(['status' => 0, 'msg'=>'添加资金流水，回退所有操作']);
                    }
                    $data["cldatetime"] = date("Y-m-d H:i:s");
                    break;
                default:
                    # code...
                    break;
            }

            $res      = $Wttklist->where($map)->save($data);
            if ($res) {
                M()->commit();
                $this->ajaxReturn(['status' => $res, '操作成功']);
            }

            M()->rollback();
            $this->ajaxReturn(['status' => 0, 'msg'=>'操作失败，回退所有操作']);

        } else {
            $info = M('Wttklist')->where(['id' => $id])->find();
            $this->assign('info', $info);
            $this->display();
        }
    }
    /**
     *  批量委托提现
     */
    public function editwtAllStatus()
    {

        $ids    = I('post.id', '');
        $ids    = explode(',', trim($ids, ','));
        $status = I('post.status', '0');

        if ($status == 3) {
            $this->ajaxReturn(['status' => 0, 'msg' => '无法一键驳回！']);
        }

        $Tklist = M("Tklist");
        $Tklist->startTrans();
        foreach ($ids as $k => $v) {
            if (intval($v)) {
                $data = [
                    "status"     => $status,
                    'cldatetime' => date("Y-m-d H:i:s"),
                ];

                $res = $Tklist->where(['id' => $v])->save($data);
                if ($res === false) {
                    $Tklist->rollback();
                    $this->ajaxReturn(['status' => 0, 'msg' => '操作失败']);
                }
            }
        }
        $Tklist->commit();
        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功!']);

    }
    //提现语音提现
    public function checkNotice()
    {
        //提款记录
        $tikuan = M('Tklist')->where(['status' => 0])->count();
        //委托提款
        $wttikuan = M('Wttklist')->where(['status' => 0])->count();
        $this->ajaxReturn(['errorno' => 0, 'num' => ($tikuan + $wttikuan)]);
    }
}
?>