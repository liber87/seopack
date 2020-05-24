<?php
	if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}	
	
	if (file_exists(MODX_BASE_PATH."assets/modules/seopack/configs/general.config.php"))
	{
		require_once(MODX_BASE_PATH."assets/modules/seopack/configs/general.config.php");
	}	
	else return; //Если конфиг не создан, то ничего не делаем.
	use WebPConvert\WebPConvert;
	//Actions
	switch($modx->Event->name)
	{
		case 'OnWebPagePrerender':
		extract($general);
		if (!function_exists('compress_html'))
		{
			function compress_html($compress)
			{
				$compress = str_replace("\n", '', $compress);
				$compress = str_replace("\s", '', $compress);
				$compress = str_replace("\r", '', $compress);
				$compress = str_replace("\t", '', $compress);
				$compress = preg_replace('/(?:(?<=\>)|(?<=\/\>))\s+(?=\<\/?)/', '', $compress);
				$compress = preg_replace('/[\t\r]\s+/', ' ', $compress);
				$compress = preg_replace('/<!(--)([^\[|\|])^(<!-->.*<!--.*-->)/', '', $compress);
				$compress = preg_replace('/\/\*.*?\*\//', '', $compress);
				return preg_replace("#\\s+#ism"," ",$compress);
			}
		}
		
		if (!function_exists('de'))
		{
			function de($agent)
			{
				$engines = array(
				array('Aport', 'Aport'),
				array('Google', 'Google'),
				array('msnbot', 'MSN'),
				array('Rambler', 'Rambler'),
				array('Yahoo', 'Yahoo'),
				array('Yandex', 'Yandex'),
				array('Aport', 'Aport robot'),
				array('Google', 'Google'),
				array('msnbot', 'MSN'),
				array('Rambler', 'Rambler'),
				array('Yahoo', 'Yahoo'),
				array('AbachoBOT', 'AbachoBOT'),
				array('accoona', 'Accoona'),
				array('AcoiRobot', 'AcoiRobot'),
				array('ASPSeek', 'ASPSeek'),
				array('CrocCrawler', 'CrocCrawler'),
				array('Dumbot', 'Dumbot'),
				array('FAST-WebCrawler', 'FAST-WebCrawler'),
				array('GeonaBot', 'GeonaBot'),
				array('Gigabot', 'Gigabot'),
				array('Lycos', 'Lycos spider'),
				array('MSRBOT', 'MSRBOT'),
				array('Scooter', 'Altavista robot'),
				array('AltaVista', 'Altavista robot'),
				array('WebAlta', 'WebAlta'),
				array('IDBot', 'ID-Search Bot'),
				array('eStyle', 'eStyle Bot'),
				array('Mail.Ru', 'Mail.Ru Bot'),
				array('Scrubby', 'Scrubby robot'),
				array('Yandex', 'Yandex')
				);
				
				foreach ($engines as $engine)
				{
					if (stristr($agent, $engine[0]))
					{
						return($engine[1]);
					}
				}
				return (false);
			}
		}
		
		
		$content = $modx->Event->params['documentOutput'];
		require_once MODX_BASE_PATH.'assets/lib/simple_html_dom.php';
		$html = new simple_html_dom();
		$html->load($content, false, null, -1, -1, true, true, DEFAULT_TARGET_CHARSET, false);
		
		$title = $html->find('title',0);
		$metaTitle = str_replace('"',"'",$title->plaintext); // Чтобы не искать потом - заголовок страницы
		$imagealt = $metaTitle.' '.$image_name.' '; //в конце подставляется порядковый номер начиная с 1
		$atitle = $metaTitle.' '.$link_name.' '; //в конце подставляется порядковый номер начиная с 1
		
		$content = $modx->Event->params['documentOutput'];
		require_once MODX_BASE_PATH.'assets/lib/simple_html_dom.php';
		
		if ($link_title=='true')
		{
			//Проставляем тэг title для ссылок
			$links = $html->find("a");
			$ln = 1;
			foreach ($links as $key => $link)
			{
				if (!$link->title)
				{
					if ($link->plaintext)
					{
						$t = str_replace('"',"'",trim(mb_substr($link->plaintext,0,50)));
						if (strlen($t)<10)
						{
							$link->title = $atitle.' '.$ln;
							$ln++;
						}
						else $link->title = $t;
					}
					else
					{
						$link->title = $atitle.' '.$ln;
						$ln++;
					}
				}
			}
		}
		if ($circle_link=='true')
		{
			//Убираем циклические ссылки
			$url = substr($_SERVER['REQUEST_URI'], 1);
			if ($url)
			{
				$cu =  $html->find("a[href='".$url."']");
				foreach($cu as $u) $u->href = null;
			}
		}
		if ($external_link=='true')
		{
			//Закрываем внешние ссылки
			$outlink = $html->find("a[href^=http]");
			foreach($outlink as $ou)
			{
				if (strpos($ou->href, MODX_SITE_URL)===false)
				{
					$ou->href = MODX_SITE_URL.''.$error_page.'?url='.$ou->href;
				}
			}
		}
		
		if ($img_alt=='true')
		{
			//Проставляем тэг alt для картинок
			$imgs = $html->find("img");
			$ln = 1;
			foreach ($imgs as $key => $img)
			{
				if (!$img->alt)
				{
					$img->alt = $imagealt.' '.$ln;
					$ln++;
				}
			}
		}
		
		
		if ($webp=='true')
		{
			if (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false
			|| (strpos($_SERVER['HTTP_USER_AGENT'], ' Safari/') !== false
            && strpos($_SERVER['HTTP_USER_AGENT'], ' Version/') === false) || strpos($_SERVER['HTTP_USER_AGENT'], ' Safari/') === false) {
				
				if (class_exists('\WebPConvert\WebPConvert'))
				{
					//
					$imgs = $html->find("img");
					foreach ($imgs as $key => $img)
					{
						
						
						if (!file_exists(MODX_BASE_PATH.$img->src.'.webp'))
						{
							if ((strpos($img->src, '://') === false) && ($img->src))
							{
								$image = MODX_BASE_PATH.$img->src;
								$destination =  MODX_BASE_PATH.$img->src.'.webp';								
								if (WebPConvert::convert($image, $destination)) $img->src = $img->src.'.webp';
							}
						}
						else $img->src = $img->src.'.webp';
					}
				}
			}
		}
		
		
		$content = $html->save();
		$html->clear();
		
		if ($one_line=='true') $content = compress_html($content); 			
		
		
		if ($favicon_generate=='true')
		{			
			$favicon = $modx->runSnippet('FaviconGenerator',array('bg'=>$bg,'img'=>$fav_img,'ico'=>1));					
			$content = str_replace('</head>',$favicon.PHP_EOL.'</head>',$content);
		}
		if ($ctrlf5=='true')
		{
			$base = $modx->getConfig['site_url'];
			$files_origin = array();
			$files_new = array();
			$expansion='css,js,jpeg,jpg,png,webp';
			
			preg_match_all('/(link|href)=("|\')[^"\'>]+/i', $content, $media);
			$data = preg_replace('/(link|href)("|\'|="|=\')(.*)/i', "$3", $media[0]);
			foreach ($data as $url)
			{
				$a = substr(strrchr($url, '.'), 1);
				$b = explode('?',$a);
				$ex = explode('/',$b[0]);
				$fo = explode('?',$url);
				$file_name = str_replace($base,'',$fo[0]);
				
				if (in_array(mb_strtolower($ex[0]),explode(',',$expansion)))
				{
					$fa = @stat($file_name);
					if ($fa)
					{
						$files_origin[] = $url;
						$files_new[] = $fo[0].'?v='.$fa['mtime'];
					}
				}
			}
			
			
			preg_match_all('/(script|img||src)=("|\')[^"\'>]+/i', $content, $media);
			$data = preg_replace('/(script|src)("|\'|="|=\')(.*)/i', "$3", $media[0]);
			foreach ($data as $url)
			{
				$a = substr(strrchr($url, '.'), 1);
				$b = explode('?',$a);
				$ex = explode('/',$b[0]);
				$fo = explode('?',$url);
				$file_name = str_replace($base,'',$fo[0]);
				
				if (in_array(mb_strtolower($ex[0]),explode(',',$expansion)))
				{
					$fa = @stat($file_name);
					if ($fa)
					{
						$files_origin[] = $url;
						$files_new[] = $fo[0].'?v='.$fa['mtime'];
					}
				}
			}
			$content = str_replace($files_origin,$files_new,$content);
		}
		
		//Fix for canonical
		if (file_exists(MODX_BASE_PATH."assets/modules/seopack/configs/short_url.config.php"))
		{
			require_once(MODX_BASE_PATH."assets/modules/seopack/configs/short_url.config.php");
			
			if ((is_array($short_url)) && (count($short_url)))
			{
				$uri = $_SERVER['REQUEST_URI'];				
				$canonical = array_search($uri, $short_url);							
				if ($canonical) $content = str_replace('</head>','<link rel="canonical" href="'.$modx->config('site_url').$canonical.'"/>'.PHP_EOL.'</head>',$content);
			}
		}
		
		if ($general['code304']=='true')
		{
			
			$detect = de($_SERVER['HTTP_USER_AGENT']);
			
			if ($detect)
			{				
				$last_modified_time = $modx->documentObject['editedon'];							
				if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $last_modified_time){					
					header('HTTP/1.1 304 Not Modified');					
					die;
					}       				
					
					header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_modified_time).' GMT'); 
					
				}
			}
			
			$modx->event->output($content);
			break;
			
			
			
			case 'OnDocFormSave':
			case 'OnDocDuplicate':
			
			if (!isset ($updateid)) { $updateid = '1'; }		  
			$id = ($_POST['id'])? $_POST['id'] : $e->params['id'];		
			$getParentIds = $modx->getParentIds($id);		
			$getParentIds = array_merge( $getParentIds, (explode(',',$updateid)) );		
			$getParentIds = array_unique($getParentIds);
			
			foreach ($getParentIds as $getParentId)
			{	
				$table = $modx->getFullTableName( 'site_content' );  			
				$fields = array('editedon'  => time() );			
				$result = $modx->db->update( $fields, $table, 'id = "' . $getParentId . '"' );
			}
			
			
			break;
			
			
			case 'OnPageNotFound':
			$q = $modx->db->escape($_REQUEST['q']);
			if (isset($_GET['url']) && (!empty($_GET['url'])) && ($q==$general['error_page']))
			{
				$pos = strpos($_SERVER['HTTP_REFERER'], $_SERVER["HTTP_HOST"]);
				
				if ($pos)
				{
					$url = $_GET['url'];
					if (!preg_match('#(https?|ftp)://\S+[^\s.,>)\];\'\"!?]#i',$url))
					{
						exit ("<p>Неверный формат запроса! Проверьте URL!</p>");
					}
					else
					{
						header("Location:$url");
						exit();
					}
				}
				else
				{
					die('fuck you, hacker fucking');
				}
			}
			$q = $modx->db->escape($_REQUEST['q']);
			
			//Short url
			if (file_exists(MODX_BASE_PATH."assets/modules/seopack/configs/short_url.config.php"))
			{
				require_once(MODX_BASE_PATH."assets/modules/seopack/configs/short_url.config.php");
				if ($short_url[$q])
				{
					$site = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/';
					$url = $site.$short_url[$q];
					$html = file_get_contents($url);
					header("HTTP/1.1 200 OK");
					echo $html;
					exit();
				}
			}
			
			//Redirect Map
			if (file_exists(MODX_BASE_PATH."assets/modules/seopack/configs/redirect.config.php"))
			{
				require_once(MODX_BASE_PATH."assets/modules/seopack/configs/redirect.config.php");
				if ($map[$q])
				{
					$modx->sendRedirect($map[$q],0,'REDIRECT_HEADER','HTTP/1.1 301 Moved Permanently');
					exit();
				}
			}
			break;
			
			case 'OnManagerMenuPrerender':
			
			$moduleid = $modx->db->getValue($modx->db->select('id', $modx->getFullTablename('site_modules'), "name = 'SeoPack'"));
			$params['menu']['seopack'] = [
			'seopack',
			'main',
			'<i class="fa fa-cog"></i> SEO',
			'index.php?a=112&id=' . $moduleid,
			'SEO',
			'',
			'',
			'main',
			0,
			100,
			'',
			];
			$modx->Event->output(serialize($params['menu']));
			return;
			break;
		}
		
