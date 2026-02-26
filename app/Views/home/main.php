<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Powerball Live Mini-View</title>
    <script>const BASE_URL = "<?= BASEURL ?>";</script>
    
    <!-- 외부 스타일 및 전용 스타일 연결 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
    
    <style>
        /* 기본 레이아웃 */
        body { background: #071221; color: #fff; margin: 0; padding: 0; overflow: hidden; font-family: 'Malgun Gothic', sans-serif; }
        
        #main-wrapper {
            width: 900px; height: 400px; margin: 30px auto;
            background: #2f5cb0; border: 2px solid #1a2a44; border-radius: 12px;
            display: flex; position: relative; box-shadow: 0 0 40px rgba(0,0,0,0.6);
        }

        /* 좌측 타이틀 바 */
        .side-title-bar {
            width: 40px; background: #c0392b; display: flex; align-items: center; justify-content: center;
            border-top-left-radius: 10px; border-bottom-left-radius: 10px;
        }
        .side-title-bar span { writing-mode: vertical-rl; font-size: 13px; font-weight: bold; color: #fff; letter-spacing: 2px; }

        /* 좌측 추첨기 영역 */
        .machine-container { flex: 0 0 800px; height: 400px; position: relative; overflow: hidden; border-centre: 2px solid #142238; }
        #lottery-canvas { width: 800px !important; height: 400px !important; }
</style>
</head>
<body>

<div id="main-wrapper">
    <div class="side-title-bar"><span>실시간 파워볼게임 미니뷰</span></div>

    <div class="machine-container">
        <canvas id="lottery-canvas" width="500" height="500"></canvas>
    </div>
</div>

<!-- 라이브러리 및 스크립트 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/matter-js/0.20.0/matter.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/gsap.min.js"></script>
<script src="<?= base_url('js/animation.js') ?>"></script>

<script>
    const POWERBALL_CONFIG = {
        baseUrl: '<?= base_url() ?>',
        lastRound: <?= $draw_data['last_id'] ?>,
        initialNumbers: <?= json_encode($draw_data['numbers']) ?>,
        powerball: <?= $draw_data['powerball'] ?>,
        serverTime: <?= $draw_data['server_time'] ?>
    };

    window.onload = function() {
        if (typeof initPowerballEngine === 'function') {
            initPowerballEngine(POWERBALL_CONFIG);
        }
    };
</script>

</body>
</html>
