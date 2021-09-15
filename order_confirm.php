<?php
/* 파트너 인증 */

/* 	※ Request URL
 *	TEST(테스트) : https://democpay.payple.kr/php/auth.php
 *	REAL(운영) : https://cpay.payple.kr/php/auth.php 
 */

include $_SERVER['DOCUMENT_ROOT'] . '/payple/inc/config.php';
header("Expires: Mon 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d, M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0; pre-check=0", false);
header("Pragma: no-cache");
//header("Content-type: application/json; charset=utf-8");


/* ※ Referer 설정 방법
	TEST : referer에는 테스트 결제창을 띄우는 도메인을 넣어주셔야합니다. 결제창을 띄울 도메인과 referer값이 다르면 무한로딩이 발생합니다.
	REAL : referer에는 파트너사 도메인으로 등록된 도메인을 넣어주셔야합니다. 
		   다른 도메인을 넣으시면 [AUTH0004] 에러가 발생합니다.
		   또한, TEST에서와 마찬가지로 결제창을 띄우는 도메인과 같아야 합니다. 
*/
$CURLOPT_HTTPHEADER = array(
	"referer: http://" . $_SERVER['HTTP_HOST'] // 필수
);

// 발급받은 비밀키. 유출에 주의하시기 바랍니다.
// 실제 서비스(REAL)에 붙이실 때는 발급받은 운영 계정 키를 넣어주세요.
$post_data = array(
	"cst_id" => $cst_id,
	"custKey" => $custKey
);

// TEST와 REAL 서버에 따라 알맞는 URL을 넣어주세요
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));

ob_start();
$authRes = curl_exec($ch);
$authBuffer = ob_get_contents();
ob_end_clean();

echo '<script>console.log(' . $authBuffer . ')</script>';

// Converting To Object
$authResult = json_decode($authBuffer);

if (!isset($authResult->result)) throw new Exception("파트너 인증요청 실패");

if ($authResult->result != 'success') throw new Exception($authResult->result_msg);

$authKey = $authResult->AuthKey;              // 인증 키
$payReqURL = $authResult->return_url;         // 결제요청 URL


/* 결제요청 파라미터 */

$pay_oid = isset($_POST['pay_oid']) ? $_POST['pay_oid'] : "";
$payer_no = isset($_POST['payer_no']) ? $_POST['payer_no'] : "";
$payer_name = isset($_POST['payer_name']) ? $_POST['payer_name'] : "";
$payer_hp = isset($_POST['payer_hp']) ? $_POST['payer_hp'] : "";
$payer_email = isset($_POST['payer_email']) ? $_POST['payer_email'] : "";
$pay_goods = isset($_POST['pay_goods']) ? addslashes($_POST['pay_goods']) : "";
$pay_total = isset($_POST['pay_total']) ? preg_replace("/([^0-9\.]+)/", "", $_POST['pay_total']) : "";

$pay_type = isset($_POST['pay_type']) ? $_POST['pay_type'] : "transfer";
$card_ver = (isset($_POST['card_ver']) && $_POST['card_ver'] == '02') ? '02' : '01';
$taxsave_flag = isset($_POST['taxsave_flag']) ? $_POST['taxsave_flag'] : "";
$payer_id = isset($_POST['payer_id']) ? $_POST['payer_id'] : "";
$pay_work = isset($_POST['pay_work']) ? $_POST['pay_work'] : "PAY";
$pay_istax = (isset($_POST['pay_istax']) && $_POST['pay_istax'] == 'N') ? 'N' : 'Y';
$pay_taxtotal = isset($_POST['pay_taxtotal']) ? preg_replace("/([^0-9]+)/", "", $_POST['pay_taxtotal']) : "";
$simple_flag = isset($_POST['simple_flag']) ? $_POST['simple_flag'] : "N";
$payer_authtype = isset($_POST['payer_authtype']) ? $_POST['payer_authtype'] : "";
$is_direct = isset($_POST['is_direct']) ? $_POST['is_direct'] : "N";

?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no" />
	<title>Insert title here</title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<!-- 페이플 TEST 결제창 -->
	<script src="https://democpay.payple.kr/js/cpay.payple.1.0.1.js"></script>

	<script>
		$(document).ready(function() {

			// callBack 함수 사용
			var getResult = function(res) {

				var url = "/order_result.php";

				var $form = $('<form></form>');
				$form.attr('action', url);
				$form.attr('method', 'post');

				$.each(res, function(key, val) {
					var input = $('<input type="hidden" name="' + key + '" value="' + val + '">');
					$form.append(input);
				});

				$form.appendTo('body');
				$form.submit();
			};

			// 결제 요청 
			$('#payAction').on('click', function(event) {

				var pay_type = "<?= $pay_type ?>";
				var pay_work = "<?= $pay_work ?>";
				var payer_id = "<?= $payer_id ?>";
				var payer_no = "<?= $payer_no ?>";
				var payer_name = "<?= $payer_name ?>";
				var payer_hp = "<?= $payer_hp ?>";
				var payer_email = "<?= $payer_email ?>";
				var pay_goods = "<?= $pay_goods ?>";
				var pay_total = Number("<?= $pay_total ?>");
				var pay_taxtotal = Number("<?= $pay_taxtotal ?>");
				var pay_istax = "<?= $pay_istax ?>";
				var pay_oid = "<?= $pay_oid ?>";
				var taxsave_flag = "<?= $taxsave_flag ?>";
				var simple_flag = "<?= $simple_flag ?>";
				var card_ver = "<?= $card_ver ?>";
				var payer_authtype = "<?= $payer_authtype ?>";
				var is_direct = "<?= $is_direct ?>";
				var pcd_rst_url = "";
				var server_name = "<?= $SERVER_NAME ?>";

				// 결제창 방식 설정 - 팝업(상대경로), 다이렉트(절대경로)
				if (is_direct == 'Y') pcd_rst_url = "http://" + server_name + "/order_result.php";
				else pcd_rst_url = "/order_result.php";

				var obj = new Object();

				/* 결제연동 파라미터 */

				//DEFAULT SET 1 
				obj.PCD_PAY_TYPE = pay_type; // (필수) 결제수단 (transfer|card)
				obj.PCD_PAY_WORK = pay_work; // (필수) 결제요청 방식 (AUTH | PAY | CERT)

				// 카드결제 시 필수 (카드 세부 결제방식)
				obj.PCD_CARD_VER = card_ver; // Default: 01 (01: 간편/정기결제, 02: 앱카드)

				/* 결제요청 방식별(PCD_PAY_WORK) 파라미터 설정 */
				/*
				 * 1. 빌링키 등록
				 * PCD_PAY_WORK : AUTH
				 */
				if (pay_work == 'AUTH') {

					obj.PCD_PAYER_NO = payer_no; // (선택) 결제자 고유번호 (파트너사 회원 회원번호) (결과전송 시 입력값 그대로 RETURN)
					obj.PCD_PAYER_NAME = payer_name; // (선택) 결제자 이름
					obj.PCD_PAYER_HP = payer_hp; // (선택) 결제자 휴대전화번호
					obj.PCD_PAYER_EMAIL = payer_email; // (선택) 결제자 이메일
					obj.PCD_TAXSAVE_FLAG = taxsave_flag; // (선택) 현금영수증 발행요청 (Y|N)
					obj.PCD_SIMPLE_FLAG = simple_flag; // (선택) 간편결제 여부 (Y|N)

				}

				/*
				 * 2. 빌링키 등록 및 결제
				 * PCD_PAY_WORK : PAY | CERT 
				 */
				if (pay_work != 'AUTH') {

					// 2.1 첫결제 및 단건(일반,비회원)결제
					if (simple_flag != 'Y' || payer_id == '') {

						obj.PCD_PAY_GOODS = pay_goods; // (필수) 상품명
						obj.PCD_PAY_TOTAL = pay_total; // (필수) 결제요청금액
						obj.PCD_PAYER_NO = payer_no; // (선택) 결제자 고유번호 (파트너사 회원 회원번호) (결과전송 시 입력값 그대로 RETURN)
						obj.PCD_PAYER_NAME = payer_name; // (선택) 결제자 이름
						obj.PCD_PAYER_HP = payer_hp; // (선택) 결제자 휴대전화번호
						obj.PCD_PAYER_EMAIL = payer_email; // (선택) 결제자 이메일
						obj.PCD_PAY_TAXTOTAL = pay_taxtotal; // (선택) 부가세(복합과세 적용 시)
						obj.PCD_PAY_ISTAX = pay_istax; // (선택) 과세여부
						obj.PCD_PAY_OID = pay_oid; // (선택) 주문번호 (미입력 시 임의 생성)
						obj.PCD_TAXSAVE_FLAG = taxsave_flag; // (선택) 현금영수증 발행요청 (Y|N)

					}

					// 2.2 간편결제 (재결제)
					if (simple_flag == 'Y' && payer_id != '') {

						// PCD_PAYER_ID 는 소스상에 표시하지 마시고 반드시 Server Side Script 를 이용하여 불러오시기 바랍니다.	
						obj.PCD_PAYER_ID = payer_id; // (필수) 결제자 고유ID (본인인증 된 결제회원 고유 KEY)
						obj.PCD_SIMPLE_FLAG = 'Y'; // (필수) 간편결제 여부 (Y|N)
						obj.PCD_PAY_GOODS = pay_goods; // (필수) 상품명
						obj.PCD_PAY_TOTAL = pay_total; // (필수) 결제요청금액
						obj.PCD_PAYER_NO = payer_no; // (선택) 결제자 고유번호 (파트너사 회원 회원번호) (결과전송 시 입력값 그대로 RETURN)
						obj.PCD_PAY_TAXTOTAL = pay_taxtotal; // (선택) 부가세(복합과세인 경우 필수)
						obj.PCD_PAY_ISTAX = pay_istax; // (선택) 과세여부
						obj.PCD_PAY_OID = pay_oid; // (선택) 주문번호 (미입력 시 임의 생성)
						obj.PCD_TAXSAVE_FLAG = taxsave_flag; // (선택) 현금영수증 발행요청 (Y|N)

					}

				}

				// DEFAULT SET 2
				obj.PCD_PAYER_AUTHTYPE = payer_authtype; // (선택) 비밀번호 결제 인증방식 (pwd : 패스워드 인증)
				obj.PCD_RST_URL = pcd_rst_url; // (필수) 결제(요청)결과 RETURN URL
				//obj.callbackFunction = getResult; // (선택) 결과를 받고자 하는 callback 함수명 (callback함수를 설정할 경우 PCD_RST_URL 이 작동하지 않음)

				// 파트너 인증시 받은 AuthKey 값 입력
				obj.PCD_AUTH_KEY = "<?= $authKey ?>";

				// 파트너 인증시 받은 return_url 값 입력
				obj.PCD_PAY_URL = "<?= $payReqURL ?>";

				PaypleCpayAuthCheck(obj);

				event.preventDefault();

			});

		});
	</script>
	<style>
		table {
			border: 1px solid #aaaaaa;
			text-align: center;
		}

		tr {
			height: 35px;
		}

		th {
			text-align: center;
			font-weight: bold;
			background-color: #ececec;
			font-size: 14px;
		}

		td {
			text-align: left;
			padding-left: 5px;
		}

		#subject,
		#parameter {
			background-color: rgba(120,82,232,0.2);
			font-size: 15px;
		}
	</style>
</head>

<body>
	<table border="1">
		<h2>| Payple 연동 API </h2>
		<tr>
			<th id="subject">
				항목
			</th>
			<th id="parameter">
				요청변수
			</th>
		</tr>
		<tr>
			<th>결제자 이름</th>
			<td><?= $payer_name ?></td>
		</tr>
		<tr>
			<th>결제자 휴대폰번호</th>
			<td><?= $payer_hp ?></td>
		</tr>
		<tr>
			<th>결제자 이메일</th>
			<td><?= $payer_email ?></td>
		</tr>
		<tr>
			<th>구매상품</th>
			<td><?= $pay_goods ?></td>
		</tr>
		<tr>
			<th>결제금액</th>
			<td><?= $pay_total ?></td>
		</tr>
		<tr>
			<th>과세여부</th>
			<td><?= $pay_istax ?></td>
		</tr>
		<tr>
			<th>주문번호</th>
			<td><?= $pay_oid ?></td>
		</tr>
		<tr>
			<th>현금영수증</th>
			<td><?= $taxsave_flag ?></td>
		</tr>
	</table>
	<br>
	<button id="payAction" style="margin-left:260px">결제하기</button>
</body>

</html>