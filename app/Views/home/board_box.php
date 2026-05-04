<?php
/**
 * 메인 우측 영역: 보드박스(유머/포토/분석픽공유/자유) + 배너 + 분석 영역
 * main.php 에서 뼈대만 두고 실제 구현은 이 파일에서 처리
 */
$list_humor = $list_humor ?? [];
$list_pick  = $list_pick ?? [];
$list_free  = $list_free ?? [];
$list_photo = $list_photo ?? [];
$is_humor_admin = $is_humor_admin ?? false;
?>
<div class="boardBox" id="boardBox">
    <ul class="menu">
        <li class="on" rel="humor">
            유머<?php if ($is_humor_admin): ?>
                <a href="#" onclick="window.open('/?view=humorRegister','humorRegister','width=600,height=650'); return false;"
                   style="margin-left:10px; display:inline-block; padding:2px 10px; border:1px solid #0e609c; background:#127CCB; color:#fff; font-weight:bold; border-radius:3px; font-size:11px; line-height:16px; vertical-align:middle;">
                    등록
                </a>
            <?php endif; ?>
        </li>
        <li rel="photo">
            포토<?php if ($is_humor_admin): ?>
                <a href="#" onclick="window.open('/?view=photoRegister','photoRegister','width=600,height=520'); return false;"
                   style="margin-left:10px; display:inline-block; padding:2px 10px; border:1px solid #0e609c; background:#127CCB; color:#fff; font-weight:bold; border-radius:3px; font-size:11px; line-height:16px; vertical-align:middle;">
                    등록
                </a>
            <?php endif; ?>
        </li>
        <li rel="pick">분석픽공유</li>
        <li class="none" rel="free">자유</li>
    </ul>
    <?php
    $list = $list_humor;
    // 최신순(id DESC)에서 앞 6개는 왼쪽(1~6), 뒤 6개는 오른쪽(7~12) 표시
    $half = (int) ceil(count($list) / 2);
    $leftList = array_slice($list, 0, $half);
    $rightList = array_slice($list, $half);

    // 유머 리스트: 제목이 flex로 남는 폭을 먹음. 메타는 댓글 수만(등록일 표시 제거로 제목 글자수 상향)
    $iconColPx = '34px';
    $humorDateLenSaved = 7; // 기존 [n.j] 최대 길이에 맞춤
    $titleMaxLen = ($is_humor_admin ? 8 : 12) + $humorDateLenSaved;
    ?>
    <div class="listBox" id="list_humor" style="display:block;">
        <div class="left">
            <ul class="list">
                <?php foreach ($leftList as $row) : ?>
                <?php
                    $humorTitle = (string) ($row->title ?? '');
                    if (mb_strlen($humorTitle) > $titleMaxLen) $humorTitle = mb_substr($humorTitle, 0, $titleMaxLen) . '...';
                ?>
                <li style="display:flex; align-items:center; width:97%; box-sizing:border-box; overflow:hidden; min-width:0;">
                    <span style="flex:0 0 <?= esc($iconColPx) ?>; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                        <img src="<?php echo site_furl('images/icon_text.png'); ?>" width="30" height="26" alt="">
                    </span>
                    <span style="flex:1 1 0; min-width:0; overflow:hidden; white-space:nowrap;">
                        <a href="<?= esc(site_furl('frame/communityBoard?bo_table=humor&wr_id=' . (int) ($row->id ?? 0))) ?>"
                           target="mainFrame"
                           title="<?= esc($row->title) ?>"
                           style="display:block; width:100%; max-width:100%; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; vertical-align:middle;">
                           <?= esc($humorTitle) ?>
                        </a>
                    </span>
                    <span class="comment humorListMeta" style="flex:0 1 auto; max-width:38%; min-width:0; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; display:block; font-size:11px; line-height:14px; text-align:left;">
                        <?php $cc = (int)($row->comment_count ?? 0); ?>
                        <?php if ($cc > 0): ?>[<?= $cc ?>]<?php endif; ?>
                    </span>
                    <?php if ($is_humor_admin): ?>
                        <span style="flex:0 0 auto; min-width:0; display:flex; gap:2px; align-items:center; justify-content:flex-end; white-space:nowrap; flex-shrink:0; margin-left:2px;">
                            <a href="#" onclick="window.open('/?view=humorEdit&id=<?= (int)($row->id ?? 0) ?>','humorEdit','width=600,height=650'); return false;"
                               style="display:inline-block; padding:1px 2px; border:1px solid #0e609c; color:#0e609c; font-weight:bold; font-size:10px; line-height:12px; border-radius:3px; background:#fff;">
                                수정
                            </a>
                            <a href="#" onclick="if(!confirm('정말 삭제하시겠습니까?')) return false; location.href='/?view=humorDelete&id=<?= (int)($row->id ?? 0) ?>'; return false;"
                               style="display:inline-block; padding:1px 2px; border:1px solid #c11a20; color:#c11a20; font-weight:bold; font-size:10px; line-height:12px; border-radius:3px; background:#fff;">
                                삭제
                            </a>
                        </span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="bar"></div>
        <div class="right">
            <ul class="list">
                <?php foreach ($rightList as $row) : ?>
                <?php
                    $humorTitle = (string) ($row->title ?? '');
                    if (mb_strlen($humorTitle) > $titleMaxLen) $humorTitle = mb_substr($humorTitle, 0, $titleMaxLen) . '...';
                ?>
                <li style="display:flex; align-items:center; width:97%; box-sizing:border-box; overflow:hidden; min-width:0;">
                    <span style="flex:0 0 <?= esc($iconColPx) ?>; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                        <img src="<?php echo site_furl('images/icon_text.png'); ?>" width="30" height="26" alt="">
                    </span>
                    <span style="flex:1 1 0; min-width:0; overflow:hidden; white-space:nowrap;">
                        <a href="<?= esc(site_furl('frame/communityBoard?bo_table=humor&wr_id=' . (int) ($row->id ?? 0))) ?>"
                           target="mainFrame"
                           title="<?= esc($row->title) ?>"
                           style="display:block; width:100%; max-width:100%; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; vertical-align:middle;">
                           <?= esc($humorTitle) ?>
                        </a>
                    </span>
                    <span class="comment humorListMeta" style="flex:0 1 auto; max-width:38%; min-width:0; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; display:block; font-size:11px; line-height:14px; text-align:right;">
                        <?php $cc = (int)($row->comment_count ?? 0); ?>
                        <?php if ($cc > 0): ?>[<?= $cc ?>]<?php endif; ?>
                    </span>
                    <?php if ($is_humor_admin): ?>
                        <span style="flex:0 0 auto; min-width:0; display:flex; gap:2px; align-items:center; justify-content:flex-end; white-space:nowrap; flex-shrink:0; margin-left:2px;">
                            <a href="#" onclick="window.open('/?view=humorEdit&id=<?= (int)($row->id ?? 0) ?>','humorEdit','width=600,height=650'); return false;"
                               style="display:inline-block; padding:1px 2px; border:1px solid #0e609c; color:#0e609c; font-weight:bold; font-size:10px; line-height:12px; border-radius:3px; background:#fff;">
                                수정
                            </a>
                            <a href="#" onclick="if(!confirm('정말 삭제하시겠습니까?')) return false; location.href='/?view=humorDelete&id=<?= (int)($row->id ?? 0) ?>'; return false;"
                               style="display:inline-block; padding:1px 2px; border:1px solid #c11a20; color:#c11a20; font-weight:bold; font-size:10px; line-height:12px; border-radius:3px; background:#fff;">
                                삭제
                            </a>
                        </span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="listBox" id="list_photo" style="display:none;">
        <ul class="list"><?php foreach ($list_photo as $row) :
            $imgSrc = !empty($row->file_path) ? site_furl('uploads/photos/'.$row->file_path) : site_furl('images/transparent.png');
        ?><li class="photo" style="vertical-align:top; line-height:0;">
            <a href="<?= esc(site_furl('frame/communityBoard?bo_table=photo&wr_id=' . (int) ($row->id ?? 0))) ?>" target="mainFrame" title="<?= esc($row->title) ?>">
                <img src="<?= esc($imgSrc) ?>" class="image" alt="<?= esc($row->title) ?>">
            </a>
        </li><?php endforeach; ?></ul>
    </div>
    <?php
    $list = $list_pick;
    $half = (int)ceil(count($list) / 2);
    $leftList = array_slice($list, 0, $half);
    $rightList = array_slice($list, $half);
    $bo = 'pick';
    ?>
    <div class="listBox" id="list_pick" style="display:none;">
        <div class="left">
            <ul class="list">
                <?php foreach ($leftList as $row) : ?>
                <li>
                    <img src="<?php echo site_furl('images/icon_text.png'); ?>" width="30" height="26" alt="">
                    <a href="<?= esc(site_furl('frame/communityBoard?bo_table=pick&wr_id=' . (int) ($row->wr_id ?? 0))) ?>" target="mainFrame" title="<?= esc($row->title) ?>"><?= esc($row->title) ?></a>
                    <span class="comment">[<?= (int)($row->comment_count ?? 0) ?>]</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="bar"></div>
        <div class="right">
            <ul class="list">
                <?php foreach ($rightList as $row) : ?>
                <li>
                    <img src="<?php echo site_furl('images/icon_text.png'); ?>" width="30" height="26" alt="">
                    <a href="<?= esc(site_furl('frame/communityBoard?bo_table=pick&wr_id=' . (int) ($row->wr_id ?? 0))) ?>" target="mainFrame" title="<?= esc($row->title) ?>"><?= esc($row->title) ?></a>
                    <span class="comment">[<?= (int)($row->comment_count ?? 0) ?>]</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php
    $list = $list_free;
    $half = (int)ceil(count($list) / 2);
    $leftList = array_slice($list, 0, $half);
    $rightList = array_slice($list, $half);
    $bo = 'free';
    ?>
    <div class="listBox" id="list_free" style="display:none;">
        <div class="left">
            <ul class="list">
                <?php foreach ($leftList as $row) : ?>
                <li>
                    <img src="<?php echo site_furl('/images/icon_text.png'); ?>" width="30" height="26" alt="">
                    <a href="<?= esc(site_furl('frame/communityBoard?bo_table=free&wr_id=' . (int) ($row->id ?? 0))) ?>" target="mainFrame" title="<?= esc($row->title) ?>"><?= esc($row->title) ?></a>
                    <span class="comment">[<?= (int)($row->comment_count ?? 0) ?>]</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="bar"></div>
        <div class="right">
            <ul class="list">
                <?php foreach ($rightList as $row) : ?>
                <li>
                    <img src="<?php echo site_furl('/images/icon_text.png'); ?>" width="30" height="26" alt="">
                    <a href="<?= esc(site_furl('frame/communityBoard?bo_table=free&wr_id=' . (int) ($row->id ?? 0))) ?>" target="mainFrame" title="<?= esc($row->title) ?>"><?= esc($row->title) ?></a>
                    <span class="comment">[<?= (int)($row->comment_count ?? 0) ?>]</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<script>
(function(){
    function initBoardBoxTabs() {
        var box = document.getElementById('boardBox');
        if (!box) return;
        var menuItems = box.querySelectorAll('ul.menu li');
        var listBoxes = box.querySelectorAll('.listBox');
        if (!menuItems.length || !listBoxes.length) return;

        function activateTab(rel) {
            if (!rel) return;
            for (var j = 0; j < menuItems.length; j++) menuItems[j].classList.remove('on');
            for (var k = 0; k < menuItems.length; k++) {
                if (menuItems[k].getAttribute('rel') === rel) {
                    menuItems[k].classList.add('on');
                }
            }
            for (var i = 0; i < listBoxes.length; i++) {
                listBoxes[i].style.display = (listBoxes[i].id === 'list_' + rel) ? 'block' : 'none';
            }
        }

        // photoRegister 팝업에서 "등록 직후" 새로고침 후 포토 탭을 자동 활성화하기 위한 플래그
        try {
            if (sessionStorage.getItem('forcePhotoTab') === '1') {
                sessionStorage.removeItem('forcePhotoTab');
                activateTab('photo');
            }
        } catch (e) {}

        for (var i = 0; i < menuItems.length; i++) {
            menuItems[i].addEventListener('click', function(e) {
                // 메뉴 안쪽의 링크(등록 버튼 등) 클릭은 탭 전환 로직을 스킵
                var tag = e && e.target && e.target.tagName ? String(e.target.tagName).toLowerCase() : '';
                if (tag === 'a') return;
                var rel = this.getAttribute('rel');
                if (!rel) return;
                e.preventDefault();
                activateTab(rel);
            });
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBoardBoxTabs);
    } else {
        initBoardBoxTabs();
    }
})();
</script>
<ul class="bannerBox" id="banner_main_area">
    <script>
    document.write("<d"+"iv id='mobonDivBanner_205215'><iframe name='ifrad' id='mobonIframe_205215' src='//www.mediacategory.com/servlet/adBanner?from="+escape(document.referrer)+"&s=205215&igb=71&iwh=200_200&cntad=1&cntsr=2' frameborder='0' scrolling='no' style='height: 200px; width:200px;'></iframe></div>");
    </script>
</ul>
<!-- inner-right (선배님 구조: 파란색으로 선택된 핵심 영역 = 이 주석 아래 중첩 div.inner-right) -->
<div class="inner-right">
    <!-- 메인프레임만. powerballMiniViewDiv 제거 → 메인헤더 중복 원인 제거 -->
    <iframe name="mainFrame" id="mainFrame" src="<?php echo site_furl('frame/dayLog'); ?>?t=<?php echo time(); ?>" frameborder="0" scrolling="no" style="width:830px; height: 1844px;"></iframe>
</div>
<!-- //inner-right -->
<!-- tmpl -->
<script id="tmpl_board" type="text/x-jquery-tmpl">
<li>
    <img src="<?php echo site_furl('images'); ?>/icon_${type}.png" width="30" height="26" alt="">
    <a href="/bbs/board.php?bo_table=${bo_table}&wr_id=${idx}" target="mainFrame" title="${title}">${title}</a>
    {{html commentView}}
    {{html newIcon}}
</li>
</script>
<script id="tmpl_photo" type="text/x-jquery-tmpl">
<li class="photo">
    <a href="/bbs/board.php?bo_table=${bo_table}&wr_id=${idx}" target="mainFrame" title="${title}">
        <span class="image"><img src="<?php echo site_furl('uploads/photos'); ?>/${file_path}" alt="${title}" class="image"></span>
    </a>
</li>
</script>
<!-- //tmpl -->
