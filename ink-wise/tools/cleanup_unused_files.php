<?php
/**
 * Cleanup unused files script
 *
 * Usage examples:
 *   php tools/cleanup_unused_files.php --scan-dir=public --refs-exts=php,blade.php,js,css,html --dry-run
 *   php tools/cleanup_unused_files.php --scan-dir=public --delete
 *
 * By default the script will search for typical asset extensions in the given scan directory
 * and look for any references to the filename/path in source files (reference extensions).
 * Unreferenced files are moved to `tools/unused_trash_<timestamp>` when `--delete` is used.
 */

function usage()
{
    echo "Usage:\n";
    echo "  php tools/cleanup_unused_files.php [--scan-dir=DIR] [--refs-exts=csv] [--dry-run] [--delete] [--help]\n";
    echo "Options:\n";
    echo "  --scan-dir=DIR     Directory to scan for candidate files (default: public)\n";
    echo "  --refs-exts=csv    Comma-separated reference file extensions to search (default: php,blade.php,js,css,html)\n";
    echo "  --dry-run          Show unused files but do not delete or move them\n";
    echo "  --delete           Move unused files to tools/unused_trash_<timestamp>\n";
    echo "  --extensions=csv   Comma-separated candidate file extensions (default: png,jpg,jpeg,gif,svg,webp,pdf,zip,mp4,webm,ttf,otf,woff,woff2)\n";
    echo "  --exclude=dirs     Comma-separated directories to exclude from reference search (relative to repo root)\n";
    echo "  --help             Show this help message\n";
    exit(0);
}

$opts = getopt("", ["scan-dir::","refs-exts::","dry-run","delete","help","extensions::","exclude::"]);
if (isset($opts['help'])) {
    usage();
}

$scanDir = $opts['scan-dir'] ?? 'public';
$refsExts = isset($opts['refs-exts']) ? explode(',', $opts['refs-exts']) : ['php','blade.php','js','css','html'];
$candidateExts = isset($opts['extensions']) ? explode(',', $opts['extensions']) : ['png','jpg','jpeg','gif','svg','webp','pdf','zip','mp4','webm','ttf','otf','woff','woff2'];
$dryRun = isset($opts['dry-run']);
$doDelete = isset($opts['delete']);
$exclude = isset($opts['exclude']) ? explode(',', $opts['exclude']) : [];

if (!is_dir($scanDir)) {
    fwrite(STDERR, "Scan directory '$scanDir' does not exist.\n");
    exit(2);
}

function iter_files($dir, $exts = []) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        if (!empty($exts)) {
            $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            if (!in_array($ext, $exts)) continue;
        }
        yield $file->getPathname();
    }
}

// Build list of reference files to search through (whole repo except excluded dirs)
$cwd = getcwd() ?: __DIR__;
$referenceFiles = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cwd));
foreach ($rii as $f) {
    if ($f->isDir()) continue;
    $rel = str_replace($cwd . DIRECTORY_SEPARATOR, '', $f->getPathname());
    $skip = false;
    foreach ($exclude as $ex) {
        if ($ex === '') continue;
        if (stripos($rel, trim($ex)) === 0) { $skip = true; break; }
    }
    if ($skip) continue;
    $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
    if (in_array($ext, $refsExts)) $referenceFiles[] = $f->getPathname();
}

// If no reference files found, warn but continue
if (empty($referenceFiles)) {
    fwrite(STDERR, "Warning: No reference files found for extensions: " . implode(',', $refsExts) . "\n");
}

// Read reference files content into memory for quick searching (okay for typical project sizes)
$referenceContents = [];
foreach ($referenceFiles as $rf) {
    $content = @file_get_contents($rf);
    if ($content === false) $content = '';
    $referenceContents[$rf] = $content;
}

// Gather candidate files
$candidates = [];
foreach (iter_files($scanDir, $candidateExts) as $file) {
    $candidates[] = $file;
}

if (empty($candidates)) {
    echo "No candidate files found in $scanDir (extensions: " . implode(',', $candidateExts) . ").\n";
    exit(0);
}

// Function to determine if a candidate is referenced
function is_referenced($candidatePath, $referenceContents, $cwd)
{
    $rel = str_replace($cwd . DIRECTORY_SEPARATOR, '', $candidatePath);
    $basename = basename($candidatePath);
    foreach ($referenceContents as $path => $content) {
        if ($content === '') continue;
        if (strpos($content, $basename) !== false) return true;
        if (strpos($content, $rel) !== false) return true;
        // also try URL-ish path
        $urlRel = str_replace('\\', '/', $rel);
        if (strpos($content, $urlRel) !== false) return true;
    }
    return false;
}

$unused = [];
foreach ($candidates as $c) {
    if (!is_referenced($c, $referenceContents, $cwd)) {
        $unused[] = $c;
    }
}

if (empty($unused)) {
    echo "No unused files found in $scanDir.\n";
    exit(0);
}

echo "Found " . count($unused) . " unused file(s) in $scanDir:\n";
foreach ($unused as $u) echo "  - $u\n";

if ($dryRun) {
    echo "\nDry-run mode: no files were moved or deleted.\n";
    exit(0);
}

if (!$doDelete) {
    echo "\nTo remove these files, re-run with --delete.\n";
    exit(0);
}

$trashDir = __DIR__ . DIRECTORY_SEPARATOR . 'unused_trash_' . date('Ymd_His');
if (!is_dir($trashDir) && !mkdir($trashDir, 0755, true)) {
    fwrite(STDERR, "Failed to create trash directory: $trashDir\n");
    exit(3);
}

foreach ($unused as $u) {
    $dest = $trashDir . DIRECTORY_SEPARATOR . ltrim(str_replace(':', '_', str_replace('\\', '/', $u)), '/');
    $destDir = dirname($dest);
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    if (@rename($u, $dest)) {
        echo "Moved: $u -> $dest\n";
    } else {
        fwrite(STDERR, "Failed to move: $u\n");
    }
}

echo "\nAll unused files moved to: $trashDir\n";
echo "Please inspect the directory before permanently deleting.\n";

exit(0);
