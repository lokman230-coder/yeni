<?php

declare(strict_types=1);

namespace App\Modules\Ticket\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Ticket\Services\TicketService;
use App\Services\Auth\AuthService;

final class TicketController
{
    public function customerList(Request $request): Response
    {
        $customer = AuthService::customer();
        $view = new View();
        return Response::html($view->render('ticket::customer.list', [
            'title'   => 'Destek Taleplerim',
            'tickets' => TicketService::forCustomer((int)$customer['id']),
        ]));
    }

    public function customerNew(Request $request): Response
    {
        $view = new View();
        return Response::html($view->render('ticket::customer.new', [
            'title' => 'Yeni Destek Talebi',
            'departments' => TicketService::departments(),
        ]));
    }

    public function customerCreate(Request $request): Response
    {
        $customer = AuthService::customer();
        $subject = trim((string)$request->input('subject', ''));
        $message = trim((string)$request->input('message', ''));
        $deptId  = (int)$request->input('department_id', 0) ?: null;
        $priority = (string)$request->input('priority', 'medium');

        if ($subject === '' || $message === '') {
            SessionManager::flash('error', 'Konu ve mesaj zorunlu.');
            return Response::redirect('/panel/destek/yeni');
        }

        $id = TicketService::create((int)$customer['id'], $subject, $message, $deptId, $priority);
        SessionManager::flash('success', 'Talebiniz oluşturuldu.');
        return Response::redirect('/panel/destek/' . $id);
    }

    public function customerShow(Request $request): Response
    {
        $customer = AuthService::customer();
        $ticket = TicketService::find((int)$request->param('id'));
        if (!$ticket || (int)$ticket['customer_id'] !== (int)$customer['id']) {
            return Response::notFound();
        }
        $view = new View();
        return Response::html($view->render('ticket::customer.show', [
            'title'       => $ticket['subject'],
            'ticket'      => $ticket,
            'replies'     => TicketService::replies((int)$ticket['id'], includeInternal: false),
            'attachments' => \App\Modules\Ticket\Services\AttachmentService::forTicket((int)$ticket['id']),
        ]));
    }

    public function customerReply(Request $request): Response
    {
        $customer = AuthService::customer();
        $ticket = TicketService::find((int)$request->param('id'));
        if (!$ticket || (int)$ticket['customer_id'] !== (int)$customer['id']) return Response::notFound();
        $msg = trim((string)$request->input('message', ''));

        $replyId = null;
        if ($msg !== '') {
            $replyId = TicketService::reply((int)$ticket['id'], 'customer', (int)$customer['id'], $msg);
        }

        // Dosya eki yükleme
        if (!empty($_FILES['attachment']['name'])) {
            $r = \App\Modules\Ticket\Services\AttachmentService::save(
                (int)$ticket['id'], $replyId, 'customer', (int)$customer['id'], $_FILES['attachment']
            );
            if (!$r['ok']) {
                SessionManager::flash('error', 'Dosya: ' . $r['error']);
            } else {
                SessionManager::flash('success', 'Yanıt + dosya gönderildi.');
            }
        } elseif ($msg !== '') {
            SessionManager::flash('success', 'Yanıt gönderildi.');
        }
        return Response::redirect('/panel/destek/' . $ticket['id']);
    }

    /** Ek dosya indir (hem müşteri hem admin — yetki kontrol) */
    public function downloadAttachment(Request $request): Response
    {
        $ticketId = (int) $request->param('id');
        $attId = (int) $request->param('att');
        $ticket = TicketService::find($ticketId);
        if (!$ticket) return Response::notFound();

        // Yetki: müşteri kendi ticket'ı veya admin
        $isOwnerCustomer = AuthService::isCustomer() && (int)AuthService::customer()['id'] === (int)$ticket['customer_id'];
        $isAdmin = AuthService::isAdmin();
        if (!$isOwnerCustomer && !$isAdmin) return Response::notFound();

        $file = \App\Modules\Ticket\Services\AttachmentService::fetch($attId, $ticketId);
        if (!$file) return Response::notFound('Dosya yok');

        return Response::make(file_get_contents($file['path']), 200, [
            'Content-Type'        => $file['mime'],
            'Content-Disposition' => 'attachment; filename="' . $file['name'] . '"',
            'Content-Length'      => (string) filesize($file['path']),
        ]);
    }

    // ---- ADMIN ----

    public function adminList(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $view = new View();
        return Response::html($view->render('ticket::admin.list', [
            'title'   => 'Destek Talepleri',
            'tickets' => TicketService::forAdmin($status !== '' ? $status : null),
            'status'  => $status,
        ]));
    }

    public function adminShow(Request $request): Response
    {
        $ticket = TicketService::find((int)$request->param('id'));
        if (!$ticket) return Response::notFound();
        $view = new View();
        return Response::html($view->render('ticket::admin.show', [
            'title'       => $ticket['subject'],
            'ticket'      => $ticket,
            'replies'     => TicketService::replies((int)$ticket['id']),
            'attachments' => \App\Modules\Ticket\Services\AttachmentService::forTicket((int)$ticket['id']),
        ]));
    }

    public function adminReply(Request $request): Response
    {
        $admin = AuthService::admin();
        $ticket = TicketService::find((int)$request->param('id'));
        if (!$ticket) return Response::notFound();
        $msg = trim((string)$request->input('message', ''));
        $isInternal = $request->input('is_internal') === '1';

        $replyId = null;
        if ($msg !== '') {
            $replyId = TicketService::reply((int)$ticket['id'], 'admin', (int)$admin['id'], $msg, $isInternal);
            SessionManager::flash('success', $isInternal ? 'İç not eklendi (müşteri görmez).' : 'Yanıt gönderildi.');
        }
        if (!empty($_FILES['attachment']['name'])) {
            $r = \App\Modules\Ticket\Services\AttachmentService::save(
                (int)$ticket['id'], $replyId, 'admin', (int)$admin['id'], $_FILES['attachment']
            );
            if (!$r['ok']) SessionManager::flash('error', 'Dosya: ' . $r['error']);
        }
        if ($request->input('close') === '1') {
            TicketService::close((int)$ticket['id']);
            SessionManager::flash('success', 'Talep kapatıldı.');
        }
        return Response::redirect('/admin/destek-merkezi/' . $ticket['id']);
    }

    /** Admin: durum / öncelik / atama değiştir */
    public function adminUpdate(Request $request): Response
    {
        $ticket = TicketService::find((int)$request->param('id'));
        if (!$ticket) return Response::notFound();
        $updates = [];
        $status = (string) $request->input('status', '');
        if (in_array($status, ['open','answered','customer_reply','on_hold','closed'], true)) {
            $updates['status'] = $status;
            if ($status === 'closed') $updates['closed_at'] = date('Y-m-d H:i:s');
        }
        $priority = (string) $request->input('priority', '');
        if (in_array($priority, ['low','medium','high','urgent'], true)) {
            $updates['priority'] = $priority;
        }
        if ($updates) {
            \App\Core\Database\Connection::update('tickets', $updates, 'id = ?', [$ticket['id']]);
            SessionManager::flash('success', 'Ticket güncellendi.');
        }
        return Response::redirect('/admin/destek-merkezi/' . $ticket['id']);
    }
}
