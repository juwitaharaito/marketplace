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
		foreach ($json[4]['katakunci'] as $item)
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
				$json[4]['katakunci'][$indexkeyword]['keyword'] = $kata;
				$indexkeywords = $indexkeyword;
				$pageurl="";
				$new = true;
			}
			else //berarti ada kecocokan
			{
				$kets = "keyword ada";
				if (isset($json[4]['katakunci'][$indexkeywords]['kategori'][$kategori]) == false)  //berarti nggak ada kategori
				{
					$page = 1;
					$pageurl="";
					$new = true;
					$kets = "keyword ada tapi kategori nggak, page = ".$page;
				}
				else
				{
					
					$page =  count($json[4]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal']); 
					$kets = "keyword dan kategori ada, page = ".$page;
					$new = false; //new = false, dia cuma menampilkan yang pernah dibuka					
				}
			}					
		}
		else //berarti tekan load more. keyword berdasarkan table yang aktif.
		{
			$page =  count($json[4]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'])+1;
			$pageurl = "&page=".$page;
			$new = true;
			$kets = "tekan loadmore, page = ".$page;
		}
		if ($new == true) //buat baru, kata nggak ada, kategori nggak ada atau tekan loadmore
		{
			$strings = str_replace(" ","+",$kata);
			if ($kategori==0)
				$tambahan = '/?search%5Border%5D=filter_float_price%3Aasc&view=galleryWide';
			else
				$tambahan = '/?search%5Border%5D=created_at%3Adesc&view=galleryWide';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:19.0) Gecko/20100101 Firefox/19.0");
			curl_setopt($ch, CURLOPT_URL,'http://olx.co.id/all-results/q-'.$strings.$tambahan.$pageurl);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$result=curl_exec($ch);
			curl_close($ch);
			$pecah = explode('<ul class="gallerywide clr normal " id="gallerywide">',$result);
			$pecah2 = explode ('</ul>',$pecah[1]);
			$string2 = preg_replace('/\s+/', ' ', $pecah2[0]);
			$string = "|<[^>]+>(.*)</[^>]+>|U";
			preg_match_all($string, $string2, $match, PREG_PATTERN_ORDER);
			$json[4]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'] = [];
			$i=0; $a=0; $b=0;
			foreach ($match[0] as $temp)
			{
				preg_match('/<a title="(.+?)" href="/',$temp,$temp2);
				if (($temp2[1] != ""))
				{
					$json[4]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$i]['nama']= $temp2[1];
					$i++;
				}
				preg_match('/src="(.+?)" class="fleft rel zi2"/',$temp,$temp3);
				if ($temp3[1] != "")
				{
					
					$json[4]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$a]['gambar']= $temp3[1];
					$a++;
				}
				preg_match('/<strong class="c000 fleft">(.+?)<\/strong>/',$temp,$temp4);
				if ($temp4[1] != "")
				{
					$json[4]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$b]['harga']= $temp4[1];
					$b++;
				}
			
	
			}
			$ket = "keyword tidak ada atau kategori tidak ada atau tekan loadmore";
		}
		else 
			$ket = "menampilkan yang sudah ada dan bukan load more";
		$array =[];
		$j = 0;
		foreach ($json[4]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'] as $pages)
		{
			foreach ($pages['items'] as $items)
			{
				$array[$j]->gambar =  $items['gambar'];
				$array[$j]->nama = $items['nama'];
				$array[$j]->harga = $items['harga'];	
				$j++;
			}
		}
		
		echo json_encode(array("arr" => $array, "mp" => "4", "json" => $json, "link" => $redirect, "kata" => $kata, "kategori" => $kategori, "page" =>$page));
		
	}
	else 
	{
	echo "gagal";
	}

                     ?>  
