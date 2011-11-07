<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * DataMapper extension - csv export/import methods
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0
 */

class DataMapper_Csv
{
	/**
	 * Convert a DataMapper model into an associative array.
	 *
	 * @param	DataMapper	$dmobject		the DataMapper object to export
	 * @param	mixed		$filename		the filename to export to, or a file pointer. if this is a file pointer, it will not be closed
	 * @param	array		$fields			array of fields to include.  if empty, includes all database columns
	 * @param	bool		$include_header	if FALSE the header is not exported with the CSV. Not recommended if planning to import this data
	 *
	 * @return	bool		TRUE on success, or FALSE on failure
	 */
	public static function csv_export($dmobject, $filename, $fields = '', $include_header = TRUE)
	{
		// determine the correct field set
		empty($fields) AND $fields = $dmobject->dm_get_config('fields');

		// assume export is a success
		$success = TRUE;

		// determine if we need to open the file or not
		if (is_string($filename) )
		{
			// open the file, if possible.
			$fp = fopen($filename, 'w');
			if ( $fp === FALSE )
			{
				throw new DataMapper_Exception("DataMapper CSV Extension: Unable to open file '$filename'");
			}
		}
		else
		{
			// assume file pointer.
			$fp = $filename;
		}

		// print out header line
		$include_header AND $success = fputcsv($fp, $fields);

		if ( $success )
		{
			foreach ( $object as $o )
			{
				$result = array();

				// convert each object into an array
				foreach ( $fields as $f )
				{
					$result[] = $o->{$f};
				}

				// output CSV-formatted line
				$success = fputcsv($fp, $result);

				// stop on first failure.
				if ( !$success )
				{
					break;
				}
			}
		}

		if ( is_string($filename) )
		{
			fclose($fp);
		}

		return $success;
	}

	// --------------------------------------------------------------------

	/**
	 * import objects from a CSV file
	 *
	 * completely empty rows are automatically skipped, as are rows that
	 * start with a # sign (assumed to be comments)
	 *
	 * @param	DataMapper	$dmobject	the type of DataMapper Object to import
	 * @param	mixed		$filename	name of CSV file, or a file pointer
	 * @param	array		$fields		if empty, the database fields are used.  Otherwise used to limit what fields are saved
	 * @param	boolean		$header_row	if true, the first line is assumed to be a header row.  Defaults to true
	 * @param	mixed		$callback	a callback method for each row.  Can return FALSE on failure to save, or 'stop' to stop the import
	 *
	 * @return	array		array of imported objects, or FALSE if unable to import
	 */
	public static function csv_import($dmobject, $filename, $fields = '', $header_row = TRUE, $callback = NULL)
	{
		$class = get_class($dmobject);

		// determine the correct field set
		empty($fields) AND $fields = $dmobject->dm_get_config('fields');

		// determine if we need to open the file or not.
		if ( is_string($filename) )
		{
			// open the file, if possible.
			$fp = fopen($filename, 'r');
			if ( $fp === FALSE )
			{
				throw new DataMapper_Exception("DataMapper CSV Extension: Unable to open file '$filename'");
			}
		}
		else
		{
			// assume file pointer.
			$fp = $filename;
		}

		$result = empty($callback) ? array() : 0;
		$columns = NULL;

		while ( ($data = fgetcsv($fp)) !== FALSE )
		{
			// get column names
			if ( is_null($columns) )
			{
				if ( $header_row )
				{
					// store header row for column names
					$columns = $data;

					// only include columns in $fields
					foreach ( $columns as $index => $name )
					{
						if( ! in_array($name, $fields) )
						{
							// mark column as false to skip
							$columns[$index] = FALSE;
						}
					}
					continue;
				}
				else
				{
					$columns = $fields;
				}
			}

			// skip on comments and empty rows
			if ( empty($data) OR $data[0][0] == '#' OR implode('', $data) == '' )
			{
				continue;
			}

			// create the object to save
			$o = new $class();

			foreach ( $columns as $index => $key )
			{
				if ( count($data) <= $index )
				{
					// more header columns than data columns
					break;
				}

				// skip columns that were determined to not be needed above
				if ( $key === FALSE )
				{
					continue;
				}

				// finally, it's OK to save the data column
				$o->{$key} = $data[$index];
			}

			if ( empty($callback) )
			{
				$result[] = $o;
			}
			else
			{
				$test = call_user_func($callback, $o);
				if ( $test === 'stop' )
				{
					break;
				}
				elseif ( $test !== FALSE )
				{
					$result++;
				}
			}
		}

		if ( is_string($filename) )
		{
			fclose($fp);
		}

		return $result;
	}

}

/* End of file csv.php */
/* Location: ./application/third_party/datamapper/extensions/csv.php */
