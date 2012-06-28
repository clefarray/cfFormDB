/**
 * cfFormDB
 * @version 1.0
 *
 * [モジュール設定]
 * &viewFields=一覧画面で表示する項目;text;
 */
include_once MODX_BASE_PATH . 'assets/modules/cfFormDB/cfformdb.class.php';

$cfdb = new cfFormDB($modx);
$cfdb->action();
