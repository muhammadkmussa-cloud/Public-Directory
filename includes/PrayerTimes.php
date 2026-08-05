<?php
/**
 * Prayer Times Calculator
 * Calculates prayer times based on location and date
 * Uses simplified calculation methods suitable for East Africa
 */

class PrayerTimes {
    
    private $latitude;
    private $longitude;
    private $timezone;
    private $date;
    
    // Calculation method parameters
    const METHOD_EAST_AFRICA = [
        'fajr' => 18.0,
        'isha' => 18.0
    ];
    
    public function __construct($lat, $lng, $timezone = 'Africa/Nairobi', $date = null) {
        $this->latitude = $lat;
        $this->longitude = $lng;
        $this->timezone = $timezone;
        $this->date = $date ? new DateTime($date, new DateTimeZone($timezone)) : new DateTime('now', new DateTimeZone($timezone));
    }
    
    /**
     * Get all prayer times for the current date
     */
    public function getTimes() {
        $times = $this->calculate();
        
        return [
            'Fajr' => $this->formatTime($times['fajr']),
            'Sunrise' => $this->formatTime($times['sunrise']),
            'Dhuhr' => $this->formatTime($times['dhuhr']),
            'Asr' => $this->formatTime($times['asr']),
            'Maghrib' => $this->formatTime($times['maghrib']),
            'Isha' => $this->formatTime($times['isha']),
            'date' => $this->date->format('Y-m-d'),
            'hijri' => $this->getHijriDate()
        ];
    }
    
    /**
     * Calculate prayer times using simplified equations
     */
    private function calculate() {
        $year = (int)$this->date->format('Y');
        $month = (int)$this->date->format('m');
        $day = (int)$this->date->format('d');
        
        // Calculate day of year
        $dayOfYear = (int)$this->date->format('z') + 1;
        
        // Simplified solar calculations
        $declination = 23.45 * sin(deg2rad(360/365 * ($dayOfYear - 81)));
        $eqTime = 9.87 * sin(2 * deg2rad(360/365 * ($dayOfYear - 81))) 
                - 7.53 * cos(deg2rad(360/365 * ($dayOfYear - 81))) 
                - 1.5 * sin(deg2rad(360/365 * ($dayOfYear - 81)));
        
        $latRad = deg2rad($this->latitude);
        $decRad = deg2rad($declination);
        
        // Dhuhr (Noon)
        $dhuhr = 12 + ($this->timezoneOffset() / 15) - ($this->longitude / 15) - ($eqTime / 60);
        
        // Asr (Shadow length = object height + shadow at noon)
        $asrAngle = atan(1 + tan(abs($latRad - $decRad)));
        $asrHourAngle = rad2deg(acos(-tan($latRad) * tan($decRad) + 1/cos($latRad - $decRad) * tan($asrAngle)));
        $asr = $dhuhr + ($asrHourAngle / 15);
        
        // Maghrib (Sunset, angle = 0.833 degrees for atmospheric refraction)
        $sunsetAngle = 90.833;
        $sunsetHourAngle = rad2deg(acos(cos(deg2rad($sunsetAngle)) / (cos($latRad) * cos($decRad)) - tan($latRad) * tan($decRad)));
        $maghrib = $dhuhr + ($sunsetHourAngle / 15);
        
        // Isha (Angle = 18 degrees)
        $ishaAngle = self::METHOD_EAST_AFRICA['isha'];
        $ishaHourAngle = rad2deg(acos(cos(deg2rad($ishaAngle)) / (cos($latRad) * cos($decRad)) - tan($latRad) * tan($decRad)));
        $isha = $dhuhr + ($ishaHourAngle / 15);
        
        // Fajr (Angle = 18 degrees)
        $fajrAngle = self::METHOD_EAST_AFRICA['fajr'];
        $fajrHourAngle = rad2deg(acos(cos(deg2rad($fajrAngle)) / (cos($latRad) * cos($decRad)) - tan($latRad) * tan($decRad)));
        $fajr = $dhuhr - ($fajrHourAngle / 15);
        
        // Sunrise (Angle = 0.833 degrees)
        $sunrise = $dhuhr - ($sunsetHourAngle / 15);
        
        return [
            'fajr' => $fajr,
            'sunrise' => $sunrise,
            'dhuhr' => $dhuhr,
            'asr' => $asr,
            'maghrib' => $maghrib,
            'isha' => $isha
        ];
    }
    
    /**
     * Format decimal hour to HH:MM
     */
    private function formatTime($decimalTime) {
        $hours = floor($decimalTime);
        $minutes = round(($decimalTime - $hours) * 60);
        
        if ($minutes >= 60) {
            $hours++;
            $minutes -= 60;
        }
        
        return sprintf('%02d:%02d', $hours % 24, $minutes);
    }
    
    /**
     * Get timezone offset in hours
     */
    private function timezoneOffset() {
        $tz = new DateTimeZone($this->timezone);
        $offset = $tz->getOffset($this->date);
        return $offset / 3600;
    }
    
    /**
     * Get approximate Hijri date
     */
    private function getHijriDate() {
        // Simple approximation (not astronomically precise)
        $gregorian = clone $this->date;
        $hijriEpoch = new DateTime('622-07-16');
        
        $diff = $gregorian->diff($hijriEpoch);
        $days = $diff->days;
        
        // Islamic year is ~354.37 days
        $hijriYear = floor($days / 354.37) + 1;
        $remainingDays = $days % 354.37;
        $hijriMonth = floor($remainingDays / 29.53) + 1;
        $hijriDay = floor($remainingDays % 29.53) + 1;
        
        $months = [
            1 => 'Muharram', 2 => 'Safar', 3 => "Rabi' al-Awwal", 
            4 => "Rabi' al-Thani", 5 => 'Jumada al-Awwal', 6 => 'Jumada al-Thani',
            7 => 'Rajab', 8 => "Sha'ban", 9 => 'Ramadan',
            10 => 'Shawwal', 11 => "Dhu'l-Qi'dah", 12 => "Dhu'l-Hijjah"
        ];
        
        return [
            'day' => (int)$hijriDay,
            'month' => $months[min(12, (int)$hijriMonth)] ?? 'Unknown',
            'year' => (int)$hijriYear
        ];
    }
    
    /**
     * Get next prayer time from current moment
     */
    public function getNextPrayer() {
        $times = $this->getTimes();
        $now = new DateTime('now', new DateTimeZone($this->timezone));
        $currentTime = (int)$now->format('H') + ((int)$now->format('i') / 60);
        
        $prayers = ['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'];
        
        foreach ($prayers as $prayer) {
            $timeParts = explode(':', $times[$prayer]);
            $prayerTime = (int)$timeParts[0] + ((int)$timeParts[1] / 60);
            
            if ($prayerTime > $currentTime) {
                return [
                    'name' => $prayer,
                    'time' => $times[$prayer],
                    'remaining' => $this->formatRemaining($currentTime, $prayerTime)
                ];
            }
        }
        
        // If all prayers passed, return Fajr for tomorrow
        return [
            'name' => 'Fajr',
            'time' => $times['Fajr'],
            'remaining' => 'Until tomorrow'
        ];
    }
    
    private function formatRemaining($current, $target) {
        $diff = $target - $current;
        $hours = floor($diff);
        $minutes = round(($diff - $hours) * 60);
        return "{$hours}h {$minutes}m";
    }
}
