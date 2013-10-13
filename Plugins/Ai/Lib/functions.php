<?php
function array_compare(array $first, array $second)
{
    return (
        count(array_diff($first, $second)) == 0 &&
        count(array_diff($first, $second)) == 0 &&
        count($second) == count($first)
    );
}