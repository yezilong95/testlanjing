<?php
namespace Admin\Controller;

class EntryController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(){


       $this->display();
    }

    public function EntryAdd(){
        $data = I("post.",'');
        $dataSave = $this->delhash($data);
        $entry_DB = M("entry");
        if($dataSave){
            $result = $entry_DB->add($dataSave);
            $result || showError('提交失败!');
        }else{
            showError('参数错误!');
        }


    }

    public function EntryList(){
        echo 'ds2';
    }

    protected function delhash($params){
        $sign_str = '';
        // 排序
       // ksort($params);

        $buff = "";
        foreach ($params as $k => $v)
        {
            if($k != "__hash__" && $v != "" && !is_array($v)){
                $buff[$k]= $v;
            }
        }
        return $buff;
    }

}