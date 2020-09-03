<?php
/*
 * 외부에서 직접 접속하여 실행되지 않도록 프로그래밍 하여 주시기 바랍니다.
 * cst_id, custKey, AuthKey 등 접속용 key 는 절대 외부에 노출되지 않도록
 * 서버 사이드 스크립트(server-side script) 내부에서 사용되어야 합니다.
 */
include $_SERVER['DOCUMENT_ROOT'] . '/payple/inc/config.inc';
header("Expires: Mon 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d, M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0; pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: application/json; charset=utf-8");

try {
	
	##################################################### AuthKey REQ #####################################################
		
	//발급받은 비밀키. 유출에 주의하시기 바랍니다.
	$post_data = array (
			"cst_id" => $cst_id,
			"custKey" => $custKey,
			"PCD_PAY_WORK" => "PUSERDEL",   /*(고정)업무구분 : PUSERDEL*/
	);
	
	// content-type : application/json
	// json_encoding...
	$post_data = json_encode($post_data);
	
	// cURL Header
	$CURLOPT_HTTPHEADER = array(
			"cache-control: no-cache",
			"content-type: application/json; charset=UTF-8",
			"referer: https://$SERVER_NAME"
	);
	
	$ch = curl_init($url);
    	curl_setopt($ch, CURLOPT_POST, true);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	ob_start();
	$AuthRes = curl_exec($ch);
	$AuthBuffer = ob_get_contents();
	ob_end_clean();

	// Converting To Object
	$AuthResult = json_decode($AuthBuffer);
	
	if (!isset($AuthResult->result)) throw new Exception("가맹점 인증요청 실패");
	
	if ($AuthResult->result != 'success') throw new Exception($AuthResult->result_msg);
	
	$cst_id = $AuthResult->cst_id;                  // 가맹점 ID
	$custKey = $AuthResult->custKey;                // 가맹점 키
	$AuthKey = $AuthResult->AuthKey;                // 인증 키
	$PCD_PAY_HOST = $AuthResult->PCD_PAY_HOST;		// 서버 HOST
	$PCD_PAY_URL = $AuthResult->PCD_PAY_URL;		// 서버 URL
	$PuserDelURL = $PCD_PAY_HOST.$PCD_PAY_URL;      // 계좌해지요청 URL
	
	
	##################################################### TAXSAVE REG REQ #####################################################
	$PCD_PAYER_ID = (isset($_POST['PCD_PAYER_ID'])) ? $_POST['PCD_PAYER_ID'] : "";
	$PCD_PAYER_NO = (isset($_POST['PCD_PAYER_NO'])) ? $_POST['PCD_PAYER_NO'] : "";
	
	
	
	/////////////////////////////////////////////////  계좌해지요청 전송 /////////////////////////////////////////////////
	
	
	$PuserDel_data = array (
			"PCD_CST_ID" => "$cst_id",
			"PCD_CUST_KEY" => "$custKey",
			"PCD_AUTH_KEY" => "$AuthKey",
			"PCD_PAYER_ID" => "$PCD_PAYER_ID",
			"PCD_PAYER_NO" => "$PCD_PAYER_NO"
	);
	
	
	// content-type : application/json
	// json_encoding...
	$post_data = json_encode($PuserDel_data);
	
	//////////////////// cURL Data Send ////////////////////
	$ch = curl_init($PuserDelURL);
    	curl_setopt($ch, CURLOPT_POST, true);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	ob_start();
	$PayRes = curl_exec($ch);
	$PayBuffer = ob_get_contents();
	ob_end_clean();
	
	///////////////////////////////////////////////////////
	
	
	///////////////////////////////////////////////// 계좌해지요청 전송 /////////////////////////////////////////////////
	// Converting To Object
	$PayResult = json_decode($PayBuffer);
	
	if (isset($PayResult->PCD_PAY_RST) && $PayResult->PCD_PAY_RST != '') {
		
		$PCD_PAY_RST = $PayResult->PCD_PAY_RST;						// success | error
		$PCD_PAY_MSG = $PayResult->PCD_PAY_MSG;						// 요청 결과 메세지
		$PCD_PAY_TYPE = $PayResult->PCD_PAY_TYPE;					// 결제방법 (계좌이체 : transfer, 신용카드 : card)
		$PCD_PAY_WORK = $PayResult->PCD_PAY_WORK;					// 업무구분 (계좌해지 : PUSERDEL)
		$PCD_PAYER_ID = $PayResult->PCD_PAYER_ID;					// 결제자 고유ID (본인인증 된 결제회원 고유 KEY)
		$PCD_PAYER_NO = $PayResult->PCD_PAYER_NO;					// (가맹점) 회원고유번호
		
		
	} else {
		
		$PCD_PAY_RST = "error";										// success | error
		$PCD_PAY_MSG = "요청결과 수신 실패";								// 요청 결과 메세지
		$PCD_PAY_TYPE = "";											// 결제방법 (계좌이체 : transfer, 신용카드 : card)
		$PCD_PAY_WORK = "PUSERDEL";									// 업무구분 (계좌해지 : PUSERDEL)
		$PCD_PAYER_ID = $PCD_PAYER_ID;								// 결제자 고유ID (본인인증 된 결제회원 고유 KEY)
		$PCD_PAYER_NO = $PCD_PAYER_NO;								// 결제자 고유번호 (가맹점 회원 회원번호)
		
	}
	
	//
	$result = array (
			"PCD_PAY_RST" => "$PCD_PAY_RST",
			"PCD_PAY_MSG" => "$PCD_PAY_MSG",
			"PCD_PAY_TYPE" => "$PCD_PAY_TYPE",
			"PCD_PAY_WORK" => "$PCD_PAY_WORK",
			"PCD_PAYER_ID" => "$PCD_PAYER_ID",
			"PCD_PAYER_NO" => $PCD_PAYER_NO
	);
	
	$DATA = json_encode($result, JSON_UNESCAPED_UNICODE);
	
	echo $DATA;
	
	exit;
	
	
	
	
} catch (Exception $e) {
	
	$errMsg = $e->getMessage();
	
	$message = ($errMsg != '') ? $errMsg : "계좌해지요청 에러";
	
	$DATA = "{\"PCD_PAY_RST\":\"error\", \"PCD_PAY_MSG\":\"$message\"}";
	
	echo $DATA;
	
}
?>
