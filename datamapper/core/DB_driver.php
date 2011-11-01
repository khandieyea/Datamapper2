<?php
/**
 * Data Mapper ORM bootstrap
 *
 * Dynamic CI Loader class extension
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0-dev
 */
$dmclass = <<<CODE
class DM_DB_Driver extends $driver
{
	public function dm_access_method(\$function, \$p1 = null, \$p2 = null, \$p3 = null, \$p4 = null)
	{
		switch (func_num_args())
		{
			case 1:
				return \$this->{\$function}();
			case 2:
				return \$this->{\$function}(\$p1);
				break;
			case 3:
				return \$this->{\$function}(\$p1, \$p2);
				break;
			case 4:
				return \$this->{\$function}(\$p1, \$p2, \$p3);
				break;
			case 5:
				return \$this->{\$function}(\$p1, \$p2, \$p3, \$p4);
				break;
		}
	}
}
CODE;

// dynamically add our class extension
eval($dmclass);
unset($dmclass);

// and update the name of the class to instantiate
$driver = 'DM_DB_Driver';

/* End of file DB_driver.php */
/* Location: ./application/third_party/datamapper/core/DB_driver.php */
