<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-04-02
 * Time: 23:01
 */
namespace Admin\Controller;

/**
 * 提现控制器
 * Class WithdrawalController
 * @package Admin\Controller
 */
class ScriptController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        echo "处理中！。。。";
        $result      = false;
        $i           = 0;
        $model       = M();
        $Order       = M('Order');
        $sql         = 'SELECT `rate`, `code` FROM `pay_channel` WHERE `rate`!=0';
        $channelList = $model->query($sql);
        while (!$result) {
            $sql       = 'SELECT `id`, `pay_tongdao`, `pay_amount` FROM `pay_order` WHERE  `cost`=0 AND `cost_rate`=0 AND `pay_status` BETWEEN 1 AND 2 LIMIT ' . $i . ', 1000';
            $orderList = $model->query($sql);

            if (!$orderList) {
                $result = true;
            }else{
                foreach ($orderList as $k1 => $v1) {
                    foreach ($channelList as $k2 => $v2) {
                        $cost = bcmul($v1['pay_amount'], $v2['rate'], 2);
                        if ($v1['pay_tongdao'] == $v2['code'] && $cost != 0) {
                            $Order->where(['id' => $v1['id']])->save(['cost' => $cost, 'cost_rate' => $v2['rate']]);
                        }

                    }
                }
                $i += 1000;
                echo '<p>已经计算了' . $i . '条数据</p>';
                sleep(1);
            }
        }
        echo "成功！";
    }
}
