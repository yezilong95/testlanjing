<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-04-02
 * Time: 23:01
 */

namespace Admin\Controller;

use Think\Auth;
use Think\Controller;

/**
 * 后台入口控制器
 * Class BaseController
 * @package Admin\Controller
 */

class BaseController extends Controller{
    /**
     * 初始化控制器
     * BaseController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // 获取当前用户ID
        if(defined('UID')) return ;
        define("UID",is_login());
        if( !UID ){// 还没登录 跳转到登录页面
            $this->redirect('Login/index');
        }
        // 是否是超级管理员
        define('IS_ROOT',   is_rootAdministrator());
        if(!IS_ROOT && C('ADMIN_ALLOW_IP')){
            // 检查IP地址访问
            if(!in_array(get_client_ip(),explode(',',C('ADMIN_ALLOW_IP')))){
                $this->error('403:禁止访问');
            }
        }
        //权限检查
        $user_info = session('admin_auth');
        $name = CONTROLLER_NAME . '/' . ACTION_NAME;
        if(CONTROLLER_NAME != 'Login' && !IS_ROOT&&$name!="System/editPassword"){
            $auth = new Auth();
            $auth_result = $auth->check($name, $user_info['uid']);
            if($auth_result === false){
                if(IS_AJAX){
                    $this->error('没有权限!');
                }else{
                    $this->error('没有权限!');
                }
            }
        }
        $this->groupId =  C('GROUP_ID');
        //获取用户的代理等级信息
        $this->assign('groupId',$this->groupId);
        //左侧菜单栏
        $admin_auth_group_access_model = D('AdminAuthGroupAccess');
        $navmenus = $admin_auth_group_access_model->getUserRules($user_info['uid']);
        $this->assign('navmenus', $navmenus);
        $this->_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
        $this->assign('siteurl',$this->_site);
        $this->assign('sitename',C('WEB_TITLE'));
        $this->assign('member',$user_info);
        $this->assign('installpwd',md5('adminadmin'.C('DATA_AUTH_KEY')));
        $this->assign('model',C('HOUTAINAME')?C('HOUTAINAME'):MODULE_NAME);
    }
}