<?php
require 'vendor/autoload.php';

use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Random;

function clear_screen() {
    if (PHP_OS_FAMILY == 'Windows') {
        system('cls');
    } else {
        system('clear');
    }
}

$green = "\033[32m";
$yellow = "\033[33m";
$red = "\033[31m";
$reset = "\033[0m";

function get_password($green, $yellow, $red, $reset) {
    echo $yellow . "Apakah Anda ingin memasukkan password sendiri? (y/n): " . $reset;
    $key_choice = trim(fgets(STDIN));

    if (strtolower($key_choice) == 'y') {
        while (true) {
            echo $yellow . "Masukkan password (16 karakter): " . $reset;
            $key_input = trim(fgets(STDIN));
            if (strlen($key_input) == 16) {
                return [ $key_input, $key_choice ];
            } else {
                echo $red . "Password harus 16 karakter.\n" . $reset;
            }
        }
    } else {
        return [ Random::string(16), $key_choice ];
    }
}

function encrypt($text, $key) {
    $aes = new AES('cbc');
    $aes->setKey($key);
    $iv = Random::string($aes->getBlockLength() >> 3);
    $aes->setIV($iv);

    $ciphertext = $aes->encrypt($text);
    return [ base64_encode($iv), base64_encode($ciphertext) ];
}

function create_decrypt_file($file_name, $iv, $ct, $key_hex, $key_choice, $green, $yellow, $red, $reset) {
    $file_name_without_extension = pathinfo($file_name, PATHINFO_FILENAME);

    if ($key_choice == 'y') {
        $decrypt_code = <<<PHP
<?php
require 'vendor/autoload.php';

use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Random;

function clear_screen() {
    if (PHP_OS_FAMILY == 'Windows') {
        system('cls');
    } else {
        system('clear');
    }

\$green = "\\033[32m";
\$yellow = "\\033[33m";
\$red = "\\033[31m";
\$reset = "\\033[0m";

function decrypt(\$iv, \$ct, \$key) {
    \$aes = new AES('cbc');
    \$aes->setKey(\$key);
    \$aes->setIV(base64_decode(\$iv));

    \$plaintext = \$aes->decrypt(base64_decode(\$ct));
    return \$plaintext;
}

\$iv = '$iv';
\$ct = '$ct';

clear_screen();
echo "\\n\$green===== Dibutuhkan Password =====\\n  by Dione\\n  Versi: 1.2\\n\$reset";
echo "Password: ";
\$key_input = trim(fgets(STDIN));
if (strlen(\$key_input) != 16) {
    echo "\$redPassword harus 16 karakter.\\n\$reset";
    exit(1);
}
try {
    \$key = \$key_input;
    \$decrypted_text = decrypt(\$iv, \$ct, \$key);
    clear_screen();
    echo "\$greenDekripsi berhasil...\\n\$reset";
    sleep(3);
    clear_screen();
    eval("?>".\$decrypted_text);
} catch (Exception \$e) {
    echo "\$redError: ".\$e->getMessage()."\\n\$reset";
}
PHP;
    } else {
        $decrypt_code = <<<PHP
<?php
require 'vendor/autoload.php';

use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Random;

function clear_screen() {
    if (PHP_OS_FAMILY == 'Windows') {
        system('cls');
    } else {
        system('clear');
    }

\$green = "\\033[32m";
\$yellow = "\\033[33m";
\$red = "\\033[31m";
\$reset = "\\033[0m";

function decrypt(\$iv, \$ct, \$key) {
    \$aes = new AES('cbc');
    \$aes->setKey(\$key);
    \$aes->setIV(base64_decode(\$iv));

    \$plaintext = \$aes->decrypt(base64_decode(\$ct));
    return \$plaintext;
}

\$iv = '$iv';
\$ct = '$ct';

clear_screen();
echo "\\n\$green===== Dibutuhkan Password =====\\n  by Dione\\n  Versi: 1.2\\n\$reset";
echo "Password: ";
\$key_hex = trim(fgets(STDIN));
if (strlen(\$key_hex) != 32) {
    echo "\$redKunci harus 32 karakter (16 byte dalam format hex).\\n\$reset";
    exit(1);
}
try {
    \$key = hex2bin(\$key_hex);
    \$decrypted_text = decrypt(\$iv, \$ct, \$key);
    clear_screen();
    echo "\$greenDekripsi berhasil...\\n\$reset";
    sleep(3);
    clear_screen();
    eval("?>".\$decrypted_text);
} catch (Exception \$e) {
    echo "\$redError: ".\$e->getMessage()."\\n\$reset";
}
PHP;
    }

    $decrypt_file_name = $file_name_without_extension . ".decrypt.php";
    file_put_contents($decrypt_file_name, $decrypt_code);
}

function main() {
    global $green, $yellow, $red, $reset;

    clear_screen();
    echo $green . "\t===== Program Enkripsi =====\n  by Dione\n  Versi: 1.2\n" . $reset;
    echo $yellow . "Nama File: " . $reset;
    $file_path = trim(fgets(STDIN));

    $text = file_get_contents($file_path);

    list($key, $key_choice) = get_password($green, $yellow, $red, $reset);
    echo $yellow . "> Simpan Key di Tempat yang aman!!\n" . $reset;
    $key_hex = bin2hex($key);
    echo $green . "Key (hex): $key_hex\n" . $reset;

    list($iv, $ciphertext) = encrypt($text, $key);
    echo $green . "Encrypted: $ciphertext\n" . $reset;

    create_decrypt_file($file_path, $iv, $ciphertext, $key_hex, $key_choice, $green, $yellow, $red, $reset);
    echo $green . "File decrypt.php telah dibuat.\n" . $reset;
}

main();
?>