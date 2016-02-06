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

		$this->search_controller_action_methods();
		$this->generate_routes_from_actions();

		if ($format === 'tsv') {
			$this->show_routes_tsv();
		} else {
			$this->show_routes();
		}
	}

	protected function show_routes_tsv()
	{
		$format = "%s\t%s\t%s\n";

		printf($format, 'URI', 'Controller/Method', 'Parent');

		foreach ($this->routes as $uri => $action) {
			$method = $action['class'].'::'.$action['method'];
			printf(
				$format,
				$uri,
				$method,
				$action['parent']
			);
		}
	}

	protected function get_max_length()
	{
		$max_uri = 0;
		$max_method = 0;
		$max_parent = 0;

		foreach ($this->routes as $uri => $action) {
			$max_uri = max($max_uri, strlen($uri));
			$method = $action['class'].'::'.$action['method'];
			$max_method = max($max_method, strlen($method));
			$max_parent = max($max_parent, strlen($action['parent']));
		}

		return [$max_uri, $max_method, $max_parent];
	}

	protected function show_routes()
	{
		list($max_uri, $max_method, $max_parent) = $this->get_max_length();
		$format = "|%-".$max_uri."s|%-".$max_method."s|%-".$max_parent."s\n";

		printf(
			$format,
			'URI',
			'Controller/Method',
			'Parent'
		);
		printf(
			$format,
			str_repeat('-', $max_uri),
			str_repeat('-', $max_method),
			str_repeat('-', $max_parent)
		);

		foreach ($this->routes as $uri => $action) {
			$method = $action['class'].'::'.$action['method'];
			printf(
				$format,
				$uri,
				$method,
				$action['parent']
			);
		}
	}

	protected function generate_routes_from_actions()
	{
		foreach ($this->actions as $action) {
			$class = $action['class'];
			$method = $action['method'];
			$parent = $action['parent'];

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
			$parent = $class->getParentClass()->name;
			$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

			foreach ($methods as $method) {
				$method_name = $method->name;
				if (
					$method_name === 'router'
					&& ! $class->isSubclassOf('Fuel\Core\Controller_Rest')
				) {
					$this->actions[] = array(
						'class' => $classname,
						'method' => 'router',
						'parent' => $parent,
					);
					continue 2;
				}
			}

			foreach ($methods as $method) {
				$method_name = $method->name;
				$prefix = array('action_', 'get_', 'post_', '_put', '_delete');
				foreach ($prefix as $http_method) {
					if (preg_match('/\A'.$http_method.'/i', $method_name, $matches)) {
						$this->actions[] = array(
							'class' => $classname,
							'method' => $method_name,
							'parent' => $parent,
						);
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
