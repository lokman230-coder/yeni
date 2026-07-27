<?php

declare(strict_types=1);

namespace App\Modules\Ticket\Services;

use App\Core\Database\Connection;

final class TicketService
{
    public static function departments(): array
    {
        try {
            return Connection::select("SELECT * FROM ticket_departments WHERE is_active = 1 ORDER BY sort_order, name");
        } catch (\Throwable) { return []; }
    }

    public static function forCustomer(int $customerId, ?string $status = null): array
    {
        $where = 'customer_id = ?'; $p = [$customerId];
        if ($status) { $where .= ' AND status = ?'; $p[] = $status; }
        try {
            return Connection::select(
                "SELECT t.*, d.name AS dept_name FROM tickets t
                 LEFT JOIN ticket_departments d ON d.id = t.department_id
                 WHERE t.{$where} ORDER BY t.updated_at DESC",
                $p
            );
        } catch (\Throwable) { return []; }
    }

    public static function forAdmin(?string $status = null): array
    {
        $where = '1=1'; $p = [];
        if ($status) { $where .= ' AND t.status = ?'; $p[] = $status; }
        try {
            return Connection::select(
                "SELECT t.*, c.email AS customer_email, c.first_name, c.last_name, d.name AS dept_name
                 FROM tickets t
                 JOIN customers c ON c.id = t.customer_id
                 LEFT JOIN ticket_departments d ON d.id = t.department_id
                 WHERE {$where} ORDER BY t.updated_at DESC LIMIT 200",
                $p
            );
        } catch (\Throwable) { return []; }
    }

    public static function find(int $id): ?array
    {
        try {
            return Connection::selectOne(
                "SELECT t.*, c.email AS customer_email, c.first_name, c.last_name, d.name AS dept_name
                 FROM tickets t
                 JOIN customers c ON c.id = t.customer_id
                 LEFT JOIN ticket_departments d ON d.id = t.department_id
                 WHERE t.id = ?", [$id]
            );
        } catch (\Throwable) { return null; }
    }

    /**
     * @param bool $includeInternal Admin ise true (iç notları da göster), müşteri ise false
     */
    public static function replies(int $ticketId, bool $includeInternal = true): array
    {
        try {
            $sql = "SELECT * FROM ticket_replies WHERE ticket_id = ?";
            if (!$includeInternal) $sql .= " AND is_internal = 0";
            $sql .= " ORDER BY created_at ASC";
            return Connection::select($sql, [$ticketId]);
        } catch (\Throwable) { return []; }
    }

    public static function create(int $customerId, string $subject, string $message, ?int $departmentId = null, string $priority = 'medium'): int
    {
        Connection::beginTransaction();
        try {
            $ticketId = Connection::insert('tickets', [
                'ticket_number' => 'TKT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
                'department_id' => $departmentId,
                'customer_id'   => $customerId,
                'subject'       => mb_substr($subject, 0, 255),
                'priority'      => in_array($priority, ['low','medium','high','urgent'], true) ? $priority : 'medium',
                'status'        => 'open',
                'last_reply_at' => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            Connection::insert('ticket_replies', [
                'ticket_id'   => $ticketId,
                'author_type' => 'customer',
                'author_id'   => $customerId,
                'message'     => $message,
                'is_internal' => 0,
            ]);
            Connection::commit();
            return $ticketId;
        } catch (\Throwable $e) {
            Connection::rollback();
            throw $e;
        }
    }

    public static function reply(int $ticketId, string $authorType, int $authorId, string $message, bool $internal = false): int
    {
        $rid = Connection::insert('ticket_replies', [
            'ticket_id'   => $ticketId,
            'author_type' => $authorType,
            'author_id'   => $authorId,
            'message'     => $message,
            'is_internal' => $internal ? 1 : 0,
        ]);
        $newStatus = $authorType === 'admin' ? 'answered' : 'customer_reply';
        Connection::update('tickets', [
            'status'        => $newStatus,
            'last_reply_at' => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ], 'id = ?', [$ticketId]);
        return $rid;
    }

    public static function close(int $ticketId): void
    {
        Connection::update('tickets', [
            'status'     => 'closed',
            'closed_at'  => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$ticketId]);
    }
}
