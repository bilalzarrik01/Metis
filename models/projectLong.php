<?php
require_once __DIR__ . '/Project.php';

class ProjectLong extends Project
{
    public function __construct(int $member_id, string $title)
    {
        parent::__construct($member_id, $title, 'long');
    }

    // You can add methods specific to "long" projects
    public function getDuration(): ?string
    {
        if ($this->start_date && $this->end_date) {
            $start = new DateTime($this->start_date);
            $end = new DateTime($this->end_date);
            $diff = $start->diff($end);
            return $diff->format('%a days');
        }
        return null;
    }
}
