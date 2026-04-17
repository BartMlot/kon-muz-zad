<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\EventRepositoryInterface;

class EventService
{
    public function __construct(
        private readonly EventRepositoryInterface $repository
    ) {}

    /**
     * @return array<string, mixed>[]
     */
    public function getEventsSummary(
        ?string $city     = null,
        ?string $dateFrom = null,
        ?string $dateTo   = null,
        ?string $category = null,
    ): array {
        $events  = $this->repository->findByFilter($city, $dateFrom, $dateTo, $category);
        $grouped = [];

        foreach ($events as $event) {
            if ($event->status !== 'confirmed') {
                continue;
            }

            $id = $event->eventId;

            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'eventId'      => $id,
                    'eventDate'    => $event->eventDate,
                    'city'         => $event->city,
                    'category'     => $event->category,
                    'totalTickets' => 0,
                ];
            }

            $grouped[$id]['totalTickets'] += $event->ticketQty;
        }

        usort($grouped, static fn($a, $b) => strcmp($a['eventDate'], $b['eventDate']));

        return array_values($grouped);
    }
}
