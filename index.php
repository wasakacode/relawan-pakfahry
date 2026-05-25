<?php
require_once __DIR__ . '/auth/auth.php';

if (is_login()) {
    redirect('dashboard/index.php');
}

redirect('login.php');