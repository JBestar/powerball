/**
 * 파워볼 실시간 추첨 애니메이션 엔진
 * 핵심 로직: Matter.js (물리) + GSAP (연출 제어)
 */

let engine, render, runner, world;
let balls = [];
let isAgitating = false;
const cx = 200, cy = 210, radius = 120; // 왼쪽 영역(500px)의 중앙인 250에 배치
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
            width: 400,
            height: 400,
            wireframes: false,
            background: 'transparent'
        }
    });
    // 1. 유리관(Chamber) 좌표 및 크기 재조정 (타원형 방지)
    
    const segments = 120;
    const nozzleHeight = 44;
    const guideThick = 10;
    // 2. 물리적 노즐 입구 생성 (공이 나갈 수 있게 양옆에 벽 세우기)
//    const nozzleLeft = Bodies.rectangle(cx - nozzleHeight / 2, cy - radius, guideThick, nozzleHeight, { isStatic: true, render: { visible: false } });
    const nozzleRight = Bodies.rectangle(cx + nozzleHeight / 2, cy - radius, guideThick, nozzleHeight, { isStatic: true, render: { visible: false } });
    const nozzleTop = Bodies.rectangle(cx, cy - radius - nozzleHeight, nozzleHeight + guideThick * 2, guideThick, { isStatic: true, render: { visible: false } });
    Composite.add(world, [nozzleTop, nozzleRight]);
    const railGap = 34; // 이중 레일 사이의 간격 (공 크기 28~30px 고려)
    const railThick = 4;
    // 2. [핵심] 유리 질감 렌더링 (afterRender)
    // [Catmull-Rom Spline 보간 함수]
    function getSplinePoint(t, p0, p1, p2, p3) {
        const v0 = (p2 - p0) * 0.5;
        const v1 = (p3 - p1) * 0.5;
        const t2 = t * t;
        const t3 = t * t2;
        return (2 * p1 - 2 * p2 + v0 + v1) * t3 + (-3 * p1 + 3 * p2 - 2 * v0 - v1) * t2 + v0 * t + p1;
    }
    const getRailPos = (prog, side) => {
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

        if (prog < 1.0) {
            const numSegments = 6;
            const totalT = prog * numSegments;
            const i = Math.floor(totalT);
            const t = totalT % 1;

            const p0 = pts[Math.max(i - 1, 0)];
            const p1 = pts[i];
            const p2 = pts[Math.min(i + 1, 7)];
            const p3 = pts[Math.min(i + 2, 7)];

            // 현재 위치 계산
            const posX = getSplinePoint(t, p0.x, p1.x, p2.x, p3.x);
            const posY = getSplinePoint(t, p0.y, p1.y, p2.y, p3.y);

            // [핵심] 평행 유지를 위한 기울기(미분) 계산
            const delta = 0.001; // 아주 작은 차이
            const nextX = getSplinePoint(t + delta, p0.x, p1.x, p2.x, p3.x);
            const nextY = getSplinePoint(t + delta, p0.y, p1.y, p2.y, p3.y);
            
            // 진행 방향 각도
            const angle = Math.atan2(nextY - posY, nextX - posX);
            
            // 진행 방향에 수직(+90도)으로 offset 적용
            return {
                x: posX + Math.sin(angle) * offset,
                y: posY - Math.cos(angle) * offset
            };
        } else {
            // 직선 구간은 기존처럼 수직 offset만 적용해도 평행함
            const t = prog - 1.0;
            return {
                x: pts[6].x + (pts[8].x - pts[6].x) * t,
                y: pts[6].y - offset
            };
        }
    };
    // 1. [핵심] 유리 플라스크 및 노즐 렌더링 함수
    function drawGlassFlask(ctx, cx, cy, radius){
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
        // 노란색 입구(노즐) 부분은 비워두기 위해 각도 제한 (예: 1.6rad ~ 7.8rad)
        const angle = (i / segments) * Math.PI * 2;
//        if (angle > 4.91 || angle < 4.53) { // 윗부분을 비워둠
            const x = cx + Math.cos(angle) * radius;
            const y = cy + Math.sin(angle) * radius;
            
            const wallSegment = Matter.Bodies.rectangle(x, y, wallWidth, wallThickness, {
                isStatic: true, // 고정된 벽
                angle: angle,
                render: { visible: true } // 화면에는 안 보임 (그림은 이미 그려져 있으므로)
            });
            Matter.Composite.add(world, wallSegment);
//        }
    }

    // 2. [렌더링 루프] 레일 -> 플라스크 순서로 그려야 입체감이 납니다.
    // [입체감 있는 레일 그리기]
    Events.on(render, 'afterRender', () => {
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
            for (let p = 0; p <= 2.0; p += 0.01) {
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
        
        ctx.restore();

        // 3. 플라스크 및 노즐 호출 (중심 좌표 확인 필수)
        drawGlassFlask(ctx, cx, cy, radius); 
    });

    // 3. 공 생성 (이미지 기준 5가지 색상 로테이션)
    const colors = ['#e74c3c', '#2ecc71', '#27ae60', '#f1c40f', '#2980b9'];
    for (let i = 1; i <= 10; i++) { // 일반볼+파워볼 합계만큼 생성
        const ballColor = colors[(i - 1) % 5];
        const ball = Bodies.circle(cx + (Math.random() - 0.5) * 60, cy + 50, 19, {
            density: 0.001,
            restitution: 0.85,
            friction: 0.005,
            frictionAir: 0.1,
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
                    y: -0.025 - (Math.random() * 0.02) 
                });
            }
        });
    }
});


    Render.run(render);
    runner = Runner.run(Runner.create(), engine);

    checkGameState(config);
}

// 5. 당첨 공 추출 전 물리 설정 해제
function prepareForExtraction(ball) {
    // 충돌 그룹을 변경하여 다른 공들과 상호작용하지 않게 합니다.
    ball.collisionFilter = { group: -1 };
    // 물리 엔진 업데이트를 정지하고 GSAP가 위치를 제어하게 합니다.
    Matter.Body.setStatic(ball, true);
}

// 6. 당첨 공 추출 애니메이션
function extractBall(ballLabel, sequence) {
    const ball = balls.find(b => b.label == ballLabel);
    if (!ball) return;

    prepareForExtraction(ball);
    const tl = gsap.timeline();
    
    // 이 예시의 좌표는 PDF 예시를 기반으로 하며, 실제 레일 경로에 맞춰 조정해야 합니다.
    tl.to(ball.position, {
        x: cx, y: cy - radius - 20, // 노즐 입구 상단으로 이동
        duration: 0.8,
        ease: "power2.out"
    })
    // 실제 프로젝트에서는 MotionPathPlugin을 사용하여 복잡한 레일 곡선을 따라 이동시킵니다.
    // 예시: .to(ball.position, { motionPath: { path: "#yourSVGPathID", ... }, ... }) 
    .to(ball.position, {
        // 이 좌표들은 플레이스홀더이며, 실제 UI 결과 보드 위치에 맞게 수정 필요
        x: 650, y: 100 + (sequence * 50), // 최종 결과창 위치로 하강 및 안착
        duration: 2.5,
        ease: "power2.inOut",
        onComplete: () => {
            // 여기에 결과 보드 UI 업데이트 로직 추가
            console.log(`Ball ${ballLabel} arrived at position ${sequence + 1}`);
        }
    });

    if (sequence === 5) { // 파워볼(마지막 공) 추출 시 화면 효과
        gsap.to('#lottery-canvas', { x: 5, duration: 0.05, repeat: 10, yoyo: true });
    }
}

// 7. 서버 시간 기준 상태 관리
// 7. 서버 시간 기준 상태 관리 (통합본)
function checkGameState(config) {
    const now = Math.floor(Date.now() / 1000);
    const remaining = 60 - (now % 60); // 1분 주기 (60초)
    
    // 1. 공 섞기 제어 (30초 전부터 시작)
    isAgitating = (remaining <= 30 && remaining > 0);

    // 2. [추가] 정각(0초)에 서버 데이터 가져오기 및 애니메이션 트리거
    // timeLeft가 60일 때(정각) 딱 한 번만 호출되도록 플래그 사용
    if (remaining === 60 && !isAnimationStarted) {
        isAnimationStarted = true;
        console.log("추첨 시작! 서버 데이터를 가져옵니다.");
        fetchDrawResult(); // 서버 API 호출 함수
    }

    // 3. 다음 회차 준비를 위해 플래그 리셋 (추첨이 한창 진행 중일 때 리셋)
    if (remaining < 50 && remaining > 40) {
        isAnimationStarted = false;
    }

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

function spawnAndExtractBall(winNumber, sequence) {
    // 1. 노즐 시작점 좌표 (예시: cx, cy - radius)
    const startX = cx; 
    const startY = cy - radius - 20;

    // 2. 물리 엔진 영향 없는 '유령 공' 생성 (렌더링용)
    const winBall = Bodies.circle(startX, startY, 17, {
        isStatic: true, // 물리 엔진이 건드리지 못하게 고정
        render: { 
            // 아까 만든 입체감 그라데이션 적용 가능
            sprite: { /* 입체 공 이미지나 캔버스 드로잉 사용 */ } 
        }
    });
    
    // 번호 표시를 위해 속성 추가
    winBall.label = winNumber;
    winBall.isExtracted = true; 
    extractedBalls.push(winBall); // 별도 관리 배열
    Composite.add(world, winBall);

    // 3. GSAP 레일 애니메이션
    const tl = gsap.timeline();
    tl.to(winBall.position, {
        x: cx - 100, // 레일의 꺾임 포인트 1
        y: cy - radius - 50,
        duration: 0.8,
        ease: "power1.inOut"
    })
    .to(winBall.position, {
        x: 650, // 최종 결과 보드 X 좌표
        y: 100 + (sequence * 50), // 결과 보드 Y 좌표 (순차적 배치)
        duration: 1.5,
        ease: "bounce.out", // 도착 시 살짝 튕기는 효과
        onComplete: () => {
            console.log(`${winNumber}번 공 안착 완료!`);
        }
    });
}
let isAnimationStarted = false;

async function fetchDrawResult() {
    try {
        const response = await fetch('lottery/getDrawResult');
        const data = await response.json();
        
        const normalNumbers = [data.n1, data.n2, data.n3, data.n4, data.n5];
        const powerBall = data.p1;

        // 3초 간격으로 하나씩 추출
        normalNumbers.forEach((num, i) => {
            setTimeout(() => {
                spawnWinningBall(num, 'normal', i);
            }, i * 3000);
        });

        // 마지막 파워볼 (일반볼 다 나오고 3초 뒤)
        setTimeout(() => {
            spawnWinningBall(powerBall, 'power', 5);
        }, 15000);

    } catch (err) {
        console.error("추첨 연동 에러:", err);
    }
}

// 당첨 공을 생성하고 애니메이션을 실행하는 함수
function spawnWinningBall(number, type, sequence) {
    // 1. 노즐 위치 (사용자 설정 좌표 cx, cy 기반)
    const startX = cx; 
    const startY = cy - radius - 20;

    // 이미지 예시 색상 매칭
    const resultColors = ['#2980b9', '#f39c12', '#27ae60', '#e74c3c', '#c0392b', '#2980b9'];
    const ballColor = (type === 'power') ? '#2980b9' : resultColors[sequence % resultColors.length];

    const winBall = Matter.Bodies.circle(cx, cy - radius, 17, {
        isStatic: true,
        label: number.toString(),
        isWinBall: true,
        ballType: type,
        ballColor: ballColor, // 결정된 색상 적용
        render: { visible: false }
    });

    Matter.Composite.add(world, winBall);

    // 3. GSAP 애니메이션 (레일 이동 경로)
    const tl = gsap.timeline();
    
    // 입구에서 살짝 위로 튀었다가 왼쪽으로 이동 (연출)
    tl.to(winBall.position, {
        y: startY - 30,
        duration: 0.5,
        ease: "power2.out"
    })
    .to(winBall.position, {
        x: cx - 150, // 왼쪽 레일 시작점 (좌표는 환경에 맞게 조정)
        y: cy - radius - 50,
        duration: 1,
        ease: "power1.inOut"
    })
    .to(winBall.position, {
        x: 700, // 최종 결과 보드 X 좌표
        y: 150 + (sequence * 45), // 결과판에 순서대로 쌓임
        duration: 2,
        ease: "bounce.out",
        onComplete: () => {
            console.log(`공 ${number} 안착 완료!`);
        }
    });
}

function startExtractionSequence(numbers, powerball) {
    // 일반볼 5개 추출
    numbers.forEach((num, i) => {
        setTimeout(() => {
            spawnWinningBall(num, 'normal', i);
        }, i * 3000); // 3초 간격
    });

    // 파워볼 추출
    setTimeout(() => {
        spawnWinningBall(powerball, 'power', 5);
    }, 15000); // 일반볼 다 나온 후
}
