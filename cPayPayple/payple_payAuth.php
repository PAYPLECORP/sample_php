<?php
header("Expires: Mon 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d, M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0; pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: application/json; charset=utf-8");


// 발급받은 비밀키. 유출에 주의하시기 바랍니다.
$post_data = array (
    "cst_id" => "",
    "custKey" => ""
);

$ch = curl_init('https://testcpay.payple.kr/php/auth.php');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_REFERER, $SERVER_NAME);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);


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
