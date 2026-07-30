<?php

declare(strict_types=1);

namespace App\Modules\Contact\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Services\Logger\Logger;

final class ContactController
{
    public function show(Request $request): Response
    {
        $view = new View();
        return Response::html($view->render('contact::form', [
            'title' => 'İletişim',
        ]));
    }

    public function submit(Request $request): Response
    {
        $data = [
            'name'    => trim((string)$request->input('name', '')),
            'email'   => trim((string)$request->input('email', '')),
            'phone'   => trim((string)$request->input('phone', '')),
            'subject' => trim((string)$request->input('subject', '')),
            'message' => trim((string)$request->input('message', '')),
        ];

        $errors = [];
        if ($data['name'] === '') $errors[] = 'İsim zorunlu.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli e-posta gerekli.';
        if ($data['message'] === '' || mb_strlen($data['message']) < 10) $errors[] = 'Mesaj en az 10 karakter olmalı.';

        if ($errors) {
            SessionManager::flash('error', implode(' ', $errors));
            SessionManager::flash('_old', $data);
            return Response::redirect('/iletisim');
        }

        Logger::info('Contact form submission', $data);
        SessionManager::flash('success', 'Mesajınız alındı. En kısa sürede dönüş yapılacaktır.');
        return Response::redirect('/iletisim');
    }
}
