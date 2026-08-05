<?php
/**
 * Umma Directory — Prayer time calculator
 * Simplified solar-position calculation (East-Africa friendly angles),
 * no external API required. Returns times in 24h "HH:MM".
 */

class PrayerTimes
{
    private $lat;
    private $lng;
    private $tz;

    const FAJR_ANGLE = 18.0;      // degrees below horizon
    const ISHA_ANGLE = 18.0;
    const SUNRISE_ANGLE = 0.833;  // refraction + solar-disc correction

    public function __construct($lat, $lng, $timezone = 'Africa/Nairobi')
    {
        $this->lat = (float)$lat;
        $this->lng = (float)$lng;
        $this->tz  = $timezone;
    }

    /** Prayer times for a given date (Y-m-d) → [Fajr, Sunrise, Dhuhr, Asr, Maghrib, Isha] as "HH:MM" */
    public function getTimesForDate($date = null)
    {
        $date = $date ?: date('Y-m-d');
        $dt   = new DateTime($date, new DateTimeZone($this->tz));
        $y    = (int)$dt->format('Y');
        $doy  = (int)$dt->format('z') + 1; // day of year 1..366

        // Solar declination & equation of time (approximate)
        $decl = 23.44 * sin(deg2rad(360 / 365 * ($doy - 81)));
        $eqt  = 9.87 * sin(deg2rad(2 * 360 / 365 * ($doy - 81)))
              - 7.53 * cos(deg2rad(360 / 365 * ($doy - 81)))
              - 1.5  * sin(deg2rad(360 / 365 * ($doy - 81)));

        $tzOffset = (new DateTimeZone($this->tz))->getOffset(new DateTime($date, new DateTimeZone($this->tz))) / 3600.0;

        // Solar noon in decimal hours
        $dhuhr = 12 + ($tzOffset - $this->lng / 15) - $eqt / 60;

        $latRad = deg2rad($this->lat);
        $decRad = deg2rad($decl);

        // Hour angle for a given ZENITH angle (degrees from vertical).
        //   cos(HA) = (cos(zenith) − sin(lat)·sin(dec)) / (cos(lat)·cos(dec))
        // Below-horizon events (Fajr/Isha/sunrise) have zenith > 90°.
        $hourAngle = function ($zenith) use ($latRad, $decRad) {
            $cos = (cos(deg2rad($zenith)) - sin($latRad) * sin($decRad)) / (cos($latRad) * cos($decRad));
            $cos = max(-1.0, min(1.0, $cos));
            return rad2deg(acos($cos)) / 15; // in hours
        };

        $sunriseHA = $hourAngle(90 + self::SUNRISE_ANGLE);
        $fajrHA    = $hourAngle(90 + self::FAJR_ANGLE);
        $ishaHA    = $hourAngle(90 + self::ISHA_ANGLE);

        // Asr: shadow factor 1 (standard) → altitude = atan(1 / (tan(|lat−dec|) + 1))
        $asrAlt = rad2deg(atan(1 / (abs(tan($latRad - $decRad)) + 1)));
        $asrHA  = $hourAngle(90 - $asrAlt);

        $times = [
            'Fajr'    => $dhuhr - $fajrHA,
            'Sunrise' => $dhuhr - $sunriseHA,
            'Dhuhr'   => $dhuhr,
            'Asr'     => $dhuhr + $asrHA,
            'Maghrib' => $dhuhr + $sunriseHA,
            'Isha'    => $dhuhr + $ishaHA,
        ];

        $out = [];
        foreach ($times as $name => $dec) {
            $out[$name] = self::formatHour($dec);
        }
        $out['date'] = $date;
        return $out;
    }

    /** Today's times (handy alias) */
    public function getTimes()
    {
        return $this->getTimesForDate();
    }

    /** Next prayer from now → ['name', 'time', 'remaining_minutes'] or null */
    public function getNextPrayer()
    {
        $times = $this->getTimes();
        $now = new DateTime('now', new DateTimeZone($this->tz));
        $order = ['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'];

        foreach ($order as $name) {
            $t = DateTime::createFromFormat('Y-m-d H:i', $times['date'] . ' ' . $times[$name], new DateTimeZone($this->tz));
            if ($t > $now) {
                return ['name' => $name, 'time' => $times[$name], 'remaining_minutes' => (int)round(($t->getTimestamp() - $now->getTimestamp()) / 60)];
            }
        }
        // All passed → tomorrow's Fajr
        $tomorrow = $this->getTimesForDate(date('Y-m-d', strtotime('+1 day')));
        $t = DateTime::createFromFormat('Y-m-d H:i', $tomorrow['date'] . ' ' . $tomorrow['Fajr'], new DateTimeZone($this->tz));
        return ['name' => 'Fajr', 'time' => $tomorrow['Fajr'], 'remaining_minutes' => (int)round(($t->getTimestamp() - $now->getTimestamp()) / 60)];
    }

    private static function formatHour($decimalHour)
    {
        $h = floor($decimalHour);
        $m = round(($decimalHour - $h) * 60);
        if ($m >= 60) { $h++; $m -= 60; }
        return sprintf('%02d:%02d', ((int)$h % 24 + 24) % 24, $m);
    }
}
