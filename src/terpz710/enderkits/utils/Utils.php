<?php

declare(strict_types=1);

namespace terpz710\enderkits\utils;

final class Utils {

    public static function formatCooldownTime(int $seconds) : string{
        $timeUnits = [
            "year" => 31536000,
            "month" => 2628002,
            "week" => 604800,
            "day" => 86400,
            "hour" => 3600,
            "minute" => 60,
            "second" => 1
        ];

        $result = [];

        foreach ($timeUnits as $unit => $value) {
            if ($seconds >= $value) {
                $count = intdiv($seconds, $value);
                $seconds %= $value;
                $result[] = "$count $unit" . ($count > 1 ? "s" : "");
            }
        }
        return empty($result) ? "0 seconds" : implode(", ", $result);
    }
}
