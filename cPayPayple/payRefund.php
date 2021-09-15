<?php
/*
 * 외부에서 직접 접속하여 실행되지 않도록 프로그래밍 하여 주시기 바랍니다.
 * cst_id, custKey, authKey 등 접속용 key 는 절대 외부에 노출되지 않도록
 * 서버 사이드 스크립트(server-side script) 내부에서 사용되어야 합니다.
 */
include $_SERVER['DOCUMENT_ROOT'] . '/payple/inc/config.php';
header("Expires: Mon 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d, M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0; pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: application/json; charset=utf-8");

try {

	/* 결제취소 파트너 인증 */

	//발급받은 비밀키. 유출에 주의하시기 바랍니다.
	$auth_data = array(
		"cst_id" => $cst_id,
		"custKey" => $custKey,
		"PCD_PAYCANCEL_FLAG" => "Y"
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
	$payRefURL = $authResult->return_url;           // 결제취소요청 URL

	/* 결제취소 요청 파라미터 */

	$pay_oid = (isset($_POST['PCD_PAY_OID'])) ? $_POST['PCD_PAY_OID'] : "";                       					// (필수) 주문번호
	$pay_date = (isset($_POST['PCD_PAY_DATE'])) ? preg_replace("/([^0-9]+)/", "", $_POST['PCD_PAY_DATE']) : "";		// (필수) 원거래 결제일자
	$refund_total = (isset($_POST['PCD_REFUND_TOTAL'])) ? $_POST['PCD_REFUND_TOTAL'] : "";          				// (필수) 결제취소 요청금액
	$refund_taxtotal = (isset($_POST['PCD_REFUND_TAXTOTAL'])) ? $_POST['PCD_REFUND_TAXTOTAL'] : "";          		// 결제취소 부가세

	/* 결제취소 요청 전송 */

	$payRefund_data = array(
		"PCD_CST_ID" => $cst_id,
		"PCD_CUST_KEY" => $custKey,
		"PCD_AUTH_KEY" => $authKey,
		"PCD_REFUND_KEY" => $refundKey,
		"PCD_PAYCANCEL_FLAG" => "Y",
		"PCD_PAY_OID" => $pay_oid,
		"PCD_PAY_DATE" => $pay_date,
		"PCD_REFUND_TOTAL" => $refund_total,
		"PCD_REFUND_TAXTOTAL" => $refund_taxtotal
	);

	// content-type : application/json
	$post_data = json_encode($payRefund_data);

	/* cURL Data Send */
	$ch = curl_init($payRefURL);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);

	ob_start();
	$payRes = curl_exec($ch);
	$payBuffer = ob_get_contents();
	ob_end_clean();


	/* 결제취소 요청 결과 */

	/* 1. 요청 결과 파라미터 모두 받기 - 2번 방법의 'exit;' 까지 모두 주석처리 후 사용 */
	//echo $payBuffer;
	//exit;


	/* 2. 요청 결과(PCD_PAY_RST)에 따라 보내는 값을 임의로 조정 */
	// Converting To Object
	$payResult = json_decode($payBuffer);

	if (isset($payResult->PCD_PAY_RST) && $payResult->PCD_PAY_RST != '') {

		$pay_rst = $payResult->PCD_PAY_RST;             	// 요청 결과 (success | error)
		$pay_msg = $payResult->PCD_PAY_MSG;             	// 요청 결과 메시지
		$pay_oid = $payResult->PCD_PAY_OID;					// 주문번호
		$pay_type = $payResult->PCD_PAY_TYPE;           	// 결제수단 (transfer|card)
		$payer_id = $payResult->PCD_PAYER_ID;				// 결제자 고유 ID  (빌링키)
		$payer_no = $payResult->PCD_PAYER_NO;           	// 결제자 고유번호 (파트너사 회원 회원번호)
		$pay_goods = $payResult->PCD_PAY_GOODS;				// 상품명
		$refund_total = $payResult->PCD_REFUND_TOTAL;		// 결제취소 요청금액
		$refund_taxtotal = $payResult->PCD_REFUND_TAXTOTAL; // 결제취소 부가세

	} else {

		$pay_rst = "error";             					// 요청 결과 (success | error)
		$pay_msg = "환불요청실패";             				// 요청 결과 메시지
		//$pay_oid											// 주문번호
		$pay_type = "";           							// 결제수단 (transfer|card) (transfer|card)
		$payer_id = "";										// 결제자 고유 ID  (빌링키)
		$payer_no = "";           							// 결제자 고유번호 (파트너사 회원 회원번호)
		$pay_goods = "";									// 상품명
		//$refund_total										// 결제취소 요청금액
		//$refund_taxtotal									// 결제취소 부가세

	}

	$DATA = array(
		"PCD_PAY_RST" => $pay_rst,
		"PCD_PAY_MSG" => $pay_msg,
		"PCD_PAY_OID" => $pay_oid,
		"PCD_PAY_TYPE" => $pay_type,
		"PCD_PAYER_NO" => $payer_no,
		"PCD_PAYER_ID" => $payer_id,
		"PCD_PAY_GOODS" => $pay_goods,
		"PCD_REFUND_TOTAL" => $refund_total,
		"PCD_REFUND_TAXTOTAL" => $refund_taxtotal
	);

	$JSON_DATA = json_encode($DATA, JSON_UNESCAPED_UNICODE);

	echo $JSON_DATA;

	exit;
} catch (Exception $e) {

	$errMsg = $e->getMessage();

	$message = ($errMsg != '') ? $errMsg : "결제취소 요청 에러";

	$DATA = "{\"PCD_PAY_RST\":\"error\", \"PCD_PAY_MSG\":\"$message\"}";

	echo $DATA;
}
