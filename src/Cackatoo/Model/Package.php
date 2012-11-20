<?php

namespace Cackatoo\Model;

/**
 * .deb package (metadata).
 *
 * @author Alexey Shockov <alexey@shockov.com>
 */
class Package
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @internal
     */
    // TODO Error handling.
    public function __construct($parameters)
    {
        // Stupid parser.
        $parameters = explode("\n", $parameters);

        $parsedParameters = [];
        foreach ($parameters as $parameter) {
            list($key, $value) = explode(':', $parameter, 2);

            if ('Package' == $key) {
                $key = 'Name';
            }

            $parsedParameters[$key] = trim($value);
        }

        $this->parameters = $parsedParameters;
    }

    public function __call($method, $arguments)
    {
        $prefix   = strtolower(substr($method, 0, 3));
        $property = substr($method, 3);

        if ('get' == $prefix) {
            if (isset($this->parameters[$property])) {
                return $this->parameters[$property];
            }
        }

        throw new \BadMethodCallException('Unknown method.');
    }
}
