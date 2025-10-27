<?php
/*
 * 외부에서 직접 접속하여 실행되지 않도록 프로그래밍 하여 주시기 바랍니다.
 * cst_id, custKey, authKey 등 접속용 key 는 절대 외부에 노출되지 않도록
 * 서버 사이드 스크립트(server-side script) 내부에서 사용되어야 합니다.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/cPayPayple/Utils/CurlClient.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/payple/inc/config.php';

CurlClient::setApiHeaders();

try {
    /* 현금영수증 발행/취소 파트너 인증 */

    //현금영수증 발행, 취소 구분
    $pay_work = "";
    if ($_POST['PCD_TAXSAVE_REQUEST'] == "regist") {
        $pay_work = "TSREG";
    } else if ($_POST['PCD_TAXSAVE_REQUEST'] == "cancel") {
        $pay_work = "TSCANCEL";
    }

    //발급받은 비밀키. 유출에 주의하시기 바랍니다.
    $auth_data = array(
        "cst_id" => $cst_id,
        "custKey" => $custKey,
        "PCD_PAY_WORK" => $pay_work   /* 현금영수증 발행 및 취소 요청 : TSREG, TSCANCEL */
    );

    // 인증 요청
    $authResult = CurlClient::post($url, $auth_data, $SERVER_NAME);

    if (!isset($authResult->result)) throw new Exception("파트너 인증요청 실패");

    if ($authResult->result != 'success') throw new Exception($authResult->result_msg);

    $cst_id = $authResult->cst_id;                  // 파트너사 ID
    $custKey = $authResult->custKey;                // 파트너사 키
    $authKey = $authResult->AuthKey;                // 인증 키
    $taxSaveRegURL = $authResult->return_url;       // 현금영수증 발행 및 취소 요청 URL


    /* 현금영수증 발행/취소 요청 파라미터 */

    $payer_id = (isset($_POST['PCD_PAYER_ID'])) ? $_POST['PCD_PAYER_ID'] : "";                                                              // (필수) 결제자 고유 ID (빌링키)
    $pay_oid = (isset($_POST['PCD_PAY_OID'])) ? $_POST['PCD_PAY_OID'] : "";                                                                 // (필수) 주문번호
    $taxsave_amount = (isset($_POST['PCD_TAXSAVE_AMOUNT'])) ? $_POST['PCD_TAXSAVE_AMOUNT'] : 0;                                             // (필수) 현금영수증 발행금액
    $taxsave_tradeuse = (isset($_POST['PCD_TAXSAVE_TRADEUSE']) && $_POST['PCD_TAXSAVE_TRADEUSE'] == 'personal') ? 'personal' : 'company';   // 현금영수증 발행 타입 (personal:소득공제용 | company:지출증빙)
    $taxsave_identinum = (isset($_POST['PCD_TAXSAVE_IDENTINUM'])) ? preg_replace("/([^0-9]+)/", "", $_POST['PCD_TAXSAVE_IDENTINUM']) : "";  // 현금영수증 발행대상 번호

    /* 현금영수증 발행/취소 요청 전송 */

    $tsReg_data = array(
        "PCD_CST_ID" => $cst_id,
        "PCD_CUST_KEY" => $custKey,
        "PCD_AUTH_KEY" => $authKey,
        "PCD_PAYER_ID" => $payer_id,
        "PCD_PAY_OID" => $pay_oid,
        "PCD_TAXSAVE_AMOUNT" => $taxsave_amount,
        "PCD_TAXSAVE_TRADEUSE" => $taxsave_tradeuse,
        "PCD_TAXSAVE_IDENTINUM" => $taxsave_identinum
    );

    // 현금영수증 발행/취소 요청
    $payResult = CurlClient::post($taxSaveRegURL, $tsReg_data, $SERVER_NAME);

    /* 현금영수증 발행/취소 요청 결과 */

    // 페이플 서버 응답을 JSON 문자열로 변환
    $payBuffer = json_encode($payResult, JSON_UNESCAPED_UNICODE);

    /* 1. 요청 결과 파라미터 모두 받기 - 2번 방법의 'exit;' 까지 모두 주석처리 후 사용 */
    //echo $payBuffer;
    //exit;

    /* 2. 요청 결과(PCD_PAY_RST)에 따라 보내는 값을 임의로 조정 */
    if (isset($payResult->PCD_PAY_RST) && $payResult->PCD_PAY_RST != '') {

        $pay_rst = $payResult->PCD_PAY_RST;                                             // 요청 결과 (success | error)
        $pay_code = $payResult->PCD_PAY_CODE;                                           // 요청 결과 코드
        $pay_msg = $payResult->PCD_PAY_MSG;                                             // 요청 결과 메세지
        $pay_work = $payResult->PCD_PAY_WORK;                                           // 요청 작업 구분 (TSREG | TSCANCEL)
        $payer_id = isset($payResult->PCD_PAYER_ID) ? $payResult->PCD_PAYER_ID : "";    // 결제자 고유 ID (빌링키)
        $pay_oid = $payResult->PCD_PAY_OID;                                             // 주문번호
        $taxsave_amount = $payResult->PCD_TAXSAVE_AMOUNT;                               // 현금영수증 발행금액
        $taxsave_mgtnum = $payResult->PCD_TAXSAVE_MGTNUM;                               // 현금영수증 발행된 국세청 발행번호

    } else {

        $pay_rst = "error";
        $pay_code = "";
        $pay_msg = "요청결과 수신 실패";
        //$pay_work = ;
        //$pay_oid = ;                                      
        $taxsave_amount = "";
        $taxsave_mgtnum = "";
    }


    $result = array(
        "PCD_PAY_RST" => $pay_rst,
        "PCD_PAY_CODE" => $pay_code,
        "PCD_PAY_MSG" => $pay_msg,
        "PCD_PAY_WORK" => $pay_work,
        "PCD_PAYER_ID" => $payer_id,
        "PCD_PAY_OID" => $pay_oid,
        "PCD_TAXSAVE_AMOUNT" => $taxsave_amount,
        "PCD_TAXSAVE_MGTNUM" => $taxsave_mgtnum
    );

    $DATA = json_encode($result, JSON_UNESCAPED_UNICODE);

    echo $DATA;

    exit;
} catch (Exception $e) {

    $errMsg = $e->getMessage();

    $message = ($errMsg != '') ? $errMsg : "현금영수증 발행/취소 요청 에러";

    $DATA = "{\"PCD_PAY_RST\":\"error\", \"PCD_PAY_MSG\":\"$message\"}";

    echo $DATA;
}
