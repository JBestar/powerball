<?php

namespace App\Models;
use CodeIgniter\Model;

class Draw_Model extends Model
{
    protected $table      = 'draws';
    protected $primaryKey = 'dw_id';
    protected $returnType = 'object';
    protected $allowedFields = ['drawn_at', 'n1', 'n2', 'n3', 'n4', 'n5', 'p1', 'sum'];
    protected $useAutoIncrement = false;
    // 최신 결과 1건 조회
    public function getLatest()
    {
        return $this->orderBy('dw_id', 'DESC')->first();
    }
    /**
     * 고급 피셔-예츠 셔플 알고리즘 (암호학적 난수 사용)
     */
    private function secureShuffle($items)
    {
        $count = count($items);
        for ($i = $count - 1; $i > 0; $i--) {
            // 암호학적으로 안전한 무작위 인덱스 선택
            $j = random_int(0, $i);
            // 요소 교체 (Swap)
            $temp = $items[$i];
            $items[$i] = $items[$j];
            $items[$j] = $temp;
        }
        return $items;
    }

    public function getOrGenerate($currentTime)
    {
        $interval = 60; 

        $draw = $this->orderBy('dw_id', 'DESC')->first();
        // 마지막 추첨 후 1분이 지났거나 데이터가 아예 없는 경우 새로 생성
        if (!$draw || ($currentTime - strtotime($draw->drawn_at)) >= $interval){
            // 1. 1~28까지의 볼 준비
            $balls = range(1, 28);
            
            // 2. 고급 셔플을 3회 반복하여 예측 불가능성 극대화 (Triple Mix)
            for($mix = 0; $mix < 3; $mix++) {
                $balls = $this->secureShuffle($balls);
            }

            // 3. 상위 5개 추출 및 정렬
            $selected = array_slice($balls, 0, 5);
            sort($selected);

            // 4. 파워볼 별도 생성 (0~9)
            $powerBall = random_int(0, 9);

            $data = [
                'drawn_at' => date('Y-m-d H:i:s'),
                'n1'       => (int)$selected[0],
                'n2'       => (int)$selected[1],
                'n3'       => (int)$selected[2],
                'n4'       => (int)$selected[3],
                'n5'       => (int)$selected[4],
                'p1'       => (int)$powerBall,
                'sum'      => (int)array_sum($selected)
            ];
            $this->insert($data);
            $data['dw_id'] = $this->insertID(); 
            return (object)$data;
        }
        return $draw;
    }
}
