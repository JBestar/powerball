<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<title>유머 내용</title>
	<?php
$local = rtrim(site_furl(''), '/');
$is_humor_admin = $is_humor_admin ?? false;
$humorId = !empty($post) ? (int) ($post->id ?? 0) : 0;
?>
	<link rel="stylesheet" href="<?php echo $local; ?>/css/common_logged.css?v=<?php echo time(); ?>" type="text/css"/>
	<style>
		body { color:#000; margin:0; background:#f5f5f5; }
		.wrap { padding:14px; }
		/* 제목+본문 한 틀 */
		.humorPanel {
			border:1px solid #d5d5d5;
			border-radius:4px;
			overflow:hidden;
			background:#fff;
			box-shadow:0 1px 3px rgba(0,0,0,0.06);
		}
		.humorPanel-title {
			background:#e8e8e8;
			color:#127CCB;
			font-size:16px;
			font-weight:bold;
			line-height:1.4;
			padding:12px 14px;
			border-bottom:1px solid #d5d5d5;
		}
		.humorPanel-body {
			font-size:14px;
			line-height:1.55;
			padding:14px;
			white-space:pre-wrap !important;
			word-break:break-word;
			color:#222;
			min-height:2em;
		}
		.meta { color:#777; font-size:12px; margin-top:10px; padding:0 2px; }
		.humorBtnRow { text-align:right; margin-top:10px; }
		.humorBtnRow .btn { display:inline-block; margin-top:10px; margin-left:6px; background:#efefef; color:#000; font-weight:bold; border:1px solid #cecece; padding:8px 14px; cursor:pointer; text-decoration:none; font-size:12px; vertical-align:middle; }
		.humorBtnRow .btn:first-child { margin-left:0; }
		.humorBtnRow .btnHumorEdit { border-color:#0e609c; color:#0e609c; background:#fff; }
		.humorBtnRow .btnHumorDel { border-color:#c11a20; color:#c11a20; background:#fff; }
		.humorPanel-empty { border:1px solid #d5d5d5; padding:14px; background:#fff; font-size:14px; }
	</style>
</head>
<body>
	<div class="wrap">
		<?php if (!empty($post)): ?>
			<div class="humorPanel">
				<div class="humorPanel-title"><?= esc($post->title ?? '') ?></div>
				<div class="humorPanel-body"><?= esc($post->content ?? '') ?></div>
			</div>
			<div class="meta">등록자: <?= esc($post->mb_uid ?? '') ?> / id: <?= $humorId ?></div>
			<div class="humorBtnRow">
				<?php if ($is_humor_admin && $humorId > 0): ?>
					<a class="btn btnHumorEdit" href="#" onclick="window.open('<?= site_furl('/?view=humorEdit&id=' . $humorId) ?>','humorEdit','width=600,height=650'); return false;">수정</a>
					<a class="btn btnHumorDel" href="<?= esc(site_furl('/?view=humorDelete&id=' . $humorId)) ?>" onclick="if(!confirm('정말 삭제하시겠습니까?')) return false;">삭제</a>
				<?php endif; ?>
				<a class="btn" href="#" onclick="window.close(); return false;">닫기</a>
			</div>
		<?php else: ?>
			<div class="humorPanel-empty">유머 내용을 찾을 수 없습니다.</div>
		<?php endif; ?>
	</div>

	<script type="text/javascript">
		// 등록/수정 후 메인 리스트를 갱신
		try {
			if (window.opener && !window.opener.closed) {
				window.opener.location.reload();
			}
		} catch (e) {}
	</script>
</body>
</html>

