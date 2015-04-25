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
	static $class_definitions = array();
	
	public static function run()
	{
		echo <<<EOL
Usage:
  oil refine generate:autocomplete  ... generate php file for IDE's auto completion
EOL;
	}
	
	public static function autocomplete()
	{
		$path = COREPATH . 'classes/';
		static::convert_filelist(\File::read_dir($path), $path);
		
		$path = PKGPATH . 'auth/classes/';
		static::convert_filelist(\File::read_dir($path), $path);
		
		$path = PKGPATH . 'email/classes/';
		static::convert_filelist(\File::read_dir($path), $path);
		
		$path = PKGPATH . 'parser/classes/';
		$filelist = static::convert_filelist(\File::read_dir($path), $path);
		
		static::generate_class_definition($filelist);
		
		$output = '<?php' . "\n\n" . implode("", static::$class_definitions);
		$file = APPPATH . '_autocomplete.php';
		$ret = file_put_contents($file, $output);
		
		if ($ret === false)
		{
			echo 'Can\'t write to ' . $file . PHP_EOL;
		}
		else
		{
			echo $file . ' was created.'. PHP_EOL;
		}
	}
	
	private static function generate_class_definition(Array $filelist)
	{
		foreach ($filelist as $file)
		{
			//echo "$file\n";
			$lines = file($file);
			$namespace = '';
			
			foreach ($lines as $line)
			{
				if (preg_match('/^namespace (\S+);/', $line, $matches))
				{
					$namespace = $matches[1];
				}
				else if (preg_match('/^class (\w+)/', $line, $matches))
				{
					//var_dump($matches);
					$class_name = $matches[1];
					// don't override. use core class
					if (! isset(static::$class_definitions[$class_name])) {
						static::$class_definitions[$class_name] = 'class ' . $class_name
							. ' extends ' . $namespace . '\\' . $class_name
							. ' {}' . "\n";
					}
				}
				else if (preg_match('/^abstract class (\w+)/', $line, $matches))
				{
					$class_name = $matches[1];
					// don't override. use core class
					if (! isset(static::$class_definitions[$class_name])) {
						static::$class_definitions[$class_name] = 'abstract class ' . $class_name
							. ' extends ' . $namespace . '\\' . $class_name
							. ' {}' . "\n";
					}
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
		// save previous list
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
