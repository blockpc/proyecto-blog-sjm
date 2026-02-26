<?php

declare(strict_types=1);

if (! function_exists('tiempo')) {
    function tiempo(int $seconds, string $format = 'H:i:s'): string
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException('$seconds must be non-negative.');
        }

        return gmdate($format, $seconds);
    }
}
