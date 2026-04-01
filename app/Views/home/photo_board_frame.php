<?php
/**
 * mainFrame 전용 — 선배님 포토 게시판(photoList 그리드 + 페이징·검색)
 * 참고: https://powerballgame.co.kr/bbs/board.php?bo_table=photo
 */
$local = $local ?? rtrim(site_furl(''), '/');
$cssVer = $cssVer ?? '1';
$bo_table = $bo_table ?? 'photo';
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
$is_photo_admin = $is_photo_admin ?? false;
$wr_id = (int) ($wr_id ?? 0);
$read_post = $read_post ?? null;
$photo_newer_id = isset($photo_newer_id) ? ($photo_newer_id === null ? null : (int) $photo_newer_id) : null;
$photo_older_id = isset($photo_older_id) ? ($photo_older_id === null ? null : (int) $photo_older_id) : null;
$read_author_nick = (string) ($read_author_nick ?? '');
$read_author_grade = (int) ($read_author_grade ?? 2);

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
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?= esc($site_title ?? '포토게시판') ?></title>
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
		nav.community-pg {
			padding: 14px 6px 22px;
			overflow: visible;
			clear: both;
		}
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
		.photoList { zoom: 1; overflow: hidden; }
		.photoList:after { content: ''; display: block; clear: both; }
		.photoList li {
			position: relative;
			float: left;
			padding: 0;
			margin: 3px;
			border: 1px solid #d2d2d2;
			background-color: #F1F1F1;
		}
		.photoList li.none { margin: 3px 0 3px 3px; }
		.photoList li a .thumb { display: block; }
		.photoList li a .thumb img { width: 200px; height: 200px; object-fit: cover; }
		.photoList ul li .title {
			position: absolute;
    		bottom: 0;
    		background-color: rgba(13,86,140,0.75);
    		width: 100%;
		}
		.photoList ul li .title a {
			height: 30px;
    		line-height: 30px;
    		padding: 0 5px;
    		color: #fff;
		}
		.photoList ul li .title .comment { color: #C11A20; padding-left: 3px; }
		.photoList li.photo-current { box-shadow: inset 0 0 0 2px #127CCB; }
		#bo_v_photo { margin-bottom: 12px; }
		#bo_v_photo .photo-view-img { max-width: 100%; height: auto; display: block; margin: 0 auto; }
	</style>
	<script src="<?= esc($local) ?>/js/jquery-1.11.2.min.js"></script>
	<script>
	var COMMUNITY_FRAME_MIN = 400;
	function communityFrameResize() {
		try {
			var b = document.body, d = document.documentElement;
			var h = Math.max(
				b.scrollHeight || 0,
				d.scrollHeight || 0,
				b.offsetHeight || 0,
				d.offsetHeight || 0,
				$(b).outerHeight(true) || 0,
				$(d).outerHeight(true) || 0
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
			requestAnimationFrame(function () {
				requestAnimationFrame(communityFrameResize);
			});
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
		scheduleCommunityFrameResize();
	});
	$(window).on('load', function(){ scheduleCommunityFrameResize(); });
	$(window).on('pageshow', function (ev) {
		if (ev.originalEvent && ev.originalEvent.persisted) {
			scheduleCommunityFrameResize();
		}
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
			<li><a href="<?= esc($buildUrl(['page' => 1, 'sfl' => 'wr_subject', 'stx' => '', 'wr_id' => null])) ?>" class="on">포토</a></li>
			<li><a href="<?= esc(site_furl('frame/communityBoard?bo_table=pick')) ?>">분석픽공유</a></li>
			<li><a href="<?= esc(site_furl('frame/communityBoard?bo_table=free')) ?>">자유</a></li>
		</ul>
	</div>

	<?php if ($read_post) :
	    $rpId = (int) ($read_post->id ?? 0);
	    $rpTitle = (string) ($read_post->title ?? '');
	    $rpFile = (string) ($read_post->file_path ?? '');
	    $rpImg = $rpFile !== '' ? site_furl('uploads/photos/' . $rpFile) : '';
	    $rpTime = $read_post->created_at ?? '';
	    $rpDateShow = $rpTime ? date('Y-m-d H:i', strtotime((string) $rpTime)) : '';
	    $readGif = site_furl('images/class/M' . $read_author_grade . '.gif');
	    ?>
	<article id="bo_v" class="photo-read">
		<div class="viewinfo">
			<div class="thumb"><img src="<?= esc(site_furl('images/profile.png')) ?>" alt=""></div>
			<div class="title"><h1><?= esc($rpTitle) ?></h1></div>
			<div class="info">
				<img src="<?= esc($readGif) ?>" alt="" onerror="this.style.display='none'">
				<a href="#" class="uname" onclick="return false;"><span class="sv_member"><?= esc($read_author_nick) ?></span></a>
				<span class="bar">|</span>
				<?= esc($rpDateShow) ?>
			</div>
		</div>
		<section id="bo_v_atc">
			<div id="bo_v_photo">
				<?php if ($rpImg !== ''): ?>
				<img src="<?= esc($rpImg) ?>" alt="<?= esc($rpTitle) ?>" class="photo-view-img">
				<?php endif; ?>
			</div>
		</section>
	</article>

	<div id="bo_v_top">
		<ul class="bo_v_nb">
			<?php if ($photo_newer_id): ?>
			<li><a href="<?= esc($buildUrl(array_merge($listBaseQuery, ['wr_id' => $photo_newer_id]))) ?>" class="btn_b01">이전글</a></li>
			<?php endif; ?>
			<?php if ($photo_older_id): ?>
			<li><a href="<?= esc($buildUrl(array_merge($listBaseQuery, ['wr_id' => $photo_older_id]))) ?>" class="btn_b01">다음글</a></li>
			<?php endif; ?>
		</ul>
		<ul class="bo_v_com">
			<li><a href="<?= esc($buildUrl($listBaseQuery)) ?>" class="btn_b01">목록</a></li>
			<li>
				<a href="#" class="btn_b02" onclick="<?php if ($is_photo_admin): ?>window.open('<?= esc(site_furl('/?view=photoRegister')) ?>','photoRegister','width=600,height=520');<?php else: ?>alert('관리자만 등록 가능합니다.');<?php endif ?> return false;">글쓰기</a>
			</li>
		</ul>
	</div>
	<?php endif; ?>

    <div class="tbl_head01 tbl_wrap">
		<div class="photoList">
			<ul>
			<?php
            $idx = 0;
	    foreach ($rows as $row) :
	        $idx++;
	        $pid = (int) ($row->id ?? 0);
	        $title = (string) ($row->title ?? '');
	        $cc = 0;
	        $file = (string) ($row->file_path ?? '');
	        $thumbSrc = $file !== '' ? site_furl('uploads/photos/' . $file) : site_furl('images/transparent.png');
	        $liClass = ($idx % 4 === 0) ? 'none' : '';
	        if ($wr_id > 0 && $pid === $wr_id) {
	            $liClass .= ($liClass !== '' ? ' ' : '') . 'photo-current';
	        }
	        $itemUrl = $buildUrl(array_merge($listBaseQuery, ['wr_id' => $pid]));
	        ?>
				<li<?= $liClass !== '' ? ' class="' . esc($liClass) . '"' : '' ?>>
					<a href="<?= esc($itemUrl) ?>">
						<span class="thumb"><img src="<?= esc($thumbSrc) ?>" alt="<?= esc($title) ?>"></span>
					</a>
					<div class="title">
						<a href="<?= esc($itemUrl) ?>"></a>
						<a href="<?= esc($itemUrl) ?>"><?= esc($title) ?></a>
						<span class="comment"><?php if ($cc > 0): ?><span style="color:yellow;">[<?= $cc ?>]</span><?php endif; ?></span>
					</div>
				</li>
			<?php endforeach; ?>
			<?php if ($total === 0 && count($rows) === 0): ?>
				<li style="float:none;width:100%;border:none;background:transparent;text-align:center;padding:24px;color:#999;">등록된 포토가 없습니다.</li>
			<?php endif; ?>
			</ul>
		</div>
    </div>

	<div class="bo_fx" style="margin-top:10px;">
		<ul class="btn_bo_user">
			<li>
				<a href="#" class="btn_b02" onclick="<?php if ($is_photo_admin): ?>window.open('<?= esc(site_furl('/?view=photoRegister')) ?>','photoRegister','width=600,height=520');<?php else: ?>alert('관리자만 등록 가능합니다.');<?php endif ?> return false;">글쓰기</a>
			</li>
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
		<input type="hidden" name="bo_table" value="photo">
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
