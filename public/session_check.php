<?php
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session data: " . json_encode($_SESSION) . "\n";
echo "Cookies: " . json_encode($_COOKIE) . "\n";
?>