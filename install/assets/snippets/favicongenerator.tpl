//<?php
/**
 * FaviconGenerator
 *
 * Создание favicon разных размеров из картинки
 *
 * @category	snippet
 * @internal	@modx_category SEO
 * @internal	@installset base
 * @internal	@overwrite true
 * @internal	@properties {}
 */

$out='';
$bg = isset($bg) ? $bg : "FFFFFF";
//apple

$sizes = array('57x57','144x144','72x72','144x144','60x60','120x120','76x76','152x152');
foreach ($sizes as $size)
{
$as = explode('x',$size);
$out.='<link rel="apple-touch-icon-precomposed" sizes="'.$size.'" href="'.$modx->runSnippet('phpthumb',array('input'=>$img,'options'=>'w='.$as[0].',h='.$as[1].',f=png,far=C,bg='.$bg)).'"/>'.PHP_EOL;
}

//classic
$sizes = array('32x32','16x16','96x96','128x128','196x196');
foreach ($sizes as $size)
{
$as = explode('x',$size);
$out.='<link rel="icon" type="image/png" sizes="'.$size.'" href="'.$modx->runSnippet('phpthumb',array('input'=>$img,'options'=>'w='.$as[0].',h='.$as[1],',f=png,far=C,bg='.$bg)).'"/>'.PHP_EOL;
}

// MS
$out.='<meta name="msapplication-TileColor" content="#FFFFFF" />'.PHP_EOL;
$out.='<meta name="msapplication-TileImage" content="'.$modx->runSnippet('phpthumb',array('input'=>$img,'options'=>'w=144,h=144,f=png,far=C,bg='.$bg)).'" />'.PHP_EOL;
$sizes = array('70x70','150x150','310x310');
foreach ($sizes as $size)
{
$as = explode('x',$size);
$out.='<meta name="msapplication-square'.$size.'logo" content="'.$modx->runSnippet('phpthumb',array('input'=>$img,'options'=>'w='.$as[0].',h='.$as[1].',f=png,far=C,bg='.$bg)).'"/>'.PHP_EOL;
}

//Generate .ico
if ($ico)
{
$out.='<link rel="shortcut icon" href="'.$modx->runSnippet('phpthumb',array('input'=>$img,'options'=>'w=16,h=16,f=ico,far=C,bg='.$bg)).'" type="image/x-icon">'.PHP_EOL.'
<link rel="icon" href="'.$modx->runSnippet('phpthumb',array('input'=>$img,'options'=>'w=16,h=16,f=ico,far=C,bg='.$bg)).'" type="image/x-icon">';
}

return $out;
