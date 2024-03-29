<?php
/* 1. 결제결과 모두 출력 */
echo "<b>Response (PAY:결과 CERT:인증 AUTH:등록)</b><br/><br/>";
foreach ($_POST as $key => $val) {
    echo $key . "=>" . $val . "<br/>";
}
echo "<br/><br/><br/>";

/* 2. 결제결과 파라미터로 받기 - 응답 파라미터를 받아서 활용해보세요. */
$pay_rst = (isset($_POST['PCD_PAY_RST'])) ? $_POST['PCD_PAY_RST'] : "";                             // 결제요청 결과 (success | error)
$pay_code = (isset($_POST['PCD_PAY_CODE'])) ? $_POST['PCD_PAY_CODE'] : "";                           // 결제요청 결과 코드
$pay_msg = (isset($_POST['PCD_PAY_MSG'])) ? $_POST['PCD_PAY_MSG'] : "";                             // 결제요청 결과 메세지
$pay_type = (isset($_POST['PCD_PAY_TYPE'])) ? $_POST['PCD_PAY_TYPE'] : "";                          // 결제수단 (transfer|card)
$card_ver = (isset($_POST['PCD_CARD_VER'])) ? $_POST['PCD_CARD_VER'] : "";                          // 카드 세부 결제방식
$pay_work = (isset($_POST['PCD_PAY_WORK'])) ? $_POST['PCD_PAY_WORK'] : "";                          // 결제요청 방식 (AUTH | PAY | CERT)
$auth_key = (isset($_POST['PCD_AUTH_KEY'])) ? $_POST['PCD_AUTH_KEY'] : "";                          // 결제요청 파트너 인증 토큰 값
$pay_reqkey = (isset($_POST['PCD_PAY_REQKEY'])) ? $_POST['PCD_PAY_REQKEY'] : "";                    // (CERT방식) 최종 결제요청 승인키
$pay_cofurl = (isset($_POST['PCD_PAY_COFURL'])) ? $_POST['PCD_PAY_COFURL'] : "";                    // (CERT방식) 최종 결제요청 URL

$payer_id = (isset($_POST['PCD_PAYER_ID'])) ? $_POST['PCD_PAYER_ID'] : "";                          // 결제자 고유 ID (빌링키)
$payer_no = (isset($_POST['PCD_PAYER_NO'])) ? $_POST['PCD_PAYER_NO'] : "";                          // 결제자 고유번호 (파트너사 회원 회원번호)
$payer_name = (isset($_POST['PCD_PAYER_NAME'])) ? $_POST['PCD_PAYER_NAME'] : "";                    // 결제자 이름
$payer_hp = (isset($_POST['PCD_PAYER_HP '])) ? $_POST['PCD_PAYER_HP '] : "";                        // 결제자 휴대전화번호
$payer_email = (isset($_POST['PCD_PAYER_EMAIL'])) ? $_POST['PCD_PAYER_EMAIL'] : "";                 // 결제자 이메일 (출금결과 수신)
$pay_oid = (isset($_POST['PCD_PAY_OID'])) ? $_POST['PCD_PAY_OID'] : "";                             // 주문번호
$pay_goods = (isset($_POST['PCD_PAY_GOODS'])) ? $_POST['PCD_PAY_GOODS'] : "";                       // 상품명
$pay_total = (isset($_POST['PCD_PAY_TOTAL'])) ? $_POST['PCD_PAY_TOTAL'] : "";                       // 결제요청금액
$pay_taxtotal = (isset($_POST['PCD_PAY_TAXTOTAL'])) ? $_POST['PCD_PAY_TAXTOTAL'] : "";              // 부가세(복합과세 적용 시)
$pay_istax = (isset($_POST['PCD_PAY_ISTAX']) && $_POST['PCD_PAY_ISTAX'] == 'N') ? 'N' : 'Y';        // 과세 여부 (과세:Y 비과세:N)
$pay_time = (isset($_POST['PCD_PAY_TIME'])) ? $_POST['PCD_PAY_TIME'] : "";                          // 결제완료 시간
$pay_date = date("Ymd", strtotime($pay_time));                                                      // 결제완료 일자
$pay_bankacctype = (isset($_POST['PCD_PAY_BANKACCTYPE'])) ? $_POST['PCD_PAY_BANKACCTYPE'] : "";     // 고객 구분 (법인 | 개인 or 개인사업자)

$pay_bank = (isset($_POST['PCD_PAY_BANK'])) ? $_POST['PCD_PAY_BANK'] : "";                          // 은행코드
$pay_bankname = (isset($_POST['PCD_PAY_BANKNAME'])) ? $_POST['PCD_PAY_BANKNAME'] : "";              // 은행명
$pay_banknum = (isset($_POST['PCD_PAY_BANKNUM'])) ? $_POST['PCD_PAY_BANKNUM'] : "";                 // 계좌번호
$taxsave_rst = (isset($_POST['PCD_TAXSAVE_RST'])) ? $_POST['PCD_TAXSAVE_RST'] : "";                 // 현금영수증 발행결과 (Y|N)

$pay_cardname = (isset($_POST['PCD_PAY_CARDNAME'])) ? $_POST['PCD_PAY_CARDNAME'] : "";              // 카드사명
$pay_cardnum = (isset($_POST['PCD_PAY_CARDNUM'])) ? $_POST['PCD_PAY_CARDNUM'] : "";                 // 카드번호
$pay_cardtradenum = (isset($_POST['PCD_PAY_CARDTRADENUM'])) ? $_POST['PCD_PAY_CARDTRADENUM'] : "";  // 카드 거래번호
$pay_cardauthno = (isset($_POST['PCD_PAY_CARDAUTHNO'])) ? $_POST['PCD_PAY_CARDAUTHNO'] : "";        // 카드 승인번호
$pay_cardreceipt = (isset($_POST['PCD_PAY_CARDRECEIPT'])) ? $_POST['PCD_PAY_CARDRECEIPT'] : "";     // 카드 매출전표 URL

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no" />
    <title>결제요청 결과</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    
    <script type="text/javascript">
        $(document).ready(function() {

            // 결제요청 재컨펌(CERT)
            $('#payConfirmAct').on('click', function(e) {

                e.preventDefault();

                $('#payConfirmResult').text('');

                var con = "결제를 승인(CERT)하시겠습니까?";

                if (confirm(con) == true) {

                    var formData = new FormData($('#payConfirmForm')[0]);

                    $.ajax({
                        type: 'POST',
                        cache: false,
                        processData: false,
                        contentType: false,
                        async: false,
                        url: '/cPayPayple/payCertSend.php',
                        dataType: 'json',
                        data: formData,
                        success: function(data) {
                            console.log(data);

                            alert(data.PCD_PAY_MSG);

                            var $_table = $("<table></table>");
                            var table_data = "";

                            $.each(data, function(key, value) {
                                table_data += '<tr><td>' + key + '</td><td>: ' + value + '</td><tr>';
                            });

                            $_table.append(table_data);

                            $_table.appendTo('#payConfirmResult');

                            $('#payConfirmResult').css('display', '');

                        },
                        error: function(jqxhr, status, error) {
                            console.log(jqxhr);

                            alert(jqxhr.statusText + ",  " + status + ",   " + error);
                            alert(jqxhr.status);
                            alert(jqxhr.responseText);
                        }
                    });

                }

            });

            // 결제 취소 요청
            $('#payRefundAct').on('click', function() {

                $('#payRefundResult').text('');

                var con = confirm("결제취소하시겠습니까?");
                if (con) {
                    var formData = new FormData($('#payRefundForm')[0]);

                    $.ajax({
                        type: 'POST',
                        cache: false,
                        processData: false,
                        contentType: false,
                        async: false,
                        url: '/cPayPayple/payRefund.php',
                        dataType: 'json',
                        data: formData,
                        success: function(data) {
                            console.log(data);

                            alert(data.PCD_PAY_MSG);

                            var $_table = $("<table></table>");
                            var table_data = "";

                            $.each(data, function(key, value) {
                                table_data += '<tr><td>' + key + '</td><td>: ' + value + '</td><tr>';
                            });

                            $_table.append(table_data);

                            $_table.appendTo('#payRefundResult');

                        },
                        error: function(jqxhr, status, error) {
                            console.log(jqxhr);

                            alert(jqxhr.statusText + ",  " + status + ",   " + error);
                            alert(jqxhr.status);
                            alert(jqxhr.responseText);

                        }
                    });
                } else {
                    return false;
                }
            });
        });
    </script>
</head>

<body>
    <div style="display:none">
        PCD_PAY_RST = <?= $pay_rst ?>
        <br>
        PCD_PAY_MSG = <?= $pay_msg ?>
        <br>
        PCD_PAY_OID = <?= $pay_oid ?>
        <br>
        PCD_PAY_TYPE = <?= $pay_type ?>
        <br>
        PCD_PAY_WORK = <?= $pay_work ?>
        <br>
        PCD_PAYER_ID = <?= $payer_id ?>
        <br>
        PCD_PAYER_NO = <?= $payer_no ?>
        <? if ($pay_type == 'transfer') { ?>
            <br>
            PCD_PAY_BANKACCTYPE = <?= $pay_bankacctype ?>
        <? } ?>
        <br>
        PCD_PAYER_NAME = <?= $payer_name ?>
        <br>
        PCD_PAYER_EMAIL = <?= $payer_email ?>
        <br>
        PCD_PAY_GOODS = <?= $pay_goods ?>
        <br>
        PCD_PAY_TOTAL = <?= $pay_total ?>
        <br>
        <? if ($pay_type == 'card') { ?>
            PCD_PAY_TAXTOTAL = <?= $pay_taxtotal ?>
            <br>
            PCD_PAY_ISTAX = <?= $pay_istax ?>
        <? } ?>
        <? if ($pay_type == 'transfer') { ?>
            <br>
            PCD_PAY_BANK = <?= $pay_bank ?>
            <br>
            PCD_PAY_BANKNAME = <?= $pay_bankname ?>
            <br>
            PCD_PAY_BANKNUM = <?= $pay_banknum ?>
        <? } ?>
        <? if ($pay_type == 'card') { ?>
            <br>
            PCD_PAY_CARDNAME = <?= $pay_cardname ?>
            <br>
            PCD_PAY_CARDNUM = <?= $pay_cardnum ?>
            <br>
            PCD_PAY_CARDTRADENUM = <?= $pay_cardtradenum ?>
            <br>
            PCD_PAY_CARDAUTHNO = <?= $pay_cardauthno ?>
            <br>
            PCD_PAY_CARDRECEIPT = <?= $pay_cardreceipt ?>
        <? } ?>
        <br>
        PCD_PAY_TIME = <?= $pay_time ?>
        <br>
        PCD_TAXSAVE_RST = <?= $taxsave_rst ?>
    </div>

    <div style="width:800px; height:20px">&nbsp;</div>

    <div style="border:1px; width:800px;text-align:center;">
        <? if ($pay_work == 'CERT') { ?><button id="payConfirmAct">결제승인요청</button> <? } ?>
        <? if ($pay_work != 'AUTH') { ?><button id="payRefundAct">결제승인취소</button> <? } ?>
    </div>

    <form id="payConfirmForm">
        <input type="hidden" name="PCD_PAY_TYPE" id="PCD_PAY_TYPE" value="<?= $pay_type ?>"> <!-- (필수) 결제수단 (transfer|card) -->
        <input type="hidden" name="PCD_AUTH_KEY" id="PCD_AUTH_KEY" value="<?= $auth_key ?>"> <!-- (필수) 파트너 인증 토큰 값 -->
        <input type="hidden" name="PCD_PAYER_ID" id="PCD_PAYER_ID" value="<?= $payer_id ?>"> <!-- (필수) 결제자 고유 ID (빌링키) (결제완료시 RETURN) -->
        <input type="hidden" name="PCD_PAY_REQKEY" id="PCD_PAY_REQKEY" value="<?= $pay_reqkey ?>"> <!-- (필수) 최종 결제요청 승인키 -->
        <input type="hidden" name="PCD_PAY_COFURL" id="PCD_PAY_COFURL" value="<?= $pay_cofurl ?>"> <!-- (필수) 최종 결제요청 URL -->
    </form>

    <form id="payRefundForm">
        <input type="hidden" name="PCD_PAY_OID" id="PCD_PAY_OID" value="<?= $pay_oid ?>"> <!-- (필수) 주문번호 -->
        <input type="hidden" name="PCD_PAY_DATE" id="PCD_PAY_DATE" value="<?= $pay_date ?>"> <!-- (필수) 원거래 결제일자 -->
        <input type="hidden" name="PCD_REFUND_TOTAL" id="PCD_REFUND_TOTAL" value="<?= $pay_total ?>"> <!-- (필수) 결제취소 요청금액 -->
        <input type="hidden" name="PCD_REFUND_TAXTOTAL" id="PCD_REFUND_TAXTOTAL" value="<?= $refund_taxtotal ?>"><!-- (선택) 결제취소 부가세 -->
    </form>

    <br /><br /><br /><br />
    <b>Response (CERT:결과)</b><br /><br />
    <div id='payConfirmResult'></div>


    <br /><br /><br /><br />
    <b>Response (취소 결과)</b><br /><br />
    <div id='payRefundResult'></div>
</body>

</html>