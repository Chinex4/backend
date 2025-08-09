<?php
/**
 * flatten_and_rename_images.php
 *
 * For each entry in coins.json:
 *  - Locate the local image file by the basename in the image URL (search recursively in coinimages)
 *  - Move it to coinimages root and rename to random 8-digit filename
 *  - Update coins.json "image" to https://img.cashtradepro.com/config/<newname>.<ext>
 */

$imageDir            = "C:/xampp/htdocs/cashtradeproApi/coinimages";   // local folder with images (has subfolders)
$jsonPath            = "C:/xampp/htdocs/cashtradeproApi/coins.json";   // your coins.json
$publicConfigPrefix  = "https://img.cashtradepro.com/config";          // final URL prefix (no trailing slash)
$allowedExts         = ['png','jpg','jpeg','webp','gif','svg'];

// --- guards
if (!is_dir($imageDir)) die("Image directory not found: $imageDir\n");
if (!file_exists($jsonPath)) die("coins.json not found: $jsonPath\n");

$coins = json_decode(file_get_contents($jsonPath), true);
if (!is_array($coins)) die("Invalid coins.json structure\n");

// --- backup JSON
$backup = $jsonPath . ".backup." . date('Ymd_His') . ".json";
if (!@copy($jsonPath, $backup)) die("Failed to create JSON backup at: $backup\n");

// --- utils
function extOf($fn){ return strtolower(pathinfo($fn, PATHINFO_EXTENSION)); }
function isAllowed($fn,$allowed){ $e=extOf($fn); return $e && in_array($e,$allowed,true); }
function randName($dir,$ext,&$used){
  do {
    $name = random_int(10000000, 99999999) . "." . $ext;
  } while (isset($used[$name]) || file_exists($dir . DIRECTORY_SEPARATOR . $name));
  $used[$name] = true;
  return $name;
}
function basenameFromUrl($url){
  $p = parse_url($url, PHP_URL_PATH);
  if (!$p) return null;
  $b = basename($p);
  return $b ?: null;
}

// --- index all files (recursive) by lowercase basename
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($imageDir, FilesystemIterator::SKIP_DOTS));
$fileIndex = []; // basename(lower) => full path
foreach ($rii as $file) {
  if (!$file->isFile()) continue;
  $base = $file->getBasename();
  if (!isAllowed($base,$allowedExts)) continue;
  $fileIndex[strtolower($base)] = $file->getPathname();
}

// --- plan renames and update JSON
$usedNames = [];
$updated = 0;
$notFound = [];
$logRows = [];
$ts = date('Ymd_His');
$logFile = $imageDir . "/rename_json_map_$ts.csv";
$log = fopen($logFile,'w');
fputcsv($log, ['key','old_url','found_path','new_name','new_url']);

foreach ($coins as $i => &$coin) {
  $key   = $coin['symbol'] ?? ($coin['coinId'] ?? ("index:$i"));
  $image = $coin['image'] ?? '';
  if (!$image) continue;

  $baseFromUrl = basenameFromUrl($image);
  if (!$baseFromUrl) continue;

  $lookup = strtolower($baseFromUrl);
  if (!isset($fileIndex[$lookup])) {
    // couldn't find a local file with that basename
    $notFound[] = [$key, $image];
    continue;
  }

  $fullOld = $fileIndex[$lookup];
  $oldName = basename($fullOld);
  $ext     = extOf($oldName);

  // generate unique random name in root
  $newName = randName($imageDir, $ext, $usedNames);
  $newFull = $imageDir . DIRECTORY_SEPARATOR . $newName;

  // move/rename
  if (!@rename($fullOld, $newFull)) {
    echo "Failed to move/rename: $fullOld -> $newFull\n";
    continue;
  }

  // update JSON image url to flat /config/<newName>
  $newUrl = rtrim($publicConfigPrefix,'/') . '/' . $newName;
  $coin['image'] = $newUrl;
  $updated++;

  fputcsv($log, [$key, $image, $fullOld, $newName, $newUrl]);

  // keep index consistent if another entry referenced the same basename (rare)
  unset($fileIndex[$lookup]);
  $fileIndex[strtolower($newName)] = $newFull;
}
unset($coin);
fclose($log);

// --- save JSON
file_put_contents($jsonPath, json_encode($coins, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// --- summary
echo "Renamed & flattened: $updated\n";
echo "JSON updated       : $jsonPath\n";
echo "Backup saved       : $backup\n";
echo "Map saved          : $logFile\n";
if (!empty($notFound)) {
  echo "Not found locally (first 10 shown):\n";
  foreach (array_slice($notFound, 0, 10) as [$k,$u]) {
    echo "  - $k => $u\n";
  }
}
