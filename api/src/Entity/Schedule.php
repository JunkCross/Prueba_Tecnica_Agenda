<?php
namespace App\Entity;

class Schedule
{
    private $id;
    private $day_of_week;
    private $start_time;
    private $end_time;
    private $created_at;

    public function __construct() {
        $this->created_at = new \DateTime();
    }

    public function getId(){ return $this->id; }
    public function getDayOfWeek(){ return $this->day_of_week; }
    public function setDayOfWeek($d){ $this->day_of_week = $d; }
    public function getStartTime(){ return $this->start_time; }
    public function setStartTime($t){ $this->start_time = $t; }
    public function getEndTime(){ return $this->end_time; }
    public function setEndTime($t){ $this->end_time = $t; }
    public function getCreatedAt(){ return $this->created_at; }
}
