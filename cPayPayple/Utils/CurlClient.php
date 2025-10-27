<?php
/**
 * CurlClient - cURL 요청을 처리하는 공통 클래스
 *
 * Payple API 호출 시 반복되는 cURL 패턴을 캡슐화하여
 * 코드 중복을 제거하고 유지보수성을 향상시킵니다.
 *
 * @author Payple Sample
 * @version 1.0.0
 */
class CurlClient
{
    /**
     * POST 요청을 보내고 JSON 응답을 반환합니다.
     *
     * @param string $url 요청 URL
     * @param array $data 전송할 데이터 (배열 형태)
     * @param string $serverName 서버 도메인 (Referer 헤더용)
     * @return object|null JSON 디코딩된 객체, 실패 시 null
     * @throws Exception cURL 실행 실패 시
     */
    public static function post($url, $data, $serverName = '')
    {
        // 데이터를 JSON으로 인코딩
        $postData = json_encode($data);

        // HTTP 헤더 설정
        $headers = array(
            "cache-control: no-cache",
            "content-type: application/json; charset=UTF-8"
        );

        // Referer 헤더 추가 (옵션)
        if (!empty($serverName)) {
            $headers[] = "referer: http://$serverName";
        }

        // cURL 초기화
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // TEST 환경용 (운영에서는 true 권장)
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // cURL 실행
        $response = curl_exec($ch);

        // cURL 에러 체크
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL 요청 실패: $error");
        }

        curl_close($ch);

        // JSON 디코딩 (CURLOPT_RETURNTRANSFER=true 이므로 $response 사용)
        $result = json_decode($response);

        return $result;
    }

    /**
     * HTTP 캐시 방지 헤더를 설정합니다.
     *
     * API 응답이 브라우저나 프록시에 캐시되는 것을 방지합니다.
     */
    public static function setNoCacheHeaders()
    {
        header("Expires: Mon 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d, M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0; pre-check=0", false);
        header("Pragma: no-cache");
    }

    /**
     * JSON 응답 헤더를 설정합니다.
     */
    public static function setJsonHeader()
    {
        header("Content-type: application/json; charset=utf-8");
    }

    /**
     * API 공통 헤더를 설정합니다 (캐시 방지 + JSON).
     */
    public static function setApiHeaders()
    {
        self::setNoCacheHeaders();
        self::setJsonHeader();
    }
}