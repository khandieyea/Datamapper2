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

class DataMapper_Tests_Miscellaneous
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
			'title' => 'DataMapper Tests &raquo; Miscellaneous',
			'methods' => array(
				'errors' => 'error messages',
			),
		);
	}

	/*
	 * error messages
	 */
	public function errors()
	{
		// empty errors
		$dmtesta = new Dmtesta();

		$expected_result = array(
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->error->all, $expected_result, 'empty $dmtesta->error->all;');

		$expected_result = '';

		$result = DataMapper_Tests::assertEqual($dmtesta->error->string, $expected_result, 'empty $dmtesta->error->string;');

		$result = DataMapper_Tests::assertEqual($dmtesta->error->data_A, $expected_result, 'empty $dmtesta->error->data_A;');

		// set an error on data_A
		try
		{
			$dmtesta->error_message('data_A', 'This error is set on the data_A field');
		}
		catch (Exception $e)
		{
			DataMapper_Tests::failed('Exception: '.$e->getMessage());
		}

		$expected_result = array(
			'data_A' => '<p>This error is set on the data_A field</p>'
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->error->all, $expected_result, '$dmtesta->error_message() &raquo; $dmtesta->error->all;');

		$expected_result = '<p>This error is set on the data_A field</p>';

		$result = DataMapper_Tests::assertEqual($dmtesta->error->string, $expected_result, '$dmtesta->error_message() &raquo; $dmtesta->error->string;');

		$result = DataMapper_Tests::assertEqual($dmtesta->error->data_A, $expected_result, '$dmtesta->error_message() &raquo; $dmtesta->error->data_A;');

		// set an error on data_A

		$dmtesta->error->clear();
		$dmtesta->error->message('fk_id_A', 'This error is set on the fk_id_A field');

		$expected_result = array(
			'fk_id_A' => '<p>This error is set on the fk_id_A field</p>'
		);

		$result = DataMapper_Tests::assertEqual($dmtesta->error->all, $expected_result, '$dmtesta->error->message() &raquo; $dmtesta->error->all;');
	}

}
