<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * tests : setup - remove the test tables from the database
 *
 * @license     MIT License
 * @package     DataMapper ORM
 * @category    DataMapper ORM
 * @author      Harro "WanWizard" Verton
 * @link        http://datamapper.wanwizard.eu
 * @version     2.0.0
 */

class DataMapper_Tests_Breakdown
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
			'title' => 'DataMapper Tests &raquo; Breakdown',
			'methods' => array(
//				'tables' => 'dropping test tables',
			),
		);
	}

	/*
	 * delete our test tables from the database
	 */
	public function tables()
	{
		// drop our test tables
		self::$CI->dbforge->drop_table('dmtests_A');
		self::$CI->dbforge->drop_table('dmtests_B');
		self::$CI->dbforge->drop_table('dmtests_C');
		self::$CI->dbforge->drop_table('dmtests_D');
		self::$CI->dbforge->drop_table('dmtests_E');
	}
}
