<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * Storage object for DataMapper results
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0
 */

class DataMapper_Datastorage
{
	/*
	 * magic getter to make sure we access all properties in lower case
	 */
	public function __get($name)
	{
		$name = strtolower($name);
		return property_exists($this, $name) ? $this->{$name} : NULL;
	}

	/*
	 * magic setter to make sure all properties are created in lower case
	 */
	public function __set($name, $value)
	{
		$this->{strtolower($name)} = $value;
	}

	/*
	 * magic isset to make sure we access all properties in lower case
	 */
	public function __isset($name)
	{
		return property_exists($this, strtolower($name));
	}

	/*
	 * magic unsetter to deal with the now famous case issue
	 */
	public function __unset($name)
	{
		unset($this->{strtolower($name)});
	}

}

/* End of file datastorage.php */
/* Location: ./application/third_party/datamapper/datamapper/datastorage.php */
