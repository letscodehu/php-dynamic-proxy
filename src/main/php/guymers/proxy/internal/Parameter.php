<?php

namespace guymers\proxy\internal;

use \Exception;
use \ReflectionParameter;

class Parameter {

	/**
	 * @var ReflectionParameter
	 */
	private $parameter;

	public function __construct(ReflectionParameter $parameter) {
		$this->parameter = $parameter;
	}

	/**
	 * Returns a string representing the parameter as it would appear in a
	 * method.
	 *
	 * @return string
	 */
	public function asString() {
		$name = $this->parameter->getName();

		return '$' . $name;
	}

	/**
	 * Returns a string representing the parameter as it would appear in a
	 * method definition.
	 *
	 * @return string
	 */
	public function asFullString() {
		$string = $this->asString();

		if ($this->parameter->isPassedByReference()) {
			$string = "&" . $string;
		}

		$type = $this->getTypeHint();

		if ($type) {
			$string = $type . " " . $string;
		}

		if ($this->parameter->isDefaultValueAvailable()) {
			$defaultValue = $this->parameter->getDefaultValue();
			$defaultValueString = var_export($defaultValue, true);

			$string = $string . " = " . $defaultValueString;
		}

        // workaround for SoapClient
        elseif ($this->parameter->getDeclaringClass()->getName() == "SoapClient") {
            $string .= $this->checkSoapClientWorkarounds();
        }

		return $string;
	}

	/**
	 * @see https://gist.github.com/Xeoncross/4723819
	 *
	 * @return string
	 */
	private function getTypeHint() {
		if ($this->parameter->isArray()) {
			return "array";
		}

		$name = null;

		try {
			// first try it on the normal way if the class is loaded then everything should go ok
			$class = $this->parameter->getClass();

			if ($class) {
				$name = $class->getName();
				$name = "\\" . $name;
			}
		} catch (Exception $exception) {
			// try to resolve it the ugly way by parsing the error message
			$parts = explode(" ", $exception->getMessage(), 3);
			$name = $parts[1];
			$name = "\\" . $name;
		}

		return $name;
	}

    private function checkSoapClientWorkarounds()
    {
        $applicable = false;
        if ($this->parameter->getDeclaringFunction()->getName() == "SoapClient" && $this->parameter->getName() == "options") {
            $applicable = true;
        }
        if ($this->parameter->getDeclaringFunction()->getName() == "__soapCall" &&
            in_array($this->parameter->getName(), ["options", "output_headers", "input_headers"])) {
            $applicable = true;
        }
        if ($this->parameter->getDeclaringFunction()->getName() == "__doRequest" && $this->parameter->getName() == "one_way") {
            $applicable = true;
        }
        if ($this->parameter->getDeclaringFunction()->getName() == "__setCookie" && $this->parameter->getName() == "value") {
            $applicable = true;
        }
        if ($this->parameter->getDeclaringFunction()->getName() == "__setLocation" && $this->parameter->getName() == "new_location") {
            $applicable = true;
        }
        return $applicable ? " = NULL" : "";
    }

}
