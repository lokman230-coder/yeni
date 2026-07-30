<?php

declare(strict_types=1);

namespace App\Core\Database;

final class ColumnDef
{
    private string $name;
    private string $type;
    private bool $nullable = false;
    private mixed $default = null;
    private bool $defaultRaw = false;
    private bool $hasDefault = false;
    private ?string $raw = null;
    private bool $onUpdateCurrent = false;
    private ?string $comment = null;

    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function nullable(bool $value = true): self
    {
        $this->nullable = $value;
        return $this;
    }

    public function default(mixed $value, bool $raw = false): self
    {
        $this->default = $value;
        $this->defaultRaw = $raw;
        $this->hasDefault = true;
        return $this;
    }

    public function onUpdateCurrent(): self
    {
        $this->onUpdateCurrent = true;
        return $this;
    }

    public function raw(string $sql): self
    {
        $this->raw = $sql;
        return $this;
    }

    public function comment(string $text): self
    {
        $this->comment = $text;
        return $this;
    }

    public function toSql(): string
    {
        $parts = ["`{$this->name}`", $this->type];

        if (!$this->nullable) {
            $parts[] = 'NOT NULL';
        } else {
            $parts[] = 'NULL';
        }

        if ($this->hasDefault) {
            if ($this->defaultRaw) {
                $parts[] = 'DEFAULT ' . $this->default;
            } elseif (is_bool($this->default)) {
                $parts[] = 'DEFAULT ' . ($this->default ? '1' : '0');
            } elseif (is_null($this->default)) {
                $parts[] = 'DEFAULT NULL';
            } elseif (is_numeric($this->default)) {
                $parts[] = 'DEFAULT ' . $this->default;
            } else {
                $parts[] = "DEFAULT '" . addslashes((string)$this->default) . "'";
            }
        }

        if ($this->onUpdateCurrent) {
            $parts[] = 'ON UPDATE CURRENT_TIMESTAMP';
        }

        if ($this->raw !== null) {
            $parts[] = $this->raw;
        }

        if ($this->comment !== null) {
            $parts[] = "COMMENT '" . addslashes($this->comment) . "'";
        }

        return implode(' ', $parts);
    }

    public function name(): string { return $this->name; }
}
