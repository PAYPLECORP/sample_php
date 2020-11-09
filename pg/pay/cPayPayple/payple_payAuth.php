<?php
header("Expires: Mon 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d, M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0; pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: application/json; charset=utf-8");

/*
 * payple_dir_path : cPayPayple 폴더 경로
 * ORDER PAGE(ex: order_confirm.html) 의 CpayForm 에  payple_dir_path 값을 지정하거나
 * 여기서 직접 지정할 수 있습니다.
 */
$payple_dir_path = (isset($_POST['payple_dir_path'])) ? $_POST['payple_dir_path'] : "";
//$payple_dir_path = "/pg/pay";

// AWS 와 같은 클라우드 서버의 경우 REFERE 추가
$CURLOPT_HTTPHEADER = array(
    "cache-control: no-cache",
    "content-type: application/json; charset=UTF-8",
    "referer: https://test.aaa.com"
);

// 발급받은 비밀키. 유출에 주의하시기 바랍니다.
// payple_dir_path : cPayPayple 설치 경로
$post_data = array (
    "cst_id" => $cst_id,
    "custKey" => $custKey
);
    

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                 //  인증결과가 수신이 안되는 경우에 따라 주석해제

ob_start();
$res = curl_exec($ch);
$buffer = ob_get_contents();
ob_end_clean();

if (!$buffer) {
    $returnVal = "";
} else {
    echo $buffer;
}
?>
