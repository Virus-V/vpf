<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>System error occurred</title>
<style>
body {
  font-family: "Source Sans Pro", Calibri,"Microsoft YaHei", Candara, Arial, sans-serif;
}
h1,
h2,
h3,
h4,
h5,
h6,
.h1,
.h2,
.h3,
.h4,
.h5,
.h6 {
  font-family: "Source Sans Pro", Calibri,"Microsoft YaHei", Candara, Arial, sans-serif;
  font-weight: 300;
  line-height: 1.1;
  color: inherit;
}

a {text-decoration:none; color:#222;}
a:hover {
    text-decoration:none;
    color:#222;
}
h1{
    font-size:40px;
}

</style>
</head>
<body>
    <h1>Oops!</h1>
    <p>发生系统错误了！╥﹏╥...</p>
    <p>您可以 [ <a HREF="<?php echo(strip_tags($_SERVER['PHP_SELF']))?>">重试</a> ] [ <a HREF="javascript:history.back()">返回</a> ] 或者 [ <a HREF="<?php echo(__APP__);?>">回到首页</a> ]</p>
    <?php if(isset($e['file'])) {?>
    <p>[ 错误位置 ] 在文件: <span><?php echo $e['file'] ;?></span> 的第 <span><?php echo $e['line'];?></span> 行。</p>
    <?php }?>
    <p>[ 错误信息 ] <?php echo strip_tags($e['message']);?></p>
    <?php if(isset($e['trace'])) {?>
    <p>[ TRACE ]</p>
    <p id="trace"><?php echo nl2br($e['trace']);?></p>
    <?php }?>
    
    <div align="center" style="color:#FF3300;margin:5pt;font-family:Verdana">
        VPF <sup style='color:gray;font-size:9pt'><?php echo VPF_VERSION;?></sup>
    </div>
</body>
</html>