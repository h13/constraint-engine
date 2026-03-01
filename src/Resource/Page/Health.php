<?php

declare(strict_types=1);

namespace ConstraintEngine\App\Resource\Page;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\ResourceObject;
use PDOException;

class Health extends ResourceObject
{
    public function __construct(
        private readonly ExtendedPdoInterface $pdo,
    ) {
    }

    public function onGet(): static
    {
        try {
            $this->pdo->fetchValue('SELECT 1');
            $this->body = [
                'status' => 'ok',
                'db' => 'connected',
            ];
        } catch (PDOException) {
            $this->code = 503;
            $this->body = [
                'status' => 'error',
                'db' => 'disconnected',
            ];
        }

        return $this;
    }
}
