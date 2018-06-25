<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace Payment\Controller;

/**
 * 用户中心首页控制器
 * Class IndexController
 * @package User\Controller
 */
class IndexController extends PaymentController{



    public function __construct(){
        parent::__construct();
        $_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
    }

    /**
     * 提交代付,代付查询的统一地址
     */
    public function index(){
        //判断是否登录
        isLogin();
        //验证传来的数据
        $post_data = verifyData($this->verify_data_);
        //获取要操作的订单id
        $post_data['id'] = explode(',', rtrim($post_data['id'], ',') );
		
        //根据操作查询不同状态的订单
        $status = $post_data['opt'] == 'exec'?'0':'1';
		
        $where = ['id'=>['in', $post_data['id']], 'status'=>$status];
        $wttk_lists = $this->selectOrder($where);
		
		$post_data['code'] = $post_data['opt'] == 'exec'?$post_data['code']:$wttk_lists[0]['df_id'];
		
		//获取要代付的通道信息
        $pfa_list = $this->findPaymentType($post_data['code']);
		
        //检查代付金额与用户金额是否相同
        $this->checkMoney($wttk_lists['userid'] , $wttk_lists['money']);
		
        //判断代付通道的文件是否存在
        $code = $pfa_list['code'];
        $code || showError('代付渠道不存在！');
        $file = APP_PATH . MODULE_NAME . '/Controller/' . $code . 'Controller.class.php';
        is_file($file) || showError('代付渠道不存在！');
        //循环存在代付通道的文件限制一次只能操作15条数据
        $opt = ucfirst( $post_data['opt']);
        if( count($wttl_lists)<= 15){
            $fp = fopen($file, "r");
            foreach($wttk_lists as $k => $v){
                //开启文件锁防止多人操作重复提交
                if( flock($fp,LOCK_EX) ) {
                    $result = R($code.'/Payment' . $opt, [$v, $pfa_list]);
                    $result!==FALSE || showError('服务器请求失败！');
                    if(is_array($result)){
                        $cost = $pfa_list['rate_type'] ? bcmul($v['tkmoney'], $pfa_list['cost_rate'], 2):$pfa_list['cost_rate'];
                        $data = [
                            'msg'       => $result['msg'],
                            'df_id'     => $pfa_list['id'],
                            'code'      => $pfa_list['code'],
                            'df_name'   => $pfa_list['title'],
                            'cost_rate' => $pfa_list['cost_rate'],
                            'cost'      => $cost,
                            'rate_type'=>$pfa_list['rate_type'],
                        ];

                        //更新提交代付时间
                        if(isset($result['daifu_time']) && $result['daifu_time']>0){
                            $data['daifu_time'] = $result['daifu_time'];
                        }

                        $this->handle($v['id'], $result['status'], $data);
                    }
                }
                flock($fp,LOCK_UN);
            }
            fclose($fp);
            showSuccess('请求成功！');
            exit;
        }
        showError('只能同时请求15条代付数据！');
    }

    //定时任务-查询上游代付订单
    public function evenQuery(){
        $where = ['status'=>1];
        $wttk_lists = $this->selectOrder($where);
        foreach($wttk_lists as $k => $v){
            $file = APP_PATH . MODULE_NAME . '/Controller/' . $v['code'] . 'Controller.class.php';
            if( is_file($file) ){
                $pfa_list = $this->findPaymentType($v['df_id']);
                $result = R($v['code'].'/PaymentQuery', [$v, $pfa_list]);
                $result!==FALSE || showError('服务器请求失败！');
                if(is_array($result)){
                    $data = [
                        'msg'       => $result['msg'],
                        'df_id'     => $pfa_list['id'],
                        'code'      => $pfa_list['code'],
                        'df_name'   => $pfa_list['title'],
                    ];
                    $this->handle($v['id'], $result['status'], $data);
                }
            }
            sleep(3);
        }
    }
    
}