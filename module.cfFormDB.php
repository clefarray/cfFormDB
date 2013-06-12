/**
 * cfFormDB
 * @version 1.0
 *
 * [モジュール設定]
 * &viewFields=一覧画面で表示する項目;text; &ignoreFields=無視する項目;text; &defaultView=デフォルト画面;list;list,csv;list &sel_csv_fields=CSV出力項目を選択;list;1,0;1
 */
include_once MODX_BASE_PATH . 'assets/modules/cfFormDB/cfformdb.class.php';

$cfdb = new cfFormDB($modx);
$cfdb->action();
