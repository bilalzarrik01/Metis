<?php
require_once __DIR__ . '/../core/BaseModel.php';

abstract class Project extends BaseModel
{
    protected static string $table = 'projects';

    protected ?int $id = null;
    protected int $member_id;
    protected string $title;
    protected string $type; // 'court' or 'long'
    protected ?string $start_date = null;
    protected ?string $end_date = null;
    protected ?string $created_at = null;

    public function __construct(int $member_id, string $title, string $type)
    {
        $this->setMemberId($member_id);
        $this->setTitle($title);
        $this->type = $type;
    }

    public function getId(): ?int { return $this->id; }
    public function getMemberId(): int { return $this->member_id; }
    public function getTitle(): string { return $this->title; }
    public function getType(): string { return $this->type; }
    public function getStartDate(): ?string { return $this->start_date; }
    public function getEndDate(): ?string { return $this->end_date; }

    public function setMemberId(int $member_id): void
    {
        if ($member_id <= 0) throw new InvalidArgumentException("Invalid member ID");
        $this->member_id = $member_id;
    }

    public function setTitle(string $title): void
    {
        if (trim($title) === '') throw new InvalidArgumentException("Title is required");
        $this->title = $title;
    }

    public function setStartDate(?string $start_date): void
    {
        $this->start_date = $start_date;
    }

    public function setEndDate(?string $end_date): void
    {
        $this->end_date = $end_date;
    }

    // Optional: validation before saving
    public function save(): bool
    {
        // check type
        if (!in_array($this->type, ['court', 'long'])) {
            throw new InvalidArgumentException("Invalid project type");
        }
        return parent::save();
    }
}
