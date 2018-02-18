<?php

/* generate a nonce which should be completely unpredictable, for use by inline script tags */
$csp_nonce = substr(base64_encode(hash("sha256", $_SERVER["UNIQUE_ID"] . $_SERVER["REQUEST_TIME_FLOAT"] . openssl_random_pseudo_bytes(6), true)), 0, 20); 

$csp = "Content-Security-Policy: default-src 'none'; " .
    "connect-src 'self'; img-src 'self'; " .
    "font-src https://cdnjs.cloudflare.com https://fonts.gstatic.com; " .
    "script-src 'self' 'nonce-{$csp_nonce}'  https://cdnjs.cloudflare.com https://ajax.googleapis.com; " .
    "style-src 'self' https://cdnjs.cloudflare.com https://ajax.googleapis.com https://fonts.googleapis.com " .
    "'sha256-UiXlt9djFx1o7crFtCH7sUqquV6B2BX9ozY9jqs43JE=' 'sha256-UiXlt9djFx1o7crFtCH7sUqquV6B2BX9ozY9jqs43JE=' " .
    "'sha256-t6oewASd7J1vBg5mQtX4hl8bg8FeegYFM3scKLIhYUc=' 'sha256-47DEQpj8HBSa+/TImW+5JCeuQeRkm5NMpJWZG3hSuFU='; ";
/* the last 2 lines above are a hack to allow inline styles such as "style='width:100%'" but need a better more scalable solution */
header($csp);
