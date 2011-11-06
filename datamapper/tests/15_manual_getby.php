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

class DataMapper_Tests_Manual_Getby
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
			'title' => 'DataMapper Tests &raquo; Manual &raquo; Get_by examples',
			'methods' => array(
				'getby' => 'Basic get_by methods',
				'advanced' => 'Advanced get_by methods'
			),
		);
	}

	/*
	 * basic get_by operations
	 */
	public function getby()
	{
		// get_by_id

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->get_by_id(1);
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
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->get_by_id(1);');

		// get_by_data_A

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->get_by_data_A('Table A Row 3');
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->get_by_data_A("Table A Row 1");');
	}

	/*
	 * advanced get_by operations
	 */
	public function advanced()
	{
		// $object->get_by_related_{model}($field, $value);

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->where_related_dmtestb('id', 2)->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where_related_dmtestb("id", 2)->get();');

		// $object->get_by_related($model, $field, $value);

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->where_related('dmtestb', 'id', 2)->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where_related("dmtestb", "id", 2)->get();');

		// $object->get_by_related($related_object, $field, $value);

		try
		{
			$dmtesta = new Dmtesta();
			$dmtestb = new Dmtestb();
			$dmtesta->where_related($dmtestb, 'id', 2)->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where_related($related, "id", 2)->get();');
	}
}
