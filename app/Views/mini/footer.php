<style>
    /* 푸터 스타일 설정 (이미지 소스 기반) */
    #footer { background: #f8f9fa; border-top: 1px solid #ddd; padding: 30px 0; color: #666; font-size: 12px; line-height: 1.8; }
    #footer dl { list-style: none; margin: 0 0 15px 0; padding: 0; text-align: center; }
    #footer dd a { color: #444; text-decoration: none; font-weight: 500; }
    #footer dd span { margin: 0 10px; color: #ccc; } /* 구분선 | 스타일 */
    #footer strong { color: #333; } /* 강조 텍스트 */
    
    #footer .small { text-align: center; color: #888; }
    #footer .bar { margin: 0 8px; color: #eee; }
    #footer br { margin-bottom: 5px; }
</style>

<div id="footer">
    <div class="ui container"> <!-- 선배님의 Semantic-UI 컨테이너 활용 -->
        
        <!-- 상단 링크 메뉴 (이용약관, 방침 등) -->
        <dl>
            <dd>
                <a href="?view=agree" target="mainFrame">이용약관</a>
                <span>|</span>
                <a href="?view=agree&type=privacy" target="mainFrame"><strong>개인정보처리방침</strong></a>
                <span>|</span>
                <a href="?view=agree&type=youth" target="mainFrame"><strong>청소년보호정책</strong></a>
                <span>|</span>
                <a href="?view=agree&type=rejectemail" target="mainFrame">이메일주소무단수집거부</a>
                <span>|</span>
                <a href="mailto:help@powerballgame.co.kr">광고 및 제휴문의</a>
                <span>|</span>
                <a href="<?= esc(site_furl('frame/customerCenter')) ?>" target="mainFrame">고객센터</a>
            </dd>
        </dl>

        <!-- 하단 사업자 정보 (이미지 소스 텍스트 그대로 반영) -->
        <p class="small">
            상호 : (주)엠커넥트글로벌 <span class="bar">|</span>
            개인정보책임자 : 강효신 <span class="bar">|</span>
            사업자등록번호 : 175-86-01836 <span class="bar">|</span>
            통신판매업신고 : 제 2023-인천부평-3874호
            <br>
            주소 : 인천광역시 부평구 주부토로 236, B동 15층 1503호(갈산동, 인천테크노밸리U1센터) <span class="bar">|</span>
            이메일 : help@powerballgame.co.kr
            <br>
            <span style="font-size: 11px; color: #bbb;">Copyright © 파워볼게임. All rights reserved.</span>
        </p>

    </div>
</div>
