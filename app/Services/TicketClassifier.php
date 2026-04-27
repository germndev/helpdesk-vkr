<?php

namespace App\Services;

use App\Models\ClassificationRule;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TicketClassifier
{
    public function classify(string $title, string $description): array
    {
        $normalizedText = $this->normalizeText(trim($title.' '.$description));

        $categoryMatch = $this->resolveBestCategoryMatch(
            ClassificationRule::query()
                ->active()
                ->with(['triggers', 'responsibles'])
                ->get(),
            $normalizedText,
        );

        return [
            'category' => $categoryMatch['accepted'] ? $categoryMatch['rule']->name : null,
            'priority' => $this->resolvePriority($normalizedText),
            'assigned_to' => $categoryMatch['accepted']
                ? $this->resolveResponsibleUserId($categoryMatch['rule'])
                : null,
            'classification_score' => $categoryMatch['score'] > 0 ? $categoryMatch['score'] : null,
            'needs_manual_review' => ! $categoryMatch['accepted'],
        ];
    }

    private function resolveBestCategoryMatch(Collection $rules, string $normalizedText): array
    {
        $minimumScore = (int) config('classification.category.min_score', 3);
        $minimumGap = (int) config('classification.category.min_gap', 2);

        $scoredRules = $rules
            ->map(function (ClassificationRule $rule) use ($normalizedText) {
                $score = 0;

                foreach ($rule->triggers as $trigger) {
                    $normalizedPhrase = $this->normalizeText($trigger->phrase);

                    if ($normalizedPhrase !== '' && str_contains($normalizedText, $normalizedPhrase)) {
                        $score += (int) $trigger->weight;
                    }
                }

                return [
                    'rule' => $rule,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->values();

        $bestMatch = $scoredRules->get(0);
        $secondBestMatch = $scoredRules->get(1);

        if (! $bestMatch || $bestMatch['score'] === 0) {
            return [
                'rule' => null,
                'score' => 0,
                'accepted' => false,
            ];
        }

        $scoreGap = $bestMatch['score'] - (($secondBestMatch['score'] ?? 0));
        $accepted = $bestMatch['score'] >= $minimumScore && $scoreGap >= $minimumGap;

        return [
            'rule' => $bestMatch['rule'],
            'score' => $bestMatch['score'],
            'accepted' => $accepted,
        ];
    }

    private function resolvePriority(string $normalizedText): string
    {
        $priorityScore = 0;

        foreach (config('classification.priority.signals.urgent', []) as $phrase => $weight) {
            if (str_contains($normalizedText, $this->normalizeText($phrase))) {
                $priorityScore += (int) $weight;
            }
        }

        foreach (config('classification.priority.signals.impact', []) as $phrase => $weight) {
            if (str_contains($normalizedText, $this->normalizeText($phrase))) {
                $priorityScore += (int) $weight;
            }
        }

        foreach (config('classification.priority.signals.blocker', []) as $phrase => $weight) {
            if (str_contains($normalizedText, $this->normalizeText($phrase))) {
                $priorityScore += (int) $weight;
            }
        }

        foreach (config('classification.priority.signals.softener', []) as $phrase => $weight) {
            if (str_contains($normalizedText, $this->normalizeText($phrase))) {
                $priorityScore -= (int) $weight;
            }
        }

        return match (true) {
            $priorityScore >= (int) config('classification.priority.thresholds.critical', 5) => 'Критический',
            $priorityScore >= (int) config('classification.priority.thresholds.high', 3) => 'Высокий',
            $priorityScore <= (int) config('classification.priority.thresholds.low', -1) => 'Низкий',
            default => 'Средний',
        };
    }

    private function resolveResponsibleUserId(ClassificationRule $rule): ?int
    {
        $responsibles = $rule->responsibles;

        if ($responsibles->isEmpty()) {
            return null;
        }

        $userIds = $responsibles->pluck('id');
        $activeStatuses = [Ticket::STATUS_NEW, Ticket::STATUS_IN_PROGRESS];
        $urgentPriorities = ['Высокий', 'Критический'];

        $taskStats = Ticket::query()
            ->selectRaw('assigned_to, COUNT(*) as total_active')
            ->selectRaw('SUM(CASE WHEN priority IN (?, ?) THEN 1 ELSE 0 END) as urgent_active', $urgentPriorities)
            ->whereIn('assigned_to', $userIds)
            ->whereIn('status', $activeStatuses)
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        $onlineIds = DB::table('sessions')
            ->whereIn('user_id', $userIds)
            ->where('last_activity', '>=', now()->subMinutes((int) config('classification.assignee.online_window_minutes', 15))->timestamp)
            ->pluck('user_id')
            ->all();

        return $responsibles
            ->sort(function (User $left, User $right) use ($taskStats, $onlineIds) {
                $leftStats = $taskStats->get($left->id);
                $rightStats = $taskStats->get($right->id);

                $leftOnline = in_array($left->id, $onlineIds, true) ? 1 : 0;
                $rightOnline = in_array($right->id, $onlineIds, true) ? 1 : 0;

                $leftUrgent = (int) ($leftStats->urgent_active ?? 0);
                $rightUrgent = (int) ($rightStats->urgent_active ?? 0);

                $leftTotal = (int) ($leftStats->total_active ?? 0);
                $rightTotal = (int) ($rightStats->total_active ?? 0);

                return [$rightOnline, $leftUrgent, $leftTotal, $left->id]
                    <=>
                    [$leftOnline, $rightUrgent, $rightTotal, $right->id];
            })
            ->first()?->id;
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
