<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\EventRepositoryInterface;

class UtmRankingService
{
    public function __construct(
        private readonly EventRepositoryInterface $repository
    ) {}

    /**
     * @return array{rank: int, campaign: string, totalTickets: int}[]
     */
    public function getTop10Campaigns(?string $status = null): array
    {
        $events  = $this->repository->findAll();
        $ranking = [];

        foreach ($events as $event) {
            if ($event->utmCampaign === '') {
                continue;
            }

            if ($status !== null && $event->status !== $status) {
                continue;
            }

            $ranking[$event->utmCampaign] = ($ranking[$event->utmCampaign] ?? 0) + $event->ticketQty;
        }

        arsort($ranking);

        $result = [];
        $rank   = 1;

        foreach ($ranking as $campaign => $total) {
            $result[] = [
                'rank'         => $rank,
                'campaign'     => $campaign,
                'totalTickets' => $total,
            ];

            if (++$rank > 10) {
                break;
            }
        }

        return $result;
    }
}
