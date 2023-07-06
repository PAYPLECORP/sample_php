# Sample_php

🏠[페이플 홈페이지](https://www.payple.kr/)<br>
페이플 결제 서비스는 간편결제, 정기결제와 같은 <br>
새로운 비즈니스모델과 서비스를 지원하기 위해 다양한 결제방식을 제공합니다.
<br><br>

## Update (2023.07.06)
결제창 호출 요청 전 프로세스인 파트너 인증 방식이 새롭게 변경되어 코드에 반영되었습니다!<br>
이제 클라이언트 단에서 키 값 하나로(clientKey) 더 빠르고 쉬운 파트너 인증을 통한 결제창 호출을 할 수 있습니다.🧑‍💻
<br><br>

## Documentation

📂 payple/inc/**config.php** 계정 관리 파일 ([계정발급 방법](https://developer.payple.kr/quick/account))<br>
#### 결제연동
>📂 **order.php &nbsp;:** &nbsp;상품 주문<br>
>📂 **order_confirm.php &nbsp;:** &nbsp;주문확정 및 결제<br>
>📂 **order_result.php &nbsp;:** &nbsp;결제결과<br>
#### 기타 API (.html, .php)
>📂 **linkReg &nbsp;:** &nbsp;URL링크결제<br>
>📂 **payCertSend &nbsp;:** &nbsp;결제요청 재컨펌(CERT) 방식<br>
>📂 **payInfo &nbsp;:** &nbsp;결제결과 조회<br> 
>📂 **payRefund &nbsp;:** &nbsp;결제취소<br>
>📂 **paySimpleCardSend &nbsp;:** &nbsp;카드 정기결제 재결제<br>
>📂 **paySimpleSend &nbsp;:** &nbsp;계좌 정기결제 재결제<br>
>📂 **payUserDel &nbsp;:** &nbsp;등록해지<br>
>📂 **payUserInfo &nbsp;:** &nbsp;등록조회<br>
>📂 **taxSaveReq &nbsp;:** &nbsp;현금영수증 발행/취소<br>
<br>

🙋‍ [페이플 API](https://developer.payple.kr) 보러가기

