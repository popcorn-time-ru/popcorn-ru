<?php

namespace App\Entity;

trait MySqlString
{
    protected function clearUtf(string $str): string
    {
        return preg_replace('/([\xF0-\xF7]...)|([\xE0-\xEF]..)/s', '#', $str);
    }
}
