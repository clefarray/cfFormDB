<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link rel="stylesheet" type="text/css" href="media/style[+theme+]/style.css" />
<title>[+pagetitle+]</title>
<style type="text/css">
table.grid td {vertical-align:top;}
</style>
<script type="text/javascript">
function submitAction(mode, id) {
  if (mode == 'delete') {
    if (!confirm('ID:'+id+'の投稿を削除してもよろしいですか？\n※この操作は取り消しができません。')) { return false; }
  }
  document.actionform.tid.value = id;
  document.actionform.mode.value = mode;
  document.actionform.submit(); 
}
</script>
</head>

<body>
  <h1>[+pagetitle+]</h1>
  <div class="sectionBody">
    <div id="actions">
      <ul class="actionButtons">
        [+add_buttons+]
        <li><a href="#" onclick="submitAction('csv','');return false;"><img src="[+icons_save+]" /> CSV出力</a></li>
        <li><a href="[+posturl+]"><img src="[+icons_refresh+]" /> 再読み込み</a></li>
        <li><a href="index.php?a=2"><img src="[+icons_cancel+]" /> 閉じる</a></li>
      </ul>
    </div>
    <div class="content">[+content+]</div>
  </div>
  <form action="[+posturl+]" method="post" name="actionform">
    <input type="hidden" name="tid" value="" />
    <input type="hidden" name="mode" value="" />
    <input type="hidden" name="cfp" value="[+page+]" />
    <input type="hidden" name="ct" value="[+count+]" />
  </form>
</body>
</html>
