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
    
    $pay_work = "";
    if($_POST['PCD_TAXSAVE_REQUEST'] == "regist"){
        $pay_work = "TSREG";
    }elseif ($_POST['PCD_TAXSAVE_REQUEST'] == "cancel"){
        $pay_work = "TSCANCEL";
    }
    
    //발급받은 비밀키. 유출에 주의하시기 바랍니다.
    $post_data = array (
        "cst_id" => $cst_id,
        "custKey" => $custKey,
        "PCD_PAY_WORK" => $pay_work,   /*(고정)업무구분 : TSREG*/
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
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    ob_start();
    $AuthRes = curl_exec($ch);
    $AuthBuffer = ob_get_contents();
    ob_end_clean();
    
    // Converting To Object
    $AuthResult = json_decode($AuthBuffer);
    
    if (!isset($AuthResult->result)) throw new Exception("가맹점 인증요청 실패");
    
    if ($AuthResult->result != 'success') throw new Exception($AuthResult->result_msg);
    
    $cst_id = $AuthResult->cst_id;                  // 가맹점 ID
    $custKey = $AuthResult->custKey;                // 가맹점 키
    $AuthKey = $AuthResult->AuthKey;                // 인증 키
    $PCD_PAY_HOST = $AuthResult->PCD_PAY_HOST;		// 서버 HOST
    $PCD_PAY_URL = $AuthResult->PCD_PAY_URL;		// 서버 URL
    $TaxsaveRegURL = $PCD_PAY_HOST.$PCD_PAY_URL;    // 현금영수증 발행요청 URL
    
    ##################################################### TAXSAVE REG REQ #####################################################
    $PCD_PAY_OID = (isset($_POST['PCD_PAY_OID'])) ? $_POST['PCD_PAY_OID'] : "";
    $PCD_TAXSAVE_AMOUNT = (isset($_POST['PCD_TAXSAVE_AMOUNT'])) ? $_POST['PCD_TAXSAVE_AMOUNT'] : 0;
    $PCD_TAXSAVE_TAXTOTAL = (isset($_POST['PCD_TAXSAVE_TAXTOTAL'])) ? $_POST['PCD_TAXSAVE_TAXTOTAL'] : 0;
    $PCD_REGULER_FLAG = (isset($_POST['PCD_REGULER_FLAG'])) ? $_POST['PCD_REGULER_FLAG'] : "";
    $PCD_TAXSAVE_TRADEUSE = (isset($_POST['PCD_TAXSAVE_TRADEUSE']) && $_POST['PCD_TAXSAVE_TRADEUSE'] == 'personal') ? 'personal' : 'company';
    $PCD_TAXSAVE_IDENTINUM = (isset($_POST['PCD_TAXSAVE_IDENTINUM'])) ? preg_replace("/([^0-9]+)/", "", $_POST['PCD_TAXSAVE_IDENTINUM']) : "";
    $PCD_TAXSAVE_EMAIL = (isset($_POST['PCD_TAXSAVE_EMAIL'])) ? $_POST['PCD_TAXSAVE_EMAIL'] : 0;
    
    
    
    /////////////////////////////////////////////////  현금영수증 발행요청 전송 /////////////////////////////////////////////////
    
    
    $tsReg_data = array (
        "PCD_CST_ID" => "$cst_id",
        "PCD_CUST_KEY" => "$custKey",
        "PCD_AUTH_KEY" => "$AuthKey",
        "PCD_TAXSAVE_AMOUNT" => $PCD_TAXSAVE_AMOUNT,
        "PCD_TAXSAVE_TAXTOTAL" => $PCD_TAXSAVE_TAXTOTAL,
        "PCD_PAY_OID" => "$PCD_PAY_OID",
        "PCD_REGULER_FLAG" => "$PCD_REGULER_FLAG",
        "PCD_TAXSAVE_TRADEUSE" => "$PCD_TAXSAVE_TRADEUSE",
        "PCD_TAXSAVE_IDENTINUM" => "$PCD_TAXSAVE_IDENTINUM",
        "PCD_TAXSAVE_EMAIL" => "$PCD_TAXSAVE_EMAIL"
    );
    
    
    // content-type : application/json
    // json_encoding...
    $post_data = json_encode($tsReg_data);
    
    //////////////////// cURL Data Send ////////////////////
    $ch = curl_init($TaxsaveRegURL);
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
    
    //  	print_r($PayBuffer);
    
    ///////////////////////////////////////////////// 현금영수증 발행요청 전송 /////////////////////////////////////////////////
    // Converting To Object
    $PayResult = json_decode($PayBuffer);
    
    if (isset($PayResult->PCD_PAY_RST) && $PayResult->PCD_PAY_RST != '') {
        
        $PCD_PAY_RST = $PayResult->PCD_PAY_RST;						// success | error
        $PCD_PAY_MSG = $PayResult->PCD_PAY_MSG;						// 요청 결과 메세지
        $PCD_PAY_OID = $PayResult->PCD_PAY_OID;						// 주문번호
        $PCD_REGULER_FLAG = $PayResult->PCD_REGULER_FLAG;			// 정기결제 요청여부 (Y|N)
        $PCD_TAXSAVE_AMOUNT = $PayResult->PCD_TAXSAVE_AMOUNT;		// 발행 금액
        $PCD_TAXSAVE_MGTNUM = $PayResult->PCD_TAXSAVE_MGTNUM;		// 국세청 발행번호 or 관리번호
        
        
        
    } else {
        
        $PCD_PAY_RST = "error";										// success | error
        $PCD_PAY_MSG = "요청결과 수신 실패";								// 요청 결과 메세지
        $PCD_PAY_OID = $PCD_PAY_OID;								// 주문번호
        $PCD_REGULER_FLAG = $PCD_REGULER_FLAG;						// 정기결제 요청여부 (Y|N)
        $PCD_TAXSAVE_AMOUNT = "";									// 발행 금액
        $PCD_TAXSAVE_MGTNUM = "";									// 국세청 발행번호 or 관리번호
        
    }
    
    //
    $result = array (
        "PCD_PAY_RST" => "$PCD_PAY_RST",
        "PCD_PAY_MSG" => "$PCD_PAY_MSG",
        "PCD_PAY_OID" => $PCD_PAY_OID,
        "PCD_REGULER_FLAG" => $PCD_REGULER_FLAG,
        "PCD_TAXSAVE_AMOUNT" => $PCD_TAXSAVE_AMOUNT,
        "PCD_TAXSAVE_MGTNUM" => $PCD_TAXSAVE_MGTNUM
    );
    
    $DATA = json_encode($result, JSON_UNESCAPED_UNICODE);
    
    echo $DATA;
    
    exit;
    
    
    
    
} catch (Exception $e) {
    
    $errMsg = $e->getMessage();
    
    $message = ($errMsg != '') ? $errMsg : "현금영수증 발행요청 에러";
    
    $DATA = "{\"PCD_PAY_RST\":\"error\", \"PCD_PAY_MSG\":\"$message\"}";
    
    echo $DATA;
    
}
?>
