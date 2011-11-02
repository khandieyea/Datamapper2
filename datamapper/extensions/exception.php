<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * DataMapper exception handler
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0
 */

class DataMapper_Exception extends Exception
{
	public function __construct($message = null, $code = 0, Exception $previous = null)
	{
		if ( DATAMAPPER_EXCEPTIONS )
		{
			parent::__construct($message, $code, $previous);
		}
		else
		{
			show_error($message);
			die();
		}
	}
}

/* End of file exception.php */
/* Location: ./application/third_party/datamapper/datamapper/exception.php */
