<?php

namespace guymers\proxy\template;

use \Exception;
use \ReflectionMethod;
use \ReflectionParameter;

use guymers\proxy\internal\Parameter;

/**
 * Template for a method
 */
class MethodTemplate {

	private static $TEMPLATE = '
		public function {method}({parameterDefinitions}) {
			$methodAndHook = $this->_methodHooks["{method}"];
			$method = $methodAndHook->getMethod();
			$hook = $methodAndHook->getHook();
			$args = func_get_args();

			return $hook->invoke($this, $method, $args);
		}
	';

	/**
	 * @var ReflectionMethod
	 */
	private $method;

	public function __construct(ReflectionMethod $method) {
		$this->method = $method;
	}

	public function render() {
		$parameters = [];
		$parameterDefinitions = [];

		foreach ($this->method->getParameters() as $parameter) {
			$internalParameter = new Parameter($parameter);

			$parameters[] = $internalParameter->asString();
			$parameterDefinitions[] = $internalParameter->asFullString();
		}

		$data = [
			"method" => $this->method->getName(),
			"parameters" => join(", ", $parameters),
			"parameterDefinitions" => join(", ", $parameterDefinitions)
		];

		return Template::render(self::$TEMPLATE, $data);
	}

}
