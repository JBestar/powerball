// window.open
function windowOpen(src,target,width,height,scroll)
{
	var wid = (screen.availWidth - width) / 2;
	var hei = (screen.availHeight - height) / 2;
	var opt = 'width='+width+',height='+height+',top='+hei+',left='+wid+',resizable=no,status=no,scrollbars='+scroll;
	window.open(src,target,opt);
}

// only number
function onlyNumber()
{
	if((event.keyCode < 48) || (event.keyCode > 57))
	{
		event.returnValue = false;
	}
}

// only number (use onkeydown)
function isNumber(event)
{
	var keyCode = event.keyCode;

	if(event.shiftKey)
	{
		return false;
	}

	if(keyCode >= 48 && keyCode <= 59)	// 0~9
	{
		return true;
	}
	else if(keyCode >= 96 && keyCode <= 105)	// keypad 0~9 (NumLock On)
	{
		return true;
	}
	else if(keyCode == 8 || keyCode == 46)	// backspace, delete
	{
		return true;
	}

	return false;
}

// set cookie
function setCookie(name,value,expiredays)
{
	var todayDate = new Date();
	todayDate.setDate( todayDate.getDate() + expiredays );
	document.cookie = name + '=' + escape( value ) + '; path=/; expires=' + todayDate.toGMTString() + ';'
}

function getCookie(name)
{
	var nameOfCookie = name + '=';
	var x = 0;
		while(x <= document.cookie.length)
		{
			var y = (x+nameOfCookie.length);
			if(document.cookie.substring( x, y ) == nameOfCookie)
			{
				if( (endOfCookie=document.cookie.indexOf( ';', y )) == -1 )
					endOfCookie = document.cookie.length; 
					return unescape( document.cookie.substring( y, endOfCookie ) ); 
			}
			x = document.cookie.indexOf( ' ', x ) + 1; 
			if(x == 0) break;
		}

	return '';
}

function memoSend(tuserid,memoid)
{
	document.getElementById('chatFrame').contentWindow.memoSend(tuserid,memoid);
}

function memoNoti(no)
{
	windowOpen('/?view=memoView&type=receive&me_id='+no,'memo',600,600,'auto');
}

var beforeType = '';
var beforeDivision = '';
function ajaxPattern(type,date,division,update)
{
	if(update != true && beforeType == type && beforeDivision == division && $('#patternBox').find('.content').html())
	{
		$('#patternBox').find('.content').empty();
		$('#patternBox a').each(function(){
			$(this).removeClass('on');
		});

		beforeType = '';
		if (typeof heightResize === 'function') setTimeout(function() { heightResize(); }, 120);
	}
	else
	{
		$('#patternBox a').each(function(){
			$(this).removeClass('on');
			if ($(this).attr('type') == division+'_'+type)
			{
				$(this).addClass('on');
			}
		});

		beforeType = type;
		beforeDivision = division;

		$.ajax({
			type:'POST',
			url:'/',
			data:{
				view:'action',
				action:'ajaxPattern',
				actionType:type,
				division:division,
				date:date
			},
			dataType:'json',
			success:function (data,textStatus){

				if(type != 'oddEven' && data.content == 'notlogin')
				{
					alert('로그인 후 이용가능합니다.');
				}
				else if(data.content == 'charge')
				{
					alert('[자유이용권] 아이템 구매 후 이용 가능합니다.\n\n구매하신 분은 [아이템] 에서 [사용] 눌러주세요.');
				}
				else if(data.content)
				{
					$('#patternBox').find('.content').html(data.content);
					$('#patternBox').find('.content').animate({scrollLeft:10000},1000);

					heightResize();
				}
			},
			error:function (xhr,textStatus,errorThrown){
				//alert('error'+(errorThrown?errorThrown:xhr.status));
			}
		});
	}
}

var sixBeforeType = '';
var sixBeforeCnt = 0;
var sixBeforeDivision = '';
function ajaxSixPattern(cnt,type,date,division,update)
{
	var $content = $('#sixBox').find('.content');
	var contentHtml = $content.length ? $content.html() : '';
	var sameChoice = String(sixBeforeType) === String(type) && String(sixBeforeCnt) === String(cnt) && String(sixBeforeDivision) === String(division);
	var goClear = update !== true && sameChoice && contentHtml && contentHtml.length > 0;
	if(goClear)
	{
		$('#sixBox').find('.content').empty();
		$('#sixBox a').each(function(){
			$(this).removeClass('on');
		});

		sixBeforeType = '';
		sixBeforeCnt = 0;
		sixBeforeDivision = '';
		if (typeof heightResize === 'function') setTimeout(function() { heightResize(); }, 120);
	}
	else
	{
		$('#sixBox a').each(function(){
			$(this).removeClass('on');
			if ($(this).attr('type') == division+'_'+type)
			{
				$(this).addClass('on');
			}
		});

		sixBeforeType = type;
		sixBeforeCnt = cnt;
		sixBeforeDivision = division;

		$.ajax({
			type:'POST',
			url:'/',
			data:{
				view:'action',
				action:'ajaxSixPattern',
				actionType:type,
				patternCnt:cnt,
				division:division,
				date:date
			},
			dataType:'json',
			success:function (data,textStatus){
				if((type != 'oddEven' || division != 'powerball') && data.content == 'notlogin')
				{
					alert('로그인 후 이용가능합니다.');
				}
				else if(data.content == 'charge')
				{
					alert('[자유이용권] 아이템 구매 후 이용 가능합니다.\n\n구매하신 분은 [아이템] 에서 [사용] 눌러주세요.');
				}
				else if(data.content)
				{
					$('#sixBox').find('.content').html(data.content);
					$('#sixBox').find('.content').animate({scrollLeft:10000},1000);

					heightResize();
				}
			},
			error:function (xhr,textStatus,errorThrown){
				//alert('error'+(errorThrown?errorThrown:xhr.status));
			}
		});
	}
}

function number_format(data)
{
	var tmp = '';
	var number = '';
	var cutlen = 3;
	var comma = ',';
	var i;

	len = data.length;
	mod = (len % cutlen);
	k = cutlen - mod;

	for(i=0; i<data.length; i++)
	{
		number = number + data.charAt(i);

		if (i < data.length - 1) 
		{
			k++;
			if ((k % cutlen) == 0) 
			{
				number = number + comma;
				k = 0;
			}
		}
	}

	return number;
}

function chargePop()
{
	windowOpen('?view=charge','charge',500,770,'no');
}

function giftPop(itemCode,chargeType,itemCnt)
{
	windowOpen('?view=giftPop&itemCode='+itemCode+'&chargeType='+chargeType+'&itemCnt='+itemCnt,'giftPop',420,530,'no');
}

$(document).ready(function(){
	$('.rollover').live({
		mouseover:function(){
			$(this).addClass('on');
		},
		mouseout:function(){
			$(this).removeClass('on');
		}
	});
});

function initAd()
{
	$('#mainFrame').load(function(){
		var chkUrl = top.location.href;

		var banner_main = '';
		var banner_left1 = '';
		var banner_left2 = '';
		var banner_sky = '';

		if(chkUrl && chkUrl.indexOf('photo') != -1)
		{
			banner_main = '<iframe width="200" height="200" allowtransparency="true" src="https://tab2.clickmon.co.kr/pop/wp_ad_200.php?PopAd=CM_M_1003067%7C%5E%7CCM_A_1007369%7C%5E%7CAdver_M_1046207&rt_ad_id_code=RTA_106068&mon_rf='+encodeURIComponent(document.referrer)+'" frameborder="0" scrolling="no"></iframe>';

			banner_left1 = '<iframe width="320" height="50" allowtransparency="true" src="https://mtab.clickmon.co.kr/pop/wp_m_320.php?PopAd=CM_M_1003067%7C%5E%7CCM_A_1007369%7C%5E%7CAdver_M_1046207&mon_rf='+encodeURIComponent(document.referrer)+'" frameborder="0" scrolling="no"></iframe>';

			banner_left2 = '<iframe width="300" height="250" allowtransparency="true" src="https://tab2.clickmon.co.kr/pop/wp_ad_300.php?PopAd=CM_M_1003067%7C%5E%7CCM_A_1007369%7C%5E%7CAdver_M_1046207&rt_ad_id_code=RTA_106068&mon_rf='+encodeURIComponent(document.referrer)+'" frameborder="0" scrolling="no"></iframe>';

			banner_sky = '<iframe width="160" height="600" allowtransparency="true" src="https://tab2.clickmon.co.kr/pop/wp_ad_160.php?PopAd=CM_M_1003067%7C%5E%7CCM_A_1007369%7C%5E%7CAdver_M_1046207&rt_ad_id_code=RTA_106068&mon_rf='+encodeURIComponent(document.referrer)+'" frameborder="0" scrolling="no"></iframe>';
			banner_sky += '<div style="height:10px;"></div>';
			banner_sky += '<div id="ad_rightBottom"><iframe width="160" height="600" allowtransparency="true" src="https://tab2.clickmon.co.kr/pop/wp_ad_160.php?PopAd=CM_M_1003067%7C%5E%7CCM_A_1007369%7C%5E%7CAdver_M_1046207&rt_ad_id_code=RTA_106068&mon_rf='+encodeURIComponent(document.referrer)+'" frameborder="0" scrolling="no"></iframe></div>';
		}
		else	// google adsense
		{
			/*
			banner_main = '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"><'+'/script><!-- powerballgame(신규) - 메인 포토옆배너 --><ins class="adsbygoogle" style="display:inline-block;width:200px;height:200px" data-ad-client="ca-pub-9485969337677894" data-ad-slot="9857015439"><'+'/ins><script>(adsbygoogle = window.adsbygoogle || []).push({});<'+'/script>';

			banner_left1 = '<!-- powerballgame - 로그인 하단(320*50) --><ins class="adsbygoogle" style="display:inline-block;width:320px;height:50px" data-ad-client="ca-pub-9485969337677894" data-ad-slot="7222626369"><'+'/ins><script>(adsbygoogle = window.adsbygoogle || []).push({});<'+'/script>';

			banner_left2 = '<!-- powerballgame(신규) - 좌측배너3 (336*280) --><ins class="adsbygoogle" style="display:inline-block;width:336px;height:280px" data-ad-client="ca-pub-9485969337677894" data-ad-slot="3818220698"><'+'/ins><script>(adsbygoogle = window.adsbygoogle || []).push({});<'+'/script>';
			banner_left2 += '<div style="margin-top:10px;text-align:center;"><!-- powerballgame(신규) - 좌측배너1 (300*600) --><ins class="adsbygoogle" style="display:inline-block;width:300px;height:600px" data-ad-client="ca-pub-9485969337677894" data-ad-slot="4462870918"><'+'/ins><script>(adsbygoogle = window.adsbygoogle || []).push({});<'+'/script></div>';
			banner_left2 += '<div style="margin-top:10px;text-align:center;"><!-- powerballgame(신규) - 좌측배너3 (336*280) --><ins class="adsbygoogle" style="display:inline-block;width:336px;height:280px" data-ad-client="ca-pub-9485969337677894" data-ad-slot="3818220698"><'+'/ins><script>(adsbygoogle = window.adsbygoogle || []).push({});<'+'/script></div>';

			banner_sky = '<!-- powerballgame(신규) - 스카이배너1 (160*600) --><ins class="adsbygoogle" style="display:inline-block;width:160px;height:600px" data-ad-client="ca-pub-9485969337677894" data-ad-slot="2411422643"><'+'/ins><script>(adsbygoogle = window.adsbygoogle || []).push({});<'+'/script>';
			banner_sky += '<div style="height:10px;"></div><div id="ad_rightBottom"><!-- powerballgame(신규) - 스카이배너3 (160*600) --><ins class="adsbygoogle" style="display:inline-block;width:160px;height:600px" data-ad-client="ca-pub-9485969337677894" data-ad-slot="5044684216"><'+'/ins><script>(adsbygoogle = window.adsbygoogle || []).push({});<'+'/script></div>';
			*/
		}

		/*
		try{
			$('#banner_main_area').html(banner_main);
			$('#banner_left1_area').html(banner_left1);
			$('#banner_left2_area').html(banner_left2);
			$('#banner_sky_area').html(banner_sky);
		}
		catch(e){}
		*/
	});
}

function frameAutoResize(frameId,height)
{
	$('#'+frameId).css('height',height+'px');
}

function heightResize()
{
	if($('body').height() < 500)
	{
		var resizeHeight = 500;
	}
	else
	{
		var resizeHeight = $('body').height();
	}

	try{
		parent.frameAutoResize('mainFrame',resizeHeight);
	}
	catch(e){}
}

$(document).ready(function(){
	frameLoad();
	
	$('#mainFrame').load(function(){
		try{
			var hashStr = encodeURIComponent(document.mainFrame.location.href);
			location.replace(location.href.split('#')[0]+'#'+hashStr);
		}
		catch(e){}
	});
});

function frameLoad()
{
	if(location.href.indexOf('#') != -1)
	{
		var urlArr = location.href.split('#');

		if(urlArr[1])
		{
			mainFrame.location.href = decodeURIComponent(urlArr[1]);
		}
	}
}

function ladderTimer(divId)
{
	if(remainTime == 0)
	{
		remainTime = 300;

		var roundNum = parseInt($('#timeRound').text())+1;
		if(roundNum == 289) roundNum = 1;
		
		$('#timeRound').text(roundNum);

		roundNum = null;

		if($('#powerballPointBetGraph').length)
		{
			setTimeout(function(){
				$('#powerballPointBetGraph .oddChart .oddBar').animate({width:'0px'},1000,function(){
					$(this).next().text('0%');
				});
				$('#powerballPointBetGraph .oddChart .oddPer').animate({right:'-7px'},1000);

				$('#powerballPointBetGraph .evenChart .evenBar').animate({width:'0px'},1000,function(){
					$(this).next().text('0%');
				});
				$('#powerballPointBetGraph .evenChart .evenPer').animate({left:'-7px'},1000);
			},3000);
		}
	}

	remainTime--;

	var remain_i = Math.floor(remainTime / 60);
	var remain_s = Math.floor(remainTime % 60);

	if(remain_i < 10) remain_i = '0' + remain_i;
	if(remain_s < 10) remain_s = '0' + remain_s;

	$('#'+divId).find('.minute').text(remain_i);
	$('#'+divId).find('.second').text(remain_s);

	remain_i = null;
	remain_s = null;
}