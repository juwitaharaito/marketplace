<?php 
	error_reporting(E_ERROR | E_PARSE);
	if ((isset($_POST["statu"]))&&(isset($_POST["json"]))&&(isset($_POST["q"])) &&(isset($_POST["kategori"])))
	{
		$new = false;
		$json = json_decode($_POST["json"],true);
		$statu = ($_POST["statu"]);
		$kata = ($_POST["q"]);
		$kata = str_replace('"','',$kata);
		$kategori = ($_POST["kategori"]);
		$kategori = str_replace('"','',$kategori);
		$indexkeyword = 0; $indexkeywords = -1;
		foreach ($json[3]['katakunci'] as $item)
		{
			$keter = $item['keyword'].",".$kata;
			if ($item['keyword'] == $kata) //cek apakah kata itu ada di json
			{
				$indexkeywords = $indexkeyword;
				$keter = "keyword ada";
			}
			$indexkeyword++;
		}
		
		if ($statu == 'menu') //berarti pindah menu. keyword dari textfield
		{
			if ($indexkeywords<0) //berarti nggak ada kecocokan kata
			{
				$keter = "keyword gaada";
				$page = 1;
				$kets = "keyword nggak ada, page = ".$page;
				$json[3]['katakunci'][$indexkeyword]['keyword'] = $kata;
				$indexkeywords = $indexkeyword;
				$pageurl="";
				$new = true;
			}
			else //berarti ada kecocokan
			{
				$kets = "keyword ada";
				if (isset($json[3]['katakunci'][$indexkeywords]['kategori'][$kategori]) == false)  //berarti nggak ada kategori
				{
					$page = 1;
					$pageurl="";
					$new = true;
					$kets = "keyword ada tapi kategori nggak, page = ".$page;
				}
				else
				{
					
					$page =  count($json[3]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal']); 
					$kets = "keyword dan kategori ada, page = ".$page;
					$new = false; //new = false, dia cuma menampilkan yang pernah dibuka					
				}
			}					
		}
		else //berarti tekan load more. keyword berdasarkan table yang aktif.
		{
			$page =  count($json[3]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'])+1;
			$pageurl = "&page=".$page;
			$new = true;
			$kets = "tekan loadmore, page = ".$page;
		}
		if ($new == true) //buat baru, kata nggak ada, kategori nggak ada atau tekan loadmore
		{
			$strings = str_replace(" ","+",$kata);
			if ($kategori==0)
				$tambahan = '&sort=price&order=asc';
			else
				$tambahan = '&last_post&order=desc';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
			curl_setopt($ch, CURLOPT_USERAGENT, "CURL");
			
			//curl_setopt($ch, CURLOPT_COOKIE, 'NID=67=pdjIQN5CUKVn0bRgAlqitBk7WHVivLsbLcr7QOWMn35Pq03N1WMy6kxYBPORtaQUPQrfMK4Yo0vVz8tH97ejX3q7P2lNuPjTOhwqaI2bXCgPGSDKkdFoiYIqXubR0cTJ48hIAaKQqiQi_lpoe6edhMglvOO9ynw; PREF=ID=52aa671013493765:U=0cfb5c96530d04e3:FF=0:LD=en:TM=1370266105:LM=1370341612:GM=1:S=Kcc6KUnZwWfy3cOl; OTZ=1800625_34_34__34_; S=talkgadget=38GaRzFbruDPtFjrghEtRw; SID=DQAAALoAAADHyIbtG3J_u2hwNi4N6UQWgXlwOAQL58VRB_0xQYbDiL2HA5zvefboor5YVmHc8Zt5lcA0LCd2Riv4WsW53ZbNCv8Qu_THhIvtRgdEZfgk26LrKmObye1wU62jESQoNdbapFAfEH_IGHSIA0ZKsZrHiWLGVpujKyUvHHGsZc_XZm4Z4tb2bbYWWYAv02mw2njnf4jiKP2QTxnlnKFK77UvWn4FFcahe-XTk8Jlqblu66AlkTGMZpU0BDlYMValdnU; HSID=A6VT_ZJ0ZSm8NTdFf; SSID=A9_PWUXbZLazoEskE; APISID=RSS_BK5QSEmzBxlS/ApSt2fMy1g36vrYvk; SAPISID=ZIMOP9lJ_E8SLdkL/A32W20hPpwgd5Kg1J');
			curl_setopt($ch, CURLOPT_AUTOREFERER, true);
			curl_setopt($ch, CURLOPT_URL,'https://www.kaskus.co.id/search/fjb?q='.$strings.$tambahan.$pageurl);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$result=curl_exec($ch);
			curl_close($ch);
			//echo ($result);
			$pecah = explode('<div id="fjb-search-result">',$result);
			$pecah2 = explode ('<div class="promoted__header">',$pecah[1]);
			$string = "|<[^>]+>(.*)</[^>]+>|U";
			//$string2 = str_replace("\"","'",($pecah2[0]));
			preg_match_all($string, $pecah2[0], $match, PREG_PATTERN_ORDER);
	
			$json[3]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'] = [];
			$i=0; $a=0; $b=0;
			foreach ($match[0] as $temp)
			{
				preg_match('/" alt="(.+?)"><div class="image__photo"/',$temp,$temp2);
				if (($temp2[1] != ""))
				{
					$json[3]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$i]['nama']= $temp2[1];
					$i++;
				}
				preg_match('/<div class="image__photo" style="background-image:url\((.+?)\)"><span class="item__tag tag--jual">/',$temp,$temp3);
				if ($temp3[1] != "")
				{
					$gambar = str_replace("/r320x320","",$temp3[1]);
					$json[3]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$a]['gambar']= $temp3[1];
					$a++;
				}
				preg_match('/<div class="price--discounted" style="margin-right: 5px;">(.+?)<\/div>/',$temp,$temp4);
				if ($temp4[1] != "")
				{
					$json[3]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$b]['harga']= $temp4[1];
					$b++;
				}
			
	
			}
			$ket = "keyword tidak ada atau kategori tidak ada atau tekan loadmore";
		}
		else 
			$ket = "menampilkan yang sudah ada dan bukan load more";
		$array =[];
		$j = 0;
		foreach ($json[3]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'] as $pages)
		{
			foreach ($pages['items'] as $items)
			{
				$array[$j]->gambar =  $items['gambar'];
				$array[$j]->nama = $items['nama'];
				$array[$j]->harga = $items['harga'];	
				$j++;
			}
		}
		$redirect = 'https://www.kaskus.co.id/search/fjb?q='.$strings.$tambahan.$pageurl;
		echo json_encode(array("arr" => $array, "mp" => "3", "json" => $json, "link" => $redirect, "kata" => $kata, "kategori" => $kategori, "page" =>$array[2]->gambar));
		
	}
	else 
	{
	echo "gagal";
	}

                     ?>  
