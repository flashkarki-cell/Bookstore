<?php
require 'config.php';
session_destroy();
redirect('auth.php');
?>
