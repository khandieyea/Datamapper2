<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM Class
 *
 * DataMapper extension - transaction methods
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0
 */

class DataMapper_Transactions {

	/**
	 * begin a transaction
	 *
	 * @param	DataMapper	$dmobject	the DataMapper object
	 * @param	bool		$test_mode	set to TRUE to only run a test (and not commit)
	 *
	 * @return	bool	success or failure
	 */
	public static function trans_begin($dmobject, $test_mode = FALSE)
	{
		return $dmobject->db->trans_begin($test_mode);
	}

	// --------------------------------------------------------------------

	/**
	 * lets you retrieve the transaction flag to determine if it has failed
	 *
	 * @param	DataMapper	$dmobject	the DataMapper object
	 *
	 * @return	bool	returns FALSE if the transaction has failed
	 */
	public static function trans_status($dmobject)
	{
		return $dmobject->db->trans_status();
	}

	// --------------------------------------------------------------------

	/**
	 * commit a transaction
	 *
	 * @param	DataMapper	$dmobject	the DataMapper object
	 *
	 * @return	bool	success or failure
	 */
	public function trans_commit($dmobject)
	{
		return $dmobject->db->trans_commit();
	}

	// --------------------------------------------------------------------

	/**
	 * rollback a transaction
	 *
	 * @param	DataMapper	$dmobject	the DataMapper object
	 *
	 * @return	bool	success or failure
	 */
	public function trans_rollback($dmobject)
	{
		return $dmobject->db->trans_rollback();
	}

	// --------------------------------------------------------------------

	/**
	 * this permits transactions to be disabled at run-time.
	 *
	 * @param	DataMapper	$dmobject	the DataMapper object
	 *
	 * @return	void
	 */
	public function trans_off($dmobject)
	{
		$dmobject->db->trans_enabled = FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * when strict mode is enabled, if you are running multiple groups of
	 * transactions, if one group fails all groups will be rolled back.
	 * if strict mode is disabled, each group is treated autonomously, meaning
	 * a failure of one group will not affect any others.
	 *
	 * @param	bool		$mode		set to FALSE to disable strict mode.
	 * @param	DataMapper	$dmobject	the DataMapper object
	 */
	public function trans_strict($dmobject, $mode = TRUE)
	{
		$dmobject->db->trans_strict($mode);
	}

	// --------------------------------------------------------------------

	/**
	 * start a transaction
	 *
	 * @param	DataMapper	$dmobject	the DataMapper object
	 * @param	bool		$test_mode	set to TRUE to only run a test (and not commit)
	 */
	public function trans_start($dmobject, $test_mode = FALSE)
	{
		$dmobject->db->trans_start($test_mode);
	}

	// --------------------------------------------------------------------

	/**
	 * complete a transaction
	 *
	 * @param	DataMapper	$dmobject	the DataMapper object
	 *
	 * @return	bool	success or failure
	 */
	public static function trans_complete($dmobject)
	{
		return $dmobject->db->trans_complete();
	}

	// --------------------------------------------------------------------

	/**
	 * begin an auto transaction if enabled.
	 *
	 * @ignore
	 *
	 * @param	DataMapper	$dmobject	the DataMapper object
	 */
	public static function dm_auto_trans_begin($dmobject)
	{
		// begin auto transaction
		$dmobject->dm_get_flag('auto_transaction') AND $dmobject->trans_begin();
	}

	// --------------------------------------------------------------------

	/**
	 * complete an auto transaction if enabled
	 *
	 * @ignore
	 *
	 * @param	DataMapper	$dmobject	the DataMapper object
	 * @param	string	$label	name for this transaction
	 */
	public static function dm_auto_trans_complete($dmobject, $label = 'complete')
	{
		// complete auto transaction
		if ( $dmobject->dm_get_flag('auto_transaction') )
		{
			// check if successful
			if ( ! $dmobject->trans_complete() )
			{
				$rule = 'transaction';

				// get corresponding error from language file
				if ( FALSE === ($line = $dmobject->dm_lang_line($rule)) )
				{
					$line = 'Unable to access the ' . $rule .' error message.';
				}

				// add transaction error message
				$dmobject->error->message($rule, sprintf($line, $label));

				// set validation as failed
				$dmobject->dm_set_flag('valid') = FALSE;
			}
		}
	}

}

/* End of file transactions.php */
/* Location: ./application/third_party/datamapper/datamapper/transactions.php */
