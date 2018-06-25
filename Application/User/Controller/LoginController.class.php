<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace User\Controller;
use Think\Verify;
/**
 * 用户登录控制器
 * Class LoginController
 * @package Home\Controller
 */
class LoginController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 商户登录、代理商登录
     */
    public function index()
    {
        $loginUrl = U(__MODULE__ . "/Login/checklogin");

        if (strpos(strtolower($_SERVER['REQUEST_URI']), 'agent') != false){ //代理商登录
            $loginTitle = '代理商登录';
        }elseif(strpos(strtolower($_SERVER['REQUEST_URI']), 'user') != false){ //商户登录
            $loginTitle = '商户登录';
        }else{
            $loginTitle = '账户登录';
        }

        $diffModel = D('Diff');
        $web_name = $diffModel->where(['key' => 'web_name'])->getField('value');
        $bg_login_img = $diffModel->where(['key' => 'bg_login_img'])->getField('value');

        $this->assign('web_name', $web_name);
        $this->assign('bg_login_img', $bg_login_img);
        $this->assign('loginTitle', $loginTitle);
        $this->assign('loginUrl',$loginUrl);
        $this->display();
    }

    /**
     * 登录验证
     */
    public function checklogin()
    {
        if(IS_POST) {
            if(strtolower(trim(__MODULE__, '/')) == C('user')){

                //代理商户
                $this->check([4]);
            }else if(strtolower(trim(__MODULE__, '/')) == C('agent')){
                //普通商户
                $this->check([5,6,7]);
            }
        }
    }

    /**
     * 检查登录
     * @param  [type] 代理类型 4=>普通商户 5=>代理商户
     * @return [type]
     */
    private function check($type)
    {
        $username = I("post.username", "", 'trim');
        $password = I("post.password");
        $varification = I("post.varification");
        $cookiename = I("post.cookiename");
        if (!$username || !$password || !$varification) {
            $this->error( '用户名、密码输入有误！');
        }
        //验证码
        $verify = new Verify();
        if (!$verify->check($varification)) {
            $this->error( '验证码输入有误！');
        }
        $fans = M('Member')->where(['username'=>$username, 'groupid'=>['in',$type]])->find();
        //不存在
        if(!$fans || $fans['status'] != 1){
            $this->error('用户账号异常！');
        }
        //密码验证
        if(md5($password.$fans['salt']) != $fans['password']){
            $this->error('密码输入有误！');
        }

        //用户登录
        $user_auth = [
            'uid'=>$fans['id'],
            'username'=>$fans['username'],
            'groupid'=>$fans['groupid'],
            'password'=>$fans['password']
        ];
        session('user_auth',$user_auth);
        ksort($user_auth); //排序
        $code = http_build_query($user_auth); //url编码并生成query字符串
        $sign = sha1($code);
        session('user_auth_sign',$sign);

        // 登录记录
        $rows['userid'] = $fans['id'];
        $rows['logindatetime'] = date("Y-m-d H:i:s");
        //旧的获取地区数据
        // $Ip = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
        // $location = $Ip->getlocation(); // 获取某个IP地址所在的位置
        $ip = get_client_ip();
        $location = \Org\Net\NIpLocation::find($ip);//返回式一个数组，索引0 国家 1省份 2城市
        $rows['loginip'] = $ip;
        $rows['loginaddress'] = $location[1] . "-" . $location[2];
        //常用地址
        $localCountry = [];
        //获取最近登录地址
        $latestLoginData = M("Loginrecord")->where(['userid' => $fans['id']])->order('id desc')->limit(3)->select();
        $address = @array_column((array)$latestLoginData, 'loginaddress', 'id');
        $country = @array_map(function($item){
            $adress = explode('-', $item);
            return $adress[1];//0为省份 1为城市
        }, $address);
        //获取数组中的重复数据
        $repeatItem = @array_unique($country);
        if($repeatItem){
            //获取最近三次登录重复的地址
            $localCountry = array_diff_assoc($country, $repeatItem);
        }
        //如果异地登录就发送通知信息
//        $sms_is_open = smsStatus();
//        $product = ['time' => date('Y-m-d H:i:s'), 'address'=>$location[1].$location[2]];
//        if($localCountry && !in_array($location[2], $localCountry) && $fans['mobile'] && $sms_is_open){
//            $ret = $this->sendStr('loginWarning', $fans['mobile'], $product);
//        }else if($localCountry && !in_array($location[2], $localCountry) && $fans['email']){
//            $message = "您的账号于{$product['time']}登录异常，异常登录地址：{$product['address']}，如非本人操纵，请及时修改账号密码。";
//            $ret = sendEmail($fans['email'], '知宇软件', $message);
//        }

        M("Loginrecord")->add($rows);

        $this->success('登录成功',U(__MODULE__ . '/Index/index'));
    }

    /**
     * 登出
     */
    public function loginout()
    {
        $user_auth = session('user_auth');
        $url = U(__MODULE__ . '/Login/index');
        session('user_auth',null);
        session('user_auth_sign',null);
        $this->success('正在退出...', $url);
    }

    /**
     * 注册
     */
    public function register()
    {
        $this->display();
    }

    /**
     * 注册表单
     */
    public function checkRegister()
    {
        if(IS_POST){
            $username = I('post.username');
            $password = I('post.password');
            $confirmpassword = I('post.confirmpassword');
            $email = I('post.email');
            $invitecode = I('post.invitecode','','trim');

            if($password != $confirmpassword){
                $this->ajaxReturn(['errono'=>10002,'msg'=>'密码输入不一致!']);
            }

            //邀请码验证
            if($this->siteconfig['invitecode']){
                $verifycode = M('Invitecode')
                    ->where(['invitecode'=>$invitecode,'status'=>1,'yxdatetime'=>array('egt', time())])
                    ->find();
                if(!$verifycode){
                    $this->ajaxReturn(array('errorno'=>10001,'msg'=>'邀请码无效!'));
                }
            }
            $isuserid = M("Member")->where(['username'=>$username])->getField("id");
            if($isuserid){
                $this->ajaxReturn(array('errorno'=>10005,'msg'=>'用户名重复!'));
            }

            $user = [
                'username' => $username,
                'password' => $password,
                'email' => $email,
                'verifycode' => $verifycode,
            ];
            $userdata = generateUser($user, $this->siteconfig);

            $newuid = M('Member')->add($userdata);
            //添加用户组权限
            /**
             * 不需要使用用户权限
             * author: feng
             * create: 2017/10/21 10:47
             */
            //M('AuthGroupAccess')->add(['uid'=>$newuid,'group_id'=>$_verfycode['regtype'] ? $_verfycode['regtype'] :4]);

            //失效邀请码
            $_failinvitecode = array('syusernameid' => $newuid, 'sydatetime' => time(), 'status' => 2);
            M('Invitecode')->where("invitecode = '" . $invitecode . "'")->save($_failinvitecode);
            //发送注册激活邮件
            $returnEmail = sendRegemail($username, $email, $userdata['activate'],$this->siteconfig);
            if($returnEmail){
                $tel = $this->siteconfig["tel"];
                $qqlist = $this->siteconfig['qq'];
                $mail = explode('@',$email)[1];
                $this->ajaxReturn(array('errorno' => 0, 'msg' => array('tel' => $tel, 'qq' => $qqlist, 'email' => $email,'mail'=>'http://mail.'.$mail)));
            }else{
                $this->ajaxReturn(['errorno'=>10003,'msg'=>$returnEmail]);
            }
        }else{
            $this->ajaxReturn(array('errorno'=>10004,'msg'=>'注册失败'));
        }
    }

    /**
     * 用户名验证
     */
    public function checkuser()
    {
        $username = I("post.username");
        $userid = M("Member")->where(['username'=>$username])->getField("id");
        $valid = true;
        if ($userid) {
            $valid = false;
            echo json_encode(array('valid' => $valid));
        } else {
            echo json_encode(array('valid' => $valid));
        }
    }

    /**
     * email 验证
     */
    public function checkemail()
    {
        exit("");
        //被sql注入风险
        $email = I("post.email");
        $userid = M("Member")->where("email='" . $email . "'")->getField("id");
        $valid = true;
        if ($userid) {
            $valid = false;
            echo json_encode(array('valid' => $valid));
        } else {
            echo json_encode(array('valid' => $valid));
        }
    }

    /**
     * 邀请码验证
     */
    public function checkinvitecode()
    {
        $invite_code = I("post.invitecode");
        $Invitecode = M("Invitecode");
        $where['invitecode'] = $invite_code;
        $where['status'] = 1;
        $where['yxdatetime'] = array('egt', time());
        $id = $Invitecode->where($where)->getField("id");
        $valid = true;
        if ($id) {
            echo json_encode(array('valid' => $valid));
        } else {
            $valid = false;
            echo json_encode(array('valid' => $valid));
        }
    }

    /**
     * 验证码
     */
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

    /**
     * 验证码验证
     */
    public function checkverify()
    {
        $code = I("request.code", "");
        $verify = new Verify();
        if ($verify->check($code)) {
            exit("ok");
        } else {
            exit("no");
        }
    }

    public function forgetpwd(){
        if(IS_POST){
            $username=I("post.username");
            $password = I('post.password');
            $confirmpassword = I('post.confirmpassword');
            $email = I('post.email');
            $code = I('post.varification','','trim');
            if(!$username){
                $this->ajaxReturn(array('status'=>0,'msg'=>'用户名不能为空'));
            }
            if(!$email){
                $this->ajaxReturn(array('status'=>0,'msg'=>'邮箱不能为空'));
            }
            if(!$code){
                $this->ajaxReturn(array('status'=>0,'msg'=>'验证码不能为空'));
            }
            if(!$password||!$confirmpassword){
                $this->ajaxReturn(array('status'=>0,'msg'=>'密码不能为空'));
            }
            if($password != $confirmpassword){
                $this->ajaxReturn(['status'=>0,'msg'=>'密码输入不一致!']);
            }
            $codemodel=M("user_code")->where(['username'=>$username,'email'=>$email,'code'=>$code,'status'=>0,'type'=>0,'endtime'=>array('gt',time())])->order('id desc')->find();
            if(!$codemodel){
                $this->ajaxReturn(array('status'=>0,'msg'=>'验证码不正确或过期'));
            }
            $member= M("member")->field('id,salt')->where(array("username"=>$username,"email"=>$email))->find();
            if($member&&M("member")->where("id=".$member["id"])->setField("password",md5($password.$member['salt']))!==false){
                M("user_code")->where(['id'=>$codemodel["id"]])->save(array("status"=>1,"uptime"=>time()));
                $this->ajaxReturn(['status'=>1,'msg'=>'修改成功!']);
            }else{
                $this->ajaxReturn(['status'=>0,'msg'=>'修改失败!']);
            }

        }
        $this->display();
    }

    /**
     * 发送邮箱验证码
     * author: feng
     * create: 2017/10/19 10:21
     */
    public function sendUserCode(){
        $username=I("post.username");
        $email=I("post.email");
        if(!$username){
            $this->ajaxReturn(array('status'=>0,'msg'=>'用户名不能为空'));
        }
        if(!$email){
            $this->ajaxReturn(array('status'=>0,'msg'=>'邮箱不能为空'));
        }
        $member= M("member")->where(array("username"=>$username,"email"=>$email))->find();
        if(!$member){
            $this->ajaxReturn(array('status'=>0,'msg'=>'用户或邮箱不正确'));
        }
        $code=rand(10000,99999);
        $returnEmail = sendFindpwdemail($username, $email, $code,$this->siteconfig);
        if($returnEmail){
            $curTime=time();
            $data=array("type"=>"0",
                "code"=>$code,
                "username"=>$username,
                "email"=>$email,
                "status"=>0,
                "ctime"=>time(),
                "endtime"=>($curTime+600)
            );
            if(M("user_code")->add($data)){
                $this->ajaxReturn(array('status'=>1,'msg'=>'发送邮件成功'));
            }
        }
        $this->ajaxReturn(array('status'=>0,'msg'=>'发送邮件失败'));





    }


}