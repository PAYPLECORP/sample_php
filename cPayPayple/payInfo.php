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
    /* 결제결과 조회 파트너 인증 */

    $auth_data = array(
        "cst_id" => $cst_id,
        "custKey" => $custKey,
        "PCD_PAYCHK_FLAG" => "Y"
    );

    // 인증 요청
    $authResult = CurlClient::post($url, $auth_data, $SERVER_NAME);

    if (!isset($authResult->result)) throw new Exception("인증요청 실패");

    if ($authResult->result != 'success') throw new Exception($authResult->result_msg);

    $cst_id = $authResult->cst_id;                  // 파트너사 ID
    $custKey = $authResult->custKey;                // 파트너사 키
    $authKey = $authResult->AuthKey;                // 인증 키
    $payInfoURL = $authResult->return_url;          // 결제결과 조회 URL


    /* 결제결과 조회 요청 파라미터 */

    $pay_type = (isset($_POST['PCD_PAY_TYPE'])) ? $_POST['PCD_PAY_TYPE'] : "transfer";                              // (필수) 결제수단 (transfer|card)
    $pay_oid = (isset($_POST['PCD_PAY_OID'])) ? $_POST['PCD_PAY_OID'] : "";                                         // (필수) 주문번호
    $pay_date = (isset($_POST['PCD_PAY_DATE'])) ? preg_replace("/([^0-9]+)/", "", $_POST['PCD_PAY_DATE']) : "";     // (필수) 원거래 결제일자

    /* 결제결과 조회 요청 전송 */

    $pay_data = array(
        "PCD_CST_ID" => $cst_id,
        "PCD_CUST_KEY" => $custKey,
        "PCD_AUTH_KEY" => $authKey,
        "PCD_PAYCHK_FLAG" => "Y",
        "PCD_PAY_TYPE" => $pay_type,
        "PCD_PAY_OID" => $pay_oid,
        "PCD_PAY_DATE" => $pay_date
    );

    // 결제 조회 요청
    $payResult = CurlClient::post($payInfoURL, $pay_data, $SERVER_NAME);

    /* 결제결과 조회 요청 결과 */

    if (isset($payResult->PCD_PAY_RST) && $payResult->PCD_PAY_RST != '') {

        $pay_rst = $payResult->PCD_PAY_RST;                                                         // 요청 결과 (success | error)
        $pay_code = $payResult->PCD_PAY_CODE;                                                       // 요청 결과 코드
        $pay_msg = $payResult->PCD_PAY_MSG;                                                         // 요청 결과 메시지
        $pay_oid = $payResult->PCD_PAY_OID;                                                         // 주문번호
        $pay_type = $payResult->PCD_PAY_TYPE;                                                       // 결제수단 (transfer|card)
        $payer_no = $payResult->PCD_PAYER_NO;                                                       // 결제자 고유번호 (파트너사 회원 회원번호)
        $payer_id = $payResult->PCD_PAYER_ID;                                                       // 결제자 고유 ID (빌링키)
        $payer_email = $payResult->PCD_PAYER_EMAIL;                                                 // 결제자 이메일
        $pay_goods = $payResult->PCD_PAY_GOODS;                                                     // 상품명
        $pay_total = $payResult->PCD_PAY_TOTAL;                                                     // 결제요청금액
        $pay_time = $payResult->PCD_PAY_TIME;                                                       // 결제완료 시간
        $pay_istax = isset($payResult->PCD_PAY_ISTAX) ? $payResult->PCD_PAY_ISTAX : "";             // 과세 여부
        $pay_taxtotal = isset($payResult->PCD_PAY_TAXTOTAL) ? $payResult->PCD_PAY_TAXTOTAL : "";    // 부가세(복합과세 적용 시)

        if ($pay_type == "card") {
            $pay_cardname = $payResult->PCD_PAY_CARDNAME;                                                     // 카드사명
            $pay_cardnum = $payResult->PCD_PAY_CARDNUM;                                                       // 카드번호
            $pay_cardtradenum = $payResult->PCD_PAY_CARDTRADENUM;                                             // 카드 거래번호
            $pay_cardreceipt = $payResult->PCD_PAY_CARDRECEIPT;                                               // 카드 매출전표 URL
            $pay_cardauthno = isset($payResult->PCD_PAY_CARDAUTHNO) ? $payResult->PCD_PAY_CARDAUTHNO : "";    // 카드 승인번호

        } else if ($pay_type == "transfer") {
            $pay_bank = $payResult->PCD_PAY_BANK;                        // 은행코드
            $pay_bankname = $payResult->PCD_PAY_BANKNAME;                // 은행명
            $pay_banknum = $payResult->PCD_PAY_BANKNUM;                  // 계좌번호
            $taxsave_flag = $payResult->PCD_TAXSAVE_FLAG;                // 현금영수증 발행요청 (Y|N)
            $taxsave_rst = $payResult->PCD_TAXSAVE_RST;                  // 현금영수증 발행결과 (Y|N)
        }
    } else {

        $pay_rst = "error";
        $pay_code = "결제내역 조회 에러";
        $pay_goods = "";
        $pay_total = "";
        $taxsave_rst = "";
    }


    $DATA = array(
        "PCD_PAY_RST" => $pay_rst,
        "PCD_PAY_CODE" => $pay_code,
        "PCD_PAY_MSG" => $pay_msg,
        "PCD_PAY_OID" => $pay_oid,
        "PCD_PAY_TYPE" => $pay_type,
        "PCD_PAYER_NO" => $payer_no,
        "PCD_PAYER_ID" => $payer_id,
        "PCD_PAYER_EMAIL" => $payer_email,
        "PCD_PAY_GOODS" => $pay_goods,
        "PCD_PAY_TOTAL" => $pay_total,
        "PCD_PAY_TIME" => $pay_time,
        "PCD_PAY_ISTAX" => $pay_istax,
        "PCD_PAY_TAXTOTAL" => $pay_taxtotal
    );
    if ($pay_type == 'card') {
        $DATA['PCD_PAY_CARDNAME'] = $pay_cardname;
        $DATA['PCD_PAY_CARDNUM'] = $pay_cardnum;
        $DATA['PCD_PAY_CARDTRADENUM'] = $pay_cardtradenum;
        $DATA['PCD_PAY_CARDAUTHNO'] = $pay_cardauthno;
        $DATA['PCD_PAY_CARDRECEIPT'] = $pay_cardreceipt;
    } else if ($pay_type == 'transfer') {
        $DATA['PCD_PAY_BANK'] = $pay_bank;
        $DATA['PCD_PAY_BANKNAME'] = $pay_bankname;
        $DATA['PCD_PAY_BANKNUM'] = $pay_banknum;
        $DATA['PCD_TAXSAVE_FLAG'] = $taxsave_flag;
        $DATA['PCD_TAXSAVE_RST'] = $taxsave_rst;
    }

    $JSON_DATA = json_encode($DATA);

    echo $JSON_DATA;
    exit;
} catch (Exception $e) {

    $errMsg = $e->getMessage();

    $message = ($errMsg != '') ? $errMsg : "결제내역 조회 에러";

    $DATA = "{\"PCD_PAY_RST\":\"error\", \"PCD_PAY_MSG\":\"$message\"}";

    echo $DATA;
}