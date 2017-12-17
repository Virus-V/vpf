
<div id="vpf_page_trace" style="padding:10px; margin: 10px; color:#666; line-height: 18px; background:#eee; border:1px solid #fff;">
    <p style="padding:0; margin:0; border-bottom:1px solid #ddd; font-size:14px; color:#f60;"><?php echo Vpf\L('_PAGE_TRACE_INFO_') ?></p>
    <p style="padding:0; margin: 5px 0 0; overflow: scroll; height: 300px; text-align: left; font-size: 12px;">
<?php foreach ($_trace as $key=>$info){echo $key.' : '.$info."<br />\n";}?>
    </p>
</div>