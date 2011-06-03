<h2>データ一覧</h2>
<p>投稿されたデータの一覧です。ただいま <strong>[+total+]</strong>件の投稿があります。
<form action="index.php" method="get" name="ctform">
<input type="hidden" name="a" value="112" /><input type="hidden" name="id" value="[+moduleid+]" />
表示件数： <select name="ct" onchange="document.ctform.submit();">
[+countlist+]
</select></form></p>

[+list+]

<p style="margin-top: 20px;">[+pageNav+]</p>
