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
        
    //발급받은 비밀키. 유출에 주의하시기 바랍니다.
    $PCD_CST_ID = $cst_id;
    $PCD_CUST_KEY = $custKey;
    
    // 결제 요청 데이터
    $PCD_PAY_TYPE = (isset($_POST['PCD_PAY_TYPE'])) ? $_POST['PCD_PAY_TYPE'] : "transfer";
    $PCD_AUTH_KEY = (isset($_POST['PCD_AUTH_KEY'])) ? $_POST['PCD_AUTH_KEY'] : "";
    $PCD_PAYER_ID = (isset($_POST['PCD_PAYER_ID'])) ? $_POST['PCD_PAYER_ID'] : "";
    $PCD_PAY_REQKEY = (isset($_POST['PCD_PAY_REQKEY'])) ? $_POST['PCD_PAY_REQKEY'] : "";
    $PCD_PAY_COFURL = (isset($_POST['PCD_PAY_COFURL'])) ? $_POST['PCD_PAY_COFURL'] : "";
    
    if ($PCD_AUTH_KEY == '') throw new Exception("가맹점인증KEY 값이 존재하지 않습니다.");
    
    if ($PCD_PAY_TYPE == 'transfer') {
    	if (!isset($_POST['PCD_PAYER_ID']) || $PCD_PAYER_ID == '') throw new Exception("결제자고유ID 값이 존재하지 않습니다.");
    }
    
    if ($PCD_PAY_REQKEY == '') throw new Exception("결제요청 고유KEY 값이 존재하지 않습니다.");
    if ($PCD_PAY_COFURL == '') throw new Exception("결제승인요청 URL 값이 존재하지 않습니다.");
    
    ///////////////////////////////////////////////// 정기결제 요청 전송 /////////////////////////////////////////////////
  
    $post_data = array (
        "PCD_CST_ID" => "$PCD_CST_ID",
        "PCD_CUST_KEY" => "$PCD_CUST_KEY",
        "PCD_AUTH_KEY" => "$PCD_AUTH_KEY",
        "PCD_PAYER_ID" => "$PCD_PAYER_ID",
        "PCD_PAY_REQKEY" => "$PCD_PAY_REQKEY"
    );
        

    
    // content-type : application/json
    // json_encoding...
    $post_data = json_encode($post_data);
    
    // cURL Header
    $CURLOPT_HTTPHEADER = array(
        "cache-control: no-cache",
        "content-type: application/json; charset=UTF-8"
    );
    
    //////////////////// cURL Data Send ////////////////////
    $ch = curl_init($PCD_PAY_COFURL);
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
//    print_r($PayBuffer);

//     include_once $_SERVER['DOCUMENT_ROOT'] . '/class/common/common.class.php';
//     include_once $_SERVER['DOCUMENT_ROOT'] . '/class/util/util.class.php';
//     $UTIL = new util();
    
//     $urls = array(
//     		$PCD_PAY_COFURL,
//     		$PCD_PAY_COFURL
//     );
    
//     $res = $UTIL->curl_multi($urls, $CURLOPT_HTTPHEADER, $post_data);
    
//     echo $res[0];
//     echo $res[1];
    
    ///////////////////////////////////////////////// 정기결제 요청 전송 결과 수신 /////////////////////////////////////////////////
    // Converting To Object
    $PayResult = json_decode($PayBuffer);

    if (!isset($PayResult->PCD_PAY_RST)) {
        
        throw new Exception("결제승인 결과수신 실패");
    }

    if (isset($PayResult->PCD_PAY_RST) && $PayResult->PCD_PAY_RST != '') {
        
        
    	$pay_rst = $PayResult->PCD_PAY_RST;             	// success | error
    	$pay_msg = $PayResult->PCD_PAY_MSG;             	// 출금이체완료 | 가맹점 건당 한도 초과.., 가맹점 월 한도 초과.., 등록된 계좌정보를 찾을 수 없습니다..., 최초 결제자 입니다. 본인인증 후 이용하세요...
    	$pay_reqkey = $PayResult->PCD_PAY_REQKEY;       	// 결제요청 고유KEY
    	$pay_oid = $PayResult->PCD_PAY_OID;
    	$pay_type = $PayResult->PCD_PAY_TYPE;           	// 결제방법 (transfer)
    	$payer_no = $PayResult->PCD_PAYER_NO;           	// 결제자 고유번호 (가맹점 회원 회원번호)
    	$pay_year = $PayResult->PCD_PAY_YEAR;           	// (정기결제) 과금 년도
    	$pay_month = $PayResult->PCD_PAY_MONTH;         	// (정기결제) 과금 월
    	$pay_year = $PayResult->PCD_PAY_YEAR;           	// 결제구분 년
    	$pay_month = $PayResult->PCD_PAY_MONTH;         	// 결제구분 월
    	$pay_goods = $PayResult->PCD_PAY_GOODS;         	// 결제 상품
    	$pay_amount = $PayResult->PCD_PAY_AMOUNT;			// 결제요청금액
    	$pay_discount = $PayResult->PCD_PAY_DISCOUNT;		// 할인금액
    	$pay_amount_real = $PayResult->PCD_PAY_AMOUNT_REAL; // 결제완료금액
    	$pay_total = $PayResult->PCD_PAY_TOTAL;         	// 결제 금액
    	if ($pay_type == 'transfer') {
	    	$pay_bank = $PayResult->PCD_PAY_BANK;			// 은행코드
	    	$pay_bankName = $PayResult->PCD_PAY_BANKNAME;	// 은행명
	    	$pay_bankNum = $PayResult->PCD_PAY_BANKNUM;		// 계좌번호
    	} else if ($pay_type == 'card') {
    		$pay_taxtotal = (isset($PayResult->PCD_PAY_TAXTOTAL)) ? $PayResult->PCD_PAY_TAXTOTAL : "";					// 부가세(복합과세적용)
    		$pay_isTax = (isset($PayResult->PCD_PAY_ISTAX)) ? $PayResult->PCD_PAY_ISTAX : "";							// 과세여부
    		$pay_cardname = (isset($PayResult->PCD_PAY_CARDNAME)) ? $PayResult->PCD_PAY_CARDNAME : "";					// 카드사명
    		$pay_cardnum = (isset($PayResult->PCD_PAY_CARDNUM)) ? $PayResult->PCD_PAY_CARDNUM : "";						// 카드번호
    		$pay_cardtradenum = (isset($PayResult->PCD_PAY_CARDTRADENUM)) ? $PayResult->PCD_PAY_CARDTRADENUM : "";		// 카드결제 거래번호
    		$pay_cardauthno = (isset($PayResult->PCD_PAY_CARDAUTHNO)) ? $PayResult->PCD_PAY_CARDAUTHNO : "";			// 카드결제 승인번호
    		$pay_cardreceipt = (isset($PayResult->PCD_PAY_CARDRECEIPT)) ? $PayResult->PCD_PAY_CARDRECEIPT : "";			// 카드전표 URL
    	}
    	$pay_time = $PayResult->PCD_PAY_TIME;           	// 결제완료 시간
    	$taxsave_rst = (isset($PayResult->PCD_TAXSAVE_RST)) ? $PayResult->PCD_TAXSAVE_RST : "";     	// 현금영수증 발행결과 (Y|N)
    	$reguler_flag = $PayResult->PCD_REGULER_FLAG;   	// 정기결제 요청여부 (Y|N)
        
        
        // 결제요청 결과 수신
        if ($pay_rst == 'success') {
            
            
            // 출금성공 결과 처리...
            
            // DB PROCESS
            /*
             INSERT INTO paylist
             (PListNo, pay_oid, pay_year, pay_month, pay_goods, pay_type, pay_total, taxsave_flag)
             VALUES
             ('$No', '$pay_oid', '$pay_year', '$pay_month', '$pay_goods', '$pay_type', $pay_total, '$taxsaave_flag')
             */
            
        }
        
        //
        $DATA = array(
        	"PCD_PAY_RST" => "$pay_rst",
        	"PCD_PAY_MSG" => "$pay_msg",
        	"PCD_PAY_REQKEY" => "$pay_reqkey",
        	"PCD_PAY_OID" => "$pay_oid",
        	"PCD_PAY_TYPE" => "$pay_type",
        	"PCD_PAYER_NO" => "$payer_no",
        	"PCD_PAY_YEAR" => "$pay_year",
        	"PCD_PAY_MONTH" => "$pay_month",
        	"PCD_PAY_GOODS" => "$pay_goods",
        	"PCD_PAY_AMOUNT" => "$pay_amount",
        	"PCD_PAY_DISCOUNT" => "$pay_discount",
        	"PCD_PAY_AMOUNT_REAL" => "$pay_amount_real",
        	"PCD_PAY_TOTAL" => "$pay_total",
        	"PCD_PAY_TIME" => "$pay_time",
        	"PCD_TAXSAVE_RST" => "$taxsave_rst"
        );
        
        if ($pay_type == 'transfer') {
        	$DATA['PCD_PAY_BANK'] = $pay_bank;
        	$DATA['PCD_PAY_BANKNAME'] = $pay_bankName;
        	$DATA['PCD_PAY_BANKNUM'] = $pay_bankNum;
        }
        
        if ($pay_type == 'card') {
        	$DATA['PCD_PAY_TAXTOTAL'] = $pay_taxtotal;
        	$DATA['PCD_PAY_ISTAX'] = $pay_isTax;
        	$DATA['PCD_PAY_CARDNAME'] = $pay_cardname;
        	$DATA['PCD_PAY_CARDNUM'] = $pay_cardnum;
        	$DATA['PCD_PAY_CARDTRADENUM'] = $pay_cardtradenum;
        	$DATA['PCD_PAY_CARDAUTHNO'] = $pay_cardauthno;
        	$DATA['PCD_PAY_CARDRECEIPT'] = $pay_cardreceipt;
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
?>
