<?php
class Activite {
    private int $id;
    private string $description;
    private string $status;
    private int $project_id;
     
    public function __construct(int $id, string $description, string $status, int $project_id){    
        $this->id = $id;
        $this->description = $description;
        $this->status = $status;
        $this->project_id = $project_id;
    }

    // Getters
    public function getId(): int {
        return $this->id;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function getProjectId(): int {
        return $this->project_id;
    }

    // Setters
    public function setDescription(string $desc): void {
        $this->description = $desc;
    }

    public function setStatus(string $status): void {
        $this->status = $status;
    }
}
