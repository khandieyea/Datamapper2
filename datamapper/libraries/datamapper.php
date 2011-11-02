<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * Transforms database tables into objects.
 *
 * @license     MIT License
 * @package     DataMapper ORM
 * @category    DataMapper ORM
 * @author      Harro "WanWizard" Verton
 * @link        http://datamapper.wanwizard.eu
 * @version     2.0.0
 */

// -------------------------------------------------------------------------
// Global definitions
// -------------------------------------------------------------------------

/**
 * DataMapper version
 */
define('DATAMAPPER_VERSION', '2.0.0');

/**
 * shortcut for the directory separator
 */
! defined('DS') AND define('DS', DIRECTORY_SEPARATOR);

/**
 * enable exceptions if not already set
 */
! defined('DATAMAPPER_EXCEPTIONS') AND define('DATAMAPPER_EXCEPTIONS', TRUE);

// -------------------------------------------------------------------------
// DataMapper class definition
// -------------------------------------------------------------------------

class DataMapper implements IteratorAggregate
{
	// -------------------------------------------------------------------------
	// Static class definition
	// -------------------------------------------------------------------------

	/**
	 * storage for the CI "superobject"
	 *
	 * @var object
	 */
	public static $CI = NULL;

	/**
	 * storage for the location of the Datamapper installation
	 *
	 * @var string
	 */
	protected static $dm_path = NULL;

	/**
	 * storage for additional model paths for the autoloader
	 *
	 * @var array
	 */
	protected static $dm_model_paths = array();

	/**
	 * storage for additional extension paths for the autoloader
	 *
	 * @var array
	 */
	protected static $dm_extension_paths = array();

	/**
	 * track the initialisation state of DataMapper
	 *
	 * @var boolean
	 */
	protected static $dm_initialized = FALSE;

	/**
	 * DataMapper default global configuration
	 *
	 * @var array
	 */
	protected static $dm_global_config = array(
		'prefix'					=> '',
		'join_prefix'				=> '',
		'error_prefix'				=> '<p>',
		'error_suffix'				=> '</p>',
		'model_prefix' 				=> '',
		'model_suffix' 				=> '',
		'created_field'				=> 'created',
		'updated_field'				=> 'updated',
		'local_time'				=> FALSE,
		'unix_timestamp'			=> TRUE,
		'timestamp_format'			=> '',
		'lang_file_format'			=> 'model_${model}',
		'field_label_lang_format'	=> '${model}_${field}',
		'auto_transaction'			=> FALSE,
		'auto_populate_has_many'	=> FALSE,
		'auto_populate_has_one'		=> FALSE,
		'all_array_uses_keys'		=> FALSE,
		'db_params'					=> FALSE,
		'cache_path'				=> FALSE,
		'cache_expiration'			=> FALSE,
		'extensions_path'			=> array(),
		'extensions'				=> array(),
		'extension_overload'		=> FALSE,
		'cascade_delete'			=> TRUE,
		'free_result_threshold'		=> 100,
	);

	/**
	 * global object configuration
	 *
	 * This array will contain the config of all loaded DataMapper models
	 *
	 * @var array
	 */
	protected static $dm_model_config = array();

	/**
	 * storage for available DataMapper extension methods
	 *
	 * @var array
	 */
	protected static $dm_extension_methods = array(
		// core extension: array methods
		'from_array'             => 'DataMapper_Array',
		'to_array'               => 'DataMapper_Array',
		'all_to_array'           => 'DataMapper_Array',
		'all_to_single_array'    => 'DataMapper_Array',

		// core extension: validation methods
		'validate'               => 'DataMapper_Validation',
		'skip_validation'        => 'DataMapper_Validation',
		'force_validation'       => 'DataMapper_Validation',
		'run_get_rules'          => 'DataMapper_Validation',

		// core extension: transaction methods
		'trans_begin'            => 'DataMapper_Transactions',
		'trans_commit'           => 'DataMapper_Transactions',
		'trans_complete'         => 'DataMapper_Transactions',
		'trans_off'              => 'DataMapper_Transactions',
		'trans_rollback'         => 'DataMapper_Transactions',
		'trans_start'            => 'DataMapper_Transactions',
		'trans_status'           => 'DataMapper_Transactions',
		'trans_strict'           => 'DataMapper_Transactions',
		'dm_auto_trans_begin'    => 'DataMapper_Transactions',
		'dm_auto_trans_complete' => 'DataMapper_Transactions',
	);

	// --------------------------------------------------------------------

	/**
	 * autoloads object classes that are used with DataMapper.
	 * this method will look in any model directories available to CI.
	 *
	 * Note:
	 * it is important that they are autoloaded as loading them manually with
	 * CodeIgniter's loader class will cause DataMapper's __get and __set functions
	 * to not function.
	 *
	 * @param	string	$class	Name of class to load.
	 *
	 * @return	void
	 */
	public static function dm_autoload($class)
	{
		// don't attempt to autoload before DataMapper is initialized, or any CI_ , EE_, or application prefixed classes
		if ( is_null(DataMapper::$CI) OR in_array(substr($class, 0, 3), array('CI_', 'EE_')) OR strpos($class, DataMapper::$CI->config->item('subclass_prefix')) === 0 )
		{
			return;
		}

		// prepare class
		$class = strtolower($class);

		// check for a datamapper core extension class
		if ( strpos($class, 'datamapper_') === 0 )
		{
			foreach ( DataMapper::$dm_extension_paths as $path )
			{
				$file = $path . DS . substr($class,11) . EXT;
				if ( file_exists($file) )
				{
					require_once($file);
					break;
				}
			}
		}

		// not a support class? check for a model next
		if ( ! class_exists($class) )
		{
			// prepare the possible model paths
			$paths = array_merge( DataMapper::$CI->load->get_package_paths(false), DataMapper::$dm_model_paths );

			foreach ( $paths as $path )
			{
				// prepare file
				$file = $path . 'models' . DS . $class . EXT;

				// Check if file exists, require_once if it does
				if ( file_exists($file) )
				{
					require_once($file);
					break;
				}
			}

			// if the class is still not loaded, do a recursive search of model paths for the class
			if ( ! class_exists($class) )
			{
				foreach ( $paths as $path )
				{
					$found = DataMapper::dm_recursive_require_once($class, $path . 'models');
					if ( $found ) break;
				}
			}
		}

	}

	// --------------------------------------------------------------------

	/**
	 * Recursive Require Once
	 *
	 * Recursively searches the path for the class, require_once if found.
	 *
	 * @param	string	$class	Name of class to look for
	 * @param	string	$path	Current path to search
	 *
	 * @return	boolean	TRUE if the class was found and loaded, FALSE otherwise
	 */
	protected static function dm_recursive_require_once($class, $path)
	{
		$found = FALSE;
		if ( is_dir($path) )
		{
			if ( $handle = opendir($path) )
			{
				while ( FALSE !== ($dir = readdir($handle)) )
				{
					// If dir does not contain a dot
					if ( strpos($dir, '.') === FALSE )
					{
						// Prepare recursive path
						$recursive_path = $path . '/' . $dir;

						// Prepare file
						$file = $recursive_path . '/' . $class . EXT;

						// Check if file exists, require_once if it does
						if ( file_exists($file)  )
						{
							require_once($file);
							$found = TRUE;
							break;
						}
						elseif ( is_dir($recursive_path) )
						{
							// Do a recursive search of the path for the class
							DataMapper::dm_recursive_require_once($class, $recursive_path);
						}
					}
				}

				closedir($handle);
			}
		}
		return $found;
	}

	// --------------------------------------------------------------------

	/**
	 * manually add paths for the model autoloader
	 *
	 * @param	mixed	$paths	path or array of paths to search
	 */
	public static function add_model_path($paths)
	{
		// make sure $paths is an array
		! is_array($paths) AND $paths = array($paths);

		foreach($paths as $path)
		{
			$path = realpath(rtrim($path, DS) . DS);
			if ( $path AND is_dir($path.'models') AND ! in_array($path, DataMapper::$dm_model_paths))
			{
				DataMapper::$dm_model_paths[] = $path;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * manually add paths for the extensions autoloader
	 *
	 * @param	mixed	$paths	path or array of paths to search
	 */
	public static function add_extension_path($paths)
	{
		// make sure $paths is an array
		! is_array($paths) AND $paths = array($paths);

		foreach($paths as $path)
		{
			$path = realpath(rtrim($path, DS) . DS);
			if ( is_dir($path) AND ! in_array($path, DataMapper::$dm_extension_paths))
			{
				DataMapper::$dm_extension_paths[] = $path;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Validate a config array
	 *
	 * Validates the contents of the DataMapper configuration array passed
	 *
	 * @param	array	$config		Array with DataMapper configuration values
	 * @param	string	$context	Name of the context: 'global' or <model_name>
	 *
	 * @return	void
	 *
	 * @throws	DataMapper_Exception	in case the configuration array passed does not validate
	 */
	protected static function dm_validate_config(&$config, $context)
	{
		foreach ( $config as $name => $value )
		{
			switch ($name)
			{
				// check and validate string values
				case 'prefix':
				case 'join_prefix':
				case 'error_prefix':
				case 'error_suffix':
				case 'created_field':
				case 'updated_field':
				case 'model_prefix':
				case 'model_suffix':
				case 'timestamp_format':
				case 'lang_file_format':
				case 'field_label_lang_format':
					empty($value) AND $config[$name] = $value = '';
					if ( ! is_string($value) )
					{
						throw new DataMapper_Exception("DataMapper: Error in the '$context' configuration => item '$name' must be a string value");
					}
					break;

				// check and validate boolean values
				case 'local_time':
				case 'unix_timestamp':
				case 'auto_transaction':
				case 'auto_populate_has_many':
				case 'auto_populate_has_one':
				case 'all_array_uses_keys':
				case 'cascade_delete':
				case 'extension_overload':
					if ( ! is_bool($value) )
					{
						throw new DataMapper_Exception("DataMapper: Error in the '$context' configuration => item '$name' must be a boolean value");
					}
					break;

				// check and validate integer values
				case 'free_result_threshold':
					is_numeric($value) and $value = (int) $value;
					if ( ! is_int($value) )
					{
						throw new DataMapper_Exception("DataMapper: Error in the '$context' configuration => item '$name' must be a integer value");
					}
					break;

				// check and validate array values
				case 'extensions':
				case 'extensions_path':
					if ( ! is_array($value) )
					{
						throw new DataMapper_Exception("DataMapper: Error in the '$context' configuration => item '$name' must be a array");
					}
					break;

				// special cases
				case 'cache_path':
					empty($value) AND $config[$name] = $value = FALSE;
					if ( is_string($value) )
					{
						if ( ! is_dir($value) AND ! is_dir($config[$name] = $value = APPPATH.$value)  )
						{
							throw new DataMapper_Exception("DataMapper: Error in the '$context' configuration => item '$name' must be a valid directory name");
						}
						elseif ( ! is_writable($value) )
						{
							throw new DataMapper_Exception("DataMapper: Error in the '$context' configuration => item '$name' must be writeable");
						}
						$config[$name] = realpath($value) . DS;
					}
					break;

				case 'cache_expiration':
					empty($value) AND $config[$name] = $value = 0;
					if ( ! is_numeric($value) OR $value < 0 )
					{
						throw new DataMapper_Exception("DataMapper: Error in the '$context' configuration => item '$name' must be an integer value >= 0");
					}
					$config[$name] = (int) $value;
					break;

				case 'db_params':
					if ( ! is_null($value) AND $value !== FALSE AND ( ! is_string($value) OR $value == '' ) )
					{
						throw new DataMapper_Exception("DataMapper: Error in the '$context' configuration => item '$name' must be NULL, FALSE or a non-empty string");
					}
					break;

				// unknown configuration item, bail out
				default:
					throw new DataMapper_Exception("DataMapper: Invalid configuration item '$name' in the '$context' configuration");
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Returns the configuration array for a model instance
	 *
	 * If it doesn't exist, it will be setup before being returned
	 *
	 * @param	object	$object		DataMapper model instance (access by reference!)
	 *
	 * @return	array	the configuration array for the model, by reference!
	 */
	protected static function dm_configure_model(&$object)
	{
		// fetch and prep the model class name
		$model_class = strtolower(get_class($object));

		// this is to ensure that this is only done once per model
		if ( ! isset(DataMapper::$dm_model_config[$model_class]) )
		{
			// setup the model config
			DataMapper::$dm_model_config[$model_class] = array(
				'model'		=> singular($model_class),
				'table'		=> plural($model_class),
				'keys'		=> array('id' => 'integer'),
				'fields'	=> array(),
				'config'	=> DataMapper::$dm_global_config,
			);

			// create a shortcut, we're lazy...
			$config =& DataMapper::$dm_model_config[$model_class];

			// load language file, if requested and it exists
			if ( ! empty($config['config']['lang_file_format']) )
			{
				$lang_file = str_replace(
					array('${model}', '${table}'),
					array($config['model'], $config['table']),
					$config['config']['lang_file_format']
				);

				DataMapper::dm_load_lang($lang_file);
			}

			$loaded_from_cache = FALSE;

			// load in the production cache for this model, if it exists and not expired
			if ( ! empty($config['config']['cache_path']))
			{
				$cache_file = $config['config']['cache_path'] . $model_class . EXT;
				if ( file_exists($cache_file) )
				{
					if ( ! empty($config['config']['cache_expiration']) AND filemtime($cache_file) + $config['config']['cache_expiration'] > time() )
					{
						DataMapper::$dm_model_config[$model_class] = unserialize(file_get_contents($cache_file));
						$loaded_from_cache = TRUE;
					}
				}
			}

			// not cached, construct the rest of the model configuration
			if ( ! $loaded_from_cache )
			{
				// *** start DEPRECATED, REMOVE IN v2.1
				$warn = TRUE;
				foreach ( get_object_vars($object) as $name => $value)
				{
					if ( isset($config['config'][$name]) )
					{
						if ( $warn )
						{
							log_message('debug', "Datamapper: Using model object properties in '".$config['model']."' for configuration is deprecated and will be removed in the next version!");
							$warn = FALSE;
						}
						$config['config'][$name] = $value;
					}
				}
				// *** end DEPRECATED, REMOVE IN v2.1

				// merge the model config, if present
				if ( isset($object->config) AND is_array($object->config) )
				{
					foreach ( $object->config as $name => $value)
					{
						isset($config['config'][$name]) AND $config['config'][$name] = $value;
					}
				}

				// and validate it
				DataMapper::dm_validate_config($config['config'], get_class($object));

				// add any extension paths to the autoloader
				DataMapper::add_extension_path($config['config']['extensions_path']);
				unset($config['config']['extensions_path']);

				// load and initialize model extensions
				DataMapper::dm_load_extensions($config['config']['extensions'], $config['config']['extension_overload']);
				unset($config['config']['extensions']);

				// check if we have a custom model name
				if ( isset($object->model) AND is_string($object->model) AND ! empty($object->model) )
				{
					$config['model'] = $object->model;
				}

				// check if we have a custom table name
				if ( isset($object->table) AND is_string($object->table) AND ! empty($object->table) )
				{
					$config['table'] = $object->table;
				}
				// and add prefix to table
				$config['table'] = $config['config']['prefix'] . $config['table'];

				// check if we have a custom primary keys
				if ( isset($object->primary_key) AND is_array($object->primary_key) AND ! empty($object->primary_key) )
				{
					// replace the default 'id' key
					$config['keys'] = $object->primary_key;
				}

				// check if we have a default order_by
				if ( isset($object->default_order_by) AND is_array($object->default_order_by) AND ! empty($object->default_order_by) )
				{
					$config['order_by'] = $object->default_order_by;
				}
				else
				{
					$config['order_by'] = array();
				}

				// validation information for this model
				$config['validation'] = array(
					'save_rules' => array(),
					'get_rules' => array(),
					'matches' => array(),
				);

				// convert validation into associative array by field name
				$associative_validation = array();
				if ( isset($object->validation) AND is_array($object->validation) )
				{
					foreach ( $object->validation as $name => $validation )
					{
						// make sure we have a valid fieldname
						if ( is_string($name) )
						{
							$validation['field'] = $name;
						}
						else
						{
							$name = $validation['field'];
						}

						// clean up possibly missing or invalid validation fields
						if ( ! isset($validation['rules']) OR ! is_array($validation['rules']) )
						{
							$validation['rules'] = array();
						}

						// populate associative validation array
						$associative_validation[$name] = $validation;

						// clean up possibly missing or invalid validation fields
						if ( ! isset($validation['get_rules']) OR ! is_array($validation['get_rules']) )
						{
							$validation['get_rules'] = array();
						}

						if ( ! empty($validation['get_rules']) )
						{
							$config['validation']['get_rules'][$name] = $validation['get_rules'];
						}

						// check if there is a "matches" validation rule
						if ( isset($validation['rules']['matches']) )
						{
							$config['validation']['matches'][$name] = $validation['rules']['matches'];
						}
					}
				}

				// set up validations for the keys, if not present
				foreach ( $config['keys'] as $name => $value )
				{
					if ( ! isset($associative_validation[$name]) )
					{
						$associative_validation[$name] = array(
							'field' => $name,
							'rules' => array($value)
						);
						if ($value == 'integer')
						{
							if ( isset($config['validation']['get_rules'][$name]) )
							{
								! in_array('intval', $config['validation']['get_rules'][$name]) AND $config['validation']['get_rules'][$name][] = 'intval';
							}
							else
							{
								$config['validation']['get_rules'][$name] = array('intval');
							}
						}
					}
				}

				$config['validation']['save_rules'] = $associative_validation;

				// construct the relationship definitions
				foreach ( array('has_one', 'has_many', 'belongs_to') as $rel_type )
				{
					if ( ! empty($object->{$rel_type}) AND is_array($object->{$rel_type}) )
					{
						foreach ($object->{$rel_type} as $relation => $relations )
						{
							// validate the defined relation and add optional values if needed
							if ( is_string($relation) OR is_array($relations) )
							{
								if ( empty($relations['my_key']) )
								{
									$relations['my_key'] = array();
									foreach ( $config['keys'] as $key => $unused )
									{
										$relations['my_key'][] = $key;
									}
								}
								empty($relations['my_class']) AND $relations['my_class'] = $config['model'];
								empty($relations['my_table']) AND $relations['my_table'] = $config['table'];
								empty($relations['related_class']) AND $relations['related_class'] = $relation;
								if ( empty($relations['join_table']) )
								{
									$relations['join_table'] = ( $relation < $relations['my_class'] ) ? plural($relation).'_'.plural($relations['my_class']) : plural($relations['my_class']).'_'.plural($relation);
								}
								if ( $rel_type == 'belongs_to' )
								{
									$relations['related_key'] = array();
								}
								elseif ( empty($relations['related_key']) OR ! is_array($relations['related_key']) )
								{
									throw new DataMapper_Exception("DataMapper: missing 'related_key' in $rel_type relation '$relation' in model '".$config['model']."'");
								}

								// and store it
								$config['relations'][$rel_type][$relation] = $relations;
							}
							else
							{
								throw new DataMapper_Exception("DataMapper: invalid '$rel_type' relation detected in model '".$config['model']."'");
							}
						}
					}
					else
					{
						$config['relations'][$rel_type] = array();
					}
				}

				// get and store the table's field names and meta data
				$fields = $object->db->field_data($config['table']);

				// store only the field names and ensure validation list includes all fields
				foreach ($fields as $field)
				{
					// populate fields array
					$config['fields'][] = $field->name;

					// add validation if current field has none
					if ( ! isset($config['validation']['rules'][$field->name]) )
					{
						// label is set below, to prevent caching language-based labels
						$config['validation']['save_rules'][$field->name] = array('field' => $field->name, 'rules' => array());
					}
				}

				// check if all defined keys are valid fields
				foreach ( $config['keys'] as $name => $type )
				{
					if ( ! in_array($name, $config['fields']) )
					{
						throw new DataMapper_Exception("DataMapper: Key field '$name' is not a valid column for table '".$config['table']."' in model '".$config['model']."'");
					}
				}

				// write to cache if needed
				if ( ! empty($config['config']['cache_path']) AND ! empty($config['config']['cache_expiration']) )
				{
					$cache_file = $config['config']['cache_path'] . $model_class . EXT;
					file_put_contents($cache_file, serialize(DataMapper::$dm_model_config[$model_class]), LOCK_EX);
				}
			}

			// record where we got this config from
			$config['from_cache'] =  $loaded_from_cache;
		}
		else
		{
			// create a shortcut, we're lazy...
			$config =& DataMapper::$dm_model_config[$model_class];
		}

		// finally, localize the labels here (because they shouldn't be cached
		// this also sets any missing labels.
		foreach($config['validation']['save_rules'] as $field => &$val)
		{
			$val['label'] = $object->dm_lang_line($field, isset($val['label']) ? $val['label'] : FALSE, $config);
		}

		// assign the resulting config to the model object
		$object->dm_config =& DataMapper::$dm_model_config[$model_class];

		// if the model contains a post_model_init, call it now
		if ( method_exists($object, 'post_model_init') )
		{
			$object->post_model_init($config['from_cache']);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Loads the extensions defined in the array passed
	 *
	 * @param	array	$extensions		array of extensions to load
	 *
	 * @return	void
	 */
	public static function dm_is_extension_method($method)
	{
		return isset(DataMapper::$dm_extension_methods[$method]) ? $method : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Loads the extensions defined in the array passed
	 *
	 * @param	array	$extensions		array of extensions to load
	 *
	 * @return	void
	 */
	protected static function dm_load_extensions($extensions, $overload)
	{
		if ( ! empty($extensions) )
		{
			foreach ( $extensions as $extension )
			{
				// determine the extension class name
				$class = 'DataMapper_'.ucfirst($extension);

				// trigger the autoloader
				if ( class_exists($class, TRUE) )
				{
					// register the public methods of this extension class
					foreach ( get_class_methods($class) as $method )
					{
						if ( isset(DataMapper::$dm_extension_methods[$method]) )
						{
							if ( DataMapper::$dm_extension_methods[$method] != $class AND ! $overload)
							{
								throw new DataMapper_Exception("DataMapper: duplicate method '$method' detected in extension '$extension' (also defined in '".DataMapper::$dm_extension_methods[$method]."')");
							}
						}
						else
						{
							DataMapper::$dm_extension_methods[$method] = $class;
						}
					}
				}
				else
				{
					throw new DataMapper_Exception("DataMapper: defined extension '$extension' can not be found");
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * DataMapper version of $this->load->lang
	 *
	 * @param	string	$lang		name of the language file to laod
	 * @return	void
	 */
	protected static function dm_load_lang($lang)
	{
		// determine the idiom
		$default_lang = DataMapper::$CI->config->item('language');
		$idiom = ($default_lang == '' OR $default_lang == 'english') ? 'en' : $default_lang;

		// check if this language file exists, we can't catch CI's lang load errors
		$file = realpath(DataMapper::$dm_path.DS.'language'.DS.$idiom.DS.$lang.'_lang'.EXT);
		if ( $file AND is_file($file) )
		{
			DataMapper::$CI->lang->load('datamapper', $idiom);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * checks if the requested keys are all part of the fields array
	 *
	 * @param	array	$keys	array of column names
	 * @param	array	$fields	array of table field names
	 *
	 * @return	bool	TRUE if a match is found, FALSE otherwise
	 */
	protected static function dm_has_keys($keys, $fields)
	{
		$found = TRUE;

		foreach ( $keys as $key => $value )
		{
			// deal with non-associative arrays
			if ( is_numeric($key) )
			{
				$key = $value;
			}

			if ( ! in_array($key, $fields) )
			{
				$found = FALSE;
				break;
			}
		}

		return $found;
	}


	// -------------------------------------------------------------------------
	// Dynamic class definition
	// -------------------------------------------------------------------------

	/**
	 * runtime configuration for the current model instance
	 *
	 * note that this will become a reference to the global model config array!
	 *
	 * @var array
	 */
	protected $dm_config = NULL;

	/**
	 * runtime flags for the current model instance
	 *
	 * @var array
	 */
	protected $dm_flags = array(
		'validated' => FALSE,
		'valid' => FALSE,
		'where_group_started' => FALSE,
		'force_validation' => FALSE,
		'auto_transaction' => FALSE,
		'where_group_started' => FALSE,
		'group_count' => 0,
		'include_join_fields' => FALSE,
	);

	/**
	 * runtime values for the current model instance
	 *
	 * @var array
	 */
	protected $dm_values = array(
		'parent' => NULL,
		'query_related' => array(),
		'instantiations' =>NULL,
	);

	/**
	 * used to keep track of the original values from the database, to
	 * prevent unecessarily changing fields.
	 *
	 * @var object
	 */
	protected $dm_original = NULL;

	/**
	 * current object field values
	 *
	 * @var object
	 */
	protected $dm_current = NULL;

	/**
	 * used to keep track of the original values from the database, to
	 * prevent unecessarily changing fields.
	 *
	 * @var object
	 */
	protected $dm_dataset_iterator = NULL;

	/**
	 * contains the result of the last query.
	 *
	 * @var array
	 */
	public $all = array();

	/**
	 * contains any errors that occur during validation, saving, or other
	 * database access.
	 *
	 * @var object	DataMapper_Errors
	 */
	public $error = NULL;

	// -------------------------------------------------------------------------

	public function __construct($param = NULL)
	{
		// when first called, initialize datamapper itself
		if ( ! DataMapper::$dm_initialized )
		{
			// make sure CI is up to spec
			if ( version_compare(CI_VERSION, '2.0.0') < 0 )
			{
				throw new DataMapper_Exception("Datamapper: this version only works on CodeIgniter v2.0.0 and above");
			}

			// get the CodeIgniter "superobject"
			DataMapper::$CI = get_instance();

			// check if we're bootstrapped properly
			if ( get_class(DataMapper::$CI->load) != 'DM_Loader' )
			{
				throw new DataMapper_Exception("Datamapper: bootstrap is not loaded in your index.php file");
			}

			// store the path to the DataMapper installation
			DataMapper::$dm_path = __DIR__;

			// store the path to the DataMapper extension files
			DataMapper::$dm_extension_paths = array(realpath(__DIR__.DS.'..'.DS.'extensions'));

			// load the global config
			DataMapper::$CI->config->load('datamapper', TRUE, TRUE);

			// merge it with the default config
			foreach ( DataMapper::$CI->config->item('datamapper') as $name => $item)
			{
				isset(DataMapper::$dm_global_config[$name]) and DataMapper::$dm_global_config[$name] = $item;
			}

			// and validate it
			DataMapper::dm_validate_config(DataMapper::$dm_global_config, 'global');

			// load and initialize global extensions
			DataMapper::dm_load_extensions(DataMapper::$dm_global_config['extensions'], FALSE);

			// set the initialize flag, we don't want to do this twice
			DataMapper::$dm_initialized = TRUE;

			// load the DataMapper language file
			DataMapper::dm_load_lang('datamapper');

			// load inflector helper for singular and plural functions
			DataMapper::$CI->load->helper('inflector');

			// load security helper for prepping functions
			DataMapper::$CI->load->helper('security');
		}

		// else it's a model object instantiation
		else
		{
			// setup a local copy of the model config
			DataMapper::dm_configure_model($this);

			// remove no longer needed properties
			if ( isset($this->model) ) unset($this->model);
			if ( isset($this->table) ) unset($this->table);
			if ( isset($this->has_one) ) unset($this->has_one);
			if ( isset($this->has_many) ) unset($this->has_many);
			if ( isset($this->belongs_to) ) unset($this->belongs_to);
			if ( isset($this->validation) ) unset($this->validation);
			if ( isset($this->default_order_by) ) unset($this->default_order_by);

			// initialize some object properties
			$this->dm_original = $this->dm_current = new DataMapper_Datastorage();

			// was a parameter passed?
			if ( ! is_null($param) )
			{
				// could be a parent object
				if ( $param instanceOf DataMapper )
				{
					$this->dm_values['parent'] = array(
						'relation' => $param->dm_find_relationship($this),
						'object' => $param,
					);
				}

				// else assume it's a primary key value
				else
				{
					// backwards compatibility with DataMapper v1.x
					$TODO = 'fixed id key name!';
					! is_array($param) and $param = array('id' => $param );
					$this->get_where($param);
				}
			}

			// set a new error object
			$this->error = new DataMapper_Errors($this);
		}
	}

	// -------------------------------------------------------------------------
	// PHP Magic methods
	// -------------------------------------------------------------------------

	/**
	 * returns the value of the named property
	 * if named property is a related item, instantiate it first
	 *
	 * this method also instantiates the DB object if necessary
	 *
	 * @ignore
	 *
	 * @param	string	$name	name of property to look for
	 *
	 * @return	mixed
	 */
	public function __get($name)
	{
		// we dynamically get DB when needed, and create a copy.
		// this allows multiple queries to be generated at the same time.
		if ( $name == 'db' )
		{
			// get the model config from static, our local one might not be loaded yet
			$config =& DataMapper::$dm_model_config[strtolower(get_class($this))];

			// mode 1: re-use CodeIgniters existing database connection, which must be loaded before loading DataMapper
			if ( $config['config']['db_params'] === FALSE )
			{
				if ( ! isset(DataMapper::$CI->db) OR ! is_object(DataMapper::$CI->db) OR ! isset(DataMapper::$CI->db->dbdriver) )
				{
					throw new DataMapper_Exception("Datamapper: CodeIgniter database library not loaded");
				}
				$this->db =& DataMapper::$CI->db;
			}
			else
			{
				// mode 2: clone CodeIgniters existing database connection, which must be loaded before loading DataMapper
				if ( $config['config']['db_params'] === NULL OR $config['config']['db_params'] === TRUE )
				{
					if ( ! isset(DataMapper::$CI->db) OR ! is_object(DataMapper::$CI->db) OR ! isset(DataMapper::$CI->db->dbdriver) )
					{
						throw new DataMapper_Exception("Datamapper: CodeIgniter database library not loaded");
					}

					// ensure the shared DB is disconnected, even if the app exits uncleanly
					if ( ! isset(DataMapper::$CI->db->_has_shutdown_hook) )
					{
						register_shutdown_function(array(DataMapper::$CI->db, 'close'));
						DataMapper::$CI->db->_has_shutdown_hook = TRUE;
					}

					// clone, so we don't create additional connections to the DB
					// NOTE: have to do it like this, for some reason assigning the clone to $this->db fails?
					$db = clone DataMapper::$CI->db;
					$this->db =& $db;
					$this->db->dm_call_method('_reset_select');
				}

				// mode 3: make a new database connection, based on the configured database name
				else
				{
					// connecting to a different database, so we *must* create additional copies.
					// It is up to the developer to close the connection!
					$this->db = DataMapper::$CI->load->database($config['config']['db_params'], TRUE, TRUE);
				}

				// these items are shared (for debugging)
				if ( is_object(DataMapper::$CI->db) AND isset(DataMapper::$CI->db->dbdriver) )
				{
					$this->db->queries =& DataMapper::$CI->db->queries;
					$this->db->query_times =& DataMapper::$CI->db->query_times;
				}
			}

			// ensure the created DB is disconnected, even if the app exits uncleanly
			if ( ! isset($this->db->_has_shutdown_hook) )
			{
				register_shutdown_function(array($this->db, 'close'));
				$this->db->_has_shutdown_hook = TRUE;
			}

			return $this->db;
		}

		// check for requested field names
		if ( isset($this->dm_current->{$name}) )
		{
			return $this->dm_current->{$name};
		}

		// special case to load names that represent related objects
		if ( $related_object = $this->dm_find_relationship($name) )
		{
			// instantiate the related object
			$class = $related_object['related_class'];
			$this->{$name} = new $class($this);

			return $this->dm_current->{$name};
		}

		// possibly return single form of related object name
		$name_single = singular(strtolower($name));
		if ( $name_single !== $name AND isset($this->dm_current->{$name_single}) )
		{
			if ( is_object($this->dm_current->{$name_single}) )
			{
				return $this->dm_current->{$name_single};
			}
		}

		// nothing found to get
		return NULL;
	}

	// -------------------------------------------------------------------------

	/**
	 * sets the value of the named property
	 *
	 * @ignore
	 *
	 * @param	string	$name	name of property to set
	 * @param	mixed	$value	value of the property
	 *
	 * @return	mixed
	 */
	public function __set($name, $value)
	{
		$this->dm_current->{$name} = $value;
	}

	// -------------------------------------------------------------------------

	/**
	 * checks if the named property exists
	 *
	 * @ignore
	 *
	 * @param	string	$name	name of property to set
	 *
	 * @return	bool
	 */
	public function __isset($name)
	{
		return isset($this->dm_current->{$name});
	}

	// --------------------------------------------------------------------

	/**
	 * calls special methods, or extension methods.
	 *
	 * @ignore
	 *
	 * @param	string	$method	method name called
	 * @param	array	$arguments	arguments to be passed to the method
	 * @return	mixed
	 */
	public function __call($method, $arguments)
	{
		// List of watched dynamic method names
		// NOTE: order matters: make sure more specific items are listed before
		// less specific items
		static $watched_methods = array(
			'save_', 'delete_',
			'get_by_related_', 'get_by_related', 'get_by_',
			'_related_subquery', '_subquery',
			'_related_', '_related',
			'_join_field',
			'_field_func', '_func'
		);

		// are we calling an extension method?
		if ( isset(DataMapper::$dm_extension_methods[$method]) )
		{
			// add our object to the top of the argument stack
			array_unshift($arguments, $this);

			// and call the extension method
			return call_user_func_array(DataMapper::$dm_extension_methods[$method].'::'.$method, $arguments);
		}

		// check if a watched method is called
		foreach ( $watched_methods as $watched_method )
		{
			// see if called method is a watched method
			if ( strpos($method, $watched_method) !== FALSE )
			{
				// split the method name to see what we need to call
				$pieces = explode($watched_method, $method);
				if ( ! empty($pieces[0]) AND ! empty($pieces[1]) )
				{
					// watched method is in the middle
					$new_method = 'dm_' . trim($watched_method, '_');
					if ( ! method_exists($this, $new_method) )
					{
						die("Method '$new_method' does not exist. Avoiding recursive calls");
					}
					return $this->{$new_method}($pieces[0], array_merge(array($pieces[1]), $arguments));
				}
				else
				{
					// watched method is a prefix or suffix
					$new_method = 'dm_' . trim($watched_method, '_');
					if ( ! method_exists($this, $new_method) )
					{
						die("Method '$method' does not exist. Avoiding recursive calls");
					}
					return $this->{$new_method}(str_replace($watched_method, '', $method), $arguments);
					break;
				}
			}
		}

		// no mapping found, fail with the standard PHP error
		echo '<hr /';
		var_dump($method, $arguments);
		echo '<hr /';
		throw new DataMapper_Exception("Call to undefined method ".get_class($this)."::$method()");
	}

	// --------------------------------------------------------------------

	/**
	 * allows for a less shallow clone than the default PHP clone.
	 *
	 * @ignore
	 */
	public function __clone()
	{
		foreach ($this as $key => $value)
		{
			if (is_object($value) AND $key != 'db')
			{
				$this->{$key} = clone($value);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * to string
	 *
	 * converts the current object into a string
	 * should be overridden in your model to be meaningful
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return ucfirst($this->dm_config['model']);
	}


	// -------------------------------------------------------------------------
	// DataMapper public support methods
	// -------------------------------------------------------------------------

	/**
	 * returns the value of a stored flag
	 *
	 * @param	string	$flag	name of the flag requested
	 *
	 * @return	mixed	the value of the flag, or NULL if not found
	 */
	public function dm_get_flag($flag)
	{
		if ( isset($this->dm_flags[$flag]) )
		{
			return $this->dm_flags[$flag];
		}

		// unknown flag
		return NULL;
	}

	// -------------------------------------------------------------------------

	/**
	 * sets the value of a stored flag
	 *
	 * @param	string	$flag	name of the flag requested
	 * @param	mixed	$flag	name of the flag requested
	 *
	 * @return	void
	 */
	public function dm_set_flag($flag, $value)
	{
		if ( isset($this->dm_flags[$flag]) )
		{
			$this->dm_flags[$flag] = $value;
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * returns the value of a stored value
	 *
	 * @param	mixed	$value	name of the value requested
	 *
	 * @return	mixed	the value of the value, or NULL if not found
	 */
	public function dm_get_value($value)
	{
		if ( isset($this->dm_values[$value]) )
		{
			return $this->dm_values[$value];
		}

		// unknown flag
		return NULL;
	}

	// -------------------------------------------------------------------------

	/**
	 * returns the value of a stored config value
	 *
	 * @param	mixed	$name	name of the config value requested
	 * @param	mixed	$subkey	name of the subkey value requested
	 *
	 * @return	mixed	the value, or NULL if not found
	 */
	public function dm_get_config($name = NULL, $subkey = NULL)
	{
		if ( func_num_args() == 0 )
		{
			return $this->dm_config;
		}
		else
		{
			if ( isset($this->dm_config[$name]) )
			{
				if ( ! is_null($subkey) AND isset($this->dm_config[$name][$subkey]) )
				{
					return $this->dm_config[$name][$subkey];
				}
				return $this->dm_config[$name];
			}
		}

		// unknown flag
		return NULL;
	}

	// -------------------------------------------------------------------------
	// DataMapper public core methods
	// -------------------------------------------------------------------------

	/**
	 * get objects from the database.
	 *
	 * @param	integer|NULL	$limit	limit the number of results
	 * @param	integer|NULL	$offset	offset the results when limiting
	 *
	 * @return	DataMapper returns self for method chaining
	 */
	public function get($limit = NULL, $offset = NULL)
	{
		// Check if this is a related object and if so, perform a related get
		if ( ! $this->dm_handle_related() )
		{
			// invalid get request, return this for chaining.
			return $this;
		}

		// storage for the query result
		$query = FALSE;

		// check if object has been validated (skipped for related items)
		if ( $this->dm_flags['validated'] AND empty($this->dm_values['parent']) )
		{
			// reset validated flag
			$this->dm_flags['validated'] = FALSE;

			// use this objects properties
			$data = $this->dm_to_array(TRUE);

			if ( ! empty($data) )
			{
				// clear this object to make way for new data
				$this->clear();

				// set up default order by (if available)
				$this->dm_default_order_by();

				// get by objects properties
				$query = $this->db->get_where($this->dm_config['table'], $data, $limit, $offset);
			}
			else
			{
				$TODO = 'Make a decision on dealing with this or not...';
//				throw new DataMapper_Exception('DataMapper: called get() on an empty validated object');
			}
		}
		else
		{
			// clear this object to make way for new data
			$this->clear();

			// set up default order by (if available)
			$this->dm_default_order_by();

			// get by built up query
			$query = $this->db->get($this->dm_config['table'], $limit, $offset);
		}

		// convert the query result into DataMapper objects
		$query AND $this->dm_process_query($query);

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the SELECT portion of the query
	 *
	 * @param	mixed	$select 	field(s) to select, array or comma separated string
	 * @param	bool	$escape		if FALSE, don't escape this field (Probably won't work)
	 *
	 * @return	DataMapper	returns self for method chaining.
	 */
	public function select($select = '*', $escape = NULL)
	{
		if ( $escape !== FALSE )
		{
			if ( ! is_array($select) )
			{
				$select = $this->add_table_name($select);
			}
			else
			{
				$updated = array();
				foreach ( $select as $sel )
				{
					$updated[] = $this->add_table_name($sel);
				}
				$select = $updated;
			}
		}
		$this->db->select($select, $escape);

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the SELECT MAX(field) portion of a query
	 *
	 * @param	string	$select	field to look at
	 * @param	string	$alias	alias of the MAX value
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function select_max($select = '', $alias = '')
	{
		// check if this is a related object
		if ( ! empty($this->dm_values['parent']) )
		{
			$alias = ($alias != '') ? $alias : $select;
		}
		$this->db->select_max($this->add_table_name($select), $alias);

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the SELECT MIN(field) portion of a query
	 *
	 * @param	string	$select	field to look at
	 * @param	string	$alias	alias of the MIN value
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function select_min($select = '', $alias = '')
	{
		// check if this is a related object
		if ( ! empty($this->dm_values['parent']) )
		{
			$alias = ($alias != '') ? $alias : $select;
		}
		$this->db->select_min($this->add_table_name($select), $alias);

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the SELECT AVG(field) portion of a query
	 *
	 * @param	string	$select	field to look at
	 * @param	string	$alias	alias of the AVG value
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function select_avg($select = '', $alias = '')
	{
		// Check if this is a related object
		if ( ! empty($this->parent))
		{
			$alias = ($alias != '') ? $alias : $select;
		}
		$this->db->select_avg($this->add_table_name($select), $alias);

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the SELECT SUM(field) portion of a query
	 *
	 * @param	string	$select	field to look at
	 * @param	string	$alias	alias of the SUM value
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function select_sum($select = '', $alias = '')
	{
		// Check if this is a related object
		if ( ! empty($this->parent))
		{
			$alias = ($alias != '') ? $alias : $select;
		}
		$this->db->select_sum($this->add_table_name($select), $alias);

		// For method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the flag to add DISTINCT to the query
	 *
	 * @param	bool	$value	set to FALSE to turn back off DISTINCT
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function distinct($value = TRUE)
	{
		$this->db->distinct($value);

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * get items matching the where clause
	 *
	 * @param	mixed			$where	see where()
	 * @param	integer|NULL	$limit	limit the number of results
	 * @param	integer|NULL	$offset	offset the results when limiting
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function get_where($where = array(), $limit = NULL, $offset = NULL)
	{
		$this->where($where);

		return $this->get($limit, $offset);
	}

	// --------------------------------------------------------------------

	/**
	 * clears the current object
	 *
	 * @return	void
	 */
	public function clear()
	{
		// clear the all list
		$this->all = array();

		// clear errors
		$this->error->clear();

		// clear this objects properties
		foreach ($this->dm_config['fields'] as $field)
		{
			$this->dm_current->{$field} = NULL;
		}

		// clear this objects related objects
		foreach ( $this->dm_config['relations'] as $relation_type )
		{
			foreach ( $relation_type as $related => $properties )
			{
				if ( isset($this->dm_current->{$related}) )
				{
					unset($this->dm_current->{$related});
				}
			}
		}

		// clear the query related list
		$this->dm_values['query_related'] = array();

		// clear and refresh stored values
		$this->dm_original = new DataMapper_Datastorage();

		// clear the saved iterator
		$this->dm_dataset_iterator = NULL;

		$this->dm_refresh_original_values();
	}

	// --------------------------------------------------------------------

	/**
	 * runs the specified query and populates the current object with the results
	 *
	 * warning: Use at your own risk.  This will only be as reliable as your query
	 *
	 * @param	string		$sql	the query to process
	 * @param	array|bool	$binds	array of values to bind (see CodeIgniter)
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function query($sql, $binds = FALSE)
	{
		// run the custom query
		$query = $this->db->query($sql, $binds);

		// and convert it into DataMapper objects
		$this->dm_process_query($query);

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * reloads in the configuration data for a model. this is mainly
	 * used to handle language changes. all instances will see the changes
	 */
	public function reinitialize_model()
	{
		DataMapper::dm_configure_model($this);
	}

	// --------------------------------------------------------------------

	/**
	 * returns TRUE if the current object has a database record
	 *
	 * @return	bool
	 */
	public function exists()
	{
		// returns TRUE if the keys of this object is set and not empty, OR
		// there are items in the ALL array.
		$exists = TRUE;

		foreach ( $this->dm_config['keys'] as $key => $type)
		{
			if ( empty($this->dm_current->{$key}) )
			{
				$exists = FALSE;
				break;
			}
		}

		// not all keys are set, check if we have results in the all array
		! $exists AND $exists = ($this->result_count() > 0);

		return $exists;
	}

	// --------------------------------------------------------------------

	/**
	 * convenience method to return the number of items from the last call to get
	 *
	 * @return	int
	 */
	public function result_count()
	{
		if ( isset($this->dm_dataset_iterator) )
		{
			return $this->dm_dataset_iterator->result_count();
		}
		else
		{
			return count($this->all);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * removes any empty objects in this objects all list.
	 * only needs to be used if you are looping through the all list
	 * a second time and you have deleted a record the first time through.
	 *
	 * @return	bool	FALSE	if the $all array was already empty
	 */
	public function refresh_all()
	{
		if ( ! empty($this->all) )
		{
			foreach ($this->all as $key => $item)
			{
				if ( ! $item->exists() )
				{
					unset($this->all[$key]);
				}
			}
			return TRUE;
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * if TRUE, the any extra fields on the join table will be included
	 *
	 * @param	bool	$include	if FALSE, turns back off the directive
	 *
	 * @return	DataMapper	returns self for method chaining.
	 */
	public function include_join_fields($include = TRUE)
	{
		$this->dm_flags['include_join_fields'] = $include;

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * starts a query group
	 *
	 * @param	string	$not	(Internal use only)
	 * @param	string	$type	(Internal use only)
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function group_start($not = '', $type = 'AND ')
	{
		// increment group count number to make them unique
		$this->dm_flags['group_count']++;

		// in case groups are being nested
		$type = $this->dm_get_prepend_type($type);

		$this->dm_flags['where_group_started'] = TRUE;

		$prefix = (count($this->db->ar_where) == 0 AND count($this->db->ar_cache_where) == 0) ? '' : $type;

		$value =  $prefix . $not . str_repeat(' ', $this->dm_flags['group_count']) . ' (';
		$this->db->ar_where[] = $value;
		if ( $this->db->ar_caching )
		{
			$this->db->ar_cache_where[] = $value;
		}

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * starts a query group, but ORs the group
	 *
	 * @return	DataMapper	returns self for method chaining.
	 */
	public function or_group_start()
	{
		return $this->group_start('', 'OR ');
	}

	// --------------------------------------------------------------------

	/**
	 * starts a query group, but NOTs the group
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function not_group_start()
	{
		return $this->group_start('NOT ', 'OR ');
	}

	// --------------------------------------------------------------------

	/**
	 * starts a query group, but OR NOTs the group
	 *
	 * @return	DataMapper	returns self for method chaining.
	 */
	public function or_not_group_start()
	{
		return $this->group_start('NOT ', 'OR ');
	}

	// --------------------------------------------------------------------

	/**
	 * ends a query group
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function group_end()
	{
		$value = str_repeat(' ', $this->dm_flags['group_count']) . ')';
		$this->db->ar_where[] = $value;
		if ( $this->db->ar_caching )
		{
			$this->db->ar_cache_where[] = $value;
		}

		$this->dm_flags['where_group_started'] = FALSE;

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the WHERE portion of the query, separates multiple calls with AND
	 *
	 * Note: called by get_where()
	 *
	 * @param	mixed 	$key	a field or array of fields to check
	 * @param	mixed	$value	for a single field, the value to compare to
	 * @param	bool	$escape	if FALSE, the field is not escaped
	 *
	 * @return	DataMapper	returns self for method chaining.
	 */
	public function where($key, $value = NULL, $escape = TRUE)
	{
		return $this->dm_where($key, $value, 'AND ', $escape);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the WHERE portion of the query, separates multiple calls with OR
	 *
	 * @param	mixed	$key	a field or array of fields to check
	 * @param	mixed	$value	for a single field, the value to compare to
	 * @param	bool	$escape	if FALSE, the field is not escaped
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function or_where($key, $value = NULL, $escape = TRUE)
	{
		return $this->dm_where($key, $value, 'OR ', $escape);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the WHERE field BETWEEN 'value1' AND 'value2' SQL query joined with
	 * AND if appropriate
	 *
	 * @param	string	$key	a field to check
	 * @param	mixed	$value	value to start with
	 * @param	mixed	$value	value to end with
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function where_between($key = NULL, $value1 = NULL, $value2 = NULL)
	{
	 	return $this->dm_where_between($key, $value1, $value2);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the WHERE field BETWEEN 'value1' AND 'value2' SQL query joined with
	 * AND if appropriate
	 *
	 * @param	string	$key	a field to check
	 * @param	mixed	$value	value to start with
	 * @param	mixed	$value	value to end with
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function where_not_between($key = NULL, $value1 = NULL, $value2 = NULL)
	{
	 	return $this->dm_where_between($key, $value1, $value2, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the WHERE field BETWEEN 'value1' AND 'value2' SQL query joined with
	 * AND if appropriate
	 *
	 * @param	string	$key	a field to check
	 * @param	mixed	$value	value to start with
	 * @param	mixed	$value	value to end with
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function or_where_between($key = NULL, $value1 = NULL, $value2 = NULL)
	{
	 	return $this->dm_where_between($key, $value1, $value2, FALSE, 'OR ');
	}

	// --------------------------------------------------------------------

	/**
	 * sets the WHERE field BETWEEN 'value1' AND 'value2' SQL query joined with
	 * AND if appropriate
	 *
	 * @param	string	$key	A field to check
	 * @param	mixed	$value	value to start with
	 * @param	mixed	$value	value to end with
	 * @return	DataMapper	returns self for method chaining
	 */
	public function or_where_not_between($key = NULL, $value1 = NULL, $value2 = NULL)
	{
	 	return $this->dm_where_between($key, $value1, $value2, TRUE, 'OR ');
	}

	// --------------------------------------------------------------------

	/**
	 * sets the WHERE field IN ('item', 'item') SQL query joined with
	 * AND if appropriate
	 *
	 * @param	string	$key	a field to check
	 * @param	array	$values	an array of values to compare against
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function where_in($key = NULL, $values = NULL)
	{
	 	return $this->dm_where_in($key, $values);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the WHERE field IN ('item', 'item') SQL query joined with
	 * OR if appropriate
	 *
	 * @param	string	$key	a field to check
	 * @param	array	$values	an array of values to compare against
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function or_where_in($key = NULL, $values = NULL)
	{
	 	return $this->dm_where_in($key, $values, FALSE, 'OR ');
	}

	// --------------------------------------------------------------------

	/**
	 * sets the WHERE field NOT IN ('item', 'item') SQL query joined with
	 * AND if appropriate
	 *
	 * @param	string	$key	a field to check
	 * @param	array	$values	an array of values to compare against
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function where_not_in($key = NULL, $values = NULL)
	{
		return $this->dm_where_in($key, $values, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the WHERE field NOT IN ('item', 'item') SQL query joined wuth
	 * OR if appropriate
	 *
	 * @param	string	$key	a field to check
	 * @param	array	$values	an array of values to compare against
	 *
	 * @return	DataMapper	Returns self for method chaining
	 */
	public function or_where_not_in($key = NULL, $values = NULL)
	{
		return $this->dm_where_in($key, $values, TRUE, 'OR ');
	}

	// --------------------------------------------------------------------

	/**
	 * sets the %LIKE% portion of the query, separates multiple calls with AND
	 *
	 * @param	mixed	$field	a field or array of fields to check
	 * @param	mixed	$match	for a single field, the value to compare to
	 * @param	string	$side	one of 'both', 'before', or 'after'
	 *
	 * @return	DataMapper	Returns self for method chaining
	 */
	public function like($field, $match = '', $side = 'both')
	{
		return $this->dm_like($field, $match, 'AND ', $side);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the NOT LIKE portion of the query, separates multiple calls with AND
	 *
	 * @param	mixed	$field	a field or array of fields to check
	 * @param	mixed	$match	for a single field, the value to compare to
	 * @param	string	$side	one of 'both', 'before', or 'after'
	 *
	 * @return	DataMapper	Returns self for method chaining.
	 */
	public function not_like($field, $match = '', $side = 'both')
	{
		return $this->dm_like($field, $match, 'AND ', $side, 'NOT');
	}

	// --------------------------------------------------------------------

	/**
	 * sets the %LIKE% portion of the query, separates multiple calls with OR
	 *
	 * @param	mixed	$field	a field or array of fields to check
	 * @param	mixed	$match	for a single field, the value to compare to
	 * @param	string	$side	one of 'both', 'before', or 'after'
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function or_like($field, $match = '', $side = 'both')
	{
		return $this->dm_like($field, $match, 'OR ', $side);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the NOT LIKE portion of the query, separates multiple calls with OR
	 *
	 * @param	mixed	$field	a field or array of fields to check
	 * @param	mixed	$match	for a single field, the value to compare to
	 * @param	string	$side	one of 'both', 'before', or 'after'
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function or_not_like($field, $match = '', $side = 'both')
	{
		return $this->dm_like($field, $match, 'OR ', $side, 'NOT');
	}

	// --------------------------------------------------------------------

	/**
	 * sets the case-insensitive %LIKE% portion of the query
	 *
	 * @param	mixed	$field	a field or array of fields to check
	 * @param	mixed	$match	for a single field, the value to compare to
	 * @param	string	$side	one of 'both', 'before', or 'after'
	 *
	 * @return	DataMapper	returns self for method chaining.
	 */
	public function ilike($field, $match = '', $side = 'both')
	{
		return $this->dm_like($field, $match, 'AND ', $side, '', TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the case-insensitive NOT LIKE portion of the query,
	 * separates multiple calls with AND
	 *
	 * @param	mixed	$field	a field or array of fields to check
	 * @param	mixed	$match	for a single field, the value to compare to
	 * @param	string	$side	one of 'both', 'before', or 'after'
	 * @return	DataMapper	returns self for method chaining
	 */
	public function not_ilike($field, $match = '', $side = 'both')
	{
		return $this->dm_like($field, $match, 'AND ', $side, 'NOT', TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the case-insensitive %LIKE% portion of the query,
	 * separates multiple calls with OR
	 *
	 * @param	mixed	$field	a field or array of fields to check
	 * @param	mixed	$match	for a single field, the value to compare to
	 * @param	string	$side	one of 'both', 'before', or 'after'
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function or_ilike($field, $match = '', $side = 'both')
	{
		return $this->dm_like($field, $match, 'OR ', $side, '', TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the case-insensitive NOT LIKE portion of the query,
	 * separates multiple calls with OR
	 *
	 * @param	mixed	$field	a field or array of fields to check
	 * @param	mixed	$match	for a single field, the value to compare to
	 * @param	string	$side	one of 'both', 'before', or 'after'
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function or_not_ilike($field, $match = '', $side = 'both')
	{
		return $this->dm_like($field, $match, 'OR ', $side, 'NOT', TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the GROUP BY portion of the query
	 *
	 * @param	string	$by	field to group by
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function group_by($by)
	{
		$this->db->group_by($this->add_table_name($by));

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the HAVING portion of the query, separates multiple calls with AND
	 *
	 * @param	string	$key	field to compare
	 * @param	string	$value	value to compare to
	 * @param	bool	$escape	if FALSE, don't escape the value
	 * @return	DataMapper	returns self for method chaining
	 */
	public function having($key, $value = '', $escape = TRUE)
	{
		return $this->dm_having($key, $value, 'AND ', $escape);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the OR HAVING portion of the query, separates multiple calls with OR
	 *
	 * @param	string	$key	field to compare
	 * @param	string	$value	value to compare to
	 * @param	bool	$escape	if FALSE, don't escape the value
	 *
	 * @return	DataMapper	returns self for method chaining.
	 */
	public function or_having($key, $value = '', $escape = TRUE)
	{
		return $this->dm_having($key, $value, 'OR ', $escape);
	}

	// --------------------------------------------------------------------

	/**
	 * sets the LIMIT portion of the query
	 *
	 * @param	integer	$limit	limit the number of results
	 * @param	integer|NULL	$offset	offset the results when limiting
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function limit($value, $offset = '')
	{
		$this->db->limit($value, $offset);

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the OFFSET portion of the query
	 *
	 * @param	integer	$offset	offset the results when limiting
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function offset($offset)
	{
		$this->db->offset($offset);

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * starts active record caching
	 */
	public function start_cache()
	{
		$this->db->start_cache();
	}

	// --------------------------------------------------------------------

	/**
	 * stops active record caching
	 */
	public function stop_cache()
	{
		$this->db->stop_cache();
	}

	// --------------------------------------------------------------------

	/**
	 * empties the active record cache
	 */
	public function flush_cache()
	{
		$this->db->flush_cache();
	}

	// --------------------------------------------------------------------

	/**
	 * adds the table name to a field if necessary
	 *
	 * @param	string	$field	field to add the table name to
	 *
	 * @return	string	possibly modified field name
	 */
	public function add_table_name($field)
	{
		// deal with composed strings using AND or OR
		if ( preg_match('/AND|OR/', $field) )
		{
			$field_parts = explode('OR', $field);
			if ( count($field_parts) > 1 )
			{
				$field = '';
				foreach ( $field_parts as $part )
				{
					$field .= (empty($field) ? '' : ' OR ') . $this->add_table_name(trim($part));
				}
			}
			$field_parts = explode('AND', $field);
			if ( count($field_parts) > 1 )
			{
				$field = '';
				foreach ( $field_parts as $part )
				{
					$field .= (empty($field) ? '' : ' AND ') . $this->add_table_name(trim($part));
				}
			}
		}

		// only add table if the field doesn't contain a dot (.) or open parentheses
		if ( preg_match('/[\.\(]/', $field) == 0 )
		{
			// split string into parts, add field
			$field_parts = explode(',', $field);
			$field = '';
			foreach ( $field_parts as $part )
			{
				! empty($field) AND $field .= ', ';
				$part = ltrim($part);

				// handle comparison operators on where
				$subparts = explode(' ', $part, 2);
				if ( $subparts[0] == '*' OR in_array($subparts[0], $this->dm_config['fields']) )
				{
					$field .= $this->db->protect_identifiers($this->dm_config['table']  . '.' . $part);
				}
				else
				{
					$field .= $part;
				}
			}
		}

		return $field;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the ORDER BY portion of the query.
	 *
	 * @param	string	$orderby	field to order by
	 * @param	string	$direction	one of 'ASC' or 'DESC'  Defaults to 'ASC'
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	public function order_by($orderby, $direction = '')
	{
		$this->db->order_by($this->add_table_name($orderby), $direction);

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * renders the last DB query performed
	 *
	 * @param	array	$delims				delimiters for the SQL string
	 * @param	bool	$return_as_string	if TRUE, don't output automatically
	 *
	 * @return	string	last db query formatted as a string
	 */
	public function check_last_query($delims = array('<pre>', '</pre>'), $return_as_string = FALSE)
	{
		$q = wordwrap($this->db->last_query(), 100, "\n\t");
		if ( ! empty($delims) )
		{
			$q = implode($q, $delims);
		}
		if ( $return_as_string === FALSE )
		{
			echo $q;
		}
		return $q;
	}

	// --------------------------------------------------------------------

	/**
	 * returns a clone of the current object
	 *
	 * @return	DataMapper	cloned copy of this object
	 */
	public function get_clone($force_db = FALSE)
	{
		$temp = clone($this);

		// This must be left in place, even with the __clone method,
		// or else the DB will not be copied over correctly.
		if ( $force_db OR (($this->dm_config['config']['db_params'] !== FALSE) AND isset($this->db)) )
		{
			// create a copy of $this->db
			$temp->db = clone($this->db);
		}

		return $temp;
	}

	// --------------------------------------------------------------------

	/**
	 * returns an unsaved copy of the current object
	 *
	 * @return	DataMapper	cloned copy of this object with an empty ID for saving as new
	 */
	public function get_copy($force_db = FALSE)
	{
		// get a clone of this object
		$copy = $this->get_clone($force_db);

		// reset the keys to make it new
		foreach ( $dm_config['keys'] as $key => $type )
		{
			$copy->dm_current->{$key} = NULL;
		}

		return $copy;
	}

	// --------------------------------------------------------------------

	/**
	 * converts a query result into an array of objects.
	 * also updates this object
	 *
	 * @param	CI_DB_result	$query
	 */
	public function dm_process_query($query)
	{
		if ( $query->num_rows() > 0 )
		{
			// reset the all array
			$this->all = array();

			// determine what to use for the all array index
			if ( count($this->dm_config['keys']) == 1 AND $this->dm_config['config']['all_array_uses_keys'] )
			{
				$indextype = key($this->dm_config['keys']);
			}
			else
			{
				$indextype = FALSE;
			}
			$index = 0;

			// flag to detect the first record in the result
			$first = TRUE;

			// fetch the current model class name
			$model = get_class($this);

			// loop through the results
			foreach ( $query->result() as $row )
			{
				// store the values of the record in the model object
				if ( $first )
				{
					// re-use the current object
					$this->dm_to_object($this, $row);
					$item =& $this;
					$first = FALSE;
				}
				else
				{
					// create the new model object
					$item = new $model();
					$this->dm_to_object($item, $row);
				}

				// and store it in the all array
				if ( $indextype )
				{
					$this->all[$this->{$indextype}] = $item;
				}
				else
				{
					$this->all[$index++] = $item;
				}
			}

			// remove instantiations
			$this->dm_values['instantiations'] = NULL;

			// free large queries
			if ( $query->num_rows() > $this->dm_config['config']['free_result_threshold'] )
			{
				$query->free_result();
			}
		}
		else
		{
			// no results, reset the object data storage
			$this->dm_refresh_original_values();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * copies the values from a query result row to an object
	 * also initializes that object by running get rules, and
	 *   refreshing stored values on the object.
	 *
	 * finally, if any "instantiations" are requested, those related objects
	 *   are created off of query results
	 *
	 * this is only public so that the iterator can access it.
	 *
	 * @ignore
	 *
	 * @param	DataMapper	$item	item to configure
	 * @param	object		$row	query results row
	 */
	public function dm_to_object($item, $row)
	{
		// populate this object with values from first record
		foreach ( $row as $key => $value )
		{
			// make sure the field name is stored in lower case!
			$item->dm_current->{$key} = $value;
		}

		// make sure any columns not part of the result are set
		foreach ( $this->dm_config['fields'] as $field )
		{
			if ( ! isset($item->dm_current->{$field}) )
			{
				$item->dm_current->{$field} = NULL;
			}
		}

		if ( ! empty($this->dm_config['validation']['get_rules']) )
		{
			$item->run_get_rules();
		}

		$item->dm_refresh_original_values();

		if ( $this->dm_values['instantiations'] )
		{
			foreach ( $this->dm_values['instantiations'] as $related_field => $field_map )
			{
				// convert fields to a 'row' object
				$row = new stdClass();
				foreach ( $field_map as $item_field => $c_field )
				{
					$row->{$c_field} = $item->{$item_field};
				}
				// get the related item
				$c =& $item->dm_get_without_auto_populating($related_field);
				// set the values
				$c->dm_to_object($c, $row);

				// also set up the ->all array
				$c->all = array();
				$c->all[0] = $c->get_clone();
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * returns the SQL string of the current query (SELECTs ONLY)
	 *
	 * note that this also _clears_ the current query info!
	 *
	 * This can be used to generate subqueries.
	 *
	 * @param	integer|NULL	$limit	limit the number of results
	 * @param	integer|NULL	$offset	offset the results when limiting
	 *
	 * @return	string	SQL as a string
	 */
	public function get_sql($limit = NULL, $offset = NULL, $handle_related = FALSE)
	{
		if ( $handle_related )
		{
die($TODO = 'get_sql(): handle related queries');
		}

		$this->db->dm_call_method('_track_aliases', $this->dm_config['table']);
		$this->db->from($this->dm_config['table']);

		$this->dm_default_order_by();

		if ( ! is_null($limit) )
		{
			$this->limit($limit, $offset);
		}

		$sql = $this->db->dm_call_method('_compile_select');

		$this->dm_clear_after_query();

		return $sql;
	}

	// --------------------------------------------------------------------

	/**
	 * runs the query, but returns the raw CodeIgniter results
	 *
	 * note that this also _clears_ the current query info!
	 *
	 * @param	integer|NULL	$limit	limit the number of results
	 * @param	integer|NULL	$offset	offset the results when limiting
	 *
	 * @return	CI_DB_result	result object
	 */
	public function get_raw($limit = NULL, $offset = NULL, $handle_related = TRUE)
	{
		if ( $handle_related )
		{
die($TODO = 'get_raw(): handle related queries');
		}

		$this->dm_default_order_by();

		$query = $this->db->get($this->dm_config['table'], $limit, $offset);
		$this->dm_clear_after_query();

		return $query;
	}

	// -------------------------------------------------------------------------
	// DataMapper public helper methods
	// -------------------------------------------------------------------------

	/**
	 * DataMapper version of $this->lang->line
	 *
	 * @ignore	public because it needs to be accessable by extensions
	 *
	 * @param	string	$line		name of the language string to lookup
	 * @param	string	$value		preset value
	 *
	 * @return	string	result of the lookup
	 */
	public function dm_lang_line($line, $value = FALSE, $config = NULL)
	{
		if ( strpos($value, 'lang:') === 0 )
		{
			$line = substr($value, 5);
			$value = FALSE;
		}

		if ( $value === FALSE )
		{
			is_null($config) AND $config =& $this->dm_config;
			if ( ! empty($config['config']['field_label_lang_format']) )
			{
				$s = array('${model}', '${table}');
				$r = array($config['model'], $config['table']);
				if ( ! is_null($line) )
				{
					$s[] = '${field}';
					$r[] = $line;
				}
				$key = str_replace($s, $r, $config['config']['field_label_lang_format']);
				$value = DataMapper::$CI->lang->dm_line($key);

				if ( $value === FALSE )
				{
					$value = $line;
				}
			}
		}

		return $value;
	}

	// --------------------------------------------------------------------

	/**
	 * used several places to temporarily override the auto_populate setting
	 *
	 * @ignore
	 *
	 * @param	string	$relation		name of the related object
	 *
	 * @return 	array|NULL
	 */
	public function dm_find_relationship($relation)
	{
		if ( $relation instanceOf DataMapper )
		{
			$relation = strtolower(get_class($relation));
		}

		foreach ( $this->dm_config['relations'] as $type => $definitions )
		{
			foreach ( $definitions as $name => $definition )
			{
				if ( $name == $relation )
				{
					$definition['type'] = $type;
					return $definition;
				}
			}
		}

		// not a valid relationship for this object
		return FALSE;
	}

	// -------------------------------------------------------------------------
	// DataMapper protected methods
	// -------------------------------------------------------------------------

	// -------------------------------------------------------------------------

	/**
	 * converts this objects current record into an array for database queries
	 * If validate is TRUE (getting by objects properties) empty objects are ignored.
	 *
	 * @ignore
	 *
	 * @param	bool	$validate
	 *
	 * @return	array
	 */
	protected function dm_to_array($validate = FALSE)
	{
		$data = array();

		foreach ($this->dm_config['fields'] as $field)
		{
			if ( $validate AND ! isset($this->dm_current->{$field}) )
			{
				continue;
			}

			$data[$field] = $this->dm_current->{$field};
		}

		return $data;
	}

	// -------------------------------------------------------------------------

	/**
	 * refreshes the orginal object values with the current values
	 *
	 * @ignore
	 */
	protected function dm_refresh_original_values()
	{
		// update stored values
		foreach ($this->dm_config['fields'] as $field)
		{
			$this->dm_original->{$field} = $this->dm_current->{$field};
		}

		// If there is a "matches" validation rule, match the field value with the other field value
		foreach ($this->dm_config['validation']['matches'] as $field_name => $match_name)
		{
			$this->dm_current->{$field_name} = $this->dm_original->{$field_name} = $this->{$match_name};
		}
	}

	// --------------------------------------------------------------------

	/**
	 * adds in the defaut order_by items, if there are any, and
	 * order_by hasn't been overridden.
	 *
	 * @ignore
	 */
	protected function dm_default_order_by()
	{
		if ( ! empty($this->dm_config['order_by']) )
		{
			$sel = $this->dm_config['table'] . '.' . '*';
			$sel_protect = $this->db->protect_identifiers($sel);

			// only add the items if there isn't an existing order_by,
			// AND the select statement is empty or includes * or table.* or `table`.*
			if ( empty($this->db->ar_orderby) AND
				(
					empty($this->db->ar_select) OR
					in_array('*', $this->db->ar_select) OR
					in_array($sel_protect, $this->db->ar_select) OR
					in_array($sel, $this->db->ar_select)

				))
			{
				foreach($this->dm_config['order_by'] as $k => $v) {
					if ( is_int($k) )
					{
						$k = $v;
						$v = '';
					}
					$k = $this->add_table_name($k);
					$this->order_by($k, $v);
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * protected function to convert the AND or OR prefix to '' when starting
	 * a group.
	 *
	 * @ignore
	 *
	 * @param	object	$type	Current type value
	 *
	 * @return	New	type value
	 */
	protected function dm_get_prepend_type($type)
	{
		if ( $this->dm_flags['where_group_started'] )
		{
			$type = '';
			$this->dm_flags['where_group_started'] = FALSE;
		}

		return $type;
	}

	// --------------------------------------------------------------------

	/**
	 * Note: called by where() and or_where().
	 *
	 * @ignore
	 *
	 * @param	mixed	$key	a field or array of fields to check
	 * @param	mixed	$value	for a single field, the value to compare to
	 * @param	string	$type	type of addition (AND or OR)
	 * @param	bool	$escape	if FALSE, the field is not escaped
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	protected function dm_where($key, $value = NULL, $type = 'AND ', $escape = NULL)
	{
		// make sure we've got a key->value pair
		if ( ! is_array($key) )
		{
			$key = array($key => $value);
		}

		foreach ( $key as $k => $v )
		{
			$new_k = $this->add_table_name($k);
			$this->db->dm_call_method('_where', $new_k, $v, $this->dm_get_prepend_type($type), $escape);
		}

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the HAVING portion of the query, separates multiple calls with AND
	 *
	 * @ignore
	 *
	 * @param	string	$key	field to compare
	 * @param	string	$value	value to compare to
	 * @param	string	$type	type of connection (AND or OR)
	 * @param	bool	$escape	if FALSE, don't escape the value
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	protected function dm_having($key, $value = '', $type = 'AND ', $escape = TRUE)
	{
		$this->db->dm_call_method('_having', $this->add_table_name($key), $value, $type, $escape);

		// For method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * NOTE: this does NOT use the built-in ActiveRecord LIKE function
	 *
	 * @ignore
	 *
	 * @param	mixed	$field		a field or array of fields to check
	 * @param	mixed	$match		for a single field, the value to compare to
	 * @param	string	$type		the type of connection (AND or OR)
	 * @param	string	$side		one of 'both', 'before', or 'after'
	 * @param	string	$not		'NOT' or ''
	 * @param	bool	$no_case	if TRUE, configure to ignore case
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	protected function dm_like($field, $match = '', $type = 'AND ', $side = 'both', $not = '', $no_case = FALSE)
	{
		// make sure we have a key->value field
		if ( ! is_array($field) )
		{
			$field = array($field => $match);
		}

		foreach ( $field as $k => $v )
		{
			$new_k = $this->add_table_name($k);
			if ( $new_k != $k )
			{
				$field[$new_k] = $v;
				unset($field[$k]);
			}
		}

		// Taken from CodeIgniter's Active Record because (for some reason)
		// it is stored separately that normal where statements.

		foreach ( $field as $k => $v )
		{
			if ( $no_case )
			{
				$k = 'UPPER(' . $this->db->protect_identifiers($k) .')';
				$v = strtoupper($v);
			}
			$f = "$k $not LIKE ";

			if ( $side == 'before' )
			{
				$m = "%{$v}";
			}
			elseif ( $side == 'after' )
			{
				$m = "{$v}%";
			}
			else
			{
				$m = "%{$v}%";
			}

			$this->dm_where($f, $m, $type, TRUE);
		}

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * called by where_between(), or_where_between(), where_not_between(), or or_where_not_between().
	 *
	 * @ignore
	 *
	 * @param	string	$key	a field to check
	 * @param	mixed	$value	value to start with
	 * @param	mixed	$value	value to end with
	 * @param	bool	$not	if TRUE, use NOT IN instead of IN
	 * @param	string	$type	the type of connection (AND or OR)
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	protected function dm_where_between($key = NULL, $value1 = NULL, $value2 = NULL, $not = FALSE, $type = 'AND ')
	{
		$type = $this->dm_get_prepend_type($type);

	 	$this->db->dm_call_method('_where', "`$key` ".($not?"NOT ":"")."BETWEEN ".$value1." AND ".$value2, NULL, $type, NULL);

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * called by where_in(), or_where_in(), where_not_in(), or or_where_not_in()
	 *
	 * @ignore
	 *
	 * @param	string	$key	A field to check
	 * @param	array	$values	An array of values to compare against
	 * @param	bool	$not	If TRUE, use NOT IN instead of IN
	 * @param	string	$type	The type of connection (AND or OR)
	 *
	 * @return	DataMapper	returns self for method chaining
	 */
	protected function dm_where_in($key = NULL, $values = NULL, $not = FALSE, $type = 'AND ')
	{
		$type = $this->dm_get_prepend_type($type);

		if ($values instanceOf DataMapper)
		{
			$arr = array();
			foreach ($values as $value)
			{
die($TODO = 'deal with the new keys structure');
				$arr[] = $value->id;
			}
			$values = $arr;
		}

	 	$this->db->dm_call_method('_where_in', $this->add_table_name($key), $values, $not, $type);

		// for method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * clears the db object after processing a query, or returning the
	 * SQL for a query
	 *
	 * @ignore
	 */
	protected function dm_clear_after_query()
	{
		// clear the query as if it was run
		$this->db->dm_call_method('_reset_select');

		// in case some include_related instantiations were set up, clear them
		$this->dm_values['instantiations'] = NULL;

		// Clear the query related list (Thanks to TheJim)
		$this->dm_values['query_related'] = array();

		// Clear the saved iterator
		unset($this->dm_dataset_iterator);
	}

	// --------------------------------------------------------------------

	/**
	 * used several places to temporarily override the auto_populate setting
	 *
	 * @ignore
	 *
	 * @param	string	$related	related name
	 *
	 * @return 	DataMapper|NULL
	 */
	protected function &dm_get_without_auto_populating($related)
	{
		// save the current settings
		$b_many = $this->dm_config['config']['auto_populate_has_many'];
		$b_one = $this->dm_config['config']['auto_populate_has_one'];

		// disable auto population
		$this->dm_config['config']['auto_populate_has_one'] = FALSE;
		$this->dm_config['config']['auto_populate_has_many'] = FALSE;

		// fetch the related object
		$ret =& $this->{$related};

		// and reset the autopopulate settings
		$this->dm_config['config']['auto_populate_has_many'] = $b_many;
		$this->dm_config['config']['auto_populate_has_one'] = $b_one;

		return $ret;
	}

	// --------------------------------------------------------------------

	/**
	 * Handles the adding the related part of a query if $parent is set
	 *
	 * @ignore
	 *
	 * @return	bool	success or failure
	 */
	protected function dm_handle_related()
	{
		// if no parent is present, there's nothing to relate
		if ( ! empty($this->dm_values['parent']) )
		{
			// determine the keys and key values
			$keys = array();

			// only add a where clause if we have a valid parent
			if ( $this->dm_values['parent']['object']->exists() )
			{
				foreach ( $this->dm_values['parent']['relation']['my_key'] as $key )
				{
					$keys[$key] = $this->dm_values['parent']['object']->{$key};
				}
			}

			// to ensure result integrity, group all previous queries
			if ( ! empty($this->db->ar_where) )
			{
				array_unshift($this->db->ar_where, '( ');
				$this->db->ar_where[] = ' )';
			}

			// add the related table selection to the query
			$this->where_related($this->dm_values['parent']['relation']['my_class'], $keys);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the specified related query
	 *
	 * @ignore
	 *
	 * @param	string	$query		query string
	 * @param	array	$arguments	arguments to process
	 * @param	mixed	$extra		used to prevent escaping in special circumstances
	 *
	 * @return	DataMapper			returns self for method chaining
	 */
	protected function dm_related($query, $arguments = array(), $extra = NULL)
	{
		if ( ! empty($query) && ! empty($arguments) )
		{
			// related by object
			if ( $arguments[0] instanceOf DataMapper )
			{
				die($TODO = 'dm_related() related query based on an object');
			}

			// related by deep relationship
			elseif ( strpos($arguments[0], '/') !== FALSE )
			{
				die($TODO = 'dm_related() related query based on an deep relation');
			}

			// normal relationship
			else
			{
				// find out what the relation is
				$related_field = array_shift($arguments);
				if ( ! $relation = $this->dm_find_relationship($related_field) )
				{
					throw new DataMapper_Exception("DataMapper: Unable to relate {$this->dm_config['model']} with '$related_field'.");
				}
				$class = $relation['related_class'];

				// no selection arguments present
				if ( empty($arguments) )
				{
					$selection = array();
					$object = new $class();
				}

				// selection is already an array
				elseif ( is_array($arguments[0]) )
				{
					$selection = array_shift($arguments);
					$object = new $class();
				}

				// selection is another object
				elseif ( $arguments[0] instanceOf DataMapper )
				{
					$object = array_shift($arguments);
					die($TODO = 'related query based on a passed object');
				}
				else
				{
					$object = new $class();
					die($TODO = 'related query based on a passed field/value pair');
				}
			}

			// get the relationship definition seen from the related model
			$other_relation = $object->dm_find_relationship($this->dm_config['model']);

			$TODO = 'prevent un-needed joins when selecting on related keys only';

			// add the join to the query
			$this->dm_add_relation($relation, $other_relation);

			// allow special arguments to be passed into query methods
			if ( is_null($extra) )
			{
				isset($arguments[0]) AND $extra = $arguments[0];
			}

			// prefix the keys with the related table name
			$keys = array();
			foreach ( $selection as $name => $value )
			{
				$keys[$other_relation['my_table'].'.'.$name] = $value;
			}

			// add the selection to the query
			if ( is_null($extra) )
			{
				$this->{$query}($keys);
			}
			else
			{
				$this->{$query}($keys, NULL, $extra);
			}
		}

		// For method chaining
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * sets the specified related query
	 *
	 * @ignore
	 *
	 * @param	array	$modela		relation definition for table A, the current model
	 * @param	array	$modelb		relation definition for table B, the joined model
	 * @param	bool	$join_only	if true, only join the join table on a many-to-many
	 *
	 * @return	void
	 */
	protected function dm_add_relation(Array $modela, Array $modelb, $join_only = FALSE)
	{
		// force the selection of the current object's columns
		if (empty($this->db->ar_select))
		{
			$this->db->select($this->dm_config['table'] . '.*');
		}

		// many-to-many relationship
		if ( $modela['type'] == 'has_many' AND $modelb['type'] == 'has_many' )
		{
			// make sure we share the same join table
			if ( $modela['join_table'] != $modela['join_table'] )
			{
				throw new DataMapper_Exception("DataMapper: '".$modela['related_class']."' and '".$modelb['my_class']."' must define the same join table");
			}

			$alias1 = $modela['join_table'];
			if ( ! in_array($alias1, $this->dm_values['query_related']) )
			{
				// build the join condition
				$cond = '';
				for ( $i = 0; $i < count($modela['my_key']); $i++ )
				{
					$cond .= ( empty($cond) ? '' : ' AND ' ) . $alias1.'.'.$modela['related_key'][$i];
					$cond .= ' = ' . $modela['my_table'].'.'.$modela['my_key'][$i];
				}

				// join modela to the join table
				$this->db->join($modela['join_table'].' '.$this->db->protect_identifiers($alias1), $cond, 'LEFT OUTER');
			}

			$alias2 = $modelb['my_table'];
			if ( ! in_array($alias2, $this->dm_values['query_related']) )
			{
				// join modelb to the join table
				if ( $join_only === FALSE )
				{
					// build the join condition
					$cond = '';
					for ( $i = 0; $i < count($modelb['my_key']); $i++ )
					{
						$cond .= ( empty($cond) ? '' : ' AND ' ) . $alias1.'.'.$modelb['related_key'][$i];
						$cond .= ' = ' . $alias2.'.'.$modelb['my_key'][$i];
					}

					// join modela to the join table
					$this->db->join($modelb['my_table'].' '.$this->db->protect_identifiers($alias2), $cond, 'LEFT OUTER');
				}
			}
		}

		// many-to-one relationship
		elseif ( $modela['type'] == 'has_many' AND $modelb['type'] == 'belongs_to' )
		{
			die('many-to-one');
		}

		// one-to-many relationship
		elseif ( $modela['type'] == 'belongs_to' AND $modelb['type'] == 'has_many' )
		{
			die('one-to-many');
		}

		// one-to-one relationship
		elseif ( $modela['type'] == 'has_one' AND $modelb['type'] == 'belongs_to' )
		{
			die('one-to-one');
		}

		// one-to-one relationship
		elseif ( $modela['type'] == 'belongs_to' AND $modelb['type'] == 'has_one' )
		{
			die('one-to-one');
		}

		// incompatible combination
		else
		{
			throw new DataMapper_Exception("DataMapper: incompatible relation detected between '".$modela['related_class']."[".$modela['type']."]' and '".$modelb['my_class']."[".$modelb['type']."]'");
		}

	}

	// -------------------------------------------------------------------------
	// IteratorAggregate methods
	// -------------------------------------------------------------------------

	/**
	 * returns a streamable result set for large queries
	 *
	 * Usage:
	 * $rs = $object->get_iterated();
	 * $size = $rs->count;
	 * foreach($rs as $o) {
	 *	 // handle $o
	 * }
	 * $rs can be looped through more than once.
	 *
	 * @param	integer|NULL	$limit	limit the number of results
	 * @param	integer|NULL	$offset	offset the results when limiting
	 *
	 * @return	DataMapper	returns self for method chaining.
	 */
	public function get_iterated($limit = NULL, $offset = NULL)
	{
		// clone $this, so we keep track of instantiations, etc.
		// because these are cleared after the call to get_raw
		$object = $this->get_clone();

		// need to clear query from the clone
		$object->db->dm_call_method('_reset_select');

		// clear the query related list from the clone
		$object->dm_values['query_related'] = array();

		// construct the iterator object
		$this->dm_dataset_iterator = new DataMapper_DatasetIterator($object, $this->get_raw($limit, $offset, TRUE));

		// for method chaining
		return $this;
	}

	// -------------------------------------------------------------------------

	/**
	 * allows the all array to be iterated over without having to specify it
	 *
	 * @return	Iterator	An iterator for the all array
	 */
	public function getIterator()
	{
		// do we have an iterator object defined?
		if ( $this->dm_dataset_iterator instanceOf DataMapper_DatasetIterator )
		{
			return $this->dm_dataset_iterator;
		}
		else
		{
			return new ArrayIterator($this->all);
		}
	}
}

// -------------------------------------------------------------------------
// Register the DataMapper autoloader
// -------------------------------------------------------------------------

/**
 * Autoloads object classes that are used with DataMapper.
 * Must be at end due to "implements IteratorAggregate"...
 */
spl_autoload_register('DataMapper::dm_autoload');

/* End of file datamapper.php */
/* Location: ./application/third_party/datamapper/libraries/datamapper.php */
