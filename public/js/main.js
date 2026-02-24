// 1. 공들이 플라스크 밑에 쌓여있는 상태 (추첨 대기)
function initWaitingState() {
    // 공들을 생성하여 하단에 뭉쳐두기 (중력 작용)
    spawnBalls(300, 450); 
}

// 2. 추첨 시작 30초 전: 무작위 튀어오름 (Matter.js)
function startAgitation() {
    setInterval(() => {
        balls.forEach(ball => {
            // 위쪽으로 무작위 힘을 가해 튀어오르게 함
            Matter.Body.applyForce(ball, ball.position, {
                x: (Math.random() - 0.5) * 0.02,
                y: -0.05 - Math.random() * 0.05
            });
        });
    }, 200);
}

// 3. 당첨 시: 노란 노즐을 통해 레일로 이동 (GSAP)
function extractBall(ballNumber) {
    const targetBall = balls.find(b => b.label === ballNumber);
    
    // GSAP 경로 애니메이션 (PDF 2페이지 레일 동선 구현)
    const tl = gsap.timeline();
    tl.to(targetBall.position, {
        y: 100, // 상단 노란 노즐 위치로 상승
        duration: 1,
        onStart: () => Matter.Body.setStatic(targetBall, true)
    })
    .to(targetBall.position, {
        x: 100, // 왼쪽 레일 진입
        y: 200, // 레일 타고 하강
        duration: 1.5,
        ease: "power1.in"
    })
    .to(targetBall.position, {
        x: 550, // 우측 결과 보드 안착
        y: 150,
        duration: 1
    });
}
