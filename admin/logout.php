<?php
require_once __DIR__ . '/../auth.php';
lunora_logout();
header('Location: login.php');
exit;
