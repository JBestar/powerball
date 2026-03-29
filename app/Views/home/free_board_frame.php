<?php
/**
 * mainFrame 전용 — 선배님 자유게시판 (community 스킨 테이블)
 * 참고: https://powerballgame.co.kr/bbs/board.php?bo_table=free
 */
$local = $local ?? rtrim(site_furl(''), '/');
$cssVer = $cssVer ?? '1';
$bo_table = $bo_table ?? 'free';
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
$is_free_admin = $is_free_admin ?? false;
$wr_id = (int) ($wr_id ?? 0);
$read_post = $read_post ?? null;
$free_notices = $free_notices ?? [];
$free_newer_id = isset($free_newer_id) ? ($free_newer_id === null ? null : (int) $free_newer_id) : null;
$free_older_id = isset($free_older_id) ? ($free_older_id === null ? null : (int) $free_older_id) : null;
$read_author_nick = (string) ($read_author_nick ?? '');
$read_author_grade = (int) ($read_author_grade ?? 2);
$icoNotice = site_furl('images/ico_notice.png');

$buildUrl = static function (array $q) use ($bo_table): string {
    $q = array_merge(['bo_table' => $bo_table], $q);
    $q = array_filter($q, static function ($v) {
        return $v !== null && $v !== '';
    });

    return site_furl('frame/communityBoard') . '?' . http_build_query($q);
};

$listBaseQuery = [
    'page' => $page,
    'wr_id' => null,
    'sfl' => $sfl,
    'stx' => $stx,
    'sst' => $sst,
    'sod' => $sod,
    'sop' => $sop,
];

$sortUrl = static function (string $col, string $dir) use ($buildUrl, $sfl, $stx, $sop): string {
    return $buildUrl([
        'page' => 1,
        'wr_id' => null,
        'sop' => $sop,
        'sst' => $col,
        'sod' => $dir,
        'sfl' => $sfl,
        'stx' => $stx,
    ]);
};
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?= esc($site_title ?? '자유게시판') ?></title>
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
	</style>
	<script src="<?= esc($local) ?>/js/jquery-1.11.2.min.js"></script>
	<?php if ($read_post): ?>
	<script src="<?= esc($local) ?>/js/viewimageresize.js"></script>
	<?php endif; ?>
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
			} else if (window.parent && window.parent.document) {
				var frame = window.parent.document.getElementById('mainFrame');
				if (frame) frame.style.height = h + 'px';
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
		<?php if ($read_post): ?>
		$("#bo_v_atc").viewimageresize();
		$(window).on('resize', function () { communityFrameResizeSoon(); });
		<?php endif; ?>
		scheduleCommunityFrameResize();
	});
	$(window).on('load', function(){
		<?php if ($read_post): ?>
		try { $(window).trigger('resize'); } catch (e) {}
		<?php endif; ?>
		scheduleCommunityFrameResize();
	});
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
	<div class="categoryTit">
		<ul style="background-color:#F1F1F1; overflow:hidden;">
			<li><a href="<?= esc(site_furl('frame/communityBoard?bo_table=humor')) ?>">유머</a></li>
			<li><a href="<?= esc(site_furl('frame/communityBoard?bo_table=photo')) ?>">포토</a></li>
			<li><a href="<?= esc(site_furl('frame/communityBoard?bo_table=pick')) ?>">분석픽공유</a></li>
			<li><a href="<?= esc($buildUrl(['page' => 1, 'sfl' => 'wr_subject', 'stx' => '', 'wr_id' => null])) ?>" class="on">자유</a></li>
		</ul>
	</div>

	<?php if ($read_post) :
	    $rpId = (int) ($read_post->id ?? 0);
	    $rpTitle = (string) ($read_post->title ?? '');
	    $rpContent = (string) ($read_post->content ?? '');
	    $rpTime = $read_post->created_at ?? '';
	    $rpDateShow = $rpTime ? date('Y-m-d H:i', strtotime((string) $rpTime)) : '';
	    $rpCc = (int) ($read_post->comment_count ?? 0);
	    $rpHit = (int) ($read_post->wr_hit ?? 0);
	    $readGif = site_furl('images/class/M' . $read_author_grade . '.gif');
	    ?>
	<article id="bo_v">
		<div class="viewinfo">
			<div class="thumb"><img src="<?= esc(site_furl('images/profile.png')) ?>" alt=""></div>
			<div class="title"><h1><?= esc($rpTitle) ?></h1></div>
			<div class="info">
				<img src="<?= esc($readGif) ?>" alt="" onerror="this.style.display='none'">
				<a href="#" class="uname" onclick="return false;"><span class="sv_member"><?= esc($read_author_nick) ?></span></a>
				<span class="bar">|</span>
				<?= esc($rpDateShow) ?>
				<span class="bar">|</span>
				조회 <?= number_format($rpHit) ?>
				<span class="bar">|</span>
				댓글 <?= $rpCc ?>
			</div>
		</div>
		<section id="bo_v_atc">
			<div id="bo_v_con"><?= nl2br(esc($rpContent), false) ?></div>
			<div id="bo_v_act">
				<span class="bo_v_act_gng">
					<a href="#" id="good_button" onclick="return false;"><strong><?= number_format((int) ($read_post->wr_good ?? 0)) ?></strong><span>추천</span></a>
					<b id="bo_v_act_good"></b>
				</span>
			</div>
		</section>
	</article>

	<div id="bo_v_top">
		<ul class="bo_v_nb">
			<?php if ($free_newer_id): ?>
			<li><a href="<?= esc($buildUrl(array_merge($listBaseQuery, ['wr_id' => $free_newer_id]))) ?>" class="btn_b01">이전글</a></li>
			<?php endif; ?>
			<?php if ($free_older_id): ?>
			<li><a href="<?= esc($buildUrl(array_merge($listBaseQuery, ['wr_id' => $free_older_id]))) ?>" class="btn_b01">다음글</a></li>
			<?php endif; ?>
		</ul>
		<ul class="bo_v_com">
			<li><a href="<?= esc($buildUrl($listBaseQuery)) ?>" class="btn_b01">목록</a></li>
			<li><a href="#" class="btn_b02" onclick="<?php if ($isLogin): ?>window.open('<?= esc(site_furl('/?view=freeRegister')) ?>','freeRegister','width=600,height=650');<?php else: ?>alert('로그인 후 이용가능합니다.');<?php endif ?> return false;">글쓰기</a></li>
		</ul>
	</div>
	<?php endif; ?>

	<div class="tbl_head01 tbl_wrap">
		<table>
		<caption class="sound_only">자유게시판 목록</caption>
		<colgroup>
			<col style="width:60px">
			<col>
			<col style="width:150px">
			<col style="width:70px">
			<col style="width:60px">
			<col style="width:60px">
			<?php if ($is_free_admin): ?><col style="width:88px"><?php endif; ?>
		</colgroup>
		<thead>
		<tr>
			<th scope="col">번호</th>
			<th scope="col">제목</th>
			<th scope="col">글쓴이</th>
			<th scope="col"><a href="<?= esc($sortUrl('wr_datetime', $sst === 'wr_datetime' && $sod === 'desc' ? 'asc' : 'desc')) ?>">날짜</a></th>
			<th scope="col"><a href="<?= esc($sortUrl('wr_hit', $sst === 'wr_hit' && $sod === 'desc' ? 'asc' : 'desc')) ?>">조회</a></th>
			<th scope="col"><a href="<?= esc($sortUrl('wr_good', $sst === 'wr_good' && $sod === 'desc' ? 'asc' : 'desc')) ?>">추천</a></th>
			<?php if ($is_free_admin): ?><th scope="col">관리</th><?php endif; ?>
		</tr>
		</thead>
		<tbody>
		<?php if ($page === 1 && !empty($free_notices)) :
		    foreach ($free_notices as $free_notice) :
		    $nid = (int) ($free_notice->id ?? 0);
		    $ntitle = (string) ($free_notice->title ?? '');
		    $ncc = (int) ($free_notice->comment_count ?? 0);
		    $nUid = (string) ($free_notice->mb_uid ?? '');
		    $nmb = trim((string) ($free_notice->mb_nickname ?? ''));
		    if ($nmb === '') {
		        $nmb = $nUid !== '' ? $nUid : '운영자';
		    }
		    $nhit = (int) ($free_notice->wr_hit ?? 0);
		    $ngood = (int) ($free_notice->wr_good ?? 0);
		    $nTime = $free_notice->created_at ?? '';
		    $nDateShow = $nTime ? date('m-d', strtotime((string) $nTime)) : '';
		    $nGrade = (strpos($nUid, '운영') !== false || $nUid === 'operator') ? 30 : 2;
		    if ($nGrade > 20) {
		        $nGrade = 20;
		    }
		    $nGif = site_furl('images/class/M' . $nGrade . '.gif');
		    ?>
		<tr class="bo_notice">
			<td class="td_num"><img src="<?= esc($icoNotice) ?>" style="vertical-align:top;" alt="공지"></td>
			<td class="td_subject">
				<a href="<?= esc($buildUrl(array_merge($listBaseQuery, ['wr_id' => $nid]))) ?>"><?= esc($ntitle) ?>
					<?php if ($ncc > 0): ?><span class="sound_only">댓글</span><span class="cnt_cmt">[<?= $ncc ?>]</span><span class="sound_only">개</span><?php endif; ?>
				</a>
			</td>
			<td class="td_name sv_use"><img src="<?= esc($nGif) ?>" alt="" onerror="this.style.display='none'"> <span class="sv_member"><?= esc($nmb) ?></span></td>
			<td class="td_date"><?= esc($nDateShow) ?></td>
			<td class="td_num"><?= number_format($nhit) ?></td>
			<td class="td_num"><?= number_format($ngood) ?></td>
			<?php if ($is_free_admin): ?><td></td><?php endif; ?>
		</tr>
		<?php
		    endforeach;
		    endif; ?>
		<?php
        $listNumBase = $total - ($page - 1) * (int) ($perPage ?? 10);
	    foreach ($rows as $idx => $row) :
	        $hid = (int) ($row->id ?? 0);
	        $title = (string) ($row->title ?? '');
	        $cc = (int) ($row->comment_count ?? 0);
	        $mb = trim((string) ($row->mb_nickname ?? ''));
	        if ($mb === '') {
	            $mb = (string) ($row->mb_uid ?? '');
	        }
	        $hit = (int) ($row->wr_hit ?? 0);
	        $good = (int) ($row->wr_good ?? 0);
	        $dRaw = $row->created_at ?? '';
	        $dShow = $dRaw ? date('m-d', strtotime((string) $dRaw)) : '';
	        $numShow = $listNumBase - $idx;
	        $rowRead = ($wr_id > 0 && $hid === $wr_id);
	        ?>
		<tr class="<?= $rowRead ? 'bo_read' : '' ?>">
			<td class="td_num"><?= $hid > 0 ? $numShow : '' ?></td>
			<td class="td_subject">
				<a href="<?= esc($buildUrl(array_merge($listBaseQuery, ['wr_id' => $hid]))) ?>"><?= esc($title) ?>
					<?php if ($cc > 0): ?><span class="sound_only">댓글</span><span class="cnt_cmt">[<?= $cc ?>]</span><span class="sound_only">개</span><?php endif; ?>
				</a>
			</td>
			<td class="td_name sv_use"><img src="<?= esc(site_furl('images/class/M2.gif')) ?>" alt=""> <span class="sv_member"><?= esc($mb) ?></span></td>
			<td class="td_date"><?= esc($dShow) ?></td>
			<td class="td_num"><?= number_format($hit) ?></td>
			<td class="td_num"><?= number_format($good) ?></td>
			<?php if ($is_free_admin): ?>
			<td class="td_mng" style="text-align:right; white-space:nowrap; font-size:10px;">
				<a href="#" onclick="window.open('<?= esc(site_furl('/?view=freeEdit&id=' . $hid)) ?>','freeEdit','width=600,height=650'); return false;" style="color:#0e609c;">수정</a>
				<a href="<?= esc(site_furl('/?view=freeDelete&id=' . $hid)) ?>" onclick="return confirm('정말 삭제하시겠습니까?');" style="color:#c11a20; margin-left:4px;">삭제</a>
			</td>
			<?php endif; ?>
		</tr>
		<?php endforeach; ?>
		<?php if ($total === 0 && count($rows) === 0 && empty($free_notices)): ?>
		<tr><td colspan="<?= $is_free_admin ? 7 : 6 ?>" style="text-align:center;padding:24px;color:#999;">등록된 글이 없습니다.</td></tr>
		<?php endif; ?>
		</tbody>
		</table>
	</div>

	<div class="bo_fx">
		<ul class="btn_bo_user">
			<li><a href="#" class="btn_b02" onclick="<?php if ($isLogin): ?>window.open('<?= esc(site_furl('/?view=freeRegister')) ?>','freeRegister','width=600,height=650');<?php else: ?>alert('로그인 후 이용가능합니다.');<?php endif ?> return false;">글쓰기</a></li>
		</ul>
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
$pgQ = array_merge($listBaseQuery, ['wr_id' => null]);
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
		<input type="hidden" name="bo_table" value="free">
		<input type="hidden" name="sca" value="">
		<input type="hidden" name="sop" value="and">
		<label for="sfl" class="sound_only">검색대상</label>
		<select name="sfl" id="sfl">
			<option value="wr_subject"<?= $sfl === 'wr_subject' ? ' selected' : '' ?>>제목</option>
			<option value="wr_content"<?= $sfl === 'wr_content' ? ' selected' : '' ?>>내용</option>
			<option value="wr_subject||wr_content"<?= $sfl === 'wr_subject||wr_content' ? ' selected' : '' ?>>제목+내용</option>
			<option value="mb_id,1"<?= $sfl === 'mb_id,1' ? ' selected' : '' ?>>회원아이디</option>
			<option value="mb_id,0"<?= $sfl === 'mb_id,0' ? ' selected' : '' ?>>회원아이디(코)</option>
			<option value="wr_name,1"<?= $sfl === 'wr_name,1' ? ' selected' : '' ?>>글쓴이</option>
			<option value="wr_name,0"<?= $sfl === 'wr_name,0' ? ' selected' : '' ?>>글쓴이(코)</option>
		</select>
		<label for="stx" class="sound_only">검색어</label>
		<input type="text" name="stx" value="<?= esc($stx) ?>" id="stx" class="frm_input" size="15" maxlength="50">
		<button type="submit" class="btn_b01" style="height:24px;padding:0 10px;cursor:pointer;">검색</button>
	</form>
</fieldset>

</body>
</html>
