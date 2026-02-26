/**
 * 파워볼 실시간 추첨 애니메이션 엔진
 * 핵심 로직: Matter.js (물리) + GSAP (연출 제어)
 */

let engine, render, runner, world;
let balls = [];
let isAgitating = false;
let isAnimationStarted = false;
let config = null; // 전역 변수로 선언
let hasSlidThisRound = false;
let remainingSeconds = 0;
let showOverlay = true;
const cx = 200, cy = 210, radius = 120; // 왼쪽 영역(500px)의 중앙인 250에 배치
const BallRadius = 18;
const railGap = 34; // 이중 레일 사이의 간격 (공 크기 28~30px 고려)
const railThick = 4;
const nozzleHeight = 44;
// 1. 엔진 초기화 및 환경 설정
function initPowerballEngine(cfg) {
    // 전역 설정에 주입 (초기 결과 배치에서 사용)
    config = cfg;
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
    const segments = 160;
    
    const guideThick = 10;
    const nozzleLeft = Bodies.rectangle(cx - nozzleHeight / 2, cy - radius, guideThick, nozzleHeight, { isStatic: true, render: { visible: false } });
    const nozzleRight = Bodies.rectangle(cx + nozzleHeight / 2, cy - radius, guideThick, nozzleHeight, { isStatic: true, render: { visible: false } });
    const nozzleTop = Bodies.rectangle(cx, cy - radius - nozzleHeight, nozzleHeight + guideThick * 2, guideThick, { isStatic: true, render: { visible: false } });
    const nozzleBottom = Bodies.rectangle(cx, cy, nozzleHeight + guideThick * 2, guideThick, { isStatic: true, render: { visible: false } });
    Composite.add(world, [nozzleLeft, nozzleRight, nozzleTop, nozzleBottom]);
    // 1. [핵심] 유리 플라스크 및 노즐 렌더링 함수
    function drawGlassFlask(ctx, cx, cy, radius){
        ctx.beginPath();
        ctx.arc(cx, cy, radius, 0, Math.PI * 2);
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
        ctx.lineWidth = 2;
        ctx.stroke();

        balls.forEach(ball => {
            ctx.font = "bold 15px Arial";
            ctx.fillStyle = "#ffffff";
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText(ball.label, ball.position.x, ball.position.y);
        });
    }
    // 120개의 작은 사각형을 원형으로 배치하여 '물리적 플라스크' 생성
    const wallThickness = 14;
    const step = (Math.PI * 2) / segments;
    const segArc = (Math.PI * 2 * radius) / segments;
    const wallWidth = segArc * 1.6;
    for (let i = 0; i < segments; i++) {
        const angle = i * step;
        const x = cx + Math.cos(angle) * radius;
        const y = cy + Math.sin(angle) * radius;
        const wallSegment = Matter.Bodies.rectangle(x, y, wallWidth, wallThickness, {
            isStatic: true,
            angle: angle + Math.PI / 2,
            render: { visible: false }
        });
        Matter.Composite.add(world, wallSegment);
    }
    const innerR = radius - 6;
    const innerThickness = 8;
    const innerSegArc = (Math.PI * 2 * innerR) / segments;
    const innerWidth = innerSegArc * 1.3;
    for (let i = 0; i < segments; i++) {
        const angle = i * step;
        const x = cx + Math.cos(angle) * innerR;
        const y = cy + Math.sin(angle) * innerR;
        const seg2 = Matter.Bodies.rectangle(x, y, innerWidth, innerThickness, {
            isStatic: true,
            angle: angle + Math.PI / 2,
            render: { visible: false }
        });
        Matter.Composite.add(world, seg2);
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
    // 전역 팔레트로 노출하여 초기배치/당첨 공에서도 동일 팔레트를 사용
    window.PB_COLORS = colors;
    window.PB_POWER_COLOR = '#2980b9';
    for (let i = 1; i <= 10; i++) {
        const ballColor = colors[(i - 1) % 5];
        const ball = Bodies.circle(cx + (Math.random() - 0.5) * 60, cy + 50, BallRadius, {
            density: 0.001,
            restitution: 0.35,
            friction: 0.1,
            frictionAir: 0.02,
            frictionStatic: 0,
            label: i.toString(),
            ballColor: ballColor, // 커스텀 속성으로 색상 저장
            render: { visible: false } // 기본 렌더링은 끔 (직접 그리기 위해)
        });
        balls.push(ball);
        Composite.add(world, ball);
    }
    // 초기 결과 배치 (현재/이전)
    placeInitialResults();

    // Render 객체 생성 후 아래 이벤트 리스너 추가
    Matter.Events.on(render, 'afterRender', () => {
        const ctx = render.context;
        // 월드의 모든 바디를 가져옴
        const allBodies = Matter.Composite.allBodies(world);
        
        allBodies.forEach(body => {
            if (!body.isWinBall && !body.isPrev && balls.includes(body)) {
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


                if (false) {
                    // 그림자 효과를 주어 흰색 숫자가 더 또렷하게 보이도록 함
                    ctx.shadowColor = "rgba(0, 0, 0, 0.5)";
                    ctx.shadowBlur = 12;
                    ctx.shadowOffsetX = -4;
                    ctx.shadowOffsetY = -4;

                    ctx.font = "bold 18px Arial"; // 크기 약간 키움
                    ctx.fillStyle = "#ffffff";    // [수정] 번호를 흰색으로
                    ctx.textAlign = "center";
                    ctx.textBaseline = "middle";
                    const txt = String(body.label);
                    ctx.fillText(txt, x, y);
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
        const allBodies = Matter.Composite.allBodies(world);
        allBodies.forEach(body => {
            if (body.isWinBall || body.isPrev) {
                const { x, y } = body.position;
                const radius = body.circleRadius;
                ctx.save();
                const grad = ctx.createRadialGradient(
                    x - radius * 0.6, y - radius * 0.7, radius * 0.01,
                    x, y, radius * 1
                );
                grad.addColorStop(0, '#e7e0e0');
                grad.addColorStop(0.4, body.ballColor || '#e74c3c');
                ctx.beginPath();
                ctx.arc(x, y, radius, 0, Math.PI * 2);
                ctx.fillStyle = grad;
                ctx.fill();
                ctx.beginPath();
                ctx.arc(x, y, radius - 1, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
                ctx.lineWidth = 0.8;
                ctx.stroke();
                ctx.shadowColor = "rgba(0, 0, 0, 0.5)";
                ctx.shadowBlur = 12;
                ctx.shadowOffsetX = -4;
                ctx.shadowOffsetY = -4;
                ctx.font = "bold 18px Arial";
                ctx.fillStyle = "#ffffff";
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                const txt = body.isPower ? String(body.label) : String(body.label).padStart(2,'0');
                ctx.fillText(txt, x, y);
                ctx.restore();
            }
        });
        if(showOverlay){
            drawWaitingOverlay(ctx);
        }
        ctx.restore();
    });
    // 4. 추첨 전 섞기 효과 (Agitation)
    Matter.Events.on(engine, 'beforeUpdate', () => {
        if (isAgitating) {
            balls.forEach(ball => {
                if (Math.random() > 0.96) {
                    Matter.Body.applyForce(ball, ball.position, {
                        x: (Math.random() - 0.5) * 0.012,
                        y: -0.018 - (Math.random() * 0.018) 
                    });
                }
                const vx = ball.velocity.x, vy = ball.velocity.y;
                const speed = Math.hypot(vx, vy);
                const maxS = 12;
                if (speed > maxS) {
                    const k = maxS / speed;
                    Matter.Body.setVelocity(ball, { x: vx * k, y: vy * k });
                }
            });
        }
    });
    Render.run(render);
    runner = Runner.run(Runner.create(), engine);
    checkGameState(cfg);
}
// 번호→팔레트 색상 매핑 (라인 95의 colors 배열 사용)
function colorFromPalette(n){
    const arr = (typeof window!=='undefined' && window.PB_COLORS) ? window.PB_COLORS : ['#e74c3c','#2ecc71','#27ae60','#f1c40f','#2980b9'];
    const idx = (Number(n)-1) % arr.length;
    return arr[idx];
}
// 초기 결과 배치
function placeInitialResults(){
    if(!config) return;
    const curr = (config.initialNumbers||[]).slice(0,5);
    const currPower = config.powerball;
    const prev = (config.prevNumbers||[]).slice(0,5);
    const prevPower = config.prevPower;
    // 현재 결과
    if(curr.length===5 && currPower!=null){
        const seqNums = curr.concat([currPower]);
        seqNums.forEach((n,i)=>{
            const b = Matter.Bodies.circle(0,0,BallRadius,{
                isStatic:true,isSensor:true,isWinBall:true,render:{visible:false},ballColor: i===5 ? (window.PB_POWER_COLOR||'#2980b9') : colorFromPalette(n)
            });
            b.label = String(n);
            b.isPower = (i===5);
            const p = getResultTargetPos(i);
            Matter.Body.setPosition(b,{x:p.x,y:p.y});
            Matter.Composite.add(world,b);
        });
    }
    // 이전 결과
    if(prev.length===5 && prevPower!=null){
        const seqNums = prev.concat([prevPower]);
        seqNums.forEach((n,i)=>{
            const b = Matter.Bodies.circle(0,0,BallRadius,{
                isStatic:true,isSensor:true,isPrev:true,render:{visible:false},ballColor: i===5 ? (window.PB_POWER_COLOR||'#2980b9') : colorFromPalette(n)
            });
            b.label = String(n);
            b.isPower = (i===5);
            const p = getPrevTargetPos(i);
            Matter.Body.setPosition(b,{x:p.x,y:p.y});
            Matter.Composite.add(world,b);
        });
    }
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
    remainingSeconds = remaining;
    
    // 1. 공 섞기 제어 (30초 전부터 시작)
    const enteringDrawWindow = (remaining <= 10);
    // 정각 직전/직후 깜빡임 방지를 위해 isAnimationStarted 먼저 재평가
    if (remaining === 60 && !isAnimationStarted) {
        isAnimationStarted = true;
        fetchDrawResult();
    }
    isAgitating = (enteringDrawWindow || isAnimationStarted);
    // 오버레이는 오직 대기 상태에서만 보임. 10초 구간 또는 정각(60)에는 숨김
    showOverlay = (!enteringDrawWindow && !isAnimationStarted && remaining < 60);

    // 2. [추가] 정각(0초) 트리거는 위에서 처리함

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
    // 팔레트 기반 색상: 일반볼은 번호 기준, 파워볼은 원래 색(#2980b9)
    const ballColor = (type === 'power') ? (window.PB_POWER_COLOR||'#2980b9') : colorFromPalette(winNumber);

    const winBall = Matter.Bodies.circle(cx, cy - radius, BallRadius, {
        isStatic: true,             // GSAP 애니메이션으로 제어
        isSensor: true,             // 다른 물체와 겹쳐도 튕겨나가지 않게 함 (유령 효과)
        label: winNumber.toString(),
        isWinBall: true,            // 당첨 공 판별용 플래그
        isPower: (type === 'power'),
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
        // 다음 회차 아이디를 보관 (첫 공이 나올 때 라벨을 변경)
        if (!config) config = {};
        config.nextRound = data.dw_id;
        hasSlidThisRound = false;
        // 3초 간격으로 하나씩 추출
        normalNumbers.forEach((num, i) => {
            setTimeout(() => {
                if(i===0 && !hasSlidThisRound){
                    // 첫 공이 나오는 순간 현재 결과를 이전으로 수평 고정 후 y축으로만 이동
                    slideCurrentToHistory();
                    hasSlidThisRound = true;
                    // 회차 번호 갱신
                    if(config.nextRound){ config.lastRound = config.nextRound; }
                }
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
                // 다음 대기 단계에서 오버레이가 최신 결과를 표시하도록 초기 값 갱신
                if(!config) config = {};
                config.initialNumbers = normalNumbers.slice(0,5);
                config.powerball = powerBall;
                config.sum = normalNumbers.reduce((a,b)=>a+Number(b),0);
                hasSlidThisRound = false;

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
        // 더 이상 자동 하강 예약은 하지 않음. 첫 공이 나올 때 slideCurrentToHistory에서만 이동
    } catch (err) {
        console.error("추첨 연동 에러:", err);
    }
}
// 현재 결과를 이전 영역으로 평행 이동 (크기 유지)
function slideCurrentToHistory(){
    const allBodies = Matter.Composite.allBodies(world);
    // 1) 기존 이전결과 공 제거
    const prevBalls = allBodies.filter(b => b.isPrev);
    prevBalls.forEach(b => Matter.Composite.remove(world, b));
    // 2) 최신 결과 공을 이전 위치로 이동
    const winBalls = allBodies.filter(b => b.isWinBall && !b.isHistory);
    winBalls.forEach((ball,i)=>{
        ball.isHistory = true;
        ball.isPrev = true;
        ball.isWinBall = false; // 더 이상 최신결과로 취급하지 않음
        const target = getPrevTargetPos(i);
        gsap.to(ball.position, { x: target.x, y: target.y, duration: 1.0, ease: "power2.inOut" });
    });
    // 첫 공이 나오는 순간에는 대기 오버레이가 다시 그려지지 않도록 확실히 차단
    showOverlay = false;
    if(config){
        const nums = winBalls.map(b=>parseInt(b.label,10));
        if(nums.length>=6){
            const prevNums = nums.slice(0,5);
            const prevP = nums[5];
            config.prevRound = config.lastRound;
            config.prevNumbers = prevNums;
            config.prevPower = prevP;
            config.prevSum = prevNums.reduce((a,b)=>a+Number(b),0);
        }
    }
}
/**
 * 결과 박스 내 공의 목표 좌표를 계산하는 함수
 * @param {number} seq - 추출 순서 (0~5)
 * @returns {object} {x, y}
 */
function panelMetrics(){
    const panelX = cx + radius + 50; 
    const panelY = cy - radius - nozzleHeight / 2;
    const panelW = radius * 2.6;
    const labelH = 35;
    const label1Top = panelY + 20;
    const label2Top = panelY + 150;
    const l1c = label1Top + labelH/2;
    const l2c = label2Top + labelH/2;
    const gap = (l2c - l1c)/2; // 동일 간격을 위한 중간점
    const row1Y = l1c + gap;   // 최신 결과 중앙
    const row2Y = l2c + gap;   // 이전 결과 중앙
    // 수평 중앙 정렬: 6개 공을 spacing 간격으로 배치해 가로폭을 계산 후 가운데에 맞춤
    const count = 6;
    const spacing = 48; // 약간 여유 있는 간격
    const rowWidth = (count - 1) * spacing + 2 * BallRadius;
    const centerX = panelX + panelW / 2;
    const startX = centerX - rowWidth / 2 + BallRadius; // 첫 공의 중심 x
    return { startX, spacing, row1Y, row2Y };
}
function getResultTargetPos(seq) {
    const m = panelMetrics();
    return { x: m.startX + (seq * m.spacing), y: m.row1Y };
}
function getPrevTargetPos(seq){
    const m = panelMetrics();
    return { x: m.startX + (seq * m.spacing), y: m.row2Y };
}

// 대기 오버레이: 검정 배경 + 노란 테두리
function drawWaitingOverlay(ctx){
    // 플라스크 중심(cx,cy) 기준으로 배치, 너비는 바깥쪽 레일보다 소폭 더 넓게
    const outerRailExtra = (railGap + railThick * 4) + 12; // 레일 간격+두께 기반으로 여유 폭 추가
    const desiredW = (2 * radius) + outerRailExtra;
    const w = Math.min(desiredW, 800 - 20); // 캔버스 여백 보정
    const h = Math.floor(radius * 0.85);
    // 레일 포함 폭의 중심에 맞추기 위해 약간 왼쪽으로 이동
    const leftShift = Math.floor(railGap/2 + railThick*2);
    const OVERLAY_X_TWEAK = 8; // 오른쪽(+), 왼쪽(-) 미세조정. 기본: 약간 오른쪽으로 8px 이동
    const x = Math.floor((cx - leftShift + OVERLAY_X_TWEAK) - w / 2);
    const y = Math.floor(cy - h / 2);
    ctx.save();
    ctx.fillStyle = "#1b1b1b";
    ctx.globalAlpha = 0.92;
    roundRectPath(ctx, x, y, w, h, 10);
    ctx.fill();
    ctx.globalAlpha = 1;
    ctx.lineWidth = 3;
    ctx.strokeStyle = "#f5c400";
    ctx.stroke();
    // 텍스트
    // 텍스트는 항상 그림자 효과 제거 (또렷한 렌더링)
    ctx.shadowColor = 'transparent';
    ctx.shadowBlur = 0;
    ctx.shadowOffsetX = 0;
    ctx.shadowOffsetY = 0;
    const nextId = (config && config.lastRound) ? (config.lastRound + 1) : 0;
    const mm = Math.floor(remainingSeconds/60);
    const ss = remainingSeconds%60;
    ctx.fillStyle = "#e9e9e9";
    ctx.font = "bold 16px 'Malgun Gothic', sans-serif";
    ctx.textAlign="center"; ctx.textBaseline="middle";
    ctx.fillText(`${mm}분 ${ss.toString().padStart(2,'0')}초 후 ${nextId}회차 결과 발표`, x + w/2, y + Math.floor(h*0.25));
    // 이전 회차 결과 라벨: 대기 상태에서는 직전 확정 회차(= lastRound)를 우선 사용
    const prevId = (!hasSlidThisRound && config && config.lastRound) ? config.lastRound
                   : ((config && config.prevRound) ? config.prevRound : (config && config.lastRound ? config.lastRound : 0));
    ctx.fillStyle = "#d4d4d4";
    ctx.font = "bold 13px 'Malgun Gothic', sans-serif";
    ctx.fillText(`[${prevId}회차] 결과는`, x + w/2, y + Math.floor(h*0.48));
    // 결과 배열: 대기 상태에서는 lastRound 결과(= initialNumbers)를 우선 사용
    const usePrev = hasSlidThisRound && config && Array.isArray(config.prevNumbers) && config.prevNumbers.length===5;
    const nums = usePrev ? config.prevNumbers : (config && Array.isArray(config.initialNumbers) ? config.initialNumbers : []);
    const p = usePrev ? config.prevPower : (config ? config.powerball : null);
    const sumVal = usePrev ? (config && config.prevSum!=null ? config.prevSum : null)
                           : (config && config.sum!=null ? config.sum : (nums.length ? nums.reduce((a,b)=>a+Number(b),0) : null));
    const padded = nums.map(n=>String(n).padStart(2,'0'));
    const pre = `[ ${padded.join(', ')}${(p!=null||sumVal!=null)?', ':''}`;
    const mid = (p!=null)? String(p) : '';
    const sep = (p!=null && sumVal!=null) ? ', ' : '';
    const sumTxt = (sumVal!=null? String(sumVal) : '');
    const endTxt = ' ]입니다.';
    const textY = y + Math.floor(h*0.75);
    // 폭을 계산해 중앙 정렬 후 색상 분리 출력
    ctx.font = "bold 15px 'Malgun Gothic', sans-serif";
    ctx.textAlign="left";
    const preW = ctx.measureText(pre).width;
    const midW = ctx.measureText(mid).width;
    const sepW = ctx.measureText(sep).width;
    const sumW = ctx.measureText(sumTxt).width;
    const endW = ctx.measureText(endTxt).width;
    const totalW = preW + midW + sepW + sumW + endW;
    let cursor = (x + w/2) - (totalW/2);
    ctx.fillStyle = "#e9e9e9";
    ctx.fillText(pre, cursor, textY);
    cursor += preW;
    if(p!=null){
        ctx.fillStyle = "#3aa0ff"; // 파워볼 파란색
        ctx.fillText(mid, cursor, textY);
        cursor += midW;
        ctx.fillStyle = "#e9e9e9";
        ctx.fillText(sep, cursor, textY);
        cursor += sepW;
    }
    if(sumVal!=null){
        ctx.fillStyle = "#e9e9e9";
        ctx.fillText(sumTxt, cursor, textY);
        cursor += sumW;
    }
    ctx.fillText(endTxt, cursor, textY);
    ctx.restore();
}
function roundRectPath(ctx,x,y,w,h,r){
    ctx.beginPath();
    ctx.moveTo(x+r, y);
    ctx.lineTo(x+w-r, y);
    ctx.quadraticCurveTo(x+w, y, x+w, y+r);
    ctx.lineTo(x+w, y+h-r);
    ctx.quadraticCurveTo(x+w, y+h, x+w-r, y+h);
    ctx.lineTo(x+r, y+h);
    ctx.quadraticCurveTo(x, y+h, x, y+h-r);
    ctx.lineTo(x, y+r);
    ctx.quadraticCurveTo(x, y, x+r, y);
    ctx.closePath();
}
function shiftResultsToHistory() {
    const allBodies = Matter.Composite.allBodies(world);
    const winBalls = allBodies.filter(b => b.isWinBall && !b.isHistory);

    winBalls.forEach((ball, i) => {
        ball.isHistory = true; // 히스토리 상태로 변경 (중복 이동 방지)
        const target = getPrevTargetPos(i);
        gsap.to(ball.position, {
            x: target.x,
            y: target.y,
            duration: 1.2,
            ease: "power2.inOut"
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

