# Buto-Plugin-DbSync_v1
Plugin to sync multiple databases against multiple schema files. Webmaster protected. 
One could set param settings/security to false to temporary disable security.


Url in this case: "/dbsync/start". 

```
plugin_modules:
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
            - _multiple_schema_files_
```

