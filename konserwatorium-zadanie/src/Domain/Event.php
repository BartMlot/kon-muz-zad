<?php

declare(strict_types=1);

namespace App\Domain;

final class Event
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventDate,
        public readonly string $city,
        public readonly string $category,
        public readonly string $orderId,
        public readonly int    $ticketQty,
        public readonly string $status,
        public readonly string $utmSource,
        public readonly string $utmCampaign,
        public readonly string $utmContent,
        public readonly bool   $soldOut,
    ) {}
}
