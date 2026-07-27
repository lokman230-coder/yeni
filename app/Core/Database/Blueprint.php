<?php

declare(strict_types=1);

namespace App\Core\Database;

final class Blueprint
{
    private string $table;
    private bool $isAlter;
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];
    private ?string $engine = 'InnoDB';
    private ?string $charset = 'utf8mb4';
    private ?string $collation = 'utf8mb4_unicode_ci';

    public function __construct(string $table, bool $isAlter = false)
    {
        $this->table = $table;
        $this->isAlter = $isAlter;
    }

    // ---- Column types ----

    public function id(string $name = 'id'): ColumnDef
    {
        $c = new ColumnDef($name, 'BIGINT UNSIGNED');
        $c->raw('AUTO_INCREMENT PRIMARY KEY');
        $this->columns[] = $c;
        return $c;
    }

    public function bigInt(string $name, bool $unsigned = false): ColumnDef
    {
        return $this->addCol($name, 'BIGINT' . ($unsigned ? ' UNSIGNED' : ''));
    }

    public function integer(string $name, bool $unsigned = false): ColumnDef
    {
        return $this->addCol($name, 'INT' . ($unsigned ? ' UNSIGNED' : ''));
    }

    public function tinyInt(string $name, bool $unsigned = false): ColumnDef
    {
        return $this->addCol($name, 'TINYINT' . ($unsigned ? ' UNSIGNED' : ''));
    }

    public function boolean(string $name): ColumnDef
    {
        return $this->addCol($name, 'TINYINT(1)')->default(0);
    }

    public function string(string $name, int $length = 255): ColumnDef
    {
        return $this->addCol($name, "VARCHAR({$length})");
    }

    public function text(string $name): ColumnDef
    {
        return $this->addCol($name, 'TEXT');
    }

    public function longText(string $name): ColumnDef
    {
        return $this->addCol($name, 'LONGTEXT');
    }

    public function json(string $name): ColumnDef
    {
        return $this->addCol($name, 'JSON');
    }

    public function decimal(string $name, int $precision = 14, int $scale = 4): ColumnDef
    {
        return $this->addCol($name, "DECIMAL({$precision},{$scale})");
    }

    public function float(string $name): ColumnDef
    {
        return $this->addCol($name, 'FLOAT');
    }

    public function date(string $name): ColumnDef
    {
        return $this->addCol($name, 'DATE');
    }

    public function dateTime(string $name): ColumnDef
    {
        return $this->addCol($name, 'DATETIME');
    }

    public function timestamp(string $name): ColumnDef
    {
        return $this->addCol($name, 'TIMESTAMP');
    }

    public function timestamps(): void
    {
        $this->addCol('created_at', 'DATETIME')->nullable()->default('CURRENT_TIMESTAMP', true);
        $this->addCol('updated_at', 'DATETIME')->nullable()->onUpdateCurrent();
    }

    public function softDeletes(): void
    {
        $this->addCol('deleted_at', 'DATETIME')->nullable();
    }

    public function enum(string $name, array $values): ColumnDef
    {
        $vals = "'" . implode("','", $values) . "'";
        return $this->addCol($name, "ENUM({$vals})");
    }

    public function char(string $name, int $length = 1): ColumnDef
    {
        return $this->addCol($name, "CHAR({$length})");
    }

    public function foreignId(string $name): ColumnDef
    {
        return $this->addCol($name, 'BIGINT UNSIGNED');
    }

    private function addCol(string $name, string $type): ColumnDef
    {
        $c = new ColumnDef($name, $type);
        $this->columns[] = $c;
        return $c;
    }

    // ---- Indexes ----

    public function index(string|array $columns, ?string $name = null): void
    {
        $cols = (array) $columns;
        $name ??= 'idx_' . $this->table . '_' . implode('_', $cols);
        $this->indexes[] = ['type' => 'INDEX', 'name' => $name, 'columns' => $cols];
    }

    public function unique(string|array $columns, ?string $name = null): void
    {
        $cols = (array) $columns;
        $name ??= 'uq_' . $this->table . '_' . implode('_', $cols);
        $this->indexes[] = ['type' => 'UNIQUE', 'name' => $name, 'columns' => $cols];
    }

    public function fullText(string|array $columns, ?string $name = null): void
    {
        $cols = (array) $columns;
        $name ??= 'ft_' . $this->table . '_' . implode('_', $cols);
        $this->indexes[] = ['type' => 'FULLTEXT', 'name' => $name, 'columns' => $cols];
    }

    public function foreign(string $column, string $refTable, string $refColumn = 'id', string $onDelete = 'CASCADE', string $onUpdate = 'CASCADE'): void
    {
        $name = 'fk_' . $this->table . '_' . $column;
        $this->foreignKeys[] = compact('name', 'column', 'refTable', 'refColumn', 'onDelete', 'onUpdate');
    }

    // ---- SQL generation ----

    public function toCreateSql(): string
    {
        $lines = [];
        foreach ($this->columns as $c) {
            $lines[] = $c->toSql();
        }
        foreach ($this->indexes as $idx) {
            $cols = '`' . implode('`, `', $idx['columns']) . '`';
            $lines[] = "{$idx['type']} `{$idx['name']}` ({$cols})";
        }
        foreach ($this->foreignKeys as $fk) {
            $lines[] = sprintf(
                'CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE %s ON UPDATE %s',
                $fk['name'], $fk['column'], $fk['refTable'], $fk['refColumn'], $fk['onDelete'], $fk['onUpdate']
            );
        }

        $body = implode(",\n  ", $lines);
        return "CREATE TABLE `{$this->table}` (\n  {$body}\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation}";
    }

    public function toAlterSqls(): array
    {
        $sqls = [];
        foreach ($this->columns as $c) {
            $sqls[] = "ALTER TABLE `{$this->table}` ADD COLUMN " . $c->toSql();
        }
        foreach ($this->indexes as $idx) {
            $cols = '`' . implode('`, `', $idx['columns']) . '`';
            $sqls[] = "ALTER TABLE `{$this->table}` ADD {$idx['type']} `{$idx['name']}` ({$cols})";
        }
        foreach ($this->foreignKeys as $fk) {
            $sqls[] = sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE %s ON UPDATE %s',
                $this->table, $fk['name'], $fk['column'], $fk['refTable'], $fk['refColumn'], $fk['onDelete'], $fk['onUpdate']
            );
        }
        return $sqls;
    }
}
