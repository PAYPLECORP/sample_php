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
	
	##################################################### AuthKey REQ #####################################################
	
	//발급받은 비밀키. 유출에 주의하시기 바랍니다.
	$post_data = array (
			"cst_id" => $cst_id,
			"custKey" => $custKey,
			"PCD_PAYCANCEL_FLAG" => "Y"
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
	
	if (!isset($AuthResult->result)) throw new Exception("인증요청 실패");
	
	if ($AuthResult->result != 'success') throw new Exception($AuthResult->result_msg);
	
	$cst_id = $AuthResult->cst_id;                  // 가맹점 ID
	$custKey = $AuthResult->custKey;                // 가맹점 키
	$AuthKey = $AuthResult->AuthKey;                // 인증 키
	$PayReqURL = $AuthResult->return_url;           // 정기결제요청 URL
	
	
	
	##################################################### PAY REQ #####################################################
	
	// 정기결제 요청 데이터
	$PCD_PAYER_EMAIL = (isset($_POST['PCD_PAYER_EMAIL'])) ? $_POST['PCD_PAYER_EMAIL'] : "";								// 결제자 Email
	$PCD_PAY_OID = (isset($_POST['PCD_PAY_OID'])) ? $_POST['PCD_PAY_OID'] : "";                       					// 주문번호
	$PCD_PAY_DATE = (isset($_POST['PCD_PAY_DATE'])) ? preg_replace("/([^0-9]+)/", "", $_POST['PCD_PAY_DATE']) : "";		// 원거래 결제일자
	$PCD_REGULER_FLAG = (isset($_POST['PCD_REGULER_FLAG'])) ? $_POST['PCD_REGULER_FLAG'] : "";							// 정기결제 Y|N
	$PCD_PAY_YEAR = (isset($_POST['PCD_PAY_YEAR'])) ? preg_replace("/([^0-9]+)/", "", $_POST['PCD_PAY_YEAR']) : ""; 	// 정기결제 구분 년도
	$PCD_PAY_MONTH = (isset($_POST['PCD_PAY_MONTH'])) ? preg_replace("/([^0-9]+)/", "", $_POST['PCD_PAY_MONTH']) : "";	// 정기결제 구분 월 
	$PCD_REFUND_TOTAL = (isset($_POST['PCD_REFUND_TOTAL'])) ? $_POST['PCD_REFUND_TOTAL'] : "";          				// 환불요청금액
	$PCD_REFUND_TAXTOTAL = (isset($_POST['PCD_REFUND_TAXTOTAL'])) ? $_POST['PCD_REFUND_TAXTOTAL'] : "";          				// 환불요청금액
	$PCD_REFUND_KEY = $refundKey;																						// 환불서비스 key
	
	
	///////////////////////////////////////////////// 환불(승인취소)요청 전송 /////////////////////////////////////////////////

	$pay_data = array (
			"PCD_CST_ID" => "$cst_id",
			"PCD_CUST_KEY" => "$custKey",
			"PCD_AUTH_KEY" => "$AuthKey",
			"PCD_PAYER_EMAIL" => $PCD_PAYER_EMAIL,
			"PCD_PAY_OID" => $PCD_PAY_OID,
			"PCD_PAY_DATE" => $PCD_PAY_DATE,
			"PCD_REGULER_FLAG" => $PCD_REGULER_FLAG,
			"PCD_PAY_YEAR" => $PCD_PAY_YEAR,
			"PCD_PAY_MONTH" => $PCD_PAY_MONTH,
			"PCD_REFUND_TOTAL" => $PCD_REFUND_TOTAL,
			"PCD_REFUND_TAXTOTAL" => $PCD_REFUND_TAXTOTAL,
			"PCD_PAYCANCEL_FLAG" => "Y",
			"PCD_REFUND_KEY" => $PCD_REFUND_KEY
	);
	
	// content-type : application/json
	// json_encoding...
	$post_data = json_encode($pay_data);
	
	//////////////////// cURL Data Send ////////////////////
	$ch = curl_init($PayReqURL);
    	curl_setopt($ch, CURLOPT_POST, true);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
	
	ob_start();
	$PayRes = curl_exec($ch);
	$PayBuffer = ob_get_contents();
	ob_end_clean();
	
	///////////////////////////////////////////////////////
// 	print_r($PayBuffer);
	
	///////////////////////////////////////////////// 환불(승인취소) 요청 전송 /////////////////////////////////////////////////
	// Converting To Object
	$PayResult = json_decode($PayBuffer);
	
	if (isset($PayResult->PCD_PAY_RST) && $PayResult->PCD_PAY_RST != '') {
		
		
		$pay_rst = $PayResult->PCD_PAY_RST;             	// success | error
		$pay_msg = $PayResult->PCD_PAY_MSG;             	// 환불성공 | 환불실패 | 가맹점 건당 한도 초과.., 가맹점 월 한도 초과.., 등록된 계좌정보를 찾을 수 없습니다..., 최초 결제자 입니다. 본인인증 후 이용하세요...
		$pay_oid = $PayResult->PCD_PAY_OID;					// 주문번호
		$pay_type = $PayResult->PCD_PAY_TYPE;           	// 결제방법 (transfer)
		$payer_id = $PayResult->PCD_PAYER_ID;				// 결제자 PAYPLE USER ID
		$payer_no = $PayResult->PCD_PAYER_NO;           	// 결제자 고유번호 (가맹점 회원 회원번호)
		$reguler_flag = $PayResult->PCD_REGULER_FLAG;		// 정기결제 Y|N
		$pay_year = $PayResult->PCD_PAY_YEAR;				// [정기결제] 년도
		$pay_month = $PayResult->PCD_PAY_MONTH;				// [정기결제] 월
		$pay_goods = $PayResult->PCD_PAY_GOODS;				// 상품명
		$refund_total = $PayResult->PCD_REFUND_TOTAL;		// 환불(승인취소)금액
		$refund_taxtotal = $PayResult->PCD_REFUND_TAXTOTAL; // 환불(승인취소)부가세
		
		
	} else {
		
		$pay_rst = "error";             					// success | error
		$pay_msg = "환불요청실패";             					// 환불요청실패 ..
		$pay_oid = $PCD_PAY_OID;							// 주문번호
		$pay_type = "";           							// 결제방법 (transfer|card)
		$payer_id = "";										// 결제자 PAYPLE USER ID
		$payer_no = "";           							// 결제자 고유번호 (가맹점 회원 회원번호)
		$reguler_flag = "";									// 정기결제 Y|N
		$pay_year = "";										// [정기결제] 년도
		$pay_month = "";									// [정기결제] 월
		$pay_goods = "";									// 상품명
		$refund_total = $PCD_REFUND_TOTAL;					// 환불(승인취소)요청금액
		$refund_taxtotal = $PCD_REFUND_TAXTOTAL;			// 환불(승인취소)부가세
		
	}
	
	//
	$DATA = array(
			"PCD_PAY_RST" => $pay_rst,
			"PCD_PAY_MSG" => $pay_msg,
			"PCD_PAY_OID" => $pay_oid,
			"PCD_PAY_TYPE" => $pay_type,
			"PCD_PAYER_NO" => $payer_no,
			"PCD_PAYER_ID" => $payer_id,
			"PCD_PAY_YEAR" => $pay_year,
			"PCD_PAY_MONTH" => $pay_month,
			"PCD_PAY_GOODS" => $pay_goods,
			"PCD_REGULER_FLAG" => $reguler_flag,
			"PCD_REFUND_TOTAL" => $refund_total,
			"PCD_REFUND_TAXTOTAL" => $refund_taxtotal
	);
	
	$JSON_DATA = json_encode($DATA, JSON_UNESCAPED_UNICODE);
	
	echo $JSON_DATA;
	
	exit;
	
	
	
} catch (Exception $e) {
	
	$errMsg = $e->getMessage();
	
	$message = ($errMsg != '') ? $errMsg : "환불(승인취소)요청 에러";
	
	$DATA = "{\"PCD_PAY_RST\":\"error\", \"PCD_PAY_MSG\":\"$message\"}";
	
	echo $DATA;
	
}
?>
