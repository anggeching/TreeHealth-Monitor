<?php
require '../db/pdo_conn.php';

if (!isset($_GET['database']) || empty($_GET['database']) || !isset($_GET['table']) || empty($_GET['table'])) {
    die("Database and table must be specified.");
}

$database = $_GET['database'];
$table = $_GET['table'];
$results = []; 
$columnNames = [];

try {
    $conn = getPDOConnection($database);
    $stmt = $conn->query("SELECT * FROM `" . $table . "` LIMIT 1"); 
    $firstRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($firstRow) {
        $columnNames = array_keys($firstRow);
        $stmtAll = $conn->query("SELECT * FROM `" . $table . "` LIMIT 100");
        $results = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmtColumns = $conn->query("SHOW COLUMNS FROM `" . $table . "`");
        $columnsData = $stmtColumns->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columnsData as $columnInfo) {
            $columnNames[] = $columnInfo['Field'];
        }
    }
} catch (PDOException $e) {
    die("Error fetching data or table information: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Viewing Table: <?php echo htmlspecialchars($table); ?> in <?php echo htmlspecialchars($database); ?></title>
    <style>
        body { font-family: sans-serif; }
        .container { width: 90%; margin: 20px auto; }
        h2 { margin-top: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .back-link { display: block; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Data in Table: <?php echo htmlspecialchars($table); ?> (Database: <?php echo htmlspecialchars($database); ?>)</h2>

        <?php if (!empty($columnNames)): ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($columnNames as $column): ?>
                            <th><?php echo htmlspecialchars($column); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($results)): ?>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <?php foreach ($row as $value): ?>
                                    <td><?php echo htmlspecialchars($value); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        <p>Showing the first 100 rows.</p>
                    <?php else: ?>
                        <tr><td colspan="<?php echo count($columnNames); ?>">No records found in this table.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Could not retrieve column names for this table.</p>
        <?php endif; ?>

        <a href="../dbcontrol.php">Back to Database Selection</a>
    </div>
</body>
</html>