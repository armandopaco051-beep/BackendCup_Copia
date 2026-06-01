<?php

namespace App\Models\Concerns;

trait HasPostgresSchema
{
    public function getTable(): string
    {
        $table = parent::getTable();

        if (! isset($this->schema) || $this->schema === '') {
            return $table;
        }

        if (str_contains($table, '.')) {
            return $table;
        }

        return $this->schema.'.'.$table;
    }
}
