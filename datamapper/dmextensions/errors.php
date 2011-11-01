<?php

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
 * @version 	2.0.0-dev
 */

class DataMapper_Errors {
	/**
	 * Array of all error messages.
	 * @var array
	 */
	public $all = array();

	/**
	 * String containing entire error message.
	 * @var string
	 */
	public $string = '';

	/**
	 * All unset fields are returned as empty strings by default.
	 * @ignore
	 * @param string $field
	 * @return string Empty string
	 */
	public function __get($field) {
		return '';
	}
}

/* End of file errors.php */
/* Location: ./application/third_party/datamapper/datamapper/errors.php */
