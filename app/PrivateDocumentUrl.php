<?php

namespace App;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\URL;

class PrivateDocumentUrl
{
    /** @param array<string, mixed> $options */
    public function __invoke(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        return URL::temporarySignedRoute('documents.preview',
            CarbonImmutable::createFromTimestamp(min($expiration->getTimestamp(), now()->addMinutes(30)->getTimestamp())),
            ['path' => $path],
        );
    }
}
