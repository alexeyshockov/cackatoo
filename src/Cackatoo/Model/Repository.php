<?php

namespace Cackatoo\Model;

use Colada\IteratorCollection;

/**
 * .deb repository.
 *
 * @author Alexey Shockov <alexey@shockov.com>
 */
class Repository extends IteratorCollection
{
    /**
     * @internal
     */
    public function __construct($packages)
    {
        // Not so beautiful...
        if (is_string($packages)) {
            // Stupid parser.
            $packages = array_filter(explode("\n\n", $packages));

            $iterator = new \ArrayIterator([]);
            foreach ($packages as $package) {
                $iterator[] = new Package($package);
            }
        } else {
            $iterator = $packages;
        }

        parent::__construct($iterator);
    }
}
