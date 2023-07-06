<?php
/**
 * 테스트 계정 및 요청 URL 관리
 * - 파트너 인증 URL과 페이플 계정(cst_id, custKey) 및 환불키(refundKey), SERVERNAME 관리
 * - 클라이언트 키(clientKey) 방식 추가 (Updated: 2023-07-06)
 */

/* 파트너 인증 Request URL */
//$url = "https://cpay.payple.kr/php/auth.php";       // REAL
$url = "https://democpay.payple.kr/php/auth.php";   // TEST

/* 테스트 계정 */
$cst_id = "test";
$custKey = "abcd1234567890";

/* 테스트 클라이언트 키 */
$clientKey = "test_DF55F29DA654A8CBC0F0A9DD4B556486";

/* 테스트 환불키 */
$refundKey = "a41ce010ede9fcbfb3be86b24858806596a9db68b79d138b147c3e563e1829a0";

/* Server Name */
$SERVER_NAME = $_SERVER['HTTP_HOST'];
