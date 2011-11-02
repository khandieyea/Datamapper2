<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * Run the Datamapper test framework
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
// Register the DataMapper autoloader
// -------------------------------------------------------------------------

spl_autoload_register('DataMapper_Tests::dm_autoload');

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
		if ( strpos($class, 'datamapper_tests') === 0 )
		{
			$file = realpath(__DIR__.DS.'..'.DS.'tests').DS.substr($class,17).EXT;
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
			$configs[$info['filename']] = call_user_func('DataMapper_Tests_'.ucfirst($info['filename']).'::_construct');
		}

		// and build a test plan
		foreach ( $configs as $name => $config )
		{
			// store the class name inside the config
			$config['name'] = $name;

			// is this a setup class?
			if ( ! empty($config['first']) )
			{
				if ( isset(self::$testplan['_first']) )
				{
					show_error('DataMapper Tests: There can only be one setup class defined!');
				}
				self::add($config, '_first');
			}

			// is this a breakdown class?
			elseif ( ! empty($config['last']) )
			{
				if ( isset(self::$testplan['_last']) )
				{
					show_error('DataMapper Tests: There can only be one breakdown class defined!');
				}
				self::add($config, '_last');
			}

			// standard test class
			else
			{
				// find out when we need to run this test
				$offset = 1;

				foreach( $config['before'] as $class )
				{
					var_dump($config);die();
				}

				foreach( $config['after'] as $class )
				{
					var_dump($config);die();
				}

				self::add($config, $name, $offset);
			}
		}

		// benchmark
		self::$CI->benchmark->mark('datamapper_tests_setup_end');

		// benchmark
		self::$CI->benchmark->mark('datamapper_tests_run_start');

		// building done, time to start testing!
		$counter = 0;
		foreach ( self::$testplan as $name => $tests )
		{
			// mark the start
			self::mark(++$counter.' &raquo; '.$tests['title'], 1, TRUE);

			// run the defined tests
			foreach ( $tests['methods'] as $method => $title )
			{
				is_numeric($method) AND $method = $title;

				self::mark('&raquo; '.$title,3);

				if ( $result = call_user_func('DataMapper_Tests_'.ucfirst($tests['name']).'::'.$method) === FALSE )
				{
					// bail out at a fatal error!
					self::mark('Test aborted due to fatal error!', 3, true);
					break 2;
				}
			}

			// mark the end
			self::mark('&raquo; finished', 3, TRUE);
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
	public static function mark($text, $size = 2, $separator = FALSE, $style = '')
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
	public static function success($text, $size = 2, $separator = FALSE)
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
	public static function failed($text, $size = 2, $separator = FALSE)
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
			self::success($text, 4);
			return TRUE;
		}
		else
		{
			self::failed($text, 4);
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
			self::success($text, 4);
			return TRUE;
		}
		else
		{
			self::failed($text, 4);
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
			self::success($text, 4);
			return TRUE;
		}
		else
		{
			self::failed($text, 4);
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * add a test config to the testplan
	 *
	 * @return	void
	 */
	protected static function add($config, $name, $offset = 1)
	{
		if ( $name == '_first' )
		{
			self::$testplan = array_merge( array('_first' => $config), self::$testplan );
		}
		elseif ( $name == '_last' )
		{
			self::$testplan['_last'] = $config;
		}
		else
		{
			$new = isset(self::$testplan['_first']) ? array('_first' => self::$testplan['_first']) : array();

			$counter = 0;
			foreach (self::$testplan as $class => $value )
			{
				if ( $class != '_first' AND $class != '_last')
				{
					if ( $counter == $offset )
					{
						$new[$name] = $config;
					}
					$new[$class] = $value;
					$counter++;
				}
			}

			if ( ! isset($new[$name]) )
			{
				$new[$name] = $config;
			}

			isset(self::$testplan['_last']) AND $new['_last'] = self::$testplan['_last'];

			self::$testplan = $new;
		}
	}
}

// Run Tests, Run!!!

DataMapper_Tests::run();


/* End of file datamapper_tests.php */
/* Location: ./application/third_party/datamapper/helper/datamapper_tests_helper.php */
