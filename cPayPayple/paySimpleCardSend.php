<?php
/*
 * 외부에서 직접 접속하여 실행되지 않도록 프로그래밍 하여 주시기 바랍니다.
 * cst_id, custKey, authKey 등 접속용 key 는 절대 외부에 노출되지 않도록
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
	
	##################################################### 카드 정기결제 재결제 파트너 인증  #####################################################
		
	//발급받은 비밀키. 유출에 주의하시기 바랍니다.
	$auth_data = array (
			"cst_id" => $cst_id,
			"custKey" => $custKey,
			"PCD_PAY_TYPE" => "card",
			"PCD_SIMPLE_FLAG" => "Y"
	);
	
	// content-type : application/json
	// json_encoding...
	$post_data = json_encode($auth_data);
	
	// cURL Header
	$CURLOPT_HTTPHEADER = array(
			"cache-control: no-cache",
			"content-type: application/json; charset=UTF-8",
			"referer: http://$SERVER_NAME"
	);
	
	$ch = curl_init($url);
    	curl_setopt($ch, CURLOPT_POST, true);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
	
	ob_start();
	$authRes = curl_exec($ch);
	$authBuffer = ob_get_contents();
	ob_end_clean();

	// Converting To Object
	$authResult = json_decode($authBuffer);
	
	if (!isset($authResult->result)) throw new Exception("인증요청 실패");
	
	if ($authResult->result != 'success') throw new Exception($authResult->result_msg);
	
	$cst_id = $authResult->cst_id;                  // 파트너사 ID
	$custKey = $authResult->custKey;                // 파트너사 키
	$authKey = $authResult->AuthKey;                // 인증 키
	$payReqURL = $authResult->return_url;           // 카드 정기결제 재결제 URL
	
	
	##################################################### 카드 정기결제 재결제 요청 파라미터 #####################################################
   
	$pay_type = "card";																								// (필수) 결제수단 (card)
    $payer_id = (isset($_POST['PCD_PAYER_ID'])) ? $_POST['PCD_PAYER_ID'] : "";        								// (필수) 결제자 고유 ID (빌링키)
    $pay_goods = (isset($_POST['PCD_PAY_GOODS'])) ? $_POST['PCD_PAY_GOODS'] : "";                 					// (필수) 상품명
    $pay_total = (isset($_POST['PCD_PAY_TOTAL'])) ? $_POST['PCD_PAY_TOTAL'] : "";                 					// (필수) 결제요청금액
	$pay_oid = (isset($_POST['PCD_PAY_OID'])) ? $_POST['PCD_PAY_OID'] : "";                       					// 주문번호
	$payer_no = (isset($_POST['PCD_PAYER_NO'])) ? $_POST['PCD_PAYER_NO'] : "";                       				// 결제자 고유번호 (파트너사 회원 회원번호)
	$payer_name = (isset($_POST['PCD_PAYER_NAME'])) ? $_POST['PCD_PAYER_NAME'] : "";                       			// 결제자 이름
	$payer_hp = (isset($_POST['PCD_PAYER_HP'])) ? $_POST['PCD_PAYER_HP'] : "";                       				// 결제자 휴대전화번호
	$payer_email = (isset($_POST['PCD_PAYER_EMAIL'])) ? $_POST['PCD_PAYER_EMAIL'] : "";								// 결제자 이메일
	$pay_istax = (isset($_POST['PCD_PAY_ISTAX']) && $_POST['PCD_PAY_ISTAX'] == 'N') ? 'N' : 'Y';    				// 과세여부
    $pay_taxtotal = (isset($_POST['PCD_PAY_TAXTOTAL'])) ? $_POST['PCD_PAY_TAXTOTAL'] : "";							// 부가세(복합과세 적용 시)
    
    ##################################################### 카드 정기결제 재결제 요청 전송 #####################################################

	$pay_data = array (
			"PCD_CST_ID" => $cst_id,
			"PCD_CUST_KEY" => $custKey,
			"PCD_AUTH_KEY" => $authKey,
			"PCD_PAY_TYPE" => $pay_type,
			"PCD_PAYER_ID" => $payer_id,
			"PCD_PAY_GOODS" => $pay_goods,
			"PCD_SIMPLE_FLAG" => "Y",
			"PCD_PAY_TOTAL" => $pay_total,
			"PCD_PAY_OID" => $pay_oid,
			"PCD_PAYER_NO" => $payer_no,
			"PCD_PAYER_NAME" => $payer_name,
			"PCD_PAYER_HP" => $payer_hp,
			"PCD_PAYER_EMAIL" => $payer_email,
			"PCD_PAY_ISTAX" => $pay_istax,
			"PCD_PAY_TAXTOTAL" => $pay_taxtotal
	);
	
	// content-type : application/json
	// json_encoding...
	$post_data = json_encode($pay_data);
	
	################### cURL Data Send ###################
	$ch = curl_init($payReqURL);
    	curl_setopt($ch, CURLOPT_POST, true);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
	
	ob_start();
	$payRes = curl_exec($ch);
	$payBuffer = ob_get_contents();
	ob_end_clean();
	
	##################################################### 카드 정기결제 재결제 요청 결과 #####################################################

	# 1. 요청 결과 파라미터 모두 받기 - #2의 'exit;' 까지 모두 주석처리 후 사용
	//echo $payBuffer;
	//exit;

	# 2. 요청 결과(PCD_PAY_RST)에 따라 보내는 값을 임의로 조정
	// Converting To Object
	$payResult = json_decode($payBuffer);
	
	if (isset($payResult->PCD_PAY_RST) && $payResult->PCD_PAY_RST != '') {
		
		$pay_rst = $payResult->PCD_PAY_RST;             		// 요청 결과 (success | error)
		$pay_code = $payResult->PCD_PAY_CODE;             		// 요청 결과 코드
		$pay_msg = $payResult->PCD_PAY_MSG;             		// 요청 결과 메시지
		$pay_oid = $payResult->PCD_PAY_OID;						// 주문번호
		$pay_type = $payResult->PCD_PAY_TYPE;        		   	// 결제수단 (card)
		$payer_no = $payResult->PCD_PAYER_NO;           		// 결제자 고유번호 (파트너사 회원 회원번호)
		$payer_id = $payResult->PCD_PAYER_ID;					// 결제자 고유 ID (빌링키)
		$payer_name = $payResult->PCD_PAYER_NAME;				// 결제자 이름
		$payer_hp = $payResult->PCD_PAYER_HP;					// 결제자 휴대전화번호
		$payer_email = $payResult->PCD_PAYER_EMAIL;				// 결제자 이메일
		$pay_goods = $payResult->PCD_PAY_GOODS;         		// 상품명
		$pay_total = $payResult->PCD_PAY_TOTAL;         		// 결제요청금액
		$pay_taxtotal = $payResult->PCD_PAY_TAXTOTAL;			// 부가세(복합과세 적용 시)
		$pay_istax = $payResult->PCD_PAY_ISTAX;					// 과세여부
		$pay_time = $payResult->PCD_PAY_TIME;           		// 결제완료 시간
		$pay_cardname = $payResult->PCD_PAY_CARDNAME;			// 카드사명
		$pay_cardnum = $payResult->PCD_PAY_CARDNUM;				// 카드번호
		$pay_cardtradenum = $payResult->PCD_PAY_CARDTRADENUM;   // 카드 거래번호
		$pay_cardauthno = $payResult->PCD_PAY_CARDAUTHNO;		// 카드 승인번호
		$pay_cardreceipt = $payResult->PCD_PAY_CARDRECEIPT;		// 카드 매출전표 URL
		$simple_flag = $payResult->PCD_SIMPLE_FLAG;				// 간편결제 여부 (Y|N)
		
	} else {
		
		$pay_rst = "error";                	// 요청 결과 (error)
		$pay_code = "";                	// 요청 결과 (error)
		$pay_msg = "카드결제실패";          // 요청 결과 메시지
		//$pay_oid = ;               		// 주문번호
		//$pay_type = ;             		// 결제수단 (card)
		//$payer_no = ;                   	// 결제자 고유번호 (파트너사 회원 회원번호)
		//$payer_id = ;		   				// 결제자 고유 ID (빌링키)
		//$payer_name = ;					// 결제자 이름
		//$payer_hp = ;						// 결제자 휴대전화번호
		//$payer_email = ;					// 결제자 이메일
		//$pay_goods = ;           			// 상품명
		//$pay_total = ;           		   	// 결제요청금액
		//$pay_taxtotal = ;					// 부가세 (복합과세 적용 시)
		//$pay_istax = ;					// 과세여부
		$pay_time = "";                    	// 결제완료 시간
		$pay_cardname = "";					// 카드사명
		$pay_cardnum = "";					// 카드번호
		$pay_cardtradenum = "";   			// 신용카드 거래번호
		$pay_cardauthno = "";				// 신용카드 승인번호
		$pay_cardreceipt = "";				// 카드 매출전표 URL
		$simple_flag = "Y";					// 간편결제 여부 (Y|N)
		
	}
	
	$DATA = array(
			"PCD_PAY_RST" => $pay_rst,
			"PCD_PAY_CODE" => $pay_code,
			"PCD_PAY_MSG" => $pay_msg,
			"PCD_PAY_OID" => $pay_oid,
			"PCD_PAY_TYPE" => $pay_type,
			"PCD_PAYER_NO" => $payer_no,
			"PCD_PAYER_ID" => $payer_id,
			"PCD_PAYER_NAME" => $payer_name,
			"PCD_PAYER_HP" => $payer_hp,
			"PCD_PAYER_EMAIL" => $payer_email,
			"PCD_PAY_GOODS" => $pay_goods,
			"PCD_PAY_TOTAL" => $pay_total,
			"PCD_PAY_TAXTOTAL" => $pay_taxtotal,
			"PCD_PAY_ISTAX" => $pay_istax,
			"PCD_PAY_TIME" => $pay_time,
			"PCD_PAY_CARDNAME" => $pay_cardname,
			"PCD_PAY_CARDNUM" => $pay_cardnum,
			"PCD_PAY_CARDTRADENUM" => $pay_cardtradenum,
			"PCD_PAY_CARDAUTHNO" => $pay_cardauthno,
			"PCD_PAY_CARDRECEIPT" => $pay_cardreceipt,
			"PCD_SIMPLE_FLAG" => $simple_flag
	);
	
	$JSON_DATA = json_encode($DATA, JSON_UNESCAPED_UNICODE);
	
	echo $JSON_DATA;
		
	exit;
	
	
	
} catch (Exception $e) {
	
	$errMsg = $e->getMessage();
	
	$message = ($errMsg != '') ? $errMsg : "간편결제(신용카드)요청 에러";
	
	$DATA = "{\"PCD_PAY_RST\":\"error\", \"PCD_PAY_MSG\":\"$message\"}";
	
	echo $DATA;
	
}
?>
