/**
 * cfFormDB
 * @version 1.0
 *
 * [���W���[���ݒ�]
 * &viewFields=�ꗗ��ʂŕ\�����鍀��;text;
 */
include_once MODX_BASE_PATH . 'assets/modules/cfFormDB/cfformdb.class.php';

$cfdb = new cfFormDB($modx);
$cfdb->action();
