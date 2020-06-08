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
			"PCD_PAY_WORK" => "AUTHREG",   /*(고정)업무구분 : AUTHREG*/
			"PCD_PAY_TYPE" => "card"
	);
	
	// content-type : application/json
	// json_encoding...
	$post_data = json_encode($post_data);
	
	// cURL Header
	$CURLOPT_HTTPHEADER = array(
			"cache-control: no-cache",
			"content-type: application/json; charset=UTF-8"
	);
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, true);
	
	if ($REMOTE_ADDR != '127.0.0.1') {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSLVERSION, 4);
	}
	
	curl_setopt($ch, CURLOPT_REFERER, $SERVER_NAME);
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
	$AuthRegReqURL = $AuthResult->return_url;       // 본인인증등록 요청 URL

	
	##################################################### AUTHREG REQ #####################################################	
	$PCD_PAY_TYPE = "card";
	$PCD_PAY_WORK = "AUTHREG";
	$PCD_PAY_BANKACCTYPE = (isset($_POST['PCD_PAY_BANKACCTYPE'])) ? $_POST['PCD_PAY_BANKACCTYPE'] : "personal";
	$PCD_PAYER_NO = (isset($_POST['PCD_PAYER_NO'])) ? $_POST['PCD_PAYER_NO'] : "";
	$PCD_PAYER_NAME = (isset($_POST['PCD_PAYER_NAME'])) ? $_POST['PCD_PAYER_NAME'] : "";
	$PCD_PAYER_BIRTH = (isset($_POST['PCD_PAYER_BIRTH'])) ? $_POST['PCD_PAYER_BIRTH'] : "";
	$PCD_PAYER_HP = (isset($_POST['PCD_PAYER_HP'])) ? $_POST['PCD_PAYER_HP'] : "";
	$PCD_PAYER_EMAIL = (isset($_POST['PCD_PAYER_EMAIL'])) ? $_POST['PCD_PAYER_EMAIL'] : "";
	$PCD_PAY_CARDNUM = (isset($_POST['PCD_PAY_CARDNUM'])) ? $_POST['PCD_PAY_CARDNUM'] : "";
	$PCD_PAY_CARDEXYEAR = (isset($_POST['PCD_PAY_CARDEXYEAR'])) ? $_POST['PCD_PAY_CARDEXYEAR'] : "";
	$PCD_PAY_CARDEXMONTH = (isset($_POST['PCD_PAY_CARDEXMONTH'])) ? $_POST['PCD_PAY_CARDEXMONTH'] : "";
	$PCD_PAY_CARDPW = (isset($_POST['PCD_PAY_CARDPW'])) ? $_POST['PCD_PAY_CARDPW'] : "";
	$PCD_REGULER_FLAG = (isset($_POST['PCD_REGULER_FLAG'])) ? $_POST['PCD_REGULER_FLAG'] : "N";
	
	/////////////////////////////////////////////////  인증등록 요청 전송 /////////////////////////////////////////////////

				
	$authreg_data = array (
			"PCD_CST_ID" => "$cst_id",
			"PCD_CUST_KEY" => "$custKey",
			"PCD_AUTH_KEY" => "$AuthKey",
			"PCD_PAY_WORK" => "$PCD_PAY_WORK",
			"PCD_PAY_TYPE" => "$PCD_PAY_TYPE",
			"PCD_PAYER_NO" => "$PCD_PAYER_NO",
			"PCD_PAYER_NAME" => "$PCD_PAYER_NAME",
			"PCD_PAYER_BIRTH" => "$PCD_PAYER_BIRTH",
			"PCD_PAYER_HP" => "$PCD_PAYER_HP",
			"PCD_PAYER_EMAIL" => "$PCD_PAYER_EMAIL",
			"PCD_PAY_CARDNUM" => "$PCD_PAY_CARDNUM",
			"PCD_PAY_CARDEXYEAR" => "$PCD_PAY_CARDEXYEAR",
			"PCD_PAY_CARDEXMONTH" => "$PCD_PAY_CARDEXMONTH",
			"PCD_PAY_CARDPW" => "$PCD_PAY_CARDPW",
			"PCD_REGULER_FLAG" => "$PCD_REGULER_FLAG"
	);
				
			
	// content-type : application/json
	// json_encoding...
	$post_data = json_encode($authreg_data);
			
	//////////////////// cURL Data Send ////////////////////
	$ch = curl_init($AuthRegReqURL);
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

	///////////////////////////////////////////////// 카드등록 요청 전송 /////////////////////////////////////////////////
	// Converting To Object
	$PayResult = json_decode($PayBuffer);

	if (isset($PayResult->PCD_PAY_RST) && $PayResult->PCD_PAY_RST != '') {
		
		$PCD_PAY_RST = $PayResult->PCD_PAY_RST;             		// success | error
		$PCD_PAY_CODE = $PayResult->PCD_PAY_CODE;					// 인증등록 요청 결과 코드
		$PCD_PAY_MSG = $PayResult->PCD_PAY_MSG;             		// 인증등록 요청 결과 메세지
		$PCD_CST_ID = $PayResult->PCD_CST_ID;						// 가맹점 CUST ID
		$PCD_PAY_WORK = $PayResult->PCD_PAY_WORK;					// 업무구분 고정 : AUTHREG
		$PCD_PAY_TYPE = $PayResult->PCD_PAY_TYPE;       		 	// 결제방법 (card)
		$PCD_PAYER_ID = $PayResult->PCD_PAYER_ID;					// 
		$PCD_PAYER_NO = $PayResult->PCD_PAYER_NO;           		// 인증등룍 요청자 고유번호 (가맹점 회원 회원번호)
		$PCD_PAY_CARDNAME = $PayResult->PCD_PAY_CARDNAME;			// 카드사명
		$PCD_PAY_CARDNUM = $PayResult->PCD_PAY_CARDNUM;				// 카드번호 (1234-****-****-1234)
		$PCD_REGULER_FLAG = $PayResult->PCD_REGULER_FLAG;   		// 정기결제 요청여부 (Y|N)
		
		
	} else {
		
		$PCD_PAY_RST = "error";             						// success | error
		$PCD_PAY_CODE = "";											// 인증등록 요청 결과 코드
		$PCD_PAY_MSG = "카드등록 결과 수신 실패";             				// 인증등록 요청 결과 메세지
		$PCD_CST_ID = $cst_id;										// 가맹점 CUST ID
		$PCD_PAY_WORK = "AUTHREG";									// 업무구분 고정 : AUTHREG
		$PCD_PAY_TYPE = "card";       		 						// 결제방법 (card)
		$PCD_PAYER_ID = "";											//
		$PCD_PAYER_NO = $PCD_PAYER_NO;           					// 인증등룍 요청자 고유번호 (가맹점 회원 회원번호)
		$PCD_PAY_CARDNAME = "";										// 카드사명
		$PCD_PAY_CARDNUM = "";										// 카드번호
		$PCD_REGULER_FLAG = $PCD_REGULER_FLAG;   					// 정기결제 요청여부 (Y|N)
		
	}
	
	//
	$result = array(
			"PCD_PAY_RST" => $PCD_PAY_RST,
			"PCD_PAY_CODE" => $PCD_PAY_CODE,
			"PCD_PAY_MSG" => $PCD_PAY_MSG,
			"PCD_CST_ID" => $cst_id,
			"PCD_PAY_WORK" => $PCD_PAY_WORK,
			"PCD_PAY_TYPE" => $PCD_PAY_TYPE,
			"PCD_PAYER_ID" => $PCD_PAYER_ID,
			"PCD_PAYER_NO" => $PCD_PAYER_NO,
			"PCD_PAY_CARDNAME" => $PCD_PAY_CARDNAME,
			"PCD_PAY_CARDNUM" => $PCD_PAY_CARDNUM,
			"PCD_REGULER_FLAG" => $PCD_REGULER_FLAG
	);
			
	$DATA = json_encode($result, JSON_UNESCAPED_UNICODE);
	
	echo $DATA;
    
	exit;
		
	
	
	
} catch (Exception $e) {
	
	$errMsg = $e->getMessage();
	
	$message = ($errMsg != '') ? $errMsg : "카드등록 에러";
	
	$DATA = "{\"PCD_PAY_RST\":\"error\", \"PCD_PAY_MSG\":\"$message\"}";
	
	echo $DATA;
	
}
?>