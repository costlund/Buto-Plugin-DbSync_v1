<?php
class PluginDbSync_v1{
  /**
   *
   */
  private $settings = null;
  private $db = null;
  /**
   * 
   */
  function __construct($buto = false) {
    if($buto){
      set_time_limit(60*20);
      ini_set('memory_limit', '2048M');
      wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/db/sync_v1/layout');
      wfPlugin::includeonce('wf/array');
      wfPlugin::includeonce('wf/yml');
      $this->settings = new PluginWfArray(wfArray::get($GLOBALS, 'sys/settings/plugin_modules/'.wfArray::get($GLOBALS, 'sys/class').'/settings'));
      if(!wfUser::hasRole("webmaster") && $this->settings->get('security')!==false){
        exit('Role webmaster is required!');
      }
      $this->settings->set('item', wfSettings::getSettingsFromYmlString($this->settings->get('item')));
      $id = wfRequest::get('id');
      if(strlen($id)){
        $this->db = new PluginWfArray($this->settings->get("item/$id"));
        $this->db->set('mysql', wfSettings::getSettingsFromYmlString($this->db->get('mysql')));
      }
      /**
       * Enable.
       */
      wfPlugin::enable('datatable/datatable_1_10_16');
      wfPlugin::enable('wf/table');
      wfPlugin::enable('twitter/bootstrap335v');
      wfPlugin::enable('wf/embed');
      wfPlugin::enable('form/form_v1');
      wfPlugin::enable('bootstrap/alertwait');
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
    $items = array();
    foreach ($this->settings->get('item') as $key => $value) {
      $element = $this->getYml('element/db.yml');
      $i = new PluginWfArray($value);
      $i->set('key', $key);
      $i->set('mysql', wfSettings::getSettingsFromYmlString($i->get('mysql')));
      $element->setByTag($i->get());
      $element->setByTag($i->get('mysql'), 'mysql');
      $schemas = array();
      foreach ($i->get('schema') as $key => $value) {
        $schemas[] = wfDocument::createHtmlElement('div', $value);
      }
      $element->setByTag(array('list' => $schemas), 'schema');
      $items[] = $element->get();
    }
    $page = $this->getYml('page/dbs.yml');
    $page->setByTag(array('items' => $items));
    wfDocument::mergeLayout($page->get());
  }
  public function page_map(){
    $schema = $this->getFields();
    foreach ($schema->get('schema/field') as $key => $value) {
      $item = new PluginWfArray($value);
      $schema_table_name = $item->get('schema_table_name');
      $schema_field_name = $item->get('schema_field_name');
      $item->set('id', $item->get('schema_table_name').'__'.$item->get('schema_field_name'));
      $item->set('reference_field', $item->get('schema_field_foreing_key/reference_table').'__'.$item->get('schema_field_foreing_key/reference_field'));
      if($item->get('schema_field_foreing_key')){
        $item->set('field_class', 'map-field bg-success '.$item->get('schema_field_foreing_key/reference_table').'__'.$item->get('schema_field_foreing_key/reference_field'));
      }else{
        $item->set('field_class', 'map-field bg-success');
      }
      $item->set('foreing_key_id', $item->get('schema_table_name').'__'.$item->get('schema_field_name').'_fk');
      $schema->set("schema/table/$schema_table_name/field/$schema_field_name", $item->get());
    }
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
        if($j->get('schema_files_name') != $schema_files_name){
          $schema_files_name = $j->get('schema_files_name');
          /**
           * Links
           */
          $id = str_replace('/', '_', $schema_files_name);
          $id = str_replace('.', '_', $id);
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
          $j->set('description', '&nbsp;&nbsp;&nbsp;&nbsp;');
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
    $schema = $this->getFields();
    $tables = $this->runSQL("SELECT TABLE_NAME FROM information_schema.tables where TABLE_TYPE='BASE TABLE' and TABLE_SCHEMA='".$schema->get('mysql/database')."' limit 1000");
    $insert_sql = null;
    foreach ($tables->get() as $v) {
      $table = new PluginWfArray($v);
      $sql = "INSERT INTO ".$table->get('TABLE_NAME')." ([FIELDS]) VALUES [VALUES];";
      
      /**
       * Fields
       */
      $fields = $this->runSQL("SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$schema->get('mysql/database')."' AND TABLE_NAME = '".$table->get('TABLE_NAME')."';", 'COLUMN_NAME');
      $str = null;
      foreach ($fields->get() as $v) {
        $field = new PluginWfArray($v);
        $str .= ','.$field->get('COLUMN_NAME');
      }
      $str = substr($str, 1);
      $sql = str_replace('[FIELDS]', $str, $sql);
      /**
       * Values
       */
      $values = $this->runSQL("SELECT * FROM `".$table->get('TABLE_NAME')."`;");
      $str = null;
      foreach ($values->get() as $v) {
        $str .= "(";
        foreach ($v as $k2 => $v2) {
          if( strstr($fields->get("$k2/COLUMN_TYPE"), 'int(') || strstr($fields->get("$k2/COLUMN_TYPE"), 'double(') ){
            if($v2){
              $str .= $v2.',';
            }else{
              $str .= "NULL,";
            }
          }elseif(($fields->get("$k2/COLUMN_TYPE")=='datetime' || $fields->get("$k2/COLUMN_TYPE")=='date' || $fields->get("$k2/COLUMN_TYPE")=='timestamp')){
            if($v2){
              $str .= "'".$v2."',";
            }else{
              $str .= "NULL,";
            }
          }else{
            if($v2){
              $str .= "'".$v2."',";
            }else{
              $str .= "NULL,";
            }
          }
        }
        $str = substr($str, 0, strlen($str)-1);
        $str .= "),";
      }
      $str = substr($str, 0, strlen($str)-1);
      $sql = str_replace('[VALUES]', $str, $sql);
      if(!$str){
        $sql = '#'.$sql;
      }
      /**
       * 
       */
      $insert_sql .= $sql."\n";
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
    //wfHelp::yml_dump($schema, true);
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
      //$row->set('attribute/onclick', "PluginDbSync_v1.table('$key')");
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
    return null;
  }
  private function db_field_create($table_name, $field_name){
    $field_data = $this->getField($table_name, $field_name);
    $field_script = $this->db_create_field_script($field_data);
    $sql = "ALTER TABLE `$table_name` ADD COLUMN $field_script;";
    $this->runSQL($sql);
    return null;
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
    return null;
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
      if(strlen($item->get('schema_field_default'))){
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
        $constraint .= ", CONSTRAINT ".$item->get('schema_table_name')."_".$item->get('schema_field_name')."_fk FOREIGN KEY (".$item->get('schema_field_name').") REFERENCES ".$item2->get('reference_table')."(".$item2->get('reference_field').")$on_delete$on_update";
      }
    }
    /**
     * Replace in SQL string.
     */
    $sql = str_replace('[fields]', $fields, $sql);
    if($primary_key){
      $primary_key = substr($primary_key, 1);
      $sql = str_replace('[primary_key]', ",PRIMARY KEY ($primary_key)", $sql);
    }else{
      $sql = str_replace('[primary_key]', null, $sql);
    }
    $sql = str_replace('[key]', $tkey, $sql);
    $sql = str_replace('[constraint]', $constraint, $sql);
    /**
     * 
     */
    return $sql;
  }
  /**
   * Page table script.
   */
  public function page_table(){
    $page = $this->getYml('page/table.yml');
    $table_data = $this->getTable(wfRequest::get('table'));
    if($table_data->get('exist')){
      $rs = $this->db_table_select_all($table_data->get('name'));
      $page->setByTag(array('rs_select_all' => $rs->get()));
      $table_data->set('count', $this->db_table_count($table_data->get('name')));
    }else{
      $table_data->set('count', null);
    }
    /**
     * Field.
     */
    $tr = array();
    foreach ($table_data->get('field') as $key => $value) {
      $row = $this->getYml('element/table_field_row.yml');
      $row->setByTag($value);
      //$row->setByTag(array('key' => $key));
      $tr[] = $row->get();
    }
    $page->setByTag(array('tbody' => $tr));
    /**
     * Foreing keys.
     */
    $temp = $this->db_foreing_keys($table_data->get('name'));
    $page->setByTag(array('foreing_keys' => $temp->get()));
    /**
     * 
     */
    $page->setByTag($table_data->get());
    wfDocument::mergeLayout($page->get());
  }
  public function page_table_create(){
    $this->db_table_create(wfRequest::get('table'));
    exit('create...');
  }
  public function page_table_drop(){
    $this->db_table_drop(wfRequest::get('table'));
    exit('drop...');
  }
  public function page_field(){
//    $schema = $this->getFields();
//    wfHelp::yml_dump($schema->get('schema/field/'.wfRequest::get('table').'#'.wfRequest::get('field')));
    $field_data = $this->getField(wfRequest::get('table'), wfRequest::get('field'));
    //wfHelp::yml_dump($field_data);
    $page = $this->getYml('page/field.yml');
    $page->setByTag($field_data->get());
    $page->setByTag(array('field_data' => $field_data->get()));
    wfDocument::mergeLayout($page->get());
  }
  public function page_field_create(){
    $this->db_field_create(wfRequest::get('table'), wfRequest::get('field'));
    exit('create field...');
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
//        if($table_name=='memb_account' && $field_name=='country_id'){
//          wfHelp::yml_dump($item, true);
//        }
        return array(
            'reference_table' => $item->get('foreing_key_reference_table'),
            'reference_field' => $item->get('foreing_key_reference_field'),
            'on_delete' => $item->get('foreing_key_on_delete'),
            'on_update' => $item->get('foreing_key_on_update')
                );
      }
    }
    //return array('table' => $table_name, 'field' => $field_name);
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
        $table->set("$key2/file", $value);
        foreach ($value2['field'] as $key3 => $value3) {
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
          $field->set($key2."#".$key3."/schema_field_foreing_key", $item3->get('foreing_key'));
          $field->set($key2."#".$key3."/db_field_type", null);
          $field->set($key2."#".$key3."/db_field_default", null);
          $field->set($key2."#".$key3."/db_field_not_null", null);
          $field->set($key2."#".$key3."/db_field_primary_key", null);
          $field->set($key2."#".$key3."/db_field_foreing_key", null);
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
        //$field->set("$key/attribute", $attribute->get());
      }
    }
    /**
     * Set foreing keys.
     */
    foreach ($field->get() as $key => $value) {
      $item = new PluginWfArray($value);
      $field->set("$key/db_field_foreing_key", $this->getForeingKey($foreing_keys, $item->get('schema_table_name'), $item->get('schema_field_name')));
      //$field->set("$key/db_field_foreing_key", 333);
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
      if(substr($key, 0, strlen($table_name)+1)==$table_name.'#'){
        $exist = $value['check_table_exist'];
        $schema_files_name = $value['schema_files_name'];
        $data[] = $value;
      }
    }
    return new PluginWfArray(array('name' => $table_name, 'exist' => $exist, 'schema_files_name' => $schema_files_name, 'field' => $data));
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
    wfPlugin::includeonce('wf/mysql');
    $mysql = new PluginWfMysql();
    $mysql->open($this->db->get('mysql'));
    $test = $mysql->runSql($sql, $key_field);
    return new PluginWfArray($test['data']);
  }
}