<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace User\Controller;
use Common\Model\MemberModel;
use Think\Page;

/** 商家代理控制器
 * Class DailiController
 * @package User\Controller
 */
class AgentController extends UserController
{

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 邀请码
     */
    public function invitecode()
    {
        $invitecode = I("get.invitecode");
        $syusername = I("get.syusername");
        $status = I("get.status");
        if (!empty($invitecode)) {
            $where['invitecode'] = ["like","%" . $invitecode . "%"];
        }
        if (!empty($syusername)) {
            $syusernameid = M("Member")->where("username = '" . $syusername . "'")->getField("id");
            $where['syusernameid'] = $syusernameid;
        }
        $regdatetime = urldecode(I("request.regdatetime"));
        if ($regdatetime) {
            list($cstime,$cetime) = explode('|',$regdatetime);
            $where['fbdatetime'] = ['between',[strtotime($cstime),strtotime($cetime)?strtotime($cetime):time()]];
        }
        if (!empty($status)) {
            $where['status'] = $status;
        }
        $where['fmusernameid'] = $this->fans['uid'];
        $count = M('Invitecode')->where($where)->count();
        $page = new Page($count,10);
        $list = M('Invitecode')
            ->where($where)
            ->limit($page->firstRow.','.$page->listRows)
            ->order('id desc')
            ->select();
        foreach($list as $k => $v){
            $list[$k]['groupname'] = $this->groupId[$v['regtype']];
        }

        $this->assign("list", $list);
        $this->assign('page',$page->show());
        //取消令牌
        C('TOKEN_ON',false);
        $this->display();
    }

    /**
     * 添加邀请码
     */
    public function addInvite()
    {
        $invitecode = $this->createInvitecode();
        $this->assign('invitecode',$invitecode);
        $this->assign('datetime',date('Y-m-d H:i:s',time()+86400));
        $this->display();
    }

    /**
     * 邀请码
     * @return string
     */
    private function createInvitecode()
    {
        $invitecodestr = random_str(C('INVITECODE'));//生成邀请码的长度在Application/Commom/Conf/config.php中修改
        $Invitecode = M("Invitecode");
        $id = $Invitecode->where("invitecode = '" . $invitecodestr . "'")->getField("id");
        if (! $id) {
            return $invitecodestr;
        } else {
            $this->createInvitecode();
        }
    }

    /**
     * 添加邀请码
     */
    public function addInvitecode()
    {
        if(IS_POST){
            $invitecode = I('post.invitecode');
            $yxdatetime = I('post.yxdatetime');
            $regtype = I('post.regtype');
            $Invitecode = M("Invitecode");
            $_formdata = array(
                'invitecode'=>$invitecode,
                'yxdatetime'=>strtotime($yxdatetime),
                'regtype'=>$regtype,
                'fmusernameid'=>$this->fans['uid'],
                'inviteconfigzt'=>1,
                'fbdatetime'=>time(),
            );
            $result = $Invitecode->add($_formdata);
            $this->ajaxReturn(['status'=>$result]);
        }
    }

    /**
     * 删除邀请码
     */
    public function delInvitecode()
    {
        if(IS_POST){
            $id = I('post.id',0,'intval');
            $res = M('Invitecode')->where(['id'=>$id])->delete();
            $this->ajaxReturn(['status'=>$res]);
        }
    }

    /**
     * 下级会员
     */
    public function member()
    {
        $where['groupid'] = ['neq',1];
        $username = I("get.username");
        $status = I("get.status");
        $authorized = I("get.authorized");
        $regdatetime = I('get.regdatetime');
        if (!empty($username) && !is_numeric($username)) {
            $where['username'] = ['like',"%".$username."%"];
        }elseif(intval($username) - 10000>0){
            $where['id'] = intval($username) - 10000;
        }
        if(!empty($status)){
            $where['status'] = $status;
        }
        if(!empty($authorized)){
            $where['authorized'] = $authorized;
        }
        $where['parentid'] = $this->fans['uid'];
        if($regdatetime){
            list($starttime,$endtime) = explode('|',$regdatetime);
            $where['regdatetime'] = ["between", [strtotime($starttime),strtotime($endtime)]];
        }
        $where['parentid'] = $this->fans['uid'];
        $count = M('Member')->where($where)->count();
        $page = new Page($count,15);
        $list = M('Member')
            ->where($where)
            ->limit($page->firstRow.','.$page->listRows)
            ->order('id desc')
            ->select();
        $this->assign("list", $list);
        $this->assign('page',$page->show());
        //取消令牌
        C('TOKEN_ON',false);
        $this->display();
    }

    //导出用户
    public function exportuser()
    {
        $username = I("get.username");
        $status = I("get.status");
        $authorized = I("get.authorized");
        $parentid = I("get.parentid");
        $groupid = I("get.groupid");

        if(is_numeric($username)){
            $map['id'] = array('eq',intval($username) - 10000);
        }else{
            $map['username'] = array('like','%'.$username.'%');
        }
        if ($status) {
            $map['status'] = array('eq',$status);
        }
        if ($authorized) {
            $map['authorized'] = array("eq", $authorized);
        }
        $map['parentid'] = array('eq',session('user_auth.uid'));
        $regdatetime = urldecode(I("request.regdatetime"));
        if ($regdatetime) {
            list($cstime,$cetime) = explode('|',$regdatetime);
            $map['regdatetime'] = ['between',[strtotime($cstime),strtotime($cetime)?strtotime($cetime):time()]];
        }

        $map['groupid'] = $groupid ? array('eq',$groupid) : array('neq',0);

        $title = array('用户名','商户号','用户类型','上级用户名','状态','认证','可用余额','冻结余额','注册时间');
        $data = M('Member')
            ->where($map)
            ->select();
        foreach ($data as $item){
            switch ($item['groupid'])
            {
                case 4:
                    $usertypestr = '商户';
                    break;
                case 5:
                    $usertypestr = '代理商';
                    break;
            }
            switch ($item['status'])
            {
                case 0:
                    $userstatus = '未激活';
                    break;
                case 1:
                    $userstatus = '正常';
                    break;
                case 2:
                    $userstatus = '已禁用';
                    break;
            }
            switch ($item['authorized'])
            {
                case 1:
                    $rzstauts = '已认证';
                    break;
                case 0:
                    $rzstauts = '未认证';
                    break;
                case 2:
                    $rzstauts = '等待审核';
                    break;
            }
            $list[] = array(
                'username'=>$item['username'],
                'userid'=>$item['id']+10000,
                'groupid'=>$usertypestr,
                'parentid'=>getParentName($item['parentid'],1),
                'status'=>$userstatus,
                'authorized'=>$rzstauts,
                'total'=>$item['balance'],
                'block'=>$item['blockedbalance'],
                'regdatetime'=>date('Y-m-d H:i:s',$item['regdatetime'])
            );
        }
        exportCsv($list,$title);
    }

    //用户状态切换
    public function editStatus()
    {
        if(IS_POST){
            $userid = intval(I('post.uid'));
            $isstatus= I('post.isopen') ? I('post.isopen'):0;
            $res = M('Member')->where(['id'=>$userid])->save(['status'=>$isstatus]);
            $this->ajaxReturn(['status'=>$res]);
        }
    }

    /**
     * 下级费率设置
     */
    public function userRateEdit()
    {
        //需要加载代理所有开放
        //$this->fans['uid'];
        $userid = I('get.uid',0,'intval');

        //系统产品列表
        $products = M('Product')
            ->join('LEFT JOIN __PRODUCT_USER__ ON __PRODUCT_USER__.pid = __PRODUCT__.id')
            ->where(['pay_product.status'=>1,'pay_product.isdisplay'=>1,'pay_product_user.userid'=>$userid,'pay_product_user.status'=>1])
            ->field('pay_product.id,pay_product.name,pay_product_user.status')
            ->select();
        //用户产品列表
        $userprods = M('Userrate')->where(['userid'=>$userid])->select();
        if($userprods){
            foreach ($userprods as $item){
                $_tmpData[$item['payapiid']] = $item;
            }
        }
        //重组产品列表
        $list = [];
        if($products){
            foreach ($products as $key=>$item){
                $products[$key]['feilv'] = $_tmpData[$item['id']]['feilv']?$_tmpData[$item['id']]['feilv']:'0.000';
                $products[$key]['fengding'] = $_tmpData[$item['id']]['fengding']?$_tmpData[$item['id']]['fengding']:'0.000';
            }
            }

        $this->assign('products',$products);
        $this->display();
    }
    //保存费率
    public function saveUserRate(){
        if(IS_POST){
            $userid = intval(I('post.userid'));
            $rows = $_POST['u'];
            //print_r($rows);
            $datalist = [];
            foreach ($rows as $key=>$item){
                $rates = M('Userrate')->where(['userid'=>$userid,'payapiid'=>$key])->find();
                if($rates){
                    $datalist[] = ['id'=>$rates['id'],'userid'=>$userid,'payapiid'=>$key,'feilv'=>$item['feilv'],'fengding'=>$item['fengding']];
                }else{
                    $datalist[] = ['userid'=>$userid,'payapiid'=>$key,'feilv'=>$item['feilv'],'fengding'=>$item['fengding']];
                }
            }
            M('Userrate')->addAll($datalist,[],true);
            $this->ajaxReturn(['status'=>1]);
        }
    }

    public function checkUserrate()
    {
        if(IS_POST){
            $pid = I('post.pid',0,'intval');
            $rate = I('post.feilv');
            if($pid){
                $selffeilv = M('Userrate')->where(['userid'=>$this->fans['uid'],'payapiid'=>$pid])->getField('feilv');
                if(($selffeilv * 1000) >= ($rate * 1000)){
                    $this->ajaxReturn(['status'=>1]);
                }
            }
        }
    }
    //下级流水
    public function childord()
    {
        $userid = I('get.userid',0,'intval')+10000;
        $data = array();
        if($userid){
            $where = array('pay_memberid'=>$userid);
            //商户号
            $memberid = I("request.memberid");
            if($memberid){
                $where['pay_memberid'] = $memberid;
            }
            //提交时间
            $createtime = urldecode(I("request.createtime"));
            if ($createtime) {
                list($cstime,$cetime) = explode('|',$createtime);
                $where['pay_applydate'] = ['between',[strtotime($cstime),strtotime($cetime)?strtotime($cetime):time()]];
            }
            //成功时间
            $successtime = urldecode(I("request.successtime"));
            if ($successtime) {
                list($sstime,$setime) = explode('|',$successtime);
                $where['pay_successdate'] = ['between',[strtotime($sstime),strtotime($setime)?strtotime($setime):time()]];
            }
            //查询下级数据
            $where['pay_status'] = array('in',array('1','2'));
            $statistic = M('Order')->field(['sum(`pay_amount`) pay_amount, sum(`pay_poundage`) pay_poundage, sum(`pay_actualamount`) pay_actualamount'])->where($where)->find();

            
            $this->assign('pay_amount',number_format($statistic['pay_amount'], 2));
            $this->assign('pay_poundage',number_format($statistic['pay_poundage'], 2));
            $this->assign('pay_actualamount',number_format($statistic['pay_actualamount'], 2));
        
            //分页
            $count = M('Order')->where($where)->count();
            $Page = new Page($count,10);
            $data = M('Order')->where($where)->limit($Page->firstRow.','.$Page->listRows)->order(['id'=>'desc'])->select();
            $show = $Page->show();
        }

        $this->assign('list',$data);
        $this->assign('page',$show);
        $this->display();
    }

    public function addUser()
    {
        $this->display();
    }

    /**
     * 添加商户，代理商
     */
    public function saveUser()
    {
        $u = $_POST['u'];
        $u['birthday'] = strtotime($u['birthday']);
        $groupId = empty($u['groupid']) ? MemberModel::GROUPID_MERCHANT : $u['groupid'];

        $has_user = M('member')->where(['username' => $u['username'], 'email' => $u['email'], '_logic' => 'or'])->find(); //必须主库模型
        if ($has_user) {
            if ($has_user['username'] == $u['username']) {
                $this->ajaxReturn(array("status"=>0, "msg"=>'用户名已存在'));
            }
            if ($has_user['email'] == $u['email']) {
                $this->ajaxReturn(array("status"=>0, "msg"=>'邮箱已存在'));
            }
        }

        // 创建用户
        $memberModel = DM('Member');
        $websiteModel = DM('Websiteconfig', 'Slave');
        $current_user = session('user_auth');
        $siteconfig = $websiteModel->find();
        $u = generateUser($u, $siteconfig);
        $s['activatedatetime'] = date("Y-m-d H:i:s");
        $u['parentid'] = $current_user['uid'];
        $u['groupid'] = $groupId;
        $res = $memberModel->add($u);

        // 发邮件通知用户密码
        sendPasswordEmail($u['username'], $u['email'], $u['origin_password'],$siteconfig);

        $this->ajaxReturn(['status'=>$res]);
    }
}
?>