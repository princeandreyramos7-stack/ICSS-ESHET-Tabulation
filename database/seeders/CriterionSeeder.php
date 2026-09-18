<?php

namespace Database\Seeders;

use App\Models\Criterion;
use Illuminate\Database\Seeder;

class CriterionSeeder extends Seeder
{
    /**
     * The five evaluation criteria shared by every track (total 100 points).
     * Text is copied verbatim from the official Panel of Evaluator sheets.
     */
    public function run(): void
    {
        $criteria = [
            [
                'name' => 'Originality, novelty, creativity, or innovativeness',
                'description' => 'Evaluates the extent to which the research presents original ideas, introduces innovative approaches, offers creative solutions, or contributes new knowledge to the field. The study should demonstrate uniqueness in its concept, methodology, application, or findings.',
                'weight' => 25,
            ],
            [
                'name' => 'Significance, impact, or contribution',
                'description' => 'Assesses the relevance and importance of the research in addressing current issues and its potential to contribute to academic advancement, industry, policy, or society. The study should demonstrate meaningful implications and practical or theoretical value.',
                'weight' => 25,
            ],
            [
                'name' => 'Clarity and coherence of the oral presentation including materials and delivery',
                'description' => 'Measures the effectiveness of the research presentation, including logical organization, clarity of explanations, confidence and professionalism of the presenter, appropriate use of presentation materials, engagement with the audience, and ability to communicate complex ideas effectively.',
                'weight' => 15,
            ],
            [
                'name' => 'Mastery of the subject',
                'description' => "Evaluates the presenter's depth of knowledge, understanding of the research topic, ability to explain the methodology and findings, and competence in responding accurately and confidently to questions from the evaluators.",
                'weight' => 20,
            ],
            [
                'name' => 'Quality of presentation materials',
                'description' => 'Assesses the overall quality, organization, readability, visual appeal, and effectiveness of presentation materials (e.g., slides, posters, multimedia). Materials should be well-designed, free from errors, and effectively support the presentation of the research.',
                'weight' => 15,
            ],
        ];

        foreach ($criteria as $index => $criterion) {
            Criterion::updateOrCreate(
                ['name' => $criterion['name']],
                $criterion + ['sort_order' => $index + 1]
            );
        }

        $total = array_sum(array_column($criteria, 'weight'));
        $this->command?->info("Criteria seeded: " . count($criteria) . " (total weight {$total})");
    }
}
