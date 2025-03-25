function PluginDbSync_v1(){
  this.data = {item: {id: null}, table: {table: null}, field: {field: null}, line_item_id: null, map_table_description_id: null, map_field_description_id: null}
  this.map_options = {color: 'silver', size: 2, startSocket: 'right', endPlug: 'arrow3', endPlugSize: '24', endPlugColor: 'black'};
  this.db = function(id){
    PluginDbSync_v1.data.item.id = id;
    PluginWfAjax.load('content', 'db/id/'+id);
    $('#modal_dbs_action').modal('hide');
  }
  this.table = function(table){
    PluginDbSync_v1.data.table.table = table;
    PluginWfBootstrapjs.modal({id: 'modal_table', url:'table/id/'+this.data.item.id+'/table/'+table, lable:'Table', size:'lg'});
  }
  this.table_create = function(){
    PluginWfBootstrapjs.modal({id: 'modal_table_create', url:'table_create/id/'+this.data.item.id+'/table/'+this.data.table.table, lable:'Table create'});
  }
  this.table_drop = function(){
    if(confirm('Drop table '+this.data.table.table+'?')){
      $.get('table_drop/id/'+this.data.item.id+'/table/'+this.data.table.table, function( data ) {
        PluginWfAjax.update('modal_table_body');
      });
    }
  }
  this.field = function(field){
    PluginDbSync_v1.data.field.field = field;
    PluginWfBootstrapjs.modal({id: 'modal_field', url:'field/id/'+this.data.item.id+'/table/'+this.data.table.table+'/field/'+field, lable:'Field'});
  }
  this.field_create = function(){
    $.get('field_create/id/'+this.data.item.id+'/table/'+this.data.table.table+'/field/'+this.data.field.field, function( data ) {
      PluginWfAjax.update('modal_field_body');
    });
  }
  this.field_create_from_db_row = function(table, field, element){
    element.innerHTML = '<img src="/plugin/wf/ajax/loading.gif">';
    $.get('field_create/id/'+this.data.item.id+'/table/'+table+'/field/'+field, function( data ) {
      element.innerHTML = '1';
    });
  }
  this.field_create_foreing_key = function(){
    $.get('field_create_foreing_key/id/'+this.data.item.id+'/table/'+this.data.table.table+'/field/'+this.data.field.field, function( data ) {
      PluginWfAjax.update('modal_field_body');
    });
  }
  this.field_update_foreing_key = function(){
    $.get('field_update_foreing_key/id/'+this.data.item.id+'/table/'+this.data.table.table+'/field/'+this.data.field.field, function( data ) {
      PluginWfAjax.update('modal_field_body');
    });
  }
  /**
   * Map
   */
  this.map = function(id){
    PluginDbSync_v1.data.item.id = id;
    PluginWfAjax.load('content', 'map/id/'+this.data.item.id);
    $('#modal_dbs_action').modal('hide');
  }
  this.mapClear = function(){
    $('.bg-warning').removeClass('bg-warning');
    $('.leader-line').remove();
  }
  this.mapFieldClick = function(element){
    var id = element.id;
    $('#'+id).addClass('bg-warning');
    $('.'+id).addClass('bg-warning');
    PluginDbSync_v1.data.line_item_id = id;
    $('.'+id).each(function( i ) {
      var myLine = new LeaderLine(
          document.getElementById(PluginDbSync_v1.data.line_item_id),
          this,
          PluginDbSync_v1.map_options
      );
    });
  }
  this.mapForeingClick = function(element){
    $(element).addClass('bg-warning');
    $('#'+element.getAttribute('data-reference_field')).addClass('bg-warning');

    PluginDbSync_v1.data.line_item_id = element.getAttribute('id');
    $('#'+element.getAttribute('data-reference_field')).each(function( i ) {
      var myLine = new LeaderLine(
          document.getElementById(PluginDbSync_v1.data.line_item_id),
          this,
          PluginDbSync_v1.map_options
      );
    });
  }
  this.mapDraw = function(){
    PluginDbSync_v1.mapClear();
    var fields = document.getElementsByClassName('map-field');
    for(i=0;i<fields.length;i++){
      this.mapDrawField(fields[i]);
    }
  }
  this.mapDrawField = function(element){
    var id = element.id;
    PluginDbSync_v1.data.line_item_id = id;
    $('.'+id).each(function( i ) {
      var myLine = new LeaderLine(
          document.getElementById(PluginDbSync_v1.data.line_item_id),
          this,
          PluginDbSync_v1.map_options
      );
    });
  }
  this.mapTableDescriptionClick = function(element){
    element.id = element.getAttribute('data-table')+'_description';
    this.data.map_table_description_id = element.id;
    PluginWfBootstrapjs.modal({id: 'modal_table_description_form', url:'table_description_form', lable:'Table description'});
  }
  this.mapTableDescriptionForm = function(){
    var element = document.getElementById(this.data.map_table_description_id);
    document.getElementById('table_description_form_id').value = this.data.item.id;
    document.getElementById('table_description_form_table_name').value = element.getAttribute('data-table');
    document.getElementById('table_description_form_description').value = element.innerHTML;
  }
  this.mapTableDescriptionCapture = function(data){
    var element = document.getElementById(this.data.map_table_description_id);
    element.innerHTML = data.description;
    $('#modal_table_description_form').modal('hide');
  }
  this.mapFieldDescriptionClick = function(element){
    element.id = element.getAttribute('data-table')+'_'+element.getAttribute('data-field')+'_description';
    this.data.map_field_description_id = element.id;
    PluginWfBootstrapjs.modal({id: 'modal_field_description_form', url:'field_description_form', lable:'Field description'});
  }
  this.mapFieldDescriptionForm = function(){
    var element = document.getElementById(this.data.map_field_description_id);
    document.getElementById('field_description_form_id').value = this.data.item.id;
    document.getElementById('field_description_form_table_name').value = element.getAttribute('data-table');
    document.getElementById('field_description_form_field_name').value = element.getAttribute('data-field');
    var innerHTML = element.innerHTML;
    if(innerHTML=='&nbsp;'){
      innerHTML = '';
    }
    document.getElementById('field_description_form_description').value = innerHTML;
  }
  this.mapFieldDescriptionCapture = function(data){
    var element = document.getElementById(this.data.map_field_description_id);
    element.innerHTML = data.description;
    $('#modal_field_description_form').modal('hide');
  }
  /**
   * 
   */
  this.schema_generator = function(id){
    PluginDbSync_v1.data.item.id = id;
    PluginWfAjax.load('content', 'schema_generator/id/'+this.data.item.id);
    $('#modal_dbs_action').modal('hide');
  }
  this.script_generator = function(id){
    PluginDbSync_v1.data.item.id = id;
    PluginWfBootstrapjs.modal({id: 'modal_script_generator', url:'script_generator', lable:'Script generator', size: 'sm'});
    $('#modal_dbs_action').modal('hide');
  }
  this.script_generator_run = function(){
    var foreing_key = document.getElementById('foreing_key').checked;
    var engine = document.getElementById('engine').value;
    $('#modal_script_generator').modal('hide');
    PluginWfAjax.load('content', 'script_generator_run/id/'+this.data.item.id+'/foreing_key/'+foreing_key+'/engine/'+engine);    
  }
  this.data_export = function(id){
    PluginDbSync_v1.data.item.id = id;
    PluginWfBootstrapjs.modal({id: 'modal_data_export', url:'data_export/id/'+this.data.item.id, lable:'Data export', size: 'lg'});
    $('#modal_dbs_action').modal('hide');
  }
  this.plugin_mail_queue_admin = function(element){
    PluginDbSync_v1.data.item.id = element.getAttribute('data-key');
    PluginWfBootstrapjs.modal({id: 'modal_mail_queue_admin', url:'plugin_mail_queue_admin/id/'+this.data.item.id, lable: element.innerHTML, size: 'lg'});
    $('#modal_dbs_action').modal('hide');
  }
  this.plugin_account_admin_v1 = function(element){
    PluginDbSync_v1.data.item.id = element.getAttribute('data-key');
    PluginWfBootstrapjs.modal({id: 'modal_account_admin_v1', url:'plugin_account_admin_v1/id/'+this.data.item.id, lable: element.innerHTML, size: 'lg'});
    $('#modal_dbs_action').modal('hide');
  }
  this.manage = function(btn){
    PluginWfBootstrapjs.modal({id: 'modal_manage', url:'manage', lable: btn.innerHTML});
  }
  this.dbs_action = function (data){
    PluginWfBootstrapjs.modal({id: 'modal_dbs_action', url:'dbs_action?id='+data.data_key, lable: 'Action', size: 'xl'});
  }
  this.form = function(data){
    if(data.row_id){
      PluginWfBootstrapjs.modal({id: 'modal_form', url:'form/row_id/'+data.row_id+'/table/'+data.table+'/id/'+data.id+'/copy/'+data.copy, lable: 'Form', size: 'lg'});
    }else{
      alert('Could not edit due to lack of id field!');
    }
  }
  this.form_capture = function(data){
    if(data._new=='No'){
      PluginWfAjax.load('modal_form_body', 'form/row_id/'+data.row_id+'/table/'+data.table+'/id/'+data.id);
    }else{
      PluginWfAjax.load('modal_form_body', 'form/row_id/'+data.row_id+'/table/'+data.table+'/id/'+data.id);
    }
  }
  this.form_delete = function(data){
    PluginWfBootstrapjs.confirm({content: 'Are you sure to delete?', method: function(){PluginDbSync_v1.form_delete_confirmed();}, data: data });    
  }
  this.form_delete_confirmed = function(){
    var data = PluginWfBootstrapjs.confirm_data;
    if(data.ok){
      PluginWfBootstrapjs.modal({id: 'modal_form_delete', url:'form_delete/row_id/'+data.data.row_id+'/table/'+data.data.table+'/id/'+data.data.id, lable: 'Delete', size: 'sm', 'fade': false});
    }
  }
  this.form_foreing_key_data = null;
  this.form_foreing_key = function(data){
    this.form_foreing_key_data = data;
    PluginWfBootstrapjs.modal({id: 'modal_form_foreing_key', url:'form_foreing_key/table/'+data.reference_table+'/id/'+data.id, lable: 'Table: '+data.reference_table, size: 'lg'});
  }
  this.form_foreing_key_row = function(data){
    $('#modal_form_foreing_key').modal('hide');
    document.getElementById(PluginDbSync_v1.form_foreing_key_data.element_id).value = data.id;
  }
  this.query_view = function(btn){
    PluginWfBootstrapjs.modal({id: 'modal_query_view', url:'query_view/query_id/'+btn.getAttribute('data-key')+'/id/'+btn.getAttribute('data-id'), lable: btn.innerHTML, size: 'xl'});
  }
}
var PluginDbSync_v1 = new PluginDbSync_v1();
