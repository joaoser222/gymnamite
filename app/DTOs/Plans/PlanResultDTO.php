<?php

namespace App\DTOs\Plans;

use App\Models\Plan;
use Spatie\LaravelData\Data;

class PlanResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public string $visibility,
        public string $created_at,
        public int $plan_category_id,
        public ?string $plan_category_name,
        public array $tiers,
        public array $modalities,
    ) {}

    public static function fromModel(Plan $plan): static
    {
        $tiers = [];
        foreach ($plan->tiers as $tier) {
            $tiers[] = [
                'id' => $tier->id,
                'quantity' => $tier->quantity,
                'price' => $tier->price,
            ];
        }

        $modalities = [];
        foreach ($plan->modalities as $modality) {
            $modalities[] = [
                'id' => $modality->id,
                'name' => $modality->name,
            ];
        }

        return new static(
            id: $plan->id,
            name: $plan->name,
            description: $plan->description,
            visibility: $plan->visibility->value,
            created_at: $plan->created_at?->toISOString() ?? '',
            plan_category_id: $plan->plan_category_id,
            plan_category_name: $plan->planCategory?->name ?? '',
            tiers: $tiers,
            modalities: $modalities,
        );
    }
}
