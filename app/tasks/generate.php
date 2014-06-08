<?php

namespace Fuel\Tasks;

/**
* Generate Task
*
* @author     Kenji Suzuki <https://github.com/kenjis>
* @copyright  2012 Kenji Suzuki
* @license    MIT License http://www.opensource.org/licenses/mit-license.php
* @link       https://github.com/kenjis/fuelphp-tools
*/

class Generate
{
	static $class_definition = '';
	
	public static function run()
	{
		echo <<<EOL
Usage:
  oil refine generate:autocomplete  ... generate php file for IDE's auto completion
EOL;
	}
	
	public static function autocomplete()
	{
		$filelist = \File::read_dir(COREPATH . 'classes');
		$filelist = static::convert_filelist($filelist);
		
		static::generate_class_definition($filelist);
		
		static::$class_definition = '<?php' . "\n\n" . static::$class_definition;
		$file = APPPATH . '_autocomplete.php';
		$ret = file_put_contents($file, static::$class_definition);
		
		if ($ret === false)
		{
			echo 'Can\'t write to ' . $file;
		}
		else
		{
			echo $file . ' was created.';
		}
	}
	
	private static function generate_class_definition(Array $filelist)
	{
		foreach ($filelist as $file)
		{
			//echo "$file\n";
			$lines = file(COREPATH . 'classes/' . $file);
			
			foreach ($lines as $line)
			{
				if (preg_match('/^class (\w+)/', $line, $matches))
				{
					//var_dump($matches);
					$class_name = $matches[1];
					static::$class_definition .= 'class ' . $class_name;
					static::$class_definition .=  ' extends Fuel\\Core\\' . $class_name;
					static::$class_definition .=  ' {}' . "\n";
				}
				else if (preg_match('/^abstract class (\w+)/', $line, $matches))
				{
					$class_name = $matches[1];
					static::$class_definition .= 'abstract class ' . $class_name;
					static::$class_definition .=  ' extends Fuel\\Core\\' . $class_name;
					static::$class_definition .=  ' {}' . "\n";
				}
			}
		}
	}
	
	/**
	* Convert Filelist Array to Single Dimension Array
	*
	* @param  array   filelist array of \File::read_dir()
	* @param  string  directory
	* @return array
	*/
	private static function convert_filelist($arr, $dir = '')
	{
		static $list = array();
	
		foreach ($arr as $key => $val)
		{
			if (is_array($val))
			{
				static::convert_filelist($val, $dir . $key);
			}
			else
			{
				$list[] = $dir . $val;
			}
		}
	
		return $list;
	}
}
