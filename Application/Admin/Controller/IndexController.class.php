<?php
namespace Admin\Controller;

class IndexController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    //首页
    public function index()
    {
        $Websiteconfig = D("Websiteconfig");
        $withdraw      = $Websiteconfig->getField("withdraw");

        $diffModel = D('Diff');
        $web_name = $diffModel->where(['key' => 'web_name'])->getField('value');

        $this->assign('web_name', $web_name);
        $this->assign("withdraw", $withdraw);
        $this->display();
    }

    //main
    public function main()
    {
        //日报
        $_data['today'] = date('Y年m月d日');
        $_data['month'] = date('Y年m月');

        $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $endThismonth   = mktime(23, 59, 59, date('m'), date('t'), date('Y'));

        //实时统计
        $orderWhere = [
            'pay_status'      => ['between', [1,2]],
            'pay_successdate' => [
                'between',
                [
                    strtotime('today'),
                    strtotime('tomorrow'),
                ],
            ],
        ];
        $orderModel = DM("Order", "Slave");
        $ddata = $orderModel
            ->field([
                'sum(`pay_amount`) amount',
                'sum(`pay_poundage`) rate',
                'sum(`pay_actualamount`) total',
            ])->where($orderWhere)
            ->find();

        $ddata['num'] = $orderModel->where($orderWhere)->count();

        //7天统计
        $lastweek = time() - 7 * 86400;
        $sql      = "select COUNT(id) as num,SUM(pay_amount) AS amount,SUM(pay_poundage) AS rate,SUM(pay_actualamount) AS total from pay_order where  1=1 and pay_status>=1 and DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(FROM_UNIXTIME(pay_successdate,'%Y-%m-%d')) and pay_successdate>=$lastweek; ";
        $wdata    = $orderModel->query($sql);

        //按月统计
        $lastyear = strtotime(date('Y-1-1'));
        $sql      = "select FROM_UNIXTIME(pay_successdate,'%Y年-%m月') AS month,SUM(pay_amount) AS amount,SUM(pay_poundage) AS rate,SUM(pay_actualamount) AS total from pay_order where  1=1 and pay_status>=1 and pay_successdate>=$lastyear GROUP BY month;  ";
        $_mdata   = $orderModel->query($sql);
        $mdata    = [];
        foreach ($_mdata as $item) {
            $mdata['amount'][] = $item['amount'] ? $item['amount'] : 0;
            $mdata['mdate'][]  = "'" . $item['month'] . "'";
            $mdata['total'][]  = $item['total'] ? $item['total'] : 0;
            $mdata['rate'][]   = $item['rate'] ? $item['rate'] : 0;
        }

        $this->assign('ddata', $ddata);
        $this->assign('wdata', $wdata[0]);
        $this->assign('mdata', $mdata);
        $this->display();
    }

    /**
     * 清除缓存
     */
    public function clearCache()
    {
        $dir = RUNTIME_PATH_CACHE;
        $this->delCache($dir);
        $this->success('缓存清除成功！');
	/*
	$groupid = session('admin_auth.groupid');
        if ($groupid == 1) {
            $dir = RUNTIME_PATH;
            $this->delCache($dir);
            $this->success('缓存清除成功！');
        } else {
            $this->error('只有总管理员能操作！');
        }
	*/
    }

    /**
     * 删除缓存目录
     * @param $dirname
     * @return bool
     */
    protected function delCache($dirname)
    {
        $result = false;
        if (!is_dir($dirname)) {
            echo " $dirname is not a dir!";
            exit(0);
        }
        $handle = opendir($dirname); //打开目录
        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                //排除"."和"."
                $dir = $dirname . DIRECTORY_SEPARATOR . $file;
                is_dir($dir) ? self::delCache($dir) : unlink($dir);
            }
        }
        closedir($handle);
        $result = rmdir($dirname) ? true : false;
        return $result;
    }

}