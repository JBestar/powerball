/**
 * 파워볼 실시간 추첨 애니메이션 엔진
 * 핵심 로직: Matter.js (물리) + GSAP (연출 제어)
 */

let engine, render, runner, world;
let balls = [];
let isAgitating = false;
let isAnimationStarted = false;
let config = null; // 전역 변수로 선언
const cx = 200, cy = 210, radius = 120; // 왼쪽 영역(500px)의 중앙인 250에 배치
const BallRadius = 18;
const railGap = 34; // 이중 레일 사이의 간격 (공 크기 28~30px 고려)
const railThick = 4;
const nozzleHeight = 44;
// 1. 엔진 초기화 및 환경 설정
function initPowerballEngine(config) {
    const { Engine, Render, Runner, Composite, Bodies, Body, Events } = Matter;

    engine = Engine.create({
        positionIterations: 10, // 위치 계산 정밀도 (기본 6)
        velocityIterations: 10, // 속도 계산 정밀도 (기본 4)
        constraintIterations: 10
    });
    world = engine.world;
    engine.gravity.y = 1.0; 

    const canvas = document.getElementById('lottery-canvas');
    // 1. 캔버스 렌더러 크기 및 배경 수정
    render = Render.create({
        canvas: canvas,
        engine: engine,
        options: {
            width: 800,
            height: 400,
            wireframes: false,
            background: 'transparent'
        }
    });  
    const segments = 120;
    
    const guideThick = 10;
    // 2. 물리적 노즐 입구 생성 (공이 나갈 수 있게 양옆에 벽 세우기)
//    const nozzleLeft = Bodies.rectangle(cx - nozzleHeight / 2, cy - radius, guideThick, nozzleHeight, { isStatic: true, render: { visible: false } });
    const nozzleRight = Bodies.rectangle(cx + nozzleHeight / 2, cy - radius, guideThick, nozzleHeight, { isStatic: true, render: { visible: false } });
//    const nozzleTop = Bodies.rectangle(cx, cy - radius - nozzleHeight, nozzleHeight + guideThick * 2, guideThick, { isStatic: true, render: { visible: false } });
    const nozzleBottom = Bodies.rectangle(cx, cy, nozzleHeight + guideThick * 2, guideThick, { isStatic: true, render: { visible: false } });
    Composite.add(world, [nozzleBottom, nozzleRight]);
    // 1. [핵심] 유리 플라스크 및 노즐 렌더링 함수
    function drawGlassFlask(ctx, cx, cy, radius){
        // C. 유리관 전체 실선 테두리 (점선 방지)
        ctx.beginPath();
        ctx.arc(cx, cy, radius, 0, Math.PI * 2);
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
        ctx.lineWidth = 2;
        ctx.stroke();

        // D. 공 번호 선명하게 그리기 (생략 방지)
        balls.forEach(ball => {
            ctx.font = "bold 15px Arial";
            ctx.fillStyle = "#ffffff";
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText(ball.label, ball.position.x, ball.position.y);
        });
    }
    // 120개의 작은 사각형을 원형으로 배치하여 '물리적 플라스크' 생성
    const wallWidth = 10;  // 벽 조각의 너비
    const wallThickness = 10;
    for (let i = 0; i <= segments; i++) {
        const angle = (i / segments) * Math.PI * 2;
        const x = cx + Math.cos(angle) * radius;
        const y = cy + Math.sin(angle) * radius;   
        const wallSegment = Matter.Bodies.rectangle(x, y, wallWidth, wallThickness, {
            isStatic: true, // 고정된 벽
            angle: angle,
            render: { visible: false } // 화면에는 안 보임 (그림은 이미 그려져 있으므로)
        });
        Matter.Composite.add(world, wallSegment);
    }
    // 2. [렌더링 루프] 플라스크 -> 레일 순서로 그려야 입체감이 납니다.
    // [입체감 있는 레일 그리기]
    Matter.Events.on(render, 'afterRender', () => {
        const ctx = render.context;
        ctx.save();
        // 3. 플라스크 및 노즐 호출 (중심 좌표 확인 필수)
        drawGlassFlask(ctx, cx, cy, radius); 
        drawResultPanel(ctx);
    });
    // 3. 공 생성 (이미지 기준 5가지 색상 로테이션)
    const colors = ['#e74c3c', '#2ecc71', '#27ae60', '#f1c40f', '#2980b9'];
    for (let i = 1; i <= 10; i++) { // 일반볼+파워볼 합계만큼 생성
        const ballColor = colors[(i - 1) % 5];
        const ball = Bodies.circle(cx + (Math.random() - 0.5) * 60, cy + 50, BallRadius, {
            density: 0.001,
            restitution: 0.5,
            friction: 0.1,
            frictionAir: 0.01,
            frictionStatic: 0,
            label: i.toString(),
            ballColor: ballColor, // 커스텀 속성으로 색상 저장
            render: { visible: false } // 기본 렌더링은 끔 (직접 그리기 위해)
        });
        balls.push(ball);
        Composite.add(world, ball);
    }
    // Render 객체 생성 후 아래 이벤트 리스너 추가
    Matter.Events.on(render, 'afterRender', () => {
        const ctx = render.context;
        // 월드의 모든 바디를 가져옴
        const allBodies = Matter.Composite.allBodies(world);
        
        allBodies.forEach(body => {
            // 섞기용 공(balls) 혹은 당첨 공(isWinBall)만 렌더링
            if (body.isWinBall || balls.includes(body)) {
                const { x, y } = body.position;
                const radius = body.circleRadius;

                ctx.save();
                
                // 1. 공 본체 (밝은 입체감 그라데이션)
                // 빛의 중심을 (x-5, y-5)에서 조금 더 중앙 쪽으로 옮기면 더 밝아집니다.
                const grad = ctx.createRadialGradient(
                    x - radius * 0.6, y - radius * 0.7, radius * 0.01, // 빛이 맺히는 점 (더 작고 선명하게)
                    x, y, radius * 1 // 공의 전체 범위
                );

                // [수정 포인트]
                grad.addColorStop(0, '#e7e0e0');             // 1. 가장 밝은 하이라이트 (흰색)
                grad.addColorStop(0.4, body.ballColor || '#e74c3c'); // 2. 본래 색상의 범위를 넓힘 (0.3 -> 0.4)
            //    grad.addColorStop(1, '#333333');           // 3. 외곽 그림자를 연한 회색으로 (000000 -> 333333)
            //    grad.addColorStop(1, '#111111');             // 4. 맨 바깥쪽만 살짝 어둡게

                ctx.beginPath();
                ctx.arc(x, y, radius, 0, Math.PI * 2);
                ctx.fillStyle = grad;
                ctx.fill();

                // [추가 포인트] 공 상단에 아주 얇은 반사광(Rim Light) 효과를 주면 훨씬 고급스러워집니다.
                ctx.beginPath();
                ctx.arc(x, y, radius - 1, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)'; // 반투명 흰색 외곽선
                ctx.lineWidth = 0.8;
                ctx.stroke();


                // 2. 당첨 공일 때 번호 그리기 (이미지 스타일 반영)
                if (body.isWinBall) {
                    // 그림자 효과를 주어 흰색 숫자가 더 또렷하게 보이도록 함
                    ctx.shadowColor = "rgba(0, 0, 0, 0.5)";
                    ctx.shadowBlur = 12;
                    ctx.shadowOffsetX = -4;
                    ctx.shadowOffsetY = -4;

                    ctx.font = "bold 18px Arial"; // 크기 약간 키움
                    ctx.fillStyle = "#ffffff";    // [수정] 번호를 흰색으로
                    ctx.textAlign = "center";
                    ctx.textBaseline = "middle";
                    ctx.fillText(body.label, x, y);
                }
                
                ctx.restore();
            }
        });
    });
    Matter.Events.on(render, 'afterRender', () => {
        const ctx = render.context;
        ctx.save();
        // 검은색 레일 (Shadow 효과 포함)
        ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
        ctx.shadowBlur = 5;
        ctx.shadowOffsetY = railThick;
        ctx.strokeStyle = '#111111';
        ctx.lineWidth = railThick;

        // 1. 이중 레일 실선 (side -1과 1로 호출)
        [-1, 1].forEach(side => {
            ctx.beginPath();
            for (let p = 0; p <= 1.0; p += 0.01) {
                const pos = getRailPos(p, side);
                if (p === 0) ctx.moveTo(pos.x, pos.y);
                else ctx.lineTo(pos.x, pos.y);
            }
            ctx.stroke();
        });
        // 2. 가름대 (듬성듬성하게 배치)
        ctx.strokeStyle = '#222222';
        ctx.lineWidth = railThick;

        for (let p = 0; p <= 1.0; p += 0.15) { 
            // -0.9, 0.9 정도로 간격을 아주 살짝 좁히면 레일 밖으로 삐져나오지 않습니다.
            const p1 = getRailPos(p, -0.9); 
            const p2 = getRailPos(p, 0.9); 
            
            ctx.beginPath();
            ctx.moveTo(p1.x, p1.y);
            ctx.lineTo(p2.x, p2.y);
            ctx.stroke();
        }
        // B. 노란색 노즐 렌더링 (플라스크 입구에 고정)
        ctx.fillStyle = '#f5c400';
        ctx.beginPath();
        // 입구 위치에 사각형 노즐 그리기
        ctx.roundRect(cx - nozzleHeight / 2, cy - radius - nozzleHeight, nozzleHeight, nozzleHeight, [5, 5, 0, 0]); 
        ctx.fill();
        // 노즐 테두리 및 광택
        ctx.strokeStyle = '#d4a700';
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.restore();
    });
    // 4. 추첨 전 섞기 효과 (Agitation)
    Matter.Events.on(engine, 'beforeUpdate', () => {
        if (isAgitating) {
            balls.forEach(ball => {
                // 0.92 -> 0.95로 변경하여 힘이 가해지는 빈도를 살짝 줄임 (더 자연스러움)
                if (Math.random() > 0.95) {
                    // 기존 힘의 수치를 대폭 낮춤
                    Matter.Body.applyForce(ball, ball.position, {
                        // 좌우 흔들림: 0.04 -> 0.015 (부드럽게 흔들림)
                        x: (Math.random() - 0.5) * 0.015,            
                        // 상향 점프: -0.07 -> -0.035 (살짝 들썩이는 정도)
                        y: -0.02 - (Math.random() * 0.02) 
                    });
                }
            });
        }
    });
    Render.run(render);
    runner = Runner.run(Runner.create(), engine);
    checkGameState(config);
}
// [Catmull-Rom Spline 보간 함수]
function getSplinePoint(t, p0, p1, p2, p3) {
    const v0 = (p2 - p0) * 0.5;
    const v1 = (p3 - p1) * 0.5;
    const t2 = t * t;
    const t3 = t * t2;
    return (2 * p1 - 2 * p2 + v0 + v1) * t3 + (-3 * p1 + 3 * p2 - 2 * v0 - v1) * t2 + v0 * t + p1;
}
function getRailPos (prog, side) {
    // side: -1 (안쪽 레일), 1 (바깥쪽 레일)
    const railGap = 34; // 두 레일 사이의 고정 간격
    const offset = (railGap / 2) * side;
    const horizontalY = cy + radius + railThick * 2 - railGap / 2;
    // 9개의 정교한 제어점 (이미지 궤적 반영)
    const pts = [
        { x: cx, y: cy - radius - railThick - railGap / 2 },  // P0: 12시
        { x: cx - (radius + railGap / 2 + 0.05 * radius) * 0.5 , y: cy - (radius + railGap / 2 + 0.05 * radius) * 0.866 },  // P1: 11시 (팽창 시작점)
        { x: cx - (radius + railGap / 2 + 0.08 * radius) * 0.866 , y: cy - (radius + railGap / 2 + 0.08 * radius) * 0.5 }, // P2: 10시 (최대 팽창)
        { x: cx - (radius + railGap / 2 + 0.04 * radius) * 1 , y: cy - (radius + railGap / 2 + 0.04 * radius) * 0 }, // P3: 9시
        { x: cx - (radius + railGap / 2 - 0.05 * radius) * 0.866 , y: cy + (radius + railGap / 2 - 0.05 * radius) * 0.5 }, // P4: 8시 (진입)
        { x: cx - (radius + railGap / 2 - 0.17 * radius) * 0.5 , y: cy + (radius + railGap / 2 - 0.17 * radius) * 0.866 }, // P5: 7시
        { x: cx, y: horizontalY  }, // P6: 6시 (직선 합류)
        { x: cx + radius / 2, y: horizontalY }, // P7
        { x: cx + radius, y: horizontalY }  // P8
    ];

    // Spline 구간 계산 (pts가 9개면 세그먼트는 8개)
    const numSegments = pts.length - 1;
    const totalT = Math.max(0, Math.min(prog, 1)) * numSegments;
    const i = Math.floor(totalT);
    const t = totalT % 1;

    // 인덱스 범위 제한 (P-1, P, P+1, P+2)
    const p0 = pts[Math.max(i - 1, 0)];
    const p1 = pts[i];
    const p2 = pts[Math.min(i + 1, numSegments)];
    const p3 = pts[Math.min(i + 2, numSegments)];

    const posX = getSplinePoint(t, p0.x, p1.x, p2.x, p3.x);
    const posY = getSplinePoint(t, p0.y, p1.y, p2.y, p3.y);

    // 방향 벡터 계산 (기울기)
    const delta = 0.01;
    const nextX = getSplinePoint(t + delta, p0.x, p1.x, p2.x, p3.x);
    const nextY = getSplinePoint(t + delta, p0.y, p1.y, p2.y, p3.y);
    const angle = Math.atan2(nextY - posY, nextX - posX);

    // 레일 간격(offset) 적용하여 평행 좌표 반환
    return {
        x: posX + Math.cos(angle + Math.PI / 2) * offset,
        y: posY + Math.sin(angle + Math.PI / 2) * offset
    };
};
// 7. 서버 시간 기준 상태 관리
function checkGameState(config) {
    const now = Math.floor(Date.now() / 1000);
    const remaining = 60 - (now % 60); // 1분 주기 (60초)
    
    // 1. 공 섞기 제어 (30초 전부터 시작)
    isAgitating = (remaining <= 10 || isAnimationStarted);

    // 2. [추가] 정각(0초)에 서버 데이터 가져오기 및 애니메이션 트리거
    // timeLeft가 60일 때(정각) 딱 한 번만 호출되도록 플래그 사용
    if (remaining === 60 && !isAnimationStarted) {
        isAnimationStarted = true;
        console.log("추첨 시작! 서버 데이터를 가져옵니다.");
        fetchDrawResult(); // 서버 API 호출 함수
    }

    // 3. 다음 회차 준비를 위해 플래그 리셋 (추첨이 한창 진행 중일 때 리셋)
//    if (remaining < 50 && remaining > 40) {
//        isAnimationStarted = false;
//    }

    // 4. 타이머 UI 업데이트
    const min = Math.floor(remaining / 60);
    const sec = remaining % 60;
    const timerElem = document.getElementById('timer-display');
    if(timerElem) {
        timerElem.innerText = `${min.toString().padStart(2, '0')}:${sec.toString().padStart(2, '0')}`;
    }

    // 1초 뒤에 다시 실행
    setTimeout(() => checkGameState(config), 1000);
}

function spawnWinningBall(winNumber, type, sequence) {
    // 이미지 예시 색상 매칭
    const resultColors = ['#2980b9', '#f39c12', '#27ae60', '#e74c3c', '#c0392b', '#2980b9'];
    const ballColor = (type === 'power') ? '#2980b9' : resultColors[sequence % resultColors.length];

    const winBall = Matter.Bodies.circle(cx, cy - radius, BallRadius, {
        isStatic: true,             // GSAP 애니메이션으로 제어
        isSensor: true,             // 다른 물체와 겹쳐도 튕겨나가지 않게 함 (유령 효과)
        label: winNumber.toString(),
        isWinBall: true,            // 당첨 공 판별용 플래그
        ballColor: ballColor,       // 매개변수로 받은 색상
        // [추가] 충돌 필터를 설정하여 모든 충돌을 무시하도록 설정
        collisionFilter: {
            group: -1,              // 음수 그룹은 같은 그룹끼리도 충돌하지 않음
            mask: 0                 // 0으로 설정하면 어떤 물체와도 충돌하지 않음
        },
        render: { visible: false }  // 기본 렌더링을 끄고 아래 afterRender에서 직접 그림
    });
    Matter.Composite.add(world, winBall);

    // 가상의 객체를 만들어 0 ~ 1까지 수치를 변화시킴
    const pathObj = { progress: 0 };
    const tl = gsap.timeline();
    const target = getResultTargetPos(sequence);
    tl.to(winBall.position, {
        x: cx, 
        y: cy - radius - 20, // 노쥴의 중심으로 이동
        duration: 0.5,
        ease: "bounce.out"
//        onComplete: () => {
//            console.log(`${winNumber}번 공 결과창 안착`);
//        }
    })
    // 1단계: 레일 구간 (0 ~ 1) - 여기서 S자 곡선을 탑니다.
    .to(pathObj, {
        progress: 1,
        duration: 2, // 레일을 내려오는 시간
        ease: "power1.inOut",
        onUpdate: () => {
            // 매 프레임마다 레일 좌표 계산하여 공 위치 고정
            const pos = getRailPos(pathObj.progress, 0);
//            console.log(`Progress: ${pathObj.progress.toFixed(2)} | X: ${pos.x.toFixed(0)}, Y: ${pos.y.toFixed(0)}`);
            Matter.Body.setPosition(winBall, { x: pos.x, y: pos.y });
        }
    })
    // 2단계: 결과 박스로 안착 (플라스크 크기 연동 좌표)
    .to(winBall.position, {
        x: target.x,
        y: target.y,
        duration: 1,
        ease: "back.out(1.7)", // 쏙 들어가는 느낌
        onComplete: () => {
            // 안착 시점에 효과음이나 반짝임 효과 추가 가능
            console.log(`${winNumber}번 공 결과박스 안착 완료`);
        }
    });
}
function updateResultUI(num, seq) {
    const ballList = document.getElementById('ball-list');
    if (!ballList) return;

    // 숫자를 두 자리 형식으로 변환 (예: 7 -> 07)
    const formattedNum = num.toString().padStart(2, '0');
    
    // 해당 순서의 공 요소를 찾음
    const balls = ballList.querySelectorAll('.ball-icon');
    
    if (balls[seq]) {
        // 기존 텍스트 변경 및 애니메이션 효과 부여
        balls[seq].innerText = formattedNum;
        
        // 시각적 강조 효과 (GSAP 사용)
        gsap.fromTo(balls[seq], 
            { scale: 0.5, opacity: 0, backgroundColor: "#fff" }, 
            { scale: 1, opacity: 1, backgroundColor: (seq === 5 ? "#27ae60" : "#2980b9"), duration: 0.5, ease: "back.out(1.7)" }
        );
    }
}
async function fetchDrawResult() {
    try {
        const response = await fetch('lottery/getDrawResult');
        const data = await response.json();
        
        const normalNumbers = [data.n1, data.n2, data.n3, data.n4, data.n5];
        const powerBall = data.p1;
        // 1. 전역 설정의 회차 번호를 DB에서 받은 dw_id로 업데이트
        // config 객체가 없으면 생성하고 값 할당
        if (!config) config = {};
        if (typeof config !== 'undefined') {
            config.lastRound = data.dw_id; 
        }
        // 3초 간격으로 하나씩 추출
        normalNumbers.forEach((num, i) => {
            setTimeout(() => {
                spawnWinningBall(num, 'normal', i);
            }, i * 3000);
        });

        // 마지막 파워볼 (일반볼 다 나오고 3초 뒤)
        setTimeout(() => {
            spawnWinningBall(powerBall, 'power', 5);
            console.log("추첨 파워볼: 추첨되었습니다.");
            // 모든 공 추출 완료 후 3초 뒤 안착 시퀀스
            setTimeout(() => {
                isAnimationStarted = false; // 섞기 힘 중단
                isAgitating = false;        // 플래그 확실히 해제

                balls.forEach(ball => {
                    // 1. 모든 고정 상태 해제
                    Matter.Body.setStatic(ball, false);
                    Matter.Sleeping.set(ball, false);
                    
                    // 2. 공기 저항 일시 제거 (빨리 떨어지게 함)
                    ball.frictionAir = 0.01;
                    
                    // 3. 중력이 작용하도록 아래 방향으로 속도 주입 (중요)
                    Matter.Body.setVelocity(ball, { x: 0, y: 5 }); 
                    
                    // 4. 미세한 무작위 X축 흔들림 (공들이 겹쳐서 공중에 멈추는 것 방지)
                    Matter.Body.applyForce(ball, ball.position, { 
                        x: (Math.random() - 0.5) * 0.001, 
                        y: 0.01 
                    });
                });
                console.log("추첨 종료: 모든 공이 바닥으로 낙하합니다.");
            }, 3000); // 파워볼 나오고 3초 여유
        }, 15000);
        // 파워볼 추출 예약 후 마지막에 추가
        setTimeout(() => {
            shiftResultsToHistory(); // 모든 공을 아래로 밀어내기
            
            // 다음 회차 준비를 위해 10초 뒤에 화면에서 완전히 제거하거나 
            // 혹은 다음 추첨 시작 시점에 resetResultUI 호출
        }, 20000); 
    } catch (err) {
        console.error("추첨 연동 에러:", err);
    }
}
/**
 * 결과 박스 내 공의 목표 좌표를 계산하는 함수
 * @param {number} seq - 추출 순서 (0~5)
 * @returns {object} {x, y}
 */
function getResultTargetPos(seq) {
    const panelX = cx + radius + 40;
    const panelY = cy - radius - 20;
    
    // 상단 라벨(20+35) 아래, 중앙 라벨(150) 위 사이의 공간
    const startX = panelX + 55;
    const startY = panelY + 90; // 공이 들어갈 Y축 중심
    const spacing = 45;

    return {
        x: startX + (seq * spacing),
        y: startY
    };
}

function shiftResultsToHistory() {
    const allBodies = Matter.Composite.allBodies(world);
    const winBalls = allBodies.filter(b => b.isWinBall && !b.isHistory);

    winBalls.forEach((ball, i) => {
        ball.isHistory = true; // 히스토리 상태로 변경 (중복 이동 방지)
        
        gsap.to(ball.position, {
            x: (cx + radius + 100) + (i * 35), // 아래쪽은 조금 더 작게 배치
            y: cy + 120, // "이전 회차 결과" 라벨 아래 좌표
            duration: 1.5,
            delay: 2, // 파워볼 안착 후 2초 뒤 이동 시작
            ease: "power2.inOut",
            onStart: () => {
                // 이동하면서 크기를 살짝 줄여서 이전 기록임을 표시
                gsap.to(ball, { circleRadius: 13, duration: 1.5 });
            }
        });
    });
}
/**
 * 결과 박스 테두리를 그리는 함수 (style.css와 동일한 위치/크기)
 */
function drawResultPanel(ctx) {
    ctx.save();
    // 플라스크 radius에 연동된 좌표 계산
    const panelX = cx + radius + 50; 
    const panelY = cy - radius - nozzleHeight / 2;
    const panelW = radius * 2.6; // 결과창 너비
    const panelH = radius * 2.3; // 결과창 높이
    const labelH = 35;
    const labelW = panelW - 40;
    ctx.save();
    ctx.beginPath();
    ctx.roundRect(panelX, panelY, panelW, panelH, 25); // 둥근 사각형
    
    // 이미지처럼 투명한 배경과 흰색 테두리
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.4)';
    ctx.lineWidth = 2;
    ctx.stroke();
    // --- [2단계] 검은색 정보 라벨 그리기 (상단/중앙) ---
    ctx.fillStyle = '#1a1a1a';
    ctx.shadowColor = 'rgba(0,0,0,0.5)';
    ctx.shadowBlur = 5;
    // 상단 라벨 (***회차 결과)
    ctx.beginPath();
    ctx.roundRect(panelX + 20, panelY + 20, labelW, labelH, 5);
    // 중앙 라벨 (이전 회차 결과)
    ctx.roundRect(panelX + 20, panelY + 150, labelW, labelH, 5);
    ctx.fill();
    // --- [3단계] 라벨 텍스트 그리기 ---
    // [핵심 수정] 그림자 설정을 초기화하여 글자를 또렷하게 만듭니다.
    ctx.shadowColor = 'transparent';
    ctx.shadowBlur = 0;
    ctx.shadowOffsetX = 0;
    ctx.shadowOffsetY = 0;
    ctx.fillStyle = '#f4ebeb';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';    
    ctx.font = 'bold 16px "Malgun Gothic", sans-serif';    
    // 상단 텍스트 (Draw_Model에서 받은 dw_id 연동 가능)
    const roundNum = (config && config.lastRound) ? config.lastRound : "---";
    ctx.fillText(`${roundNum}회차 결과`, panelX + panelW/2, panelY + 20 + labelH/2);       
    // 중앙 텍스트
    ctx.fillText(`이전 회차 결과`, panelX + panelW/2, panelY + 150 + labelH/2);
    ctx.restore();
}

