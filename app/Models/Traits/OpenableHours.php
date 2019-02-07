<?php

namespace App\Models\Traits;

use App\Models\OpeningHours;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasOpenableHours
{
    use SoftDeletes;

    /**
     * Duplicate
     * @param $value
     */
    public function getOpenAttribute() {
        if (null === $this->open_period_mins) {
            return null;
        }

        return date('h:ia', mktime(0, $this->open_period_mins));
    }

    /**
     * Duplicate
     * @param $value
     */
    public function getCloseAttribute() {
        if (null === $this->close_period_mins) {
            return null;
        }

        return date('h:ia', mktime(0, $this->close_period_mins));
    }

    /**
     * @param $value
     */
    public function setDatesAttribute($value)
    {
        $days = [];
        foreach (explode(";", $value) as $item) {
            $days[] = date('wm', strtotime($item));
        }

        $this->days = json_encode($days);
        $this->attributes['dates'] = $value;
    }

    /**
     * @param string $time
     * @return int
     */
    static function minutesCnt(string $time):int {
        $timeStart = strtotime("12:00am");
        $timeEnd   = strtotime($time);

        return ($timeEnd - $timeStart) / 60;
    }
}
