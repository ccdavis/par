<?php

require_once "PHPUnit/Framework.php";
require_once "PHPUnit.php";


require_once dirname(__FILE__) . "/par.php";

class TestRecord extends Par{
    function __construct($init=true){

    if ($init){
       parent::__construct( 'test_records'); $this->child_class = __CLASS__;
       }
    else{
	parent::__construct( ''); $this->child_class = __CLASS__;
   }
  }

}

class Book extends Par{
  function __construct(){parent::__construct( 'books'); $this->child_class = __CLASS__;}
}



class ParTest extends  PHPUnit_TestCase
{
  function ParTest($name){
    $this->PHPUnit_TestCase($name);
  }

  function setup(){
    $create_test_table = "create table test_records( id int(11) not null auto_increment,
      name varchar(25) not null,
      size int(11),
      primary key(id))";
    // connect to db
    global $db;	
    $db = new DatabaseConnection('localhost:3307','polka_tester', 'modhybe84', 'polkadot_test');
    $db->execute("drop table if exists test_records");
    $db->execute($create_test_table);
    $db->execute("insert into test_records(name,size) values('name1',257)");

  }


   public function testFindFirstBy()
   {
      $finder =  new TestRecord();
      $fixture = $finder->find_first_by("name","name1");
      $this->assertEquals("name1",$fixture->{'name'});
    }

    public function testCreate()
    {
    // Normally would not need $db, but we're using it to access the database
    // to check the TestRecord class
      global $db;
      $test_record = new TestRecord();
      $test_record->{'name'} = "new name";
      $test_record->{'size'} = 15;
      $test_record->create();

      $results = $db->execute("select * from test_records");
      $this->assertEquals(2,$db->result_set_size());
      $finder = new TestRecord();
      $this->assertEquals(15, $finder->find_first_by("size",15)->{'size'});
    }

    public function testDelete()
    {
      global $db;
      $test_record = new TestRecord();
      $test_record->{'name'} = "new name";
      $test_record->{'size'} = 15;
      $test_record->create();
       $new_id = $test_record->id();

       $db->execute("select * from test_records");
       $this->assertEquals(2,$db->result_set_size());
       $finder = new TestRecord();
      $this->assertEquals(15, $finder->find_first_by("size",15)->{'size'});
      $this->assertEquals($new_id, $finder->find_first_by("size",15)->id());

      // Now delete one of the records and show it cannot be found
      $new_rec = $finder->find_first_by("size",15);
      $new_rec->delete();

      $db->execute("select * from test_records where size = 15");
      $this->assertEquals(0, $db->result_set_size());
    }

    public function testUpdate()
    {
      global $db;
	  $test_record = new TestRecord();
	  $test_record->{'name'} = "new name";
	  $test_record->{'size'} = 15;
	  $test_record->create();

	  $results = $db->execute("select * from test_records");
	  $this->assertEquals(2,$db->result_set_size());
	  $finder = new TestRecord();
      $this->assertEquals(15, $finder->find_first_by("size",15)->{'size'});

      // Now update (change) one of the records and show the old one is missing
      // and a record with the new field value exists.
      $new_rec = $finder->find_first_by("size",15);
      $original_id = $new_rec->id();
      $new_rec->{'size'} = 12;
      $updated_record_id = $new_rec->update()->id();
      echo "original ID " . $original_id . " updated id " . $updated_record_id . "\n";
      $this->assertEquals($original_id, $updated_record_id);

      $results = $db->execute("select * from test_records where size = 15");
      $this->assertEquals(0,$db->result_set_size());
      $results = $db->execute("select * from test_records");
      $this->assertEquals(2, $db->result_set_size());

      $finder = new TestRecord();
      $updated_record = $finder->find($updated_record_id);
      $this->assertEquals(12, $updated_record->{'size'});
    }


    public function testFindBy(){
      $names = array();
    	$test_record = new TestRecord();
	   $test_record->{'name'} = "asdf";
	    $test_record->{'size'} = 2;
      	$test_record->create();
      	$names []= $test_record->{'name'};

      	$test_record = new TestRecord();
	$test_record->{'name'} = "xyz";
	$test_record->{'size'} = 5;
      	$test_record->create();
      	$names []= $test_record->{'name'};

      	$records = $test_record->find_by("name",$names);
      	$this->assertEquals(2, sizeof($records));
    }


  public function testDisplay(){
  	global $db;
  	$db->execute("delete from test_records");
        $test_record = new TestRecord();
        $test_record->{'name'} = "asdf";
        $test_record->{'size'} = 2;
        $test_record->create();
        $record2 = new TestRecord();
        $record2->{'name'} ="";
        $record2->create();

        $tr =  $test_record->find_all_in_order("id asc");
        $this->assertEquals(2,$tr[0]->display("size",99));
        $this->assertEquals("asdf",$tr[0]->display("name","default"));

        $this->assertEquals(null,$tr[1]->{'size'});
        $this->assertEquals(99,$tr[1]->display("size",99));
        $this->assertEquals("default",$tr[1]->display("name","default"));

        $tr[1]->name = "abc";
        $this->assertEquals("abc",$tr[1]->display("name","default"));
  }
// Make sure the table name is copied
  public function testFastFindByRecords()
  {
        global $db;
        $test_record = new TestRecord();
        $test_record->{'name'} = "asdf";
        $test_record->{'size'} = 2;

        $test_record->create();
        $test_record = new TestRecord();
        $test_record->{'name'} = "asdf";
        $test_record->{'size'} = 3;
        $test_record->create();

        $records = $test_record->find_by_conditions("name = ? or size > ?",array("asdf",0));
        $record = $records[1];
        $this->assertEquals("test_records",$record->table_name());
  }


    public function testFindByConditions()
    {
      global $db;
      $test_record = new TestRecord();
      $test_record->{'name'} = "asdf";
      $test_record->{'size'} = 2;
      $test_record->create();


      $records = $test_record->find_by_conditions("name = ? or size = ?",array("asdf",257));
      $this->assertEquals(2, sizeof($records));
      $records = $test_record->find_by_conditions("name like ? and size = ?",array("asdf",2));
      $this->assertEquals(1, sizeof($records));

      $records = $test_record->find_by_conditions("name like ?",array("11111"));
      $this->assertEquals(0, sizeof($records));
    }

}

 $suite = new PHPUnit_TestSuite("ParTest");
 echo PHPUnit::run($suite)->toHTML();
?>
