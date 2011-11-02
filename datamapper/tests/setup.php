<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * tests : setup - make sure we have a database to test with
 *
 * @license     MIT License
 * @package     DataMapper ORM
 * @category    DataMapper ORM
 * @author      Harro "WanWizard" Verton
 * @link        http://datamapper.wanwizard.eu
 * @version     2.0.0
 */

class DataMapper_Tests_Setup
{
	public static $CI;

	/*
	 * dummy static constructor
	 *
	 * called by the runner, to check what tests this class contains, and in
	 * which sequence they should be called
	 */
	public function _construct()
	{
		self::$CI = get_instance();

		return array(
			'title' => 'DataMapper Tests &raquo; Setup',
			'first' => TRUE,
			'last' => FALSE,
			'before' => array(),
			'after' => array(),
			'methods' => array(
				'database' => 'preparing the environment',
				'tables' => 'creating test tables',
			),
		);
	}

	/*
	 * check if we have a database to work with
	 */
	public function database()
	{
		// make sure we're connected to the database
		self::$CI->load->database();
		if ( ! isset(self::$CI->db) OR ! self::$CI->db instanceOf DM_DB_Driver )
		{
			DataMapper_Tests::failed('DataMapper Tests: Can not make a connection to the database. Check your CI database configuration!');
			return FALSE;
		}
		self::$CI->load->dbforge();
	}

	/*
	 * crerate all required tables
	 */
	public function tables()
	{
		// create our test tables : standard table A
		self::$CI->dbforge->add_field("id int(11) NOT NULL AUTO_INCREMENT");
		self::$CI->dbforge->add_field("data_A varchar(50) NOT NULL DEFAULT ''");
		self::$CI->dbforge->add_key("id", TRUE);
		self::$CI->dbforge->create_table('dmtests_A', TRUE);

		// add test data to table A
		self::$CI->db->insert('dmtests_A', array('data_A' => 'Table A Row 1'));
		self::$CI->db->insert('dmtests_A', array('data_A' => 'Table A Row 2'));
		self::$CI->db->insert('dmtests_A', array('data_A' => 'Table A Row 3'));

		// create our test tables : standard table B
		self::$CI->dbforge->add_field("id int(11) NOT NULL AUTO_INCREMENT");
		self::$CI->dbforge->add_field("data_B varchar(50) NOT NULL DEFAULT ''");
		self::$CI->dbforge->add_key("id", TRUE);
		self::$CI->dbforge->create_table('dmtests_B', TRUE);

		// add test data to table B
		self::$CI->db->insert('dmtests_B', array('data_B' => 'Table B Row 1'));
		self::$CI->db->insert('dmtests_B', array('data_B' => 'Table B Row 2'));
		self::$CI->db->insert('dmtests_B', array('data_B' => 'Table B Row 3'));

		// create our test tables : join table between A and B
		self::$CI->dbforge->add_field("id int(11) NOT NULL AUTO_INCREMENT");
		self::$CI->dbforge->add_field("fk_id_A int(11) NOT NULL DEFAULT 0");
		self::$CI->dbforge->add_field("fk_id_B int(11) NOT NULL DEFAULT 0");
		self::$CI->dbforge->add_field("data_C varchar(50) NOT NULL DEFAULT ''");
		self::$CI->dbforge->add_key("id", TRUE);
		self::$CI->dbforge->create_table('dmtests_C', TRUE);

		// add test data to table C
		self::$CI->db->insert('dmtests_C', array('fk_id_A' => 1, 'fk_id_B' => 1, 'data_C' => 'Table C join A_1 to B_1'));
		self::$CI->db->insert('dmtests_C', array('fk_id_A' => 1, 'fk_id_B' => 2, 'data_C' => 'Table C join A_1 to B_2'));
		self::$CI->db->insert('dmtests_C', array('fk_id_A' => 1, 'fk_id_B' => 3, 'data_C' => 'Table C join A_1 to B_3'));
		self::$CI->db->insert('dmtests_C', array('fk_id_A' => 2, 'fk_id_B' => 2, 'data_C' => 'Table C join A_2 to B_2'));

		// create our test tables : table D with in-table-foreign-key to table A
		self::$CI->dbforge->add_field("id int(11) NOT NULL AUTO_INCREMENT");
		self::$CI->dbforge->add_field("fk_id_A int(11) NOT NULL DEFAULT 0");
		self::$CI->dbforge->add_field("data_D varchar(50) NOT NULL DEFAULT ''");
		self::$CI->dbforge->add_key("id", TRUE);
		self::$CI->dbforge->create_table('dmtests_D', TRUE);

		// add test data to table D
		self::$CI->db->insert('dmtests_D', array('fk_id_A' => 1, 'data_D' => 'Table D Row 1 FK A_1'));
		self::$CI->db->insert('dmtests_D', array('fk_id_A' => 2, 'data_D' => 'Table D Row 1 FK A_2'));
	}
}
