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

class DataMapper_Tests_Models
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
			'title' => 'DataMapper Tests &raquo; Instantiating test models',
			'methods' => array(
				'models' => 'test loading all models',
			),
		);
	}

	/*
	 * basic get operations
	 */
	public function models()
	{
		// instantiate all test models

		$dmtesta = new Dmtesta();
		$dmtestb = new Dmtestb();
		$dmtestc = new Dmtestc();
		$dmtestd = new Dmtestd();
		$dmteste = new Dmteste();

		if ( $dmtesta instanceOf DataMapper AND
			$dmtestb instanceOf DataMapper AND
			$dmtestc instanceOf DataMapper AND
			$dmtestd instanceOf DataMapper AND
			$dmteste instanceOf DataMapper )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

}
