<?php

declare(strict_types=1);

namespace dctxdev\tw\math;

/**
 * Class Time
 * @package skywars\math
 */
class Time {

    /**
     * @param int $time
     * @return string
     */
    public static function calculateTime(int $time): string {
        return gmdate("i:s", $time); 
    }
}
