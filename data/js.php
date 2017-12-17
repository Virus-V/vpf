<?php
/* usage: js.php?v=1&file=f1.js,f2.js (v为版本号,方便更新) */
if(false && extension_loaded('zlib')) /*检查服务器是否开启了zlib拓展 */
{
    ob_start('ob_gzhandler');
}
header("content-type:application/x-javascript; charset: utf-8");
header("cache-control: must-revalidate");
$offset = 60*60*24;
$expire = "expires: ".gmdate("D, d M Y H:i:s", time() + $offset)." GMT";
header ($expire);
ob_start("compress"); /* Start the output buffer */

/** Function which actually compress */
function compress($buffer)
{
    return $buffer;
}

/* get file name */
$file = $_GET['file'];
$file = explode(',', $file);
/* include all CSS files */
foreach($file as $f)
{
    include($f);
}

ob_end_flush(); /* Flush the output buffer */
?>