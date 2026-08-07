<?php

namespace App\DTOs\DirectLessons;

use App\DTOs\Contracts\BaseDTO;
use App\Models\DirectLesson;

class UpdateDirectLessonDTO extends BaseDTO
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(public DirectLesson $directLesson, public array $data) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromValidatedData(DirectLesson $directLesson, array $data): static
    {
        return new static($directLesson, $data);
    }
}
