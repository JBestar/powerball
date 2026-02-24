<?php
namespace App\Controllers;
use App\Models\Draw_Model;

class Lottery extends BaseController {
    public function getDrawResult() {
        $model = new Draw_Model();
        // 현재 시간 기준으로 결과를 가져오거나 없으면 생성함
        $result = $model->getOrGenerate(time());
        return $this->response->setJSON($result);
    }
}
