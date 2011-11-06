<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * tests : test all relationships with single primary key tables
 *
 * @license     MIT License
 * @package     DataMapper ORM
 * @category    DataMapper ORM
 * @author      Harro "WanWizard" Verton
 * @link        http://datamapper.wanwizard.eu
 * @version     2.0.0
 */

class DataMapper_Tests_Manual_Advanced
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
			'title' => 'DataMapper Tests &raquo; Manual &raquo; Get (Advanced) examples',
			'methods' => array(
				'deeprelations' => 'Deep relationship queries',
				'jointables' => 'Join table queries',
			),
		);
	}

	/*
	 * Deep relationship queries
	 */
	public function deeprelations()
	{
		// where_related by deep string

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->where_related('dmtestb/dmteste', 'data_E', 'Table E Row 1 FK B_2')->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 1,
				'fk_id_A' => 0,
				'data_A' => 'Table A Row 1',
			),
			array(
				'id' => 2,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 2',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where_related("deep/related", "data_E", "Table E Row 1 FK B_2")->get();');

		// where_related by object array

		try
		{
			$dmtesta = new Dmtesta();
			$dmtestb = new Dmtestb();
			$dmteste = new Dmteste();
			$dmtesta->where_related( array($dmtestb, $dmteste), 'data_E', 'Table E Row 1 FK B_2')->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 1,
				'fk_id_A' => 0,
				'data_A' => 'Table A Row 1',
			),
			array(
				'id' => 2,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 2',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where_related( array($deep, $related), "data_E", "Table E Row 1 FK B_2")->get();');
	}

	/*
	 * Deep relationship queries
	 */
	public function jointables()
	{
		// where_join_field

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->where_join_field('dmtestb', 'data_C', 'Table C join A_2 to B_2')->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 2,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 2',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->->where_join_field("dmtestb", "data_C", "Table C join A_2 to B_2")->get()');
	}

}
