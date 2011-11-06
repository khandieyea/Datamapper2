<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * Datamapper test framework
 *
 * @license     MIT License
 * @package     DataMapper ORM
 * @category    DataMapper ORM
 * @author      Harro "WanWizard" Verton
 * @link        http://datamapper.wanwizard.eu
 * @version     2.0.0
 */

/**
 * shortcut for the directory separator
 */
! defined('DS') AND define('DS', DIRECTORY_SEPARATOR);

// -------------------------------------------------------------------------
// Core tests class
// -------------------------------------------------------------------------

class DataMapper_Tests
{
	// storage for the CI instance
	public static $CI;

	// storage for the final test plan
	public static $testplan = array();

	// counter for succesful tests
	public static $success = 0;

	// counter for failed tests
	public static $failed = 0;

	// --------------------------------------------------------------------

	/**
	 * autoloads object classes that are used with DataMapper Test framework.
	 *
	 * @param	string	$class	Name of class to load.
	 *
	 * @return	void
	 */
	public static function dm_autoload($class)
	{
		// prepare class
		$class = strtolower($class);

		// check for a datamapper core extension class
		if ( is_numeric(substr($class, 0, 2)) )
		{
			$file = realpath(__DIR__.DS.'..'.DS.'tests').DS.$class.EXT;
			if ( file_exists($file) )
			{
				require_once($file);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * run all defined tests
	 *
	 * @return	void
	 */
	public static function run()
	{
		// get the CI instance
		self::$CI = get_instance();

		// load the profiler library, with a custom config (we need to see queries!)
		self::$CI->load->library('profiler', array('query_toggle_count' => 999999));

		// enable the profiler
		self::$CI->output->enable_profiler(TRUE);

		// load the datamapper library
		self::$CI->load->library('datamapper');

		// benchmark
		self::$CI->benchmark->mark('datamapper_tests_setup_start');

		// load the directory helper
		self::$CI->load->helper('directory');

		// path to the test classes
		$path = realpath(__DIR__.DS.'..'.DS.'tests').DS;

		// load all test classes
		$dir = directory_map($path, 1);

		// storage for the raw test class config
		$configs = array();

		// load the test configs
		foreach ( $dir as $file )
		{
			$info = pathinfo($file);
			$class = 'DataMapper_Tests_'.ucfirst(substr($info['filename'],3));
			class_exists($info['filename']);
			$configs[$info['filename']] = call_user_func($class.'::_construct');
		}

		// make sure we hav the correct order of the tests
		ksort($configs);

		// benchmark
		self::$CI->benchmark->mark('datamapper_tests_setup_end');

		// benchmark
		self::$CI->benchmark->mark('datamapper_tests_run_start');

		// building done, time to start testing!
		$counter = 0;
		foreach ( $configs as $name => $tests )
		{
			// set the name
			$tests['name'] = substr($name, 3);

			// mark the start
			self::mark(++$counter.' &raquo; '.$tests['title'], 3, TRUE);

			// run the defined tests
			if ( ! empty($tests['methods']) AND is_array($tests['methods']) )
			{
				foreach ( $tests['methods'] as $method => $title )
				{
					is_numeric($method) AND $method = $title;

					self::mark('&raquo; '.$title, 4);

					if ( is_callable('DataMapper_Tests_'.ucfirst($tests['name']).'::'.$method) )
					{
						if ( $result = call_user_func('DataMapper_Tests_'.ucfirst($tests['name']).'::'.$method) === FALSE )
						{
							// bail out at a fatal error!
							self::mark('Test aborted due to fatal error!', 3, true);
							break 2;
						}
					}
					else
					{
						self::failed('skipped, test method is not defined!', 5, true);
					}
				}

				// mark the end
				self::mark('&raquo; finished', 4, TRUE);
			}
			else
			{
				// mark the end
				self::mark('&raquo; no tests defined for this section', 4, TRUE);
			}
		}

		// print the successes and failures
		self::$success AND self::success(self::$success.' of '.(self::$success+self::$failed).' tests finished successfully', 3);
		self::$failed AND self::failed(self::$failed.' of '.(self::$success+self::$failed-1).' tests failed', 3);

		// benchmark
		self::$CI->benchmark->mark('datamapper_tests_run_end');
	}

	// --------------------------------------------------------------------

	/**
	 * print a message
	 *
	 * @return	void
	 */
	public static function mark($text, $size = 3, $separator = FALSE, $style = '')
	{
		$style == 'green' AND $style = 'color:white;background-color:green;margin:5;padding:5;';
		$style == 'red' AND $style = 'color:white;background-color:red;margin:5;padding:5;';

		echo '<h',$size,' style="',$style,'">',$text,'</h',$size,'>',($separator?'<hr />':'');
	}

	// --------------------------------------------------------------------

	/**
	 * print a success message
	 *
	 * @return	void
	 */
	public static function success($text, $size = 5, $separator = FALSE)
	{
		self::mark($text, $size, $separator, 'green');
		self::$success++;
	}

	// --------------------------------------------------------------------

	/**
	 * print a failed message
	 *
	 * @return	void
	 */
	public static function failed($text, $size = 5, $separator = FALSE)
	{
		self::mark($text, $size, $separator, 'red');
		self::$failed++;
	}

	// --------------------------------------------------------------------

	/**
	 * dump a variable
	 *
	 * @return	void
	 */
	public static function dump($var, $title = '')
	{
		! empty($title) and mark($title.':',4);
		var_dump($var);
	}

	// --------------------------------------------------------------------

	/**
	 * assertTrue
	 *
	 * @return	void
	 */
	public static function assertTrue($var, $text)
	{
		if ( $var === TRUE )
		{
			self::success($text);
			return TRUE;
		}
		else
		{
			self::failed($text);
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * assertFalse
	 *
	 * @return	void
	 */
	public static function assertFalse($var, $text)
	{
		if ( $var === FALSE )
		{
			self::success($text);
			return TRUE;
		}
		else
		{
			self::failed($text);
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * assertEqual
	 *
	 * @return	void
	 */
	public static function assertEqual($var1, $var2, $text)
	{
		if ( $var1 === $var2 )
		{
			self::success($text);
			return TRUE;
		}
		else
		{
			self::failed($text);
			return FALSE;
		}
	}

}

// -------------------------------------------------------------------------
// Register the DataMapper test packages autoloader
// -------------------------------------------------------------------------

spl_autoload_register('DataMapper_Tests::dm_autoload');
