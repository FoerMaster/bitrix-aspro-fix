<?php

/**
 * Скрипт для автоматической чистки сайта от известных вредоносных файлов
 * и исправления уязвимых участков кода.
 *
 * Рекомендуется сделать резервную копию файлов перед запуском.
 * By Vyacheslav Vyatkin 2025 https://vk.com/vyachesiav
 * and https://dd-blog.ru/bitrix-vzlom-saytov-pod-upravleniem-aspro/?ysclid=m6taazywau96587635
 */

// Настройте корневой путь к сайту (обычно DOCUMENT_ROOT)
$documentRoot = '.';

// --- 1. Обновление уязвимых файлов через замену кода ---

// Файлы с уязвимым кодом, где необходимо заменить вызов unserialize
$replacements = [
    '/ajax/reload_basket_fly.php' => [
        'search'  => '$arParams = unserialize(urldecode($_REQUEST["PARAMS"]));',
        'replace' => '$arParams = json_decode($_REQUEST["PARAMS"]);'
    ],
    '/ajax/show_basket_fly.php' => [
        'search'  => '$arParams = unserialize(urldecode($_REQUEST["PARAMS"]));',
        'replace' => '$arParams = json_decode($_REQUEST["PARAMS"]);'
    ],
    '/ajax/show_basket_popup.php' => [
        'search'  => '$arParams = unserialize(urldecode($_REQUEST["PARAMS"]));',
        'replace' => '$arParams = json_decode($_REQUEST["PARAMS"]);'
    ],
    '/bitrix/wizards/aspro/max/site/public/ru/ajax/reload_basket_fly.php' => [
        'search'  => '$arParams = unserialize(urldecode($_REQUEST["PARAMS"]));',
        'replace' => '$arParams = json_decode($_REQUEST["PARAMS"]);'
    ],
    '/bitrix/wizards/aspro/max/site/public/ru/ajax/show_basket_fly.php' => [
        'search'  => '$arParams = unserialize(urldecode($_REQUEST["PARAMS"]));',
        'replace' => '$arParams = json_decode($_REQUEST["PARAMS"]);'
    ],
    '/bitrix/wizards/aspro/max/site/public/ru/ajax/show_basket_popup.php' => [
        'search'  => '$arParams = unserialize(urldecode($_REQUEST["PARAMS"]));',
        'replace' => '$arParams = json_decode($_REQUEST["PARAMS"]);'
    ],
];

// Файлы компонента, где требуется заменить блок кода
$replacementsBlock = [
    '/include/mainpage/comp_catalog_ajax.php' => [
        'search'  => '$arIncludeParams = ($bAjaxMode ? $_POST["AJAX_PARAMS"] : $arParamsTmp);
$arGlobalFilter = ($bAjaxMode ? unserialize(urldecode($_POST["GLOBAL_FILTER"])) : ($_GET[\'GLOBAL_FILTER\'] ? unserialize(urldecode($_GET[\'GLOBAL_FILTER\'])) : array()));
$arComponentParams = unserialize(urldecode($arIncludeParams));',
        'replace' => 'if ($_POST["AJAX_PARAMS"] && !is_array(unserialize(urldecode($_POST["AJAX_PARAMS"]), ["allowed_classes" => false]))) {
    header(\'HTTP/1.1 403 Forbidden\');
    $APPLICATION->SetTitle(\'Error 403: Forbidden\');
    echo \'Error 403: Forbidden_1\';
    require_once($_SERVER[\'DOCUMENT_ROOT\'] . \'/bitrix/modules/main/include/epilog_after.php\');
    die();
}
$arIncludeParams = ($bAjaxMode ? $_POST["AJAX_PARAMS"] : $arParamsTmp);
$arGlobalFilter = ($bAjaxMode ? unserialize(urldecode($_POST["GLOBAL_FILTER"]), ["allowed_classes" => false]) : array());
$arComponentParams = unserialize(urldecode($arIncludeParams), ["allowed_classes" => false]);'
    ],
    '/bitrix/wizards/aspro/max/site/public/ru/include/mainpage/comp_catalog_ajax.php' => [
        'search'  => '$arIncludeParams = ($bAjaxMode ? $_POST["AJAX_PARAMS"] : $arParamsTmp);
$arGlobalFilter = ($bAjaxMode ? unserialize(urldecode($_POST["GLOBAL_FILTER"])) : ($_GET[\'GLOBAL_FILTER\'] ? unserialize(urldecode($_GET[\'GLOBAL_FILTER\'])) : array()));
$arComponentParams = unserialize(urldecode($arIncludeParams));',
        'replace' => 'if ($_POST["AJAX_PARAMS"] && !is_array(unserialize(urldecode($_POST["AJAX_PARAMS"]), ["allowed_classes" => false]))) {
    header(\'HTTP/1.1 403 Forbidden\');
    $APPLICATION->SetTitle(\'Error 403: Forbidden\');
    echo \'Error 403: Forbidden_1\';
    require_once($_SERVER[\'DOCUMENT_ROOT\'] . \'/bitrix/modules/main/include/epilog_after.php\');
    die();
}
$arIncludeParams = ($bAjaxMode ? $_POST["AJAX_PARAMS"] : $arParamsTmp);
$arGlobalFilter = ($bAjaxMode ? unserialize(urldecode($_POST["GLOBAL_FILTER"]), ["allowed_classes" => false]) : array());
$arComponentParams = unserialize(urldecode($arIncludeParams), ["allowed_classes" => false]);'
    ]
];

/**
 * Функция для обновления файла: поиск и замена строки.
 *
 * @param string $filePath Полный путь к файлу.
 * @param string $search Строка, которую нужно найти.
 * @param string $replace Строка-замена.
 */
function updateFile($filePath, $search, $replace) {
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        if (strpos($content, $search) !== false) {
            $newContent = str_replace($search, $replace, $content);
            if (file_put_contents($filePath, $newContent) !== false) {
                echo "Обновлён: $filePath\n";
            } else {
                echo "Не удалось записать изменения в: $filePath\n";
            }
        } else {
            echo "Не найден искомый фрагмент в: $filePath\n";
        }
    } else {
        echo "Файл не найден: $filePath\n";
    }
}

// Обрабатываем файлы с одиночной заменой
foreach ($replacements as $relativePath => $data) {
    $filePath = $documentRoot . $relativePath;
    updateFile($filePath, $data['search'], $data['replace']);
}

// Обрабатываем файлы с заменой блоков кода
foreach ($replacementsBlock as $relativePath => $data) {
    $filePath = $documentRoot . $relativePath;
    updateFile($filePath, $data['search'], $data['replace']);
}

// --- 2. Удаление вредоносных файлов по MD5-хэшам ---
// Известные MD5-хэши вредоносных файлов
$maliciousHashes = [
    "07a3fe9875d3a8b7c57874c4cc509929",
    "50b7604d856f36983b9bb3066f894f3f"
];

/**
 * Рекурсивно сканирует директорию и удаляет файлы с указанными MD5-хэшами.
 *
 * @param string $dir Корневая директория для сканирования.
 * @param array $maliciousHashes Массив с известными вредоносными хэшами.
 */
function scanAndRemoveMaliciousFiles($dir, $maliciousHashes) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filePath = $file->getPathname();
            $hash = md5_file($filePath);
            if (in_array($hash, $maliciousHashes)) {
                if (unlink($filePath)) {
                    echo "Удалён вредоносный файл: $filePath\n";
                } else {
                    echo "Не удалось удалить файл: $filePath\n";
                }
            }
        }
    }
}
echo "Поиск хешей...\n";
scanAndRemoveMaliciousFiles($documentRoot, $maliciousHashes);

// --- 3. Проверка директории /ajax/ на подозрительные файлы ---
// Ищем PHP-файлы с именами, состоящими из букв и цифр, и содержащие "eval(base64"
$ajaxDir = $documentRoot . '/ajax/';
if (is_dir($ajaxDir)) {
    $files = scandir($ajaxDir);
    foreach ($files as $file) {
        // Проверяем, что имя файла состоит только из букв и цифр и имеет расширение .php
        if (preg_match('/^[a-z0-9]+\.php$/i', $file)) {
            $filePath = $ajaxDir . $file;
            $content = file_get_contents($filePath);
            if (stripos($content, 'eval(base64') !== false) {
                if (unlink($filePath)) {
                    echo "Удалён подозрительный файл: $filePath\n";
                } else {
                    echo "Не удалось удалить подозрительный файл: $filePath\n";
                }
            }
        }
    }
} else {
    echo "Директория не найдена: $ajaxDir\n";
}

// --- 4. (Опционально) Дополнительное сканирование всего сайта ---
// Если необходимо просканировать весь сайт на наличие фрагмента "eval(base64" и удалить такие файлы,
// раскомментируйте следующий блок. Будьте осторожны – эта операция может затронуть и легитимные файлы.

/*
function scanAndCleanEvalBase64($dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION)) == 'php') {
            $filePath = $file->getPathname();
            $content = file_get_contents($filePath);
            if (stripos($content, 'eval(base64') !== false) {
                if (unlink($filePath)) {
                    echo "Удалён файл с eval(base64): $filePath\n";
                } else {
                    echo "Не удалось удалить файл с eval(base64): $filePath\n";
                }
            }
        }
    }
}
 
// Раскомментируйте следующую строку для запуска сканирования по всему сайту:
// scanAndCleanEvalBase64($documentRoot);
*/

echo "Очистка и исправление завершены.\n";
?>
