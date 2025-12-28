<?php
require_once __DIR__ . '/Project.php';

class ProjectCourt extends Project
{
    public function __construct(int $member_id, string $title)
    {
        parent::__construct($member_id, $title, 'court');
    }

    public function getShortDescription(): string
    {
        return substr($this->title, 0, 50);
    }
}
