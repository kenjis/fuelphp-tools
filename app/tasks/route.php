<?php

namespace Fuel\Tasks;

use ReflectionClass;
use ReflectionMethod;

/**
 * Route Task
 *
 * @author     Kenji Suzuki <https://github.com/kenjis>
 * @copyright  2016 Kenji Suzuki
 * @license    MIT License http://www.opensource.org/licenses/mit-license.php
 * @link       https://github.com/kenjis/fuelphp-tools
 */
class Route
{
	private $filelist = array();
	private $actions = array();
	private $routes = array();

	public static function run()
	{
		return static::help();
	}

	public static function help()
	{
		echo <<<HELP
Usage:
  php oil r route:controller [<format>]

Description:
  This command dumps routes from controller/method.

Examples:
  php oil r route:controller
  php oil r route:controller tsv

HELP;
	}

	/**
	 * Show URI and Controller/Method from controllers
	 * 
	 * @param string $format output format (tsv)
	 * 
	 * @TODO module support
	 */
	public function controller($format = null)
	{
		$path = APPPATH . 'classes/controller/';
		$this->convert_filelist(\File::read_dir($path), $path);
//		var_dump($this->filelist);

		$this->search_controller_action_methods();
//		var_dump($this->actions);
		$this->generate_routes_from_actions();

		if ($format === 'tsv') {
			$this->show_routes_tsv();
		} else {
			$this->show_routes();
		}
	}

	protected function show_routes_tsv()
	{
		$max_key = 0;
		$max_val = 0;
		
		foreach ($this->routes as $key => $val) {
			$max_key = max($max_key, strlen($key));
			$max_val = max($max_val, strlen($val));
		}
		
		printf("%s\t%s\n",   'URI', 'Controller/Method');
		
		foreach ($this->routes as $key => $val) {
			printf("%s\t%s\n", $key, $val);
		}
	}

	protected function show_routes()
	{
		$max_key = 0;
		$max_val = 0;
		
		foreach ($this->routes as $key => $val) {
			$max_key = max($max_key, strlen($key));
			$max_val = max($max_val, strlen($val));
		}
		
		printf("|%-".$max_key."s|%-".$max_val."s|\n",   'URI', 'Controller/Method');
		printf(
			"|%-".$max_key."s|%-".$max_val."s|\n",
			str_repeat('-', $max_key),
			str_repeat('-', $max_val)
		);
		
		foreach ($this->routes as $key => $val) {
			printf("|%-".$max_key."s|%-".$max_val."s|\n", $key, $val);
		}
	}

	protected function generate_routes_from_actions()
	{
		foreach ($this->actions as $action) {
			$action_ = preg_replace('/::/', ':', $action);
			list($class, $method) = explode(':', $action_);
//			var_dump($class, $method);
			
			$uri = preg_replace('/_/', '/', $class);
			$uri = strtolower(preg_replace('/\AController\//', '', $uri));
			
			$method = strtolower(preg_replace('/\A.+?_/', '', $method));
			if ($method === 'router') {
				$uri .= '/*';
			} elseif ($method !== 'index') {
				$uri .= '/' . $method;
			}
//			var_dump($uri);
			
			$this->routes['/'.$uri] = $action;
		}

		ksort($this->routes);
	}

	protected function search_controller_action_methods()
	{
		foreach ($this->filelist as $file) {
			$classname = $this->get_classname($file);
//			echo $classname, PHP_EOL;
			$class = new ReflectionClass($classname);
			$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

			foreach ($methods as $method) {
				$method_name = $method->name;
				if ($method_name === 'router') {
					$this->actions[] = $classname . '::router';
					continue 2;
				}
			}

			foreach ($methods as $method) {
				$method_name = $method->name;
				$prefix = array('action_', 'get_', 'post_', '_put', '_delete');
				foreach ($prefix as $http_method) {
					if (preg_match('/\A'.$http_method.'/i', $method_name, $matches)) {
						$this->actions[] = $classname . '::' . $method_name;
						break;
					}
				}
			}
		}
	}

	protected function get_classname($file)
	{
		$lines = file($file);
		$namespace = '';

		foreach ($lines as $line) {
			if (preg_match('/\Anamespace (\S+);/i', $line, $matches)) {
				$namespace = $matches[1];
			} elseif (preg_match('/\Aclass (\w+)/i', $line, $matches)) {
				$classname = $matches[1];
			}
		}

		if ($namespace) {
			$fqcn = $namespace . '\\' . $classname;
		} else {
			$fqcn = $classname;
		}

		return $classname;
	}

	/**
	* Convert Filelist Array to Single Dimension Array
	*
	* @param  array   filelist array of \File::read_dir()
	* @param  string  directory
	* @return array
	*/
	protected function convert_filelist($arr, $dir = '')
	{
		foreach ($arr as $key => $val) {
			if (is_array($val)) {
				$this->convert_filelist($val, $dir . $key);
			} else {
				$this->filelist[] = $dir . $val;
			}
		}
	}
}
