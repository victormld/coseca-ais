<?php
declare(strict_types=1);

namespace App\Stage;

class AdscriptionStage implements StageInterface
{
    use StageTrait;

    public function initialize(): void {}

    public function close(string $status) {}
}