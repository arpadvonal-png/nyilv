<?php

declare(strict_types=1);

final class Request
{
    public static function body(): array
    {
        $rawBody = file_get_contents('php://input');
        if ($rawBody === false || trim($rawBody) === '') {
            return [];
        }

        $data = json_decode($rawBody, true);
        return is_array($data) ? $data : [];
    }

    public static function requireFields(array $data, array $fields): array
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }
}

