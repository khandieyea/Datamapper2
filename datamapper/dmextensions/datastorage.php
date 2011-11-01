<?php

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
 * @version 	2.0.0-dev
 */

class DataMapper_Datastorage
{
	/*
	 * magic setter to make sure all properties are created in lower case
	 */
	public function __set($name, $value)
	{
		$this->{strtolower($name)} = $value;
	}
}


/* End of file datastorage.php */
/* Location: ./application/third_party/datamapper/datamapper/datastorage.php */
