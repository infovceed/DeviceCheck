<?php

namespace App\Traits;

use DateTime;
use DateTimeZone;

trait DateTrait
{
    
    public function setTimezone($updated_at, $timezone) {
        $date = new DateTime($updated_at);
        $date->setTimezone(new DateTimeZone($timezone));
        return $date;
    }
}
