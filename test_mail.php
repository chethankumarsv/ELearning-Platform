<?php
require __DIR__ . '/includes/mailer.php';

if (send_reset_email('youremail@gmail.com', 'Test User', 'testtoken123')) {
    echo 'Mail sent';
} else {
    echo 'Mail failed';
}
