<?php

//--------------------------------------------------------------------
// App Namespace
//--------------------------------------------------------------------
// This defines the default Namespace that is used throughout
// CodeIgniter to refer to the Application directory. Change
// this constant to change the namespace that all application
// classes should use.
//
// NOTE: changing this will require manually modifying the
// existing namespaces of App\* namespaced-classes.
//
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
|--------------------------------------------------------------------------
| Composer Path
|--------------------------------------------------------------------------
|
| The path that Composer's autoload file is expected to live. By default,
| the vendor folder is in the Root directory, but you can customize that here.
*/
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
|--------------------------------------------------------------------------
| Timing Constants
|--------------------------------------------------------------------------
|
| Provide simple ways to work with the myriad of PHP functions that
| require information to be in seconds.
*/
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2592000);
defined('YEAR')   || define('YEAR', 31536000);
defined('DECADE') || define('DECADE', 315360000);

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('ENV_PRODUCTION')   || define('ENV_PRODUCTION', 'production');
defined('ENV_DEVELOPMENT')  || define('ENV_DEVELOPMENT', 'development');

defined('PUBLICPATH')           || define('PUBLICPATH', ROOTPATH."public".DIRECTORY_SEPARATOR);

defined('APP_PHANTOM')       || define('APP_PHANTOM', 'Phantom');
defined('APP_BOLTON')        || define('APP_BOLTON', 'Bolton');
defined('APP_HERMES')        || define('APP_HERMES', 'Hermes');
defined('APP_ATM')           || define('APP_ATM', 'ATM');
defined('APP_FUN')           || define('APP_FUN', 'FUN');
defined('APP_DUNK')          || define('APP_DUNK', 'DUNK');
defined('APP_DOLPHIN')          || define('APP_DOLPHIN', 'Dolphin');

defined('APP_TYPE_1')        || define('APP_TYPE_1', 1);      //Slot + FSlot
defined('APP_TYPE_2')        || define('APP_TYPE_2', 2);      //FSlot
defined('APP_TYPE_3')        || define('APP_TYPE_3', 3);      //Slot
//Genuine
defined('APP_SLOT_THEPLUS')  || define('APP_SLOT_THEPLUS', 1); 
defined('APP_SLOT_KGON')     || define('APP_SLOT_KGON', 2); 
defined('APP_SLOT_STAR')     || define('APP_SLOT_STAR', 3); 
defined('APP_SLOT_RAVE')     || define('APP_SLOT_RAVE', 4); 
defined('APP_SLOT_TREEM')     || define('APP_SLOT_TREEM', 5); 
defined('APP_SLOT_SIGMA')     || define('APP_SLOT_SIGMA', 6); 

//Natural 
defined('APP_FSLOT_GSPLAY')  || define('APP_FSLOT_GSPLAY', 1); 
defined('APP_FSLOT_GOLD')    || define('APP_FSLOT_GOLD', 2); 

//Casino
defined('APP_CASINO_KGON')      || define('APP_CASINO_KGON', 2); 
defined('APP_CASINO_STAR')      || define('APP_CASINO_STAR', 3); 
defined('APP_CASINO_RAVE')      || define('APP_CASINO_RAVE', 4); 
defined('APP_CASINO_TREEM')     || define('APP_CASINO_TREEM', 5); 
defined('APP_CASINO_SIGMA')     || define('APP_CASINO_SIGMA', 6); 


defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code


$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST']."".str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
defined('BASEURL') || define('BASEURL', $base_url);

defined('LOG_FILE')             || define('LOG_FILE', ROOTPATH."logs".DIRECTORY_SEPARATOR);
defined('LOG_WRITE')            || define('LOG_WRITE', true);

//config site index
defined('CONF_SITENAME')       || define('CONF_SITENAME', 1);
defined('CONF_DOMAIN')         || define('CONF_DOMAIN', 2);
defined('CONF_USERPAGE')       || define('CONF_USERPAGE', 3);
defined('CONF_ADMINPAGE')      || define('CONF_ADMINPAGE', 4);
defined('CONF_NOTICE_MAIN')    || define('CONF_NOTICE_MAIN', 5);
defined('CONF_NOTICE_BANK')    || define('CONF_NOTICE_BANK', 6);
defined('CONF_CHARGEINFO')     || define('CONF_CHARGEINFO', 8);
defined('CONF_CHARGEMACRO')    || define('CONF_CHARGEMACRO', 9);
defined('CONF_MAINTAIN')       || define('CONF_MAINTAIN', 10);
defined('CONF_NOTICE_URGENT')  || define('CONF_NOTICE_URGENT', 12);
defined('CONF_GAMEPER_FULL')   || define('CONF_GAMEPER_FULL', 13);
defined('CONF_MULTI_LOGIN')    || define('CONF_MULTI_LOGIN', 14);
defined('CONF_SOUND_1')        || define('CONF_SOUND_1', 15);
defined('CONF_SOUND_2')        || define('CONF_SOUND_2', 16);
defined('CONF_SOUND_3')        || define('CONF_SOUND_3', 17);
defined('CONF_SOUND_4')        || define('CONF_SOUND_4', 18);
defined('CONF_API_GOLD')       || define('CONF_API_GOLD', 19);
defined('CONF_API_KGON')       || define('CONF_API_KGON', 20);
defined('CONF_API_HPPLAY')     || define('CONF_API_HPPLAY', 21);
defined('CONF_API_THEPLUS')    || define('CONF_API_THEPLUS', 22);
defined('CONF_API_GSPLAY')     || define('CONF_API_GSPLAY', 23);
defined('CONF_API_STAR')       || define('CONF_API_STAR', 30);
defined('CONF_API_HOLD')       || define('CONF_API_HOLD', 68);
defined('CONF_API_RAVE')       || define('CONF_API_RAVE', 89);
defined('CONF_API_TREEM')      || define('CONF_API_TREEM', 112);
defined('CONF_API_SIGMA')      || define('CONF_API_SIGMA', 113);

defined('CONF_CHARGE_MANUAL')  || define('CONF_CHARGE_MANUAL', 24);
defined('CONF_DISCHA_MANUAL')  || define('CONF_DISCHA_MANUAL', 25);
defined('CONF_API_VACC')        || define('CONF_API_VACC', 26);     //virtual account api
defined('CONF_BPG_DENY')        || define('CONF_BPG_DENY', 27);
defined('CONF_EVOL_DENY')       || define('CONF_EVOL_DENY', 28);
defined('CONF_SLOT_DENY')       || define('CONF_SLOT_DENY', 29);
defined('CONF_CAS_DENY')       || define('CONF_CAS_DENY', 31);
defined('CONF_EOS5_DENY')       || define('CONF_EOS5_DENY', 32);
defined('CONF_EOS3_DENY')       || define('CONF_EOS3_DENY', 33);
defined('CONF_PBG_DENY')        || define('CONF_PBG_DENY', 34);
defined('CONF_COIN5_DENY')      || define('CONF_COIN5_DENY', 45);
defined('CONF_COIN3_DENY')      || define('CONF_COIN3_DENY', 46);
defined('CONF_HOLD_DENY')       || define('CONF_HOLD_DENY', 69);
defined('CONF_DHP_DENY')       || define('CONF_DHP_DENY', 74); //DH Powerball
defined('CONF_SPK_DENY')       || define('CONF_SPK_DENY', 75); //Speed Keno


defined('CONF_BET_CANCEL')      || define('CONF_BET_CANCEL', 35);
defined('CONF_BET_NL_DENY')     || define('CONF_BET_NL_DENY', 36);
defined('CONF_BET_NP_DENY')     || define('CONF_BET_NP_DENY', 37);
defined('CONF_BET_N2P_DENY')    || define('CONF_BET_N2P_DENY', 38);
defined('CONF_BET_PN_DENY')     || define('CONF_BET_PN_DENY', 39);
defined('CONF_BET_N2P_4EN')     || define('CONF_BET_N2P_4EN', 40);
defined('CONF_BET_PAN_TYPE')    || define('CONF_BET_PAN_TYPE', 41);
defined('CONF_BET_CONFIRM_DENY')|| define('CONF_BET_CONFIRM_DENY', 42); //betting without cofirmation
defined('CONF_BET_BLANK_EN')    || define('CONF_BET_BLANK_EN', 43); //blank betting
defined('CONF_TRANS_DENY')      || define('CONF_TRANS_DENY', 44);   //disable transfer of money between users
defined('CONF_RETURN_DENY')     || define('CONF_RETURN_DENY', 47);  //disable withdraw of money between users
defined('CONF_TRANS_LV1')       || define('CONF_TRANS_LV1', 48);    //enable transfer of money between only 1-level difference users 
defined('CONF_RETURN_LV1')      || define('CONF_RETURN_LV1', 49);   //enable withdraw of money between only 1-level difference users
defined('CONF_NOTICE_DT')       || define('CONF_NOTICE_DT', 50);
defined('CONF_TRANS_LVS')       || define('CONF_TRANS_LVS', 51);    //level that is enable to transfer and withdraw
defined('CONF_DEPOSIT_PLAY')    || define('CONF_DEPOSIT_PLAY', 52);   //disable deposit during play 
defined('CONF_WITHDRAW_PLAY')   || define('CONF_WITHDRAW_PLAY', 53);   //disable withdraw during play
defined('CONF_MAIN_GAMEIMG')    || define('CONF_MAIN_GAMEIMG', 54);   //game images for main page 
defined('CONF_DELAY_PLAY')      || define('CONF_DELAY_PLAY', 55);   //delay of play
defined('CONF_AUTOAPPS')        || define('CONF_AUTOAPPS', 62);   //auto app images

defined('LEVEL_MAX')           || define('LEVEL_MAX', 100);
defined('LEVEL_ADMIN')         || define('LEVEL_ADMIN', 100);
defined('LEVEL_COMPANY')       || define('LEVEL_COMPANY', 99);
defined('LEVEL_AGENCY')        || define('LEVEL_AGENCY', 98);
defined('LEVEL_EMPLOYEE')      || define('LEVEL_EMPLOYEE', 97);
defined('LEVEL_MIN')           || define('LEVEL_MIN', 1);

defined('GRADE_1')             || define('GRADE_1', 1);

//status
defined('STATE_DISABLE')      || define('STATE_DISABLE', 0);
defined('STATE_ACTIVE')       || define('STATE_ACTIVE', 1);
defined('STATE_VERIFY')       || define('STATE_VERIFY', 2);
defined('STATE_REFUSE')       || define('STATE_REFUSE', 3);
defined('STATE_WAIT')         || define('STATE_WAIT', 4);
defined('STATE_HOT')          || define('STATE_HOT', 5);

//permit state
defined('PERMIT_CANCEL')       || define('PERMIT_CANCEL', 0);
defined('PERMIT_OK')           || define('PERMIT_OK', 1);
defined('PERMIT_REQ')         || define('PERMIT_REQ', 2);
defined('PERMIT_WAIT')         || define('PERMIT_WAIT', 3);
defined('PERMIT_DELETE')       || define('PERMIT_DELETE', 4);

//Json Result Code
defined('RESULT_OK')           || define('RESULT_OK', 1);
defined('RESULT_FAIL')         || define('RESULT_FAIL', 2);
defined('RESULT_STOP')         || define('RESULT_STOP', 3);
defined('RESULT_ERROR')        || define('RESULT_ERROR', 4);
defined('RESULT_EXIST_ID')     || define('RESULT_EXIST_ID', 5);
defined('RESULT_EXIST_NAME')   || define('RESULT_EXIST_NAME', 6);
defined('RESULT_WAIT')         || define('RESULT_WAIT', 7);
defined('RESULT_EMP_ERROR')    || define('RESULT_EMP_ERROR', 8);
defined('RESULT_MAINTAIN')     || define('RESULT_MAINTAIN', 9);
defined('RESULT_CAPTCHA_ERR')  || define('RESULT_CAPTCHA_ERR', 11);

//Json Result Status
defined('STATUS_SUCCESS')      || define('STATUS_SUCCESS', 'success');
defined('STATUS_FAIL')         || define('STATUS_FAIL', 'fail');
defined('STATUS_LOGOUT')       || define('STATUS_LOGOUT', 'logout');


//game delay seconds
defined('ADVANCE_SEC')    	   || define('ADVANCE_SEC', 20);

defined('SESS_TYPE_SITE')       || define('SESS_TYPE_SITE', 0);
defined('SESS_TYPE_EVOL')       || define('SESS_TYPE_EVOL', 1);
defined('SESS_TYPE_PRAG')       || define('SESS_TYPE_PRAG', 2);

defined('CURL_TIMEOUT_MIN')       || define('CURL_TIMEOUT_MIN', 5);
defined('CURL_TIMEOUT_MAX')       || define('CURL_TIMEOUT_MAX', 100);

//game type
defined('GAME_PBG_BALL')         || define('GAME_PBG_BALL', 1);
defined('GAME_DHP_BALL')         || define('GAME_DHP_BALL', 2);
defined('GAME_CASINO_KGON')      || define('GAME_CASINO_KGON', 3);
defined('GAME_CASINO_EVOL')      || define('GAME_CASINO_EVOL', 4);
defined('GAME_BOGLE_BALL')       || define('GAME_BOGLE_BALL', 5);
defined('GAME_BOGLE_LADDER')     || define('GAME_BOGLE_LADDER', 6);
defined('GAME_SLOT_THEPLUS')     || define('GAME_SLOT_THEPLUS', 7);
defined('GAME_SLOT_GSPLAY')      || define('GAME_SLOT_GSPLAY', 8);
defined('GAME_EOS5_BALL')        || define('GAME_EOS5_BALL', 9);
defined('GAME_EOS3_BALL')        || define('GAME_EOS3_BALL', 10);
defined('GAME_COIN5_BALL')       || define('GAME_COIN5_BALL', 11);
defined('GAME_COIN3_BALL')       || define('GAME_COIN3_BALL', 12);
defined('GAME_SLOT_GOLD')        || define('GAME_SLOT_GOLD', 13);  //Gold slot
defined('GAME_SPKN_BALL')        || define('GAME_SPKN_BALL', 14); //Speed Kino
defined('GAME_SLOT_KGON')        || define('GAME_SLOT_KGON', 15);  //KGON slot
defined('GAME_SLOT_STAR')        || define('GAME_SLOT_STAR', 16);  //STAR slot
defined('GAME_CASINO_STAR')      || define('GAME_CASINO_STAR', 17);  //STAR casino
defined('GAME_HOLD_CMS')         || define('GAME_HOLD_CMS', 18);  //HOLDEM
defined('GAME_SLOT_RAVE')        || define('GAME_SLOT_RAVE', 19);  //RAVE slot
defined('GAME_CASINO_RAVE')      || define('GAME_CASINO_RAVE', 20);  //RAVE casino
defined('GAME_SLOT_TREEM')       || define('GAME_SLOT_TREEM', 21);  //TREEM slot
defined('GAME_CASINO_TREEM')     || define('GAME_CASINO_TREEM', 22);  //TREEM casino
defined('GAME_SLOT_SIGMA')       || define('GAME_SLOT_SIGMA', 23);  //SIGMA slot
defined('GAME_CASINO_SIGMA')     || define('GAME_CASINO_SIGMA', 24);  //SIGMA casino
defined('GAME_EVOL_BALL')        || define('GAME_EVOL_BALL', 25);

defined('N2P_MAX_HOLE')          || define('N2P_MAX_HOLE', 4);

defined('DELAY_GAME')            || define('DELAY_GAME', 10);
defined('DELAY_TRANSFER')        || define('DELAY_TRANSFER', 5);
defined('DELAY_APIBET')          || define('DELAY_APIBET', 2);
defined('DELAY_PLAYING')         || define('DELAY_PLAYING', 300); //expiration time of game play (seconds)

//money change type
defined('MONEYCHANGE_CHARGE')    || define('MONEYCHANGE_CHARGE', 1);   
defined('MONEYCHANGE_EXCHANGE')  || define('MONEYCHANGE_EXCHANGE', 2); 
defined('POINTCHANGE_EXCHANGE')  || define('POINTCHANGE_EXCHANGE', 3); 
defined('MONEYCHANGE_BET_PB')    || define('MONEYCHANGE_BET_PB', 4); //PBG
defined('MONEYCHANGE_DENY_PB')   || define('MONEYCHANGE_DENY_PB', 5);
defined('MONEYCHANGE_WIN_PB')    || define('MONEYCHANGE_WIN_PB', 6);
defined('MONEYCHANGE_BET_DH')    || define('MONEYCHANGE_BET_DH', 7); //DHP 
defined('MONEYCHANGE_DENY_DH')    || define('MONEYCHANGE_DENY_DH', 8);
defined('MONEYCHANGE_WIN_DH')    || define('MONEYCHANGE_WIN_DH', 9);
defined('MONEYCHANGE_BET_SK')    || define('MONEYCHANGE_BET_SK', 10); //Speed Keno
defined('MONEYCHANGE_DENY_SK')    || define('MONEYCHANGE_DENY_SK', 11);
defined('MONEYCHANGE_WIN_SK')    || define('MONEYCHANGE_WIN_SK', 12);
defined('MONEYCHANGE_BET_BB')    || define('MONEYCHANGE_BET_BB', 13); //Boggle
defined('MONEYCHANGE_DENY_BB')    || define('MONEYCHANGE_DENY_BB', 14);
defined('MONEYCHANGE_WIN_BB')    || define('MONEYCHANGE_WIN_BB', 15);
defined('MONEYCHANGE_BET_BS')    || define('MONEYCHANGE_BET_BS', 16); //Boggle 
defined('MONEYCHANGE_DENY_BS')    || define('MONEYCHANGE_DENY_BS', 17);
defined('MONEYCHANGE_WIN_BS')    || define('MONEYCHANGE_WIN_BS', 18);

defined('MONEYCHANGE_CHARGE_DEC')   || define('MONEYCHANGE_TRANS_DEC', 19);     //Transfer to lower level
defined('MONEYCHANGE_CHARGE_INC')   || define('MONEYCHANGE_TRANS_INC', 20);     //Transfer from upper level
defined('MONEYCHANGE_EXCHANGE_INC') || define('MONEYCHANGE_EXCHANGE_INC', 27);  //withdraw lower level
defined('MONEYCHANGE_EXCHANGE_DEC') || define('MONEYCHANGE_EXCHANGE_DEC', 28);  //withdraw by upper level

defined('MONEYCANCEL_CHARGE')    || define('MONEYCANCEL_CHARGE', 21);   //cancel deposit
defined('MONEYCANCEL_EXCHANGE')  || define('MONEYCANCEL_EXCHANGE', 22); //cancel withdraw

defined('MONEYCHANGE_INC')       || define('MONEYCHANGE_INC', 23);     //deposit directly 
defined('MONEYCHANGE_DEC')       || define('MONEYCHANGE_DEC', 26);     //withdraw directly 
defined('MONEYCHANGE_WITHDRAW')  || define('MONEYCHANGE_WITHDRAW', 24); //withdraw all money
defined('POINTHANGE_WITHDRAW')   || define('POINTHANGE_WITHDRAW', 25);  //withdraw all point

defined('MONEYCHANGE_BET_EO5')    || define('MONEYCHANGE_BET_EO5', 31); //Eos5
defined('MONEYCHANGE_DENY_EO5')   || define('MONEYCHANGE_DENY_EO5', 32);
defined('MONEYCHANGE_WIN_EO5')    || define('MONEYCHANGE_WIN_EO5', 33); 
defined('MONEYCHANGE_BET_EO3')    || define('MONEYCHANGE_BET_EO3', 34); //Eos3
defined('MONEYCHANGE_DENY_EO3')   || define('MONEYCHANGE_DENY_EO3', 35);
defined('MONEYCHANGE_WIN_EO3')    || define('MONEYCHANGE_WIN_EO3', 36);
defined('MONEYCHANGE_BET_CO5')    || define('MONEYCHANGE_BET_CO5', 37); //Coin5
defined('MONEYCHANGE_DENY_CO5')   || define('MONEYCHANGE_DENY_CO5', 38);
defined('MONEYCHANGE_WIN_CO5')    || define('MONEYCHANGE_WIN_CO5', 39);
defined('MONEYCHANGE_BET_CO3')    || define('MONEYCHANGE_BET_CO3', 40); //Coin3
defined('MONEYCHANGE_DENY_CO3')   || define('MONEYCHANGE_DENY_CO3', 41);
defined('MONEYCHANGE_WIN_CO3')    || define('MONEYCHANGE_WIN_CO3', 42);
defined('MONEYCHANGE_BET_EBAL')   || define('MONEYCHANGE_BET_EBAL', 43); //Evol
defined('MONEYCHANGE_DENY_EBAL')  || define('MONEYCHANGE_DENY_EBAL', 44);
defined('MONEYCHANGE_WIN_EBAL')   || define('MONEYCHANGE_WIN_EBAL', 45);

//money transfer type
defined('TRANS_EVOL_SITE')      || define('TRANS_EVOL_SITE', 1);  
defined('TRANS_SITE_EVOL')      || define('TRANS_SITE_EVOL', 2);  
defined('TRANS_SITE_KGON')      || define('TRANS_SITE_KGON', 3); 
defined('TRANS_KGON_SITE')      || define('TRANS_KGON_SITE', 4); 
defined('TRANS_SITE_PLUS')      || define('TRANS_SITE_PLUS', 5); 
defined('TRANS_PLUS_SITE')      || define('TRANS_PLUS_SITE', 6); 
defined('TRANS_SITE_GSPL')      || define('TRANS_SITE_GSPL', 7); 
defined('TRANS_GSPL_SITE')      || define('TRANS_GSPL_SITE', 8); 
defined('TRANS_SITE_GOLD')      || define('TRANS_SITE_GOLD', 9); 
defined('TRANS_GOLD_SITE')      || define('TRANS_GOLD_SITE', 10); 
defined('TRANS_SITE_STAR')      || define('TRANS_SITE_STAR', 11); 
defined('TRANS_STAR_SITE')      || define('TRANS_STAR_SITE', 12); 
defined('TRANS_SITE_HOLD')      || define('TRANS_SITE_HOLD', 13); 
defined('TRANS_HOLD_SITE')      || define('TRANS_HOLD_SITE', 14); 
defined('TRANS_SITE_RAVE')      || define('TRANS_SITE_RAVE', 15); 
defined('TRANS_RAVE_SITE')      || define('TRANS_RAVE_SITE', 16); 
defined('TRANS_SITE_TREEM')     || define('TRANS_SITE_TREEM', 17); 
defined('TRANS_TREEM_SITE')     || define('TRANS_TREEM_SITE', 18); 
defined('RECOVER_EGG')          || define('RECOVER_EGG', 19); 
defined('TRANS_SITE_SIGMA')     || define('TRANS_SITE_SIGMA', 20); 
defined('TRANS_SIGMA_SITE')     || define('TRANS_SIGMA_SITE', 21); 
// 
defined('NOTICE_MSG')            || define('NOTICE_MSG', 0);
defined('NOTICE_BOARD')          || define('NOTICE_BOARD', 1);   
defined('NOTICE_CUSTOMER')       || define('NOTICE_CUSTOMER', 3);   
defined('NOTICE_MSG_ALL')        || define('NOTICE_MSG_ALL', 4);

// AAS 요청오류
defined('INVALID_ACCESS_TOKEN') || define('INVALID_ACCESS_TOKEN', "INVALID_ACCESS_TOKEN");  //증명서 불일치(code/token)
defined('INVALID_PRODUCT')      || define('INVALID_PRODUCT', "INVALID_PRODUCT");            //프로젝트가 존재하지 않는 경우
defined('INVALID_PARAMETER')    || define('INVALID_PARAMETER', "INVALID_PARAMETER");        //요청이 잘못된 경우
defined('INVALID_USER')         || define('INVALID_USER', "INVALID_USER");                  //사용자가 존재하지 않을 경우
defined('DOUBLE_USER')          || define('DOUBLE_USER', "DOUBLE_USER");                    //해당 유저가 이미 존재하는 경우
defined('INSUFFICIENT_FUNDS')   || define('INSUFFICIENT_FUNDS', "INSUFFICIENT_FUNDS");      //귀사의 알이 부족한 경우
defined('INTERNAL_ERROR')       || define('INTERNAL_ERROR', "INTERNAL_ERROR");              //기타오류
defined('INVALID_AMOUNT')       || define('INVALID_AMOUNT', "INVALID_AMOUNT");              //금액이 올바르지 않은경우
defined('GAME_PLAYING')         || define('GAME_PLAYING', "GAME_PLAYING");                  //해당 유저가 게임중인 경우
defined('CONNECT_ERROR')        || define('CONNECT_ERROR', "CONNECT_ERROR");              //기타오류

// SLOT 결과
defined('SLOTCODE_SUCCESS')         || define('SLOTCODE_SUCCESS', 0);       //정상   
defined('SLOTCODE_WARNING')         || define('SLOTCODE_WARNING', 1);       //정상적으로 실행되었으나 Description 확인 필요 
defined('SLOTCODE_SESSION_FAIL')    || define('SLOTCODE_SESSION_FAIL', 8);       //게임세션 생성 실패   
defined('SLOTCODE_IP_AUTH')         || define('SLOTCODE_IP_AUTH', 9);       //인증되지 않은 IP 
defined('SLOTCODE_USER_BALANCE')    || define('SLOTCODE_USER_BALANCE', 10); //충분하지 않은 회원 잔액 
defined('SLOTCODE_AGENT_BALANCE')   || define('SLOTCODE_AGENT_BALANCE', 11);//충분하지 않은 관리자 잔액 
defined('SLOTCODE_NOFORMAT')        || define('SLOTCODE_NOFORMAT', 64);    //값이 형식에 불일치 
defined('SLOTCODE_OUTRANGE')        || define('SLOTCODE_OUTRANGE', 65);    //값이 범위에서 벗어남 
defined('SLOTCODE_DOUBLE_USER')     || define('SLOTCODE_DOUBLE_USER', 89);       //중복된 회원 ID 
defined('SLOTCODE_SESSION_END')     || define('SLOTCODE_SESSION_END', 96);     //만료된 게임세션
defined('SLOTCODE_SESSION_NO')      || define('SLOTCODE_SESSION_NO', 97);     //찾을 수 없는 게임세션 
defined('SLOTCODE_USER_NONE')       || define('SLOTCODE_USER_NONE', 98);     //찾을 수 없는 회원 ID 
defined('SLOTCODE_PARAMETER_NO')    || define('SLOTCODE_PARAMETER_NO', 99);    //API 호출을 위한 매개변수 부족 
defined('SLOTCODE_API_FAIL')        || define('SLOTCODE_API_FAIL', 100);    //API 요청 실패 - 시스템 관리자 문의 

defined('HTTP_CODE_200')        || define('HTTP_CODE_200', 200);    
defined('HTTP_CODE_400')        || define('HTTP_CODE_400', 400);    
defined('HTTP_CODE_403')        || define('HTTP_CODE_403', 403);    
defined('HTTP_CODE_404')        || define('HTTP_CODE_404', 404);    
defined('HTTP_CODE_409')        || define('HTTP_CODE_409', 409);  

defined('PLAY_FAIL_TRANSFER')       || define('PLAY_FAIL_TRANSFER', "1001");    //play failure due to failing of transfer
defined('PLAY_FAIL_RESPONSE')       || define('PLAY_FAIL_RESPONSE', "1002");    //play failure due to no response


defined('TRYLOG_SUCCESS')           || define('TRYLOG_SUCCESS', "Success");    
defined('TRYLOG_DENIED')            || define('TRYLOG_DENIED', "Denied");    
defined('TRYLOG_FAIL')              || define('TRYLOG_FAIL', "Fail");    
defined('TRYLOG_NONE')              || define('TRYLOG_NONE', "None");    
defined('TRYLOG_DELETED')           || define('TRYLOG_DELETED', "Deleted");    
defined('TRYLOG_BLOCK')             || define('TRYLOG_BLOCK', "Block");    
defined('TRYLOG_MAINTAIN')          || define('TRYLOG_MAINTAIN', "Maintain");    
defined('TRYLOG_WAIT')              || define('TRYLOG_WAIT', "Waiting");    
defined('TRYLOG_IDBLOCK')           || define('TRYLOG_IDBLOCK', "Id-Block");    
defined('TRYLOG_IPBLOCK')           || define('TRYLOG_IPBLOCK', "Ip-Block");    
defined('TRYLOG_IPDENIED')          || define('TRYLOG_IPDENIED', "Ip-denied");    
defined('TRYLOG_LOGINING')          || define('TRYLOG_LOGINING', "Logining");    

defined('NUM_POINT_CNT')            || define('NUM_POINT_CNT', 1);    

