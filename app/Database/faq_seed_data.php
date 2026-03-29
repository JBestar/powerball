<?php

declare(strict_types=1);

/**
 * 자주묻는질문 초기 데이터 (선배 https://powerballgame.co.kr/bbs/board.php?bo_table=faq 본문 HTML)
 * 삽입 순서: id 1=번호1 … id 13=번호13, 목록은 id DESC 로 번호 13~1 표시
 */
$dir = __DIR__ . '/faq_chunks';
$load = static function (string $file) use ($dir): string {
    $p = $dir . '/' . $file;
    return is_file($p) ? (string) file_get_contents($p) : '';
};

return [
    ['title' => '회원가입은 어떻게 하나요?', 'content' => $load('01.html')],
    ['title' => '회원 탈퇴를 하려면 어떻게 하나요?', 'content' => $load('02.html')],
    ['title' => '코인은 어디에 사용하나요?', 'content' => $load('03.html')],
    ['title' => '파워볼게임은 무료로 이용 가능한가요?', 'content' => $load('04.html')],
    ['title' => '아이템 구매 후 사용법이 궁금합니다.', 'content' => $load('05.html')],
    ['title' => '파워볼게임은 배팅사이트인가요?', 'content' => $load('06.html')],
    ['title' => '블랙리스트 삭제는 어떻게 하나요?', 'content' => $load('07.html')],
    ['title' => '건빵과 총알 아이템이 궁금합니다.', 'content' => $load('08.html')],
    ['title' => '개인채팅방 개설은 어떻게 하나요?', 'content' => $load('09.html')],
    ['title' => '개인채팅방 방장픽 기능은 어떻게 사용하나요?', 'content' => $load('10.html')],
    ['title' => '파워볼게임(PBG)는 무엇인가요?', 'content' => $load('11.html')],
    ['title' => '파워볼게임(PBG)의 추첨결과 연산 시스템이란?', 'content' => $load('12.html')],
    ['title' => '파워볼게임(PBG)의 이용가능 시간은 ?', 'content' => $load('13.html')],
];
