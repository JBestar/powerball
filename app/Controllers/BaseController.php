<?php

namespace App\Controllers;


/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 *
 * @package CodeIgniter
 */

use CodeIgniter\Controller;

//use App\Models\ConfSite_Model;
//use App\Models\Member_Model;
//use App\Models\Sess_Model;
//use App\Models\Notice_Model;
//use App\Models\ConfGame_Model;
//use App\Models\CasPrd_Model;
//use App\Models\SlotPrd_Model;
//use App\Models\Transfer_Model;

use App\Libraries\ApiCas_Lib;
use App\Libraries\ApiKgon_Lib;
use App\Libraries\ApiSlot_Lib;
use App\Libraries\ApiFslot_Lib;
use App\Libraries\ApiGslot_Lib;
use App\Libraries\ApiHslot_Lib;
use App\Libraries\ApiHold_Lib;
use App\Libraries\ApiRave_Lib;
use App\Libraries\ApiTreem_Lib;
use App\Libraries\ApiSigma_Lib;

class BaseController extends Controller
{

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = ['url', 'session', 'common_helper', 'curl_helper'];
	protected $session ;

	protected $modelMember;
	protected $modelConfsite;
	protected $modelConfgame;
	protected $modelSess;
	protected $modelNotice;
	protected $modelCasprd;
	protected $modelSlotprd;
	protected $modelTransfer;

	protected $libApiCas;
	protected $libApiKgon;
	protected $libApiSlot;
	protected $libApiFslot;
	protected $libApiGslot;
	protected $libApiHslot;
	protected $libApiHold;
	protected $libApiRave;
	protected $libApiTreem;
	protected $libApiSigma;

    /**
     * Constructor.
     */
	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);


        //--------------------------------------------------------------------
		// Preload any models, libraries, etc, here.
		//--------------------------------------------------------------------
		// E.g.:
		// $this->session = \Config\Services::session();

        $this->session = session();
//		$this->modelMember = new Member_Model();
//		$this->modelConfsite = new ConfSite_Model();
//		$this->modelConfgame = new ConfGame_Model();
//		$this->modelSess = new Sess_Model();
//		$this->modelNotice = new Notice_Model();
//		$this->modelCasprd = new CasPrd_Model();
//      $this->modelSlotprd = new SlotPrd_Model();
//      $this->modelTransfer = new Transfer_Model();
		
//		$this->libApiCas = new ApiCas_Lib();
//		$this->libApiKgon = new ApiKgon_Lib();
//		$this->libApiSlot = new ApiSlot_Lib();
//      $this->libApiFslot = new ApiFslot_Lib();
//      $this->libApiGslot = new ApiGslot_Lib();
//      $this->libApiHslot = new ApiHslot_Lib();
//      $this->libApiHold = new ApiHold_Lib();
//      $this->libApiRave = new ApiRave_Lib();
//      $this->libApiTreem = new ApiTreem_Lib();
//      $this->libApiSigma = new ApiSigma_Lib();
	
    }

	protected function setLanguage(){
		
        $locale = $this->session->get('lang');
		$configApp = new \Config\App();
		if(!array_key_exists('app.lang', $_ENV) || intval($_ENV['app.lang']) == 0 ){
			if(is_null($locale) || is_array($locale) || strlen($locale) < 1 || $locale != $configApp->defaultLocale){
				$locale = $configApp->defaultLocale;
				$this->session->set('lang', $locale);
			}
		}
        if(is_null($locale) || is_array($locale) || strlen($locale) < 1){

			// $configApp = new \Config\App();
			if(array_key_exists('app.lang', $_ENV) && intval($_ENV['app.lang']) > 0 ){
				
				$locale = $this->request->getCookie('lang');
				writeLog("cookieLang=".$locale);
				if(strlen($locale) < 1){
					$locale = $this->request->getLocale();
					writeLog("requestLocale=".$locale);
				}
				
				if(!in_array($locale, $configApp->supportedLocales)){
					$locale = $configApp->defaultLocale;
					writeLog("defaultLocale=".$locale);
				}
			} else 
				$locale = $configApp->defaultLocale;
			$this->session->set('lang', $locale);
        }
        $language = \Config\Services::language();
        $language->setLocale($this->session->lang);
		// writeLog(" language=".$language->getLocale());
		
	}
/*    
	protected function getSiteConf(){
		
		$confs = ['site_name'=>"", "bpg_deny"=>false, "evol_deny"=>false, "slot_deny"=>false, "cas_deny"=>false, 
			"eos5_deny"=>false, "eos3_deny"=>false, "evp_deny"=>true, "dhp_deny"=>false, "spk_deny"=>false,  
			"coin5_deny"=>false, "coin3_deny"=>false, "pbg_deny"=>false, "apps_enable"=>false, "hold_deny"=>false];
		$arrConf = $this->modelConfsite->getSiteConf();  
		
		foreach($arrConf as $objConf){
			switch($objConf->conf_id){
				case CONF_SITENAME:	$confs['site_name'] = $objConf->conf_content;
					break;
				case CONF_BPG_DENY:	$confs['bpg_deny'] = $objConf->conf_active == STATE_ACTIVE?true:false;
					break;
				case CONF_EVOL_DENY: $confs['evol_deny'] = $objConf->conf_active == STATE_ACTIVE?true:false;
					break;
				case CONF_SLOT_DENY: $confs['slot_deny'] = $objConf->conf_active == STATE_ACTIVE?true:false;
					break;
				case CONF_CAS_DENY:	$confs['cas_deny'] = $objConf->conf_active == STATE_ACTIVE?true:false;
					break;
				case CONF_EOS5_DENY:	$confs['eos5_deny'] = $objConf->conf_active == STATE_ACTIVE?true:false;
					break;
				case CONF_EOS3_DENY:	$confs['eos3_deny'] = $objConf->conf_active == STATE_ACTIVE?true:false;
					break;
				case CONF_COIN5_DENY:	$confs['coin5_deny'] = $objConf->conf_active == STATE_ACTIVE?true:false;
					break;
				case CONF_COIN3_DENY:	$confs['coin3_deny'] = $objConf->conf_active == STATE_ACTIVE?true:false;
					break;
				case CONF_PBG_DENY:	$confs['pbg_deny'] = $objConf->conf_active == STATE_ACTIVE?true:false;
					break;
				case CONF_DHP_DENY:	$confs['dhp_deny'] = $objConf->conf_active == STATE_ACTIVE?true:false;
					break;
				case CONF_SPK_DENY:	$confs['spk_deny'] = $objConf->conf_active == STATE_ACTIVE?true:false;
					break;
				case CONF_AUTOAPPS:	$confs['apps_enable'] = $objConf->conf_idx == STATE_ACTIVE?true:false;
					if($confs['apps_enable']){
						if(strlen($objConf->conf_content) < 1 ){
							$confs['apps_enable'] = false;
						} else {
							$confs['apps_auto'] = [];
							$arrInfo = explode(';', $objConf->conf_content);
							if(count($arrInfo) > 0){
								foreach($arrInfo as $objInfo){
									$info = explode('#', $objInfo);
									if(count($info) > 2){
										$app = new \stdClass();
										$app->ename = $info[0];
										$app->name = $info[1];
										$app->path = $info[2];
										$app->act = 1;
										if(count($info) > 3){
											$app->act = intval($info[3]);
											if($app->act == 0){
												$app->path = "";
											}
										}
										array_push($confs['apps_auto'], $app);
									}
								}
								if(count($confs['apps_auto']) == 0)
									$confs['apps_enable'] = false;
							}
						}
					}
					break;
				case CONF_HOLD_DENY: $confs['hold_deny'] = $objConf->conf_active == STATE_ACTIVE?true:false;
					break;
				default:break;
			}
		}
		
		if(array_key_exists('app.hold', $_ENV) && $_ENV['app.hold'] == 1) {
			$confs['evol_deny'] = false;
			$confs['cas_deny'] = false;
			$confs['slot_deny'] = false;
			$confs['bpg_deny'] = false;
		}

		return $confs;
	}
*/
	protected function sess_destroy(){
		$sess_id = $this->session->session_id;
		$this->modelSess->deleteBySess($sess_id);
		$this->session->destroy();
	}

	protected function sess_action(){
		if(array_key_exists('app.sess_act', $_ENV) && $_ENV['app.sess_act'] == 1){
			$sess_id = $this->session->session_id;
			$this->modelSess->updateAction($sess_id);
			if($_ENV['CI_ENVIRONMENT'] == ENV_DEVELOPMENT)
				writeLog("[sess_action] sess_id=".$sess_id);
		}
	}
/*
	protected function casinoPrd($confs){
		$navInfo['cas_evol'] = [];
		if(!$confs['evol_deny']){
			$navInfo['cas_evol'] = $this->modelCasprd->gets(GAME_CASINO_EVOL);
		}
		$navInfo['cas_cnt'] = count($navInfo['cas_evol']);
		$navInfo['cas_kgon'] = [];
		if(!$confs['cas_deny']){
			$gameId = GAME_CASINO_KGON;
			if($_ENV['app.casino'] == APP_CASINO_STAR)
				$gameId = GAME_CASINO_STAR;
			else if($_ENV['app.casino'] == APP_CASINO_RAVE)
				$gameId = GAME_CASINO_RAVE;
			else if($_ENV['app.casino'] == APP_CASINO_TREEM)
				$gameId = GAME_CASINO_TREEM;
			else if($_ENV['app.casino'] == APP_CASINO_SIGMA)
				$gameId = GAME_CASINO_SIGMA;
			$navInfo['cas_kgon'] = $this->modelCasprd->gets($gameId);
		}
		$navInfo['cas_cnt'] += count($navInfo['cas_kgon']);
		return $navInfo;
	}

	protected function slotPrd($confs){
		$navInfo['slot_plus'] = [];
		if(!$confs['slot_deny']){
			$gameId = 0;
			
			if($_ENV['app.type'] == APP_TYPE_2 ){
				$gameId = GAME_SLOT_GSPLAY;
				if($_ENV['app.fslot'] == APP_FSLOT_GOLD)
					$gameId = GAME_SLOT_GOLD;
			} else if($_ENV['app.type'] == APP_TYPE_1 || $_ENV['app.type'] == APP_TYPE_3){
				$gameId = GAME_SLOT_THEPLUS;
				if($_ENV['app.slot'] == APP_SLOT_KGON)
					$gameId = GAME_SLOT_KGON;
				else if($_ENV['app.slot'] == APP_SLOT_STAR)
					$gameId =GAME_SLOT_STAR;
				else if($_ENV['app.slot'] == APP_SLOT_RAVE)
					$gameId =GAME_SLOT_RAVE;
				else if($_ENV['app.slot'] == APP_SLOT_TREEM)
					$gameId =GAME_SLOT_TREEM;
				else if($_ENV['app.slot'] == APP_SLOT_SIGMA)
					$gameId =GAME_SLOT_SIGMA;
			}	
			$navInfo['slot_plus'] = $this->modelSlotprd->gets($gameId);
					
		}
		return $navInfo;
	}

	protected function allEgg(&$objMember){
		$confs = $this->getSiteConf();
		if(!$confs["evol_deny"]){
			$this->evEgg($objMember);
			usleep(100000);
		}
		$bHcasino = false;
		$bKcasino = false;
		$bRcasino = false;
		$bTcasino = false;
		$bScasino = false;
		if(!$confs["cas_deny"]){
			if($_ENV['app.casino'] == APP_CASINO_STAR){
				$this->hslEgg($objMember);
				$bHcasino = true;
			} else if($_ENV['app.casino'] == APP_CASINO_RAVE){
				$this->raveEgg($objMember);
				$bRcasino = true;
			} else if($_ENV['app.casino'] == APP_CASINO_TREEM){
				$this->treemEgg($objMember);
				$bTcasino = true;
			} else if($_ENV['app.casino'] == APP_CASINO_SIGMA){
				$this->sigmaEgg($objMember);
				$bScasino = true;
			} else {
				$this->kgonEgg($objMember);
				$bKcasino = true;
			}
			usleep(100000);
		}
		if($_ENV['app.type'] == APP_TYPE_1 || $_ENV['app.type'] == APP_TYPE_3){
			if($_ENV['app.slot'] == APP_SLOT_THEPLUS)
				$this->slEgg($objMember);
			else if($_ENV['app.slot'] == APP_SLOT_KGON && !$bKcasino)
				$this->kgonEgg($objMember);
			else if($_ENV['app.slot'] == APP_SLOT_STAR && !$bHcasino)
				$this->hslEgg($objMember);
			else if($_ENV['app.slot'] == APP_SLOT_RAVE && !$bRcasino)
				$this->raveEgg($objMember);
			else if($_ENV['app.slot'] == APP_SLOT_TREEM && !$bTcasino)
				$this->treemEgg($objMember);
			else if($_ENV['app.slot'] == APP_SLOT_SIGMA && !$bScasino)
				$this->sigmaEgg($objMember);
		}
		if($_ENV['app.type'] == APP_TYPE_1 || $_ENV['app.type'] == APP_TYPE_2){
			usleep(100000);
			if($_ENV['app.fslot'] == APP_FSLOT_GSPLAY)
				$this->fslEgg($objMember);
			else if($_ENV['app.fslot'] == APP_FSLOT_GOLD)
				$this->gslEgg($objMember);
		}
		if(!$confs["hold_deny"] ){
			usleep(100000);
			$this->holdEgg($objMember);
		}
	}
	
	protected function evEgg(&$objMember){
		$iResult = 0;

		$logHead = "<EvEgg>";
		//Request Money
		if($objMember->mb_live_id > 0){
			$arrResult = $this->libApiCas->getUserInfo($objMember->mb_live_uid);
			writeLog($logHead." ".$objMember->mb_uid."-UserInfo Status=".$arrResult['status']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead." ".$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$objMember->mb_live_money = $arrResult['balance'];
				$this->modelMember->updateLiveMoney($objMember);   
				$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function kgonEgg(&$objMember){
		$iResult = 0;

		$logHead = "<KgonEgg>";
		//Request Money
		if($objMember->mb_kgon_id > 0){
			$arrResult = $this->libApiKgon->getUserInfo($objMember->mb_kgon_uid);
			writeLog($logHead." ".$objMember->mb_uid."-UserInfo Status=".$arrResult['status']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead." ".$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$objMember->mb_kgon_money = floor($arrResult['balance']);
				$this->modelMember->updateKgonMoney($objMember);   
				$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function slEgg(&$objMember){
		$iResult = 0;

		$logHead = "<SlEgg> ";
		//Request Money
		if($objMember->mb_slot_uid !== ""){
			$arrResult = $this->libApiSlot->getUserInfo($objMember->mb_slot_uid);
			writeLog($logHead.$objMember->mb_uid."-UserInfo resultCode=".$arrResult['resultCode']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$objMember->mb_slot_money = $arrResult['balance'];
				$this->modelMember->updateSlotMoney($objMember);   
				$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	
	protected function fslEgg(&$objMember){
		$iResult = 0;
		$logHead = "<FslEgg> ";

		//fslot money
		if($objMember->mb_fslot_id > 0){

			$arrResult = $this->libApiFslot->getUserInfo($objMember->mb_fslot_uid);
			writeLog($logHead.$objMember->mb_uid."-UserInfo Status=".$arrResult['status']);

			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$objMember->mb_fslot_money = $arrResult['balance'];

				$this->modelMember->updateFslotMoney($objMember);   
				$iResult = 1;
			
			}
		} else {
            $iResult = 1;
        }

		return $iResult;
	}

	protected function gslEgg(&$objMember){
		$iResult = 0;

		$logHead = "<GslEgg> ";
		//Request Money
		if($objMember->mb_gslot_uid !== ""){
			$arrResult = $this->libApiGslot->getUserInfo($objMember->mb_gslot_uid);
			writeLog($logHead.$objMember->mb_uid."-UserInfo status=".$arrResult['status']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$objMember->mb_gslot_money = $arrResult['balance'];
				$this->modelMember->updateGslotMoney($objMember);   
				$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function hslEgg(&$objMember){
		$iResult = 0;
		$logHead = "<HslEgg> ";
		//Request Money
		if($objMember->mb_hslot_token !== ""){
			$arrResult = $this->libApiHslot->getUserInfo($objMember->mb_hslot_token);
			writeLog($logHead.$objMember->mb_uid."-UserInfo status=".$arrResult['status']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$objMember->mb_hslot_money = $arrResult['balance'];
				$this->modelMember->updateHslotMoney($objMember);   
				$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function holdEgg(&$objMember){
		$iResult = 0;

		$logHead = "<HoldEgg>";
		//Holdem Money
		if($objMember->mb_hold_uid != ""){
			
			$arrResult = $this->libApiHold->getUserInfo($objMember->mb_hold_uid);
			writeLog($logHead." ".$objMember->mb_uid."-UserInfo Status=".$arrResult['status']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead." ".$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$objMember->mb_hold_money = $arrResult['balance'];
				$this->modelMember->updateHoldMoney($objMember);   
				$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function raveEgg(&$objMember){
		$iResult = 0;

		$logHead = "<RaveEgg>";
		
		if($objMember->mb_rave_id > 0){
		
			$arrResult = $this->libApiRave->getUserInfo($objMember->mb_rave_uid);
			writeLog($logHead." ".$objMember->mb_uid."-UserInfo Status=".$arrResult['status']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead." ".$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$objMember->mb_rave_money = floor($arrResult['balance']);
				$this->modelMember->updateRaveMoney($objMember);   
				$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}
	
	protected function treemEgg(&$objMember){
		$iResult = 0;

		$logHead = "<TreemEgg>";
		
		if(strlen($objMember->mb_treem_uid) > 0){
		
			$arrResult = $this->libApiTreem->getUserInfo($objMember->mb_treem_uid);
			writeLog($logHead." ".$objMember->mb_uid."-UserInfo Status=".$arrResult['status']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead." ".$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$objMember->mb_treem_money = floor($arrResult['balance']);
				$this->modelMember->updateTreemMoney($objMember);   
				$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function sigmaEgg(&$objMember){
		$iResult = 0;

		$logHead = "<SigmaEgg>";
		
		if(strlen($objMember->mb_sigma_uid) > 0){
		
			$arrResult = $this->libApiSigma->getUserInfo($objMember->mb_sigma_uid);
			writeLog($logHead." ".$objMember->mb_uid."-UserInfo Status=".$arrResult['status']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead." ".$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$objMember->mb_sigma_money = floor($arrResult['balance']);
				$this->modelMember->updateSigmaMoney($objMember);   
				$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function alltoGame(&$objMember, $iGame = 0){
		$logHead = "<AlltoGame> ";
		$iResult = 0;
		$objUser = $this->modelMember->getByFid($objMember->mb_fid);
		if(diffDt(date('Y-m-d H:i:s'), $objUser->mb_time_call) < DELAY_TRANSFER){
			writeLog($logHead.$objMember->mb_uid."-Now=".date('Y-m-d H:i:s')." Call=".$objUser->mb_time_call);
			return $iResult;
		}
		$this->modelMember->updateCallTm($objMember);
		
		if($iGame == GAME_CASINO_EVOL){
			if($this->sltoMb($objMember) == 1 && $this->fsltoMb($objMember) == 1 &&
				$this->kgtoMb($objMember) == 1 && $this->gsltoMb($objMember) == 1 && 
				$this->hsltoMb($objMember) == 1 && $this->holtoMb($objMember) == 1 &&
				$this->rvtoMb($objMember) == 1 && $this->trtoMb($objMember) == 1 &&
				$this->sgtoMb($objMember) == 1){
					$iResult = $this->mbtoEv($objMember);
			}
		} else if($iGame == GAME_SLOT_THEPLUS){
			if($this->evtoMb($objMember) == 1 && $this->fsltoMb($objMember) == 1 &&
				$this->kgtoMb($objMember) == 1 && $this->gsltoMb($objMember) == 1 && 
				$this->hsltoMb($objMember) == 1 && $this->holtoMb($objMember) == 1 &&
				$this->rvtoMb($objMember) == 1 && $this->trtoMb($objMember) == 1 &&
				$this->sgtoMb($objMember) == 1) {
					$iResult = $this->mbtoSl($objMember);
			}
		} else if($iGame == GAME_SLOT_GSPLAY){
			if($this->evtoMb($objMember) == 1 && $this->sltoMb($objMember) == 1 &&
				$this->kgtoMb($objMember) == 1 && $this->gsltoMb($objMember) == 1 && 
				$this->hsltoMb($objMember) == 1 && $this->holtoMb($objMember) == 1 &&
				$this->rvtoMb($objMember) == 1 && $this->trtoMb($objMember) == 1 &&
				$this->sgtoMb($objMember) == 1) {
					$iResult = $this->mbtoFsl($objMember);
			}
		} else if($iGame == GAME_SLOT_GOLD){
			if($this->evtoMb($objMember) == 1 && $this->sltoMb($objMember) == 1 &&
				$this->kgtoMb($objMember) == 1 && $this->fsltoMb($objMember) == 1 && 
				$this->hsltoMb($objMember) == 1 && $this->holtoMb($objMember) == 1 &&
				$this->rvtoMb($objMember) == 1 && $this->trtoMb($objMember) == 1 &&
				$this->sgtoMb($objMember) == 1) {
					$iResult = $this->mbtoGsl($objMember);
			}
		} else if($iGame == GAME_CASINO_KGON || $iGame == GAME_SLOT_KGON){
			if($this->evtoMb($objMember) == 1 && $this->sltoMb($objMember) == 1 &&
				$this->fsltoMb($objMember) == 1 && $this->gsltoMb($objMember) == 1 && 
				$this->hsltoMb($objMember) == 1 && $this->holtoMb($objMember) == 1 &&
				$this->rvtoMb($objMember) == 1 && $this->trtoMb($objMember) == 1 &&
				$this->sgtoMb($objMember) == 1) {
					$iResult = $this->mbtoKg($objMember);
			}
		} else if($iGame == GAME_CASINO_STAR || $iGame == GAME_SLOT_STAR){
			if($this->evtoMb($objMember) == 1 && $this->sltoMb($objMember) == 1 &&
				$this->fsltoMb($objMember) == 1 && $this->kgtoMb($objMember) == 1 && 
				$this->gsltoMb($objMember) == 1 && $this->holtoMb($objMember) == 1 &&
				$this->rvtoMb($objMember) == 1 && $this->trtoMb($objMember) == 1 &&
				$this->sgtoMb($objMember) == 1) {
					$iResult = $this->mbtoHsl($objMember);
			}
		} else if($iGame == GAME_HOLD_CMS){
			if($this->evtoMb($objMember) == 1 && $this->sltoMb($objMember) == 1 &&
				$this->fsltoMb($objMember) == 1 && $this->kgtoMb($objMember) == 1 && 
				$this->gsltoMb($objMember) == 1 && $this->hsltoMb($objMember) == 1 &&
				$this->rvtoMb($objMember) == 1 && $this->trtoMb($objMember) == 1 &&
				$this->sgtoMb($objMember) == 1) {
					$iResult = $this->mbtoHol($objMember);
			}
		} else if($iGame == GAME_CASINO_RAVE || $iGame == GAME_SLOT_RAVE){
			if($this->evtoMb($objMember) == 1 && $this->sltoMb($objMember) == 1 &&
				$this->fsltoMb($objMember) == 1 && $this->kgtoMb($objMember) == 1 && 
				$this->gsltoMb($objMember) == 1 && $this->hsltoMb($objMember) == 1 && 
				$this->holtoMb($objMember) == 1 && $this->trtoMb($objMember) == 1 &&
				$this->sgtoMb($objMember) == 1) {
					$iResult = $this->mbtoRv($objMember);
			}
		} else if($iGame == GAME_CASINO_TREEM || $iGame == GAME_SLOT_TREEM){
			if($this->evtoMb($objMember) == 1 && $this->sltoMb($objMember) == 1 &&
				$this->fsltoMb($objMember) == 1 && $this->kgtoMb($objMember) == 1 && 
				$this->gsltoMb($objMember) == 1 && $this->hsltoMb($objMember) == 1 && 
				$this->holtoMb($objMember) == 1 && $this->rvtoMb($objMember) == 1 &&
				$this->sgtoMb($objMember) == 1) {
					$iResult = $this->mbtoTr($objMember);
			}
		} else if($iGame == GAME_CASINO_SIGMA || $iGame == GAME_SLOT_SIGMA){
			if($this->evtoMb($objMember) == 1 && $this->sltoMb($objMember) == 1 &&
				$this->fsltoMb($objMember) == 1 && $this->kgtoMb($objMember) == 1 && 
				$this->gsltoMb($objMember) == 1 && $this->hsltoMb($objMember) == 1 && 
				$this->holtoMb($objMember) == 1 && $this->rvtoMb($objMember) == 1 &&
				$this->trtoMb($objMember) == 1) {
					$iResult = $this->mbtoSg($objMember);
			}
		} else {
			if($this->evtoMb($objMember) == 1 && $this->sltoMb($objMember) == 1 &&
				$this->fsltoMb($objMember) == 1 && $this->kgtoMb($objMember) == 1 &&
				$this->gsltoMb($objMember) == 1 && $this->hsltoMb($objMember) == 1 && 
				$this->holtoMb($objMember) == 1  && $this->rvtoMb($objMember) == 1 &&
				$this->trtoMb($objMember) == 1 && $this->sgtoMb($objMember) == 1) {
					$iResult = 1;
			}
		}
		return $iResult ;

	}

	
	protected function evtoMb(&$objMember){
		$iResult = 0;
		$logHead = "<EvtoMb> ";
		//Evol => Site
		if($objMember->mb_live_id > 0){
			//Evol Money
			$arrResult = $this->libApiCas->getUserInfo($objMember->mb_live_uid);
			writeLog($logHead.$objMember->mb_uid."-UserInfo Status=".$arrResult['status']);

			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$amount = 0;
				if($arrResult['balance'] > 0){
					//Withdraw
					usleep(500000);
					$amount = $arrResult['balance'];
					$arrResp = $this->libApiCas->subBalance($objMember->mb_live_uid, $amount);
				} else {
					$objMember->mb_live_money = $arrResult['balance'];
					$this->modelMember->updateLiveMoney($objMember); 
					$iResult = 1;   //success
                    return $iResult;
				}
				
				if($arrResp['status'] == 1)
                {
                    writeLog($logHead.$objMember->mb_uid."-Withdraw RemainBalance=".$arrResp['balance']);
					$objMember->mb_live_money = $arrResp['balance'];
					$this->modelMember->updateLiveMoney($objMember);   
						
					if($this->modelMember->updateAssets($objMember, $amount)){
						$this->modelTransfer->register(TRANS_EVOL_SITE, $objMember, $objMember->mb_live_money+$amount, 0-$amount);
                        $objMember->mb_money += $amount;   
						writeLog($logHead.$objMember->mb_uid."-Withdraw Money=".$objMember->mb_money);
						$iResult = 1;
                    }
                } 
			} else {
				// if($objMember->mb_live_money == 0)
					$iResult = 1;
			}
		} else {
            $iResult = 1;
        }

		return $iResult;
	}

	protected function kgtoMb(&$objMember){
		$iResult = 0;
		$logHead = "<KgtoMb> ";

		//KGON => Site
		if($objMember->mb_kgon_id > 0){
			$arrResult = $this->libApiKgon->getUserInfo($objMember->mb_kgon_uid);
			writeLog($logHead." ".$objMember->mb_uid."-UserInfo Status=".$arrResult['status']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$amount = floor($arrResult['balance']);
				if($amount > 0){
					usleep(500000);
					//Withdraw
					$arrResp = $this->libApiKgon->subBalance($objMember->mb_kgon_uid, $amount, true);
				} else {
					$objMember->mb_kgon_money = $amount;
					$this->modelMember->updateKgonMoney($objMember); 
					$iResult = 1;   //success
                    return $iResult;
				}
			
				if($arrResp['status'] == 1)
				{
					$amount = floor($arrResp['amount']);
					writeLog($logHead.$objMember->mb_uid."-Withdraw RemainBalance=".$arrResp['balance']);
					$objMember->mb_kgon_money = $arrResp['balance'];
					$this->modelMember->updateKgonMoney($objMember);   
						
					if( $this->modelMember->updateAssets($objMember, $amount)){
						$this->modelTransfer->register(TRANS_KGON_SITE, $objMember, $objMember->mb_kgon_money+$amount, 0-$amount);
						$objMember->mb_money += $amount;   
						writeLog($logHead.$objMember->mb_uid."-Withdraw Money=".$objMember->mb_money);
						$iResult = 1;
					}
				} 
			} else {
				// if($objMember->mb_kgon_money == 0)
					$iResult = 1;
			}
		} else {
            $iResult = 1;
        }

		return $iResult;
	}

	protected function sltoMb(&$objMember){
		$iResult = 0;

		$logHead = "<SltoMb> ";
		//Slot => Site
		if($objMember->mb_slot_uid !== ""){
			
			$arrResult = $this->libApiSlot->getUserInfo($objMember->mb_slot_uid, true);
			writeLog($logHead." ".$objMember->mb_uid."-UserInfo resultCode=".$arrResult['resultCode']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead." ".$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$amount = 0;
				if($arrResult['balance'] > 0){
					//Withdraw
					usleep(500000);
					$amount = $arrResult['balance'];
					$arrResp =  $this->libApiSlot->subBalance($objMember->mb_slot_uid, $amount);
					writeLog($logHead." ".$objMember->mb_uid."-Withdraw resultCode=".$arrResp['resultCode']);
				} else {
					$objMember->mb_slot_money = $arrResult['balance'];
					$this->modelMember->updateSlotMoney($objMember);
                    $iResult = 1;   //success
                    return $iResult;
                }

				if($arrResp['status'] == 1)
				{
					writeLog($logHead.$objMember->mb_uid."-Withdraw RemainBalance=".$arrResp['balance']);
                    $objMember->mb_slot_money = $arrResp['balance'];
					$this->modelMember->updateSlotMoney($objMember);
					
					if($this->modelMember->updateAssets($objMember, $amount)){
						$this->modelTransfer->register(TRANS_PLUS_SITE, $objMember, $objMember->mb_slot_money+$amount, 0-$amount);
                        $objMember->mb_money += $amount;   
						writeLog($logHead.$objMember->mb_uid."-Withdraw Money=".$objMember->mb_money);
                        $iResult = 1;
                    }
                } 
			} else {
				// if($objMember->mb_slot_money == 0)
					$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	
	protected function fsltoMb(&$objMember){
		$iResult = 0;
		$logHead = "<FsltoMb> ";

		//Fslot => Site
		if($objMember->mb_fslot_id > 0){
			//Fslot money
			$arrResult = $this->libApiFslot->getUserInfo($objMember->mb_fslot_uid);
			writeLog($logHead.$objMember->mb_uid."-UserInfo Status=".$arrResult['status']);

			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$amount = 0;
				if($arrResult['balance'] > 0){
					//Withdraw
					usleep(500000);
					$amount = $arrResult['balance'];
					$arrResp = $this->libApiFslot->subBalance($objMember->mb_fslot_uid, $amount);
				} else {
					$objMember->mb_fslot_money = $arrResult['balance'];
					$this->modelMember->updateFslotMoney($objMember);   
					$iResult = 1;   //success
                    return $iResult;
				}
				
				if($arrResp['status'] == 1)
                {
                    writeLog($logHead.$objMember->mb_uid."-Withdraw RemainBalance=".$arrResp['balance']);
					$objMember->mb_fslot_money = $arrResp['balance'];
					$this->modelMember->updateFslotMoney($objMember);   
						
					if($this->modelMember->updateAssets($objMember, $amount)){
						$this->modelTransfer->register(TRANS_GSPL_SITE, $objMember, $objMember->mb_fslot_money+$amount, 0-$amount);
                        $objMember->mb_money += $amount;   
						writeLog($logHead.$objMember->mb_uid."-Withdraw Money=".$objMember->mb_money);
                        $iResult = 1;
                    }
                } 
			} else {
				// if($objMember->mb_fslot_money == 0){
					$iResult = 1;
			}
		} else {
            $iResult = 1;
        }

		return $iResult;
	}

	protected function gsltoMb(&$objMember){
		$iResult = 0;
		$logHead = "<GsltoMb> ";
		//GoldSlot => Site
		if($objMember->mb_gslot_uid !== ""){
			
			$arrResult = $this->libApiGslot->getUserInfo($objMember->mb_gslot_uid);
			writeLog($logHead." ".$objMember->mb_uid."-UserInfo status=".$arrResult['status']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead." ".$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$amount = 0;
				if($arrResult['balance'] > 0){
					//Withdraw
					usleep(500000);
					$amount = $arrResult['balance'];
					$arrResp =  $this->libApiGslot->subBalance($objMember->mb_gslot_uid, $amount);
					writeLog($logHead." ".$objMember->mb_uid."-Withdraw status=".$arrResp['status']);
				} else {
					$objMember->mb_gslot_money = $arrResult['balance'];
					$this->modelMember->updateGslotMoney($objMember);
                    $iResult = 1;   //success
                    return $iResult;
                }

				if($arrResp['status'] == 1)
				{
					writeLog($logHead.$objMember->mb_uid."-Withdraw RemainBalance=".$arrResp['balance']);
					$objMember->mb_gslot_money = $arrResp['balance'];
					$this->modelMember->updateGslotMoney($objMember);

                    if($this->modelMember->updateAssets($objMember, $amount)){
						$this->modelTransfer->register(TRANS_GOLD_SITE, $objMember, $objMember->mb_gslot_money+$amount, 0-$amount);
						$objMember->mb_money += $amount;   
						writeLog($logHead.$objMember->mb_uid."-Withdraw Money=".$objMember->mb_money);
						$iResult = 1;
					}
                } 
			} else {
				// if($objMember->mb_gslot_money == 0)
				$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function hsltoMb(&$objMember){
		$iResult = 0;
		$logHead = "<HsltoMb> ";
		//Star slot => Site
		if($objMember->mb_hslot_token !== ""){
			
			$arrResult = $this->libApiHslot->subBalance($objMember->mb_hslot_token);
			writeLog($logHead." ".$objMember->mb_uid."-UserInfo status=".$arrResult['status']);
			if($arrResult['status'] == 1)
			{
				$amount = $arrResult['amount'];

				writeLog($logHead.$objMember->mb_uid."-Withdraw amount=".$arrResult['amount']);
				$objMember->mb_hslot_money = $arrResult['balance'];
				$this->modelMember->updateHslotMoney($objMember);

				if($this->modelMember->updateAssets($objMember, $amount)){
					$this->modelTransfer->register(TRANS_STAR_SITE, $objMember, $objMember->mb_hslot_money+$amount, 0-$amount);
					$objMember->mb_money += $amount;   
					writeLog($logHead.$objMember->mb_uid."-Withdraw Money=".$objMember->mb_money);
					$iResult = 1;
				}
			} else {
				// if($objMember->mb_hslot_money == 0)
					$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function holtoMb(&$objMember){
		$iResult = 0;

		$logHead = "<HoltoMb> ";
		//Holdem => Site
		if($objMember->mb_hold_uid !== ""){
			
			$arrResult = $this->libApiHold->getUserInfo($objMember->mb_hold_uid);
			writeLog($logHead." ".$objMember->mb_uid."-UserInfo error=".$arrResult['error']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead." ".$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$amount = 0;
				if($arrResult['balance'] > 0){
					//Withdraw
					usleep(500000);
					$amount = $arrResult['balance'];
					$arrResp =  $this->libApiHold->subBalance($objMember->mb_hold_uid, $amount);
					writeLog($logHead." ".$objMember->mb_uid."-Withdraw error=".$arrResp['error']);
				} else {
					$objMember->mb_hold_money = $arrResult['balance'];
					$this->modelMember->updateHoldMoney($objMember);
                    $iResult = 1;   //success
                    return $iResult;
                }

				if($arrResp['status'] == 1)
				{
					writeLog($logHead.$objMember->mb_uid."-Withdraw RemainBalance=".$arrResp['balance']);
                    $objMember->mb_hold_money = $arrResp['balance'];
					$this->modelMember->updateHoldMoney($objMember);
					
					if($this->modelMember->updateAssets($objMember, $amount)){
						$this->modelTransfer->register(TRANS_HOLD_SITE, $objMember, $objMember->mb_hold_money+$amount, 0-$amount);
						$objMember->mb_money += $amount;
						writeLog($logHead.$objMember->mb_uid."-Withdraw Money=".$objMember->mb_money);
                        $iResult = 1;
                    }
                } 
			} else {
				// if($objMember->mb_hold_money == 0)
					$iResult = 1;
			}
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function rvtoMb(&$objMember){
		$iResult = 0;
		$logHead = "<RvtoMb> ";
		//RAVE => Site
		if($objMember->mb_rave_id > 0){
			$arrResult = $this->libApiRave->getUserInfo($objMember->mb_rave_uid);
			writeLog($logHead." ".$objMember->mb_uid."-UserInfo Status=".$arrResult['status']);
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-UserInfo Balance=".$arrResult['balance']." Money=".$objMember->mb_money);
				$amount = floor($arrResult['balance']);
				if($amount > 0){
					usleep(500000);
					//Withdraw
					$arrResp = $this->libApiRave->subBalance($objMember->mb_rave_uid, $amount, true);
				} else {
					$objMember->mb_rave_money = $amount;
					$this->modelMember->updateRaveMoney($objMember); 
					$iResult = 1;   //success
                    return $iResult;
				}
			
				if($arrResp['status'] == 1)
				{
					$amount = floor($arrResp['amount']);
					writeLog($logHead.$objMember->mb_uid."-Withdraw RemainBalance=".$arrResp['balance']);
					$objMember->mb_rave_money = $arrResp['balance'];
					$this->modelMember->updateRaveMoney($objMember);   
						
					if( $this->modelMember->updateAssets($objMember, $amount)){
						$this->modelTransfer->register(TRANS_RAVE_SITE, $objMember, $objMember->mb_rave_money+$amount, 0-$amount);
						$objMember->mb_money += $amount;   
						writeLog($logHead.$objMember->mb_uid."-Withdraw Money=".$objMember->mb_money);
						$iResult = 1;
					}
				} 
			} else {
				// if($objMember->mb_rave_money == 0){
					$iResult = 1;
				// }
			}
		} else {
            $iResult = 1;
        }

		return $iResult;
	}

	protected function trtoMb(&$objMember){
		$iResult = 0;
		$logHead = "<TrtoMb> ";
		//Treem => Site
		if(strlen($objMember->mb_treem_uid) > 0){
			$amount = 0;
			//Withdraw
			$arrResp = $this->libApiTreem->subBalance($objMember->mb_treem_uid, $amount, true);
		
			if($arrResp['status'] == 1)
			{
				$amount = floor(abs($arrResp['amount']));
				writeLog($logHead.$objMember->mb_uid."-Withdraw RemainBalance=".$arrResp['balance']);
				$objMember->mb_treem_money = $arrResp['balance'];
				$this->modelMember->updateTreemMoney($objMember);   
					
				if( $this->modelMember->updateAssets($objMember, $amount)){
					$this->modelTransfer->register(TRANS_TREEM_SITE, $objMember, $objMember->mb_treem_money+$amount, 0-$amount);
					$objMember->mb_money += $amount;   
					writeLog($logHead.$objMember->mb_uid."-Withdraw Money=".$objMember->mb_money);
					$iResult = 1;
				}
			} else if(array_key_exists('balance', $arrResp)) {
				$objMember->mb_treem_money = $arrResp['balance'];
				$this->modelMember->updateTreemMoney($objMember);   
				$iResult = 1;
			}
		
		} else {
            $iResult = 1;
        }

		return $iResult;
	}

	protected function sgtoMb(&$objMember){
		$iResult = 0;
		$logHead = "<SgtoMb> ";
		//Sigma => Site
		if(strlen($objMember->mb_sigma_uid) > 0){
			$amount = 0;
			//Withdraw
			$arrResp = $this->libApiSigma->subBalance($objMember->mb_sigma_uid, $amount, true);
		
			if($arrResp['status'] == 1)
			{
				$amount = floor(abs($arrResp['amount']));
				writeLog($logHead.$objMember->mb_uid."-Withdraw RemainBalance=".$arrResp['balance']);
				$objMember->mb_sigma_money = $arrResp['balance'];
				$this->modelMember->updateSigmaMoney($objMember);   
					
				if( $this->modelMember->updateAssets($objMember, $amount)){
					$this->modelTransfer->register(TRANS_SIGMA_SITE, $objMember, $objMember->mb_sigma_money+$amount, 0-$amount);
					$objMember->mb_money += $amount;   
					writeLog($logHead.$objMember->mb_uid."-Withdraw Money=".$objMember->mb_money);
					$iResult = 1;
				}
			} else if(array_key_exists('balance', $arrResp)) {
				$objMember->mb_sigma_money = $arrResp['balance'];
				$this->modelMember->updateSigmaMoney($objMember);   
				$iResult = 1;
			}
		
		} else {
            $iResult = 1;
        }

		return $iResult;
	}

	protected function mbtoEv(&$objMember){
		$iResult = 0;
		$logHead = "<MbtoEv> ";

		//Site => Evol
		if($objMember->mb_live_id > 0 && intval($objMember->mb_money) > 0){
			//
			$arrResult = $this->libApiCas->addBalance($objMember->mb_live_uid, $objMember->mb_money);
			writeLog($logHead.$objMember->mb_uid."-Deposit Status=".$arrResult['status']);
				
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-Deposit Balance=".$arrResult['balance']);
				if($this->modelMember->updateAssets($objMember, 0-$arrResult['amount'])){
					$amount = $arrResult['amount'];
					$this->modelTransfer->register(TRANS_SITE_EVOL, $objMember, $objMember->mb_live_money-$amount, $amount);
					$objMember->mb_live_money = $arrResult['balance'];
					$this->modelMember->updateLiveMoney($objMember);   
					$objMember->mb_money -= $arrResult['amount'];   
					$iResult = 1;
				}
			} 
		} else {
            $iResult = 1;
        }

		return $iResult;
	}

	protected function mbtoKg(&$objMember){
		$iResult = 0;
		$logHead = "<MbtoKg> ";

		//Site => KGON
		if($objMember->mb_kgon_id > 0 && intval($objMember->mb_money) > 0){
			//
			$arrResult = $this->libApiKgon->addBalance($objMember->mb_kgon_uid, $objMember->mb_money);
			writeLog($logHead.$objMember->mb_uid."-Deposit Status=".$arrResult['status']);
				
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-Deposit Balance=".$arrResult['balance']);
				if($this->modelMember->updateAssets($objMember, 0-$arrResult['amount'])){
					$objMember->mb_kgon_money = $arrResult['balance'];
					$amount = $arrResult['amount'];
					$this->modelTransfer->register(TRANS_SITE_KGON, $objMember, $objMember->mb_kgon_money-$amount, $amount);
					$this->modelMember->updateKgonMoney($objMember);   
					$objMember->mb_money -= $arrResult['amount'];   
					$iResult = 1;
				}
			} 
		} else {
            $iResult = 1;
        }

		return $iResult;
	}

	protected function mbtoSl(&$objMember){
		$iResult = 0;
		$logHead = "<MbtoSl> ";
		//Site => Slot
		if($objMember->mb_slot_uid !== "" && intval($objMember->mb_money) > 0){
			
			$arrResult = $this->libApiSlot->addBalance($objMember->mb_slot_uid, $objMember->mb_money);
			writeLog($logHead." ".$objMember->mb_uid."-Deposit resultCode=".$arrResult['resultCode']);
			
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-Deposit Balance=".$arrResult['balance']);
				if($this->modelMember->updateAssets($objMember, 0-$arrResult['amount'])){
					$objMember->mb_slot_money = $arrResult['balance'];
					$amount = $arrResult['amount'];
					$this->modelTransfer->register(TRANS_SITE_PLUS, $objMember, $objMember->mb_slot_money-$amount, $amount);
					$this->modelMember->updateSlotMoney($objMember);
					$objMember->mb_money -= $arrResult['amount'];   
					$iResult = 1;
				}
			} 
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	
	protected function mbtoFsl(&$objMember){
		$iResult = 0;
		$logHead = "<MbtoFsl> ";

		//Site => Fslot
		if($objMember->mb_fslot_id > 0 && intval($objMember->mb_money) > 0){
			//
			$arrResult = $this->libApiFslot->addBalance($objMember->mb_fslot_uid, $objMember->mb_money);
			writeLog($logHead.$objMember->mb_uid."-Deposit Status=".$arrResult['status']);
				
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-Deposit Balance=".$arrResult['balance']);
				if($this->modelMember->updateAssets($objMember, 0-$arrResult['amount'])){
					$objMember->mb_fslot_money = $arrResult['balance'];
					$amount = $arrResult['amount'];
					$this->modelTransfer->register(TRANS_SITE_GSPL, $objMember, $objMember->mb_fslot_money-$amount, $amount);
					$this->modelMember->updateFslotMoney($objMember);   
					$objMember->mb_money -= $arrResult['amount'];   
					$iResult = 1;
				}
			} 
		} else {
            $iResult = 1;
        }

		return $iResult;
	}

	protected function mbtoGsl(&$objMember){
		$iResult = 0;
		$logHead = "<MbtoGsl> ";
		//Site => Gold slot
		if($objMember->mb_gslot_uid !== "" && intval($objMember->mb_money) > 0){
			
			$arrResult = $this->libApiGslot->addBalance($objMember->mb_gslot_uid, $objMember->mb_money);
			writeLog($logHead." ".$objMember->mb_uid."-Deposit status=".$arrResult['status']);
			
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-Deposit Balance=".$arrResult['balance']);
				if($this->modelMember->updateAssets($objMember, 0-$arrResult['amount'])){
					$objMember->mb_gslot_money = $arrResult['balance'];
					$amount = $arrResult['amount'];
					$this->modelTransfer->register(TRANS_SITE_GOLD, $objMember, $objMember->mb_gslot_money-$amount, $amount);
					$this->modelMember->updateGslotMoney($objMember);
					$objMember->mb_money -= $arrResult['amount'];   
					$iResult = 1;
				}
			} 
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function mbtoHsl(&$objMember){
		$iResult = 0;
		$logHead = "<MbtoHsl> ";
		//Site => Star Slot
		if($objMember->mb_hslot_token !== "" && intval($objMember->mb_money) > 0){
			
			$arrResult = $this->libApiHslot->addBalance($objMember->mb_hslot_token, $objMember->mb_money);
			writeLog($logHead." ".$objMember->mb_uid."-Deposit status=".$arrResult['status']);
			
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-Deposit Amount=".$arrResult['amount']);
				if($this->modelMember->updateAssets($objMember, 0-$arrResult['amount'])){
					$objMember->mb_hslot_money += $arrResult['amount'];
					$amount = $arrResult['amount'];
					$this->modelTransfer->register(TRANS_SITE_STAR, $objMember, $objMember->mb_hslot_money-$amount, $amount);
					$this->modelMember->updateHslotMoney($objMember);
					$objMember->mb_money -= $arrResult['amount'];   
					$iResult = 1;
				}
			} 
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function mbtoHol(&$objMember){
		$iResult = 0;
		$logHead = "<MbtoHold> ";
		//Site => Holdem
		if($objMember->mb_hold_uid !== "" && intval($objMember->mb_money) > 0){
			
			$arrResult = $this->libApiHold->addBalance($objMember->mb_hold_uid, $objMember->mb_money);
			writeLog($logHead." ".$objMember->mb_uid."-Deposit status=".$arrResult['status']);
			
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-Deposit Amount=".$arrResult['amount']);
				if($this->modelMember->updateAssets($objMember, 0-$arrResult['amount'])){
					$objMember->mb_hold_money += $arrResult['amount'];
					$amount = $arrResult['amount'];
					$this->modelTransfer->register(TRANS_SITE_HOLD, $objMember, $objMember->mb_hold_money-$amount, $amount);
					$this->modelMember->updateHoldMoney($objMember);
					$objMember->mb_money -= $arrResult['amount'];   
					$iResult = 1;
				}
			} 
		} else {
            $iResult = 1;
        }
		return $iResult;
	}

	protected function mbtoRv(&$objMember){
		$iResult = 0;
		$logHead = "<MbtoRv> ";

		//Site => Rave
		if($objMember->mb_rave_id > 0 && floor($objMember->mb_money) > 0){
			//
			$arrResult = $this->libApiRave->addBalance($objMember->mb_rave_uid, $objMember->mb_money);
			writeLog($logHead.$objMember->mb_uid."-Deposit Status=".$arrResult['status']);
				
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-Deposit Balance=".$arrResult['balance']);
				if($this->modelMember->updateAssets($objMember, 0-$arrResult['amount'])){
					$objMember->mb_rave_money = $arrResult['balance'];
					$amount = $arrResult['amount'];
					$this->modelTransfer->register(TRANS_SITE_RAVE, $objMember, $objMember->mb_rave_money-$amount, $amount);
					$this->modelMember->updateRaveMoney($objMember);   
					$objMember->mb_money -= $arrResult['amount'];   
					$iResult = 1;
				}
			} 
		} else {
            $iResult = 1;
        }

		return $iResult;
	}
	
	protected function mbtoTr(&$objMember){
		$iResult = 0;
		$logHead = "<MbtoTr> ";

		//Site => Treem
		if(strlen($objMember->mb_treem_uid) > 0 && floor($objMember->mb_money) > 0){
			//
			$arrResult = $this->libApiTreem->addBalance($objMember->mb_treem_uid, $objMember->mb_money);
			writeLog($logHead.$objMember->mb_uid."-Deposit Status=".$arrResult['status']);
				
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-Deposit Balance=".$arrResult['balance']);
				if($this->modelMember->updateAssets($objMember, 0-$arrResult['amount'])){
					$objMember->mb_treem_money = $arrResult['balance'];
					$amount = $arrResult['amount'];
					$this->modelTransfer->register(TRANS_SITE_TREEM, $objMember, $objMember->mb_treem_money-$amount, $amount);
					$this->modelMember->updateTreemMoney($objMember);   
					$objMember->mb_money -= $arrResult['amount'];   
					$iResult = 1;
				}
			} 
		} else {
            $iResult = 1;
        }

		return $iResult;
	}

	protected function mbtoSg(&$objMember){
		$iResult = 0;
		$logHead = "<MbtoSg> ";

		//Site => Sigma
		if(strlen($objMember->mb_sigma_uid) > 0 && floor($objMember->mb_money) > 0){
			//
			$arrResult = $this->libApiSigma->addBalance($objMember->mb_sigma_uid, $objMember->mb_money);
			writeLog($logHead.$objMember->mb_uid."-Deposit Status=".$arrResult['status']);
				
			if($arrResult['status'] == 1)
			{
				writeLog($logHead.$objMember->mb_uid."-Deposit Balance=".$arrResult['balance']);
				if($this->modelMember->updateAssets($objMember, 0-$arrResult['amount'])){
					$objMember->mb_sigma_money = $arrResult['balance'];
					$amount = $arrResult['amount'];
					$this->modelTransfer->register(TRANS_SITE_SIGMA, $objMember, $objMember->mb_sigma_money-$amount, $amount);
					$this->modelMember->updateSigmaMoney($objMember);   
					$objMember->mb_money -= $arrResult['amount'];   
					$iResult = 1;
				}
			} 
		} else {
            $iResult = 1;
        }

		return $iResult;
	}*/
}
