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

class DataMapper_Tests_Manual_Basic
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
			'title' => 'DataMapper Tests &raquo; Manual &raquo; Get (Basic) examples',
			'methods' => array(
				'get' => 'basic get() operations',
			),
		);
	}

	/*
	 * basic get operations
	 */
	public function get()
	{
		// fetch all records and validate the result

		$dmtesta = new Dmtesta();
		$dmtesta->get();

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
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->get();');

		// fetch one record at offset 2 (the third record)

		$dmtesta = new Dmtesta();
		$dmtesta->get(1, 2);

		$expected_result = array(
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->get(1, 2);');

		// echo a field value from the result

		$result = DataMapper_Tests::assertEqual($dmtesta->data_A, 'Table A Row 3', 'echo $model->data_A;');

		// get the data_A value from all records

		$dmtesta = new Dmtesta();
		$dmtesta->get();

		$result = array();

		foreach ($dmtesta as $obj)
		{
			$result[] = $obj->data_A;
		}

		$expected_result = array(
			'Table A Row 1',
			'Table A Row 2',
			'Table A Row 3',
		);

		$result = DataMapper_Tests::assertEqual($result, $expected_result, 'foreach ($dmtesta as $obj) { $result[] = $obj->data_A; }');

		// do a validated get

		$dmtesta = new Dmtesta();

		$dmtesta->data_A = 'Table A Row 3';
		$dmtesta->validate()->get();

		$expected_result = array(
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$dmtesta->validate()->get();');

		// do a validated get which extra selection criteria

		$dmtesta = new Dmtesta();

		$dmtesta->data_A = 'Table A Row 3';
		$dmtesta->select('id')->where('id', 1)->validate()->get();

		$expected_result = array(
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$dmtesta->select("id")->where("id", 1)->validate()->get();');

		// get where

		$dmtesta = new Dmtesta();
		$dmtesta->get_where(array('fk_id_A' => 1), 1, 1);

		$expected_result = array(
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$dmtesta->get_where(array("fk_id_A" => 1), 1, 1);');

		// select

		$dmtesta = new Dmtesta();

		$dmtesta->select('data_A')->get();

		$expected_result = array(
			array(
				'id' => NULL,
				'fk_id_A' => NULL,
				'data_A' => 'Table A Row 1',
			),
			array(
				'id' => NULL,
				'fk_id_A' => NULL,
				'data_A' => 'Table A Row 2',
			),
			array(
				'id' => NULL,
				'fk_id_A' => NULL,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$dmtesta->select("data_A")->get();');
	}

}
