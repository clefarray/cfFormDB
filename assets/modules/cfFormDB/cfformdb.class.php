<?php
/**
 * cfFromDB
 * 
 * cfFormMailerで投稿された情報を記録、表示、CSV出力
 * 
 * @author		Clefarray Factory
 * @version	1.0
 * @internal	@properties  &viewFields=一覧画面で表示する項目;text; &ignoreFields=無視する項目;text; &defaultView=デフォルト画面;list;list,csv;list &sel_csv_fields=CSV出力項目を選択;list;1,0;1 &headLabels=表示や出力時のヘッダラベル<br>【書式】name|ラベル,name2|ラベル2,…;textarea;
 *
 */  
class cfFormDB {
  
  var $modx;
  var $data;
  var $version = '1.0';
  var $tbl_cfformdb;
  var $tbl_cfformdb_detail;
  var $ignoreParams;
  var $headLabel;

  function __construct($modx) {
    global $manager_theme, $_style, $e, $incPath, $content;

    $this->modx = &$modx;
    $this->e    = &$e;
    
    $this->tbl_cfformdb        = $this->modx->getFullTableName('cfformdb');
    $this->tbl_cfformdb_detail = $this->modx->getFullTableName('cfformdb_detail');
    
    $this->data['theme']     = '/' . $manager_theme;
    $this->data['posturl']   = 'index.php?a=112&id=' . $content['id'];
    $this->data['pagetitle'] = $content['name'] . ' v' .$this->version;
    
    $this->ignoreParams = $this->modx->event->params['ignoreFields'];
    if(!empty($this->ignoreParams)) $this->ignoreParams = explode(',', $this->ignoreParams);
    else                            $this->ignoreParams = array();
    
    /*
     *ラベルを任意に設定できるように調整。
     * フォームからPostされるnameとラベルの対応を以下のようにモジュール設定画面で定義すると反映する感じ。
     * name|ラベル,name2|ラベル2,name3|ラベル3, …
     * |か;でnameとラベルのペアを区切る。カンマでペアを増やす感じ。
     */
    $this->headLabel = array();
    $headLabels = $this->modx->event->params['headLabels'];
    if(!empty($headLabels)){
        $headLabels = explode(',', $headLabels);
        foreach($headLabels as $item){
            preg_match('/(.+)[\|;](.+)/',$item,$m);
            $this->headLabel[$m[1]]=$m[2];
        }
    }

    $this->modx->loadExtension('maketable');
  }

  /**
   * メインアクション
   */
  function action() {
    
    if (!IN_MANAGER_MODE) {
      return;
    }

    // 処理分岐
    switch($_POST['mode']) {
      case "allfields":
        $this->viewAllFields();
        break;
      case "delete":
        $this->delete();
        break;
      case "create_table":
        $this->createTable();
        break;
      case "csv":
        $this->csv();
        break;
      case "csv_generate":
        $this->generateCSV();
        break;
      default:
        $this->defaultAction();
        break;
    }

    // 出力
    $this->view();
    return true;
  }

  /**
   * 登録済みデータの一覧表示
   * 
   */
  function defaultAction() {
    global $content;

    if (!$this->ifTableExists()) {
      // テーブルが存在しない場合
      $this->data['content'] = $this->parser($this->loadTemplate('tablecreate.tpl'), $this->data);
    } else {
      $defaultView = isset($this->modx->event->params['defaultView']) ? $this->modx->event->params['defaultView'] : 'list';
      if($defaultView==='csv' && !isset($_GET['mode'])) {
          $this->csv();
          return true;
      }
      /**
       * 登録済みデータの一覧表示
       */
      $rs = $this->modx->db->select('postid, created', $this->tbl_cfformdb, '', 'created DESC');
      $records = array();
      if ($this->modx->db->getRecordCount($rs) > 0) {
        // 表示する項目を取得
        $viewParams = $this->modx->event->params['viewFields'];
        if ($viewParams) {
          $viewParamsArr = explode(",", str_replace(" ", "", $viewParams));
          foreach ($viewParamsArr as $val) {
            $viewParamsWhere[] = "'" . $this->modx->db->escape($val) . "'";
          }
        } else {
          $viewParamsWhere = array();
        }
        
        // 総件数を取得
        $total = $this->modx->db->getRecordCount($rs);

        // ページ分割
        $count = isset($_GET['ct']) ? intval($_GET['ct']) : 30;
        $page = isset($_GET['cfp']) ? intval($_GET['cfp']) : 1;
        $start = ($page - 1) * $count;
        $maxPage = (int)($total / $count) + 1;
        $pageNav = array();
        if ($maxPage > 1) {
          if ($page > 1) {
            $pageNav[] = sprintf('<a href="%s&amp;cfp=%d&amp;ct=%d">&laquo;前</a>', $this->data['posturl'], ($page - 1), $count);
          }
          for ($i=1; $i<=$maxPage; $i++) {
            if ($i == $page) {
              $pageNav[] = sprintf('<strong>%d</strong>', $i);
            } else {
              $pageNav[] = sprintf('<a href="%s&amp;cfp=%d&amp;ct=%d">%d</a>', $this->data['posturl'], $i, $count, $i);
            }
          }
          if ($page < $maxPage) {
            $pageNav[] = sprintf('<a href="%s&amp;cfp=%d&amp;ct=%d">次&raquo;</a>', $this->data['posturl'], ($page + 1), $count);
          }
          $params['pageNav'] = implode('&nbsp;|&nbsp;', $pageNav);
        }
        
        // 投稿を取得
        $rs = $this->modx->db->select('postid, created', $this->tbl_cfformdb, '', 'created DESC', $start . ',' . $count);
        $field_keys = array();
        $loop = 0;
        while ($buf = $this->modx->db->getRow($rs)) {
          $where = 'postid=' . $buf['postid'] . (count($viewParamsWhere) ? ' AND field IN (' . implode(",", $viewParamsWhere) . ')' : '');
          $detail_rs = $this->modx->db->select('field,value', $this->tbl_cfformdb_detail, $where, 'rank ASC');
          $records[$loop]['id'] = $buf['postid'];
          while ($detail_buf = $this->modx->db->getRow($detail_rs)) {
            if(in_array($detail_buf['field'], $this->ignoreParams)) continue;
            if(100 < mb_strlen($detail_buf['value'],'utf8'))
                $detail_buf['value'] = mb_substr($detail_buf['value'],0,100,'utf8') . ' ...';
            $records[$loop][$detail_buf['field']] = $detail_buf['value'];
            $field_keys[$detail_buf['field']] = $this->getLabel($detail_buf['field']);
          }
          $records[$loop]['created'] = $buf['created'];
          $records[$loop]['view'] = '<a href="[+posturl+]" onclick="submitAction(\'allfields\', ' . $buf['postid'] . ');return false;"><img src="[+icons_preview_resource+]" />詳細表示</a>';
          $records[$loop]['delete'] = '<a href="[+posturl+]" onclick="submitAction(\'delete\', ' . $buf['postid'] . ');return false;"><img src="[+icons_delete+]" />削除</a>';
          $loop++;
        }

        $tbl = new MakeTable();
        $tbl->setTableClass('grid');
        $tbl->setRowHeaderClass('gridHeader');
        $tbl->setRowRegularClass('gridItem');
        $tbl->setRowAlternateClass('gridAltItem');
        $listTableHeader = array_merge(
            array(
                'id' => 'ID',
                'created' => '投稿日時',
                'view' => '表示',
                'delete' => '削除'
            ),
            $field_keys
        );
        
        foreach (array(30, 50, 100) as $val) {
          $params['countlist'] .= sprintf('<option value="%d"%s>%d件</option>', $val, ($val == $count ? ' selected="selected"' :  ''), $val) . "\n";
        }
        $params['moduleid'] = $content['id'];
        $params['list'] = $this->parser($tbl->create($records, $listTableHeader), $this->data);
        $params['total'] = $total;
        $this->data['content'] = $this->parser($this->loadTemplate('list.tpl'), $params);
        $this->data['page'] = $page;
        $this->data['count'] = $count;
        $this->data['add_buttons']  = $this->parser('
        <li><a href="#" onclick="submitAction(\'csv\',\'\');return false;"><img src="[+icons_save+]" /> CSV出力</a></li>
        <li><a href="[+posturl+]"><img src="[+icons_refresh+]" /> 再読み込み</a></li>
        <li><a href="index.php?a=2"><img src="[+icons_cancel+]" /> 閉じる</a></li>
        ', $this->data);
      } else {
        $this->data['content'] = '<div class="sectionBody">データはありません</div>';
      }
    }
  }

  /**
   * 指定IDのすべての項目を表示
   * 
   */
  function viewAllFields() {
    $id = intval($_POST['tid']);
    $page = intval($_POST['cfp']);
    $count = intval($_POST['ct']);
    if ($id) {
      $sql = sprintf("SELECT B.field, B.value, A.created FROM %s A LEFT JOIN %s B ON A.postid=B.postid WHERE A.postid=%d ORDER BY B.rank ASC",
        $this->tbl_cfformdb,
        $this->tbl_cfformdb_detail,
        $id
      );
      $rs = $this->modx->db->query($sql);
      if ($this->modx->db->getRecordCount($rs)) {
        while ($buf = $this->modx->db->getRow($rs)) {
          $created = $buf['created'];
          unset($buf['created']);
          $buf['value'] = nl2br($buf['value']);
          $buf['field'] = $this->getLabel($buf['field']);
          $records[] = $buf;
        }
        
        $tbl = new MakeTable();
        $tbl->setTableClass('grid');
        $tbl->setRowRegularClass('gridItem');
        $tbl->setRowAlternateClass('gridAltItem');
        $listTableHeader = array('field' => '項目','value' => '登録内容');

        $content = sprintf("<p>ID: %d<br />投稿日時：%s</p>", $id, $created) . $this->parser($tbl->create($records, $listTableHeader), $this->data);
        $this->data['content'] = '<div class="sectionBody">' . $content . '</div>';
        $this->data['add_buttons']  = $this->parser('
        <li><a href="[+posturl+]&amp;mode=list&amp;cfp=' . "{$page}&amp;ct={$count}" . '"><img src="[+icons_cancel+]" />一覧に戻る</a></li>
        <li><a href="#" onclick="submitAction(\'delete\', ' . $id . ');return false;"><img src="[+icons_delete+]" />削除</a></li>
        ', $this->data);
      }
    }  
  }

  /**
   * 投稿を削除
   * 
   */
  function delete() {
    $id = intval($_POST['tid']);
    if ($id) {
      $sql = sprintf("DELETE FROM %s WHERE postid=%d LIMIT 1", $this->tbl_cfformdb, $id);
      $this->modx->db->query($sql);
      $sql = sprintf("DELETE FROM %s WHERE postid=%d", $this->tbl_cfformdb_detail, $id);
      $this->modx->db->query($sql);
      $this->data['content'] = $this->parser('
        <div class="section">
        <div class="sectionBody">
        <p>ID: ' . $id . 'の投稿を削除しました<br />
        <ul class="actionButtons">
          <li><a href="[+posturl+]"><img src="[+icons_save+]" />戻る</a></li>
        </ul>
        </div>
        </div>', $this->data);
    } 
  }

  /**
   * CSV出力のための設定画面
   * 
   */
  function csv() {
    
    // 件数チェック
    $rs = $this->modx->db->select('COUNT(*)', $this->tbl_cfformdb);
    if (!$this->modx->db->getRecordCount($rs)) {
      $this->e->setError(1, '出力するデータがありません');
      $this->e->dumpError();
      return;
    }

    // 項目一覧を取得
    $rs = $this->modx->db->select('DISTINCT(field)', $this->tbl_cfformdb_detail, '', 'rank');
    $loop = 0;
    $fields = array();
    $tpl = '<input type="checkbox" name="fields[]" value="%s" id="f_%d" %s /> <label for="f_%d">%s</label>';
    while ($buf = $this->modx->db->getRow($rs)) {
      $checked = in_array($buf['field'], $this->ignoreParams) ? '' : 'checked="checked"';
      $label = $this->getLabel($buf['field']);
      $fields[] = sprintf($tpl, $buf['field'], $loop, $checked, $loop, $label);
      $loop++;
    }
    $params = $this->data;
    $params['fields'] = implode("<br />", $fields);
    $params['site_url'] = $this->modx->config['site_url'];
    $params['manager_url'] = MODX_MANAGER_URL;
    $params['mgrlog_datefr'] = 'この日付から';
    $params['mgrlog_dateto'] = 'この日付まで';
    $params['datepicker_offset'] = $this->modx->config['datepicker_offset'];
    $params['datetime_format']   = $this->modx->config['datetime_format'];
    $params['dayNames']          = "['日','月','火','水','木','金','土']";
    $params['monthNames']        = "['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月']";
    $params['display'] = $this->modx->event->params['sel_csv_fields']==='1' ? '' : 'none';
    
    $this->data['content'] = $this->parser($this->loadTemplate('csv_settings.tpl'), $params);
    $this->data['add_buttons']  = $this->parser('
    <li><a href="[+posturl+]&amp;mode=list=list"><img src="[+icons_refresh+]" /> 一覧表示</a></li>
    <li><a href="index.php?a=2"><img src="[+icons_cancel+]" /> 閉じる</a></li>
    ', $this->data);
  }

  /**
   * CSV形式で出力
   * 
   */
  function generateCSV() {
    
    // 出力する項目を取得
    if (!count($_POST['fields'])) {
      echo '<script>alert("出力する項目がありません");location.href="' . $this->data["posturl"] . '";</script>';
      exit;
    } else {
      $fields = array();
      $labels = array();
      foreach ($_POST['fields'] as $val) {
        $fields[] = "'" . $val . "'";
        $labels[$val]=$this->getLabel($val);
      }
    }
    // 出力数
    switch($_POST['count']) {
      case "30":  $count = 30; break;
      case "50":  $count = 50; break;
      case "100": $count = 100; break;
      default:    $count = 0;  // すべて
    }
    // ソート
    $sort = ($_POST['sort'] ? "created DESC" : "created ASC");
    // 期間指定
    $start = !empty($_POST['start']) ? $this->modx->db->escape($_POST['start']) : 0;
    $end   = !empty($_POST['end'])   ? $this->modx->db->escape($_POST['end'])   : 0;
    if(!empty($start) && !empty($end))
    {
        $where = "WHERE created BETWEEN '{$start}' AND '{$end}'";
    }
    elseif(!empty($start))
    {
        $where = "WHERE created >= '{$start}'";
    }
    elseif(!empty($end))
    {
        $where = "WHERE created <= '{$end}'";
    }
    else $where = '';
    // データ出力
    header('Content-type: application/octet-stream');
    header('Content-Disposition: attachment; filename=cfoutput.csv');

    ob_start();
    $loop = 0;
    $sql = sprintf("SELECT postid,created FROM %s %s ORDER BY %s", $this->tbl_cfformdb, $where, $sort) . ($count ? ' LIMIT ' . $count : '');
    $rs = $this->modx->db->query($sql);
    echo '//' . implode(',', array_merge(array('ID'), array_values($labels), array('datetime'))) . "\n";
    while ($buf = $this->modx->db->getRow($rs)) {
      echo $buf['postid'] . ',';
      $sql = sprintf("SELECT * FROM %s WHERE postid=%d AND field IN (%s) ORDER BY rank", $this->tbl_cfformdb_detail, $buf['postid'], implode(',', $fields));
      $detail_rs = $this->modx->db->query($sql);
      $detail = array();
      while ($detail_buf = $this->modx->db->getRow($detail_rs)) {
        if (in_array($detail_buf['field'], $_POST['fields'])) {
            if(strpos($detail_buf['value'],'"')!==false)
                $detail_buf['value'] = str_replace('"','""',$detail_buf['value']);
            $detail[$detail_buf['field']] = $detail_buf['value'];
        }
      }
      foreach ($_POST['fields'] as $field) {
        echo '"' . $detail[$field] . '",';
      }
      echo '"'.$buf['created'].'"' . "\n";
      $loop++;
    }
    $output = ob_get_flush();
    ob_end_clean();
    $output = mb_convert_encoding($output, 'sjis', 'utf-8');
    $size = strlen($output);
    header("Content-Length: {$size}");
    echo $output;
    exit;
  }

  /**
   * 新規テーブル作成
   */
  function createTable() {
    if ($this->ifTableExists()) {
      $content= 'テーブルは存在しています';
    } else {
      $flag = false;
      $this->modx->db->query('START TRANSACTION');
      if(version_compare($this->modx->db->getVersion(),'4.1.0', '>='))
      {
          $char_collate = ' DEFAULT CHARSET=utf8 COLLATE utf8_general_ci';
      }
      else $char_collate = '';
      $sql = "CREATE TABLE {$this->tbl_cfformdb} (`postid` int auto_increment primary key, `created` datetime) ENGINE=MyISAM";
      $this->modx->db->query($sql.$char_collate);
      if (!($err = $this->modx->db->getLastError())) {
        $sql = "CREATE TABLE {$this->tbl_cfformdb_detail} (`postid` int not null, `field` varchar(255) not null, `value` text, `rank` int, PRIMARY KEY ( `postid` , `field` )) ENGINE=MyISAM";
        $this->modx->db->query($sql.$char_collate);
        if (!($err2 = $this->modx->db->getLastError())) {
          $this->modx->db->query('COMMIT');
          $flag = true;
        }
      }

      if ($flag) {
        $content = $this->parser('
        <div class="section">
        <div class="sectionBody">
        テーブルを作成しました<br />
        <ul class="actionButtons">
          <li><a href="[+posturl+]"><img src="[+icons_save+]" />戻る</a></li>
        </ul>
        </div></div>', $this->data);
      } else {
        $this->modx->db->query('ROLLBACK');
        $content = 'テーブル作成に失敗しました::' . $err . "::" . $err2;
      }
    }
    $this->data['content'] = $content;
  }

  /**
   * テーブルの存在確認
   */
  function ifTableExists() {
    $sql = "SHOW TABLES FROM " . $this->modx->db->config['dbase'] . " LIKE '%cfformdb%'";
    if ($rs = $this->modx->db->query($sql)) {
      if ($this->modx->db->getRecordCount($rs) == 2) {
        return true;
      }
    }
    return false;
  }

    /**
    *　ラベル指定取得
    *    指定がなければそのまま
    */
    function getLabel($f=''){
        $r='';
        if(!empty($f)){
            if(isset($this->headLabel[$f]) && !empty($this->headLabel[$f])){
                $r=$this->headLabel[$f];
            }else{
                $r=$f;
            }
        }
        return $r;
    }

  /**
   * 画面出力
   *
   */
  function view() {
    if ($tpl = $this->loadTemplate('main.tpl')) {
      $tpl = $this->parser($tpl, $this->data);
      $tpl = preg_replace("/\[\+.+?\+\]/", "", $tpl);
      echo $tpl;
    } else {
      $this->e->setError(1, 'テンプレートの読み込みに失敗しました');
      $this->e->dumpError();
    }
    return "";
  }

  /**
   * テンプレート取得
   */
  function loadTemplate($tplname) {
    $filename = MODX_BASE_PATH . "assets/modules/cfFormDB/" . $tplname;
    if (@file_exists($filename)) {
      $tpl = file_get_contents($filename);
      return $tpl;
    } else {
      return false;
    }
  }
  
  /**
   * テンプレート変数を展開
   */
  function parser($tpl, $vars = array()) {
  	global $_style;

    if (count($vars)) {
      foreach ($vars as $key => $val) {
        $tpl = str_replace("[+".$key."+]", $val, $tpl);
      }
      foreach ($_style as $key => $val) {
      	$tpl = str_replace("[+".$key."+]", $val, $tpl);
      }
    }
    return $tpl;
  }

}
