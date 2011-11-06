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
				'includerelated' => 'Include on related queries',
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

	/*
	 * Include on related queries
	 *
	 * Note that the test queries use an order_by() clause to make the result order predictable!
	 */
	public function includerelated()
	{
		// include_related by string

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->include_related('dmtestb')->order_by('id', 'ASC')->order_by('dmtestb_data_b', 'ASC')->get();

			$result = array();
			foreach ( $dmtesta as $a )
			{
				$result[] = array(
					'id' =>	$a->id,
					'fk_id_a' => $a->fk_id_a,
					'data_a' => $a->data_a,
					'dmtestb_id' => $a->dmtestb_id,
					'dmtestb_data_b' => $a->dmtestb_data_b,
				);
			}
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'dmtestb_id' => '1',
				'dmtestb_data_b' => 'Table B Row 1',
			),
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'dmtestb_id' => '2',
				'dmtestb_data_b' => 'Table B Row 2',
			),
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'dmtestb_id' => '3',
				'dmtestb_data_b' => 'Table B Row 3',
			),
			array(
				'id' => 2,
				'fk_id_a' => 1,
				'data_a' => 'Table A Row 2',
				'dmtestb_id' => '2',
				'dmtestb_data_b' => 'Table B Row 2',
			),
			array(
				'id' => 3,
				'fk_id_a' => 1,
				'data_a' => 'Table A Row 3',
				'dmtestb_id' => NULL,
				'dmtestb_data_b' => NULL,
			),
		);

		$result = DataMapper_Tests::assertEqual($result, $expected_result, '$model->include_related("related")->get();');

		// include_related by object

		try
		{
			$dmtesta = new Dmtesta();
			$dmtestb = new Dmtestb();
			$dmtesta->include_related($dmtestb)->order_by('id', 'ASC')->order_by('dmtestb_data_b', 'ASC')->get();

			$result = array();
			foreach ( $dmtesta as $a )
			{
				$result[] = array(
					'id' =>	$a->id,
					'fk_id_a' => $a->fk_id_a,
					'data_a' => $a->data_a,
					'dmtestb_id' => $a->dmtestb_id,
					'dmtestb_data_b' => $a->dmtestb_data_b,
				);
			}
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'dmtestb_id' => '1',
				'dmtestb_data_b' => 'Table B Row 1',
			),
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'dmtestb_id' => '2',
				'dmtestb_data_b' => 'Table B Row 2',
			),
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'dmtestb_id' => '3',
				'dmtestb_data_b' => 'Table B Row 3',
			),
			array(
				'id' => 2,
				'fk_id_a' => 1,
				'data_a' => 'Table A Row 2',
				'dmtestb_id' => '2',
				'dmtestb_data_b' => 'Table B Row 2',
			),
			array(
				'id' => 3,
				'fk_id_a' => 1,
				'data_a' => 'Table A Row 3',
				'dmtestb_id' => NULL,
				'dmtestb_data_b' => NULL,
			),
		);

		$result = DataMapper_Tests::assertEqual($result, $expected_result, '$model->include_related($related)->get();');

		// include_related by string with custom fields and append_name

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->include_related('dmtestb', array('data_B'), 'append')->order_by('id', 'ASC')->order_by('append_data_b', 'ASC')->get();

			$result = array();
			foreach ( $dmtesta as $a )
			{
				$result[] = array(
					'id' =>	$a->id,
					'fk_id_a' => $a->fk_id_a,
					'data_a' => $a->data_a,
					'append_data_b' => $a->append_data_B,
				);
			}
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'append_data_b' => 'Table B Row 1',
			),
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'append_data_b' => 'Table B Row 2',
			),
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'append_data_b' => 'Table B Row 3',
			),
			array(
				'id' => 2,
				'fk_id_a' => 1,
				'data_a' => 'Table A Row 2',
				'append_data_b' => 'Table B Row 2',
			),
			array(
				'id' => 3,
				'fk_id_a' => 1,
				'data_a' => 'Table A Row 3',
				'append_data_b' => NULL,
			),
		);

		$result = DataMapper_Tests::assertEqual($result, $expected_result, '$model->->include_related("related", array("data_B"), "append")->get();');

		// include_related by deep string

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->include_related('dmtestb/dmteste')->order_by('id', 'ASC')->order_by('dmtestb_dmteste_data_e', 'ASC')->get();

			$result = array();
			foreach ( $dmtesta as $a )
			{
				$result[] = array(
					'id' =>	$a->id,
					'fk_id_a' => $a->fk_id_a,
					'data_a' => $a->data_a,
					'dmtestb_dmteste_id' => $a->dmtestb_dmteste_id,
					'dmtestb_dmteste_fk_id_B' => $a->dmtestb_dmteste_fk_id_B,
					'dmtestb_dmteste_data_E' => $a->dmtestb_dmteste_data_E,
				);
			}
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'dmtestb_dmteste_id' => NULL,
				'dmtestb_dmteste_fk_id_B' => NULL,
				'dmtestb_dmteste_data_E' => NULL,
			),
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'dmtestb_dmteste_id' => '1',
				'dmtestb_dmteste_fk_id_B' => '1',
				'dmtestb_dmteste_data_E' => 'Table E Row 1 FK B_1',
			),
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'dmtestb_dmteste_id' => '2',
				'dmtestb_dmteste_fk_id_B' => '2',
				'dmtestb_dmteste_data_E' => 'Table E Row 1 FK B_2',
			),
			array(
				'id' => 2,
				'fk_id_a' => 1,
				'data_a' => 'Table A Row 2',
				'dmtestb_dmteste_id' => '2',
				'dmtestb_dmteste_fk_id_B' => '2',
				'dmtestb_dmteste_data_E' => 'Table E Row 1 FK B_2',
			),
			array(
				'id' => 3,
				'fk_id_a' => 1,
				'data_a' => 'Table A Row 3',
				'dmtestb_dmteste_id' => NULL,
				'dmtestb_dmteste_fk_id_B' => NULL,
				'dmtestb_dmteste_data_E' => NULL,
			),
		);

		$result = DataMapper_Tests::assertEqual($result, $expected_result, '$model->include_related("deep/relation")->get();');

		// include_related by deep string

		try
		{
			$dmtesta = new Dmtesta();
			$dmtesta->include_related('dmtestb/dmteste', array('id'), 'append', TRUE)->order_by('id', 'ASC')->order_by('append_id', 'ASC')->get();

			$result = array();
			foreach ( $dmtesta as $a )
			{
				$result[] = array(
					'id' =>	$a->id,
					'fk_id_a' => $a->fk_id_a,
					'data_a' => $a->data_a,
					'append_id' => $a->append_id,
					'dmtestb' => TRUE,
					'dmteste' => TRUE,
				);
			}
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'append_id' => NULL,
				'dmtestb' => $dmtesta->all[0]->dmtestb instanceOf Dmtestb,
				'dmteste' => $dmtesta->all[0]->dmtestb->dmteste instanceOf Dmteste AND isset($dmtesta->all[0]->dmtestb->dmteste->id),
			),
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'append_id' => NULL,
				'dmtestb' => $dmtesta->all[1]->dmtestb instanceOf Dmtestb,
				'dmteste' => $dmtesta->all[1]->dmtestb->dmteste instanceOf Dmteste AND isset($dmtesta->all[1]->dmtestb->dmteste->id),
			),
			array(
				'id' => 1,
				'fk_id_a' => 0,
				'data_a' => 'Table A Row 1',
				'append_id' => NULL,
				'dmtestb' => $dmtesta->all[2]->dmtestb instanceOf Dmtestb,
				'dmteste' => $dmtesta->all[2]->dmtestb->dmteste instanceOf Dmteste AND isset($dmtesta->all[2]->dmtestb->dmteste->id),
			),
			array(
				'id' => 2,
				'fk_id_a' => 1,
				'data_a' => 'Table A Row 2',
				'append_id' => NULL,
				'dmtestb' => $dmtesta->all[3]->dmtestb instanceOf Dmtestb,
				'dmteste' => $dmtesta->all[3]->dmtestb->dmteste instanceOf Dmteste AND isset($dmtesta->all[3]->dmtestb->dmteste->id),
			),
			array(
				'id' => 3,
				'fk_id_a' => 1,
				'data_a' => 'Table A Row 3',
				'append_id' => NULL,
				'dmtestb' => $dmtesta->all[4]->dmtestb instanceOf Dmtestb,
				'dmteste' => $dmtesta->all[4]->dmtestb->dmteste instanceOf Dmteste AND isset($dmtesta->all[4]->dmtestb->dmteste->id),
			),
		);

		$result = DataMapper_Tests::assertEqual($result, $expected_result, '$model->->include_related("deep/relation", array("id"), "append", TRUE)->->get();');
	}
}
