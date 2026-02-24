<!DOCTYPE html>
<script>
    // PHP에서 정의된 BASEURL을 JS 전역 변수로 전달
    const BASE_URL = "<?= BASEURL ?>";
</script>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Powerball Live Mini-View</title>
    <!-- 선배님 스타일: 최소한의 부트스트랩과 커스텀 스타일 -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com">
    <style>
        /* 1. 전체 틀거리 및 스크롤 방지 */
        body { background: #071221; color: #fff; margin: 0; padding: 0; overflow: hidden; font-family: 'Malgun Gothic', sans-serif; }
        
        #main-wrapper {
            width: 900px; 
            height: 400px;
            margin: 30px auto;
            background: #2f5cb0;
            border: 2px solid #1a2a44;
            border-radius: 12px;
            display: flex;
            position: relative;
            box-shadow: 0 0 40px rgba(0,0,0,0.6);
        }

        /* 2. 좌측 빨간색 타이틀 바 (견본 이미지 재현) */
        .side-title-bar {
            width: 100px;
            background: #c0392b;
            display: flex;
            align-items: center;
            justify-content: center;
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }
        .side-title-bar span {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 13px;
            font-weight: bold;
            color: #ff9999;
            letter-spacing: 2px;
        }

        /* 3. 좌측: 추첨 기계 영역 */
        /* 왼쪽 기계 영역의 크기를 고정하여 캔버스 왜곡 방지 */
        .machine-container {
            flex: 0 0 400px; /* 400px 너비 고정 */
            height: 400px;
            position: relative;
            overflow: hidden;
            border-right: 2px solid #142238;
        }

        #lottery-canvas {
            width: 400px !important;
            height: 400px !important;
        }
        /* 4. 우측: 결과 보드 영역 */
        .result-container {
            flex: 1;
            padding: 50px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(0, 0, 0, 0.15);
        }

        /* 결과 박스 (견본 이미지와 동일한 둥근 사각형) */
        .result-panel {
            background: #16243d;
            border: 2px solid #2a3b5a;
            border-radius: 25px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.5);
        }

        .round-text { font-size: 20px; color: #8bb3ff; margin-bottom: 25px; font-weight: bold; }
        
        /* 당첨 번호 공 스타일 */
        .ball-row { display: flex; justify-content: center; gap: 12px; margin-bottom: 30px; }
        .ball-icon {
            width: 48px; height: 48px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 18px; color: #fff;
            box-shadow: inset -2px -2px 6px rgba(0,0,0,0.4), 0 4px 8px rgba(0,0,0,0.6);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        .bg-blue { background: radial-gradient(circle at 30% 30%, #3498db, #2980b9); }
        .bg-green { background: radial-gradient(circle at 30% 30%, #2ecc71, #27ae60); }

        /* 이전 회차 결과 버튼 */
        .btn-prev {
            background: #2a3b5a;
            color: #a0aec0;
            border: none;
            padding: 12px;
            border-radius: 10px;
            width: 100%;
            font-size: 15px;
            transition: 0.3s;
        }
        .btn-prev:hover { background: #354a71; color: #fff; }

        /* 하단 타이머 */
        .timer-area { margin-top: 25px; font-size: 16px; color: #fff; font-family: monospace; }
    </style>
</head>
<body>

<div id="main-wrapper">
    <!-- 빨간 타이틀 바 -->
    <div class="side-title-bar">
        <span>실시간 파워볼게임 미니뷰</span>
    </div>

    <!-- 추첨 기계 (좌) -->
    <div class="machine-container">
        <div id="nozzle-overlay"></div>
        <canvas id="lottery-canvas" width="500" height="500"></canvas>
    </div>

    <!-- 결과 보드 (우) -->
    <div class="result-container">
        <div class="result-panel">
            <div class="round-text" id="round-info"><?= $draw_data['last_id'] ?>회차 결과</div>
            
            <div class="ball-row" id="ball-list">
                <!-- 일반볼 5개 -->
                <?php foreach($draw_data['numbers'] as $n): ?>
                    <div class="ball-icon bg-blue"><?= sprintf('%02d', $n) ?></div>
                <?php endforeach; ?>
                <!-- 파워볼 1개 -->
                <div class="ball-icon bg-green"><?= sprintf('%02d', $draw_data['powerball']) ?></div>
            </div>

            <button class="btn-prev">이전 회차 결과</button>
        </div>

        <div class="timer-area text-center">
            <span id="timer-display">00:00</span>
        </div>
    </div>
</div>

<!-- 라이브러리 로드 -->
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

