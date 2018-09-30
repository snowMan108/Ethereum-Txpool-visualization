<?php
/* 
    Bitcoin Mempool Visualization
    Copyright (C) 2017  Jochen Hoenicke

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

$dbtype = "mysql";
$dbdatabase = "dbname=btc_mempool;host=localhost";
$dbdsn = "$dbtype:$dbdatabase";
$dbuser = "www";
$dbpass = "<redacted>";
$dboptions = array();

$feelevels = 46;

try {
    $db = new PDO($dbdsn, $dbuser, $dbpass, $dboptions);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_GET["s"]) || !isset($_GET["e"])) {
        exit;
    }
    $start = intval($_GET["s"]);
    $end = intval($_GET["e"]);
    $increment = 1;
    if (isset($_GET["i"])) {
        $increment = intval($_GET["i"]);
    }
    if ($increment <= 0) {
        $increment = 1;
    }
    $query = $db->prepare("SELECT * FROM mempool WHERE time >= :start AND time < :end and (time DIV 60) MOD :increment = 0 ORDER BY time");

    $query->execute(array(':start' => $start, ':end' => $end, ':increment' => $increment));
    header("Content-Type: application/json; charset=UTF-8");
    echo 'call([';
    $comma="";
    while ($row = $query->fetch(PDO::FETCH_NUM)) {
        for ($i = 0; $i < 3*$feelevels+1; $i++) {
            if (!isset($row[$i])) {
                $row[$i] = 0;
            }
        }
        echo $comma.'['.$row[0].',['.
             join(',', array_slice($row, 1, $feelevels)).'],['.
             join(',', array_slice($row, 1 + $feelevels, $feelevels)).'],['.
             join(',', array_slice($row, 1 + 2*$feelevels, $feelevels)).']]';
        $comma = ",\n";
    }
    echo "]);\n";
    exit;
} catch (PDOException $ex) {
    header('HTTP/1.1 500 Internal Server Error');
    echo $ex->getMessage().$ex;
    exit;
}
?>
