<h3>CSV出力設定</h3>
<p>下記設定を確認し、「出力する」ボタンをクリックしてください</p>
<form action="[+posturl+]" method="post" name="csvform">
<input type="hidden" name="mode" value="csv_generate" />
<table class="grid" style="margin-bottom: 30px;">
  <thead>
    <tr>
      <th style="width: 20%">項目</th>
      <th style="width: 80%">設定</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th>出力する項目</th>
      <td>[+fields+]</td>
    </tr>
    <tr class="gridAltItem">
      <th>出力件数</th>
      <td><select name="count"><option value="0">すべて</option><option value="30">30件</option><option value="50">50件</option><option value="100">100件</option></select></td>
    </tr>
    <tr>
      <th>出力順</th>
      <td><select name="sort"><option value="0">日付の古いものから</option><option value="1">日付の新しいものから</option></select></td>
    </tr>
  </tbody>
</table>
<div class="actionButtons"><a href="#" onclick="document.csvform.submit();return false;"><img src="[+icons_save+]" />出力する</a></div>
</form>