<?php

namespace Database\Seeders;

use App\Models\AiModels\AiModel;
use Illuminate\Database\Seeder;

class AiModelSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            [
                'name' => 'GPT-4',
                'description' => 'OpenAI\'s most capable model, great for complex tasks that require advanced reasoning and creativity.',
            ],
            [
                'name' => 'GPT-3.5 Turbo',
                'description' => 'Fast and efficient model, ideal for most conversational tasks and quick responses.',
            ],
            [
                'name' => 'Claude 3 Opus',
                'description' => 'Anthropic\'s most intelligent model, excellent for complex analysis and detailed responses.',
            ],
            [
                'name' => 'Claude 3 Sonnet',
                'description' => 'Balanced model offering great performance for everyday tasks at a lower cost.',
            ],
            [
                'name' => 'Gemini Pro',
                'description' => 'Google\'s advanced model with strong reasoning capabilities and multimodal understanding.',
            ],
        ];

        foreach ($models as $model) {
            AiModel::firstOrCreate(
                ['name' => $model['name']],
                ['description' => $model['description']]
            );
        }

        $this->command->info('AI Models seeded successfully!');
    }
}
