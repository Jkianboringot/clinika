<?php




$ts = @file_get_contents(__DIR__ . '/queue.php') ?: '';

$ts = preg_replace('#/\*.*?\*/#s', '', $ts);

$get = function (string $key) use ($ts) {
    return preg_match("/\b{$key}\b\s*:\s*'([^']*)'/", $ts, $m) ? $m[1] : null;
};

return [
    'url'   => $get('url'),
    'id'    => $get('id'),
    'token' =>  $get('token'),
];