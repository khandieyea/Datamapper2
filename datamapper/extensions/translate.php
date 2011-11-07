<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * DataMapper extension - translate methods
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0
 */

class DataMapper_Translate
{
	/**
	 * do language translations of the field list
	 *
	 * @param	DataMapper	$dmobject	the DataMapper Object to convert
	 * @param	array		$fields		array of fields to include.  if empty, includes all database columns
	 *
	 * @return	object	the Datamapper object
	 */
	public static function translate( $dmobject, $fields = array() )
	{
		// make sure $fields is an array
		$fields = (array) $fields;

		// determine the correct field set
		empty($fields) AND $fields = $dmobject->dm_get_config('fields');

		// loop through the fields
		foreach ( $fields as $f )
		{
			// loop through the all array
			foreach ( $dmobject->all as $key => $all_object )
			{
				if ( isset($all_object->{$f}) )
				{
					( $line = $dmobject->dm_lang_line($all_object->{$f}) ) AND $dmobject->all[$key]->{$f} = $line;
				}
			}
		}

		// return the Datamapper object
		return $dmobject;
	}

}

/* End of file translate.php */
/* Location: ./application/third_party/datamapper/extensions/translate.php */
