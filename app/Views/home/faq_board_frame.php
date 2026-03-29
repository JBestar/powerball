<?php
/**
 * mainFrame 전용 — 자주묻는질문 (선배 faq 스킨: 아코디언 + 검색)
 * 참고: https://powerballgame.co.kr/bbs/board.php?bo_table=faq
 */
$local = $local ?? rtrim(site_furl(''), '/');
$cssVer = $cssVer ?? '1';
$bo_table = $bo_table ?? 'faq';
$page = (int) ($page ?? 1);
$totalPages = (int) ($totalPages ?? 1);
$total = (int) ($total ?? 0);
$rows = $rows ?? [];
$sfl = $sfl ?? 'wr_subject';
$stx = $stx ?? '';
$sst = $sst ?? 'wr_datetime';
$sod = $sod ?? 'desc';
$sop = $sop ?? 'and';
$isLogin = $isLogin ?? false;
$login_uid = $login_uid ?? '';

$buildUrl = static function (array $q) use ($bo_table): string {
    $q = array_merge(['bo_table' => $bo_table], $q);
    $q = array_filter($q, static function ($v) {
        return $v !== null && $v !== '';
    });

    return site_furl('frame/communityBoard') . '?' . http_build_query($q);
};

$listBaseQuery = [
    'page' => $page,
    'sfl' => $sfl,
    'stx' => $stx,
    'sst' => $sst,
    'sod' => $sod,
    'sop' => $sop,
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?= esc($site_title ?? '자주묻는질문') ?></title>
	<link rel="stylesheet" href="<?= esc($local) ?>/css/default.css?v=<?= esc((string) $cssVer) ?>" type="text/css">
	<link rel="stylesheet" href="<?= esc($local) ?>/css/style.css?v=<?= esc((string) $cssVer) ?>" type="text/css">
	<link rel="stylesheet" href="<?= esc($local) ?>/css/bbs.css?v=<?= esc((string) $cssVer) ?>" type="text/css">
	<style>
		#bo_list .sound_only,
		nav.community-pg .sound_only,
		#bo_sch .sound_only {
			position: absolute !important;
			left: -9999px !important;
			top: auto !important;
			width: 1px !important;
			height: 1px !important;
			margin: -1px !important;
			padding: 0 !important;
			overflow: hidden !important;
			clip: rect(0, 0, 0, 0) !important;
			border: 0 !important;
			font-size: 0 !important;
			line-height: 0 !important;
		}
		nav.community-pg { padding: 14px 6px 22px; overflow: visible; clear: both; }
		nav.community-pg .pg_current {
			display: inline-block !important;
			position: relative !important;
			z-index: 2;
			min-width: 26px;
			min-height: 26px;
			line-height: 24px !important;
			font-size: 12px !important;
			padding: 2px 6px !important;
			box-sizing: border-box;
			vertical-align: middle !important;
		}
		#bo_sch { font-size: 12px; padding-bottom: 24px; }
		#bo_sch select, #bo_sch input, #bo_sch button { font-size: 12px; }
		body { padding-bottom: 8px; }
		#bo_list .tbl_head01 table.faqTable { table-layout: fixed; }
		#bo_list .tbl_head01 .faq .title a { cursor: pointer; }
		#bo_list .tbl_head01 th.subj { text-align: left; padding-left: 68px; }
		#bo_list .faq-content td { word-break: break-word; }
		#bo_list .faq-content img { max-width: 100%; height: auto; }
	</style>
	<script src="<?= esc($local) ?>/js/jquery-1.11.2.min.js"></script>
	<script>
	var COMMUNITY_FRAME_MIN = 400;
	function communityFrameResize() {
		try {
			var b = document.body, d = document.documentElement;
			var h = Math.max(
				b.scrollHeight || 0, d.scrollHeight || 0,
				b.offsetHeight || 0, d.offsetHeight || 0,
				$(b).outerHeight(true) || 0, $(d).outerHeight(true) || 0
			);
			h = Math.max(COMMUNITY_FRAME_MIN, Math.ceil(h)) + 56;
			if (window.parent && window.parent.frameAutoResize) {
				window.parent.frameAutoResize('mainFrame', h);
			}
		} catch (e) {}
	}
	function communityFrameResizeSoon() {
		if (window.requestAnimationFrame) {
			requestAnimationFrame(function () { requestAnimationFrame(communityFrameResize); });
		} else {
			setTimeout(communityFrameResize, 0);
		}
	}
	function scheduleCommunityFrameResize() {
		var delays = [0, 50, 150, 350, 700, 1200, 2200, 4000];
		for (var i = 0; i < delays.length; i++) {
			(function (ms) {
				setTimeout(function () { communityFrameResizeSoon(); }, ms);
			})(delays[i]);
		}
	}
	$(document).ready(function(){
		try { top.initAd(); } catch(e) {}
		$('.faq .title a').on('click', function(e){
			e.preventDefault();
			var id = 'content_' + $(this).attr('rel');
			var $row = $(this).closest('.faq');
			if ($('#' + id).is(':hidden')) {
				$('#' + id).show();
				$row.find('td').css('background-color', '#F1FCFE');
				$(this).css('font-weight', 'bold');
			} else {
				$row.find('td').css('background-color', '#fff');
				$(this).css('font-weight', 'normal');
				$('#' + id).hide();
			}
			scheduleCommunityFrameResize();
		});
		scheduleCommunityFrameResize();
	});
	$(window).on('load', function(){ scheduleCommunityFrameResize(); });
	$(window).on('pageshow', function (ev) {
		if (ev.originalEvent && ev.originalEvent.persisted) scheduleCommunityFrameResize();
	});
	</script>
</head>
<body>
<?php if ($isLogin && $login_uid !== ''): ?>
<div id="hd_login_msg"><?= esc($login_uid) ?>님 로그인 중 <a href="<?= esc(site_furl('/logout')) ?>">로그아웃</a></div>
<?php endif; ?>

<div id="bo_list" style="width:100%">
	<div class="tbl_head01 tbl_wrap">
		<table class="faqTable">
		<caption class="sound_only">자주묻는질문 목록</caption>
		<colgroup>
			<col style="width:50px">
			<col>
		</colgroup>
		<thead>
		<tr>
			<th scope="col">번호</th>
			<th scope="col" class="subj"><span class="line">제목</span></th>
		</tr>
		</thead>
		<tbody>
		<?php
        $listNumBase = $total - ($page - 1) * (int) ($perPage ?? 50);
	    foreach ($rows as $idx => $row) :
	        $hid = (int) ($row->id ?? 0);
	        $title = (string) ($row->title ?? '');
	        $content = (string) ($row->content ?? '');
	        $numShow = $listNumBase - $idx;
	        ?>
		<tr class="faq">
			<td class="td_num"><?= $hid > 0 ? $numShow : '' ?></td>
			<td class="title" style="padding-left:10px;"><a href="#" onclick="return false;" rel="<?= $hid ?>"><?= esc($title) ?></a></td>
		</tr>
		<tr id="content_<?= $hid ?>" class="faq-content" style="display:none;background-color:#f8f8f8;">
			<td colspan="2" style="padding:20px;"><?= $content ?></td>
		</tr>
		<?php endforeach; ?>
		<?php if ($total === 0 && count($rows) === 0): ?>
		<tr><td colspan="2" style="text-align:center;padding:24px;color:#999;">등록된 글이 없습니다.</td></tr>
		<?php endif; ?>
		</tbody>
		</table>
	</div>

	<div class="bo_fx">
		<ul class="btn_bo_user"></ul>
	</div>
</div>

<?php
$window = 10;
$half = (int) floor($window / 2);
$startPg = max(1, $page - $half);
$endPg = min($totalPages, $startPg + $window - 1);
if ($endPg - $startPg < $window - 1) {
    $startPg = max(1, $endPg - $window + 1);
}
$pgQ = $listBaseQuery;
?>
<nav class="pg_wrap community-pg" aria-label="페이지"><span class="pg">
<?php if ($page > 1): ?>
	<a href="<?= esc($buildUrl(array_merge($pgQ, ['page' => $page - 1]))) ?>" class="pg_page pg_prev">&lt;</a>
<?php endif; ?>
<?php for ($p = $startPg; $p <= $endPg; $p++) : ?>
	<?php if ($p === $page): ?>
		<span class="sound_only">열린</span><strong class="pg_current"><?= $p ?></strong><span class="sound_only">페이지</span>
	<?php else: ?>
		<a href="<?= esc($buildUrl(array_merge($pgQ, ['page' => $p]))) ?>" class="pg_page"><?= $p ?><span class="sound_only">페이지</span></a>
	<?php endif; ?>
<?php endfor; ?>
<?php if ($page < $totalPages): ?>
	<a href="<?= esc($buildUrl(array_merge($pgQ, ['page' => $page + 1]))) ?>" class="pg_page pg_next">&gt;</a>
	<a href="<?= esc($buildUrl(array_merge($pgQ, ['page' => $totalPages]))) ?>" class="pg_page pg_end">&gt;&gt;</a>
<?php endif; ?>
</span></nav>

<fieldset id="bo_sch">
	<legend class="sound_only">게시물 검색</legend>
	<form name="fsearch" method="get" action="<?= esc(site_furl('frame/communityBoard')) ?>">
		<input type="hidden" name="bo_table" value="faq">
		<input type="hidden" name="sca" value="">
		<input type="hidden" name="sop" value="and">
		<label for="sfl" class="sound_only">검색대상</label>
		<select name="sfl" id="sfl">
			<option value="wr_subject"<?= $sfl === 'wr_subject' ? ' selected' : '' ?>>제목</option>
			<option value="wr_content"<?= $sfl === 'wr_content' ? ' selected' : '' ?>>내용</option>
			<option value="wr_subject||wr_content"<?= $sfl === 'wr_subject||wr_content' ? ' selected' : '' ?>>제목+내용</option>
			<option value="wr_name,1"<?= $sfl === 'wr_name,1' ? ' selected' : '' ?>>글쓴이</option>
		</select>
		<label for="stx" class="sound_only">검색어</label>
		<input type="text" name="stx" value="<?= esc($stx) ?>" id="stx" class="frm_input" size="15" maxlength="50">
		<button type="submit" class="btn_b01" style="height:24px;padding:0 10px;cursor:pointer;">검색</button>
	</form>
</fieldset>
</body>
</html>
