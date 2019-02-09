<?php

use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    public static function generatePIN($digits = 4)
    {
        $i = 0; //counter
        $pin = ""; //our default pin is blank.
        while ($i < $digits) {
            //generate a random number between 0 and 9.
            $pin .= mt_rand(1, 9);
            $i++;
        }
        return $pin;
    }
}
