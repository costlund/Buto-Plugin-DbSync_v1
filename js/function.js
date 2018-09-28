function PluginDbSync_v1(){
  this.data = {item: {id: null}, table: {table: null}, field: {field: null}}
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
  this.map = function(){
    PluginWfAjax.load('content', 'map/id/'+this.data.item.id);    
  }
}
var PluginDbSync_v1 = new PluginDbSync_v1();
