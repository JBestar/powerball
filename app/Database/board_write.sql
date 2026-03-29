-- 메인 페이지 리스트박스(유머/포토·분석픽공유/자유)용 게시 목록 테이블
-- powerball DB에서 실행하세요.

USE powerball;

CREATE TABLE IF NOT EXISTS `board_write` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bo_table` varchar(20) NOT NULL COMMENT '게시판구분: humor, pick, free',
  `wr_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '글번호(링크에 사용)',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '제목',
  `comment_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '댓글수',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bo_table` (`bo_table`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='메인 리스트박스 게시 목록';

-- 예시 데이터 (선택)
INSERT INTO `board_write` (`bo_table`, `wr_id`, `title`, `comment_count`, `created_at`) VALUES
('humor', 1, '내일 지구멸망하면', 1, NOW()),
('humor', 2, '삼겹살파티구먼', 8, NOW()),
('humor', 3, '오늘저녁은 치킨 두마리구먼', 1, NOW()),
('humor', 4, '당신은 왜 공부하나요?', 1, NOW()),
('humor', 5, '■섬남에게 고백한 여자', 3, NOW()),
('humor', 6, '짱구아빠', 2, NOW()),
('pick', 1, '실시간 분석 픽 공유합니다', 0, NOW()),
('pick', 2, '오늘 회차 추천 픽', 5, NOW()),
('free', 1, '자유게시판 첫 글', 0, NOW()),
('free', 2, '안녕하세요', 2, NOW());
