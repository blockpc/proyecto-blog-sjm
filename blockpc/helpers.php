<?php

declare(strict_types=1);

if (! function_exists('tiempo')) {
    function tiempo(int $seconds, $format = "H:i:s"): string|false
    {
        return gmdate($format, $seconds);
    }
}
