<?php

use App\Core\Database\Connection;
use App\Services\Auth\PasswordHasher;

return new class {
    public function run(): void {
        $email = 'admin@ahost.web.tr';
        $exists = Connection::selectOne("SELECT id FROM admins WHERE email = ?", [$email]);
        if ($exists) return;

        $role = Connection::selectOne("SELECT id FROM admin_roles WHERE slug = 'super_admin'");
        $roleId = $role['id'] ?? null;

        Connection::insert('admins', [
            'username'      => 'admin',
            'email'         => $email,
            'password_hash' => PasswordHasher::hash('AhostOne2026!'),
            'full_name'     => 'Süper Admin',
            'role_id'       => $roleId,
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        echo "  → Admin oluşturuldu: {$email} / AhostOne2026!" . PHP_EOL;
    }
};
