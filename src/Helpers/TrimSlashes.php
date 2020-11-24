<?php

namespace Mvarkus\Helpers;

trait TrimSlashes
{
    /**
     * Removes slashes from the string's end.
     *
     * @param string $string
     *
     * @return string
     */
    function trimSlashesFromTheEnd(string $string): string
    {
        return $string !== '/' ? rtrim($string, '/') : $string;
    }

    /**
     * Removes slashes from the string's start and adds single slash.
     *
     * @param string $string
     *
     * @return string
     */
    function trimExtraSlashesFromTheStart(string $string): string
    {
        return $string !== '/' ? '/'.ltrim($string, '/') : $string;
    }
}