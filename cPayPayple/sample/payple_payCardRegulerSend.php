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
			"PCD_PAY_TYPE" => "card",
			"PCD_REGULER_FLAG" => "Y"
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
    
    // 간편결제 요청 데이터
    $PCD_PAYER_ID = (isset($_POST['PCD_PAYER_ID'])) ? $_POST['PCD_PAYER_ID'] : "";        								// 결제자 PAYPLE USER ID
    $PCD_PAYER_NO = (isset($_POST['PCD_PAYER_NO'])) ? $_POST['PCD_PAYER_NO'] : "";
    $PCD_PAYER_EMAIL = (isset($_POST['PCD_PAYER_EMAIL'])) ? $_POST['PCD_PAYER_EMAIL'] : "";								// 결제자 Email
    $PCD_PAY_GOODS = (isset($_POST['PCD_PAY_GOODS'])) ? $_POST['PCD_PAY_GOODS'] : "";                 					// 결제 상품명
    $PCD_PAY_TOTAL = (isset($_POST['PCD_PAY_TOTAL'])) ? $_POST['PCD_PAY_TOTAL'] : "";                 					// 결제금액
    $PCD_PAY_TAXTOTAL = (isset($_POST['PCD_PAY_TAXTOTAL'])) ? $_POST['PCD_PAY_TAXTOTAL'] : "";			// 부가세	(복합과세 적용 시)
    $PCD_PAY_ISTAX = (isset($_POST['PCD_PAY_ISTAX']) && $_POST['PCD_PAY_ISTAX'] == 'N') ? 'N' : 'Y';    // 과세여부
    $PCD_PAY_OID = (isset($_POST['PCD_PAY_OID'])) ? $_POST['PCD_PAY_OID'] : "";                       					// 주문번호
    $PCD_PAY_YEAR = (isset($_POST['PCD_PAY_YEAR'])) ? preg_replace("/([^0-9]+)/", "", $_POST['PCD_PAY_YEAR']) : "";		// 정기결제 구분년도
    $PCD_PAY_MONTH = (isset($_POST['PCD_PAY_MONTH'])) ? preg_replace("/([^0-9]+)/", "", $_POST['PCD_PAY_MONTH']) : "";  // 정기결제 구분월
    
    
    ///////////////////////////////////////////////// 간편결제(신용카드) 요청 전송 /////////////////////////////////////////////////
    
    $pay_type = "card";                 // 결제방법
    
    $pay_data = array (
    		"PCD_CST_ID" => "$cst_id",
    		"PCD_CUST_KEY" => "$custKey",
    		"PCD_AUTH_KEY" => "$AuthKey",
    		"PCD_PAY_TYPE" => "$pay_type",
    		"PCD_PAYER_ID" => $PCD_PAYER_ID,
    		"PCD_PAYER_NO" => $PCD_PAYER_NO,
    		"PCD_PAYER_EMAIL" => $PCD_PAYER_EMAIL,
    		"PCD_PAY_GOODS" => $PCD_PAY_GOODS,
    		"PCD_PAY_TOTAL" => $PCD_PAY_TOTAL,
    		"PCD_PAY_TAXTOTAL" => $PCD_PAY_TAXTOTAL,
    		"PCD_PAY_ISTAX" => $PCD_PAY_ISTAX,
    		"PCD_PAY_OID" => $PCD_PAY_OID,
    		"PCD_PAY_YEAR" => $PCD_PAY_YEAR,
    		"PCD_PAY_MONTH" => $PCD_PAY_MONTH,
    		"PCD_REGULER_FLAG" => "Y"
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
    
//     print_r($PayBuffer);
    
//     include_once $_SERVER['DOCUMENT_ROOT'] . '/class/common/common.class.php';
//     include_once $_SERVER['DOCUMENT_ROOT'] . '/class/util/util.class.php';
//     $UTIL = new util();
    
//     $urls = array(
//     		$PayReqURL,
//     		$PayReqURL
//     );
    
//     $res = $UTIL->curl_multi($urls, $CURLOPT_HTTPHEADER, $post_data);
    
//     echo $res[0];
//     echo $res[1];
    
//     exit;
    
    ///////////////////////////////////////////////// 간편결제 요청 전송 /////////////////////////////////////////////////
    // Converting To Object
    $PayResult = json_decode($PayBuffer);
    
    if (isset($PayResult->PCD_PAY_RST) && $PayResult->PCD_PAY_RST != '') {
    	
    	
    	$pay_rst = $PayResult->PCD_PAY_RST;             		// success | error
    	$pay_msg = $PayResult->PCD_PAY_MSG;             		// 카드결제완료 | 가맹점 건당 한도 초과.., 가맹점 월 한도 초과.., 인증 카드정보를 찾을 수 없습니다..., 최초 결제자 입니다. 본인인증 후 이용하세요...
    	$pay_oid = $PayResult->PCD_PAY_OID;
    	$pay_type = $PayResult->PCD_PAY_TYPE;           		// 결제방법 (card)
    	$payer_id = $PayResult->PCD_PAYER_ID;					// 결제자 PAYPLE USER ID
    	$payer_no = $PayResult->PCD_PAYER_NO;           		// 결제자 고유번호 (가맹점 회원 회원번호)
    	$payer_email = $PayResult->PCD_PAYER_EMAIL;				// 결제자 Email
    	$pay_goods = $PayResult->PCD_PAY_GOODS;         		// 결제 상품
    	$pay_year = $PayResult->PCD_PAY_YEAR;					// 결제 구분년도
    	$pay_month = $PayResult->PCD_PAY_MONTH;					// 결제 구분월
    	$pay_total = $PayResult->PCD_PAY_TOTAL;         		// 결제 금액
    	$pay_taxtotal = $PayResult->PCD_PAY_TAXTOTAL;		// 부가세(복합과세 적용 시)
    	$pay_istax = $PayResult->PCD_PAY_ISTAX;				// 과세여부
    	$pay_time = $PayResult->PCD_PAY_TIME;           		// 결제완료 시간
    	$pay_cardname = $PayResult->PCD_PAY_CARDNAME;			// 카드사명
    	$pay_cardnum = $PayResult->PCD_PAY_CARDNUM;				// 카드번호
    	$pay_cardtradenum = $PayResult->PCD_PAY_CARDTRADENUM;   // 신용카드 거래번호
    	$pay_cardauthno = $PayResult->PCD_PAY_CARDAUTHNO;		// 신용카드 승인번호
    	$pay_cardreceipt = $PayResult->PCD_PAY_CARDRECEIPT;		// 카드전표 URL
    	$reguler_flag = $PayResult->PCD_REGULER_FLAG;			// 간편결제 FLAG
    	
    	
    } else {
    	
    	$pay_rst = "error";                				// success | error
    	$pay_msg = "카드결제실패";            				// 카드결제완료 | 카드결제실패 | 가맹점 건당 한도 초과.., 가맹점 월 한도 초과.., 인증 카드정보를 찾을 수 없습니다..., 최초 결제자 입니다. 본인인증 후 이용하세요...
    	$pay_oid = $PCD_PAY_OID;               			// 주문번호
    	$pay_type = $pay_type;             				// 결제방법 (card)
    	$payer_id = $PCD_PAYER_ID;		   				// 결제자 PAYPLE USER ID
    	$payer_no = $PCD_PAYER_NO;                   	// 결제자 고유번호 (가맹점 회원 회원번호)
    	$payer_email = $PCD_PAYER_EMAIL;				// 결제자 Email
    	$pay_goods = $PCD_PAY_GOODS;           			// 결제 상품
    	$pay_year = $PCD_PAY_YEAR;						// 결제 구분년도
    	$pay_month = $PCD_PAY_MONTH;					// 결제 구분월
    	$pay_total = $PCD_PAY_TOTAL;           		   	// 결제 요청금액
    	$pay_taxtotal = $PCD_PAY_TAXTOTAL;				// 부가세 (복합과세 적용 시)
    	$pay_istax = $PCD_PAY_ISTAX;					// 과세여부
    	$pay_time = "";                    				// 결제완료 시간
    	$pay_cardname = "";								// 카드사명
    	$pay_cardnum = "";								// 카드번호
    	$pay_cardtradenum = "";   						// 신용카드 거래번호
    	$pay_cardauthno = "";							// 신용카드 승인번호
    	$pay_cardreceipt = "";							// 카드전표 URL
    	$reguler_flag = "Y";							// 간편결제 FLAG
    	
    }
    
    //
    $DATA = array(
    		"PCD_PAY_RST" => "$pay_rst",
    		"PCD_PAY_MSG" => "$pay_msg",
    		"PCD_PAY_OID" => "$pay_oid",
    		"PCD_PAY_TYPE" => "$pay_type",
    		"PCD_PAYER_ID" => "$payer_id",
    		"PCD_PAYER_NO" => "$payer_no",
    		"PCD_PAYER_EMAIL" => "$payer_email",
    		"PCD_PAY_GOODS" => "$pay_goods",
    		"PCD_PAY_YEAR" => "$pay_year",
    		"PCD_PAY_MONTH" => "$pay_month",
    		"PCD_PAY_TOTAL" => "$pay_total",
    		"PCD_PAY_TAXTOTAL" => "$pay_taxtotal",
    		"PCD_PAY_ISTAX" => "$pay_istax",
    		"PCD_PAY_TIME" => "$pay_time",
    		"PCD_PAY_CARDNAME" => "$pay_cardname",
    		"PCD_PAY_CARDNUM" => "$pay_cardnum",
    		"PCD_PAY_CARDTRADENUM" => "$pay_cardtradenum",
    		"PCD_PAY_CARDAUTHNO" => "$pay_cardauthno",
    		"PCD_PAY_CARDRECEIPT" => "$pay_cardreceipt",
    		"PCD_REGULER_FLAG" => "$reguler_flag"
    );
    
    $JSON_DATA = json_encode($DATA, JSON_UNESCAPED_UNICODE);
    
    echo $JSON_DATA;
    
    exit;

    
    
} catch (Exception $e) {
 
    $errMsg = $e->getMessage();
    
    $message = ($errMsg != '') ? $errMsg : "정기결제(카드)요청 에러";
    
    $DATA = "{\"PCD_PAY_RST\":\"error\", \"PCD_PAY_MSG\":\"$message\"}";
    
    echo $DATA;
    
}
?>
