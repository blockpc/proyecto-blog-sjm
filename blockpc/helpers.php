<?php

declare(strict_types=1);

if (! function_exists('tiempo')) {
    /**
     * Format a number of seconds as a GMT/UTC time string.
     *
     * @param int $seconds Number of seconds (must be greater than or equal to 0).
     * @param string $format Date/time format string compatible with gmdate.
     * @return string The formatted time string according to the given format in GMT/UTC.
     * @throws \InvalidArgumentException If $seconds is negative.
     */
    function tiempo(int $seconds, string $format = 'H:i:s'): string
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException('$seconds must be non-negative.');
        }

        return gmdate($format, $seconds);
    }
}
