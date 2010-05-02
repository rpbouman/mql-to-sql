<?php
set_include_path(
    get_include_path()
.   PATH_SEPARATOR.'../php'
);
/*****************************************************************************
*   General Functions
******************************************************************************/
function get_last_json_error(){
    if (function_exists('json_last_error')){
        $error = json_last_error();
        $message = $error.': ';
        switch($error){
            case JSON_ERROR_NONE:
                $message .= 'No error has occurred';
                break;
            case JSON_ERROR_DEPTH:
                $message .= 'The maximum stack depth has been exceeded';
                break;
            case JSON_ERROR_CTRL_CHAR	:
                $message .= 'Control character error, possibly incorrectly encoded';
                break;
            case JSON_ERROR_SYNTAX:
                $message .= 'Syntax error';
                break;
            case JSON_ERROR_UTF8:
                $message .= 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
        }
    } 
    else {
        $message = 'function json_last_error() does not exist - no error information available. PHP version: '.phpversion();
    }
    return $message;
}
/*****************************************************************************
*   MQL processing Functions
******************************************************************************/
function analyze_type($type) {
    $type_pattern = '/^\/(\w+)\/(\w+)$/';
    $matches = array();
    if (preg_match($type_pattern, $type, $matches)){
        return array(
            'domain'    => $matches[1]
        ,   'type'     => $matches[2]
        );
    }
    return FALSE;
}

function is_filter_property($value){
    if ($value===NULL) {
        return FALSE;
    }
    else 
    if (is_object($value) && count(get_object_vars($value))===0){
        return FALSE;
    }
    else 
    if (is_array($value) && count($value)===0) {
        return FALSE;
    }
    else {
        return TRUE;
    }
}

function analyze_property($property_name, $property_value){
    $property_pattern = '/^((\w+):)?(((\/\w+\/\w+)\/)?(\w+))(<=?|>=?|~=|!=|\|=|\?=)?$/';
    $matches = array();
    if (preg_match($property_pattern, $property_name, $matches)){
        return array(
            'prefix'        =>  $matches[2]
        ,   'qualifier'     =>  $matches[5]
        ,   'name'          =>  $matches[6]
        ,   'operator'      =>  $matches[7]
        ,   'qualified'     =>  $matches[4]? TRUE : FALSE
        ,   'value'         =>  $property_value
        ,   'is_filter'     =>  is_filter_property($property_value)
        ,   'is_directive'  =>  FALSE
        ,   'schema'        =>  NULL
        );
    }
    return FALSE;
}

function get_type_from_schema($domain, $type){
    global $metadata;
    return $metadata['domains'][$domain]['types'][$type];
}

function process_mql_object($mql_object, &$parent){
    $object_vars = get_object_vars($mql_object);    
    $properties = array();
    $parent['properties'] = &$properties;
    $type = NULL;
    $types = array();
    
    if($parent && $parent['schema']) {
        $parent_schema_type_name = $parent['schema']['type'];
        $parent_schema_type = analyze_type($parent_schema_type_name);
        $parent_schema_type_domain = $parent_schema_type['domain'];
        $parent_schema_type_type = $parent_schema_type['type'];
        $parent_schema_type = get_type_from_schema($parent_schema_type_domain, $parent_schema_type_type);
        if (!$parent_schema_type) {
            exit('The parent type "/'
            .$parent_schema_type_domain.'/'.$parent_schema_type_type
            .'" was not found in the schema.'
            .' This indicates a logical error in the schema.'
            );
        }
        $types[$parent_schema_type_name] = $parent_schema_type;
    }
    
    foreach ($object_vars as $property_key => $property_value) {
        if (!($property = analyze_property($property_key, $property_value))){
            exit('Property "'.$property_key.'" is not valid.');
        }
        $operator = $property['operator'];
        if ($operator
        && ($property_value === NULL
        ||  is_object($property_value) 
        || ($operator!=='|=' && is_array($property_value))
        || ($operator==='|=' && count($property_value)===0))
        ) {
            exit("Operator ".$operator.' '
            .($operator==='|=' 
            ? 'takes a non-empty list of values' 
            : 'takes a single value (not an object or an array)')
            );
        }
        $property_qualifier = $property['qualifier'];
        $property_name      = $property['name'];
        switch($property['name']){
            case 'type':
            case 'creator':
            case 'guid':
            case 'id':         
            case 'key':         
            case 'name':
            case 'permission':
            case 'timestamp':
            case 'type':
                if ($property_qualifier==='') {
                    $property['qualifier'] = '/type/object';
                }
                break;
            case 'limit':
            case 'optional':
            case 'return':
            case 'sort':
                if ($property_qualifier==='' ) {
                    $property['is_directive'] = TRUE;
                }
            default:
                if ($property_qualifier === '/type/object'){
                    exit('"'.$property_name.'" is not a universal property, and may not have the qualifier "'.$property_qualifier.'".');
                }
        }
        if ($property['qualifier'] === '/type/object'
        &&  $property_name         === 'type'
        &&  $property_value        !== NULL
        &&  !$types[$property_value]
        ) {     
            $type = analyze_type($property_value);
            if (!$type) {
                exit('"'.$property_value.'" is not a valid type identifier.');
            }
            $domain = $type['domain'];
            $domain_type = $type['type'];
            $type = get_type_from_schema($domain, $domain_type);
            if (!$type) {
                exit('Type "/'.$domain.'/'.$domain_type.'" not found in schema.');
            }
            $types[$property_value] = $type;
        }            
        $properties[$property_key] = $property;
    }
    $parent['types'] = array_keys($types);
    switch (count($types)) {
        case 0:
            exit('Could not find a type. Currently we rely on a known type');
            break;
        case 1:                        
            foreach($types as $type_name => $type){} //assigning the contents of the array to the $type variable. php gurus, any better way to do this?
            break;
        default:
            exit('Found more than one type. Currently we can handle only one type.');
    }   
    foreach ($properties as $property_name => &$property){
        if ($property['is_directive']===TRUE) {
            continue;
        }
        switch ($property['qualifier']) {
            case '/type/object':
                continue;
            case '':
                $schema_property = $type['properties'][$property['name']];
                if ($schema_property) {
                    $property['qualifier'] = $type_name;
                    $property['schema'] = $schema_property;
                    if ($schema_property['join_condition']) {
                        process_mql($property['value'], $property);
                    }
                }
                else {
                    exit('No property "'.$property['name'].'" in type "'.$type_name.'".');
                }
                break;
            default:
                if ($property['qualifier']!==$type_name) {
                    exit('Property "'.$property['qualifier'].'/'.$property['name']
                    .'" does not belong to the type "'.$type_name.'". This feature is not supported yet.');
                }
        }
        
    }
}

function process_mql_array($mql_array, &$parent){
    $count = count($mql_array);
    switch ($count) {
        case 0:
            break;
        case 1:
            $parent['entries'] = array();
            if ($parent['schema']) {
                $parent['entries']['schema'] = $parent['schema'];
            }
            process_mql($mql_array[0], $parent['entries']);
            break;
        default:
            exit('Expected a dictionary or a list with one element in a read (were you trying to write?)');
    }
}

function process_mql($mql, &$parent){
    if ($mql===NULL) {
    }
    else 
    if (is_object($mql)){
        process_mql_object($mql, $parent);
    }
    else 
    if (is_array($mql)){ 
        process_mql_array($mql, $parent);
    }
    else {
        exit('mql query must be an object or an array, not "'.gettype($mql).'".');
    }
}
/*****************************************************************************
*   SQL generation Functions
******************************************************************************/
$t_alias_id = 0;
$c_alias_id = 0;
$p_id = 0;

function reset_ids(){
    global $t_alias_id, $c_alias_id, $p_id;
    $t_alias_id = 0;
    $c_alias_id = 0;
    $p_id = 0;
}

function get_t_alias(){
    global $t_alias_id;
    return 't'.(++$t_alias_id);
}

function get_c_alias($new=TRUE){
    global $c_alias_id;
    if ($new){
        $c_alias_id++;
    }
    return 'c'.$c_alias_id;
}

function get_p_name(){
    global $p_id;
    return ':p'.(++$p_id);
}

function get_from_clause($schema, $t_alias, $child_t_alias, $schema_name, $table_name, &$query){
    $direction = $schema['direction'];

    $from = &$query['from'];
    switch ($direction) {
        case 'referencing->referenced':
            $from .= "\n".($schema['nullable']? 'LEFT' : 'INNER').' JOIN ';
            break;
        case 'referenced<-referencing':
            $from .= "\nINNER JOIN ";
            $select = &$query['select'];
            $order_by = &$query['order_by'];
            $merge_into = &$query['merge_into'];
            $merge_into_columns = &$merge_into['columns'];
            break;
    }

    //set up the join condition
    $join_condition = '';
    if ($direction){
        foreach ($schema['join_condition'] as $columns) {
            if ($join_condition==='') {
                $join_condition = "\nON";
            }
            else {
                $join_condition .= "\nAND";
            }
            switch ($direction){
                case 'referencing->referenced':
                    $join_condition .= ' '  .$child_t_alias.'.'.$columns['referencing_column']
                                    .  ' = '.$t_alias.'.'.$columns['referenced_column'];
                    break;
                case 'referenced<-referencing':
                    $column_ref = $t_alias.'.'.$columns['referencing_column'];
                    $alias = $t_alias.get_c_alias();
                    $merge_into_columns[] = $alias;
                    $select[$column_ref] = $alias;
                    $order_by .= ($order_by===''? 'ORDER BY ' : "\n, ");
                    $order_by .= $alias;
                    $join_condition .= ' '  .$child_t_alias.'.'.$columns['referenced_column']
                                    .  ' = '.$t_alias.'.'.$columns['referencing_column'];
                    break;
            }
        }            
    }
    $from = &$query['from'];
    $from .= $schema_name.'.'.$table_name.' AS '.$t_alias.$join_condition;    
}

function map_mql_to_pdo_type($mql_type){
    switch ($mql_type){
        case '/type/boolean':
            $pdo_type = PDO::PARAM_BOOL;
            break;
        case '/type/content':
            $pdo_type = PDO::PARAM_LOB;
            break;
        case '/type/datetime':
        case '/type/text':
        case '/type/float': //this feels so wrong.
            $pdo_type = PDO::PARAM_STR;
            break;
        case '/type/int':
            $pdo_type = PDO::PARAM_INT;
            break;
        case '/type/rawstring':
            $pdo_type = PDO::PARAM_STR;
            break;
    }
    return $pdo_type;
}

function add_parameter(&$where, &$params, $value, $pdo_type){
    $where .= ($param_name = get_p_name());
    $params[] = array(
        'name'  =>  $param_name
    ,   'value' =>  $value
    ,   'type'  =>  $pdo_type
    );
}

function add_parameter_for_property(&$where, &$params, $property){
    $property_value = $property['value'];
    $mql_type = $property['schema']['type'];
    $pdo_type = map_mql_to_pdo_type($mql_type);
    if (is_array($property_value)) {
        $num_entries = count($property_value);
        for ($i=0; $i<$num_entries; $i++) {
            if ($i){
                $where .= ', ';
            }
            add_parameter($where, $params, $property_value[$i], $pdo_type);
        }
    }
    else {
        add_parameter($where, $params, $property_value, $pdo_type);
    }
}

function handle_filter_property(&$where, &$params, $t_alias, $column_name, $property){
    $where .= ($where===''? 'WHERE': "\nAND").' '.$t_alias.'.'.$column_name;
    
    //prepare right hand side of the filter expression
    if ($operator = $property['operator']) {
        //If an operator is specified, 
        //the expression is used in the WHERE clause.
        switch ($operator) {
            case '~=':  //funky mql pattern matcher
                //not implemented yet.
                break;
            case '<': case '>': case '<=': case '>=': case '!=':
                $where .= ' '.$operator.' ';
                break;
            case '|=':
                $where .= ' IN (';
                $add_closing_parenthesis = TRUE;
                break;
            case '?=':  //extension. Ordinary database LIKE
                $where .= ' LIKE ';
                break;
        }
    }
    else {
        //If no operator is specified, 
        //the comparison is automatically with equals.
        $where .= ' = ';
    }
    //prepare the right hand side of the comparison expression
    add_parameter_for_property(&$where, &$params, $property);    
    if ($add_closing_parenthesis) {
        $where .= ')';
    }
}

function handle_non_filter_property($t_alias, $column_name, &$select, &$property){
    $c_alias = $t_alias.get_c_alias();
    $column_ref = $t_alias.'.'.$column_name;
    $select[$column_ref] = $c_alias;
    $property['alias'] = $c_alias;
}

function generate_sql(&$mql_node, &$queries, $query_index, $child_t_alias=NULL, &$merge_into=NULL){
    global $metadata;

    if ($mql_node['entries']) {
        generate_sql($mql_node['entries'], $queries, $query_index, $child_t_alias, $merge_into);
        return;
    }
    if (!isset($mql_node['query_index'])){
        $mql_node['query_index'] = $query_index;
    }
    
    $query = &$queries[$query_index];
    if (!$query){
        $prev_query = $queries[$merge_into['query_index']];
        $query = array(
            'select'        =>  array()
        ,   'from'          =>  $prev_query ? $prev_query['from'] : "FROM "
        ,   'where'         =>  $prev_query ? $prev_query['where'] : ''
        ,   'order_by'      =>  ''
        ,   'params'        =>  $prev_query ? $prev_query['params'] : array()
        ,   'mql_node'      =>  &$mql_node
        ,   'indexes'       =>  array()
        ,   'merge_into'    =>  $merge_into
        ,   'results'       =>  array()
        );
        $queries[$query_index] = &$query;        
    }
    $select = &$query['select'];
    $from   = &$query['from'];
    $where  = &$query['where'];
    $params = &$query['params'];
    $indexes = &$query['indexes'];
    
    $type = analyze_type($mql_node['types'][0]);
    $domain_name = $type['domain'];
    $schema_domain = $metadata['domains'][$domain_name];
    if (!($schema_name = $schema_domain['schema_name'])){
        $schema_name = $domain_name;
    }
    $type_name = $type['type'];
    $schema_type = $schema_domain['types'][$type_name];
    if (!($table_name = $schema_type['table_name'])){
        $table_name = $type_name;
    }
    $t_alias = get_t_alias();
        
    $schema = $mql_node['schema'];

    get_from_clause($schema, $t_alias, $child_t_alias, $schema_name, $table_name, $query);
    
    $properties = &$mql_node['properties'];    
    foreach ($properties as $property_name => &$property) {
        $schema = $property['schema'];        
        if ($direction = $schema['direction']) {
            if ($direction === 'referenced<-referencing'){
                $index_columns = array();
                $index_columns_string = '';
                foreach ($schema['join_condition'] as $columns) {
                    $column_ref = $t_alias.'.'.$columns['referenced_column'];
                    if (!($c_alias = $select[$column_ref])) {
                        $c_alias = $t_alias.get_c_alias();
                        $select[$column_ref] = $c_alias;
                    }
                    $index_columns_string .= $c_alias;
                    $index_columns[] = $c_alias;
                }
                if (!$indexes[$index_columns_string]){
                    $indexes[$index_columns_string] = array(
                        'columns'   =>  $index_columns
                    ,   'entries'   =>  array()
                    );
                }
                $merge_into = array(
                    'query_index'   =>  $query_index                  
                ,   'index'         =>  $index_columns_string
                ,   'columns'       =>  array()
                );
                $new_query_index = count($queries);
            }
            else {
                $merge_into = NULL;
                $new_query_index = $query_index;
            }            
            $property['query_index'] = $new_query_index;
            generate_sql($property, $queries, $new_query_index, $t_alias, $merge_into);
        }
        else 
        if ($column_name = $schema['column_name']){
            if ($property['is_filter']) {        
                handle_filter_property(&$where, &$params, $t_alias, $column_name, $property);
            }
            else {
                handle_non_filter_property($t_alias, $column_name, &$select, &$property);
            }        
        }
    }
}

/*****************************************************************************
*   Execute query / render result
******************************************************************************/
function map_mql_to_php_type($mql_type){
    switch ($mql_type){
        case '/type/boolean':
            $php_type = 'bool';
            break;
        case '/type/content':
        case '/type/datetime':
        case '/type/text':
        case '/type/rawstring':
            $php_type = 'string';
            break;
        case '/type/float': 
            $php_type = 'float';
            break;
        case '/type/int':
            $php_type = 'integer';
            break;
    }
    return $php_type;
}

$statement_cache = array();

function prepare_sql_statement($statement_text){
    global $pdo, $statement_cache;
    if (!($statement_handle = $statement_cache[$statement_text])){
        $statement_handle = $pdo->prepare($statement_text);
        $statement_cache[$statement_text] = $statement_handle;
    }
    return $statement_handle;
}

function &execute_sql($statement_text, $params){
    global $pdo;
    $statement_handle = prepare_sql_statement($statement_text);
    foreach($params as $param_key => $param){
        $statement_handle->bindValue(
            $param['name']
        ,   $param['value']
        ,   $param['type']
        );
    }
    $statement_handle->execute();
    $result = &$statement_handle->fetchAll(PDO::FETCH_ASSOC);
    $statement_handle->closeCursor();
    return $result;
}

function get_query_sql($query){
    $sql = '';
    foreach ($query['select'] as $column_ref => $column_alias) {
        $sql .= ($sql===''? 'SELECT ' : "\n, ").$column_ref.' AS '.$column_alias;
    }
    $sql .= "\n".$query['from']."\n".$query['where']."\n".$query['order_by'];
    return $sql;
    
}

function execute_query($query){
    $sql = get_query_sql($query);
    return execute_sql($sql, $query['params']);
}

function get_result_object(&$mql_node, $query_index, &$result_object=NULL, $key=NULL){
    if($mql_node['query_index']!==$query_index){
        return;
    }
    $object = array();
    
    if (is_array($result_object)) {
        $result_object[$key] = &$object;
    } 
    else {
        $result_object = &$object;
    }

    if ($mql_node['entries']) {
         get_result_object($mql_node['entries'], $query_index, $object, 0);
    }
    else 
    if ($mql_node['properties']) {
        foreach ($mql_node['properties'] as $property_key => $property) {
            if ($property['operator']) {
                continue;
            }
            $value = $property['value'];
            if (is_object($value) || is_array($value)){
                get_result_object($property, $query_index, $object, $property_key);
            }
            else {
                $object[$property_key] = $value;
            }
        }
    }
    $mql_node['result_object'] = $object;
    return $object;
}

function fill_result_object(&$mql_node, $query_index, $data, &$result_object){
    global $explicit_type_conversion;
    if($mql_node['query_index']!==$query_index){
        return;
    }

    if ($mql_node['entries']) {
        fill_result_object($mql_node['entries'], $query_index, $data, &$result_object[0]);
    }
    else
    if ($properties = $mql_node['properties']) {
        foreach ($result_object as $key => $value) {
            $property = $properties[$key];
            if (is_object($value) || is_array($value)){
                fill_result_object($property, $query_index, $data, &$result_object[$key]);
            }
            else
            if ($alias = $property['alias']) {
                if ($explicit_type_conversion) {                    
                    settype($data[$alias], map_mql_to_php_type($property['schema']['type']));
                }
                $result_object[$key] = $data[$alias];
            }
        }
    }
}

function add_entry_to_indexes(&$indexes, $row_index, &$row) {
    foreach($indexes as $index_name => &$index) {
        $entries = &$index['entries'];
        $cols = $index['columns'];
        $colcount = count($cols) - 1;
        for ($i=0; $i<$colcount; $i++){
            $col = $cols[$i];
            $sub_entries = &$entries[$row[$col]];
            if (!$sub_entries) {
                $sub_entries = array();
                $entries[$row[$col]] = &$sub_entries;                
            }
            $entries = &$sub_entries;
        }
        $entries[$row[$cols[$i]]] = $row_index;
    }
    
}

function &get_entry_from_index(&$query, $index_name, $key){
    $index = $query['indexes'][$index_name]['entries'];
    foreach ($key as $k) {
        $index = $index[$k];
    }
    $results = &$query['results'];
    return $results[$index];    
}

function merge_result_object(&$mql_node, &$result_object, $query_index, &$data, $from, $to){
    if ($mql_node['entries']) {
        merge_result_object($mql_node['entries'], &$result_object[0], $query_index, &$data, $from, $to);
    }
    else
    if ($properties = $mql_node['properties']) {
        foreach ($properties as $property_key => $property) {
            if ($property['query_index']===$query_index) {
                $result_object[$property_key] = array();
                $target = &$result_object[$property_key];
                for ($i=$from; $i<=$to; $i++){
                    $target[] = &$data[$i];
                }
            }
            else {
                $value = $property['value'];
                if (is_object($value) || is_array($value)){
                    merge_result_object($property, &$result_object[$property_key], $query_index, &$data, $from, $to);
                }
            }
        }
    }
}

function merge_results(&$queries, $query_index, $key, $from, $to){
    if ($from===-1){
        return;
    }
    $query = &$queries[$query_index];
    $merge_into = $query['merge_into'];
    $target_query_index = $merge_into['query_index'];
    $target_query = &$queries[$target_query_index];
    $index_name = $merge_into['index'];
    $merge_target = &get_entry_from_index(&$target_query, $index_name, $key);
    
    merge_result_object($target_query['mql_node'], $merge_target, $query_index, $query['results'], $from, $to);
}

function execute_queries(&$queries) {
    foreach($queries as $query_index => &$query){
        $indexes = &$query['indexes'];
        $sql = get_query_sql($query);
        $mql_node = $query['mql_node'];
        get_result_object($mql_node, $query_index);
        $result_object = $mql_node['result_object'];
        if ($merge_into = $query['merge_into']) {
            $merge_into_columns = $merge_into['columns'];
            $merge_into_values_new = array();
            $merge_into_values_old = array();
            $offset = -1;
        }
        $result = &$query['results'];
        $rows = execute_query($query);
        foreach($rows as $row_index => $row){
            if ($merge_into){            
                foreach ($merge_into_columns as $col_index => $alias){
                    $merge_into_values_new[$col_index] = $row[$alias];
                }
                if ($merge_into_values_new !== $merge_into_values_old){
                    merge_results(&$queries, $query_index, $merge_into_values_old, $offset, $row_index);
                    $offset = $row_index;
                }
                $merge_into_values_old = $merge_into_values_new;
            }
            fill_result_object($mql_node, $query_index, $row, $result_object);
            $result[$row_index] = $result_object;
            add_entry_to_indexes($indexes, $row_index, $row);
        }
        if ($merge_into_values_old) {
            merge_results(&$queries, $query_index, $merge_into_values_old, $offset, $row_index);
        }
    }
}
/*****************************************************************************
*   Handle request
******************************************************************************/
function handle_query($query){
    //check if the query parameter is valid MQL query envelope
    if (!property_exists($query, 'query')) {
        exit('MQL query envelope must have a query attribute');
    }
    $mql = $query->query;

    $tree = array();
    reset_ids();
    process_mql($mql, $tree);
    generate_sql($tree, $queries, 0);
    //print_r($tree);
    execute_queries($queries);
    $result = &$queries[0]['results'];
    return array(
        'code'      =>  '/api/status/ok'
    ,   'result'    =>  $result
    );
}

function handle_queries($queries){
    $queries = get_object_vars($queries);
    $results = array(
        'code' =>  '/api/status/ok'
    );
    foreach ($queries as $query_key => $query){
        $result = handle_query($query);
        $results[$query_key] = $result;
    }
    return $results;
}

$args = NULL;

switch ($_SERVER['REQUEST_METHOD']){
    case 'GET':
        $args = $_GET;
        break;
    case 'POST':
        $args = $_POST;
        break;
    default:
        exit('Must use either GET or POST');
}

$query = $args['query'];
$queries = $args['queries'];

//check if the query parameter is present
if ((!isset($query) && !isset($queries))
||  ( isset($query) &&  isset($queries))) {
    exit('Either query or queries must be specified');
}

$query_or_queries = $query? $query : $queries;

//immunize against magic quoting
if (get_magic_quotes_gpc() === 1) {
    $query_or_queries = stripslashes($query_or_queries);
}

//check if the query parameter is valid JSON
$query_decode = json_decode($query_or_queries);
if ($query_decode===NULL) {
    exit('query or queries not valid JSON ('.get_last_json_error().')');
}

//testing if the envelope is an object (not some other random JSON value)
if (!is_object($query_decode)) {
    exit('Envelope must be an object');
}

/*****************************************************************************
*   Schema
******************************************************************************/
$metadata_file_name = '../schema/schema-sqlite.json';

if (!file_exists($metadata_file_name)){
    exit('Cannot find schema file "'.$metadata_file_name.'".');
}

$metadata_file_contents = file_get_contents($metadata_file_name);

if (!$metadata = json_decode($metadata_file_contents, TRUE)) {
    exit('schema is not valid json ('.get_last_json_error().').');
}

/*****************************************************************************
*   Database (PDO)
******************************************************************************/
$pdo_config = $metadata['pdo'];

if (!is_array($pdo_config)) {
    exit('schema does not specify a valid pdo configuration.');
}
$pdo = new PDO(
    $pdo_config['dsn']
,   $pdo_config['username']
,   $pdo_config['password']
,   $pdo_config['driver_options']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//ok - this bit here is nasty - sqlite does not support schemas, 
//instead we have to "attach" a database file and give that an alias.
//right now we have no way to sanely configure this from within the schema
//we just have to live with it for now, and yank out the PDO config from the schema.
//that was necessary anyway to ease deployment (same schema, different connection situation)
$file = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'sakila.sqlite';
$pdo->exec('attach "'.$file.'" AS sakila');
$explicit_type_conversion = $pdo_config['explicit_type_conversion'];
/*****************************************************************************
*   Main
******************************************************************************/
if ($queries) {
    $result = handle_queries($query_decode);
}
else {
    $result = handle_query($query_decode);
}
$result['status'] = '200 OK';
$result['transaction_id'] = 'not implemented';

echo json_encode($result);