<?php
/*
 * 외부에서 직접 접속하여 실행되지 않도록 프로그래밍 하여 주시기 바랍니다.
 * cst_id, custKey, AuthKey 등 접속용 key 는 절대 외부에 노출되지 않도록
 * 서버 사이드 스크립트(server-side script) 내부에서 사용되어야 합니다.
 */

header("Expires: Mon 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d, M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0; pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: application/json; charset=utf-8");

try {
	

	##################################################### AUTHREG Confirm REQ #####################################################
	$PCD_CST_ID = (isset($_POST['PCD_CST_ID'])) ? $_POST['PCD_CST_ID'] : "";
	$PCD_CUST_KEY = (isset($_POST['PCD_CUST_KEY'])) ? $_POST['PCD_CUST_KEY'] : "";
	$PCD_AUTH_KEY = (isset($_POST['PCD_AUTH_KEY'])) ? $_POST['PCD_AUTH_KEY'] : "";
	$PCD_AUTH_NUM = (isset($_POST['PCD_AUTH_NUM'])) ? $_POST['PCD_AUTH_NUM'] : "";
	$PCD_AUTH_REQNUM = (isset($_POST['PCD_AUTH_REQNUM'])) ? $_POST['PCD_AUTH_REQNUM'] : "";
	$PCD_AUTH_CODE = (isset($_POST['PCD_BANK_AUTH_CODE'])) ? $_POST['PCD_BANK_AUTH_CODE'] : "";
	$return_url = (isset($_POST['return_url'])) ? $_POST['return_url'] : "";
	
	///////////////////////////////////////////////// 인증등록 승인요청 전송 /////////////////////////////////////////////////
	
	
	$authregConfirm_data = array (
			"PCD_CST_ID" => $PCD_CST_ID,
			"PCD_CUST_KEY" => $PCD_CUST_KEY,
			"PCD_AUTH_KEY" => $PCD_AUTH_KEY,
			"PCD_PAY_WORK" => "AUTHREG",
			"PCD_PAY_TYPE" => "transfer",
			"PCD_AUTH_NUM" => $PCD_AUTH_NUM,
			"PCD_AUTH_REQNUM" => $PCD_AUTH_REQNUM,
			"PCD_AUTH_CODE" => $PCD_AUTH_CODE
	);
	

	// content-type : application/json
	// json_encoding...
	$post_data = json_encode($authregConfirm_data);

	//////////////////// cURL Data Send ////////////////////
	// cURL Header
	$CURLOPT_HTTPHEADER = array(
			"cache-control: no-cache",
			"content-type: application/json; charset=UTF-8"
	);

	$ch = curl_init($return_url);
	curl_setopt($ch, CURLOPT_POST, true);
	
	if ($REMOTE_ADDR != '127.0.0.1') {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSLVERSION, 4);
	}
	
	curl_setopt($ch, CURLOPT_REFERER, $SERVER_NAME);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
	
	ob_start();
	$PayRes = curl_exec($ch);
	$PayBuffer = ob_get_contents();
	ob_end_clean();
	
	///////////////////////////////////////////////////////

	
	///////////////////////////////////////////////// 인증등록 승인요청 전송 /////////////////////////////////////////////////
	// Converting To Object
	$PayResult = json_decode($PayBuffer);
	
	if (isset($PayResult->PCD_PAY_RST) && $PayResult->PCD_PAY_RST != '') {
		
		$PCD_PAY_RST = $PayResult->PCD_PAY_RST;             // success | error
		$PCD_PAY_MSG = $PayResult->PCD_PAY_MSG;             // 인증등록 승인 결과 메세지
		$PCD_CST_ID = $PayResult->PCD_CST_ID;				// 가맹점 CUST ID
		$PCD_PAY_WORK = $PayResult->PCD_PAY_WORK;			// 업무구분 고정 : AUTHREG
		$PCD_PAY_TYPE = $PayResult->PCD_PAY_TYPE;       	// 결제방법 (transfer)
		$PCD_PAYER_NO = $PayResult->PCD_PAYER_NO;           // 인증등룍 요청자 고유번호 (가맹점 회원 회원번호)
		$PCD_PAYER_ID = $PayResult->PCD_PAYER_ID;			// 인증회원 고유 ID
		$PCD_PAYER_NAME = $PayResult->PCD_PAYER_NAME;		// 인증등록 요청자 이름
		$PCD_REGULER_FLAG = $PayResult->PCD_REGULER_FLAG;   // 정기결제 요청여부 (Y|N)
		
		
	} else {
		
		$PCD_PAY_RST = "error";             				// success | error
		$PCD_PAY_MSG = "인증등록 승인신청 결과 수신 실패";            // 인증등록 승인 실패 메세지
		$PCD_PAY_WORK = $PCD_PAY_WORK;						// 업무구분 고정 : AUTHREG
		$PCD_PAY_TYPE = $PCD_PAY_TYPE;       				// 결제방법 (transfer)
		$PCD_PAYER_NO = $PCD_PAYER_NO;           			// 인증등룍 요청자 고유번호 (가맹점 회원 회원번호)
		$PCD_PAYER_ID = '';									// 인증회원 고유 ID
		$PCD_PAYER_NAME = $PCD_PAYER_NAME;					// 인증등록 요청자 이름
		$PCD_REGULER_FLAG = $PCD_REGULER_FLAG;   			// 정기결제 요청여부 (Y|N)
		
	}
	
	//
	$result = array(
			"PCD_PAY_RST" => "$PCD_PAY_RST",
			"PCD_PAY_MSG" => "$PCD_PAY_MSG",
			"PCD_CST_ID" => "$PCD_CST_ID",
			"PCD_PAY_WORK" => "$PCD_PAY_WORK",
			"PCD_PAY_TYPE" => "$PCD_PAY_TYPE",
			"PCD_PAYER_NO" => "$PCD_PAYER_NO",
			"PCD_PAYER_ID" => "$PCD_PAYER_ID",
			"PCD_PAYER_NAME" => "$PCD_PAYER_NAME",
			"PCD_REGULER_FLAG" => "$PCD_REGULER_FLAG"
	);
	
	$DATA = json_encode($result, JSON_UNESCAPED_UNICODE);
	
	echo $DATA;
	
	exit;
	
	
	
	
} catch (Exception $e) {
	
	$errMsg = $e->getMessage();
	
	$message = ($errMsg != '') ? $errMsg : "인증등록 승인요청 에러";
	
	$DATA = "{\"PCD_PAY_RST\":\"error\", \"PCD_PAY_MSG\":\"$message\"}";
	
	echo $DATA;
	
}
?>