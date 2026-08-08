<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class DailyItTicketReportAggregator
{
    /**
     * Tiket dibuat atau di-assign dalam jendela [startOfDay, end], sekali per tiket per teknisi.
     *
     * @return list<array{assignee_id: int, name: string, count: int}>
     */
    public function forDay(CarbonInterface $moment, ?string $depId = 'IT'): array
    {
        $start = $moment->copy()->startOfDay();
        $end = $moment->copy();

        /** @var array<int, array<int, true>> $ticketIdsByAssignee */
        $ticketIdsByAssignee = [];

        $created = Ticket::query()
            ->published()
            ->whereNotNull('assignee_id')
            ->whereBetween('created_at', [$start, $end])
            ->when(
                $depId !== null && $depId !== '',
                fn ($q) => $q->whereHas(
                    'assignee',
                    fn ($u) => $u->where('dep_id', $depId)->whereIn('role', ['staff', 'admin'])
                )
            )
            ->get(['id', 'assignee_id']);

        foreach ($created as $ticket) {
            $assigneeId = (int) $ticket->assignee_id;
            $ticketIdsByAssignee[$assigneeId][(int) $ticket->id] = true;
        }

        $activities = TicketActivity::query()
            ->where('action', TicketActivity::ACTION_ASSIGNED)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('ticket', fn ($q) => $q->published())
            ->get(['ticket_id', 'new_value']);

        if ($activities->isNotEmpty()) {
            $names = $activities->pluck('new_value')->filter()->unique()->values();

            $usersByName = User::query()
                ->whereIn('name', $names)
                ->whereIn('role', ['staff', 'admin'])
                ->when(
                    $depId !== null && $depId !== '',
                    fn ($q) => $q->where('dep_id', $depId)
                )
                ->get(['id', 'name'])
                ->groupBy('name');

            foreach ($activities as $activity) {
                $name = $activity->new_value;
                if (! is_string($name) || $name === '') {
                    continue;
                }

                /** @var Collection<int, User>|null $matches */
                $matches = $usersByName->get($name);
                $user = $matches?->first();
                if ($user === null) {
                    continue;
                }

                $ticketIdsByAssignee[(int) $user->id][(int) $activity->ticket_id] = true;
            }
        }

        if ($ticketIdsByAssignee === []) {
            return [];
        }

        $users = User::query()
            ->whereIn('id', array_keys($ticketIdsByAssignee))
            ->get(['id', 'name'])
            ->keyBy('id');

        $rows = [];
        foreach ($ticketIdsByAssignee as $assigneeId => $ticketIds) {
            $user = $users->get($assigneeId);
            if ($user === null) {
                continue;
            }

            $rows[] = [
                'assignee_id' => $assigneeId,
                'name' => $user->name,
                'count' => count($ticketIds),
            ];
        }

        usort($rows, function (array $a, array $b): int {
            if ($a['count'] !== $b['count']) {
                return $b['count'] <=> $a['count'];
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $rows;
    }
}
