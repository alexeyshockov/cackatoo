<?php

namespace Cackatoo\Exception;

/**
 * @author Alexey Shockov <alexey@shockov.com>
 */
class DeployException extends \RuntimeException
{
    public function __construct($message = "Deploy error.", Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
