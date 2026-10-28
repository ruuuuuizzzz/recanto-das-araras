<?php
require __DIR__.'/../../config/database.php'; require_post();
audit($conn,'usuarios',(int)$_SESSION['user']['id'],'logout');
$_SESSION=[]; session_destroy(); header('Location: '.base_url('login.php'));
