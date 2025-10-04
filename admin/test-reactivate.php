<?php
http_response_code(410);
header('Content-Type: text/plain');
echo 'admin/test-reactivate.php has been removed. Use the main UI to manage licenses.';
