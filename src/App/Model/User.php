<?php

declare(strict_types=1);

namespace App\Model; // Note the namespace `App\App\Model` to match `src/App/App/Model`

class User
{
    public function __construct(
        public string $name,
        public string $email,
        public ?int $id = null,
        public ?string $createdAt = null
    ) {}

    // You can add methods here for data transformation or validation,
    // but typically no database-specific logic.
}
