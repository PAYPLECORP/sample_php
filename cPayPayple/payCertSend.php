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
    /* 결제요청 재컨펌(CERT) */

    // 결제 요청(CERT) 데이터 - 필수
    $pay_type = (isset($_POST['PCD_PAY_TYPE'])) ? $_POST['PCD_PAY_TYPE'] : "transfer";          // 결제수단 (transfer|card)
    $auth_key = (isset($_POST['PCD_AUTH_KEY'])) ? $_POST['PCD_AUTH_KEY'] : "";                  // 파트너 인증 토큰 값
    $payer_id = (isset($_POST['PCD_PAYER_ID'])) ? $_POST['PCD_PAYER_ID'] : "";                  // 결제자 고유 ID (빌링키)
    $pay_reqkey = (isset($_POST['PCD_PAY_REQKEY'])) ? $_POST['PCD_PAY_REQKEY'] : "";            // 최종 결제요청 승인키
    $pay_cofurl = (isset($_POST['PCD_PAY_COFURL'])) ? $_POST['PCD_PAY_COFURL'] : "";            // 최종 결제요청 URL

    if ($auth_key == '') throw new Exception("파트너인증 KEY 값이 존재하지 않습니다.");

    if ($pay_type == 'transfer') {
        if (!isset($_POST['PCD_PAYER_ID']) || $payer_id == '') throw new Exception("결제자고유ID 값이 존재하지 않습니다.");
    }

    if ($pay_reqkey == '') throw new Exception("결제요청 고유KEY 값이 존재하지 않습니다.");
    if ($pay_cofurl == '') throw new Exception("결제승인요청 URL 값이 존재하지 않습니다.");

    
    /* 결제요청 재컨펌(CERT) 요청 전송 */
    //발급받은 비밀키. 유출에 주의하시기 바랍니다.
    $payCert_data = array(
        "PCD_CST_ID" => $cst_id,
        "PCD_CUST_KEY" => $custKey,
        "PCD_AUTH_KEY" => $auth_key,
        "PCD_PAYER_ID" => $payer_id,
        "PCD_PAY_REQKEY" => $pay_reqkey
    );



    // content-type : application/json
    // json_encoding...
    $post_data = json_encode($payCert_data);

    // cURL Header
    $CURLOPT_HTTPHEADER = array(
        "cache-control: no-cache",
        "content-type: application/json; charset=UTF-8"
    );

    /* cURL Data Send */
    $ch = curl_init($pay_cofurl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);

    ob_start();
    $payRes = curl_exec($ch);
    $payBuffer = ob_get_contents();
    ob_end_clean();

    /* 결제요청 재컨펌(CERT) 요청 결과 */

    /* 1. 요청 결과 파라미터 모두 받기 - 2번 방법의 'exit;' 까지 모두 주석처리 후 사용 */
    //echo $payBuffer;
    //exit;

    /* 2. 요청 결과(PCD_PAY_RST)에 따라 보내는 값을 임의로 조정 */
    // Converting To Object
    $payResult = json_decode($payBuffer);

    if (!isset($payResult->PCD_PAY_RST)) {
        throw new Exception("결제승인 결과수신 실패");
    }

    if (isset($payResult->PCD_PAY_RST) && $payResult->PCD_PAY_RST != '') {

        $pay_rst = $payResult->PCD_PAY_RST;                     // 요청 결과 (success | error)
        $pay_code = $payResult->PCD_PAY_CODE;                   // 요청 결과 코드
        $pay_msg = $payResult->PCD_PAY_MSG;                     // 요청 결과 메시지
        $pay_reqkey = $payResult->PCD_PAY_REQKEY;               // (CERT방식) 최종 결제요청 승인키
        $pay_oid = $payResult->PCD_PAY_OID;                     // 주문번호
        $pay_type = $payResult->PCD_PAY_TYPE;                   // 결제수단 (transfer|card)
        $payer_id = $payResult->PCD_PAYER_ID;                   // 결제자 고유 ID (빌링키)
        $payer_no = $payResult->PCD_PAYER_NO;                   // 결제자 고유번호 (파트너사 회원 회원번호)
        $payer_name = $payResult->PCD_PAYER_NAME;               // 결제자 이름
        $payer_hp = $payResult->PCD_PAYER_HP;                   // 결제자 휴대전화번호
        $payer_email = $payResult->PCD_PAYER_EMAIL;             // 결제자 이메일
        $pay_goods = $payResult->PCD_PAY_GOODS;                 // 상품명
        $pay_amount = $payResult->PCD_PAY_AMOUNT;               // 결제금액
        $pay_discount = $payResult->PCD_PAY_DISCOUNT;           // 페이플 이벤트 할인금액
        $pay_amount_real = $payResult->PCD_PAY_AMOUNT_REAL;     // 실 결제금액
        $pay_total = $payResult->PCD_PAY_TOTAL;                 // 결제요청금액
        $pay_istax = $payResult->PCD_PAY_ISTAX;                 // 과세 여부
        $pay_taxtotal = $payResult->PCD_PAY_TAXTOTAL;           // 부가세(복합과세 적용 시)
        $pay_time = $payResult->PCD_PAY_TIME;                   // 결제완료 시간

        if ($pay_type == 'card') {
            $pay_cardname = $payResult->PCD_PAY_CARDNAME;                                                       // 카드사명
            $pay_cardnum = $payResult->PCD_PAY_CARDNUM;                                                         // 카드번호
            $pay_cardtradenum = $payResult->PCD_PAY_CARDTRADENUM;                                               // 카드결제 거래번호
            $pay_cardauthno = $payResult->PCD_PAY_CARDAUTHNO;                                                   // 카드결제 승인번호
            $pay_cardreceipt = isset($payResult->PCD_PAY_CARDRECEIPT) ? $payResult->PCD_PAY_CARDRECEIPT : "";   // 카드 매출전표 URL

        } else if ($pay_type == 'transfer') {
            $pay_bank = $payResult->PCD_PAY_BANK;                           // 은행코드
            $pay_bankName = $payResult->PCD_PAY_BANKNAME;                   // 은행명
            $pay_bankNum = $payResult->PCD_PAY_BANKNUM;                     // 계좌번호
            $taxsave_flag = $payResult->PCD_TAXSAVE_FLAG;                   // 현금영수증 발행요청 (Y|N)
            $taxsave_rst = $payResult->PCD_TAXSAVE_RST;                     // 현금영수증 발행결과 (Y|N)
        }


        $DATA = array(
            "PCD_PAY_RST" => $pay_rst,
            "PCD_PAY_CODE" => $pay_code,
            "PCD_PAY_MSG" => $pay_msg,
            "PCD_PAY_REQKEY" => $pay_reqkey,
            "PCD_PAY_OID" => $pay_oid,
            "PCD_PAY_TYPE" => $pay_type,
            "PCD_PAYER_ID" => $payer_id,
            "PCD_PAYER_NO" => $payer_no,
            "PCD_PAYER_NAME" => $payer_name,
            "PCD_PAYER_HP" => $payer_hp,
            "PCD_PAYER_EMAIL" => $payer_email,
            "PCD_PAY_GOODS" => $pay_goods,
            "PCD_PAY_AMOUNT" => $pay_amount,
            "PCD_PAY_DISCOUNT" => $pay_discount,
            "PCD_PAY_AMOUNT_REAL" => $pay_amount_real,
            "PCD_PAY_TOTAL" => $pay_total,
            "PCD_PAY_ISTAX" => $pay_istax,
            "PCD_PAY_TAXTOTAL" => $pay_taxtotal,
            "PCD_PAY_TIME" => $pay_time
        );

        if ($pay_type == 'card') {
            $DATA['PCD_PAY_CARDNAME'] = $pay_cardname;
            $DATA['PCD_PAY_CARDNUM'] = $pay_cardnum;
            $DATA['PCD_PAY_CARDTRADENUM'] = $pay_cardtradenum;
            $DATA['PCD_PAY_CARDAUTHNO'] = $pay_cardauthno;
            $DATA['PCD_PAY_CARDRECEIPT'] = $pay_cardreceipt;
        } else if ($pay_type == 'transfer') {
            $DATA['PCD_PAY_BANK'] = $pay_bank;
            $DATA['PCD_PAY_BANKNAME'] = $pay_bankName;
            $DATA['PCD_PAY_BANKNUM'] = $pay_bankNum;
            $DATA['PCD_TAXSAVE_FLAG'] = $taxsave_flag;
            $DATA['PCD_TAXSAVE_RST'] = $taxsave_rst;
        }

        $JSON_DATA = json_encode($DATA, JSON_UNESCAPED_UNICODE);

        echo $JSON_DATA;

        exit;
    } else {

        throw new Exception();
    }
} catch (Exception $e) {

    $errMsg = $e->getMessage();

    $message = ($errMsg != '') ? $errMsg : "결제승인요청 에러";

    $DATA = "{\"PCD_PAY_RST\":\"error\", \"PCD_PAY_MSG\":\"$message\"}";

    echo $DATA;
}
