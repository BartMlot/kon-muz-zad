<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Event;
use RuntimeException;

class CsvEventRepository implements EventRepositoryInterface
{
    /** @var Event[]|null */
    private ?array $cache = null;

    public function __construct(
        private readonly string $csvPath,
        private readonly array  $columnMapping,
    ) {}

    public function findAll(): array
    {
        return $this->loadAll();
    }

    public function findByFilter(
        ?string $city      = null,
        ?string $dateFrom  = null,
        ?string $dateTo    = null,
        ?string $category  = null,
    ): array {
        return array_values(
            array_filter(
                $this->loadAll(),
                function (Event $e) use ($city, $dateFrom, $dateTo, $category): bool {
                    if ($city !== null && $this->normalize($e->city) !== $this->normalize($city)) {
                        return false;
                    }
                    if ($dateFrom !== null && $e->eventDate < $dateFrom) {
                        return false;
                    }
                    if ($dateTo !== null && $e->eventDate > $dateTo) {
                        return false;
                    }
                    if ($category !== null && $e->category !== strtolower($category)) {
                        return false;
                    }
                    return true;
                }
            )
        );
    }

    private function loadAll(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $this->cache = $this->parseAndMap();
        return $this->cache;
    }

    /** @throws RuntimeException */
    private function parseAndMap(): array
    {
        if (!file_exists($this->csvPath) || !is_readable($this->csvPath)) {
            throw new RuntimeException("CSV file not found or not readable: {$this->csvPath}");
        }

        $handle = fopen($this->csvPath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Cannot open CSV file: {$this->csvPath}");
        }

        try {
            return $this->readRows($handle);
        } finally {
            fclose($handle);
        }
    }

    /** @param resource $handle */
    private function readRows($handle): array
    {
        $headers = fgetcsv($handle);
        if ($headers === false || $headers === [null]) {
            throw new RuntimeException("CSV file is empty or has no header row");
        }

        $missingColumns = array_diff(array_values($this->columnMapping), $headers);
        if (!empty($missingColumns)) {
            throw new RuntimeException(
                "CSV is missing required columns: " . implode(', ', $missingColumns)
            );
        }

        $headerIndex = array_flip($headers);
        $events      = [];
        $lineNumber  = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            if ($row === [null]) {
                continue;
            }

            try {
                $events[] = $this->createEvent($row, $headerIndex);
            } catch (\Throwable $e) {
                error_log("[CsvEventRepository] Skipping malformed row {$lineNumber}: {$e->getMessage()}");
            }
        }

        return $events;
    }

    private function normalize(string $value): string
    {
        $map = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
            'Ą' => 'a', 'Ć' => 'c', 'Ę' => 'e', 'Ł' => 'l', 'Ń' => 'n',
            'Ó' => 'o', 'Ś' => 's', 'Ź' => 'z', 'Ż' => 'z',
        ];

        return strtolower(strtr($value, $map));
    }

    private function createEvent(array $row, array $headerIndex): Event
    {
        $col = function (string $domainKey) use ($row, $headerIndex): string {
            $csvHeader   = $this->columnMapping[$domainKey];
            $columnIndex = $headerIndex[$csvHeader];
            return trim($row[$columnIndex] ?? '');
        };

        return new Event(
            eventId:     $col('eventId'),
            eventDate:   $col('eventDate'),
            city:        $col('city'),
            category:    strtolower($col('category')),
            orderId:     $col('orderId'),
            ticketQty:   (int) $col('ticketQty'),
            status:      strtolower($col('status')),
            utmSource:   $col('utmSource'),
            utmCampaign: $col('utmCampaign'),
            utmContent:  $col('utmContent'),
            soldOut:     strtolower($col('soldOut')) === 'true',
        );
    }
}
