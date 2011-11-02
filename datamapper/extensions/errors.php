<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * DataMapper error object
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0
 */

class DataMapper_Errors {

	/**
	 * array of all error messages
	 *
	 * @var	array
	 */
	public $all = array();

	/**
	 * string containing entire error message
	 *
	 * @var	string
	 */
	public $string = '';

	/**
	 * error message prefix
	 *
	 * @var	string
	 */
	protected $dm_prefix = '';

	/**
	 * error message suffix
	 *
	 * @var	string
	 */
	protected $dm_suffix = '';

	// --------------------------------------------------------------------

	/**
	 *
	 * @ignore
	 *
	 * @param	DataMapper	$object	DataMapper parent object
	 *
	 * @return void
	 */
	public function __construct(DataMapper $object)
	{
		// get the error prefix and suffix from the config
		$this->dm_prefix = $object->dm_get_config('config', 'error_prefix');
		$this->dm_suffix = $object->dm_get_config('config', 'error_suffix');
	}

	// --------------------------------------------------------------------

	/**
	 * all unset fields are returned as empty strings by default
	 *
	 * @ignore
	 *
	 * @param	string	$property	the undefined property name
	 *
	 * @return	string	empty string
	 */
	public function __get($property)
	{
		return '';
	}

	// --------------------------------------------------------------------

	/**
	 * return the string with all errors
	 *
	 * @ignore
	 *
	 * @return	string	error string
	 */
	public function __toString()
	{
		return $this->string;
	}

	// --------------------------------------------------------------------
	/**
	 * resets the error object
	 *
	 * @ignore
	 *
	 * @return	void
	 */
	public function clear()
	{
		foreach ( get_object_vars($this) as $name => $value )
		{
			switch ($name)
			{
				// do not clear these
				case 'dm_prefix':
				case 'dm_suffix':
					break;

				// set to empty string
				case 'string':
					$this->{$name} = '';
					break;

				// set to empty array
				case 'all':
					$this->{$name} = array();
					break;

				// else remove them
				default:
					unset($this->{$name});
			}
		}
	}

	// --------------------------------------------------------------------
	/**
	 * adds an error message to this objects error object
	 *
	 * @param	string	$field	field to set the error on
	 * @param	string	$error	error message
	 */
	public function message($field, $error)
	{
		if ( ! empty($field) AND ! empty($error) )
		{
			// set field specific error and add the field error to errors all list
			$this->{$field} = $this->all[$field] = $this->dm_prefix . $error . $this->dm_suffix;

			// append field error to error message string
			$this->string .= $this->error->{$field};
		}
	}

}

/* End of file errors.php */
/* Location: ./application/third_party/datamapper/datamapper/errors.php */
