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

	/* URL링크결제 파트너 인증 */

	//발급받은 비밀키. 유출에 주의하시기 바랍니다.
	$auth_data = array(
		"cst_id" => $cst_id,
		"custKey" => $custKey,
		"PCD_PAY_WORK" => "LINKREG"   /* URL링크결제 생성요청   : LINKREG */
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

	if (!isset($authResult->result)) throw new Exception("파트너 인증요청 실패");

	if ($authResult->result != 'success') throw new Exception($authResult->result_msg);

	$cst_id = $authResult->cst_id;                  // 파트너사 ID
	$custKey = $authResult->custKey;                // 파트너사 키
	$authKey = $authResult->AuthKey;                // 인증 키
	$linkRegURL = $authResult->return_url;      	// 링크생성 요청 URL


	/* URL링크결제 생성 요청 파라미터 */

	$pay_work = "LINKREG";																				// (필수) 요청 작업 구분 (URL링크결제 : LINKREG)
	$pay_type = (isset($_POST['PCD_PAY_TYPE'])) ? $_POST['PCD_PAY_TYPE'] : "transfer|card";				// (필수) 결제수단 (transfer|card)
	$pay_goods = (isset($_POST['PCD_PAY_GOODS'])) ? $_POST['PCD_PAY_GOODS'] : "";						// (필수) 상품명		
	$pay_total = (isset($_POST['PCD_PAY_TOTAL'])) ? $_POST['PCD_PAY_TOTAL'] : "";						// (필수) 결제요청금액
	$card_ver = (isset($_POST['PCD_CARD_VER'])) ? $_POST['PCD_CARD_VER'] : "";							// 카드 세부 결제방식 (Default: 01+02)
	$pay_istax = (isset($_POST['PCD_PAY_ISTAX'])) ? $_POST['PCD_PAY_ISTAX'] : "Y";						// 과세여부
	$pay_taxtotal = (isset($_POST['PCD_PAY_TAXTOTAL'])) ? $_POST['PCD_PAY_TAXTOTAL'] : "";				// 부가세(복합과세 적용 시)
	$taxsave_flag = (isset($_POST['PCD_TAXSAVE_FLAG'])) ? $_POST['PCD_TAXSAVE_FLAG'] : "";				// 현금영수증 발행요청 (Y|N)
	$link_expiredate = (isset($_POST['PCD_LINK_EXPIREDATE'])) ? $_POST['PCD_LINK_EXPIREDATE'] : "";		// URL 결제 만료일


	/* URL링크결제 생성 요청 전송 */

	$linkReg_data = array(
		"PCD_CST_ID" => $cst_id,
		"PCD_CUST_KEY" => $custKey,
		"PCD_AUTH_KEY" => $authKey,
		"PCD_PAY_WORK" => $pay_work,
		"PCD_PAY_TYPE" => $pay_type,
		"PCD_PAY_GOODS" => $pay_goods,
		"PCD_PAY_TOTAL" => $pay_total,
		"PCD_CARD_VER" => $card_ver,
		"PCD_PAY_ISTAX" => $pay_istax,
		"PCD_PAY_TAXTOTAL" => $pay_taxtotal,
		"PCD_TAXSAVE_FLAG" => $taxsave_flag,
		"PCD_LINK_EXPIREDATE" => $link_expiredate
	);


	// content-type : application/json
	// json_encoding...
	$post_data = json_encode($linkReg_data);

	/* cURL Data Send */
	$ch = curl_init($linkRegURL);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);

	ob_start();
	$payRes = curl_exec($ch);
	$payBuffer = ob_get_contents();
	ob_end_clean();


	/* URL링크결제 생성 요청 결과 */

	/* 1. 요청 결과 파라미터 모두 받기 - 2번 방법의 'exit;' 까지 모두 주석처리 후 사용 */
	//echo $payBuffer;
	//exit;

	/* 2. 요청 결과(PCD_LINK_RST)에 따라 보내는 값을 임의로 조정 */
	// Converting To Object
	$payResult = json_decode($payBuffer);

	if (isset($payResult->PCD_LINK_RST) && $payResult->PCD_LINK_RST != '') {

		$link_rst = $payResult->PCD_LINK_RST;					// 요청 결과 (success)
		$link_msg = $payResult->PCD_LINK_MSG;					// 요청 결과 메세지
		$pay_goods = $payResult->PCD_PAY_GOODS;					// 상품명
		$pay_type = $payResult->PCD_PAY_TYPE;					// 결제수단
		$card_ver = $payResult->PCD_CARD_VER;					// 카드 세부 결제방식
		$pay_total = $payResult->PCD_PAY_TOTAL;					// 결제요청금액
		$pay_istax = $payResult->PCD_PAY_ISTAX;					// 과세여부
		$pay_taxtotal = $payResult->PCD_PAY_TAXTOTAL;			// 부가세(복합과세 적용 시)
		$taxsave_flag = $payResult->PCD_TAXSAVE_FLAG;			// 현금영수증 발행요청 (Y|N)
		$link_expiredate = $payResult->PCD_LINK_EXPIREDATE;		// URL 결제 만료일
		$link_key = $payResult->PCD_LINK_KEY;					// 링크요청 키
		$link_url = $payResult->PCD_LINK_URL;					// 링크결제 URL
		$link_memo = $payResult->PCD_LINK_MEMO;					// 링크결제 메모

	} else {

		$link_rst = "error";									// 요청 결과 (error)
		$link_msg = "요청결과 수신 실패";						 // 요청 결과 메세지
		$pay_goods = "";										// 상품명
		$pay_total = "";										// 결제요청금액
		$taxsave_flag = "";										// 현금영수증 발행요청 (Y|N)
		$link_url = "";											// 링크결제 URL
		$link_memo = "";										// 링크결제 메모

	}

	$DATA = array(
		"PCD_LINK_RST" => $link_rst,
		"PCD_LINK_MSG" => $link_msg,
		"PCD_PAY_TYPE" => $pay_type,
		"PCD_PAY_GOODS" => $pay_goods,
		"PCD_PAY_TOTAL" => $pay_total,
		"PCD_PAY_ISTAX" => $pay_istax,
		"PCD_PAY_TAXTOTAL" => $pay_taxtotal,
		"PCD_TAXSAVE_FLAG" => $taxsave_flag,
		"PCD_LINK_EXPIREDATE" => $link_expiredate,
		"PCD_LINK_MEMO" => $link_memo,
		"PCD_LINK_KEY" => $link_key,
		"PCD_LINK_URL" => $link_url
	);
	if ($pay_type != "transfer") {
		$DATA['PCD_CARD_VER'] = $card_ver;
	}

	$JSON_DATA = json_encode($DATA, JSON_UNESCAPED_UNICODE);

	echo $JSON_DATA;

	exit;
} catch (Exception $e) {

	$errMsg = $e->getMessage();

	$message = ($errMsg != '') ? $errMsg : "링크생성 요청 에러";

	$DATA = "{\"PCD_LINK_RST\":\"error\", \"PCD_LINK_MSG\":\"$message\"}";

	echo $DATA;
}
