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

class DataMapper_Tests_Singlekeys
{
	public static $CI;

	public static $dmtesta;
	public static $dmtestb;
	public static $dmtestc;
	public static $dmtestd;
	public static $dmteste;

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
			'title' => 'DataMapper Tests &raquo; Single Keys',
			'first' => FALSE,
			'last' => FALSE,
			'before' => array(),
			'after' => array(),
			'methods' => array(
				'models' => 'loading test models',
				'get' => 'basic get() operations',
				'simple_related' => 'simple related get() operations',
			),
		);
	}

	/*
	 * load our models
	 */
	public function models()
	{
		// load our test models, test if they're loaded correctly

		self::$dmtesta = new Dmtesta();
		$result = DataMapper_Tests::assertTrue(self::$dmtesta instanceOf DataMapper, 'check if self::$dmtesta is a DataMapper object');
		if ( ! $result ) return FALSE;

		self::$dmtestb = new Dmtestb();
		$result = DataMapper_Tests::assertTrue(self::$dmtestb instanceOf DataMapper, 'check if self::$dmtestb is a DataMapper object');
		if ( ! $result ) return FALSE;

		self::$dmtestc = new Dmtestc();
		$result = DataMapper_Tests::assertTrue(self::$dmtestc instanceOf DataMapper, 'check if self::$dmtestc is a DataMapper object');
		if ( ! $result ) return FALSE;

		self::$dmtestd = new Dmtestd();
		$result = DataMapper_Tests::assertTrue(self::$dmtestd instanceOf DataMapper, 'check if self::$dmtestd is a DataMapper object');
		if ( ! $result ) return FALSE;

		self::$dmteste = new Dmteste();
		$result = DataMapper_Tests::assertTrue(self::$dmteste instanceOf DataMapper, 'check if self::$dmteste is a DataMapper object');
		if ( ! $result ) return FALSE;
	}

	/*
	 * basic get operations
	 */
	public function get()
	{
		// fetch the first record and validate the result

		self::$dmtesta->where('id', 1)->get();
		$result = DataMapper_Tests::assertEqual(self::$dmtesta->to_array(), array('id' => 1, 'fk_id_A' => 0, 'data_A' => 'Table A Row 1'), 'self::$dmtesta get() first record');

		self::$dmtestb->where('id', 1)->get();
		$result = DataMapper_Tests::assertEqual(self::$dmtestb->to_array(), array('id' => 1, 'data_B' => 'Table B Row 1'), 'self::$dmtestb get() first record');

		self::$dmtestc->where('id', 1)->get();
		$result = DataMapper_Tests::assertEqual(self::$dmtestc->to_array(), array('id' => 1, 'fk_id_A' => 1, 'fk_id_B' => 1, 'data_C' => 'Table C join A_1 to B_1'), 'self::$dmtestc get() first record');

		self::$dmtestd->where('id', 1)->get();
		$result = DataMapper_Tests::assertEqual(self::$dmtestd->to_array(), array('id' => 1, 'fk_id_A' => 1, 'data_D' => 'Table D Row 1 FK A_1'), 'self::$dmtestd get() first record');

		self::$dmteste->where('id', 1)->get();
		$result = DataMapper_Tests::assertEqual(self::$dmteste->to_array(), array('id' => 1, 'fk_id_B' => 1, 'data_E' => 'Table E Row 1 FK B_1'), 'self::$dmteste get() first record');
	}

	/*
	 * related get operations
	 */
	public function simple_related()
	{
		// getting simple child relations, fetch the records in dmtestb related to dmtesta, id = 1

		$expected_result = array(
			array(
				'id' => 1,
				'data_B' => 'Table B Row 1',
			),
			array(
				'id' => 2,
				'data_B' => 'Table B Row 2',
			),
			array(
				'id' => 3,
				'data_B' => 'Table B Row 3',
			),
		);

		self::$dmtesta->dmtestb->get();
		$result = DataMapper_Tests::assertEqual(self::$dmtesta->dmtestb->all_to_array(), $expected_result, 'self::$dmtesta->dmtestb->get() related records');

		// getting simple child relations, fetch the records with id = 2 in dmtestb related to dmtesta, id = 1

		$expected_result = array(
			array(
				'id' => 2,
				'data_B' => 'Table B Row 2',
			),
		);

		self::$dmtesta->dmtestb->where('id', 2)->get();
		$result = DataMapper_Tests::assertEqual(self::$dmtesta->dmtestb->all_to_array(), $expected_result, 'self::$dmtesta->dmtestb->where("id", 2)->get() related records');

		// getting simple grandchild relations, fetch the records of dmteste related to dmtestb.id = 2 related to dmtesta.id = 1

		$expected_result = array(
			array(
				'id' => 2,
				'fk_id_B' => 2,
				'data_E' => 'Table E Row 1 FK B_2',
			),
		);

		self::$dmtesta->clear()->where('id', 1)->dmtestb->where('id', 2)->dmteste->get();
self::$dmtesta->dmtestb->dmteste->check_last_query();
		$result = DataMapper_Tests::assertEqual(self::$dmtesta->dmtestb->dmteste->all_to_array(), $expected_result, 'self::$dmtesta->clear()->where("id", 1)->dmtestb->where("id", 3)->dmteste->get() related records');

		// getting self referenced results of dmtesta, related to dmtesta.id = 1
		self::$dmtesta->clear()->where('id', 1)->selfref->get();
self::$dmtesta->selfref->check_last_query();
var_dump(self::$dmtesta->clear()->where('id', 1)->selfref->all_to_array());
	}
}
