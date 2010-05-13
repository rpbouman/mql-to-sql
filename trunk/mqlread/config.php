<?php

//The yui_url variable is used only by the query editor.
//note: by default, the hosted yui resources are used.
//Set up a local instance of YUI 2 in case you want rely
//on having access to the internett
$yui_url = 'http://yui.yahooapis.com/2.8.1';

//note: the file with the connection data contains an absolute path
//to the sqlite database file. You have to modify that to match the 
//location on your system or else it won't work!
$connection_file_name = '../schema/connection-sqlite.json';

$metadata_file_name = '../schema/schema-sqlite.json';

