<?php 
namespace Pay\Controller;
class TestController{
    public function dd(){
        echo "string";
        //$this->test('10002');
    }

    public function get_between($input, $start, $end) {
        $substr = substr($input, strlen($start)+strpos($input, $start),(strlen($input) - strpos($input, $end))*(-1));

        echo '---'.$substr;
    }
    public function test()
    {
        $referer      = $_SERVER["HTTP_REFERER"]; // 获取完整的来路URL
        echo $referer."<br>";//http://39.108.91.92/demo/index1.php
$re = parse_url($referer);
dump($re);
        /*array(3) {
  ["scheme"] => string(4) "http"
  ["host"] => string(14) "120.78.148.129"
  ["path"] => string(9) "/demo.php"
}*/
         $this->get_between($referer,'39','92');
        
    }

}
