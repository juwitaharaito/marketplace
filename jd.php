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
		foreach ($json[5]['katakunci'] as $item)
		{
			$keter = $item['keyword'].",".$kata;
			if ($item['keyword'] == $kata) //cek apakah kata itu ada di json
			{
				$indexkeywords = $indexkeyword;
				$keter = "keyword ada";
			}
			$indexkeyword++;
		}
		
		if ($statu == 'menu' || $statu == 'kategori') //berarti pindah menu. keyword dari textfield
		{
			if ($indexkeywords<0) //berarti nggak ada kecocokan kata
			{
				$keter = "keyword gaada";
				$page = 1;
				$kets = "keyword nggak ada, page = ".$page;
				$json[5]['katakunci'][$indexkeyword]['keyword'] = $kata;
				$indexkeywords = $indexkeyword;
				$pageurl="";
				$new = true;
			}
			else //berarti ada kecocokan
			{
				$kets = "keyword ada";
				if (isset($json[5]['katakunci'][$indexkeywords]['kategori'][$kategori]) == false)  //berarti nggak ada kategori
				{
					$page = 1;
					$pageurl="";
					$new = true;
					$kets = "keyword ada tapi kategori nggak, page = ".$page;
				}
				else
				{
					$page =  count($json[5]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal']); 
					$kets = "keyword dan kategori ada, page = ".$page;
					$new = false; //new = false, dia cuma menampilkan yang pernah dibuka					
				}
			}					
		}
		else //berarti tekan load more. keyword berdasarkan table yang aktif.
		{
			$page =  count($json[5]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'])+1;
			$pageurl = "&page=".$page;
			$new = true;
			$kets = "tekan loadmore, page = ".$page;
		}
		if ($new == true) //buat baru, kata nggak ada atau kategori nggak ada atau tekan loadmore
		{
			$strings = str_replace(" ","+",$kata);
			if ($kategori==0)
				$tambahan = '&sortType=sort_lowprice_asc';
			else
				$tambahan = '&sortType=sort_online_time_desc';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, 'https://www.jd.id/search?keywords='.$strings.$tambahan.$pageurl);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$result=curl_exec($ch);
			curl_close($ch);
			$pecah = explode('<div class="list-products-t">',$result);
			$pecah2 = explode ('<div class="f-cb">',$pecah[1]);
			$string = "|<[^>]+>(.*)</[^>]+>|U";
			$string2 = preg_replace('/\s+/', ' ', $pecah2[0]);
			preg_match_all($string, $string2, $match, PREG_PATTERN_ORDER);
			$json[5]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'] = [];
			$i=0; $a=0; $b=0; $c=0;
			foreach ($match[0] as $temp)
			{
				preg_match('/alt="(.+?)" onerror=/',$temp,$temp2);
				if (($temp2[1] != ""))
				{
					$json[5]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$i]['nama']= $temp2[1];
					$i++;
				}
				preg_match('/<img src="(.+?)" alt=/',$temp,$temp3);
				if ($temp3[1] != "")
				{
					$json[5]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$a]['gambar']= $temp3[1];
					$a++;
				}
				preg_match('/<a href="(.+?)" target="_blank">/',$temp,$temp5);
				if ($temp5[1] != "")
				{
					$json[5]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$c]['url']= $temp5[1];
					$c++;
				}
			}
			$pecaah = explode('event: "viewList", item: ',$result);
			$pecaah2 = explode ('}',$pecaah[1]);
			$string2 = preg_replace('/\s+/', ' ', $pecaah2[0]);
			$string2 = str_replace(" ","",$string2);
			$ch2 = curl_init();
			curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch2, CURLOPT_URL, 'https://ipr.jd.id/getBatchPrdPromo.html?json={"currency":"IDR","spuIdList":'.$string2.'}&callback=promoServiceCallback');
			curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
			$result2=curl_exec($ch2);
			curl_close($ch2);
			$pecah = explode('promoServiceCallback({"currency":"IDR","spuList":',$result2);
			$pecah2 = explode ('})',$pecah[1]);
			$json1 = json_decode($pecah2[0],true);
			foreach($json1 as $item)
			{
				$json[5]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$b]['harga'] = $item['showPrice'];
				$b++;
			}
			if ($json[5]['katakunci'][$indexkeywords]['kategori'][$kategori]['pagecount']== null)
			{
				preg_match('/onclick="goToPage\((.+?),\'/',$result,$pagecount);
				if ($pagecount[1] != null)
					$pagecounts = $pagecount[1];
				else
				{
					$pagecounts = '1';
				}
				$json[5]['katakunci'][$indexkeywords]['kategori'][$kategori]['pagecount'] = $pagecounts;
			}
			$ket = "keyword tidak ada atau kategori tidak ada atau tekan loadmore";
		}
		else 
			$ket = "menampilkan yang sudah ada dan bukan load more";
		$array =[];
		$j = 0;
		foreach ($json[5]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'] as $pages)
		{
			foreach ($pages['items'] as $items)
			{
				$array[$j]->gambar =  $items['gambar'];
				$array[$j]->nama = $items['nama'];
				$array[$j]->url = $items['url'];
				$array[$j]->harga = "Rp. ".$items['harga'];	
				$j++;
			}
		}
		$pagecounts = $json[5]['katakunci'][$indexkeywords]['kategori'][$kategori]['pagecount'];
		echo json_encode(array("arr" => $array, "mp" => "5", "json" => $json, "kasus" => '1', "kata" => $kata, "kategori" => $kategori, "page" =>$page, "pagecount" => $pagecounts));
		
	}
	else 
	{
	echo "gagal";
	}

                     ?>  
