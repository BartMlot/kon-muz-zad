<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Event;

interface EventRepositoryInterface
{
    /** @return Event[] */
    public function findAll(): array;

    /** @return Event[] */
    public function findByFilter(
        ?string $city      = null,
        ?string $dateFrom  = null,
        ?string $dateTo    = null,
        ?string $category  = null,
    ): array;
}
