<?php

  	function is_login($checkCookie = false){
      $hasSession = isset($_SESSION['logged_in']);
      $sessionVal = $hasSession ? json_encode($_SESSION['logged_in']) : 'not set';
      $hasCookie = isset($_COOKIE['logged']);
      $cookieVal = $hasCookie ? $_COOKIE['logged'] : 'not set';
      writeLog("[is_login] checkCookie=" . ($checkCookie ? '1' : '0') . " session(logged_in)=" . $sessionVal . " cookie(logged)=" . $cookieVal);

      if(!$hasSession)
        return false;
      else if($checkCookie && !$hasCookie)
        return false;
      else if($checkCookie && $cookieVal !== 'yes')
        return false;
      else if($_SESSION['logged_in'] == TRUE)
        return true;
      else return false;
  	}

    function is_Mobile(){
      $useragent=$_SERVER['HTTP_USER_AGENT'];
      if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)))
        return true;
      return false;
    }

    if (! function_exists('is_request_https')) {
        /**
         * 프록시(X-Forwarded-Proto) 뒤에서도 HTTPS 페이지로 판별 (Mixed Content 방지).
         */
        function is_request_https(): bool
        {
            if (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                return true;
            }
            if (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443') {
                return true;
            }
            if (! empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
                return true;
            }
            if (! empty($_SERVER['HTTP_X_FORWARDED_SSL']) && (string) $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
                return true;
            }

            return false;
        }
    }

    function site_furl($url){
      $base = $_ENV['app.furl'] ?? '';
      $out = (substr($url, 0, 1) == "/")
        ? $base.$url
        : $base."/".$url;
      if (is_request_https() && preg_match('#^http://#i', $out)) {
        $out = preg_replace('#^http://#i', 'https://', $out, 1);
      }

      return $out;
    }
    
    function num_format($numVal,$afterPoint=2,$minAfterPoint=0,$thousandSep=",",$decPoint="."){
      // Same as number_format() but without unnecessary zeros.
      $ret = number_format($numVal,$afterPoint,$decPoint,$thousandSep);
      if($afterPoint!=$minAfterPoint){
        while(($afterPoint>$minAfterPoint) && (substr($ret,-1) =="0") ){
          // $minAfterPoint!=$minAfterPoint and number ends with a '0'
          // Remove '0' from end of string and set $afterPoint=$afterPoint-1
          $ret = substr($ret,0,-1);
          $afterPoint = $afterPoint-1;
        }
      }
      if(substr($ret,-1)==$decPoint) {$ret = substr($ret,0,-1);}
      return $ret;
    }

    //sidebar info
    function getNavInfo($objUser = NULL) {

      $navInfo= array(
          'user_id' => '',
          'user_name' => '',
          'user_grade' => GRADE_1,
          'user_money' => '0',
          'user_point' => '0',
          'user_off' => false,
      );
      if(!is_null($objUser)){
        $navInfo['user_id'] = $objUser->mb_uid;
        $navInfo['user_name'] = $objUser->mb_nickname;
        $navInfo['user_grade'] = $objUser->mb_grade;
        $navInfo['user_money'] = floor(allMoney($objUser)); //num_format(allMoney($objUser), NUM_POINT_CNT);
        $navInfo['user_point'] = floor($objUser->mb_point);//num_format(floatval($objUser->mb_point), NUM_POINT_CNT);
        if(array_key_exists('app.tree', $_ENV) && intval($_ENV['app.tree']) == 1 )
          $navInfo['user_off'] = intval($objUser->mb_state_delete) > 0;
      }

      return $navInfo;
    }

    function getUserInfo($objUser, $objEmp = null) {

      $userInfo['user_id'] = $objUser->mb_uid;
      $userInfo['user_name'] = $objUser->mb_nickname;
      $userInfo['user_grade'] = $objUser->mb_grade;
      $userInfo['user_wallet'] = floor($objUser->mb_money);
      $userInfo['user_money'] = floor(allMoney($objUser)); //num_format(allMoney($objUser), NUM_POINT_CNT, 0);
      $userInfo['user_egg'] = floor(allEgg($objUser));
      $userInfo['user_point'] = floor($objUser->mb_point);//num_format(floatval($objUser->mb_point), NUM_POINT_CNT, 0);
      $userInfo['user_off'] = false;
      if(array_key_exists('app.tree', $_ENV) && intval($_ENV['app.tree']) == 1 )
        $userInfo['user_off'] = intval($objUser->mb_state_delete) > 0;
      $userInfo['user_bank_name'] = $objUser->mb_bank_name;

      $len = mb_strlen($objUser->mb_bank_own);
      $own = $objUser->mb_bank_own;
      if($len > 1){
        $own = mb_substr($objUser->mb_bank_own, 0, 1);
        $own.= str_repeat('*', $len - 1);
      }
      $userInfo['user_bank_own'] = $own;

      $len = mb_strlen($objUser->mb_bank_num);
      $num = $objUser->mb_bank_num;
      if($len > 2){
        $num = mb_substr($objUser->mb_bank_num, 0, 2);
        $num.= str_repeat('*', $len - 2);
      }
      $userInfo['user_bank_num'] = $num;
      $userInfo['user_phone'] = $objUser->mb_phone;
      $userInfo['user_join_at'] = $objUser->mb_time_join;
      $userInfo['user_login_last'] = $objUser->mb_time_last;
      $userInfo['user_ip_last'] = $objUser->mb_ip_last;
      $userInfo['user_alarm'] = $objUser->mb_state_alarm;
      if($objEmp != null)
        $userInfo['user_emp'] = $objEmp->mb_uid;
      else
        $userInfo['user_emp'] = "";

      return $userInfo;
    }

    function getMiniInfo() {

      $navInfo= array(
          'gm_ref' => '',
          'gm_bgb' => '',
          'gm_bgl' => '',
          'gm_e5' => '',
          'gm_e3' => '',
          'gm_r5' => '',
          'gm_r3' => '',
          'gm_bg' => '',
          'gm_eos' => '',
          'gm_ro' => '',
          'gm_pbg' => '',
          'gm_dhp' => '',
          'gm_evp' => '',
          'gm_spk' => '',
          'ls_rnd' => '',
          'ls_bet' => '',
      );
      
      return $navInfo;
    }

    function getDatesInfo() {
      $tmNow = time();
      return array(
        date('Y/m/d', $tmNow),
        date('Y/m/d', strtotime("-1 day", $tmNow)),
        date('Y/m/d', strtotime("-2 days", $tmNow)),
        date('Y/m/d', strtotime("-3 days", $tmNow)),
        date('Y/m/d', strtotime("-4 days", $tmNow)),
      );
    }

    function getPbRoundTimes($objConfPb, $bAdvance = false){

      //date_default_timezone_set('Asia/Seoul');
      //$tmNow = mktime('23','59','40','5','25','2021')+TM_OFFSET;
      $tmNow = time()+ ($bAdvance ? ADVANCE_SEC:0);
      if($objConfPb->game_index == GAME_SPKN_BALL)
        $tmNow += 3*60; 
      
      $nYear = date("Y",$tmNow);
      $nMonth = date("m",$tmNow);
      $nDay = date("d",$tmNow);

      $nHour = date("G",$tmNow);
      $nMin = date("i",$tmNow);

      $nSumMinutes = $nHour * 60 + $nMin ;
      $nRoundNo = floor($nSumMinutes / 5) ;
      $nRoundNo = $nRoundNo % 288 + 1;
      $arrRoundInfo['round_no'] = $nRoundNo;

      $strDate = "";
      if($nSumMinutes < 1440){
        $strDate = date( 'Y-m-d', $tmNow );
      }
      else {
        $strDate = date('Y-m-d', strtotime("+1 day", $tmNow));
      }

      $arrRoundInfo['round_date'] = $strDate;

      $nSumMinutes = $nRoundNo * 5 ;
      $nHour = $nSumMinutes / 60;
      $nHour = floor($nHour);
      $nMinute = $nSumMinutes % 60;

      //현재시간설정      
      $tmRoundCurrent = date("Y-m-d H:i:s", time());        
      $arrRoundInfo['round_current'] = $tmRoundCurrent;

      //회차 마감시간설정
      $strRoundEnd = $strDate." ".$nHour.":".$nMinute.":"."0";
      $tmRoundEnd = strtotime($strRoundEnd);
      if($bAdvance)
        $tmRoundEnd -= ADVANCE_SEC;
        // $tmRoundEnd = strtotime("-".ADVANCE_SEC." seconds", $tmRoundEnd);
      if($objConfPb->game_index == GAME_SPKN_BALL)
        $tmRoundEnd -= (3*60-5); 
      $arrRoundInfo['round_end'] = date("Y-m-d H:i:s", $tmRoundEnd);
      
      //회차 시작시간
      $tmRoundStart = strtotime("-5 minutes", $tmRoundEnd);
      $arrRoundInfo['round_start'] = date("Y-m-d H:i:s", $tmRoundStart);
      
      $tmBetEnd = 0;
      //베팅 마감시간
      if($objConfPb->game_time_countdown >= 20 && $objConfPb->game_time_countdown <= 280 ) {
        //$objConfPb->game_time_countdown += 5;
        $tmBetEnd = strtotime("-".$objConfPb->game_time_countdown." seconds", $tmRoundEnd);      
      } else $tmBetEnd = strtotime("-1 minutes", $tmRoundEnd); 

      $arrRoundInfo['round_bet_end'] = date("Y-m-d H:i:s", $tmBetEnd);
      
      return $arrRoundInfo;
    }

    function getBbRoundTimes($objConf){

      $tmNow = time();
      $nYear = date("Y",$tmNow);
      $nMonth = date("m",$tmNow);
      $nDay = date("d",$tmNow);

      $nHour = date("G",$tmNow);
      $nMin = date("i",$tmNow);
      //$second = date("s",$tmNow);

      $nSumMinutes = $nHour * 60 + $nMin;
      $nRoundNo = floor($nSumMinutes / 2) ;
      $nRoundNo = $nRoundNo % 720 + 1;
      $arrRoundInfo['round_no'] = $nRoundNo;

      $strDate = date( 'Y-m-d', $tmNow );
      $arrRoundInfo['round_date'] = $strDate;

      $nSumMinutes = $nRoundNo * 2 ;
      $nHour = $nSumMinutes / 60;
      $nHour = floor($nHour);
      $nMinute = $nSumMinutes % 60;

      //현재시간   
      $tmRoundCurrent = date("Y-m-d H:i:s", $tmNow);        
      $arrRoundInfo['round_current'] = $tmRoundCurrent;

      //회차 마감시간
      $strRoundEnd = $strDate." ".$nHour.":".$nMinute.":"."0";
      $tmRoundEnd = strtotime($strRoundEnd);
      $arrRoundInfo['round_end'] = date("Y-m-d H:i:s", $tmRoundEnd);
      
      //회차 시작시간
      $tmRoundStart = strtotime("-2 minutes", $tmRoundEnd);
      $arrRoundInfo['round_start'] = date("Y-m-d H:i:s", $tmRoundStart);
      
      $tmBetEnd = 0;
      //베팅 마감시간설정
      if($objConf->game_bet_permit != PERMIT_OK){
        $tmBetEnd = $tmRoundStart;
      } else if($objConf->game_time_countdown >= 20 && $objConf->game_time_countdown <= 90 ) {
        //$objConf->game_time_countdown += 5;
        $tmBetEnd = strtotime("-".$objConf->game_time_countdown." seconds", $tmRoundEnd);      
      } else $tmBetEnd = strtotime("-1 minutes", $tmRoundEnd); 

      $arrRoundInfo['round_bet_end'] = date("Y-m-d H:i:s", $tmBetEnd);
      
      return $arrRoundInfo;
    }
    
    function getBsRoundTimes($objConf){

      $tmNow = time();
      $nYear = date("Y",$tmNow);
      $nMonth = date("m",$tmNow);
      $nDay = date("d",$tmNow);

      $nHour = date("G",$tmNow);
      $nMin = date("i",$tmNow);
      //$second = date("s",$tmNow);

      $nSumMinutes = $nHour * 60 + $nMin;
      $nRoundNo = floor($nSumMinutes / 3) ;
      $nRoundNo = $nRoundNo % 480 + 1;
      $arrRoundInfo['round_no'] = $nRoundNo;

      $strDate = date( 'Y-m-d', $tmNow );
      $arrRoundInfo['round_date'] = $strDate;

      $nSumMinutes = $nRoundNo * 3 ;
      $nHour = $nSumMinutes / 60;
      $nHour = floor($nHour);
      $nMinute = $nSumMinutes % 60;

      //현재시간   
      $tmRoundCurrent = date("Y-m-d H:i:s", $tmNow);        
      $arrRoundInfo['round_current'] = $tmRoundCurrent;

      //회차 마감시간
      $strRoundEnd = $strDate." ".$nHour.":".$nMinute.":"."0";
      $tmRoundEnd = strtotime($strRoundEnd);
      $arrRoundInfo['round_end'] = date("Y-m-d H:i:s", $tmRoundEnd);
      
      //회차 시작시간
      $tmRoundStart = strtotime("-3 minutes", $tmRoundEnd);
      $arrRoundInfo['round_start'] = date("Y-m-d H:i:s", $tmRoundStart);
      
      $tmBetEnd = 0;
      //베팅 마감시간
      if($objConf->game_bet_permit != PERMIT_OK){
        $tmBetEnd = $tmRoundStart;
      } else if($objConf->game_time_countdown >= 20 && $objConf->game_time_countdown <= 150 ) {
        //$objConf->game_time_countdown += 5;
        $tmBetEnd = strtotime("-".$objConf->game_time_countdown." seconds", $tmRoundEnd);      
      } else $tmBetEnd = strtotime("-1 minutes", $tmRoundEnd); 

      $arrRoundInfo['round_bet_end'] = date("Y-m-d H:i:s", $tmBetEnd);
      
      return $arrRoundInfo;
    }


    
    function calcRoundId($objLastRound, &$arrRoundData) {
      $iResult = 0;   //
      if($objLastRound->round_date == $arrRoundData['round_date']){
        $arrRoundData['round_id'] = $objLastRound->round_fid + $arrRoundData['round_no'] - $objLastRound->round_num;
        $iResult = 1;
        
      } else if($objLastRound->round_date < $arrRoundData['round_date']){
        
        $date1 = date_create($objLastRound->round_date);
        $date2 = date_create($arrRoundData['round_date']);
        
        $dtDiff = date_diff($date1, $date2);
        $nRoundDiff = $dtDiff->days*288 + $arrRoundData['round_no'] - $objLastRound->round_num;
        $arrRoundData['round_id'] = $objLastRound->round_fid + $nRoundDiff;
        if($nRoundDiff > 0 && $nRoundDiff < 300)
          $iResult = 1;
        
      } else {
        $arrRoundData['round_id'] = $objLastRound->round_fid + 1;
      }
      return $iResult;
    }

    
    function InvalidGameTime(){

      $tmNow = time()+ADVANCE_SEC;
      
      $nHour = date("G",$tmNow);
      $nMin = date("i",$tmNow);

      $nMinSum = $nHour * 60 + $nMin;
      if($nMinSum <= 365)
        return true;
      return false;

    }

    function getMemberState($objMember, $iGame){
      if(is_null($objMember))
            return false;
        else if($objMember->mb_state_active != STATE_ACTIVE)
            return false;
        else if($iGame > 0 && getStateByGame($objMember, $iGame) != PERMIT_OK) {
            return false;  
        }
        return true;
    }

    function getStateByGame($objMember, $iGame){

      switch($iGame){
          case GAME_PBG_BALL: return $objMember->mb_game_pb;
          case GAME_DHP_BALL: return $objMember->mb_game_ps;
          case GAME_SPKN_BALL: return $objMember->mb_game_ks;
          case GAME_CASINO_EVOL:
          case GAME_CASINO_KGON:
          case GAME_CASINO_STAR: 
          case GAME_CASINO_RAVE: 
          case GAME_CASINO_TREEM: 
          case GAME_CASINO_SIGMA: return $objMember->mb_game_cs;
          case GAME_BOGLE_BALL: return $objMember->mb_game_bb;
          case GAME_BOGLE_LADDER: return $objMember->mb_game_bs;
          case GAME_SLOT_THEPLUS: 
          case GAME_SLOT_GSPLAY: 
          case GAME_SLOT_GOLD:     
          case GAME_SLOT_KGON:     
          case GAME_SLOT_STAR: 
          case GAME_SLOT_RAVE: 
          case GAME_SLOT_TREEM: 
          case GAME_SLOT_SIGMA: return $objMember->mb_game_sl;
          case GAME_HOLD_CMS: return $objMember->mb_game_hl;
          case GAME_EOS5_BALL: 
          case GAME_EOS3_BALL:  return $objMember->mb_game_eo;
          case GAME_COIN5_BALL: 
          case GAME_COIN3_BALL:  return $objMember->mb_game_co;
          default: break;
      } 
      return 0;
    }

    function isEnableBet(&$arrBetData, $objUser, $objConf, $arrRoundData){

      //0:오류 1:정상 2:유저베팅차단 3:전체베팅차단 4:최소금액오류 5:최대금액오류 6:보유머니 부족 7:적중최고금액 초과 
      if(is_null( $objConf)) return 0;
      if(is_null( $objUser)) return 0;

      if (!array_key_exists("roundno", $arrBetData))
        return 0;
      
      if($arrRoundData['round_no'] != $arrBetData['roundno']) return 0;

      //게임 베팅가능성
      if($objConf->game_bet_permit == STATE_DISABLE) return 3;

      //유저 베팅 가능성
      if($objUser->mb_state_active != STATE_ACTIVE || getStateByGame($objUser, $arrBetData['game']) != STATE_ACTIVE) 
        return 2;
      //상부회원 상태정보
      if($objUser->emp_state_active != STATE_ACTIVE) return 2;

      //베팅요청정보 체크
      if($arrBetData['roundid'] < 1 ) return 0;
      if($arrBetData['mode'] < 1 || $arrBetData['mode'] > 38) return 0;
      if($arrBetData['amount'] < 1) return 0;

      //금액 조건 체크
      $arrBetData['amount'] = (int)($arrBetData['amount']);
      if($arrBetData['amount'] > $objUser->mb_money)  return 6;


      $tmCurrent = date("Y-m-d H:i:s", time()); 

      if($tmCurrent < $arrRoundData['round_start'] || $tmCurrent > $arrRoundData['round_bet_end']){
          return 0;        
      }

      if(strlen($arrBetData['target']) < 1)
        return 0;

      $ratioBet = "";
      $nMode = (int)($arrBetData['mode']);


      if(array_key_exists('bet.nl_deny', $_ENV) && $_ENV['bet.nl_deny']) {
        if($nMode >= 21 && $nMode <= 29)
          return 0;
      }
      if(array_key_exists('bet.np_deny', $_ENV) && $_ENV['bet.np_deny']) {
        if($nMode >= 13 && $nMode <= 20)
          return 0;
      }
      if(array_key_exists('bet.n2p_deny', $_ENV) && $_ENV['bet.n2p_deny']) {
        if($nMode >= 31 && $nMode <= 38)
          return 0;
      }
      if(array_key_exists('bet.pn_deny', $_ENV) && $_ENV['bet.pn_deny']) {
        if($nMode == 30)
          return 0;
      }

      $iType = 0; 
      if($arrBetData['game'] == GAME_PBG_BALL || $arrBetData['game'] == GAME_BOGLE_BALL
        || ( $arrBetData['game'] >= GAME_EOS5_BALL && $arrBetData['game'] <= GAME_COIN3_BALL) 
        || $arrBetData['game'] == GAME_SPKN_BALL || $arrBetData['game'] == GAME_DHP_BALL) {

         switch ($nMode) {
           case 1: $ratioBet = $objConf->game_ratio_1; $iType = 1; break;
           case 2: $ratioBet = $objConf->game_ratio_2; $iType = 1; break;
           case 3: $ratioBet = $objConf->game_ratio_3; $iType = 1; break;
           case 4: $ratioBet = $objConf->game_ratio_4; $iType = 1; break;
           case 5: $ratioBet = $objConf->game_ratio_5; $iType = 2; break;
           case 6: $ratioBet = $objConf->game_ratio_6; $iType = 2; break;
           case 7: $ratioBet = $objConf->game_ratio_7; $iType = 2; break;
           case 8: $ratioBet = $objConf->game_ratio_8; $iType = 2; break;
           case 9: $ratioBet = $objConf->game_ratio_9; $iType = 3; break;
           case 10: $ratioBet = $objConf->game_ratio_10; $iType = 3; break;
           case 11: $ratioBet = $objConf->game_ratio_11; $iType = 3; break;
           case 12: $ratioBet = $objConf->game_ratio_12; $iType = 3; break;
           case 13: $ratioBet = $objConf->game_ratio_13; $iType = 4; break;
           case 14: $ratioBet = $objConf->game_ratio_14; $iType = 4; break;
           case 15: $ratioBet = $objConf->game_ratio_15; $iType = 4; break;
           case 16: $ratioBet = $objConf->game_ratio_16; $iType = 4; break;
           case 17: $ratioBet = $objConf->game_ratio_17; $iType = 4; break;
           case 18: $ratioBet = $objConf->game_ratio_18; $iType = 4; break;
           case 19: $ratioBet = $objConf->game_ratio_19; $iType = 4; break;
           case 20: $ratioBet = $objConf->game_ratio_20; $iType = 4; break;
           case 21: $ratioBet = $objConf->game_ratio_21; $iType = 5; break;
           case 22: $ratioBet = $objConf->game_ratio_22; $iType = 5; break;
           case 23: $ratioBet = $objConf->game_ratio_23; $iType = 5; break;
           case 24: $ratioBet = $objConf->game_ratio_24; $iType = 5; break;
           case 25: $ratioBet = $objConf->game_ratio_25; $iType = 5; break;
           case 26: $ratioBet = $objConf->game_ratio_26; $iType = 5; break;
           case 27: $ratioBet = $objConf->game_ratio_27; $iType = 5;  break;
           case 28: $ratioBet = $objConf->game_ratio_28; $iType = 5;  break;
           case 29: $ratioBet = $objConf->game_ratio_29; $iType = 5;  break;
           case 30: $ratioBet = $objConf->game_ratio_30; $iType = 7;  break;
           case 31:
           case 32:
           case 33:
           case 34:
           case 35:
           case 36:
           case 37:
           case 38: $ratioBet = $objConf->game_ratio_31; $iType = 6; break;
           default: break;
         }
      } else {
        switch ($nMode) {
          case 1: $ratioBet = $objConf->game_ratio_1; $iType = 1; break;
          case 2: $ratioBet = $objConf->game_ratio_2; $iType = 1; break;
          case 3: $ratioBet = $objConf->game_ratio_3; $iType = 1; break;
          case 4: $ratioBet = $objConf->game_ratio_4; $iType = 2; break;
          case 5: $ratioBet = $objConf->game_ratio_5; $iType = 2; break;
          case 6: $ratioBet = $objConf->game_ratio_6; $iType = 2; break;
          case 7: $ratioBet = $objConf->game_ratio_7; $iType = 2; break;
          default: break;
        }

      }

      if(strlen($ratioBet) < 1)   return 0;

      $arrBetData['ratio'] = floatval($ratioBet);
      $arrLimit = getLimitAmount($objConf, $iType);
      if($arrLimit[0] > 0 && $arrBetData['amount'] < $arrLimit[0])  return 4;   //최소베팅금액 미달
      if($arrLimit[1] > 0 && $arrBetData['amount'] > $arrLimit[1])  return 5;   //최대베팅금액 초과
      if($arrLimit[2] > 0 && $arrBetData['amount'] * $arrBetData['ratio'] > $arrLimit[2]) return 7; //최대적중금액 초과

      return 1;
    }

    function isEnableApiBet(&$arrBetData, $objUser, $objConf, $arrRoundData){

      //0:오류 1:정상 3:베팅차단 4:최소금액오류 5:최대금액오류 6:보유머니 부족 7:적중최고금액 초과 
      if(is_null( $objConf)) return 0;
      if(is_null( $objUser)) return 0;

      //게임 베팅가능성
      if($objConf->game_bet_permit == STATE_DISABLE) return 3;

      //유저 베팅 가능성
      if($objUser->mb_state_active != STATE_ACTIVE || getStateByGame($objUser, $arrBetData['game']) != STATE_ACTIVE) 
        return 3;
      //상부회원 상태정보
      if($objUser->emp_state_active != STATE_ACTIVE) return 3;

      if($arrBetData['amount'] < 1) return 0;

      //금액 조건 체크
      $arrBetData['amount'] = (int)($arrBetData['amount']);
      if($arrBetData['amount'] > $objUser->mb_money)  return 6;

      if(strlen($arrBetData['target']) < 1)
        return 0;


      $ratioBet = "";
      $nMode = (int)($arrBetData['mode']);
      
      if(array_key_exists('bet.nl_deny', $_ENV) && $_ENV['bet.nl_deny']) {
        if($nMode >= 21 && $nMode <= 29)
          return 0;
      }
      if(array_key_exists('bet.np_deny', $_ENV) && $_ENV['bet.np_deny']) {
        if($nMode >= 13 && $nMode <= 20)
          return 0;
      }
      if(array_key_exists('bet.n2p_deny', $_ENV) && $_ENV['bet.n2p_deny']) {
        if($nMode >= 31 && $nMode <= 38)
          return 0;
      }
      if(array_key_exists('bet.pn_deny', $_ENV) && $_ENV['bet.pn_deny']) {
        if($nMode == 30)
          return 0;
      }
      
      $iType = 0; 
      if($arrBetData['game'] == GAME_PBG_BALL || $arrBetData['game'] == GAME_BOGLE_BALL
      || ( $arrBetData['game'] >= GAME_EOS5_BALL && $arrBetData['game'] <= GAME_COIN3_BALL) 
      || $arrBetData['game'] >= GAME_SPKN_BALL) {

         switch ($nMode) {
           case 1: $ratioBet = $objConf->game_ratio_1; $iType = 1; break;
           case 2: $ratioBet = $objConf->game_ratio_2; $iType = 1; break;
           case 3: $ratioBet = $objConf->game_ratio_3; $iType = 1; break;
           case 4: $ratioBet = $objConf->game_ratio_4; $iType = 1; break;
           case 5: $ratioBet = $objConf->game_ratio_5; $iType = 2; break;
           case 6: $ratioBet = $objConf->game_ratio_6; $iType = 2; break;
           case 7: $ratioBet = $objConf->game_ratio_7; $iType = 2; break;
           case 8: $ratioBet = $objConf->game_ratio_8; $iType = 2; break;
           case 9: $ratioBet = $objConf->game_ratio_9; $iType = 3; break;
           case 10: $ratioBet = $objConf->game_ratio_10; $iType = 3; break;
           case 11: $ratioBet = $objConf->game_ratio_11; $iType = 3; break;
           case 12: $ratioBet = $objConf->game_ratio_12; $iType = 3; break;
           case 13: $ratioBet = $objConf->game_ratio_13; $iType = 4; break;
           case 14: $ratioBet = $objConf->game_ratio_14; $iType = 4; break;
           case 15: $ratioBet = $objConf->game_ratio_15; $iType = 4; break;
           case 16: $ratioBet = $objConf->game_ratio_16; $iType = 4; break;
           case 17: $ratioBet = $objConf->game_ratio_17; $iType = 4; break;
           case 18: $ratioBet = $objConf->game_ratio_18; $iType = 4; break;
           case 19: $ratioBet = $objConf->game_ratio_19; $iType = 4; break;
           case 20: $ratioBet = $objConf->game_ratio_20; $iType = 4; break;
           case 21: $ratioBet = $objConf->game_ratio_21; $iType = 5; break;
           case 22: $ratioBet = $objConf->game_ratio_22; $iType = 5; break;
           case 23: $ratioBet = $objConf->game_ratio_23; $iType = 5; break;
           case 24: $ratioBet = $objConf->game_ratio_24; $iType = 5; break;
           case 25: $ratioBet = $objConf->game_ratio_25; $iType = 5; break;
           case 26: $ratioBet = $objConf->game_ratio_26; $iType = 5; break;
           case 27: $ratioBet = $objConf->game_ratio_27; $iType = 5;  break;
           case 28: $ratioBet = $objConf->game_ratio_28; $iType = 5;  break;
           case 29: $ratioBet = $objConf->game_ratio_29; $iType = 5;  break;
           case 30: $ratioBet = $objConf->game_ratio_30; $iType = 7;  break;
           case 31:
           case 32:
           case 33:
           case 34:
           case 35:
           case 36:
           case 37:
           case 38: $ratioBet = $objConf->game_ratio_31; $iType = 6; break;
           default: break;
         }
      } else {
        switch ($nMode) {
          case 1: $ratioBet = $objConf->game_ratio_1; $iType = 1; break;
          case 2: $ratioBet = $objConf->game_ratio_2; $iType = 1; break;
          case 3: $ratioBet = $objConf->game_ratio_3; $iType = 1; break;
          case 4: $ratioBet = $objConf->game_ratio_4; $iType = 2; break;
          case 5: $ratioBet = $objConf->game_ratio_5; $iType = 2; break;
          case 6: $ratioBet = $objConf->game_ratio_6; $iType = 2; break;
          case 7: $ratioBet = $objConf->game_ratio_7; $iType = 2; break;
          default: break;
        }

      }

      if(strlen($ratioBet) < 1)   return 0;

      $arrBetData['ratio'] = floatval($ratioBet);
      $arrLimit = getLimitAmount($objConf, $iType);
      if($arrLimit[0] > 0 && $arrBetData['amount'] < $arrLimit[0])  return 4;   //최소베팅금액 미달
      if($arrLimit[1] > 0 && $arrBetData['amount'] > $arrLimit[1])  return 5;   //최대베팅금액 초과
      if($arrLimit[2] > 0 && $arrBetData['amount'] * $arrBetData['ratio'] > $arrLimit[2]) return 7; //최대적중금액 초과

      return 1;
    }
    
    function getLimitAmount($objConf, $iType){
      $minBet = 0;
      $maxBet = 0;
      $maxWin = 0;
  
      switch($iType){
          case 1: 
              $minBet = intval($objConf->game_min_bet_money);
              $maxBet = intval($objConf->game_max_bet_money);
              $maxWin = intval($objConf->game_max_win_money);
              break;
          case 2: 
              $minBet = intval($objConf->game_min2_bet_money);
              $maxBet = intval($objConf->game_max2_bet_money);
              $maxWin = intval($objConf->game_max2_win_money);
              break;
          case 3: 
              $minBet = intval($objConf->game_min3_bet_money);
              $maxBet = intval($objConf->game_max3_bet_money);
              $maxWin = intval($objConf->game_max3_win_money);
              break;
          case 4: 
              $minBet = intval($objConf->game_min4_bet_money);
              $maxBet = intval($objConf->game_max4_bet_money);
              $maxWin = intval($objConf->game_max4_win_money);
              break;
          case 5: 
              $minBet = intval($objConf->game_min5_bet_money);
              $maxBet = intval($objConf->game_max5_bet_money);
              $maxWin = intval($objConf->game_max5_win_money);
              break;
          case 6: 
              $minBet = intval($objConf->game_min6_bet_money);
              $maxBet = intval($objConf->game_max6_bet_money);
              $maxWin = intval($objConf->game_max6_win_money);
              break;
          case 7: 
              $minBet = intval($objConf->game_min7_bet_money);
              $maxBet = intval($objConf->game_max7_bet_money);
              $maxWin = intval($objConf->game_max7_win_money);
              break;
          default : 
              $minBet = intval($objConf->game_min_bet_money);
              $maxBet = intval($objConf->game_max_bet_money);
              $maxWin = intval($objConf->game_max_win_money);
              break;
      }
      return [$minBet, $maxBet, $maxWin];
    }

    function isEnableBetTime($arrRoundData){

      $tmCurrent = date("Y-m-d H:i:s", time()); 

      if($tmCurrent < $arrRoundData['round_start'] || $tmCurrent > $arrRoundData['round_bet_end']){
          return false;        
      }
      return true;
    }

    function getRatioByGame($objMember, $iGame, $iMode = 0){
      $fRatio = 0;
      switch($iGame){
          case GAME_PBG_BALL: 
          case GAME_SPKN_BALL: 
          case GAME_DHP_BALL: 
          case GAME_BOGLE_BALL: 
          case GAME_EOS5_BALL:
          case GAME_EOS3_BALL: 
          case GAME_COIN5_BALL:
          case GAME_COIN3_BALL: 
                $fRatio = $iMode<5 ? $objMember->mb_game_pb_ratio : $objMember->mb_game_pb2_ratio;
                break;
          case GAME_BOGLE_LADDER: 
              $fRatio = $objMember->mb_game_pb_ratio;
              break;
          case GAME_CASINO_EVOL: 
          case GAME_CASINO_KGON: 
          case GAME_CASINO_STAR: 
                $fRatio = $objMember->mb_game_cs_ratio;
                break;
          case GAME_SLOT_THEPLUS:
          case GAME_SLOT_GSPLAY: 
          case GAME_SLOT_GOLD: 
          case GAME_SLOT_KGON: 
          case GAME_SLOT_STAR: 
                $fRatio = $objMember->mb_game_sl_ratio;
                break;
          case GAME_HOLD_CMS: 
                  $fRatio = $objMember->mb_game_hl_ratio;
                  break;
          default: break;
      } 
      $fRatio = floatval($fRatio);
      
      if($fRatio <= 0)
        $fRatio = 0;
      return $fRatio;
    } 

    function isEnableN2pBet($arrStatis, $mode){
      
      if(is_null($arrStatis))
        return true;
      if(count($arrStatis) < N2P_MAX_HOLE)
        return true;

      if(count($arrStatis) > N2P_MAX_HOLE)
        return false;

      $bEnable = false;
      foreach($arrStatis as $statis){
        if($statis->bet_mode == $mode){
          $bEnable = true;
          break; 
        }
      }
      return $bEnable ;
    }

    function findFollowRate($game, $arrFollow, $mb_fid){
      $rate = 0;
      foreach($arrFollow as $follow){
        if($follow->fl_mb_fid == $mb_fid){
          switch($game){
            case GAME_PBG_BALL: 
              $rate = $follow->fl_pb_rate;
              break;
            case GAME_SPKN_BALL: 
              $rate = $follow->fl_sk_rate;
              break;
            case GAME_DHP_BALL: 
              $rate = $follow->fl_ev_rate;
              break;
            case GAME_BOGLE_BALL: 
              $rate = $follow->fl_bb_rate;
              break;
            case GAME_BOGLE_LADDER: 
              $rate = $follow->fl_bs_rate;
              break;
            case GAME_EOS5_BALL: 
              $rate = $follow->fl_e5_rate;
              break;
            case GAME_EOS3_BALL: 
              $rate = $follow->fl_e3_rate;
              break;
            case GAME_COIN5_BALL: 
              $rate = $follow->fl_c5_rate;
              break;
            case GAME_COIN3_BALL: 
              $rate = $follow->fl_c3_rate;
              break;
            default: break;
          }
          break; 
        }
      }
      return $rate/100.0 ;
    }

    function checkApiUri($arrReqData){

      if(strlen($arrReqData['game'])<1) return false;
      else if(strlen($arrReqData['id'])<1) return  false;
      else if(strlen($arrReqData['pwd'])<1) return  false;
      else if(strlen($arrReqData['balance'])<1) return  false;

      return true;
      
    }

    function checkApiBalance($sBalance){
      $arrAmount = [];
      $arrBalance = explode('|', $sBalance);
      foreach($arrBalance as $amount){
        if(!is_numeric($amount))
          break;

        $arrAmount[] = $amount;
      }

      return $arrAmount;

    }

    function fslotErrorMsg($arrResp){
      $msg = "";
      if(!array_key_exists('error', $arrResp))
        return "";

      if($arrResp['error'] == INVALID_ACCESS_TOKEN){
        $msg = "토큰정보가 유효하지 않습니다. 관리자에게 문의해주세요";
      } else if($arrResp['error'] == INVALID_PRODUCT){
        $msg = "게임정보가 존재하지 않습니다. 관리자에게 문의해주세요";
      } else if($arrResp['error'] == INVALID_PARAMETER){
        $msg = "요청이 잘못되었습니다. 관리자에게 문의해주세요 ";
      } else if($arrResp['error'] == INVALID_USER){
        $msg = "존재하지 않는 사용자입니다.";
      } else if($arrResp['error'] == DOUBLE_USER){
        $msg = "중복된 사용자입니다.";
      } else if($arrResp['error'] == INSUFFICIENT_FUNDS){
        $msg = "전체 보유알이 부족합니다. 관리자에게 문의해주세요";
      } else if($arrResp['error'] == INVALID_AMOUNT){
        $msg = "금액정보가 올바르지 않습니다.";
      } else if($arrResp['error'] == GAME_PLAYING){
        $msg = "게임진행중이므로 조작이 실패되었습니다.";
      } else{
        $msg = "조작이 실패되었습니다.";

      }
      return $msg;

    }

    function allMoney($member){
      $nMoney = 0;
      if(is_null($member))
        return $nMoney;

      $nMoney = floatval($member->mb_money) + $member->mb_live_money + $member->mb_slot_money + $member->mb_fslot_money +
        $member->mb_kgon_money + $member->mb_gslot_money+ $member->mb_hslot_money + $member->mb_hold_money + 
        $member->mb_rave_money + $member->mb_treem_money + $member->mb_sigma_money;
      return floor($nMoney); //round($nMoney, NUM_POINT_CNT);
    }

    function allEgg($member){
      $nMoney = 0;
      if(is_null($member))
        return $nMoney;

      $nMoney = floatval($member->mb_live_money) + $member->mb_slot_money + $member->mb_fslot_money +
        $member->mb_kgon_money + $member->mb_gslot_money + $member->mb_hslot_money + $member->mb_hold_money + 
        $member->mb_rave_money + $member->mb_treem_money + $member->mb_sigma_money;
      return floor($nMoney); //round($nMoney, NUM_POINT_CNT);
    }

    function createGameId($str){
      $createId = $str;
      if(array_key_exists("app.testV", $_ENV) && $_ENV['app.testV'] == 1){
        $createId = "T".$str;
      } 
      return $createId;
    }

    function diffDt($dt1, $dt2){
      return abs( strtotime($dt1) - strtotime($dt2) );
    }

    function validLoginValue($userId, $userPw){
      $checkOk = preg_match("/^[A-Za-z0-9_]+$/", $userId);
      if($checkOk)
        $checkOk = !preg_match("/^\'+\s*or+.*$/i", $userPw); //i-Ignore Case 
      return $checkOk;
    }

    function validUserId($userId){
      return preg_match("/^[A-Za-z0-9_]{4,16}$/", $userId);
    }

    function validUserPw($userPw){
      $checkOk = true;
			$pwdLen = strlen($userPw);
      if($pwdLen < 8 || $pwdLen > 20 )
				$checkOk = false;

      if($checkOk)
        $checkOk = preg_match("/^[A-Za-z0-9\{\}\[\]\/?.,;:|\)\*~`!\^\-_\+<>@\#\$%&\\\=\(\'\"]{8,20}/", $userPw); //^[a-zA-Z\\d`~!@#$%^&*()-_=+]{8,20}$
      
      if($checkOk)
        $checkOk = preg_match("/[\{\}\[\]\/?.,;:|\)\*~`!\^\-_\+<>@\#\$%&\\\=\(\'\"]+/", $userPw); //^[a-zA-Z\\d`~!@#$%^&*()-_=+]{8,20}$
      
      return $checkOk;
    }

    function isValidIp($strIps, $logIp){
      $arrIp = explode(";", $strIps);
      foreach($arrIp as $ip){
        if(trim($ip) === $logIp){
          return true;
        }
      }
      return false;
    }

    function isEmptyNotice($content){
      
      $content = trim($content);
      if(strlen($content) < 1)
        return true;
      
      return  preg_match("/^(<\w+>)?<br>(<\/\w+>)?$/i", $content);
    }

    function langTo($locale, $msgType, $param){
      $result = "";
      switch($msgType){
        case "game_delay":
          if($locale == "cn")
            $result = $param."秒后请再试一次";
          else if($locale == "en")
            $result = "Please try again in ".$param." seconds.";
          else
            $result = $param."초후 다시 시도해주세요";
          break;
        case "withdrawal_delay":
          if($locale == "cn")
            $result = "取款间隔至少为".$param."个小时.";
          else if($locale == "en")
            $result = "The withdrawal interval is at least ".$param." hours.";
          else
            $result = "출금간격은 최소 ".$param."시간입니다.";
          break;
        case "withdrawal_deny":
          if($locale == "cn")
            $result = "玩游戏时不能申请取款. (等待时间:".$param."分钟)";
          else if($locale == "en")
            $result = "You can't request for withdrawal during the game. (Waiting Time:".$param." minutes)";
          else
            $result = "게임플레이중에는 출금신청을 하실수 없습니다. (대기 시간:".$param."분)";
          break;
          
          break;
        default: break;
      }
      return $result;
    }

    function getExchangeList($arrMember, $count){

      $result = []; 
      $memCnt = count($arrMember);
      if($memCnt < $count)
        return $result;

      $tmNow = time();
      $delay = 0;
      for($i=0; $i<$count; $i++){
        $memCnt = count($arrMember);
        if($memCnt < 1)
          break;
        else if($memCnt > 1)
          $idx = rand(0, $memCnt-1);
        else $idx = 0;
        $member = $arrMember[$idx];
        if(strlen($member->mb_uid) < 3){
          $count ++;
          continue;
        }
        $delay += rand(120, 10800);
        $obj = new \stdClass();
        $obj->uid = substr($member->mb_uid,0,3)."***";
        $obj->amount =  number_format(rand(1, 200)*10000);
        $obj->time = date("Y-m-d H:i:s", $tmNow-$delay); 
        array_push($result, $obj);
        array_splice($arrMember, $idx, 1);
      }
      return $result;

    }
    
    function getFiles($dir, $ext, &$arrInfo)
    {
      if (substr($dir, strlen($dir)-1, 1) != DIRECTORY_SEPARATOR)
          $dir .= DIRECTORY_SEPARATOR;

      if(!file_exists($dir)){
        return;
      }


      if ($handle = opendir($dir))
      {
        writeLog("captchaSrc=".$dir);

          while ($obj = readdir($handle))
          {
              if ($obj != '.' && $obj != '..')
              {
                  if (is_file($dir.$obj) && strlen($obj) > 4)
                  {
                    if( strtoLower(substr($obj, strlen($obj)-4, 4)) === ".".$ext)
                      array_push($arrInfo, substr($obj, 0, strlen($obj)-4));
                  }                  
              }
          }

          closedir($handle);
      }
    
    }
    
    function getAllFiles($dir, $ext, &$arrInfo)
    {
      if (substr($dir, strlen($dir)-1, 1) != DIRECTORY_SEPARATOR)
          $dir .= DIRECTORY_SEPARATOR;

      if(!file_exists($dir)){
        return;
      }

      if ($handle = opendir($dir))
      {

          while ($obj = readdir($handle))
          {
              if ($obj != '.' && $obj != '..')
              {
                  if (is_file($dir.$obj) && strlen($obj) > 4)
                  {
                    if( strtoLower(substr($obj, strlen($obj)-4, 4)) === ".".$ext){
                      $file = new \StdClass;
                      $file->path = $dir.$obj;
                      $file->name = $obj;
                      array_push($arrInfo, $file);

                    }
                  } else if(is_dir($dir.$obj)){
                      getAllFiles($dir.$obj, $ext, $arrInfo);
                  }                  
              }
          }

          closedir($handle);
      }
    
    }

    function generateMembers($arrMember, $count){
        $seed = microtime(true);
        for($i=0; $i<$count; $i++){
          $member = new \stdClass();
          $member->mb_uid = generateString(10, $seed+=11111);
          array_push($arrMember, $member);
        }
        return $arrMember;
    }
    
    function generateString($length, $seed)  
    {  
        // $characters  = "0123456789";  
        $characters = "abcdefghijklmnopqrstuvwxyz";  
        // $characters .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";  
        
        $randStr = "";  
        $nmr_loops = $length;  
        
        while ($nmr_loops--)  
        {  
            mt_srand($seed++);
            $randStr .= $characters[mt_rand(0, strlen($characters) - 1)];  
        }  
        
        return $randStr;  
    }  

    function generateExtId(){
      $seed = microtime(true); //1709876543.123456 - 16 digits
      
      $randStr = generateString(48, $seed);
      $secs = intval($seed);
      return $randStr.intval($seed).intval(($seed-$secs)*1000000);
    }

    /**
     * public/images/class/*.gif 중 파일명이 M숫자 또는 F숫자인 것만 모음 (선배님 등급 아이콘).
     */
    function member_class_gif_pool(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = [];
        if (defined('FCPATH')) {
            $dir = FCPATH . 'images' . DIRECTORY_SEPARATOR . 'class';
            if (is_dir($dir)) {
                $files = glob($dir . DIRECTORY_SEPARATOR . '*.gif');
                if (is_array($files)) {
                    foreach ($files as $path) {
                        $base = basename($path, '.gif');
                        if (preg_match('/^([MF])(\d+)$/i', $base, $m)) {
                            $cache[] = strtoupper($m[1]) . (int) $m[2];
                        }
                    }
                }
            }
        }
        $cache = array_values(array_unique($cache));
        sort($cache, SORT_NATURAL);
        if ($cache === []) {
            $cache = ['M1'];
        }
        return $cache;
    }

    /**
     * 닉 옆 /images/class/Mxx.gif 용 식별자.
     * mb_color 가 M15·F20 형태이고 해당 파일이 풀에 있으면 그대로, 아니면 mb_fid 로 풀에서 고정(매번 랜덤이 아님).
     */
    function member_class_gif_id_for_display($mbColor, int $mbFid): string
    {
        $pool = member_class_gif_pool();
        $raw = trim((string) $mbColor);
        $rawNoHash = ltrim($raw, '#');
        if (preg_match('/^([MF])(\d{1,3})$/i', $rawNoHash, $m)) {
            $id = strtoupper($m[1]) . (int) $m[2];
            if (in_array($id, $pool, true)) {
                return $id;
            }
        }
        $n = count($pool);
        $idx = $mbFid > 0 ? (abs((int) crc32((string) $mbFid)) % $n) : 0;

        return $pool[$idx];
    }

    if (! function_exists('ci_app_debug')) {
        /**
         * CI_ENVIRONMENT 가 development 일 때만 true (프론트 JS 디버그 로그용).
         */
        function ci_app_debug(): bool
        {
            if (defined('ENV_DEVELOPMENT')) {
                return (string) ($_ENV['CI_ENVIRONMENT'] ?? '') === ENV_DEVELOPMENT;
            }

            return (string) ($_ENV['CI_ENVIRONMENT'] ?? '') === 'development';
        }
    }
?>
