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
				'get' => 'Basic get() operations',
				'selections' => 'Field selections',
				'results' => 'Limiting results',
				'grouping' => 'Query grouping',
				'others' => 'Other Query statements',
				'cache' => 'Active record caching',
			),
		);
	}

	/*
	 * basic get operations
	 */
	public function get()
	{
		// fetch all records and validate the result

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->get();
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
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->get();');

		// fetch one record at offset 2 (the third record)

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->get(1, 2);
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->get(1, 2);');

		// echo a field value from the result

		$result = DataMapper_Tests::assertEqual($dmtesta->data_A, 'Table A Row 3', 'echo $model->data_A;');

		// get the data_A value from all records

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->get();

			$result = array();

			foreach ($dmtesta as $obj)
			{
				$result[] = $obj->data_A;
			}
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			'Table A Row 1',
			'Table A Row 2',
			'Table A Row 3',
		);

		$result = DataMapper_Tests::assertEqual($result, $expected_result, 'foreach ($dmtesta as $obj) { $result[] = $obj->data_A; }');

		// do a validated get

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->data_A = 'Table A Row 3';
			$dmtesta->validate()->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->validate()->get();');

		// do a validated get which extra selection criteria

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->data_A = 'Table A Row 3';
			$dmtesta->select('id')->where('id', 1)->validate()->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->select("id")->where("id", 1)->validate()->get();');

		// get where

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->get_where(array('fk_id_A' => 1), 1, 1);
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->get_where(array("fk_id_A" => 1), 1, 1);');
	}

	/*
	 * field selections
	 */
	public function selections()
	{
		// select

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->select('data_A')->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->select("data_A")->get();');

		// select_max

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->select_max('id')->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 3,
				'fk_id_A' => NULL,
				'data_A' => NULL,
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->select_max("id")->get()');

		// select_min

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->select_min('id')->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 1,
				'fk_id_A' => NULL,
				'data_A' => NULL,
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->select_min("id")->get()');

		// select_avg

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->select_avg('id')->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 2,
				'fk_id_A' => NULL,
				'data_A' => NULL,
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->select_avg("id")->get()');

		// select_sum

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->select_sum('id')->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 6,
				'fk_id_A' => NULL,
				'data_A' => NULL,
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->select_sum("id")->get()');

		// select_distinct

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->select('fk_id_A')->distinct()->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => NULL,
				'fk_id_A' => 0,
				'data_A' => NULL,
			),
			array(
				'id' => NULL,
				'fk_id_A' => 1,
				'data_A' => NULL,
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->select("fk_id_A")->distinct->get()');
	}

	/*
	 * limiting results
	 */
	public function results()
	{
		// where() with simple key/value pair

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->where('fk_id_A', 1)->get();
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
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where("fk_id_A", 1)->get();');

		// where() with multiple key/value pair

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->where('fk_id_A', 1)->where('data_A', 'Table A Row 3')->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where("fk_id_A", 1)->where("data_A", "Table A Row 3")->get();');

		// where() with associative array method

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->where( array('fk_id_A' => 1, 'data_A' => 'Table A Row 3') )->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where( array("fk_id_A" => 1, "data_A" => "Table A Row 3") )->get();');

		// where() with operator

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->where('fk_id_A !=', 1)->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where("fk_id_A !=", 1)->get();');

		// where() with custom string

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->where('fk_id_A = 1 AND data_A = "Table A Row 3"')->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where("fk_id_A = 1 AND data_A = \'Table A Row 3\'")->get();');

		// or_where()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->where('fk_id_A', 0)->or_where('fk_id_A', 1)->get();
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
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where("fk_id_A", 0)->or_where("fk_id_A", 1)->get();');

		// where_in()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->where_in('fk_id_A', array(0, 1))->get();
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
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where_in("fk_id_A", array(0, 1))->get()');

		// or_where_in()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->where_in('fk_id_A', array(0))->or_where_in('fk_id_A', array(1))->get();
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
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where_in("fk_id_A", array(0))->or_where_in("fk_id_A", array(1))->get();');

		// where_not_in()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->where_not_in('fk_id_A', array(1))->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where_not_in("fk_id_A", array(0))->get()');

		// or_where_not_in()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->where_not_in('fk_id_A', array(0))->where_not_in('fk_id_A', array(1))->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where_not_in("fk_id_A", array(0))->or_where_not_in("fk_id_A", array(1))->get()');

		// like()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->like('data_A', 'Row 3')->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->like("data_A", "Row 3")->get();');

		// or_like()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->like('data_A', 'Row 3')->or_like('data_A', 'Row 1')->get();
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
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->like("data_A", "Row 3")->or_like("data_A", "Row 1")->get();');

		// not_like()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->not_like('data_A', 'Row 3')->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->not_like("data_A", "Row 3")->get();');

		// or_not_like()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->not_like('data_A', 'Row 3')->or_not_like('data_A', 'Row 2')->get();
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
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->not_like("data_A", "Row 3")->or_not_like("data_A", "Row 2")->get();');

		// ilike() - to properly test this, the table data need to be case sensitive!

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->ilike('data_A', 'ROW 2')->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->ilike("data_A", "TABLE")->get();');

		DataMapper_Tests::mark('The next test result will be RED if you have used a "dbcollat" in your database configuration that is NOT case-sensitive!', 5);

		// ilike() - to properly test this, the table data need to be case sensitive!

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->like('data_A', 'ROW')->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->like("data_A", "TABLE")->get();');
	}

	/*
	 * query grouping
	 */
	public function grouping()
	{
		// do some complicated grouping

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->where('id', 1)->group_start()->where('id !=', 2)->or_where('id', 3)->group_end()->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where("id", 1)->group_start()->where("id !=", 2)->or_where("id", 3)->group_end()->get();');

		// empty group test

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->where('id', 1)->group_start()->group_end()->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->where("id", 1)->group_start()->group_end()->get();');
	}

	/*
	 * other features
	 */
	public function others()
	{
		// group_by()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->group_by('fk_id_A')->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->group_by("fk_id_A")->get();');

		// group_by() using multiple fields

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->group_by( array('fk_id_A', 'data_A') )->get();
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
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->group_by( array("fk_id_A", "data_A") )->get();');

		// having()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->having('fk_id_A', 1)->get();
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
			array(
				'id' => 3,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->having("fk_id_A", 1)->get();');

		// order_by()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->order_by('data_A', 'DESC')->get();
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
			array(
				'id' => 2,
				'fk_id_A' => 1,
				'data_A' => 'Table A Row 2',
			),
			array(
				'id' => 1,
				'fk_id_A' => 0,
				'data_A' => 'Table A Row 1',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->order_by("data_A", "DESC")->get();');

		// limit()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->order_by('data_A', 'DESC')->limit(1)->get();
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

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->order_by("data_A", "DESC")->limit(1)->get();');
	}

	/*
	 * active record caching
	 */
	public function cache()
	{
		// start and stop cache()

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->start_cache()->select('id')->stop_cache()->get()->select('data_A')->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 1,
				'fk_id_A' => NULL,
				'data_A' => 'Table A Row 1',
			),
			array(
				'id' => 2,
				'fk_id_A' => NULL,
				'data_A' => 'Table A Row 2',
			),
			array(
				'id' => 3,
				'fk_id_A' => NULL,
				'data_A' => 'Table A Row 3',
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->start_cache()->select("id")->stop_cache()->get()->select("data_A")->get();');

		try
		{
			$dmtesta = new Dmtesta();

			$dmtesta->flush_cache()->select('id')->get();
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 1,
				'fk_id_A' => NULL,
				'data_A' => NULL,
			),
			array(
				'id' => 2,
				'fk_id_A' => NULL,
				'data_A' => NULL,
			),
			array(
				'id' => 3,
				'fk_id_A' => NULL,
				'data_A' => NULL,
			),
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->all_to_array(), $expected_result, '$model->flush_cache()->select("id")->get();');
	}
}
