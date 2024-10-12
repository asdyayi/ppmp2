<?php
function log_user_activity($user_id, $action) {
    $log_file = 'user_activity.log';
    $timestamp = date("Y-m-d H:i:s");
    $log_entry = "User ID: $user_id | Action: $action | Timestamp: $timestamp" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
?>
