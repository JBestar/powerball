-- 포토 리스트용 테이블 (유저 업로드 포토 메타정보)
-- 실제 이미지 파일은 서버 폴더(public/uploads/photos/)에 저장하고, 여기엔 경로만 저장합니다.
-- powerball DB에서 실행하세요.

USE powerball;

CREATE TABLE IF NOT EXISTS `board_photo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wr_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '글번호(상세 링크에 사용)',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '제목',
  `file_path` varchar(255) NOT NULL DEFAULT '' COMMENT '저장 경로(예: 2025/03/abc.jpg, 기준: public/uploads/photos/)',
  `created_at` datetime DEFAULT NULL,
  `mb_uid` int(10) unsigned DEFAULT NULL COMMENT '등록자 회원번호',
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='포토 게시 목록';

-- 예시 데이터 (이미지 파일을 public/uploads/photos/ 에 넣고 file_path만 맞추면 됨)
-- INSERT INTO `board_photo` (`wr_id`, `title`, `file_path`, `created_at`) VALUES
-- (1, '첫 포토', 'sample1.jpg', NOW()),
-- (2, '두번째 포토', 'sample2.jpg', NOW());
