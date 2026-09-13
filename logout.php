<?php
require_once __DIR__ . '/auth.php';
lunora_logout();
header('Location: index.php');
exit;
