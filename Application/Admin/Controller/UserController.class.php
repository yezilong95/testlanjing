<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-04-02
 * Time: 23:01
 */

namespace Admin\Controller;

use Org\Util\Str;
use Think\Page;

/**
 * 用户管理控制
 * Class UserController
 * @package Admin\Controller
 */

class UserController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        //通道
        $channels = M('Channel')
            ->where(['status' => 1])
            ->field('id,code,title,paytype,status')
            ->select();
        $this->assign('channels', json_encode($channels));
        $this->assign('channellist', $channels);
    }

    /**
     * 用户列表
     */
    public function index()
    {
        $where['groupid'] = I('get.groupid') ? I('get.groupid') : ['neq', 1];
        $username         = I("get.username");
        $status           = I("get.status");
        $authorized       = I("get.authorized");
        $parentid         = I('get.parentid');
        $regdatetime      = I('get.regdatetime');
        $name         = I('get.name');
        if (!empty($username) && !is_numeric($username)) {
            $where['username'] = ['like', "%" . $username . "%"];
        } elseif (intval($username) - 10000 > 0) {
            $where['id'] = intval($username) - 10000;
        }
        if (!empty($status)) {
            $where['status'] = $status;
        }
        if (!empty($authorized)) {
            $where['authorized'] = $authorized;
        }

        if (!empty($parentid) && !is_numeric($parentid)) {
            $User              = M("Member");
            $pid               = $User->where("username = '" . $parentid . "'")->getField("id");
            $where['parentid'] = $pid;
        } elseif ($parentid) {
            $where['parentid'] = $parentid;
        }
        if (!empty($name)) {
            $where['realname'] = ["like","%$name%"];
        }
        if ($regdatetime) {
            list($starttime, $endtime) = explode('|', $regdatetime);
            $where['regdatetime']      = ["between", [strtotime($starttime), strtotime($endtime)]];
        }
        $count = M('Member')->where($where)->count();
        $size  = 15;
        $rows  = I('get.rows', $size);
        if (!$rows) {
            $rows = $size;
        }
        $page = new Page($count, $rows);
        $list = M('Member')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();
        
        foreach ($list as $k => $v) {
            $list[$k]['domain'] = trim($v['domain']) ?$v['domain']:'该商户无风控域名';
            $list[$k]['groupname'] = $this->groupId[$v['groupid']];
        }
        $this->assign('rows', $rows);
        $this->assign("list", $list);
        $this->assign('page', $page->show());
        //取消令牌
        C('TOKEN_ON', false);
        $this->display();
    }

    public function invitecode()
    {
        $invitecode = I("get.invitecode");
        $fbusername = I("get.fbusername");
        $syusername = I("get.syusername");
        $regtype    = I("get.groupid");
        $status     = I("get.status");
        if (!empty($invitecode)) {
            $where['invitecode'] = ["like", "%" . $invitecode . "%"];
        }
        if (!empty($fbusername)) {
            $fbusernameid          = M("Member")->where("username = '" . $fbusername . "'")->getField("id");
            $where['fmusernameid'] = $fbusernameid;
        }
        if (!empty($syusername)) {
            $syusernameid          = M("Member")->where("username = '" . $syusername . "'")->getField("id");
            $where['syusernameid'] = $syusernameid;
        }
        if (!empty($regtype)) {
            $where['regtype'] = $regtype;
        }
        $regdatetime = urldecode(I("request.regdatetime"));
        if ($regdatetime) {
            list($cstime, $cetime) = explode('|', $regdatetime);
            $where['fbdatetime']   = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }
        if (!empty($status)) {
            $where['status'] = $status;
        }
        $count = M('Invitecode')->where($where)->count();
        $size  = 15;
        $rows  = I('get.rows', $size);
        if (!$rows) {
            $rows = $size;
        }
        $page = new Page($count, $rows);
        $list = M('Invitecode')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();

        $Admin = M('Admin');
        foreach ($list as $k => $v) {
            if ($v['is_admin']) {
                $username                 = $Admin->where(['id' => $v['fmusernameid']])->getField('username');
                $list[$k]['fmusernameid'] = $username;
            } else {
                $list[$k]['fmusernameid'] = getusername($v['fmusernameid']);
            }
            $list[$k]['is_admin']  = $v['is_admin'] ? '管理员' : '代理商';
            $list[$k]['groupname'] = $this->groupId[$v['regtype']];
        }
        $this->assign('rows', $rows);
        $this->assign("list", $list);
        $this->assign('page', $page->show());
        //取消令牌
        C('TOKEN_ON', false);
        $this->display();
    }

    public function setInvite()
    {
        $data = M("Inviteconfig")->find();
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 保存邀请码设置
     */
    public function saveInviteConfig()
    {
        if (IS_POST) {
            $Inviteconfig                   = M("Inviteconfig");
            $_formdata['invitezt']          = I('post.invitezt');
            $_formdata['invitetype2number'] = I('post.invitetype2number');
            $_formdata['invitetype2ff']     = I('post.invitetype2ff');
            $_formdata['invitetype5number'] = I('post.invitetype5number');
            $_formdata['invitetype5ff']     = I('post.invitetype5ff');
            $_formdata['invitetype6number'] = I('post.invitetype6number');
            $_formdata['invitetype6ff']     = I('post.invitetype6ff');
            $result                         = $Inviteconfig->where(array('id' => I('post.id')))->save($_formdata);
            $this->ajaxReturn(['status' => $result]);
        }
    }

    /**
     * 添加邀请码
     */
    public function addInvite()
    {
        $invitecode = $this->createInvitecode();
        $this->assign('invitecode', $invitecode);
        $this->assign('datetime', date('Y-m-d H:i:s', time() + 86400));
        $this->display();
    }

    /**
     * 邀请码
     * @return string
     */
    private function createInvitecode()
    {
        $invitecodestr = random_str(C('INVITECODE')); //生成邀请码的长度在Application/Commom/Conf/config.php中修改
        $Invitecode    = M("Invitecode");
        $id            = $Invitecode->where("invitecode = '" . $invitecodestr . "'")->getField("id");
        if (!$id) {
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
        if (IS_POST) {
            $invitecode = I('post.invitecode');
            $yxdatetime = I('post.yxdatetime');
            $regtype    = I('post.regtype');
            $Invitecode = M("Invitecode");

            $_formdata = array(
                'invitecode'     => $invitecode,
                'yxdatetime'     => strtotime($yxdatetime),
                'regtype'        => $regtype,
                'fmusernameid'   => session('admin_auth.uid'),
                'inviteconfigzt' => 1,
                'fbdatetime'     => time(),
                'is_admin'       => 1,
            );
            $result = $Invitecode->add($_formdata);
            $this->ajaxReturn(['status' => $result]);
        }
    }

    /**
     * 删除邀请码
     */
    public function delInvitecode()
    {
        if (IS_POST) {
            $id  = I('post.id', 0, 'intval');
            $res = M('Invitecode')->where(['id' => $id])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    public function getRandstr()
    {
        echo random_str();
    }
    public function batchdel()
    {
        $ids  = I("post.ids");
        $ids  = trim($ids, ',');
        $type = M("User")->where(array('id' => array('in', $ids)))->delete();
        M('Money')->where(array('userid' => array('in', $ids)))->delete();
        M('userbasicinfo')->where(array('userid' => array('in', $ids)))->delete();
        M('userpassword')->where(array('userid' => array('in', $ids)))->delete();
        M('userpayapi')->where(array('userid' => array('in', $ids)))->delete();
        M('Userrate')->where(array('userid' => array('in', $ids)))->delete();
        M('userverifyinfo')->where(array('userid' => array('in', $ids)))->delete();
        if ($type) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    /**
     * 删除用户
     */
    public function delUser()
    {
        if (IS_POST) {
            $id  = I('post.uid', 0, 'intval');
            $res = M('Member')->where(['id' => $id])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //导出用户
    public function exportuser()
    {
        $username   = I("get.username");
        $status     = I("get.status");
        $authorized = I("get.authorized");
        $parentid   = I("get.parentid");
        $groupid    = I("get.groupid");

        if (is_numeric($username)) {
            $map['id'] = array('eq', intval($username) - 10000);
        } else {
            $map['username'] = array('like', '%' . $username . '%');
        }
        if ($status) {
            $map['status'] = array('eq', $status);
        }
        if ($authorized) {
            $map['authorized'] = array("eq", $authorized);
        }
        if ($parentid) {
            if (is_numeric($parentid)) {
                $sjuserid = M('Member')->where("id = " . ($parentid - 10000))->getField("id");
            } else {
                $sjuserid = M('Member')->where("username like '%" . $parentid . "%'")->getField("id");
            }
            $map['parentid'] = array('eq', $sjuserid);
        }
        $regdatetime = urldecode(I("request.regdatetime"));
        if ($regdatetime) {
            list($cstime, $cetime) = explode('|', $regdatetime);
            $map['regdatetime']    = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }

        $map['groupid'] = $groupid ? array('eq', $groupid) : array('neq', 0);

        $title = array('用户名', '商户号', '用户类型', '上级用户名', '状态', '认证', '可用余额', '冻结余额', '注册时间');
        $data  = M('Member')
            ->where($map)
            ->select();
        foreach ($data as $item) {
            switch ($item['groupid']) {
                case 4:
                    $usertypestr = '商户';
                    break;
                case 5:
                    $usertypestr = '代理商';
                    break;
            }
            switch ($item['status']) {
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
            switch ($item['authorized']) {
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
                'username'    => $item['username'],
                'userid'      => $item['id'] + 10000,
                'groupid'     => $usertypestr,
                'parentid'    => getParentName($item['parentid'], 1),
                'status'      => $userstatus,
                'authorized'  => $rzstauts,
                'total'       => $item['balance'],
                'block'       => $item['blockedbalance'],
                'regdatetime' => date('Y-m-d H:i:s', $item['regdatetime']),
            );
        }
        exportCsv($list, $title);
    }

    public function jbxx()
    {
        $userid           = I("post.userid");
        $Userbasicinfo    = M("Userbasicinfo");
        $list             = $Userbasicinfo->where("userid=" . $userid)->find();
        $list['username'] = M('User')->where(array('id' => $userid))->getField('username');
        $list['usertype'] = M('User')->where(array('id' => $userid))->getField('usertype');
        $this->ajaxReturn($list, "json");
    }

    public function editjbxx()
    {
        if (IS_POST) {
            $rows['fullname']    = I('post.fullname');
            $rows['sfznumber']   = I('post.sfznumber');
            $rows['birthday']    = I('post.birthday');
            $rows['phonenumber'] = I('post.phonenumber');
            $rows['qqnumber']    = I('post.sfznumber');
            $rows['address']     = I('post.address');
            $rows['sex']         = I('post.sex');
            $usertype            = I('post.usertype');
            M('User')->where(array('id' => I('post.userid')))->save(array('usertype' => $usertype));
            $returnstr = M("Userbasicinfo")->where(array('id' => I('post.id')))->save($rows);
            if ($returnstr == 1 || $returnstr == 0) {
                exit("ok");
            } else {
                exit("no");
            }
        }
    }

    public function zhuangtai()
    {
        $userid = I("post.userid");
        $User   = M("User");
        $status = $User->where("id=" . $userid)->getField("status");
        exit($status);
    }

    public function xgzhuangtai()
    {
        $userid         = I("post.userid");
        $status         = I("post.status");
        $User           = M("User");
        $data["status"] = $status;
        $returnstr      = $User->where("id=" . $userid)->save($data);
        if ($returnstr == 1 || $returnstr == 0) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    public function renzheng()
    {
        $userid         = I("post.userid");
        $Userverifyinfo = M("Userverifyinfo");
        $list           = $Userverifyinfo->where("userid=" . $userid)->find();
        $this->ajaxReturn($list, "json");
    }

    /**
     * 保存认证
     */
    public function editAuthoize()
    {
        if (IS_POST) {
            $rows   = I('post.u');
            $userid = $rows['userid'];
            unset($rows['userid']);
            $res = M('Member')->where(['id' => $userid])->save($rows);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    public function renzhengeditdomain()
    {
        $userid         = I("post.userid");
        $domain         = I("post.domain");
        $Userverifyinfo = M("Userverifyinfo");
        $data["domain"] = $domain;
        $returnstr      = $Userverifyinfo->where("userid=" . $userid)->save($data);
        if ($returnstr == 1 || $returnstr == 0) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    public function renzhengeditmd5key()
    {
        $userid         = I("post.userid");
        $md5key         = I("post.md5key");
        $Userverifyinfo = M("Userverifyinfo");
        $data["md5key"] = $md5key;
        $returnstr      = $Userverifyinfo->where("userid=" . $userid)->save($data);
        if ($returnstr == 1 || $returnstr == 0) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    /**
     * 修改密码
     */
    public function editPassword()
    {
        if (IS_POST) {
            $userid  = I("post.userid");
            $salt    = I("post.salt");
            $groupid = I('post.groupid');
            $u       = I('post.u');
            if ($u['password']) {
                $data['password'] = md5($u['password'] . ($groupid < 4 ? C('DATA_AUTH_KEY') : $salt));
            }
            if ($u['paypassword']) {
                $data['paypassword'] = md5($u['paypassword']);
            }
            $res = M('Member')->where("id=" . $userid)->save($data);
            $this->ajaxReturn(['status' => $res]);
        } else {
            $userid = I('get.uid', 0, 'intval');
            if ($userid) {
                $data = M('Member')
                    ->where(['id' => $userid])->find();
                $this->assign('u', $data);
            }

            $this->display();
        }
    }

    public function bankcard()
    {
        $userid   = I("post.userid");
        $Bankcard = M("Bankcard");
        $list     = $Bankcard->where("userid=" . $userid)->find();
        $this->ajaxReturn($list, "json");
    }

    public function editbankcard()
    {
        if (IS_POST) {
            $id   = I('post.id');
            $rows = [
                'bankname'     => I('post.bankname', '', 'trim'),
                'bankzhiname'  => I('post.bankzhiname', '', 'trim'),
                'banknumber'   => I('post.banknumber', '', 'trim'),
                'bankfullname' => I('post.bankfullname', '', 'trim'),
                'sheng'        => I('post.sheng', '', 'trim'),
                'shi'          => I('post.shi', '', 'trim'),
            ];
            $returnstr = M("Bankcard")->where(['id' => $id])->save($rows);
            if ($returnstr == 1 || $returnstr == 0) {
                exit("ok");
            } else {
                exit("no");
            }
        }
    }

    public function suoding()
    {
        $id               = I("post.id");
        $disabled         = I("post.disabled");
        $data["disabled"] = $disabled;
        if ($disabled == 0) {
            $data["jdatetime"] = date("Y-m-d H:i:s");
        }
        $Bankcard  = M("Bankcard");
        $returnstr = $Bankcard->where("id=" . $id)->save($data);
        if ($returnstr == 1 || $returnstr == 0) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    public function tongdao()
    {
        $userid     = I("post.userid");
        $Userpayapi = M("Userpayapi");
        $list       = $Userpayapi->where("userid=" . $userid)->find();
        if (!$list) {
            $Payapiconfig              = M("Payapiconfig");
            $payapiid                  = $Payapiconfig->where("`default`=1")->getField("payapiid");
            $Payapi                    = M("Payapi");
            $en_payname                = $Payapi->where("id=" . $payapiid)->getField("en_payname");
            $Userpayapi->userid        = $userid;
            $Userpayapi->payapicontent = $en_payname . "|";
            $Userpayapi->add();
            $list = $Userpayapi->where("userid=" . $userid)->find();
        }
        $Payapiconfig     = M("Payapiconfig");
        $payapiid         = $Payapiconfig->where("`default`=1")->getField("payapiid");
        $Payapi           = M("Payapi");
        $en_payname       = $Payapi->where("id=" . $payapiid)->getField("en_payname");
        $list["disabled"] = $en_payname;

        $Payapiconfig = M("Payapiconfig");
        $payapiidstr  = $Payapiconfig->field("payapiid")
            ->where("disabled=1")
            ->select(false);
        $Payapi             = M("Payapi");
        $listlist           = $Payapi->where("id in (" . $payapiidstr . ")")->select();
        $payapiaccountarray = array();
        foreach ($listlist as $key) {

            $Userpayapizhanghao = M("Userrate");
            $val                = $Userpayapizhanghao->where("userid=" . $userid . " and payapiid=" . $key["id"])->getField("defaultpayapiuserid");
            if (!$val) {
                $Payapiaccount = M("Payapiaccount");
                $val           = $Payapiaccount->where("payapiid=" . $key["id"] . " and defaultpayapiuser=1")->getField("id");
            }
            $payapiaccountarray[$key["en_payname"] . $key["id"]] = $val;
        }

        $obj = array(
            'list'               => $list,
            'payapiaccountarray' => $payapiaccountarray,
        );

        $this->ajaxReturn($obj, "json");
    }

    public function edittongdao()
    {
        $userid     = I("post.userid");
        $selecttype = I("post.selecttype");
        $payname    = I("post.payname");

        $Userpayapi    = M("Userpayapi");
        $payapicontent = $Userpayapi->where("userid=" . $userid)->getField("payapicontent");
        if ($selecttype == 1) {
            $payapicontent = str_replace($payname . "|", "", $payapicontent);
        } else {
            $payapicontent = $payapicontent . $payname . "|";
        }
        $data["payapicontent"] = $payapicontent;
        $num                   = $Userpayapi->where("userid=" . $userid)->save($data);
        if ($num) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    public function editdefaultpayapiuser()
    {
        $userid   = I("post.userid");
        $payapiid = I("post.payapiid");
        $val      = I("post.val");

        $Userpayapizhanghao = M("Userrate");
        $list               = $Userpayapizhanghao->where("userid=" . $userid . " and payapiid=" . $payapiid)->select();
        if (!$list) {
            $data["userid"]              = $userid;
            $data["payapiid"]            = $payapiid;
            $data["defaultpayapiuserid"] = $val;
            $Userpayapizhanghao->add($data);
        } else {
            $data["defaultpayapiuserid"] = $val;
            $Userpayapizhanghao->where("userid=" . $userid . " and payapiid=" . $payapiid)->save($data);
        }
        exit("ok");
    }

    public function feilv()
    {
        $userid       = I("post.userid");
        $Payapiconfig = M("Payapiconfig");
        $payapiidstr  = $Payapiconfig->field("payapiid")
            ->where("disabled=1")
            ->select(false);
        $Payapi             = M("Payapi");
        $listlist           = $Payapi->where("id in (" . $payapiidstr . ")")->select();
        $payapiaccountarray = array();
        foreach ($listlist as $key) {

            $Userpayapizhanghao = M("Userrate");
            $val                = $Userpayapizhanghao->where("userid=" . $userid . " and payapiid=" . $key["id"])->getField("feilv");
            if (!$val) {
                $Payapiaccount = M("Payapiaccount");
                $val           = $Payapiaccount->where("payapiid=" . $key["id"] . " and defaultpayapiuser=1")->getField("defaultrate");
            }

            $val2 = $Userpayapizhanghao->where("userid=" . $userid . " and payapiid=" . $key["id"])->getField("fengding");
            if (!$val2) {
                $Payapiaccount = M("Payapiaccount");
                $val2          = $Payapiaccount->where("payapiid=" . $key["id"] . " and defaultpayapiuser=1")->getField("fengding");
            }

            $payapiaccountarray[$key["en_payname"] . $key["id"]] = $val . "|" . $val2;
        }

        $this->ajaxReturn($payapiaccountarray, "json");
    }

    public function editfeilv()
    {
        $userid             = I("post.userid");
        $payapiid           = I("post.payapiid");
        $val1               = I("post.feilvval", "") ? I("post.feilvval", "") : 0;
        $val2               = I("post.fengdingval", "") ? I("post.fengdingval", "") : 0;
        $Userpayapizhanghao = M("Userrate");
        $list               = $Userpayapizhanghao->where("userid=" . $userid . " and payapiid=" . $payapiid)->select();
        if (!$list) {
            $data["userid"]   = $userid;
            $data["payapiid"] = $payapiid;
            $data["feilv"]    = $val1;
            $data["fengding"] = $val2;
            $Userpayapizhanghao->add($data);
        } else {
            $data["feilv"]    = $val1;
            $data["fengding"] = $val2;
            $Userpayapizhanghao->where("userid=" . $userid . " and payapiid=" . $payapiid)->save($data);
        }
        exit("ok");
    }

    public function tksz()
    {
        $userid           = I("post.userid");
        $User             = M("User");
        $usertype         = $User->where("id=" . $userid)->getField("usertype");
        $websiteid        = $User->where("id=" . $userid)->getField("websiteid");
        $useriduserid     = $userid;
        $Payapiconfig     = M("Payapiconfig");
        $disabledpayapiid = $Payapiconfig->field('payapiid')->where("disabled=0")->select(false);
        $Payapi           = M("Payapi");
        $tongdaolist      = $Payapi->where("id not in (" . $disabledpayapiid . ")")->select();
        $datetype         = array("b", "w", "j");
        $Tikuanmoney      = M("Tikuanmoney");
        $array            = array();
        foreach ($tongdaolist as $tongdao) {
            // file_put_contents("loguser.txt",$tongdao["id"]."----", FILE_APPEND);
            for ($i = 0; $i < 2; $i++) {
                // file_put_contents("loguser.txt",$i."----", FILE_APPEND);
                foreach ($datetype as $val) {
                    // file_put_contents("loguser.txt",$val."||".$userid."||".$websiteid."|||||||", FILE_APPEND);
                    $count = $Tikuanmoney->where("t=" . $i . " and userid=" . $userid . " and payapiid=" . $tongdao["id"] . " and websiteid = " . $websiteid . " and datetype = '" . $val . "'")->count();
                    // file_put_contents("loguser.txt",$count."*********", FILE_APPEND);
                    if ($count <= 0) {
                        $Tikuanmoney->t         = $i;
                        $Tikuanmoney->datetype  = $val;
                        $Tikuanmoney->userid    = $userid;
                        $Tikuanmoney->websiteid = $websiteid;
                        $Tikuanmoney->payapiid  = $tongdao["id"];
                        $Tikuanmoney->add();
                        $value = "0.00";
                    } else {
                        $value = $Tikuanmoney->where("t=" . $i . " and userid=" . $userid . " and payapiid=" . $tongdao["id"] . " and websiteid = " . $websiteid . " and datetype = '" . $val . "'")->getField("money");
                    }
                    $array["form" . $tongdao["id"]]["t" . $i . $val] = $value;
                }
            }
            $array["form" . $tongdao["id"]]["tikuanpayapiid"] = $tongdao["id"];
            $array["form" . $tongdao["id"]]["userid"]         = $useriduserid;
        }

        $Tikuanconfig = M("Tikuanconfig");
        $count        = $Tikuanconfig->where("websiteid=" . $websiteid . " and userid=" . $userid)->count();
        if ($count <= 0) {
            $data["websiteid"] = $websiteid;
            $data["userid"]    = $userid;
            $Tikuanconfig->add($data);
        }
        $tikuanconfiglist         = $Tikuanconfig->where("websiteid=" . $websiteid . " and userid=" . $userid)->find();
        $arraystr                 = array();
        $arraystr["tikuanconfig"] = $tikuanconfiglist;
        $arraystr["tksz"]         = $array;
        $this->ajaxReturn($arraystr, "json");
    }

    public function Edittikuanmoney()
    {
        $userid = I("post.userid");

        $User      = M("User");
        $usertype  = $User->where("id=" . $userid)->getField("usertype");
        $websiteid = $User->where("id=" . $userid)->getField("websiteid");
        /*
         * if($usertype == 2){ //如果用户类型为2 分站管理员
         * $Website = M("Website");
         * $websiteid = $Website->where("userid=".$userid)->getField("id");
         * $useriduserid = $userid;
         * $userid = 0;
         *
         * }else{
         * $websiteid = 0;
         * }
         */

        $payapiid = I("post.tikuanpayapiid");

        $datetype = array(
            "b",
            "w",
            "j",
        );

        $Tikuanmoney = M("Tikuanmoney");

        for ($i = 0; $i < 2; $i++) {
            foreach ($datetype as $val) {
                $Tikuanmoney->money = I("post.t" . $i . $val, 0);
                $Tikuanmoney->where("t=" . $i . " and userid=" . $userid . " and payapiid=" . $payapiid . " and websiteid = " . $websiteid . " and datetype = '" . $val . "'")->save();
            }
        }
        exit("修改成功！");
    }

    /**
     * 用户资金操作
     */
    public function usermoney()
    {
        $userid = I("get.userid");
        $info   = M("Member")->where("id=" . $userid)->find();
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 增加、减少余额
     */
    public function incrMoney()
    {
        $userid = I("request.uid");
        $info   = M("Member")->where("id=" . $userid)->find();
        $this->assign('info', $info);

        if (IS_POST) {
            $userid     = I("post.uid");
            $cztype     = I("post.cztype");
            $bgmoney    = I("post.bgmoney");
            $contentstr = I("post.memo", "");

            if (($info['balance'] - $bgmoney) < 0 && $cztype == 4) {
                $this->ajaxReturn(['status' => 0, 'msg' => "账上余额不足" . $bgmoney . "元，不能完成减金操作"]);
            }
            if ($cztype == 3) {
                $data["balance"] = array('exp', "balance+" . $bgmoney);
            } elseif ($cztype == 4) {
                $data["balance"] = array('exp', "balance-" . $bgmoney);
            }
            $res = M('Member')->where(['id' => $userid])->save($data);
            if ($res) {
                $arrayField = array(
                    "userid"     => $userid,
                    "money"      => $bgmoney,
                    "datetime"   => date("Y-m-d H:i:s"),
                    "tongdao"    => '',
                    "transid"    => "",
                    "orderid"    => "",
                    "lx"         => $cztype, // 增减类型
                    "contentstr" => $contentstr,
                );
                moneychangeadd($arrayField);
            }
            $this->ajaxReturn(['status' => $res]);

        } else {
            $this->display();
        }
    }

    /**
     * 冻结、解冻余额
     */
    public function frozenMoney()
    {
        $userid = I("request.uid");
        $info   = M("Member")->where("id=" . $userid)->find();
        $this->assign('info', $info);

        if (IS_POST) {
            $userid     = I("post.uid");
            $cztype     = I("post.cztype");
            $bgmoney    = I("post.bgmoney");
            $contentstr = I("post.memo", "");
            if ($bgmoney <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => "金额需要大于0"]);
            }

            if (($info['blockedbalance'] - $bgmoney) < 0 && $cztype == 8) {
                $this->ajaxReturn(['status' => 0, 'msg' => "账上冻结余额不足" . $bgmoney . "元，不能完成减金操作"]);
            }
            //冻结
            if ($cztype == 7 && ($info['balance'] - $bgmoney) < 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => "账上余额不足" . $bgmoney . "元，不能完成冻结操作"]);
            }
            if ($cztype == 7) {
                $data["balance"]        = array('exp', "balance-" . $bgmoney);
                $data["blockedbalance"] = array('exp', "blockedbalance+" . $bgmoney);
            } elseif ($cztype == 8) {
                $data["balance"]        = array('exp', "balance+" . $bgmoney);
                $data["blockedbalance"] = array('exp', "blockedbalance-" . $bgmoney);
            }
            $res = M('Member')->where(['id' => $userid])->save($data);
            if ($res) {
                $arrayField = array(
                    "userid"     => $userid,
                    "money"      => $bgmoney,
                    "datetime"   => date("Y-m-d H:i:s"),
                    "tongdao"    => '',
                    "transid"    => "",
                    "orderid"    => "",
                    "lx"         => $cztype, // 增减类型
                    "contentstr" => $contentstr,
                );
                moneychangeadd($arrayField);
            }
            $this->ajaxReturn(['status' => $res]);

        } else {
            $this->display();
        }
    }

    /**
     * 增加、减少保证金
     */
    public function incrEnsureMoney()
    {
        $userid = I("request.uid");
        $info   = M("Member")->where("id=" . $userid)->find();
        $this->assign('info', $info);

        if (IS_POST) {
            $userid     = I("post.uid");
            $cztype     = I("post.cztype");
            $bgmoney    = I("post.bgmoney");
            $contentstr = I("post.memo", "");
            if ($bgmoney <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => "金额需要大于0"]);
            }

            if (($info['ensurebalance'] - $bgmoney) < 0 && $cztype == 14) {
                $this->ajaxReturn(['status' => 0, 'msg' => "保证金不足" . $bgmoney . "元，不能完成减金操作"]);
            }
            //增加保证金
            if ($cztype == 13 && ($info['balance'] - $bgmoney) < 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => "账上余额不足" . $bgmoney . "元，不能完成增加保证金操作"]);
            }
            if ($cztype == 13) {
                //增加保证金
                $data["balance"]        = array('exp', "balance-" . $bgmoney);
                $data["ensurebalance"] = array('exp', "ensurebalance+" . $bgmoney);
            } elseif ($cztype == 14) {
                //减少保证金
                $data["balance"]        = array('exp', "balance+" . $bgmoney);
                $data["ensurebalance"] = array('exp', "ensurebalance-" . $bgmoney);
            }
            $res = M('Member')->where(['id' => $userid])->save($data);
            if ($res) {
                $arrayField = array(
                    "userid"     => $userid,
                    "money"      => $bgmoney,
                    "datetime"   => date("Y-m-d H:i:s"),
                    "tongdao"    => '',
                    "transid"    => "",
                    "orderid"    => "",
                    "lx"         => $cztype, // 增减类型
                    "contentstr" => $contentstr,
                );
                moneychangeadd($arrayField);
            }
            $this->ajaxReturn(['status' => $res]);

        } else {
            $this->display();
        }
    }

    /**
     * 手动去管理定时解冻任务
     * author: feng
     * create: 2017/10/21 15:43
     */
    public function frozenTiming()
    {
        //通道

        $where    = array();
        $memberid = I("get.uid");
        if ($memberid) {
            $where['userid'] = array('eq', $memberid);
        } else {
            return;
        }

        $orderid = I("get.orderid");
        if ($orderid) {
            $where['orderid'] = array('eq', $orderid);
        }

        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime) = explode('|', $createtime);
            $where['createtime']   = ['between', [strtotime($cstime), strtotime($cetime ? $cetime : date('Y-m-d'))]];
        }
        $count = M('blockedlog')->where($where)->count();
        $page  = new Page($count, 15);
        $list  = M('blockedlog')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('status asc,id desc')
            ->select();
        $this->assign("list", $list);
        $this->assign("page", $page->show());
        C('TOKEN_ON', false);

        $this->display();
    }
    /**
     * 解冻
     * author: feng
     * create: 2017/10/21 17:15
     */
    public function frozenHandle()
    {
        if (IS_POST) {
            $id = I('post.id', 0, 'intval');
            if (!$id) {
                $this->ajaxReturn(['status' => 0]);
            }

            $maps['status'] = array('eq', 0);
            $maps["id"]     = $id;
            $blockData      = M('blockedlog')->where($maps)->order('id asc')->find();
            if (!$blockData) {
                $this->ajaxReturn(['status' => 0, 'msg' => '不存在或已解冻']);
            }
            $blockedbalance = M('Member')->where(['id' => $blockData['userid']])->getField("blockedbalance");

            if ($blockedbalance < $blockData["amount"]) {
                $this->ajaxReturn(['status' => 0, 'msg' => '冻结金额不足']);
            }
            $rows                   = array();
            $rows['balance']        = array('exp', "balance+{$blockData['amount']}");
            $rows['blockedbalance'] = array('exp', "blockedbalance-{$blockData['amount']}");
            //开启事务
            $Model = M();
            $Model->startTrans();
            //更新资金
            $upRes = $Model->table('pay_member')->where(['id' => $blockData['userid']])->save($rows);
            //更新状态
            $uplog = $Model->table('pay_blockedlog')->where(array('id' => $blockData['id']))->save(array('status' => 1));
            //增加记录
            $data               = array();
            $data['userid']     = $blockData['userid'];
            $data['money']      = $blockData['amount'];
            $data['datetime']   = date("Y-m-d H:i:s");
            $data['tongdao']    = $blockData['pid'];
            $data['transid']    = $blockData['orderid']; //交易流水号
            $data['orderid']    = $blockData['orderid'];
            $data['lx']         = 8; //解冻
            $data['contentstr'] = "订单金额解冻";
            $change             = $Model->table('pay_moneychange')->add($data);

            //提交事务
            if ($upRes && $uplog && $change) {
                $Model->commit();
                $this->ajaxReturn(['status' => 1]);
            } else {
                $Model->rollback();
            }
            $this->ajaxReturn(['status' => 0]);

        }
    }
    /**
     * 批量处理
     * author: feng
     * create: 2017/10/21 18:22
     */
    public function frozenHandles()
    {
        if (IS_POST) {
            $ids = I('post.ids');
            if (!$ids) {
                $this->ajaxReturn(['status' => 0]);
            }

            $idsArr   = explode(",", $ids);
            $sucCount = 0;
            $msg      = "";
            foreach ($idsArr as $k => $id) {
                $maps['status'] = array('eq', 0);
                $maps["id"]     = $id;
                $blockData      = M('blockedlog')->where($maps)->order('id asc')->find();
                if (!$blockData) {
                    continue;
                }
                $blockedbalance = M('member')->where(['id' => $blockData['userid']])->field("blockedbalance");
                if ($blockedbalance < $blockData["amount"]) {
                    $msg = '冻结金额不足';
                    break;
                }
                $rows                   = array();
                $rows['balance']        = array('exp', "balance+{$blockData['amount']}");
                $rows['blockedbalance'] = array('exp', "blockedbalance-{$blockData['amount']}");
                //开启事务
                $Model = M();
                $Model->startTrans();
                //更新资金
                $upRes = $Model->table('pay_member')->where(['id' => $blockData['userid']])->save($rows);
                //更新状态
                $uplog = $Model->table('pay_blockedlog')->where(array('id' => $blockData['id']))->save(array('status' => 1));
                //增加记录
                $data               = array();
                $data['userid']     = $blockData['userid'];
                $data['money']      = $blockData['amount'];
                $data['datetime']   = date("Y-m-d H:i:s");
                $data['tongdao']    = $blockData['pid'];
                $data['transid']    = $blockData['orderid']; //交易流水号
                $data['orderid']    = $blockData['orderid'];
                $data['lx']         = 8; //解冻
                $data['contentstr'] = "订单金额解冻";
                $change             = $Model->table('pay_moneychange')->add($data);

                //提交事务
                if ($upRes && $uplog && $change) {
                    $Model->commit();
                    $sucCount++;
                } else {
                    $Model->rollback();
                }

            }
            $this->ajaxReturn(array("status" => $sucCount == count($idsArr) ? 1 : 0, "count" => $sucCount, "msg" => $msg));

        }
    }

    //切换身份
    public function changeuser()
    {
        $userid = I('get.userid');
        $info   = M('Member')->where(['id=' . $userid])->find();
        if ($info) {
            $user_auth = [
                'uid'      => $info['id'],
                'username' => $info['username'],
                'groupid'  => $info['groupid'],
                'password' => $info['password'],
            ];
            session('user_auth', $user_auth);
            ksort($user_auth); //排序
            $code = http_build_query($user_auth); //url编码并生成query字符串
            $sign = sha1($code);
            session('user_auth_sign', $sign);
            $module['4'] = C('user');
            foreach ($this->groupId as $k => $v) {
                if ($k != 4) {
                    $module[$k] = C('agent');
                }

            }
            header('Location:' . $this->_site . $module[$info['groupid']] . '.html');
        }
    }

    //用户状态切换
    public function editStatus()
    {
        if (IS_POST) {
            $userid   = intval(I('post.uid'));
            $isstatus = I('post.isopen') ? I('post.isopen') : 0;
            $res      = M('Member')->where(['id' => $userid])->save(['status' => $isstatus]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    /**
     * 用户认证
     */
    public function authorize()
    {
        $userid = I('get.uid', 0, 'intval');
        if ($userid) {
            $data = M('Member')->where(['id' => $userid])->find();
            //上传图片
            $images = M('Attachment')
                ->where(['userid' => $userid])
                ->limit(6)
                ->field('path')
                ->order('id desc')
                ->select();
            $data['images'] = $images;
            $this->assign('u', $data);
        }
        $this->display();
    }

    //编辑用户级别
    public function editUser()
    {
        $userid = I('get.uid', 0, 'intval');
        if ($userid) {
            $data = M('Member')
                ->where(['id' => $userid])->find();
            $this->assign('u', $data);

            //用户组
            //$groups = M('AuthGroup')->field('id,title')->select();
        }
        /**
         * 升级，用户组不再与用户组关联
         * author: feng
         * create: 2017/10/19 15:03
         */

        $this->assign('merchants', C('MERCHANTS'));
        $this->display();
    }
    //保存编辑用户级别
    public function saveUser()
    {
        if (IS_POST) {
            $userid        = I('post.userid', 0, 'intval');
            $u             = $_POST['u'];
            $u['birthday'] = strtotime($u['birthday']);

            if ($userid) {
                $res = M('Member')->where(['id' => $userid])->save($u);
            } else {
                $has_user = M('member')->where(['username' => $u['username'], 'email' => $u['email'], '_logic' => 'or'])->find();
                if ($has_user) {
                    if ($has_user['username'] == $u['username']) {
                        $this->ajaxReturn(array("status" => 0, "msg" => '用户名已存在'));
                    }
                    if ($has_user['email'] == $u['email']) {
                        $this->ajaxReturn(array("status" => 0, "msg" => '邮箱已存在'));
                    }
                }

                $siteconfig = M("Websiteconfig")->find();

                foreach ($this->groupId as $k => $v) {
                    if ($u['groupid'] == $k && $u['groupid'] != 4) {
                        $u['verifycode']['regtype'] = $k;
                    }

                }
                $u                     = generateUser($u, $siteconfig);
                $u['activatedatetime'] = date("Y-m-d H:i:s");
                // 创建用户
                $res = M('Member')->add($u);
                // 发邮件通知用户密码
                sendPasswordEmail($u['username'], $u['email'], $u['origin_password'], $siteconfig);
            }

            //编辑用户组
            /*if($res){
            M('AuthGroupAccess')->where(['uid'=>$userid])->save(['group_id'=>$u['groupid']]);
            }*/
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //编辑用户费率
    public function userRateEdit()
    {
        $userid = I('get.uid', 0, 'intval');
        //系统产品列表
        $products = M('Product')
            ->where(['status' => 1, 'isdisplay' => 1])
            ->field('id,name')
            ->select();
        //用户产品列表
        $userprods = M('Userrate')->where(['userid' => $userid])->select();
        if ($userprods) {
            foreach ($userprods as $item) {
                $_tmpData[$item['payapiid']] = $item;
            }
        }
        //重组产品列表
        $list = [];
        if ($products) {
            foreach ($products as $key => $item) {
                $products[$key]['feilv']    = $_tmpData[$item['id']]['feilv'] ? $_tmpData[$item['id']]['feilv'] : '0.0000';
                $products[$key]['fengding'] = $_tmpData[$item['id']]['fengding'] ? $_tmpData[$item['id']]['fengding'] : '0.0000';
            }
        }
        $this->assign('products', $products);
        $this->display();
    }

    //保存费率
    public function saveUserRate()
    {
        if (IS_POST) {
            $userid = intval(I('post.userid'));
            $rows   = $_POST['u'];
            //print_r($rows);
            $datalist = [];
            foreach ($rows as $key => $item) {
                $rates = M('Userrate')->where(['userid' => $userid, 'payapiid' => $key])->find();
                if ($rates) {
                    $data_insert[] = ['id' => $rates['id'], 'userid' => $userid, 'payapiid' => $key, 'feilv' => $item['feilv'], 'fengding' => $item['fengding']];
                } else {
                    $data_update[] = ['userid' => $userid, 'payapiid' => $key, 'feilv' => $item['feilv'], 'fengding' => $item['fengding']];
                }
            }
            M('Userrate')->addAll($data_insert, [], true);
            M('Userrate')->addAll($data_update, [], true);
            $this->ajaxReturn(['status' => 1]);
        }
    }

    //编辑用户通道
    public function editUserProduct()
    {

        $userid = I('get.uid', 0, 'intval');
        //系统产品列表
        $products = M('Product')
            ->where(['isdisplay' => 1])
            ->field('id,name,status,paytype')
            ->select();
        //用户产品列表
        $userprods = M('Product_user')->where(['userid' => $userid])->select();
        if ($userprods) {
            foreach ($userprods as $key => $item) {
                $_tmpData[$item['pid']] = $item;
            }
        }
        //重组产品列表
        $list = [];
        if ($products) {
            foreach ($products as $key => $item) {
                $products[$key]['status']  = $_tmpData[$item['id']]['status'];
                $products[$key]['channel'] = $_tmpData[$item['id']]['channel'];
                $products[$key]['polling'] = $_tmpData[$item['id']]['polling'];
                //权重
                $weights    = [];
                $weights    = explode('|', $_tmpData[$item['id']]['weight']);
                $_tmpWeight = "";
                if (is_array($weights)) {
                    foreach ($weights as $value) {
                        list($pid, $weight) = explode(':', $value);
                        if ($pid) {
                            $_tmpWeight[$pid] = ['pid' => $pid, 'weight' => $weight];
                        }
                    }
                } else {
                    list($pid, $weight) = explode(':', $_tmpData[$item['id']]['weight']);
                    if ($pid) {
                        $_tmpWeight[$pid] = ['pid' => $pid, 'weight' => $weight];
                    }
                }
                $products[$key]['weight'] = $_tmpWeight;
            }
        }
        $this->assign('products', $products);
        $this->display();
    }
    //保存编辑用户通道
    public function saveUserProduct()
    {
        if (IS_POST) {
            $userid = I('post.userid', 0, 'intval');
            $u      = $_POST['u'];
            foreach ($u as $key => $item) {
                $weightStr = '';
                $status    = $item['status'] ? $item['status'] : 0;
                if (is_array($item['w'])) {
                    foreach ($item['w'] as $weigths) {
                        if ($weigths['pid']) {
                            $weightStr .= $weigths['pid'] . ':' . $weigths['weight'] . "|";
                        }
                    }
                }
                $product = M('Product_user')->where(['userid' => $userid, 'pid' => $key])->find();
                if ($product) {
                    $data_insert[] = ['id' => $product['id'], 'userid' => $userid, 'pid' => $key, 'status' => $status, 'polling' => $item['polling'], 'channel' => $item['channel'], 'weight' => trim($weightStr, '|')];
                } else {
                    $data_update[] = ['userid' => $userid, 'pid' => $key, 'status' => $status, 'polling' => $item['polling'], 'channel' => $item['channel'], 'weight' => trim($weightStr, '|')];
                }
            }
            M('Product_user')->addAll($data_insert, [], true);
            M('Product_user')->addAll($data_update, [], true);
            $this->ajaxReturn(['status' => 1]);
        }
    }

    //提现
    public function userWithdrawal()
    {
        $userid = I('get.uid', 0, 'intval');
        $data   = M('Tikuanconfig')->where(['userid' => $userid])->find();
        $this->assign('u', $data);
        $this->display();
    }
    //保存提现规则
    public function saveWithdrawal()
    {
        if (IS_POST) {
            $userid = I('post.userid', 0, 'intval');
            $id     = I('post.id', 0, 'intval');
            if ($_POST['u']['systemxz']) {
                $rows = $_POST['u'];
            } else {
                $rows['systemxz'] = 0;
            }
            if ($id) {
                $res = M('Tikuanconfig')->where(['id' => $id, 'userid' => $userid])->save($rows);
            } else {
                $rows['userid'] = $userid;
                $res            = M('Tikuanconfig')->add($rows);
            }
            $this->ajaxReturn(['status' => $res]);
        }
    }
    //解冻费率
    public function thawingFunds()
    {

        $allowstart = $configs['allowstart'] ? $configs['allowstart'] : 1;
        $allowend   = $configs['allowend'] ? $configs['allowend'] : 5;
        //计划执行

        $curtime            = strtotime('today');
        $yesterday          = strtotime('yesterday');
        $maps['thawtime']   = array('elt', $curtime + 7200);
        $maps['createtime'] = array('lt', $curtime);
        $maps['status']     = array('eq', 0);
        $data               = M('blockedlog')->where($maps)->limit(600)->order('id asc')->select();

        $i = 0;
        if ($data) {
            foreach ($data as $item) {
                $rows                   = array();
                $rows['balance']        = array('exp', "balance+{$item['amount']}");
                $rows['blockedbalance'] = array('exp', "blockedbalance-{$item['amount']}");

                //开启事务
                $Model = M();
                $Model->startTrans();

                //更新资金
                $upRes = $Model->table('pay_member')->where(['id' => $item['userid']])->save($rows);

                //更新状态
                $uplog = $Model->table('pay_blockedlog')->where(array('id' => $item['id']))->save(array('status' => 1));

                //增加记录
                $data               = array();
                $data['userid']     = $item['userid'];
                $data['money']      = $item['amount'];
                $data['datetime']   = date("Y-m-d H:i:s");
                $data['tongdao']    = $item['pid'];
                $data['transid']    = $item['orderid']; //交易流水号
                $data['orderid']    = $item['orderid'];
                $data['lx']         = 8; //解冻
                $data['contentstr'] = "订单金额解冻";
                $change             = $Model->table('pay_moneychange')->add($data);

                //提交事务
                if ($upRes && $uplog && $change) {
                    $i++;
                    $Model->commit();
                } else {
                    $Model->rollback();
                }
            }
        }
        $this->ajaxReturn(['status' => 'ok', 'msg' => '解冻了' . $i . '条数据']);

    }

    public function saveAddDomain()
    {
        $Member = M('Member');
        if (IS_POST) {
            $domain = I('post.domain', 'trim');
            $id     = I('post.id', '');
            $result = $Member->where(['id' => $id])->save(['domain' => $domain]);
            $this->ajaxReturn(['status' => $result]);
        } else {
            $uid = I('get.userid', '');

            $domain = $Member->where(['id' => $uid])->getField('domain');
            $this->assign('domain', $domain);
            $this->assign('id', $uid);
            $this->display();
        }
    }
}
