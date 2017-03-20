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
		foreach ($json[0]['katakunci'] as $item)
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
				$json[0]['katakunci'][$indexkeyword]['keyword'] = $kata;
				$indexkeywords = $indexkeyword;
				$pageurl="";
				$new = true;
			}
			else //berarti ada kecocokan
			{
				$kets = "keyword ada";
				if (isset($json[0]['katakunci'][$indexkeywords]['kategori'][$kategori]) == false)  //berarti nggak ada kategori
				{
					$page = 1;
					$pageurl="";
					$new = true;
					$kets = "keyword ada tapi kategori nggak, page = ".$page;
				}
				else
				{
					
					$page =  count($json[0]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal']); 
					$kets = "keyword dan kategori ada, page = ".$page;
					$new = false; //new = false, dia cuma menampilkan yang pernah dibuka					
				}
			}					
		}
		else //berarti tekan load more. keyword berdasarkan table yang aktif.
		{
			$page =  count($json[0]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'])+1;
			$pageurl = "&page=".$page;
			$new = true;
			$kets = "tekan loadmore, page = ".$page;
		}
		if ($new == true) //buat baru, kata nggak ada, kategori nggak ada atau tekan loadmore
		{
			//$kets = "masuk ke curl";
			$strings = str_replace(" ","+",$kata);
			curl_setopt($ch2, CURLOPT_URL, $strings);
			$ch2 = curl_init('http://www.lazada.co.id/catalog/?q='.$strings);
			curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
			curl_exec($ch2);
			if ($kategori==0)
				$tambahan = '&sort=priceasc';
			else
				$tambahan = '';
			if (!curl_errno($ch2)) 
			{
				$info = curl_getinfo($ch2);
				if ($info['redirect_url']!="")
					$redirect = $info['redirect_url'].$tambahan.$pageurl;
				else
					$redirect = $info['url'].$tambahan.$pageurl;
			}
			if ($redirect == "")
				$redirect = 'http://www.lazada.co.id/catalog/?q='.$strings.$tambahan.$pageurl;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, $redirect);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$result=curl_exec($ch);
			curl_close($ch);
			curl_close($ch2);
			$pecah = explode('<script type="application/ld+json">',$result);
			$pecah2 = explode ('</script>',$pecah[1]);
			$json1 = json_decode($pecah2[0],true);
			$json[0]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'] = [];
			$i = 0;
			foreach($json1['itemListElement'] as $item)
			{
				$json[0]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$i]['gambar'] = $item['image'];
				$json[0]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$i]['nama'] = $item['name'];
				$json[0]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'][$page-1]['items'][$i]['harga'] = $item['offers']['price'];
				$i++;
			}
			$ket = "keyword tidak ada atau kategori tidak ada atau tekan loadmore";
		}
		else 
			$ket = "menampilkan yang sudah ada dan bukan load more";
		$array =[];
		$j = 0;
		foreach ($json[0]['katakunci'][$indexkeywords]['kategori'][$kategori]['hal'] as $pages)
		{
			foreach ($pages['items'] as $items)
			{
				$array[$j]->gambar =  $items['gambar'];
				$array[$j]->nama = $items['nama'];
				$array[$j]->harga = "Rp. ".$items['harga'];	
				$j++;
			}
		}

		echo json_encode(array("arr" => $array, "mp" => "0", "json" => $json, "link" => $pecah[1], "kata" => $kata, "kategori" => $kategori, "page" =>$page));
		
	}
	else 
	{
	echo "gagal";
	}

                     ?>  