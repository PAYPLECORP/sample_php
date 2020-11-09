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
			"PCD_PAY_WORK" => "LINKREG",   /*(고정)업무구분 : AUTHREG*/
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
	$LinkRegURL = $PCD_PAY_HOST.$PCD_PAY_URL;       // 링크생성 요청 URL
	
	
	##################################################### AUTHREG REQ #####################################################
	$PCD_PAY_WORK = "LINKREG";
	$PCD_PAY_TYPE = (isset($_POST['PCD_PAY_TYPE'])) ? $_POST['PCD_PAY_TYPE'] : "transfer|card";
	$PCD_PAY_GOODS = (isset($_POST['PCD_PAY_GOODS'])) ? $_POST['PCD_PAY_GOODS'] : "";
	$PCD_PAY_YEAR = (isset($_POST['PCD_PAY_YEAR'])) ? $_POST['PCD_PAY_YEAR'] : "";
	$PCD_PAY_MONTH = (isset($_POST['PCD_PAY_MONTH'])) ? $_POST['PCD_PAY_MONTH'] : "";
	$PCD_PAY_TOTAL = (isset($_POST['PCD_PAY_TOTAL'])) ? $_POST['PCD_PAY_TOTAL'] : "";
	$PCD_REGULER_FLAG = (isset($_POST['PCD_REGULER_FLAG'])) ? $_POST['PCD_REGULER_FLAG'] : "";
	$PCD_TAXSAVE_FLAG = (isset($_POST['PCD_TAXSAVE_FLAG'])) ? $_POST['PCD_TAXSAVE_FLAG'] : "";
	
	
	/////////////////////////////////////////////////  링크생성 요청 전송 /////////////////////////////////////////////////
	
	
	$linkreg_data = array (
			"PCD_CST_ID" => "$cst_id",
			"PCD_CUST_KEY" => "$custKey",
			"PCD_AUTH_KEY" => "$AuthKey",
			"PCD_PAY_WORK" => "$PCD_PAY_WORK",
			"PCD_PAY_TYPE" => "$PCD_PAY_TYPE",
			"PCD_PAY_GOODS" => "$PCD_PAY_GOODS",
			"PCD_PAY_YEAR" => "$PCD_PAY_YEAR",
			"PCD_PAY_MONTH" => "$PCD_PAY_MONTH",
			"PCD_PAY_TOTAL" => "$PCD_PAY_TOTAL",
			"PCD_REGULER_FLAG" => "$PCD_REGULER_FLAG",
			"PCD_TAXSAVE_FLAG" => "$PCD_TAXSAVE_FLAG"
	);
	

	// content-type : application/json
	// json_encoding...
	$post_data = json_encode($linkreg_data);
	
	//////////////////// cURL Data Send ////////////////////
	$ch = curl_init($LinkRegURL);
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
	

	///////////////////////////////////////////////// 링크생성 요청 전송 /////////////////////////////////////////////////
	// Converting To Object
	$PayResult = json_decode($PayBuffer);
	
	if (isset($PayResult->PCD_LINK_RST) && $PayResult->PCD_LINK_RST != '') {
		
		$PCD_LINK_RST = $PayResult->PCD_LINK_RST;					// success | error
		$PCD_LINK_MSG = $PayResult->PCD_LINK_MSG;					// 요청 결과 메세지
		$PCD_PAY_GOODS = $PayResult->PCD_PAY_GOODS;					// 상품명
		$PCD_PAY_TOTAL = $PayResult->PCD_PAY_TOTAL;					// 결제요청금액
		$PCD_REGULER_FLAG = $PayResult->PCD_REGULER_FLAG;			// 정기결제 요청여부 (Y|N)
		$PCD_PAY_YEAR = $PayResult->PCD_PAY_YEAR;					// 정기결제 구분년도
		$PCD_PAY_MONTH = $PayResult->PCD_PAY_MONTH;					// 정기결제 구분월
		$PCD_TAXSAVE_FLAG = $PayResult->PCD_TAXSAVE_FLAG;			// 현금영수증 발행요청 (Y|N)
		$PCD_LINK_URL = $PayResult->PCD_LINK_URL;					// LINK결제 URL
		
		
	} else {
		
		$PCD_LINK_RST = "error";									// success | error
		$PCD_LINK_MSG = "요청결과 수신 실패";								// 요청 결과 메세지
		$PCD_PAY_GOODS = "";										// 상품명
		$PCD_PAY_TOTAL = "";										// 결제요청금액
		$PCD_REGULER_FLAG = "";										// 정기결제 요청여부 (Y|N)
		$PCD_PAY_YEAR = "";											// 정기결제 구분년도
		$PCD_PAY_MONTH = "";										// 정기결제 구분월
		$PCD_TAXSAVE_FLAG = "";										// 현금영수증 발행요청 (Y|N)
		$PCD_LINK_URL = "";											// LINK결제 URL
		
	}
	
	//
	$result = array (
			"PCD_LINK_RST" => "$PCD_LINK_RST",
			"PCD_LINK_MSG" => "$PCD_LINK_MSG",
			"PCD_PAY_GOODS" => "$PCD_PAY_GOODS",
			"PCD_PAY_TOTAL" => $PCD_PAY_TOTAL,
			"PCD_REGULER_FLAG" => $PCD_REGULER_FLAG,
			"PCD_PAY_YEAR" => $PCD_PAY_YEAR,
			"PCD_PAY_MONTH" => $PCD_PAY_MONTH,
			"PCD_TAXSAVE_FLAG" => $PCD_TAXSAVE_FLAG,
			"PCD_LINK_URL" => "$PCD_LINK_URL"
	);
	
	$DATA = json_encode($result, JSON_UNESCAPED_UNICODE);
	
	echo $DATA;
	
	exit;
	
	
	
	
} catch (Exception $e) {
	
	$errMsg = $e->getMessage();
	
	$message = ($errMsg != '') ? $errMsg : "링크생성 요청 에러";
	
	$DATA = "{\"PCD_LINK_RST\":\"error\", \"PCD_LINK_MSG\":\"$message\"}";
	
	echo $DATA;
	
}
?>
