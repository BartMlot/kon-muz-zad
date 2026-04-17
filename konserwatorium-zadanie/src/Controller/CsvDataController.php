<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\EventService;
use App\Service\UtmRankingService;
use InvalidArgumentException;

class CsvDataController
{
    public function __construct(
        private readonly EventService      $eventService,
        private readonly UtmRankingService $utmRankingService,
    ) {}

    public function handle(): void
    {
        $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'events';

        match ($action) {
            'events'               => $this->listEvents(),
            'utm-ranking'          => $this->utmRanking(),
            'utm-ranking-confirmed' => $this->utmRankingConfirmed(),
            default                => throw new InvalidArgumentException("Unknown action: '{$action}'"),
        };
    }

    private function listEvents(): void
    {
        $city     = $this->sanitizeString($_GET['city']     ?? '');
        $dateFrom = $this->validateDate($_GET['dateFrom']   ?? '');
        $dateTo   = $this->validateDate($_GET['dateTo']     ?? '');
        $category = $this->validateEnum($_GET['category']   ?? '', ['kids', 'adults', '']);

        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            throw new InvalidArgumentException("'data od' nie może być później niż 'data do'");
        }

        $data = $this->eventService->getEventsSummary(
            city:     $city     !== '' ? $city     : null,
            dateFrom: $dateFrom !== '' ? $dateFrom : null,
            dateTo:   $dateTo   !== '' ? $dateTo   : null,
            category: $category !== '' ? $category : null,
        );

        $this->jsonResponse($data);
    }

    private function utmRanking(): void
    {
        $this->jsonResponse($this->utmRankingService->getTop10Campaigns());
    }

    private function utmRankingConfirmed(): void
    {
        $this->jsonResponse($this->utmRankingService->getTop10Campaigns('confirmed'));
    }

    private function sanitizeString(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function validateDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException(
                "Niepoprawny format danych wejściowych: '{$value}'. Data powinna być w formacie RRRR-MM-DD."
            );
        }

        return $value;
    }

    private function validateEnum(string $value, array $allowed): string
    {
        if (!in_array($value, $allowed, strict: true)) {
            throw new InvalidArgumentException(
                "Niepoprawna kategoria: '{$value}'. Dozwolone: " . implode(', ', array_filter($allowed))
            );
        }

        return $value;
    }

    private function jsonResponse(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
