<?php
class PluginDbSync_v1{
  /**
   *
   */
  private $settings = null;
  private $db = null;
  private $item = nulL;
  private $mysql = null;
  /**
   * 
   */
  function __construct($buto = false) {
    if($buto){
      set_time_limit(60*20);
      ini_set('memory_limit', '2048M');
      wfGlobals::setSys('layout_path', '/plugin/db/sync_v1/layout');
      wfPlugin::includeonce('wf/array');
      wfPlugin::includeonce('wf/yml');
      wfPlugin::enable('bootstrap/navbar_v1');
      $this->settings = new PluginWfArray(wfArray::get($GLOBALS, 'sys/settings/plugin_modules/'.wfArray::get($GLOBALS, 'sys/class').'/settings'));
      if(!wfUser::hasRole("webmaster") && $this->settings->get('security')!==false){
        exit('Role webmaster is required!');
      }
      /**
       * mysql
       */
      wfPlugin::includeonce('wf/mysql');
      $this->mysql = new PluginWfMysql();
      /**
       * item
       */
      $temp = new PluginWfArray(wfSettings::getSettingsFromYmlString($this->settings->get('item')));
      $this->settings->set('item', $temp->get('item') );
      $this->settings->set('schema', $temp->get('schema') );
      $this->settings->set('queries', $temp->get('queries') );
      /**
       * settings
       */
      foreach($this->settings->get('item') as $k => $v){
        if($this->settings->get("item/$k/backup")){
          $this->settings->set("item/$k/has_backup", 'Yes');
        }else{
          $this->settings->set("item/$k/has_backup", '');
        }
      }
      /**
       * Set schema from string
       */
      foreach ($this->settings->get('item') as $key => $value) {
        if(!is_array($value['schema'])){
          $this->settings->set("item/$key/schema", $this->settings->get("schema/".$value['schema']));
        }
      }
      $id = wfRequest::get('id');
      if(wfPhpfunc::strlen($id)){
        /**
         * item
         */
        $this->item = new PluginWfArray($this->settings->get("item/$id"));
        $this->item->set('mysql', wfSettings::getSettingsFromYmlString($this->item->get('mysql')));
        /**
         * 
         */
        $this->db = new PluginWfArray($this->settings->get("item/$id"));
        $this->db->set('mysql', wfSettings::getSettingsFromYmlString($this->db->get('mysql')));
        try {
          /**
           * 
           */
          $rs = $this->runSQL("select version() as Version");
          $this->item->set('mysql/version', $rs->get('0/Version'));
        }
        catch (exception $e) {
          $this->item->set('mysql/version', null);
        }
      }
      /**
       * Mysql params
       */
      foreach ($this->settings->get('item') as $key => $value) {
        $i = new PluginWfArray($value);
        $mysql = new PluginWfArray(wfSettings::getSettingsFromYmlString($i->get('mysql')));
        $this->settings->set("item/$key/server", $mysql->get('server'));
        $this->settings->set("item/$key/database", $mysql->get('database'));
        $this->settings->set("item/$key/user_name", $mysql->get('user_name'));
        $this->settings->set("item/$key/data_key", $key);
      }
      /**
       * Schema text
       */
      foreach ($this->settings->get('item') as $key => $value) {
        $str = null;
        if(isset($value['schema']) && $value['schema']){
          foreach ($value['schema'] as $key2 => $value2) {
            $str .= ', '.$value2;
          }
        }
        $str = wfPhpfunc::substr($str, 2);
        $this->settings->set("item/$key/schema_text", $str);
      }
      /**
       * PluginMailQueue_admin.
       */
      foreach ($this->settings->get('item') as $key => $value) {
        $this->settings->set("item/$key/plugin_mail_queue_admin", false);
        $this->settings->set("item/$key/plugin_mail_queue_admin_text", '');
        if(isset($value['schema']) && $value['schema']){
          foreach ($value['schema'] as $key2 => $value2) {
            if($value2=='/plugin/mail/queue/mysql/schema.yml'){
              $this->settings->set("item/$key/plugin_mail_queue_admin", true);
              $this->settings->set("item/$key/plugin_mail_queue_admin_text", 'Yes');
              break;
            }
          }
        }
      }
      /**
       * PluginAccountAdmin_v1
       */
      foreach ($this->settings->get('item') as $key => $value) {
        $this->settings->set("item/$key/plugin_account_admin_v1", false);
        $this->settings->set("item/$key/plugin_account_admin_v1_text", '');
        if(isset($value['schema']) && $value['schema']){
          foreach ($value['schema'] as $key2 => $value2) {
            if($value2=='/plugin/wf/account2/mysql/schema.yml'){
              $this->settings->set("item/$key/plugin_account_admin_v1", true);
              $this->settings->set("item/$key/plugin_account_admin_v1_text", 'Yes');
              break;
            }
          }
        }
      }
      /**
       * Enable.
       */
      wfPlugin::enable('datatable/datatable_1_10_18');
      wfPlugin::enable('wf/table');
      wfPlugin::enable('twitter/bootstrap335v');
      wfPlugin::enable('wf/embed');
      wfPlugin::enable('form/form_v1');
      wfPlugin::enable('bootstrap/alertwait');
      wfPlugin::enable('bootstrap/navtabs_v1');
      /**
       * Unset i18n event for this module.
       */
      wfPlugin::event_remove('document_render_string', 'i18n/translate_v1');
    }
  }
  /**
   * Start page.
   */
  public function page_start(){
    $page = $this->getYml('page/start.yml');
    $page = wfDocument::insertAdminLayout($this->settings, 1, $page);
    wfDocument::mergeLayout($page->get());
  }
  /**
   * Many databases.
   */
  public function page_dbs(){
    $page = $this->getYml('page/dbs.yml');
    wfDocument::mergeLayout($page->get());
  }
  public function page_dbs_data(){
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    exit($datatable->set_table_data($this->settings->get('item')));
  }
  public function page_dbs_action(){
    $id = wfRequest::get('id');
    /**
     * queries
     */
    $queries = $this->settings->get("item/$id/queries");
    if($queries && !is_array($queries)){
      $queries = $this->settings->get("queries/$queries");
    }
    if($queries){
      foreach($queries as $k => $v){
        $queries[$k]['link'] = '<a href="#" onclick="PluginDbSync_v1.query_view(this)" data-id="'.$id.'" data-key="'.$k.'" data-name="'.$v['name'].'">View</a>';
      }
    }
    /**
     * backup / script
     */
    $backup = $this->settings->get("item/$id/backup");
    $backup = new PluginWfArray($backup);
    if($backup->get()){
      $user_name = $this->item->get("mysql/user_name");
      $password = $this->item->get("mysql/password");
      $server = $this->item->get("mysql/server");
      $database = $this->item->get("mysql/database");
      $server_folder = $backup->get("server_folder");
      $local_folder = $backup->get("local_folder");
      if($backup->get('ssh')){
        $dump = 'ssh '.$backup->get('ssh').' "mysqldump -u '.$user_name.' -p\"'.$password.'\" -h '.$server.' '.$database.' > '.$server_folder.'/'.$database.'.sql;"';
        $backup->set('script/dump', $dump);
        $scp = 'scp '.$backup->get('ssh').':'.$server_folder.'/'.$database.'.sql "'.$local_folder.'"';
        $backup->set('script/scp', $scp);
        $mv = 'mv "'.$local_folder.'/'.$database.'.sql" "'.$local_folder.'/'.$database.'_$(date +%y%m%d).sql"';
        $backup->set('script/mv', $mv);
        $backup->set('script/combined', $dump.' && '.$scp.' && '.$mv);
      }else{
        $dump = '/Applications/MAMP/Library/bin/mysqldump -u '.$user_name.' -p"'.$password.'" '.$database.' > "'.$local_folder.'/'.$database.'_$(date +%y%m%d).sql"   ';
        $backup->set('script/dump', $dump);
      }
    }
    /**
     * 
     */
    $element = new PluginWfYml(__DIR__.'/page/dbs_action.yml');
    $element->setByTag($this->item->get());
    $element->setByTag($this->settings->get("item/$id"));
    $element->setByTag($this->item->get('mysql'), 'mysql');
    $element->setByTag(array('data' => $queries), 'queries');
    $element->setByTag(wfRequest::getAll(), 'get');
    $element->setByTag(array('backup' => $backup->get()));
    $element->setByTag(array('script' => $backup->get('script')));
    wfDocument::renderElement($element);
  }
  public function page_query_view(){
    $id = wfRequest::get('id');
    $query_id= wfRequest::get('query_id');
    /**
     * 
     */
    $queries = $this->settings->get("item/$id/queries");
    if(!is_array($queries)){
      $data = $this->settings->get("queries/$queries/$query_id");
    }else{
      $data = $this->settings->get("item/$id/queries/$query_id");
    }
    /**
     * 
     */
    $data = new PluginWfArray($data);
    $select = array();
    foreach($data->get('select') as $k => $v){
      $select[$v] = $v;
    }
    $element = wfDocument::getElementFromFolder(__DIR__, __FUNCTION__);
    $element->setByTag($data->get());
    $element->setByTag(array('select' => $select), 'table');
    wfDocument::renderElement($element);
  }
  public function page_query_view_data(){
    $id = wfRequest::get('id');
    $query_id= wfRequest::get('query_id');
    /**
     * 
     */
    $queries = $this->settings->get("item/$id/queries");
    if(!is_array($queries)){
      $data = $this->settings->get("queries/$queries/$query_id");
    }else{
      $data = $this->settings->get("item/$id/queries/$query_id");
    }
    /**
     * 
     */
    $data = new PluginWfArray($data);
    $rs = $this->runSQL($data->get('sql'), false);
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    exit($datatable->set_table_data($rs->get()));
  }
  public function page_execute_capture(){
    $sql = wfRequest::get('sql');
    wfUser::setSession('plugin/db/sync_v1/execute/sql', $sql);
    $rs = $this->runSQL($sql)->get();
    wfHelp::print($rs);
  }
  public function page_map(){
    $schema = $this->getFields();
    /**
     * Data modify.
     */
    foreach ($schema->get('schema/field') as $key => $value) {
      $item = new PluginWfArray($value);
      $schema_table_name = $item->get('schema_table_name');
      $schema_field_name = $item->get('schema_field_name');
      $item->set('id', $item->get('schema_table_name').'__'.$item->get('schema_field_name'));
      $item->set('reference_field', $item->get('schema_field_foreing_key/reference_table').'__'.$item->get('schema_field_foreing_key/reference_field'));
      if($item->get('schema_field_foreing_key')){
        $item->set('field_class', 'map-field '.$item->get('schema_field_foreing_key/reference_table').'__'.$item->get('schema_field_foreing_key/reference_field'));
      }else{
        $item->set('field_class', 'map-field');
      }
      $item->set('foreing_key_id', $item->get('schema_table_name').'__'.$item->get('schema_field_name').'_fk');
      $schema->set("schema/table/$schema_table_name/field/$schema_field_name", $item->get());
    }
    /**
     * 
     */
    $page = $this->getYml('page/map.yml');
    $items = array();
    $schema_files_name = null;
    $links = array();
    foreach ($schema->get('schema/table') as $key => $value) {
      $i = new PluginWfArray($value);
      $item = $this->getYml('element/map_item.yml');
      $item->setByTag(array('description' => $i->get('description')));
      $fields = array();
      foreach ($i->get('field') as $key2 => $value2) {
        $j = new PluginWfArray($value2);
        if($j->get('is_extra')){
          $j->set('bg', 'bg-white');
        }else{
          $j->set('bg', '');
        }
        if($j->get('schema_files_name') != $schema_files_name){
          $schema_files_name = $j->get('schema_files_name');
          /**
           * Links
           */
          $id = wfPhpfunc::str_replace('/', '_', $schema_files_name);
          $id = wfPhpfunc::str_replace('.', '_', $id);
          $links[] = wfDocument::createHtmlElement('div', array(wfDocument::createHtmlElement('a', $schema_files_name, array('href' => "#$id"))));
          /**
           * Map schema element
           */
          $map_schema = $this->getYml('element/map_schema.yml');
          $map_schema->setByTag($j->get());
          $map_schema->setByTag(array('anchor_id' => $id));
          $items[] = $map_schema->get();
        }
        $field = $this->getYml('element/map_item_field.yml');
        if(!$j->get('description')){
          $j->set('description', '&nbsp;');
        }
        $j->set('table_name', $i->get('name'));
        $field->setByTag($j->get());
        $field->setByTag($j->get('schema_field_foreing_key'), 'schema_field_foreing_key', true);
        $fields[] = $field->get();
      }
      $item->setByTag(array('fields' => $fields, 'table_name' => $key));
      $items[] = $item->get();
    }
    /**
     * Links
     */
    $page->setByTag(array('items' => $items));
    $page->setByTag(array('links' => $links));
    wfDocument::mergeLayout($page->get());
  }
  /**
   * 
   */
  public function page_table_description_form(){
    $form = new PluginWfYml(__DIR__.'/form/table_description_form.yml');
    $widget = wfDocument::createWidget('form/form_v1', 'render', $form->get());
    wfDocument::renderElement(array($widget));
  }
  /**
   * 
   */
  public function page_table_description_capture(){
    $form = new PluginWfYml(__DIR__.'/form/table_description_form.yml');
    $widget = wfDocument::createWidget('form/form_v1', 'capture', $form->get());
    wfDocument::renderElement(array($widget));
  }
  public function page_import_capture(){
    wfDocument::renderElementFromFolder(__DIR__, __FUNCTION__);
  }
  public function form_import_capture($form){
    wfPlugin::includeonce('string/array');
    $plugin_string_array = new PluginStringArray();
    $schema = $this->getFields();
    $table = wfRequest::get('table');
    $type_of_run = wfRequest::get('type_of_run');
    wfUser::setSession('plugin/db/sync_v1/import/xls', wfRequest::get('xls'));
    wfUser::setSession('plugin/db/sync_v1/import/type_of_run', wfRequest::get('type_of_run'));
    $xls = $plugin_string_array->from_excel_data(wfRequest::get('xls'));
    /**
     * field
     */
    $field = new PluginWfArray();
    foreach($xls['data']['0'] as $k => $v){
      $field->set("$v/name", $v);
      $field->set("$v/exist", false);
      if($schema->get("schema/field/$table#$v")){
        $field->set("$v/exist", true);
      }
    }
    /**
     * error
     */
    $return = new PluginWfArray();
    $return->set('error/message', null);
    $return->set('type_of_run', $type_of_run);
    $str = '';
    foreach($field->get() as $k => $v){
      $i = new PluginWfArray($v);
      if(!$i->get('exist')){
        $str .= ','.$i->get('name');
      }
    }
    if($str){
      $str = substr($str, 1);
      $return->set('error/message', "Field $str does not exist!");
    }
    /**
     * sql
     */
    $sql = "insert into $table (";
    foreach($field->get() as $k => $v){
      $i = new PluginWfArray($v);
      $sql .= $i->get('name').',';
    }
    $sql = substr($sql, 0, strlen($sql)-1);
    $sql .= ") values ";
    $values = '';
    foreach($xls['data'] as $k => $v){
      if($k==0){
        continue;
      }
      $values .= '(';
      foreach($v as $k2 => $v2){
        $values .= "'".$v2."',";
      }
      $values = substr($values, 0, strlen($values)-1);
      $values .= '),';
    }
    $values = substr($values, 0, strlen($values)-1);
    $values .= ";";
    $sql .= $values;
    $return->set('sql', $sql);
    /**
     * import_into_db
     */
    if($type_of_run=='import_into_db'){
      try {
        $this->runSQL($sql);
      }
      catch(Exception $e) {
        $return->set('error/message', $e->getMessage());
      }
    }
    /**
     * 
     */
    $json = json_encode($return->get());
    return array("PluginDbSync_v1.import_capture($json)");
  }
  public function capture_table_description(){
    $schema = $this->getFields();
    $table_name = wfRequest::get('table_name');
    $file_edit = new PluginWfYml(wfGlobals::getAppDir().$schema->get("schema/table/$table_name/file"));
    $file_edit->set("tables/$table_name/description", wfRequest::get('description'));
    $file_edit->save();
    $data = json_encode(array('description' => wfRequest::get('description')));
    return array("PluginDbSync_v1.mapTableDescriptionCapture($data);");
  }
  /**
   * 
   */
  public function page_field_description_form(){
    $form = new PluginWfYml(__DIR__.'/form/field_description_form.yml');
    $widget = wfDocument::createWidget('form/form_v1', 'render', $form->get());
    wfDocument::renderElement(array($widget));
  }
  /**
   * 
   */
  public function page_field_description_capture(){
    $form = new PluginWfYml(__DIR__.'/form/field_description_form.yml');
    $widget = wfDocument::createWidget('form/form_v1', 'capture', $form->get());
    wfDocument::renderElement(array($widget));
  }
  public function page_tables(){
    wfDocument::renderElementFromFolder(__DIR__, __FUNCTION__);
  }
  public function page_tables_data(){
    $schema = $this->getFields();
    $rs = array();
    foreach($schema->get('schema/table') as $k => $v){
      $rs[] = $v;
    }
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    exit($datatable->set_table_data($rs));
  }
  public function capture_field_description(){
    $schema = $this->getFields();
    $table_name = wfRequest::get('table_name');
    $field_name = wfRequest::get('field_name');
    $file_edit = new PluginWfYml(wfGlobals::getAppDir().$schema->get("schema/table/$table_name/file"));
    $file_edit->set("tables/$table_name/field/$field_name/description", wfRequest::get('description'));
    $file_edit->save();
    $data = json_encode(array('description' => wfRequest::get('description')));
    return array("PluginDbSync_v1.mapFieldDescriptionCapture($data);");
  }
  /**
   * Generate yml schema in textarea.
   */
  public function page_schema_generator(){
    $schema = $this->generateSchema();
    $page = $this->getYml('page/schema_generator.yml');
    $page->setByTag(array('yml' => wfHelp::getYmlDump( array('tables' => $schema->get())  )));
    wfDocument::mergeLayout($page->get());
  }
  /**
   * Generate script form.
   */
  public function page_script_generator(){
    $element = $this->getYml('element/script_generator.yml');
    wfDocument::renderElement($element->get());
  }
  /**
   * Data export
   */
  public function page_data_export(){
    /**
     * Sets to true. In future we maybe want to use singel line as a setting.
     */
    $multiline = true;
    /**
     * 
     */
    $schema = $this->getFields();
    /**
     * Get all tables in db.
     */
    $tables = $this->runSQL("SELECT TABLE_NAME FROM information_schema.tables where TABLE_TYPE='BASE TABLE' and TABLE_SCHEMA='".$schema->get('mysql/database')."' limit 1000");
    /**
     * In schema.
     */
    foreach ($tables->get() as $key => $value) {
      if($schema->get('schema/table/'.$value['TABLE_NAME'])){
        $tables->set("$key/in_schema", true);
      }else{
        $tables->set("$key/in_schema", false);
      }
    }
    /**
     * 
     */
    $insert_sql = null;
    foreach ($tables->get() as $v) {
      $table = new PluginWfArray($v);
      if(!$table->get('in_schema')){
        continue;
      }
      $sql = "INSERT INTO ".$table->get('TABLE_NAME')." ([FIELDS]) VALUES [VALUES];";
      /**
       * Fields
       */
      $fields = $this->runSQL("SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$schema->get('mysql/database')."' AND TABLE_NAME = '".$table->get('TABLE_NAME')."';", 'COLUMN_NAME');
      $str = null;
      $str_fields = null;
      foreach ($fields->get() as $v) {
        $field = new PluginWfArray($v);
        $str_fields .= ','.$field->get('COLUMN_NAME');
      }
      $str_fields = wfPhpfunc::substr($str_fields, 1);
      $sql = wfPhpfunc::str_replace('[FIELDS]', $str_fields, $sql);
      /**
       * Values
       */
      $values = $this->runSQL("SELECT * FROM `".$table->get('TABLE_NAME')."`;");
      $str = null;
      $rows = array();
      foreach ($values->get() as $v) {
        if($multiline){
          $str = null;
        }
        $str .= "(";
        foreach ($v as $k2 => $v2) {
          if( wfPhpfunc::strstr($fields->get("$k2/COLUMN_TYPE"), 'int(') || wfPhpfunc::strstr($fields->get("$k2/COLUMN_TYPE"), 'double(') ){
            if($v2){
              $str .= $v2.',';
            }else{
              $str .= "NULL,";
            }
          }elseif(($fields->get("$k2/COLUMN_TYPE")=='datetime' || $fields->get("$k2/COLUMN_TYPE")=='date' || $fields->get("$k2/COLUMN_TYPE")=='timestamp')){
            if($v2){
              $v2 = wfPhpfunc::str_replace("'", "", $v2);
              $str .= "'".$v2."',";
            }else{
              $str .= "NULL,";
            }
          }else{
            if($v2){
              $v2 = wfPhpfunc::str_replace("'", "\'", $v2);
              $str .= "'".$v2."',";
            }else{
              $str .= "NULL,";
            }
          }
        }
        $str = wfPhpfunc::substr($str, 0, wfPhpfunc::strlen($str)-1);
        $str .= ")";
        if(!$multiline){
          $str .= ",";
        }else{
          $rows[] = $str;
        }
      }
      if(!$multiline){
        $str = wfPhpfunc::substr($str, 0, wfPhpfunc::strlen($str)-1);
        $sql = wfPhpfunc::str_replace('[VALUES]', $str, $sql);
        if(!$str){
          $sql = '#'.$sql;
        }
        $insert_sql .= $sql."\n";
      }else{
        foreach ($rows as $key => $value) {
          $insert_sql .= wfPhpfunc::str_replace('[VALUES]', $value, $sql)."\n";
        }
      }
    }
    $element = $this->getYml('element/data_export.yml');
    $element->setByTag(array('insert_sql' => $insert_sql));
    wfDocument::renderElement($element->get());
  }
  /**
   * Generate script in textarea.
   */
  public function page_script_generator_run(){
    $page = $this->getYml('page/script_generator_run.yml');
    $foreing_key = false;
    if(wfRequest::get('foreing_key')=='true'){
      $foreing_key = true;
    }
    ini_set('max_execution_time', 120);
    $script = null;
    $get_fields = $this->getFields();
    foreach ($get_fields->get('schema/table') as $key => $value) {
      $script .= "\n".$this->db_create_table_script($key, $foreing_key, wfRequest::get('engine'), $get_fields);
    }
    $page->setByTag(array('script' => $script));
    wfDocument::mergeLayout($page->get());
  }
  /**
   * One database.
   */
  public function page_db(){
    /**
     * 
     */
    $schema = $this->getFields();
    $page = $this->getYml('page/db.yml');
    $id = wfRequest::get('id');
    $class = wfGlobals::get('class');
    /**
     * Settings.
     */
    $page->setByTag(array('id' => $id));
    $page->setByTag($this->db->get());
    $page->setByTag($this->db->get('mysql'), 'mysql');
    $schemas = array();
    foreach ($this->db->get('schema') as $key => $value) {
      $schemas[] = wfDocument::createHtmlElement('div', $value);
    }
    $page->setByTag(array('list' => $schemas), 'schema');
    /**
     * Error.
     */
    $page->setByTag($schema->get('errors/schema'), 'schema');
    $page->setByTag($schema->get('errors/table'), 'table');
    $page->setByTag($schema->get('errors/field'), 'field');
    $page->setByTag($schema->get('errors/attribute'), 'attribute');
    $page->setByTag($schema->get('errors/foreing_key'), 'foreing_key');
    /**
     * Field.
     */
    foreach ($schema->get('schema/field') as $key => $value) {
      $item = new PluginWfArray($value);
      if(!$item->get('check_field_exist')){
        $schema->set("schema/field/$key/check_field_exist", array(wfDocument::createHtmlElement('a', 'Create', array('onclick' => "PluginDbSync_v1.field_create_from_db_row('".$item->get('schema_table_name')."', '".$item->get('schema_field_name')."', this)"))));
      }
    }
    $tr = array();
    foreach ($schema->get('schema/field') as $key => $value) {
      $row = $this->getYml('element/db_field_row.yml');
      $row->setByTag($value);
      $row->setByTag(array('key' => $key));
      $tr[] = $row->get();
    }
    $page->setByTag(array('tbody' => $tr));
    /**
     * Tables.
     */
    $tr = array();
    foreach ($schema->get('schema/table') as $key => $value) {
      $row = $this->getYml('element/db_tables_row.yml');
      $row->setByTag($value);
      $row->setByTag(array('key' => $key));
      $tr[] = $row->get();
    }
    $page->setByTag(array('tbody' => $tr), 'tables');
    /**
     * Foreing keys.
     */
    $temp = $this->db_foreing_keys();
    $page->setByTag(array('foreing_keys' => $temp->get()));
    /**
     * 
     */
    wfDocument::mergeLayout($page->get());
  }
  private function db_table_create($table_name){
    $sql = $this->db_create_table_script($table_name);
    $this->runSQL($sql);
    /**
     * Index
     */
    foreach($this->db_create_table_index_script($table_name) as $v){
      $this->runSQL($v);
    }
    /**
     * 
     */
    return $sql;
  }
  private function db_field_create($table_name, $field_name){
    $field_data = $this->getField($table_name, $field_name);
    $field_script = $this->db_create_field_script($field_data);
    $sql = "ALTER TABLE `$table_name` ADD COLUMN $field_script;";
    $this->runSQL($sql);
    return $sql;
  }
  /**
   * Add foreing key.
   */
  private function db_field_create_foreing_key($table_name, $field_name){
    $field_data = $this->getField($table_name, $field_name);
    $reference_table = $field_data->get('schema_field_foreing_key/reference_table');
    $reference_field = $field_data->get('schema_field_foreing_key/reference_field');
    $on_delete =       $field_data->get('schema_field_foreing_key/on_delete');
    $on_update =       $field_data->get('schema_field_foreing_key/on_update');
    $index_name = $table_name.'_'.$field_name.'_fk_idx';
    $constraint_name = $table_name.'_'.$field_name.'_fk';
    $sql = <<<string
      ALTER TABLE `$table_name` 
      ADD INDEX $index_name ($field_name ASC);
string;
    $this->runSQL($sql);
    $sql = <<<string
      ALTER TABLE `$table_name` 
      ADD CONSTRAINT $constraint_name 
        FOREIGN KEY ($field_name)
        REFERENCES $reference_table ($reference_field)
        ON DELETE $on_delete
        ON UPDATE $on_update;
string;
    $this->runSQL($sql);
    return null;
  }
  /**
   * Update foreing key.
   */
  private function db_field_update_foreing_key($table_name, $field_name){
    $field_data = $this->getField($table_name, $field_name);
    $reference_table = $field_data->get('schema_field_foreing_key/reference_table');
    $reference_field = $field_data->get('schema_field_foreing_key/reference_field');
    $on_delete =       $field_data->get('schema_field_foreing_key/on_delete');
    $on_update =       $field_data->get('schema_field_foreing_key/on_update');
    $constraint_name = $table_name.'_'.$field_name.'_fk';
    $sql = <<<string
      ALTER TABLE `$table_name`
      DROP FOREIGN KEY $constraint_name;
string;
    $this->runSQL($sql);
    $sql = <<<string
      ALTER TABLE `$table_name` 
      ADD CONSTRAINT $constraint_name 
        FOREIGN KEY ($field_name)
        REFERENCES $reference_table ($reference_field)
        ON DELETE $on_delete
        ON UPDATE $on_update;
string;
    $this->runSQL($sql);
    return null;
  }
  private function db_table_drop($table_name){
    $sql = "drop table $table_name;";
    $this->runSQL($sql);
    return $sql;
  }
  private function db_table_count($table_name){
    $sql = "select count(*) as count from `$table_name`;";
    $rs = $this->runSQL($sql);
    return $rs->get('0/count');
  }
  private function db_table_select_all($table_name){
    $sql = "select * from `$table_name` limit 1000;";
    $rs = $this->runSQL($sql);
    return $rs;
  }
  private function db_foreing_keys($table_name = null){
    $database = $this->db->get('mysql/database');
    if(is_null($table_name)){
      $sql = <<<string
        SELECT 
        col.constraint_name,
        col.table_name                        as table_name,
        col.column_name                     as field_name,
        col.referenced_table_name     as foreing_key_reference_table,
        col.referenced_column_name as foreing_key_reference_field,
        constr.delete_rule                    as foreing_key_on_delete,
        constr.update_rule                   as foreing_key_on_update
        from information_schema.KEY_COLUMN_USAGE as col
        inner join information_schema.referential_constraints as constr on col.constraint_name=constr.constraint_name 
        where col.table_schema = '$database' and constr.constraint_schema = '$database' and col.constraint_name<>'PRIMARY'
         ; 
string;
    }else{
      $sql = <<<string
        SELECT 
        col.constraint_name,
        col.table_name                        as table_name,
        col.column_name                     as field_name,
        col.referenced_table_name     as foreing_key_reference_table,
        col.referenced_column_name as foreing_key_reference_field,
        constr.delete_rule                    as foreing_key_on_delete,
        constr.update_rule                   as foreing_key_on_update
        from information_schema.KEY_COLUMN_USAGE as col
        inner join information_schema.referential_constraints as constr on col.constraint_name=constr.constraint_name 
        where col.table_schema = '$database' and constr.constraint_schema = '$database' and col.constraint_name<>'PRIMARY' and (col.table_name='$table_name' or col.referenced_table_name='$table_name')
         ; 
string;
    }
    $rs = $this->runSQL($sql);
    return $rs;
  }
  
  private function db_create_field_script($item){
    $type = $item->get('schema_field_type');
    if(strtolower($type)=='timestamp'){
      $type .= ' NULL';  
    }
    $not_null = null;
    if($item->get('schema_field_not_null')){
      $not_null = " NOT NULL";
    }
    $default = null;
    $auto_increment = null;
    if($item->get('schema_field_auto_increment')){
      $auto_increment = " auto_increment";
    }else{
      if(wfPhpfunc::strlen($item->get('schema_field_default'))){
        if($item->get('schema_field_default') == null || strtolower($item->get('schema_field_default')) == 'null' || strtoupper($item->get('schema_field_default'))=='CURRENT_TIMESTAMP'){
          $default = " default ".$item->get('schema_field_default')."";
        }else{
          $default = " default '".$item->get('schema_field_default')."'";
        }
      }
    }
    return '`'.$item->get('schema_field_name').'` '.$type.$not_null.$default.$auto_increment;
  }
  /**
   * One table create script.
   */
  private function db_create_table_index_script($table_name){
    $table_data = $this->getTable($table_name);
    $sql = array();
    $temp = "ALTER TABLE `[table_name]` ADD INDEX `[index_name]` ([fields]);";
    if($table_data->get('index')){
      foreach($table_data->get('index') as $k => $v){
        $sql2 = $temp;
        $sql2 = wfPhpfunc::str_replace('[table_name]', $table_name, $sql2);
        $sql2 = wfPhpfunc::str_replace('[index_name]', $k, $sql2);
        $fields = '';
        foreach($v['columns'] as $v2){
          $fields .= ",`$v2` ASC";
        }
        $sql2 = wfPhpfunc::str_replace('[fields]', wfPhpfunc::substr($fields,1), $sql2);
        $sql[] = $sql2;
      }
    }
    return $sql;
  }
   /**
   * One table create script.
   */
  private function db_create_table_script($table_name, $foreing_key = true, $engine = 'InnoDB', $get_fields = null){
    /**
     * Get all fields for one table.
     */
    $table_data = $this->getTable($table_name, $get_fields);
    /**
     * Create create script.
     */
    $sql = "CREATE TABLE `$table_name` ([fields][primary_key] [key] [constraint] ) ENGINE=$engine DEFAULT CHARSET=latin1;";
    $fields = null;
    $primary_key = null;
    $tkey = null;
    $constraint = null;
    foreach ($table_data->get('field') as $key => $value) {
      $item = new PluginWfArray($value);
      /**
       * Fields.
       */
      $temp = $this->db_create_field_script($item);
      if(!$fields){
        $fields = $temp;
      }  else {
        $fields .= ','.$temp;
      }
      /**
       * Primary key.
       */
      if($item->get('schema_field_primary_key')){
        $primary_key .= ','.$item->get('schema_field_name');
      }
      /**
       * Foreing key.
       */
      if($foreing_key && $item->get('schema_field_foreing_key')){
        $tkey .= ", KEY ".$item->get('schema_table_name')."_".$item->get('schema_field_name')."_fk (".$item->get('schema_field_name').")";
      }
      /**
       * Constraint.
       */
      if($foreing_key && $item->get('schema_field_foreing_key')){
        $item2 = new PluginWfArray($item->get('schema_field_foreing_key'));
        $on_delete = null;
        if(strtoupper($item2->get('on_delete')) == 'CASCADE'){
          $on_delete = " ON DELETE CASCADE";
        }
        $on_update = null;
        if(strtoupper($item2->get('on_update')) == 'CASCADE'){
          $on_update = " ON UPDATE CASCADE";
        }
        $constraint .= ", CONSTRAINT ".$item->get('schema_table_name')."_".$item->get('schema_field_name')."_fk FOREIGN KEY (".$item->get('schema_field_name').") REFERENCES `".$item2->get('reference_table')."`(".$item2->get('reference_field').")$on_delete$on_update";
      }
    }
    /**
     * Replace in SQL string.
     */
    $sql = wfPhpfunc::str_replace('[fields]', $fields, $sql);
    if($primary_key){
      $primary_key = wfPhpfunc::substr($primary_key, 1);
      $sql = wfPhpfunc::str_replace('[primary_key]', ",PRIMARY KEY ($primary_key)", $sql);
    }else{
      $sql = wfPhpfunc::str_replace('[primary_key]', '', $sql);
    }
    $sql = wfPhpfunc::str_replace('[key]', $tkey, $sql);
    $sql = wfPhpfunc::str_replace('[constraint]', $constraint, $sql);
    /**
     * 
     */
    return $sql;
  }
  /**
   * Page table script.
   */
  public function page_table(){
    /**
     * 
     */
    $page = $this->getYml('page/table.yml');
    $page->setByTag(wfRequest::getAll(), 'get');
    $table_data = $this->getTable(wfRequest::get('table'));
    /**
     * Field.
     */
    $tr = array();
    foreach ($table_data->get('field') as $key => $value) {
      $row = $this->getYml('element/table_field_row.yml');
      $row->setByTag($value);
      $tr[] = $row->get();
    }
    $page->setByTag(array('tbody' => $tr));
    /**
     * Foreing keys.
     */
    $temp = $this->db_foreing_keys($table_data->get('name'));
    $page->setByTag(array('foreing_keys' => $temp->get()));
    /**
     * helper
     */
    $page->setByTag(array(
      'insert' => $this->helper_insert($table_data), 
      'select' => $this->helper_select($table_data), 
      'update' => $this->helper_update($table_data),
      'delete' => $this->helper_delete($table_data)
    ), 'helper');
    /**
     * 
     */
    $page->setByTag($table_data->get());
    wfDocument::mergeLayout($page->get());
  }
  public function page_table_data(){
    $table_data = $this->getTable(wfRequest::get('table'));
    $field = array();
    foreach($table_data->get('field') as $k => $v){
      $field[$v['schema_field_name']] = $v['schema_field_name'];
    }
    $element = wfDocument::getElementFromFolder(__DIR__, __FUNCTION__);
    $element->setByTag(array('field' => $field));
    wfDocument::renderElement($element);
  }
  public function page_table_data_data(){
    $table_data = $this->getTable(wfRequest::get('table'));
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    if($table_data->get('exist')){
      $rs = $this->db_table_select_all($table_data->get('name'));
      $temp = array();
      foreach($rs->get() as $k => $v){
        $i = new PluginWfArray($v);
        $i->set('row_id', $i->get('id'));
        $temp[] = $i->get();
      }
      exit($datatable->set_table_data($temp));
    }else{
      exit($datatable->set_table_data(array()));
    }
  }
  private function helper_insert($table_data){
    $data = ''.$table_data->get('name')."_insert:\n";
    $data .= "  sql: |\n";
    $data .= '    insert into '.$table_data->get('name')."(\n";
    $field = '';
    foreach($table_data->get('field') as $k => $v){
      if($k==0){
        $field .= '    '.$v['schema_field_name']."\n";
      }else{
        $field .= '    ,'.$v['schema_field_name']."\n";
      }
    }
    $data .= $field."    ) values (\n";
    $values = '';
    foreach($table_data->get('field') as $k => $v){
      //$values .= '    rs:'.$v['schema_field_name']."\n";
      if($k==0){
        $values .= "    ?\n";
      }else{
        $values .= "    ,?\n";
      }
    }
    $data .= $values."    );\n";
    $data .= "  params:\n";
    foreach($table_data->get('field') as $k => $v){
      $data .= '    -'."\n";
      $data .= '      type: '.$v['schema_field_type']."\n";
      $data .= '      value: rs:'.$v['schema_field_name']."\n";
    }
    return $data;
  }
  private function helper_select($table_data){
    $data = ''.$table_data->get('name')."_select:\n";
    $data .= "  sql: |\n";
    $data .= '    select '."\n";
    $field = '';
    foreach($table_data->get('field') as $k => $v){
      if($k==0){
        $field .= '    '.$v['schema_field_name']."\n";
      }else{
        $field .= '    ,'.$v['schema_field_name']."\n";
      }
    }
    $data .= $field."    from ".$table_data->get('name')."\n";
    $data .= "  select:\n";
    foreach($table_data->get('field') as $k => $v){
      $data .= '    - '.$v['schema_field_name']."\n";
    }
    return $data;
  }
  private function helper_update($table_data){
    $data = ''.$table_data->get('name')."_update:\n";
    $data .= "  sql: |\n";
    $data .= '    update '.$table_data->get('name').' set'."\n";
    $field = '';
    foreach($table_data->get('field') as $k => $v){
      if($k==0){
        $field .= '    '.$v['schema_field_name']."=?\n";
      }else{
        $field .= '    ,'.$v['schema_field_name']."=?\n";
      }
    }
    $data .= $field."    where 1=2 \n";
    $data .= "  params:\n";
    foreach($table_data->get('field') as $k => $v){
      $data .= '    -'."\n";
      $data .= '      type: '.$v['schema_field_type']."\n";
      $data .= '      value: rs:'.$v['schema_field_name']."\n";
    }
    return $data;
  }
  private function helper_delete($table_data){
    $data = ''.$table_data->get('name')."_delete:\n";
    $data .= "  sql: |\n";
    $data .= '    delete from '.$table_data->get('name')."\n";
    $data .= "    where 1=2 \n";
    return $data;
  }
  public function page_table_create(){
    $sql = $this->db_table_create(wfRequest::get('table'));
    exit($sql);
  }
  public function page_table_drop(){
    $sql = $this->db_table_drop(wfRequest::get('table'));
    exit($sql);
  }
  public function page_field(){
    $field_data = $this->getField(wfRequest::get('table'), wfRequest::get('field'));
    $page = $this->getYml('page/field.yml');
    $page->setByTag($field_data->get());
    $page->setByTag(array('field_data' => $field_data->get()));
    wfDocument::mergeLayout($page->get());
  }
  public function page_field_create(){
    $sql = $this->db_field_create(wfRequest::get('table'), wfRequest::get('field'));
    exit($sql);
  }
  public function page_field_create_foreing_key(){
    $this->db_field_create_foreing_key(wfRequest::get('table'), wfRequest::get('field'));
    exit('create foreing key...');
  }
  public function page_field_update_foreing_key(){
    $this->db_field_update_foreing_key(wfRequest::get('table'), wfRequest::get('field'));
    exit('update foreing key...');
  }
  private function getForeingKey($foreing_keys, $table_name, $field_name){
    foreach ($foreing_keys->get() as $key => $value) {
      $item = new PluginWfArray($value);
      if($item->get('table_name')==$table_name && $item->get('field_name')==$field_name){
        return array(
            'reference_table' => $item->get('foreing_key_reference_table'),
            'reference_field' => $item->get('foreing_key_reference_field'),
            'on_delete' => $item->get('foreing_key_on_delete'),
            'on_update' => $item->get('foreing_key_on_update')
                );
      }
    }
    return null;
  }
  private function generateSchema(){
    $foreing_keys = $this->db_foreing_keys();
    $db_schema = $this->runSQL("SELECT * FROM INFORMATION_SCHEMA.COLUMNS where TABLE_SCHEMA='".$this->db->get('mysql/database')."';");
    $schema = new PluginWfArray();
    foreach ($db_schema->get() as $key => $value) {
      $item = new PluginWfArray($value);
      $schema->set($item->get('TABLE_NAME').'/field/'.$item->get('COLUMN_NAME').'/type', $item->get('COLUMN_TYPE'));
      if($item->get('IS_NULLABLE')=='NO'){
        $schema->set($item->get('TABLE_NAME').'/field/'.$item->get('COLUMN_NAME').'/not_null', true);
      }
      if($item->get('COLUMN_KEY')=='PRI'){
        $schema->set($item->get('TABLE_NAME').'/field/'.$item->get('COLUMN_NAME').'/primary_key', true);
      }
      if($item->get('EXTRA')=='auto_increment'){
        $schema->set($item->get('TABLE_NAME').'/field/'.$item->get('COLUMN_NAME').'/auto_increment', true);
      }
      if($item->get('COLUMN_DEFAULT')){
        $schema->set($item->get('TABLE_NAME').'/field/'.$item->get('COLUMN_NAME').'/default', $item->get('COLUMN_DEFAULT'));
      }
      $foreing_key = $this->getForeingKey($foreing_keys, $item->get('TABLE_NAME'), $item->get('COLUMN_NAME'));
      if($foreing_key){
        $schema->set($item->get('TABLE_NAME').'/field/'.$item->get('COLUMN_NAME').'/foreing_key', $foreing_key);
      }
    }
    return $schema;
  }
  /**
   * All data synced schema and db.
   */
  private function getFields(){
    $foreing_keys = $this->db_foreing_keys();
    /**
     * Get db schema.
     */
    $db_schema = $this->runSQL("SELECT * FROM INFORMATION_SCHEMA.COLUMNS where TABLE_SCHEMA='".$this->db->get('mysql/database')."';");
    $field = new PluginWfArray();
    $table = new PluginWfArray();
    /**
     * Get data from multiple schemas.
     */
    $i = 0;
    foreach ($this->db->get('schema') as $key => $value) {
      $item = wfSettings::getSettingsAsObject($value);
      foreach ($item->get('tables') as $key2 => $value2) {
        $item2 = new PluginWfArray($value2);
        $table->set("$key2/description", $item2->get('description'));
        $table->set("$key2/index", $item2->get('index'));
        $table->set("$key2/file", $value);
        foreach ($value2['field'] as $key3 => $value3) {
          $item3 = new PluginWfArray($value3);
          /**
           * 
           */
          if($item3->get('foreing_key')){
            if(!$item3->get('foreing_key/reference_field')){
              $item3->set('foreing_key/reference_field', 'id');
            }
            if(!$item3->get('foreing_key/on_delete')){
              $item3->set('foreing_key/on_delete', 'CASCADE');
            }
            if(!$item3->get('foreing_key/on_update')){
              $item3->set('foreing_key/on_update', 'CASCADE');
            }
          }
          /**
           * 
           */
          $i++;
          $field->set($key2."#".$key3."/number", $i);
          $field->set($key2."#".$key3."/description", $item3->get('description'));
          $field->set($key2."#".$key3."/schema_files/", $value);
          $field->set($key2."#".$key3."/schema_files_count", 0);
          $field->set($key2."#".$key3."/schema_files_name", null);
          $field->set($key2."#".$key3."/schema_table_name", $key2);
          $field->set($key2."#".$key3."/schema_field_name", $key3);
          $field->set($key2."#".$key3."/schema_field_type", $item3->get('type'));
          $field->set($key2."#".$key3."/schema_field_auto_increment", $item3->get('auto_increment'));
          $field->set($key2."#".$key3."/schema_field_default", $item3->get('default'));
          $field->set($key2."#".$key3."/schema_field_not_null", $item3->get('not_null'));
          $field->set($key2."#".$key3."/schema_field_primary_key", $item3->get('primary_key'));
          $field->set($key2."#".$key3."/schema_field_foreing_key", $item3->get('foreing_key'));
          $field->set($key2."#".$key3."/db_field_type", null);
          $field->set($key2."#".$key3."/db_field_default", null);
          $field->set($key2."#".$key3."/db_field_not_null", null);
          $field->set($key2."#".$key3."/db_field_primary_key", null);
          $field->set($key2."#".$key3."/db_field_foreing_key", null);
          $field->set($key2."#".$key3."/option", $item3->get('option'));
          $field->set($key2."#".$key3."/option_json", null);
          if($item3->get('option')){
            $field->set($key2."#".$key3."/option_json", json_encode($item3->get('option')));
          }
          $field->set($key2."#".$key3."/is_extra", false);
        }
        if($item->get('extra/field')){
          foreach ($item->get('extra/field') as $key3 => $value3) {
            $item3 = new PluginWfArray($value3);
            $i++;
            $field->set($key2."#".$key3."/number", $i);
            $field->set($key2."#".$key3."/description", $item3->get('description'));
            $field->set($key2."#".$key3."/schema_files/", $value);
            $field->set($key2."#".$key3."/schema_files_count", 0);
            $field->set($key2."#".$key3."/schema_files_name", null);
            $field->set($key2."#".$key3."/schema_table_name", $key2);
            $field->set($key2."#".$key3."/schema_field_name", $key3);
            $field->set($key2."#".$key3."/schema_field_type", $item3->get('type'));
            $field->set($key2."#".$key3."/schema_field_auto_increment", $item3->get('auto_increment'));
            $field->set($key2."#".$key3."/schema_field_default", $item3->get('default'));
            $field->set($key2."#".$key3."/schema_field_not_null", $item3->get('not_null'));
            $field->set($key2."#".$key3."/schema_field_primary_key", $item3->get('primary_key'));
            $field->set($key2."#".$key3."/db_field_type", null);
            $field->set($key2."#".$key3."/db_field_default", null);
            $field->set($key2."#".$key3."/db_field_not_null", null);
            $field->set($key2."#".$key3."/db_field_primary_key", null);
            $field->set($key2."#".$key3."/db_field_foreing_key", null);
            $field->set($key2."#".$key3."/option", $item3->get('option'));
            $field->set($key2."#".$key3."/option_json", null);
            if($item3->get('option')){
              $field->set($key2."#".$key3."/option_json", json_encode($item3->get('option')));
            }
            $field->set($key2."#".$key3."/is_extra", true);
          }
        }
      }
    }
    /**
     * Check if table and field exist in db.
     */
    foreach ($field->get() as $key => $value) {
      $field->set("$key/check_table_exist",     $this->check_table_exist($field->get("$key/schema_table_name"),                                         $db_schema));
      $field->set("$key/check_field_exist",     $this->check_field_exist($field->get("$key/schema_table_name"), $field->get("$key/schema_field_name"), $db_schema));
      $field->set("$key/check_attribute_match", false);
      $field->set("$key/check_foreing_key_match", false);
    }
    /**
     * Set field attribute.
     */
    foreach ($field->get() as $key => $value) {
      $item = new PluginWfArray($value);
      if($item->get('check_field_exist')){
        $attribute = $this->get_field_attribute($field->get("$key/schema_table_name"), $field->get("$key/schema_field_name"), $db_schema);
        $field->set("$key/db_field_type", $attribute->get('COLUMN_TYPE'));
        $field->set("$key/db_field_default", $attribute->get('COLUMN_DEFAULT'));
        if($attribute->get('IS_NULLABLE')=='NO'){
          $field->set("$key/db_field_not_null", true);
        }elseif($attribute->get('IS_NULLABLE')=='YES'){
          $field->set("$key/db_field_not_null", null);
        }else{
          $field->set("$key/db_field_not_null", null);
        }
        if($attribute->get('COLUMN_KEY')=='PRI'){
          $field->set("$key/db_field_primary_key", true);
        }elseif($attribute->get('COLUMN_KEY')=='MUL'){
          $field->set("$key/db_field_primary_key", null);
        }else{
          $field->set("$key/db_field_primary_key", null);
        }
      }
    }
    /**
     * Set foreing keys.
     */
    foreach ($field->get() as $key => $value) {
      $item = new PluginWfArray($value);
      $field->set("$key/db_field_foreing_key", $this->getForeingKey($foreing_keys, $item->get('schema_table_name'), $item->get('schema_field_name')));
    }
    /**
     * Check foreing key match.
     */
    foreach ($field->get() as $key => $value) {
      $item = new PluginWfArray($value);
      if($item->get('schema_field_foreing_key')==$item->get('db_field_foreing_key')){
        $field->set("$key/check_foreing_key_match", true);
      }
    }
    /**
     * Set attribute miss match.
     */
    foreach ($field->get() as $key => $value) {
      $item = new PluginWfArray($value);
      if(
              $item->get('schema_field_type')        == $item->get('db_field_type') && 
              $item->get('schema_field_default')     == $item->get('db_field_default') && 
              $item->get('schema_field_not_null')    == $item->get('db_field_not_null') && 
              $item->get('schema_field_primary_key') == $item->get('db_field_primary_key')
              ){
        $field->set("$key/check_attribute_match", true);
      }else{
        $field->set("$key/check_attribute_match", false);
      }
    }
    /**
     * Set schema attribute.
     */
    foreach ($field->get() as $key => $value) {
      $item = new PluginWfArray($value);
      $field->set("$key/schema_files_count", sizeof($item->get('schema_files')));
      if(sizeof($item->get('schema_files'))==1){
        $field->set("$key/schema_files_name", $item->get('schema_files/0'));
      }
    }
    /**
     * Errors.
     */
    $errors = new PluginWfArray();
    $errors->set('schema',    array('field' => array(), 'text' => null));
    $errors->set('table',     array('field' => array(), 'table' => array()));
    $errors->set('field',     array('field' => array()));
    $errors->set('attribute', array('field' => array()));
    $errors->set('foreing_key', array('field' => array()));
    foreach ($field->get() as $key => $value) {
      $item = new PluginWfArray($value);
      /**
       * Schema.
       */
      if($item->get('schema_files_count') > 1){
        $errors->set('schema/field/', $key);
        $errors->set('schema/text', $errors->get('schema/text').$key.', ');
      }
      /**
       * Table.
       */
      if($item->get('check_table_exist') <> 'true'){
        $errors->set('table/field/', $key);
        $errors->set('table/table/'.$item->get('schema_table_name'), $item->get('schema_table_name'));
      }
      /**
       * Field.
       */
      if($item->get('check_field_exist') <> 'true'){
        $errors->set('field/field/', $key);
      }
      /**
       * Attribute.
       */
      if($item->get('check_attribute_match') <> true){
        $errors->set('attribute/field/', $key);
      }
      /**
       * Foreing key.
       */
      if($item->get('check_foreing_key_match') <> true){
        $errors->set('foreing_key/field/', $key);
      }
    }
    $errors->set('schema/count', sizeof($errors->get('schema/field')));
    $errors->set('table/count_field', sizeof($errors->get('table/field')));
    $errors->set('table/count_table', sizeof($errors->get('table/table')));
    $errors->set('field/count', sizeof($errors->get('field/field')));
    $errors->set('attribute/count', sizeof($errors->get('attribute/field')));
    $errors->set('foreing_key/count', sizeof($errors->get('foreing_key/field')));
    /**
     * Table.
     */
    $i = 0;
    foreach ($field->get() as $key => $value) {
      $item = new PluginWfArray($value);
      if($table->get($item->get('schema_table_name').'/number')){
        continue;
      }
      $i++;
      $table->set($item->get('schema_table_name').'/number', $i);
      $table->set($item->get('schema_table_name').'/name',   $item->get('schema_table_name'));
      $table->set($item->get('schema_table_name').'/exist',  $item->get('check_table_exist'));
    }
    /**
     * 
     */
    return new PluginWfArray(array('mysql' => $this->db->get('mysql'), 'errors' => $errors->get(), 'schema' => array('table' => $table->get(), 'field' => $field->get())));
  }
  private function getTable($table_name, $get_fields = null){
    $data = array();
    $exist = false;
    $schema_files_name = null;
    if(is_null($get_fields)){
      $get_fields = $this->getFields();
    }
    foreach ($get_fields->get('schema/field') as $key => $value) {
      if(wfPhpfunc::substr($key, 0, wfPhpfunc::strlen($table_name)+1)==$table_name.'#'){
        $exist = $value['check_table_exist'];
        $schema_files_name = $value['schema_files_name'];
        $data[] = $value;
      }
    }
    return new PluginWfArray(array('name' => $table_name, 'exist' => $exist, 'schema_files_name' => $schema_files_name, 'field' => $data, 'index' => $get_fields->get("schema/table/$table_name/index")));
  }
  private function getField($table_name, $field_name){
    $table_data = $this->getTable($table_name);
    foreach ($table_data->get('field') as $key => $value) {
      $item = new PluginWfArray($value);
      if($item->get('schema_table_name')==$table_name && $item->get('schema_field_name')==$field_name){
        return new PluginWfArray($item->get());
      }
    }
    return null;
  }
  private function check_table_exist($table_name, $db_schema){
    $exist = false;
    foreach ($db_schema->get() as $key => $value) {
      $item = new PluginWfArray($value);
      if($item->get('TABLE_NAME')==$table_name){
        $exist = true;
        break;
      }
    }
    return $exist;
  }
  private function check_field_exist($table_name, $column_name, $db_schema){
    $exist = false;
    foreach ($db_schema->get() as $key => $value) {
      $item = new PluginWfArray($value);
      if($item->get('TABLE_NAME')==$table_name && $item->get('COLUMN_NAME')==$column_name){
        $exist = true;
        break;
      }
    }
    return $exist;
  }
  private function get_field_attribute($table_name, $column_name, $db_schema){
    foreach ($db_schema->get() as $key => $value) {
      $item = new PluginWfArray($value);
      if($item->get('TABLE_NAME')==$table_name && $item->get('COLUMN_NAME')==$column_name){
        return new PluginWfArray($value);
      }
    }
    return new PluginWfArray();
  }
  private function getYml($file){
    return wfSettings::getSettingsAsObject('/plugin/db/sync_v1/'.$file);
  }
  private function runSQL($sql, $key_field = 'id'){
    $this->mysql->open($this->db->get('mysql'));
    $test = $this->mysql->runSql($sql, $key_field);
    return new PluginWfArray($test['data']);
  }
  private function executeSQL($sql){
    $this->mysql->open($this->db->get('mysql'));
    $test = $this->mysql->execute($sql);
  }
  public function page_plugin_mail_queue_admin(){
    $page = $this->getYml('page/plugin_mail_queue_admin.yml');
    $schema = $this->getFields();
    $_SESSION['plugin']['mail']['queue_admin']['mysql'] = $schema->get('mysql');
    wfDocument::mergeLayout($page->get());
  }
  public function page_plugin_account_admin_v1(){
    $page = $this->getYml('page/plugin_account_admin_v1.yml');
    $schema = $this->getFields();
    $_SESSION['plugin']['account']['admin_v1']['mysql'] = $schema->get('mysql');
    wfDocument::mergeLayout($page->get());
  }
  public function page_manage(){
    $element = new PluginWfYml(__DIR__.'/page/manage.yml');
    wfDocument::renderElement($element);
  }
  public function page_form(){
    $element = wfDocument::getElementFromFolder(__DIR__, __FUNCTION__);
    $element->setByTag(array('form' => $this->form_build()->get()));
    wfDocument::renderElement($element);
  }
  public function page_form_capture(){
    $element = wfDocument::getElementFromFolder(__DIR__, __FUNCTION__);
    $element->setByTag(array('form' => $this->form_build()->get()));
    wfDocument::renderElement($element);
  }
  public function form_capture($form){
    /**
     * 
     */
    wfUser::setSession('plugin/db/sync/form_capture', wfRequest::getAll());
    /**¨
     * 
     */
    $table_data = $this->getTable(wfRequest::get('table'));
    /**
     *
     */
    if(wfRequest::get('_new')=='Yes'){
      /**
       * insert
       */
      $db_form_capture_insert = new PluginWfYml(__DIR__.'/mysql/sql.yml', 'db_form_capture_insert');
      /**
       * table
       */
      $db_form_capture_insert->set('sql', str_replace('[table]', $table_data->get('name'), $db_form_capture_insert->get('sql')));
      $db_form_capture_insert->setByTag(wfRequest::getAll());
      /**
       * fields, values, params
       */
      $fields = "(id\n";
      $values = "(?\n";
      $params = array();
      $params[] = array('type' => 's', 'value' => wfRequest::get('row_id'));
      foreach($table_data->get('field') as $k => $v){
        $i = new PluginWfArray($v);
        if($v['schema_field_name']=='id'){
          continue;
        }
        if(in_array($i->get('schema_field_name'), array('created_by', 'created_at', 'updated_by', 'updated_at'))){
          continue; 
        }
        $type = 's';
        if(substr($i->get('schema_field_type'), 0, 7)=='tinyint'){
          $type = 'i';
        }
        $fields .= ','.$i->get('schema_field_name')."\n";
        $values .= ",?\n";
        $params[] = array('type' => $type, 'value' => wfRequest::get($i->get('schema_field_name')), 'name' => $i->get('schema_field_name'));
      }
      $fields .= ")\n";
      $values .= ")\n";
      $db_form_capture_insert->set('sql', str_replace('[fields]', $fields, $db_form_capture_insert->get('sql')));
      $db_form_capture_insert->set('sql', str_replace('[values]', $values, $db_form_capture_insert->get('sql')));
      $db_form_capture_insert->setByTag(array('params' => $params));
      /**
       * execute
       */
      $this->executeSQL($db_form_capture_insert->get());
      /**
       * 
       */
      wfRequest::set('original_id', wfRequest::get('row_id'));
    }else{
      /**
       * update
       */
      $db_form_capture_update = new PluginWfYml(__DIR__.'/mysql/sql.yml', 'db_form_capture_update');
      $params = array();
      $params[] = array('type' => 's', 'value' => wfRequest::get('row_id'));
      $fields = ",id=?\n";
      /**
       * table
       */
      $db_form_capture_update->set('sql', str_replace('[table]', $table_data->get('name'), $db_form_capture_update->get('sql')));
      /**
       * field, params
       */
      foreach($table_data->get('field') as $k => $v){
        $i = new PluginWfArray($v);
        if($v['schema_field_name']=='id'){
          continue;
        }
        if(in_array($i->get('schema_field_name'), array('created_by', 'created_at', 'updated_by', 'updated_at'))){
          continue; 
        }
        $type = 's';
        if(substr($i->get('schema_field_type'), 0, 7)=='tinyint'){
          $type = 'i';
        }
        $fields .= ','.$i->get('schema_field_name')."=?\n";
        $params[] = array('type' => $type, 'value' => wfRequest::get($i->get('schema_field_name')), 'name' => $i->get('schema_field_name'));
      }
      $db_form_capture_update->set('sql', str_replace('[fields]', substr($fields, 1), $db_form_capture_update->get('sql')));
      $params[] = array('type' => 's', 'value' => wfRequest::get('original_id'));
      $db_form_capture_update->setByTag(array('params' => $params));
      /**
       * 
       */
      $this->executeSQL($db_form_capture_update->get());
    }
    /**
     * 
     */
    $json = json_encode(wfRequest::getAll());
    return array("PluginDbSync_v1.form_capture($json)");
  }
  private function form_build(){
    $table_data = $this->getTable(wfRequest::get('table'));
    $form = new PluginWfYml('/plugin/db/sync_v1/form/form.yml');
    $items = array();
    /**
     * 
     */
    foreach($table_data->get('field') as $k => $v){
      $i = new PluginWfArray($v);
      if(in_array($i->get('schema_field_name'), array('created_by', 'created_at', 'updated_by', 'updated_at'))){
        continue; 
      }
      $j = new PluginWfArray();
      /**
       * type
       */
      if(substr($i->get('schema_field_type'), 0, 7)=='varchar'){
        $j->set('type', 'varchar');
      }elseif(substr($i->get('schema_field_type'), 0, 7)=='tinyint'){
        $j->set('type', 'varchar');
      }elseif(substr($i->get('schema_field_type'), 0, 7)=='text'){
        $j->set('type', 'text');
      }else{
        $j->set('type', 'varchar');
      }
      /**
       * default
       */
      $j->set('default', 'rs:'.$i->get('schema_field_name'));
      /**
       * label
       */
      $label = $i->get('schema_field_name').' ('.$i->get('schema_field_type').')';
      if($i->get('schema_field_foreing_key')){
        $i->set('schema_field_foreing_key/id', wfRequest::get('id'));
        $i->set('schema_field_foreing_key/element_id', 'frm_row_'.$i->get('schema_field_name'));
        $json = json_encode($i->get('schema_field_foreing_key'));
        $label .= " <a href='#' onclick='PluginDbSync_v1.form_foreing_key($json)'>".$i->get('schema_field_foreing_key/reference_table')."</a>";
      }
      $j->set('label', $label);
      /**
       * placeholder
       */
      if(substr($i->get('schema_field_type'), 0, 7)=='varchar'){
        $j->set('placeholder', 'Text (1-'. substr($i->get('schema_field_type'), 8) .'');
      }
      if(substr($i->get('schema_field_type'), 0, 7)=='tinyint'){
        $j->set('placeholder', '0');
      }
      /**
       * rename id to row_id
       */
      if($i->get('schema_field_name')=='id'){
        $i->set('schema_field_name', 'row_id');
      }
      /**
       * 
       */
      $items[$i->get('schema_field_name')] = $j->get(); 
    }
    /**
     * 
     */
    $type = 'hidden';
    $j = new PluginWfArray();
    $j->set('type', $type);
    $j->set('label', 'original_id (for db)');
    $j->set('default', wfRequest::get('row_id'));
    $j->set('mandatory', false);
    $items['original_id'] = $j->get();
    $j = new PluginWfArray();
    $j->set('type', $type);
    $j->set('label', 'id (for db)');
    $j->set('default', wfRequest::get('id'));
    $j->set('mandatory', true);
    $items['id'] = $j->get();
    $j = new PluginWfArray();
    $j->set('type', $type);
    $j->set('label', 'table (for db)');
    $j->set('default', wfRequest::get('table'));
    $j->set('mandatory', true);
    $items['table'] = $j->get();
    $j = new PluginWfArray();
    $j->set('type', $type);
    $j->set('label', 'new (for db)');
    $j->set('default', 'No');
    $j->set('mandatory', true);
    $items['_new'] = $j->get();
    /**
     * 
     */
    $form->set('items', $items);
    /**
     * row_id
     */
    if(wfRequest::get('row_id')!='__add__'){
      $rs = $this->runSQL("select * from ".$table_data->get('name')." where id='".wfRequest::get('row_id')."';");
      $form->setByTag($rs->get(wfRequest::get('row_id')));
      $form->setByTag(array('new' => false), 'form');
    }else{
      $form->set('items/row_id/default', wfCrypt::getUid());
      $form->set('items/_new/default', 'Yes');
      if(wfRequest::get('copy')=='no'){
        $form->setByTag(wfRequest::getAll(), 'rs', true);
      }else{
        $form->setByTag(wfUser::getSession()->get('plugin/db/sync/form_capture'), 'rs', true);
      }
      $form->setByTag(array('new' => true), 'form');
    }
    /**
     * 
     */
    return $form;
  }
  public function page_form_delete(){
    /**¨
     * 
     */
    $table_data = $this->getTable(wfRequest::get('table'));
    $db_form_delete = new PluginWfYml(__DIR__.'/mysql/sql.yml', 'db_form_delete');
    $db_form_delete->set('sql', str_replace('[table]', $table_data->get('name'), $db_form_delete->get('sql')));
    $db_form_delete->setByTag(wfRequest::getAll());
    /**
     * execute
     */
    $this->executeSQL($db_form_delete->get());
    /**
     * 
     */
    wfDocument::renderElementFromFolder(__DIR__, __FUNCTION__);
  }
  public function page_form_foreing_key(){
    $table_data = $this->getTable(wfRequest::get('table'));
    /**
     * field
     */
    $field = array();
    foreach($table_data->get('field') as $k => $v){
      $field[$v['schema_field_name']] = $v['schema_field_name'];
    }
    /**
     * 
     */
    $element = wfDocument::getElementFromFolder(__DIR__, __FUNCTION__);
    $element->setByTag(array('field' => $field));
    wfDocument::renderElement($element);
  }
  public function page_form_foreing_key_data(){
    $table_data = $this->getTable(wfRequest::get('table'));
    $rs_temp = $this->runSQL("select * from ".$table_data->get('name')."");
    $rs = array();
    foreach($rs_temp->get() as $k => $v){
      $rs[] = $v;
    }
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    exit($datatable->set_table_data($rs));
  }
}
