# Buto-Plugin-DbSync_v1

<p>Plugin to sync multiple databases against multiple schema files. Webmaster protected. 
One could set param settings/security to false to temporary disable security.</p>
<ul>
<li>Sync schema files agains database.</li>
<li>Show map with lines between relationships.</li>
<li>Generate schema yml file content from database.</li>
<li>Generate create script.</li>
<li>Generate data export.</li>
</ul>

<a name="key_0"></a>

## Settings

<ul>
<li>Url in this case: "/dbsync/start". </li>
<li>One could set "item: yml:/pat/to_file/file.yml".</li>
</ul>

<a name="key_0_0"></a>

### Theme

<pre><code>plugin_modules:
  dbsync:
    plugin: 'db/sync_v1'
    settings:
      security: true
      admin_layout: _path_to_admin_layout_(optional)
      item:
        _any_key_:
          name: My database
          mysql: _mysql_settings_
          schema:
            - _multiple_schema_files_</code></pre>
<p>String to yml.</p>
<pre><code>plugin_modules:
  dbsync:
    plugin: 'db/sync_v1'
    settings:
      item: 'yml:/../buto_data/theme/[theme]/_db_sync_v1_items.yml'</code></pre>

<a name="key_0_1"></a>

### File

<p>YML file (_db_sync_v1_items.yml).</p>
<pre><code>item:
  -
    name: Web server
    mysql: 'yml:/../buto_data/mysql.yml'
    schema:
      - /plugin/_my_/_schema_/schema.yml</code></pre>
<p>YML file using schema param (_db_sync_v1_items.yml).</p>
<pre><code>schema:
  _web_server_:
    - /plugin/_my_/_schema_/schema.yml
item:
  -
    name: Web server
    mysql: 'yml:/../buto_data/mysql.yml'
    schema: _web_server_</code></pre>

<a name="key_1"></a>

## Usage



<a name="key_1_0"></a>

### Map view

<p>On map view one could draw lines between relationships, edit table and field description.</p>

<a name="key_1_1"></a>

### Plugin mail/queue_admin

<p>If schema file exist a button to use this plugin is visible.</p>

<a name="key_1_2"></a>

### Index

<p>By using the index param it is possible to add index.</p>
<pre><code>tables:
  my_table:
    index:
      my_name_of_index:
        columns:
          - parent_id
          - year
          - month</code></pre>

<a name="key_1_3"></a>

### Schema file

<pre><code>tables:
  account:
    description: Optional
    field:
      id:
        type: varchar(50)
        not_null: true
        primary_key: true
      username:
        type: varchar(50)
      activated:
        type: int(11)
      activate_date:
        type: datetime
  account_log:
    field:
      id:
        type: int(16)
        not_null: true
        auto_increment: true
        primary_key: true
      account_id:
        type: varchar(50)
        foreing_key:
          reference_table: account
          reference_field: id (Optional/Defalut)
          on_delete: CASCADE (Optional/Defalut)
          on_update: CASCADE (Optional/Defalut)
      date:
        type: datetime</code></pre>
<p>Extra field to add to each table if not exist in schema.</p>
<pre><code>extra:
  field:
    created_at:
      type: timestamp
      default: CURRENT_TIMESTAMP
    updated_at:
      type: timestamp
    created_by:
      type: varchar(50)
    updated_by:
      type: varchar(50)</code></pre>

<a name="key_2"></a>

## Pages



<a name="key_2_0"></a>

### page_data_export



<a name="key_2_1"></a>

### page_db



<a name="key_2_2"></a>

### page_dbs



<a name="key_2_3"></a>

### page_dbs_action



<a name="key_2_4"></a>

### page_dbs_data



<a name="key_2_5"></a>

### page_execute_capture



<a name="key_2_6"></a>

### page_field



<a name="key_2_7"></a>

### page_field_create



<a name="key_2_8"></a>

### page_field_create_foreing_key



<a name="key_2_9"></a>

### page_field_description_capture



<a name="key_2_10"></a>

### page_field_description_form



<a name="key_2_11"></a>

### page_field_update_foreing_key



<a name="key_2_12"></a>

### page_form



<a name="key_2_13"></a>

### page_form_capture



<a name="key_2_14"></a>

### page_form_delete



<a name="key_2_15"></a>

### page_form_foreing_key



<a name="key_2_16"></a>

### page_form_foreing_key_data



<a name="key_2_17"></a>

### page_manage



<a name="key_2_18"></a>

### page_map



<a name="key_2_19"></a>

### page_plugin_account_admin_v1



<a name="key_2_20"></a>

### page_plugin_mail_queue_admin



<a name="key_2_21"></a>

### page_schema_generator



<a name="key_2_22"></a>

### page_script_generator



<a name="key_2_23"></a>

### page_script_generator_run



<a name="key_2_24"></a>

### page_start



<a name="key_2_25"></a>

### page_table



<a name="key_2_26"></a>

### page_table_create



<a name="key_2_27"></a>

### page_table_description_capture



<a name="key_2_28"></a>

### page_table_description_form



<a name="key_2_29"></a>

### page_table_drop



<a name="key_3"></a>

## Widgets



<a name="key_4"></a>

## Event



<a name="key_5"></a>

## Construct



<a name="key_5_0"></a>

### __construct



<a name="key_6"></a>

## Methods



<a name="key_6_0"></a>

### capture_table_description



<a name="key_6_1"></a>

### capture_field_description



<a name="key_6_2"></a>

### db_table_create



<a name="key_6_3"></a>

### db_field_create



<a name="key_6_4"></a>

### db_field_create_foreing_key



<a name="key_6_5"></a>

### db_field_update_foreing_key



<a name="key_6_6"></a>

### db_table_drop



<a name="key_6_7"></a>

### db_table_count



<a name="key_6_8"></a>

### db_table_select_all



<a name="key_6_9"></a>

### db_foreing_keys



<a name="key_6_10"></a>

### db_create_field_script



<a name="key_6_11"></a>

### db_create_table_index_script



<a name="key_6_12"></a>

### db_create_table_script



<a name="key_6_13"></a>

### helper_insert



<a name="key_6_14"></a>

### helper_select



<a name="key_6_15"></a>

### helper_update



<a name="key_6_16"></a>

### helper_delete



<a name="key_6_17"></a>

### getForeingKey



<a name="key_6_18"></a>

### generateSchema



<a name="key_6_19"></a>

### getFields



<a name="key_6_20"></a>

### getTable



<a name="key_6_21"></a>

### getField



<a name="key_6_22"></a>

### check_table_exist



<a name="key_6_23"></a>

### check_field_exist



<a name="key_6_24"></a>

### get_field_attribute



<a name="key_6_25"></a>

### getYml



<a name="key_6_26"></a>

### runSQL



<a name="key_6_27"></a>

### executeSQL



<a name="key_6_28"></a>

### form_capture



<a name="key_6_29"></a>

### form_build



