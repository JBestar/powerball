<?php

namespace App\Controllers;

/**
 * 파워볼 애니메이션 전용 메인 컨트롤러
 * 추첨 데이터 생성 및 애니메이션 뷰 호출에 집중
 */
class Home extends BaseController
{
    public function index()
    {
        // 1. 모델 로드 및 현재 시간 설정
        $drawModel = new \App\Models\Draw_Model();
        $currentTime = time();
        
        // 2. 현재 회차 결과 가져오기 (없으면 모델에서 자동 생성 및 DB 저장)
        $currentDraw = $drawModel->getOrGenerate($currentTime);

        // 3. 뷰에 전달할 최소한의 데이터 구성
        $viewData = [
            'draw_data' => [
                'last_id'     => $currentDraw->dw_id,
                'numbers'     => [
                    $currentDraw->n1, 
                    $currentDraw->n2, 
                    $currentDraw->n3, 
                    $currentDraw->n4, 
                    $currentDraw->n5
                ],
                'powerball'   => $currentDraw->p1,
                'server_time' => $currentTime
            ],
            // 에러 방지를 위한 headInfo 기본값 (View에서 참조 시 에러 방지)
            'headInfo' => [
                'lang' => 'ko'
            ]
        ];

        // 4. 메인 애니메이션 뷰 호출
        return view('home/main', $viewData);
    }

    /**
     * 실시간 추첨 결과 API (JS Polling용)
     */
    public function get_live_status()
    {
        $drawModel = new \App\Models\Draw_Model();
        $current = $drawModel->getLatest();
        
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $current,
            'time'   => time()
        ]);
    }
}
