function PluginDbSync_v1(){
  this.data = {item: {id: null}, table: {table: null}, field: {field: null}, line_item_id: null, map_table_description_id: null, map_field_description_id: null}
  this.map_options = {color: 'silver', size: 2, startSocket: 'right', endPlug: 'arrow3', endPlugSize: '24', endPlugColor: 'black'};
  this.db = function(id){
    PluginDbSync_v1.data.item.id = id;
    PluginWfAjax.load('content', 'db/id/'+id);    
  }
  this.table = function(table){
    PluginDbSync_v1.data.table.table = table;
    PluginWfBootstrapjs.modal({id: 'modal_table', url:'table/id/'+this.data.item.id+'/table/'+table, lable:'Table', size:'lg'});
  }
  this.table_create = function(){
    $.get('table_create/id/'+this.data.item.id+'/table/'+this.data.table.table, function( data ) {
      PluginWfAjax.update('modal_table_body');
    });
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
  }
  this.mapClear = function(){
    $('.bg-danger').removeClass('bg-danger');
    $('.leader-line').remove();
  }
  this.mapFieldClick = function(element){
    var id = element.id;
    $('#'+id).addClass('bg-danger');
    $('.'+id).addClass('bg-danger');
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
    $(element).addClass('bg-danger');
    $('#'+element.getAttribute('data-reference_field')).addClass('bg-danger');

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
    if(innerHTML=='&nbsp;&nbsp;&nbsp;&nbsp;'){
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
  }
  this.script_generator = function(id){
    PluginDbSync_v1.data.item.id = id;
    PluginWfBootstrapjs.modal({id: 'modal_script_generator', url:'script_generator', lable:'Script generator', size: 'sm'});
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
  }
  this.plugin_mail_queue_admin = function(element){
    PluginDbSync_v1.data.item.id = element.getAttribute('data-key');
    PluginWfBootstrapjs.modal({id: 'modal_mail_queue_admin', url:'plugin_mail_queue_admin/id/'+this.data.item.id, lable: element.innerHTML, size: 'lg'});
  }
  this.plugin_account_admin_v1 = function(element){
    PluginDbSync_v1.data.item.id = element.getAttribute('data-key');
    PluginWfBootstrapjs.modal({id: 'modal_account_admin_v1', url:'plugin_account_admin_v1/id/'+this.data.item.id, lable: element.innerHTML, size: 'lg'});
  }
}
var PluginDbSync_v1 = new PluginDbSync_v1();




































