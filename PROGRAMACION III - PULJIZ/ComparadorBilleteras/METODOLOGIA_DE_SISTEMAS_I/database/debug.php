<?php
foreach ($_ENV as $k => $v) {
    if (stripos($k, 'MYSQL') !== false || stripos($k, 'DATABASE') !== false) {
        echo "$k = $v\n";
    }
}
