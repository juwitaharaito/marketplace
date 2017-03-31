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
		foreach ($json[6]['katakunci'] as $item)
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
				$json[6]['katakunci'][$indexkeyword]['keyword'] = $kata;
				$indexkeywords = $indexkeyword;
				$pageurl="";
				$new = true;
			}
			else //berarti ada kecocokan
			{
				$kets = "keyword ada";
				if (isset($json[6]['katakunci'][$indexkeywords]['kategori'][$kategori]) == false)  //berarti nggak ada kategori
				{
					$page = 1;
					$pageurl="";
					$new = true;
					$kets = "keyword ada tapi kategori nggak, page = ".$page;
				}
				else
				{
					$page =  count($json[6]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal']); 
					$kets = "keyword dan kategori ada, page = ".$page;
					$new = false; //new = false, dia cuma menampilkan yang pernah dibuka					
				}
			}					
		}
		else //berarti tekan load more. keyword berdasarkan table yang aktif.
		{
			$page =  count($json[6]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'])+1;
			$pageurl = "i=".(($page-1)*24);
			$new = true;
			$kets = "tekan loadmore, page = ".$page;
		}
		if ($new == true) //buat baru, kata nggak ada atau kategori nggak ada atau tekan loadmore
		{
			$strings = str_replace(" ","+",$kata);
			if ($kategori==0)
				$tambahan = '&o=3&';
			else
				$tambahan = '&o=1&';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:19.0) Gecko/20100101 Firefox/19.0");
			curl_setopt($ch, CURLOPT_URL, 'https://www.blibli.com/search?s='.$strings.$tambahan.$pageurl);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$result=curl_exec($ch);
			curl_close($ch);
			$pecah = explode('<section class="product-list-tumbnails">',$result);
			$pecah2 = explode ('</section',$pecah[1]);	
			$string = "|<[^>]+>(.*)</[^>]+>|U";
			$string2 = preg_replace('/\s+/', ' ', $pecah2[0]);
			preg_match('/-(.+?)<\/strong> dari/',$result,$itemterakhir);
			preg_match_all($string, $string2, $match, PREG_PATTERN_ORDER);			
	
			$i=0; $a=0; $b=0; $c=0;
			foreach ($match[0] as $temp)
			{
				preg_match('/<div class="product-title" title="(.+?)">/',$temp,$temp2);
				if (($temp2[1] != ""))
				{
					$json[6]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$i]['nama']= $temp2[1];
					$i++;
				}
				preg_match('/data-original="(.+?)" width="/',$temp,$temp3);
				if ($temp3[1] != "")
				{
					$json[6]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$a]['gambar']= $temp3[1];
					$a++;
				}
				preg_match('/<span class="new-price-text">Rp(.+?)<\/span>/',$temp,$temp4);
				if ($temp4[1] != "")
				{
					$json[6]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$b]['harga']= $temp4[1];
					$b++;
				}
				preg_match('/href="(.+?)" onClick="/',$temp,$temp5);
				if ($temp5[1] != "")
				{
					$json[6]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$c]['url']= $temp5[1];
					$c++;
				}
			}
			if ($json[6]['katakunci'][$indexkeywords]['kategori'][$kategori]['pagecount']== null)
			{
				preg_match('/<\/h1>" ditemukan <b>(.+?)<\/b> produk<\/span>/',$result,$banyakitem);
				if ($banyakitem[1] != null)
					$banyakitems = $banyakitem[1];
				$json[6]['katakunci'][$indexkeywords]['kategori'][$kategori]['pagecount'] = $banyakitems;
			}
			$ket = "keyword tidak ada atau kategori tidak ada atau tekan loadmore";
		}
		else 
			$ket = "menampilkan yang sudah ada dan bukan load more";
		$array =[];
		$j = 0;
		foreach ($json[6]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'] as $pages)
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
		$banyakitems = $json[6]['katakunci'][$indexkeywords]['kategori'][$kategori]['pagecount'];
		echo json_encode(array("arr" => $array, "mp" => "6", "json" => $json, "kasus" => '1', "kata" => $kata, "kategori" => $kategori, "page" =>$itemterakhir[1], "pagecount" => $banyakitems));
		
	}
	else 
	{
	echo "gagal";
	}

                     ?>  