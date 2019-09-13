# Buto-Plugin-DbSync_v1
Plugin to sync multiple databases against multiple schema files. Webmaster protected. 
One could set param settings/security to false to temporary disable security.

## Features

- Sync schema files agains database.
- Show map with lines between relationships.
- Generate schema yml file content from database.
- Generate create script.
- Generate data export.

## Settings

- Url in this case: "/dbsync/start". 
- One could set "item: yml:/pat/to_file/file.yml".


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

## Map view

On map view one could draw lines between relationships, edit table and field description.

## Plugin mail/queue_admin

If schema file exist a button to use this plugin is visible.