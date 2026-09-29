<?php

/**
 * If the vhost document root is the project folder (…/backend) instead of
 * …/backend/public, the server runs this file first. Laravel 11+ has no
 * Http Kernel; the old skeleton entry here would fatal. Delegate to public.
 */
require __DIR__.'/public/index.php';
