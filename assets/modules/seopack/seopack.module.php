<?php
	if(!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true){ die();}
	if (!class_exists('TransAlias')) {
		require_once MODX_BASE_PATH.'assets/plugins/transalias/transalias.class.php';
	}
	if (file_exists(MODX_BASE_PATH."assets/modules/seopack/configs/short_url.config.php"))
	{
		require_once(MODX_BASE_PATH."assets/modules/seopack/configs/short_url.config.php");
	}
	if (file_exists(MODX_BASE_PATH."assets/modules/seopack/configs/general.config.php"))
	{
		require_once(MODX_BASE_PATH."assets/modules/seopack/configs/general.config.php");
	}
	if (file_exists(MODX_BASE_PATH."assets/modules/seopack/configs/redirect.config.php"))
	{
		require_once(MODX_BASE_PATH."assets/modules/seopack/configs/redirect.config.php");
	}	
	if (file_exists(MODX_BASE_PATH."assets/modules/seopack/lang/".$modx->config['manager_language'].".php"))
	{
		require_once(MODX_BASE_PATH."assets/modules/seopack/lang/".$modx->config['manager_language'].".php");
	}
	else require_once(MODX_BASE_PATH."assets/modules/seopack/lang/russian-UTF8.php.php"); 
	
	
	switch($_POST['act'])
	{
		case 'short_url':
		$short_url = array();
		foreach($_POST['url'] as $key => $val)
		{
			if (($_POST['url'][$key]) && ($_POST['short_url'][$key])) $short_url[$_POST['short_url'][$key]] = $_POST['url'][$key];
		}
		$text = "<?php".PHP_EOL.'$short_url='.var_export($short_url,1).';';
		$f=fopen(MODX_BASE_PATH."assets/modules/seopack/configs/short_url.config.php",'w');
		fwrite($f,$text);
		fclose($f);
		break;
		
		
		case 'general':
		unset($_POST['act']);
		$text = "<?php".PHP_EOL.'$general='.var_export($_POST,1).';';
		$f=fopen(MODX_BASE_PATH."assets/modules/seopack/configs/general.config.php",'w');
		fwrite($f,$text);
		fclose($f);
		break;
		
		case 'redirect':
		$map = array();
		foreach($_POST['old_url'] as $key => $val)
		{
			if (($_POST['old_url'][$key]) && ($_POST['new_url'][$key]))
			{
				if (is_numeric($_POST['new_url'][$key])) $_POST['new_url'][$key] = $modx->makeUrl($_POST['new_url'][$key]);
				$map[$_POST['old_url'][$key]] = $_POST['new_url'][$key];
			}
		}
		$text = "<?php".PHP_EOL.'$map='.var_export($map,1).';';
		
		$f=fopen(MODX_BASE_PATH."assets/modules/seopack/configs/redirect.config.php",'w');
		fwrite($f,$text);
		fclose($f);
		break;
		
		case 'robots':
		$f=fopen(MODX_BASE_PATH."robots.txt",'w');
		fwrite($f,$_POST['robots']);
		fclose($f);
		break;
		
		case 'set_error_page':
		$tpl = $modx->config('default_template');
		include_once(MODX_BASE_PATH."assets/lib/MODxAPI/modResource.php");
		$doc = new modResource($modx);
		$doc->create(array(
		'pagetitle' => 'Страница не найдена!',
		'template' => $tpl,
		'parent' => 0,
		'content'=>'<p>Страница не найдена! Передите на <a href="./">главную страницу.</a></p>',
		'menuindex'=>'999999'
		));
		$id = $doc->save(true, false);
		$modx->db->update(array('setting_value'=>$id),$modx->getFullTableName('system_settings'),'setting_name="error_page"');
		break;
		
		case 'set_site_map':
		$snippet = $modx->db->getValue('Select count(*) from '.$modx->getFullTableName('site_snippets').' where name="sitemap"');
		if (!$snippet)
		{
			$category = $modx->db->getValue('Select id from '.$modx->getFullTableName('categories').' where category="SEO"');
			$code = file_get_contents('https://raw.githubusercontent.com/extras-evolution/sitemap/master/assets/snippets/sitemap/snippet.sitemap.php');
			$modx->db->insert(array('name'=>'sitemap','description'=>'<strong>1.0.11</strong> google-sitemap.xml','code'=>$modx->db->escape($code),'category' => $category),$modx->getFullTableName('site_snippets'));
		}
		include_once(MODX_BASE_PATH."assets/lib/MODxAPI/modResource.php");
		$doc = new modResource($modx);
		$doc->create(array(
		'pagetitle' => 'sitemap',
		'alias' => 'sitemap.xml',
		'template' => 0,			
		'parent' => 0,
		'contentType' => 'text/xml',
		'content'=>'[[sitemap]]',
		'menuindex'=>'999998'
		));
		$doc->save(true, false);		
		break;
		
		case 'rename_robots_txt':
		rename(MODX_BASE_PATH.'sample-robots.txt',MODX_BASE_PATH.'robots.txt');
		break;
		
		case 'create_robots_txt':
		$txt = file_get_contents('https://raw.githubusercontent.com/evolution-cms/evolution/2.0.x/sample-robots.txt');
		$f=fopen(MODX_BASE_PATH."robots.txt",'w');
		fwrite($f,$txt);
		fclose($f);
		break;
		
		case 'MassAction':
		if ($_POST['template']) $template = 'c.template = '.$_POST['template'];
		$modx->runSnippet(
		'DocLister',
		array(
        'saveDLObject' => '_DL',
		'parents'=>$_POST['parent'],
		'depth'=>$_POST['depth'],
		'addWhereList'=>$template
		)
		);
		$_DL = $modx->getPlaceholder('_DL');
		$ids = $_DL->getOneField('id');
		if (count($ids))
		{
			include_once(MODX_BASE_PATH."assets/lib/MODxAPI/modResource.php");
			foreach($ids as $id)
			{
				$doc = new modResource($modx);
				$doc->edit($id);
				foreach($_POST['tv'] as $key=>$val)$doc->set($_POST['tv'][$key], $_POST['value'][$key]);					
				$doc->save(true, false);
				unset($doc);	
			}
		}
		
		break;
	}
	
	if ($_POST['act']) header("Refresh: 0");	
	
?>
<html>
	<head>
		<title><?=$_lang['evopack_module_title'];?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<script src="media/script/jquery/jquery.min.js" type="text/javascript"></script>
		<script type="text/javascript" src="media/script/tabpane.js"></script>
		<meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width" />
		<meta http-equiv="Content-Type" content="text/html; charset=<?=$modx->config['modx_charset'];?>" />
		<link rel="stylesheet" type="text/css" href="<?=$modx->config['site_manager_url'];?>media/style/default/css/styles.min.css" />
		<style>
			.text-primary,td{font-size: 0.8125rem !important; cursor:ponter;}
		</style>
	</head>
	<body class="sectionBody">
		<h1><i class="fa fa-th"></i>Seo-пакет</h1>
		<div id="actions">
			<div class="btn-group">
				<div class="btn-group dropdown">
					<a id="Button1" class="btn btn-success save" href="javascript:;">
						<i class="fa fa-floppy-o"></i><span>Сохранить</span>
					</a>
				</div>
			</div>
		</div>
		<div class="tab-pane " id="docManagerPane">
			<script type="text/javascript">
				tpResources = new WebFXTabPane(document.getElementById('docManagerPane'));
			</script>
			
			<div class="tab-page" id="tabGeneral">
				<h2 class="tab"><i class="fa fa-home"></i> <?php echo $lang['general'];?></h2>
				<script type="text/javascript">tpResources.addTabPage(document.getElementById('tabGeneral'));</script>
				<div class="tab-body">
					<form id="general_seo" method="post" action="">
						<input type="hidden" name="act" value="general">
						<table width="100%" class="displayparams grid">
							<thead>
								<tr>
									<td>Параметр</td>
									<td>Значение</td>
								</tr>
							</thead>
							<tbody>
								<tr class="">
									<?php
										if ($modx->config['error_page']==$modx->config['site_start'])
										{
										?>
										<td class="labelCell" width="20%">
											<span class="paramLabel">Страница 404</span>
											<span class="paramDesc">
											</span>
										</td>
										<td class="inputCell relative" width="74%">
											Ссылается на стартовую страницу. <a href="javascript:$('#set_error_page').submit();">Исправить?</a>
										</td>
									<? }?>
								</tr>
								<?php
									$site_map = $modx->db->getValue('Select count(*) from '.$modx->getFullTableName('site_content').' where alias ="sitemap.xml"');
									if (!$site_map) {
									?>
									<tr class="">
										<td class="labelCell" width="20%">
											<span class="paramLabel">Наличие sitemap:</span>
											<span class="paramDesc">
											</span>
										</td>
										<td class="inputCell relative" width="74%">
											Отсутствует. <a href="javascript:$('#set_site_map').submit();">Установить?</a>
										</td>
									</tr>
									<?php } 
									$existence_sitemap_in_robots = true;
									if (!file_exists(MODX_BASE_PATH.'robots.txt'))
									{
										$existence_sitemap_in_robots = false;
										if (file_exists(MODX_BASE_PATH.'sample-robots.txt')) $esir_txt = 'Файл robots.txt отсутствует. <a href="javascript:$(\'#rename_robots_txt\').submit()">Переименовать sample-robots.txt в robots.txt?</a>';
										else $esir_txt = 'Файл robots.txt отсутствует. <a href="javascript:$(\'#create_robots_txt\').submit()">Создать?</a>';
									}				
									else
									{
										
									}
									if (!$existence_sitemap_in_robots)
									{
									?>
									<tr class="">
										<td class="labelCell" width="20%">
											<span class="paramLabel">Наличие записии о sitemap в robots.txt</span>
											<span class="paramDesc">
											</span>
										</td>
										<td class="inputCell relative" width="74%">
											<? echo $esir_txt;?>
										</td>
									</tr>
									<?
									}
								?>
								
								<tr><td colspan="2" style="background-color: #d4f5ff;">Работа с изображениями</td></tr>
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Подставлять Alt к картинкам</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<select name="img_alt">
											<option value="true" <?php if ($general['img_alt']=='true') echo 'selected="selected"';?>>true</option>
											<option value="false" <?php if ($general['img_alt']!='true') echo 'selected="selected"';?>>false</option>
										</select>
									</td>
								</tr>
								<tr class="img_alt_block">
									<td class="labelCell" width="20%">
										<span class="paramLabel">Что подставлять к картинке?</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<input type="text" name="image_name" value="<?=$general['image_name'];?>">
									</td>
								</tr>
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Использовать формат WEBP для всех картинок</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<select name="webp">
											<option value="true" <?php if ($general['webp']=='true') echo 'selected="selected"';?>>true</option>
											<option value="false" <?php if ($general['webp']!='true') echo 'selected="selected"';?>>false</option>
										</select>
										<?php
											if (!class_exists('\WebPConvert\WebPConvert'))
											{	
												echo '<span style="color:red;">Не установлена библиотека <a href="https://github.com/rosell-dk/webp-convert/" target="_blank">WebPConvert</a>!</span>';
											}
										?>
									</td>
								</tr>
								
								<tr><td colspan="2" style="background-color: #d4f5ff;">Работа с ссылками</td></tr>
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Подставлять Title к ссылкам</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<select name="link_title">
											<option value="true" <?php if ($general['link_title']=='true') echo 'selected="selected"';?>>true</option>
											<option value="false" <?php if ($general['link_title']!='true') echo 'selected="selected"';?>>false</option>
										</select>
									</td>
								</tr>
								<tr class="link_title_block">
									<td class="labelCell" width="20%">
										<span class="paramLabel">Что подставлять к ссылке?</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<input type="text" name="link_name" value="<?php echo $general['link_name'];?>">
									</td>
								</tr>
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Убирать циклические ссылки?</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<select name="circle_link">
											<option value="true" <?php if ($general['circle_link']=='true') echo 'selected="selected"';?>>true</option>
											<option value="false" <?php if ($general['circle_link']!='true') echo 'selected="selected"';?>>false</option>
										</select>
									</td>
								</tr>
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Убирать внешние ссылки?</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<select name="external_link">
											<option value="true" <?php if ($general['external_link']=='true') echo 'selected="selected"';?>>true</option>
											<option value="false" <?php if ($general['external_link']!='true') echo 'selected="selected"';?>>false</option>
										</select>
									</td>
								</tr>
								<tr class="external_link_block">
									<td class="labelCell" width="20%">
										<span class="paramLabel">Внешняя страница выхода</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<input type="text" name="error_page" value="<?=$general['error_page'];?>">
									</td>
								</tr>
								
								
								<tr><td colspan="2" style="background-color: #d4f5ff;">Работа с Favicon</td></tr>
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Генерирвать фавикон?</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<select name="favicon_generate">
											<option value="true" <?php if ($general['favicon_generate']=='true') echo 'selected="selected"';?>>true</option>
											<option value="false" <?php if ($general['favicon_generate']!='true') echo 'selected="selected"';?>>false</option>
										</select>
									</td>
								</tr>
								<tr class="favicon_generate_block">
									<td class="labelCell" width="20%">
										<span class="paramLabel">Фон для favicon</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<input type="text" name="bg" value="<?=$general['bg'];?>">
									</td>
								</tr>
								<tr  class="favicon_generate_block">
									<td class="labelCell" width="20%">
										<span class="paramLabel">Картинка для фавикона</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<div style="position:relative;">	
											<input type="text" name="img" id="img" value="<?=$general['img'];?>" class="imageField">
											<input type="button" value="Вставить" onclick="BrowseServer('img')" style="position: absolute; right: 0;">
										</div>	
										
									</td>
								</tr>
								
								<tr><td colspan="2" style="background-color: #d4f5ff;">Прочее</td></tr>
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Вытягивать код в одну строку?</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<select name="one_line">
											<option value="true" <?php if ($general['one_line']=='true') echo 'selected="selected"';?>>true</option>
											<option value="false" <?php if ($general['one_line']!='true') echo 'selected="selected"';?>>false</option>
										</select>
									</td>
								</tr>
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Отслеживать версии файлов</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<select name="ctrlf5">
											<option value="true"  <?php if ($general['ctrlf5']=='true') echo 'selected="selected"';?>>true</option>
											<option value="false" <?php if ($general['ctrlf5']!='true') echo 'selected="selected"';?>>false</option>
										</select>
									</td>
								</tr>
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Подставлять Canonical для страниц с пагинацией</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<select name="paginate">
											<option value="true"  <?php if ($general['paginate']=='true') echo 'selected="selected"';?>>true</option>
											<option value="false" <?php if ($general['paginate']!='true') echo 'selected="selected"';?>>false</option>
										</select>
									</td>
								</tr>
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Отдавать ответ 304 роботам</span>
										<span class="paramDesc">
										</span>
									</td>
									<td class="inputCell relative" width="74%">
										<select name="code304">
											<option value="true"  <?php if ($general['code304']=='true') echo 'selected="selected"';?>>true</option>
											<option value="false" <?php if ($general['code304']!='true') echo 'selected="selected"';?>>false</option>
										</select>
									</td>
								</tr>
							</tbody>
						</table>
					</form>
				</div>
			</div>
			
			<div class="tab-page" id="tabMassAction">
				<h2 class="tab"><i class="fa fa-home"></i> Массовое изменение полей</h2>
				<script type="text/javascript">tpResources.addTabPage(document.getElementById('tabMassAction'));</script>
				<div class="tab-body">
					<form method="post" id="MassAction">
						<input type="hidden" name="act" value="MassAction">
						<table width="100%" class="displayparams grid">
							<thead>
								<tr>
									<td>Параметр</td>
									<td colspan="3">Значение</td>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Родитель</span>										
									</td>
									<td class="inputCell relative" width="74%"  colspan="3">
										<input type="text" name="parent" value="" requried="requried">
									</td>
								</tr>								
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Глубина</span>										
									</td>
									<td class="inputCell relative" width="74%" colspan="3">
										<input type="number" name="depth" value="">
									</td>
								</tr>
								<tr>
									<td class="labelCell" width="20%">
										<span class="paramLabel">Шаблон</span>
									</td>
									<td class="inputCell relative" width="74%" colspan="3">
										<select name="template">
											<option value="">Любой</option>
											<?php
												$res = $modx->db->query('Select id,templatename from '.$modx->getFullTableName('site_templates'));
												while ($row = $modx->db->getRow($res)) echo '<option value="'.$row['id'].'">'.$row['templatename'].'</option>';
											?>
										</select>
									</td>
								</tr>								
								<tr>
									<td class="labelCell">
										<span class="paramLabel"></span>					
									</td>
									<td class="inputCell relative" >
										<select name="tv[]">
											<optgroup label="SEO">
												<?php
													$res = $modx->db->query('SELECT name,caption FROM '.$modx->getFullTableName('site_tmplvars').' as tv
													left join '.$modx->getFullTableName('categories').' as cat
													on tv.category = cat.id
													where cat.category="SEO"');
													while ($row = $modx->db->getRow($res)) echo '<option value="'.$row['name'].'">'.$row['caption'].'</option>';
												?>
											</optgroup>
											<optgroup label="Другие ТВ-поля">
												<?php
													$res = $modx->db->query('SELECT name,caption FROM '.$modx->getFullTableName('site_tmplvars').' as tv
													left join '.$modx->getFullTableName('categories').' as cat
													on tv.category = cat.id
													');
													while ($row = $modx->db->getRow($res)) echo '<option value="'.$row['name'].'">'.$row['caption'].'</option>';
												?>
											</optgroup>
											<optgroup label="Поля документа">
												<?php
													$fields = array('pagetitle'=>'Заголовок','longtitle'=>'Расширенный заголовок','introtext'=>'Описание','content'=>'Содержимое ресурса');
													foreach($fields as $key=>$val)  echo '<option value="'.$key.'">'.$val.'</option>'; 
												?>
											</optgroup>
										</select>
									</td>
									<td class="inputCell relative">
										<input type="text" name="value[]" placeholder="Значение">
									</td>
									<td class="inputCell relative" style="width:1px;">
										<a href="javascript:;" class="btn btn-warning btn-minus"><i class="fa fa-remove"></i></a>
										<a href="javascript:;" class="btn btn-success btn-plus-mass"><i class="fa fa-plus"></i></a>
									</td>
								</tr>
								
								
							</tbody>
						</table>
					</form>
				</div>
			</div>
			<div class="tab-page" id="tabShortUrl">
				<h2 class="tab"><i class="fa fa-list-alt"></i> Короткие ссылки</h2>
				<script type="text/javascript">tpResources.addTabPage(document.getElementById('tabShortUrl'));</script>
				<div class="tab-body">
					<form method="post" id="short_url">
						<input type="hidden" name="act" value="short_url">
						<table width="100%" class="displayparams grid">
							<thead>
								<tr>
									<td>Полная ссылка</td>
									<td>Короткая ссылка</td>
									<td></td>
								</tr>
							</thead>
							<tbody>
								<?php
									if (count($short_url))
									{
										foreach($short_url as $key => $item)
										{
											echo '<tr>
											<td class="inputCell relative" width="60%">
											<input type="text" name="url[]" value="'.$item.'">
											</td>
											<td class="inputCell relative" width="20%">
											<input type="text" name="short_url[]" value="'.$key.'">
											</td>
											<td class="inputCell relative text-center" width="10%">
											<a href="javascript:;" class="btn btn-warning btn-minus"><i class="fa fa-remove"></i></a>
											<a href="javascript:;" class="btn btn-success btn-plus-link"><i class="fa fa-plus"></i></a>
											</td>
											</tr>';
										}
									}
								?>
								<tr>
									<td class="inputCell relative" width="60%">
										<input type="text" name="url[]" value="">
									</td>
									<td class="inputCell relative" width="30%">
										<input type="text" name="short_url[]" value="">
									</td>
									<td class="inputCell relative text-center" width="10%">
										<a href="javascript:;" class="btn btn-warning btn-minus"><i class="fa fa-remove"></i></a>
										<a href="javascript:;" class="btn btn-success btn-plus-link"><i class="fa fa-plus"></i></a>
									</td>
								</tr>
							</tbody>
						</table>
					</form>
				</div>
			</div>
			<div class="tab-page" id="tabRedirectMap">
				<h2 class="tab"><i class="fa fa-newspaper-o"></i> Карта перенаправлений</h2>
				<script type="text/javascript">tpResources.addTabPage(document.getElementById('tabRedirectMap'));</script>
				<div class="tab-body">
					<form method="post" id="robots">
						<input type="hidden" name="act" value="redirect">
						<table width="100%" class="displayparams grid">
							<thead>
								<tr>
									<td>Старая ссылка</td>
									<td>Новая ссылка (или id документа)</td>
									<td></td>
								</tr>
							</thead>
							<tbody>
								<?php
									if (is_array($map) && (count($map)))
									{
										foreach($map as $key => $item)
										{
											echo '<tr>
											<td class="inputCell relative" width="45%">
											<input type="text" name="old_url[]" value="'.$key.'">
											</td>
											<td class="inputCell relative" width="45%">
											<input type="text" name="new_url[]" value="'.$item.'">
											</td>
											<td class="inputCell relative text-center" width="10%">
											<a href="javascript:;" class="btn btn-warning btn-minus"><i class="fa fa-remove"></i></a>
											<a href="javascript:;" class="btn btn-success btn-plus-url"><i class="fa fa-plus"></i></a>
											</td>
											</tr>		';
										}
									}
								?>
								<tr>
									<td class="inputCell relative" width="45%">
										<input type="text" name="old_url[]" value="">
									</td>
									<td class="inputCell relative" width="45%">
										<input type="text" name="new_url[]" value="">
									</td>
									<td class="inputCell relative text-center" width="10%">
										<a href="javascript:;" class="btn btn-warning btn-minus"><i class="fa fa-remove"></i></a>
										<a href="javascript:;" class="btn btn-success btn-plus-url"><i class="fa fa-plus"></i></a>
									</td>
								</tr>
							</tbody>
						</table>
					</form>
				</div>
			</div>
			<div class="tab-page" id="tabRobots">
				<h2 class="tab"><i class="fa fa-newspaper-o"></i> Robots.txt</h2>
				<script type="text/javascript">tpResources.addTabPage(document.getElementById('tabRobots'));</script>
				<div class="tab-body">
					<form method="post" id="robots">
						<input type="hidden" name="act" value="robots">
						<textarea cols="40" rows="30" style="width:100%" name="robots"><?php
							if (file_exists(MODX_BASE_PATH.'robots.txt'))
							{
								$txt = file_get_contents(MODX_BASE_PATH.'robots.txt');
								echo $txt;
							}
							else echo '#file robots.txt not found';
						?></textarea>
					</form>
				</div>
			</div>
			
		</div>
		<div style="display:none;">
			<form method="post" action="" id="set_error_page">
				<input type="hidden"  name="act" value="set_error_page">
			</form>
			
			<form method="post" action="" id="set_site_map">
				<input type="hidden"  name="act" value="set_site_map">
			</form>
			
			<form method="post" action="" id="rename_robots_txt">
				<input type="hidden"  name="act" value="rename_robots_txt">
			</form>
			
			<form method="post" action="" id="create_robots_txt">
				<input type="hidden"  name="act" value="create_robots_txt">
			</form>			
		</div>
		<script>
			$(document).on('click','.btn-plus-link',function(){
				var tr = '<tr><td class="inputCell relative" width="60%"><input type="text" name="url[]" value=""></td><td class="inputCell relative" width="30%"><input type="text" name="short_url[]" value=""></td><td class="inputCell relative text-center" width="10%"><a href="javascript:;" class="btn btn-warning"><i class="fa fa-remove btn-minus"></i></a><a href="javascript:;" class="btn btn-success btn-plus-link"><i class="fa fa-plus"></i></a></td></tr>';
				$(this).closest('tbody').append(tr);
			});
			$(document).on('click','.btn-plus-url',function(){
				var tr = '<tr><td class="inputCell relative" width="45%"><input type="text" name="old_url[]" value=""></td><td class="inputCell relative" width="45%"><input type="text" name="new_url[]" value=""></td><td class="inputCell relative text-center" width="10%"><a href="javascript:;" class="btn btn-warning btn-minus"><i class="fa fa-remove"></i></a><a href="javascript:;" class="btn btn-success btn-plus-url"><i class="fa fa-plus"></i></a></td></tr>';
				$(this).closest('tbody').append(tr);
			});
			
			$(document).on('click','.btn-plus-mass',function(){
				var tr = '<tr><td class="labelCell"><span class="paramLabel"></span></td><td class="inputCell relative" ><select name="tv[]"><optgroup label="SEO"><?php
					$res = $modx->db->query('SELECT name,caption FROM '.$modx->getFullTableName('site_tmplvars').' as tv
					left join '.$modx->getFullTableName('categories').' as cat
					on tv.category = cat.id
					where cat.category="SEO"');
					while ($row = $modx->db->getRow($res)) echo '<option value="'.$row['name'].'">'.$row['caption'].'</option>';
					?></optgroup><optgroup label="Другие ТВ-поля"><?php
					$res = $modx->db->query('SELECT name,caption FROM '.$modx->getFullTableName('site_tmplvars').' as tv
					left join '.$modx->getFullTableName('categories').' as cat
					on tv.category = cat.id
					');
					while ($row = $modx->db->getRow($res)) echo '<option value="'.$row['name'].'">'.$row['caption'].'</option>';
					?></optgroup><optgroup label="Поля документа"><?php
					$fields = array('pagetitle'=>'Заголовок','longtitle'=>'Расширенный заголовок','introtext'=>'Описание','content'=>'Содержимое ресурса');
					foreach($fields as $key=>$val)  echo '<option value="'.$key.'">'.$val.'</option>'; 
				?></optgroup></select></td><td class="inputCell relative"><input type="text" name="value[]" placeholder="Значение"></td><td class="inputCell relative" style="width:1px;"><a href="javascript:;" class="btn btn-warning btn-minus"><i class="fa fa-remove"></i></a><a href="javascript:;" class="btn btn-success btn-plus-mass"><i class="fa fa-plus"></i></a></td></tr>';
				$(this).closest('tbody').append(tr);
			});
			
			$(document).on('click','.btn-minus',function(){
				var c = $(this).closest('tbody').find('tr').length;
				if (c>1)
				{
					$(this).closest('tr').remove();
				}
			});
			$(document).on('click','.save',function(){
				var id_form = $("form:visible").attr('id');
				$('#'+id_form).submit();
			});
			
			$('select').change(function(){
				var name = $(this).attr('name');
				if (name!='tv[]')
				{
					if ($(this).val()=='true') $('.'+name+'_block').show();
					else $('.'+name+'_block').hide();
				}
			});
			
			
			
			$('select').trigger('change');
			
			/* <![CDATA[ */
			var lastImageCtrl;
			var lastFileCtrl;
			function OpenServerBrowser(url, width, height ) {
				var iLeft = (screen.width  - width) / 2 ;
				var iTop  = (screen.height - height) / 2 ;
				
				var sOptions = 'toolbar=no,status=no,resizable=yes,dependent=yes' ;
				sOptions += ',width=' + width ;
				sOptions += ',height=' + height ;
				sOptions += ',left=' + iLeft ;
				sOptions += ',top=' + iTop ;
				
				var oWindow = window.open( url, 'FCKBrowseWindow', sOptions ) ;
			}			
			function BrowseServer(ctrl) {
				lastImageCtrl = ctrl;
				var w = screen.width * 0.5;
				var h = screen.height * 0.5;
				OpenServerBrowser('<? echo MODX_SITE_URL;?>/manager/media/browser/mcpuk/browser.php?Type=images', w, h);
			}
			function BrowseFileServer(ctrl) {
				lastFileCtrl = ctrl;
				var w = screen.width * 0.5;
				var h = screen.height * 0.5;
				OpenServerBrowser('<? echo MODX_SITE_URL;?>/manager/media/browser/mcpuk/browser.php?Type=files', w, h);
			}
			function SetUrlChange(el) {
				if ('createEvent' in document) {
					var evt = document.createEvent('HTMLEvents');
					evt.initEvent('change', false, true);
					el.dispatchEvent(evt);
					} else {
					el.fireEvent('onchange');
				}
			}
			function SetUrl(url, width, height, alt) {
				if(lastFileCtrl) {
					var c = document.getElementById(lastFileCtrl);
					if(c && c.value != url) {
						c.value = url;
						SetUrlChange(c);
					}
					lastFileCtrl = '';
					} else if(lastImageCtrl) {
					var c = document.getElementById(lastImageCtrl);
					if(c && c.value != url) {
						c.value = url;
						SetUrlChange(c);
					}
					lastImageCtrl = '';
					} else {
					return;
				}
			}
			/* ]]> */
			
		</script>
		<style>
			.mc{    vertical-align: middle !important;    text-align: center;}
		</style>
		
	</body>
</html>
