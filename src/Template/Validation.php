<?php

namespace Nepf2\Template;

class Validation
{
    private array $results;
    private bool $success;

    public function __construct(array &$results)
    {
        $this->results = &$results;
        $this->success = true;
    }

    public function test(string $field, bool $condition, string $message): self
    {
        if ($condition) {
            $this->results[$field] = true;
        } else {
            $this->results[$field] = $message;
            $this->success = false;
        }
        return $this;
    }

    public function succeeded(): bool
    {
        return $this->success;
    }

}