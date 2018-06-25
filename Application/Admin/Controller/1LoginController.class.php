<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-15
 * Time: 22:27
 */
namespace Admin\Controller;

use Think\Controller;
use Think\Verify;

class LoginController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->assign('siteurl', $this->_site);
        $this->assign('sitename', C('WEB_TITLE'));
    }

    //登录页
    public function index()
    {
        $this->display();
    }

    //登录检查
    public function checkLogin()
    {
        if (IS_POST) {
            $username      = I("post.username");
            $loginpassword = I("post.password");
            $verification  = I("post.verify");
            $verify        = new Verify();
            if (empty($username) || empty($loginpassword)) {
                $this->ajaxReturn(['errorno' => 1, 'msg' => '账号和密码不能为空！', 'url' => '']);
            }
            // //验证码校验
            if (!$verify->check($verification)) {
                $this->ajaxReturn(['errorno' => 1, 'msg' => '验证码输入错误！', 'url' => '']);
            }
            $admin = M("admin");
            $info  = $admin->where(array("username" => $username, "password" => md5($loginpassword . C('DATA_AUTH_KEY'))))->find();
            
            if ($info) {

                // 登录记录
                $rows['userid']        = $info['id'];
                $rows['logindatetime'] = date("Y-m-d H:i:s");
                $Ip                    = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
                $location              = $Ip->getlocation(); // 获取某个IP地址所在的位置
                $Websiteconfig = M('Websiteconfig');
                $loginIp = $Websiteconfig->where(['id'=>1])->getField('login_ip');   

                if (trim($loginIp)) {
                    $ipItem = explode("|", $loginIp);
                    if (!in_array($location['ip'], $ipItem)) {
                         $this->ajaxReturn(['errorno' => 1, 'msg' => '登录ip错误', 'url' => '']);
                    }

                }
               

                $rows['loginip']      = $location['ip'];
                $rows['loginaddress'] = $location['country'] . "-" . $location['area'];
                M("Loginrecord")->add($rows);

                $admin_indo = [
                    'uid'      => $info['id'],
                    'username' => $info['username'],
                    'groupid'  => $info['groupid'],
                    'password' => $info['password'],
                ];

                session('admin_auth', $admin_indo);
                //session auth
                ksort($admin_indo); //排序
                $code = http_build_query($admin_indo); //url编码并生成query字符串
                $sign = sha1($code);
                session('admin_auth_sign', $sign);

                $this->ajaxReturn(['errorno' => 0, 'msg' => '登录成功!', 'url' => U('Index/index')]);
            } else {
                $this->ajaxReturn(['errorno' => 1, 'msg' => '你的帐号或密码不正确！', 'url' => '']);
            }

        }
    }
    //退出登录
    public function loginout()
    {
        session('admin_auth', null);
        $this->success('正在退出...', '/' . C("LOGINNAME"));
    }

    //验证码
    public function verifycode()
    {
        $config = array(
            'length' => 4, // 验证码位数
            'useNoise' => false, // 关闭验证码杂点
            'useImgBg' => false, // 使用背景图片
            'useZh' => false, // 使用中文验证码
            'useCurve' => false, // 是否画混淆曲线
            'useNoise' => true,// 是否添加杂点
            'expire'    =>  3600,
            'codeSet'   =>  '0123456789',
        );
        ob_end_clean();
        $verify = new Verify($config);
        $verify->entry();
    }

    //升级
    public function upgrade()
    {
        $users = M('User')
            ->join('LEFT JOIN __USERBASICINFO__ ON __USERBASICINFO__.userid = __USER__.id')
            ->join('LEFT JOIN __USERPASSWORD__ ON __USERPASSWORD__.userid = __USERBASICINFO__.userid')
            ->join('LEFT JOIN __USERVERIFYINFO__ ON __USERVERIFYINFO__.userid = __USERBASICINFO__.userid')
            ->field('pay_user.*, pay_userbasicinfo.fullname, pay_userbasicinfo.sex,pay_userbasicinfo.birthday,pay_userbasicinfo.sfznumber,pay_userbasicinfo.phonenumber,pay_userbasicinfo.qqnumber,pay_userbasicinfo.address,pay_userpassword.loginpassword,pay_userpassword.paypassword,pay_userverifyinfo.uploadsfzzm,pay_userverifyinfo.uploadsfzbm,pay_userverifyinfo.uploadscsfz,pay_userverifyinfo.uploadyhkzm,pay_userverifyinfo.uploadyhkbm,pay_userverifyinfo.uploadyyzz,pay_userverifyinfo.status as rzstatus,pay_userverifyinfo.domain,pay_userverifyinfo.md5key')
            ->select();
        foreach ($users as $info) {
            if ($info['id'] != 1) {
                $rows[] = [
                    'id'               => $info['id'],
                    'username'         => $info['username'],
                    'groupid'          => $info['usertype'],
                    'parentid'         => $info['superioruserid'],
                    'email'            => $info['email'],
                    'status'           => $info['status'],
                    'activate'         => $info['activate'],
                    'regdatetime'      => $info['regdatetime'],
                    'activatedatetime' => $info['activatedatetime'] ? $info['activatedatetime'] : 0,
                    'realname'         => $info['fullname'] ? $info['fullname'] : 'NULL',
                    'sex'              => $info['sex'] ? $info['sex'] : 1,
                    'birthday'         => strtotime($info['birthday']),
                    'sfznumber'        => $info['sfznumber'] ? $info['sfznumber'] : 'NULL',
                    'mobile'           => $info['phonenumber'] ? $info['phonenumber'] : 'NULL',
                    'qq'               => $info['qqnumber'] ? $info['qqnumber'] : 'NULL',
                    'address'          => $info['address'] ? $info['address'] : 'NULL',
                    'password'         => $info['loginpassword'] ? $info['loginpassword'] : md5('123456'),
                    'paypassword'      => $info['paypassword'] ? $info['paypassword'] : md5('123456'),
                    'uploadsfzzm'      => $info['uploadsfzzm'] ? $info['uploadsfzzm'] : 'NULL',
                    'uploadsfzbm'      => $info['uploadsfzbm'] ? $info['uploadsfzbm'] : 'NULL',
                    'uploadscsfz'      => $info['uploadscsfz'] ? $info['uploadscsfz'] : 'NULL',
                    'uploadyhkzm'      => $info['uploadyhkzm'] ? $info['uploadyhkzm'] : 'NULL',
                    'uploadyhkbm'      => $info['uploadyhkbm'] ? $info['uploadyhkbm'] : 'NULL',
                    'uploadyyzz'       => $info['uploadyyzz'] ? $info['uploadyyzz'] : 'NULL',
                    'authorized'       => $info['rzstatus'] ? $info['rzstatus'] : 'NULL',
                    'domain'           => $info['domain'] ? $info['domain'] : 'NULL',
                    'apikey'           => $info['md5key'] ? $info['md5key'] : 'NULL',
                    'status'           => $info['status'],
                ];
            }
        }
        $res = M('Member')->addAll($rows, [], true);
        exit('ok');
    }
}
