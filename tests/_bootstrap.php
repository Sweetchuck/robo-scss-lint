<?php
// This is global bootstrap for autoloading

call_user_func(function () {
    exec('ulimit -S -n 4096', $stdOutput, $exitCode);
    if ($exitCode) {
        throw new \Exception('Failed to set the ulimut to 4096');
    }
});
