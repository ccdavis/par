<?php


/* This class creates dynamic model objects.  The idea here is that the getters and setters for
the classes extending this class match the columns in the corresponding database tables.

Each model class -- those extending Par --   mirrors a table in the database.


For instance:

class School extends Par {
    function __construct(){parent::__construct( 'schools'); $this->child_class = __CLASS__;}
  }

That's it.

The Par constructor dynamically generates the needed getters and setters for the columns
in the table named in the constructor of the extending class.

This is particularly useful when a schema is under-going rapid change.  It also
means you can avoid having two places where your schema must be declared.

You read and write attributes like so:

  $my_record_instance->{'name'} = "my name";

  $this_name = $this_record_instance->{'name'};


  You create an instance of the model class before working with it:

    $my_school = new School();

   You can set attributes and then create a new record with create():

   $my_school->{'name'} = "School of Public Health";
   $my_school->{'address'} = "515 University Avenue";

   // and set a foreign key
   $my_school->{'city_id'} = minneapolis->id();

   // Will create a new record or throw an exception if it fails
   $my_school->create();


  Or you can load an existing record, change it and save it:
    $a_city = new City();

    $a_city = $a_city->find($this_school->{'city_id'});
    $a_city->{'name'} = 'St. Paul';
    $a_city->update();


Of course you will want to include special behaviour in the model classes.  For example:


class Institution extends Par {
    function __construct(){parent::__construct( 'institutions'); $this->child_class = __CLASS__;}

    // Returns the parent institution if one exists
    function parentInstitution(){
		return $this->find($this->{'parent_institution_id'});
	}

	// Returns a list of all institutions belonging to this one (the U of M will own
	// Carlson, School of Public Health, etc.)
	function child_institutions(){
	  return $this->find_by('parent_institution_id',$this->id());
	}


	// We don't check for weird characters or  other low level stuff (should be handled elsewhere,)
	//just logical consistency.
	function validate(){
	  $msgs = array();
	  $is_valid = true;

	  if (strlen($this->{'name'})<2)
	  {
	    is_valid = false;
	    $msgs [] = "name must be more than one character long.";
	  }
	  .....
	    return list of problem fields with messages or empty array if valid
	    return $msgs;
  	  }
	}



*/


/* In place of a real database abstraction layer; probably better to just replace with all mysql stuff in Par*/
class DatabaseConnection{	
	public $connection;
	public $result_set;
		
	function DatabaseConnection($hostname, $username, $password, $database_name)
	{	    
	  $this->result_set = null;
	    $this->connection = mysql_connect($hostname, $username, $password);
	    $db_selected = mysql_select_db($database_name, $this->connection);
	    if(!$db_selected)
            {
	       throw new Exception('Error connecting to database' .  mysql_error($this->connection));
            }
	}

	public function execute( $query )
	{	  	   
	    $this->result_set = mysql_query ( $query, $this->connection);
	    $error = mysql_error();
	    if ($error !=""){
	      throw new Exception($error );
	    }
	    
	    return $this->result_set;		
	}
	
	public function result_set_size()
	{
	  return mysql_num_rows($this->result_set);
	}
	

	public function close()
	{
	    mysql_close($this->connection);
	}
}

class Par
{
    // Class of extending class -- needed for correct polymorphic behaviour
   protected $child_class;


// id of the instance / current row
  private $id_value = 0;

  // name of primary key field -- over-ride in child constructor if necessary
  protected $primary_key = "id";

  // name of database table
  private $table;

  // key-value pairs
  private $fields = array();

// Can only create un-persisted objects.  Can only update persisted objects.
// Once loaded or created an object has $persisted == true.
  private $persisted = false;

// Sets all field values of the instance to null
// The child constructor must call this constructor with the table name of the
// data backing the child class.
  function __construct( $table="")
  {
    global $db;
    if (null == $db){    
       //$db = new DatabaseConnection($db_config['host'],$db_config['username'],$db_config['password'],$db_config['database']);
       throw new Exception("No database connection.");
    }

    $this->table = $table;
    if ($this->table !="")
      foreach($this->column_names() as $name)
        if ($name != $this->primary_key)
          $this->fields[$name] = null;
  }

// Alias for whatever the ID column is really called (should be 'ID' in most cases.)
  public function id()
  {
    return $this->id_value;
  }

  public function table_name(){
    return $this->table;
  }

  // Returns an  array of the column names from the table
  // Using this we can make the active record class instances really dynamic.
  public function column_names()
  {
    return array_keys($this->columns_by_name());
  }

  public function content_column_names(){
    return array_keys($this->fields);
  }


  // Returns objects with all column information for the current table
  protected function columns()
  {
    $sql = "show columns from " . $this->table;
    //$result = mysql_query($sql);
    global $db;
    $result = $db->execute($sql);
    $this->catch_and_throw_any_mysql_errors("Could not get column info from table " . $this->table_name());

    $all_columns = array();
    while ($column = mysql_fetch_object($result))
        array_push($all_columns,$column);

   return $all_columns;
  }

  // Returns hash (assoc array) of columns by their names
  public  function columns_by_name()
  {
    $cols_by_name = array();
    foreach ($this->columns() as $column)
      $cols_by_name[$column->Field] = $column;

     return $cols_by_name;
  }

  // Returns nothing.  Throws exception if $result has caused a mysql error.
  // Otherwise does nothing at all.
  private function catch_and_throw_any_mysql_errors($msg)
  {
    $error_text = mysql_error();
    
    if ($error_text !="")
    {
      throw new Exception($msg  . ' error on ' . $this->table_name() . ' ' . $error_text);
    }
  }

  // Throws  any exception that is created by this class
  protected function throw_Par_exception($msg)
  {
      throw new Exception($msg . " in " . $this->table_name());
  }

  public function set_table($table){
    $this->table =$table;
  }

  private function all_results($result){
      $record_objs = array();

      while ($row = mysql_fetch_assoc($result))
      {
        $record_obj = new $this->child_class(false);
        $record_obj->set_table($this->table_name());
        $record_obj->set_all_fields($row);
        $record_objs []= $record_obj;

      }

    return $record_objs;
  }

  private function count_all_results($result){
    return mysql_num_rows($result);
  }





// Magic method for reading all database columns for the currently instantiated record.
  public function __get( $key )
  {
    if (array_key_exists( $key, $this->fields))
      return $this->fields[ $key ];
    else
      $this->throw_Par_exception("No field named " . $key . " in table " . $this->table_name());
  }

// Magic method for setting all attributes on the table.  throws exception if the
// attribute isn't backed by a column.
  public function __set( $key, $value )
  {
    if ( array_key_exists( $key, $this->fields ) )
    {
      $this->fields[ $key ] = $value;
      return true;
    }
    $this->throw_Par_exception("No field named " . $key . " in table " . $this->table_name());
  }

// Instantiates and returns one active record with the ID $id.  Throws an exception
// if the ID isn't in the table.
// If $id is an array, the elements should be IDs in the table.  All records
// with the IDS will be loaded and returned.
  public function find( $id )
  {
    if (is_array($id))
      return $this->find_all_by_ids($id);

    $result = Par::mysql_prepare(
    "SELECT * FROM ".$this->table_name() ." WHERE ".
     $this->primary_key . " =?",
      array($id)
    );

    if (mysql_num_rows($result) ==0)
      return null;

    $row = mysql_fetch_assoc($result);

	// check for error
    $this->catch_and_throw_any_mysql_errors('Could not retrieve ' . $id);
    $new_obj = new $this->child_class();
    $new_obj->set_all_fields($row);
    return $new_obj;
  }


  // Like find() but returns a group of records with the set of IDs
  protected function find_all_by_ids($ids)
  {
    if (!is_array($ids))
      $this->throw_Par_exception("find_all_by_ids requires an array of IDs");

     $replacements  = array();

     foreach($ids as $id)
       $replacements []= "?";

     $find_set_sql = "(" . join($replacements,",") . ")";
     $result = Par::mysql_prepare(
      "SELECT * FROM ".$this->table_name() ." WHERE ".
       $this->primary_key . " in " . $find_set_sql,
       $ids);

    $this->catch_and_throw_any_mysql_errors("");
    return $this->all_results($result);
  }


  // Given a row full of values and an ID, assign to correct class members
  public function set_all_fields($row)
  {
  // This allows creating new rows without id values
    if ($row[$this->primary_key] != "")
    {
      $this->id_value = $row[$this->primary_key];
      $this->persisted = true;
    } else{
      $this->persisted = false;
    }

    foreach( array_keys( $row ) as $key )
      if ($key != $this->primary_key)
	$this->fields[ $key ] = $row[ $key ];
    $this->persisted = true;
  }



// Pass either a comma delimited list of field names or an array literal followed by the text to find
// Returns an array of  model objects of type $child_class.
//
//  NOTE:  Only MyISAM tables support the fulltext search.  You will get an exception on other tables.
// The default is to use 'rlike' on all fields in the list. Searches done
// with $fulltext == false will not return results ordered by relevance.
//
  public function text_search($field_names, $search_text, $fulltext = false)
  {
    if (is_array($field_names))
    {
      $field_names = join(",", $field_names);
    }

    $rlike_sequence = explode(",",$field_names);
    for ($i=0;$i<sizeof($rlike_sequence);$i++)
      $rlike_sequence[$i] .= " rlike ?";

      // We have to make the array containing the text to be cleaned match the initial number of
      // ? symbols iffulltext is false:
      $substitute = array($search_text);
      if (!$fulltext && sizeof($rlike_sequence) > 1)
        for ($i=0;$i<sizeof($rlike_sequence)-1;$i++)
          $substitute[]= $search_text;

    $search_sql = join(" or ",$rlike_sequence);

    if ($fulltext)
      $search_sql ="match(" . $field_names. ") against(?)";

    $sql = "SELECT * FROM ".$this->table_name() ." WHERE ". $search_sql;
    $result = Par::mysql_prepare($sql,$substitute);

	// check for error
    $this->catch_and_throw_any_mysql_errors('Could not search on  ' . $field_names . ' for ' . $search_text . " \n using " . $sql);

    return $this->all_results($result);
  }

// Finds the first occurrance of a row where field
// matches value.  Returns an object of the specific model type.
  public function find_first_by($field_name, $value)
  {
      $result = Par::mysql_prepare(
        "SELECT * FROM ".$this->table_name() ." WHERE ".
       $field_name . " =? limit 1",
        array($value)
    );

  // check for error
    $this->catch_and_throw_any_mysql_errors('Could not retrieve  a ' . $this->table_name() . " with " .
        $field_name . " of value " . $value);

    // If result set is empty return null
    if (mysql_num_rows($result) < 1)
      return null;

    $row = mysql_fetch_assoc($result);

    $record_obj = new $this->child_class();
    $record_obj->set_all_fields($row);
    return $record_obj;
  }



// Find all rows where column field_name matches value or list of values
// Returns array of objects of the specific model type.
  public function find_by($field_name, $value)
  {
    $find_sql = "";

    if (is_array($value))
    {
      $replacements  = array();

      foreach($value as $v)
         $replacements []= "?";

       $find_sql = " in (" . join($replacements,",") . ")";
     }
     else
     {
       $find_sql = " = ? ";
       $value = array($value);
      }

    $result = Par::mysql_prepare(
      "SELECT * FROM ".$this->table_name() ." WHERE ".
       $field_name . $find_sql,
        $value
    );

  // check for error
    $this->catch_and_throw_any_mysql_errors('Could not retrieve  a ' . $this->table_name() . " with " .
      $field_name . " of " . $value);

    return $this->all_results($result);
  }

  protected function find_by_object($foreign_key, $objs){
    if (!is_array($objs)) $objs = array($objs);
    $ids = array();
    foreach($objs as $obj)
      $ids []= $obj->id();
      return $this->find_by($foreign_key, $ids);
  }


  // Instead of a single value of one column, lets you use any where clause syntax
  // Returns instances of the child class.
  // Conditions is a string with the '?' in place of values, $values is an array of
  // values to put into the conditions.  For example:
  //  $book->find_by_conditions("publisher like ? and publication_year = ?",array("XYZ Publications", "1995"));
  public function find_by_conditions($conditions, $values,$count=false){
    $result = Par::mysql_prepare(
    "SELECT * FROM ".$this->table_name() ." WHERE ". $conditions, $values);

  // check for error
    $this->catch_and_throw_any_mysql_errors('Could not retrieve  a ' . $this->table_name() .
      " with conditions " . $conditions);

    if ($count)
      return $this->count_all_results($result);
    else
      return $this->all_results($result);
  }

  // Supply the select  clause separately from the rest
  public function find_by_sql($select, $sql,$values,$count=false){
    $select .= ", " . $this->table_name() . "." . $this->primary_key . " ";
    $result = Par::mysql_prepare($select . $sql, $values);
    $this->catch_and_throw_any_mysql_errors('Could not retrieve  with ' . $select . " " . $sql);
    if (!$count)
       return $this->all_results($result);
     else
       return $this->count_all_results($result);
  }


// Returns every record in order using an order by clause with content in $order_by
  public function find_all_in_order($order_str){
    $result = Par::mysql_prepare("select * from " .
    $this->table_name() ." order by " . $order_str);

	// check for error
    $this->catch_and_throw_any_mysql_errors('Could not retrieve  a ' . $this->table_name() . " with order by clause " . $order_str);
    return $this->all_results($result);
  }

  // Generate new Pars not backed by a table (for reports or from complex joins)
  // For this to work the class extending Par must not use the
  // standard constructor referencing a table.
  public function report($sql,$values){
    $result = Par::mysql_prepare($sql, $values);
    $this->catch_and_throw_any_mysql_errors("Problem with query " . $sql);

    return $this->all_results($result);
  }

  // This is for action-taking. report fails to work, since
  // DELETE etc don't return anything, which causes all_results to spit warnings
  protected static function do_action($sql, $values) {
    Par::mysql_prepare($sql, $values);
    $error = mysql_error();
    if($error != '') {
    	throw new Exception("$error in query ($sql)");
    }
  }


// Create new row for the contents of this object.   returns $this so you can chain calls.
// The id() method will return the new ID.
  public function create()
  {
    $fields = $this->primary_key . ", ";
    $fields .= join(", ", array_keys($this->fields));

    $inspoints = array("0");
    foreach(array_keys($this->fields) as $field)
      $inspoints []= "?";

    $inspt = join( ", ", $inspoints );

    $sql = "INSERT INTO ".$this->table_name() .
            " ($fields) VALUES ($inspt )";

    $values = array();
    foreach(array_keys($this->fields) as $field )
      $values []= $this->fields[$field];

    $insert_result = Par::mysql_prepare($sql,$values);

    $this->catch_and_throw_any_mysql_errors('Could not create new row');

    $id_sql = "SELECT last_insert_id()" ;


    //$result = mysql_query( "SELECT last_insert_id()" );
    global $db;
    $result = $db->execute($id_sql);

    $row = mysql_fetch_row( $result);
    	$this->catch_and_throw_any_mysql_errors('Could not create new row');
    $this->id_value = $row[0];
    $this->persisted = true;
    //return $row[0];
    return $this;
  }

// Always updates one row, the persisted version of this object.    Throws an
// exception if it could not update.
// Returns $this so you can chain calls.
  public function update()
  {
    if (!$this->persisted)
    {
      $this->throw_Par_exception('Cannot update a record that does not yet exist.  Use create() first.');
    }

    $sets = array();
    $values = array();
    foreach(array_keys($this->fields) as $field )
    {
      $sets []= $field.'=?';
      $values []= $this->fields[$field];
    }
    
    $set = join(", ", $sets);
    $values []= $this->id_value;

    $sql = 'UPDATE '.$this->table_name() .' SET '.$set.
      ' WHERE ' . $this->primary_key . '=?';

    $result = Par::mysql_prepare($sql,$values);

    // Throw exception if update failed
    $this->catch_and_throw_any_mysql_errors('Could not update row ' . $this->id_value);
    return $this;
  }

// Always deletes one row, the persisted version of the object.  Throws an
// exception if it fails.
  public function delete()
  {
    $result = Par::mysql_prepare(
	'DELETE FROM '.$this->table_name() .' WHERE '.
	$this->primary_key . '=?',
	array( $this->id_value )
    );
    $this->catch_and_throw_any_mysql_errors('Could not delete row ' . $this->id());
  }


  public static function delete_all($table_name)
  {
  	$result = Par::mysql_prepare('DELETE FROM '. $table_name);
  	$error_text = mysql_error();
  	if ($error_text !="")
  	  throw new Exception($error_text);
  }

     // mysql_query() wrapper. takes two arguments. first
     // is the query with '?' placeholders in it. second argument
     // is an array containing the values to substitute in place
     // of the placeholders (in order, of course).
     // Pass NULL constant in array to get unquoted word NULL
     private  static function mysql_prepare ($query, $phs = array())
     {
         $position = 0;
         foreach ($phs as $ph) {
            if ( isset($ph) ) {
                 if (is_numeric($ph)) {
                     // $ph doesn't need to be changed. numbers are safe.
                 } elseif (is_array($ph)) {
                     // this is useful when you want to do "blah IN ?" and pass an array
                     $ph = "('" . implode("','", array_map("mysql_real_escape_string", $ph)) . "')";
                 } else {
                     $ph = "'" . mysql_real_escape_string($ph) . "'";
                 }
             } else {
                 $ph = "NULL" ;
             }
             // start after the last replace.
             // (we may have added a ? that we don't want to replace)
             $qmark = strpos($query, '?', $position);
             $query = substr_replace($query, $ph, $qmark, 1);
             $position = $qmark + strlen($ph);
         }

         global $db;
         $result = $db->execute($query);
         return $result;
     }

     public function display($col,$default,$prefix=NULL,$suffix=NULL){
       if ($this->{$col} == null)
         return $default;
         else
           return $prefix.$this->{$col}.$suffix;
     }

  public function F($col) {
  	return htmlspecialchars($this->{$col});
  }
}

?>
