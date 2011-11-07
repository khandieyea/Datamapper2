<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * DataMapper extension - simplecache methods
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0
 */

class DataMapper_Simplecache
{
	/**
	 * allows CodeIgniter's caching method to cache large result sets
	 * call it exactly as get();
	 *
	 * @param	DataMapper	$dmobject	the DataMapper object
	 *
	 * @return	DataMapper	the DataMapper object for chaining
	 */
	public static function get_cached($dmobject)
	{
		if ( $dmobject->dm_get_flag('should_delete_cache') === TRUE )
		{
			$dmobject->db->cache_delete();
			$dmobject->dm_set_flag('should_delete_cache', FALSE);
		}

		// enable DB caching
		$dmobject->db->cache_on();

		// get the arguments, and pop the object
		$args = func_get_args();
		array_shift($args);

		// call the get() method
		call_user_func_array(array($dmobject, 'get'), $args);

		// disable DB caching
        $dmobject->db->cache_off();

		// return for chaining
		return $dmobject;
	}

	// -------------------------------------------------------------------------

	/**
	 * clears the cached query the next time get_cached is called
	 *
	 * @param	DataMapper	$object	the DataMapper object
	 *
	 * @return	DataMapper	the DataMapper $object for chaining
	 */
	public static function clear_cache($dmobject)
	{
		// get the arguments, and pop the object
		$args = func_get_args();
		array_shift($args);


		if ( ! empty($args) )
		{
			call_user_func_array(array($dmobject->db, 'cache_delete'), $args);
		}
		else
		{
			$dmobject->dm_set_flag('should_delete_cache', TRUE);
		}

		// return for chaining
		return $dmobject;
    }

}

/* End of file simplecache.php */
/* Location: ./application/third_party/datamapper/extensions/simplecache.php */
