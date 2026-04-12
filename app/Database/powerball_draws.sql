-- 추첨 결과 보관 테이블 (5분마다 1회 추첨)
-- powerball DB에서 실행하세요.

USE powerball;

CREATE TABLE IF NOT EXISTS `draw_results` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `round` int(10) unsigned NOT NULL COMMENT '회차번호 (유일)',
  `daily_round` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'KST 게임일 기준 일회차(1~288, drawn_at 시각으로 결정)',
  `ball1` tinyint(2) unsigned NOT NULL COMMENT '일반볼 1 (1~28)',
  `ball2` tinyint(2) unsigned NOT NULL COMMENT '일반볼 2 (1~28)',
  `ball3` tinyint(2) unsigned NOT NULL COMMENT '일반볼 3 (1~28)',
  `ball4` tinyint(2) unsigned NOT NULL COMMENT '일반볼 4 (1~28)',
  `ball5` tinyint(2) unsigned NOT NULL COMMENT '일반볼 5 (1~28)',
  `powerball` tinyint(1) unsigned NOT NULL COMMENT '파워볼 (0~9)',
  `ball_sum` smallint(3) unsigned NOT NULL COMMENT '일반볼 5개 합계 (5~140)',
  `drawn_at` datetime NOT NULL COMMENT '추첨 시각(5분 단위: XX:00,05,10,...,55만 사용)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '레코드가 DB에 INSERT된 시각(시스템 기록용)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_round` (`round`),
  UNIQUE KEY `uk_drawn_at` (`drawn_at`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='추첨 결과 (5분 주기)';
