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
    <div id="actions">
      <ul class="actionButtons">
        [+add_buttons+]
      </ul>
    </div>
    <div class="section">
    [+content+]
    </div>
  <form action="[+posturl+]" method="post" name="actionform">
    <input type="hidden" name="tid" value="" />
    <input type="hidden" name="mode" value="" />
    <input type="hidden" name="cfp" value="[+page+]" />
    <input type="hidden" name="ct" value="[+count+]" />
  </form>
</body>
</html>
