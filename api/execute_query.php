<?php
require '../db/pdo_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['database']) && !empty($_POST['database']) && isset($_POST['query']) && !empty($_POST['query'])) {
    $database = $_POST['database'];
    $query = trim($_POST['query']);
    $results = null;
    $error = null;
    $rowCount = 0;
    $columnNames = [];

    try {
        $conn = getPDOConnection($database);
        $stmt = $conn->query($query);

        // Check if it's a SELECT statement
        if (stripos(trim($query), 'SELECT') === 0) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = $stmt->columnCount() > 0 && !empty($results) ? array_keys($results[0]) : [];
            $rowCount = $stmt->rowCount();
        } else {
            // For INSERT, UPDATE, DELETE, etc.
            $rowCount = $stmt->rowCount();
        }

    } catch (PDOException $e) {
        $error = "Error executing query: " . $e->getMessage();
    }
} else {
    die("Invalid request.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Executing Query on Database: <?php echo htmlspecialchars($database); ?></title>
    <style>
        body { font-family: sans-serif; }
        .container { width: 90%; margin: 20px auto; }
        h2 { margin-top: 20px; }
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .back-link { display: block; margin-top: 20px; }
        .query-executed { margin-top: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Executing Query on Database: <?php echo htmlspecialchars($database); ?></h2>

        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php elseif (stripos(trim($query), 'SELECT') === 0): ?>
            <?php if (!empty($results)): ?>
                <p class="query-executed">Query executed successfully. <?php echo $rowCount; ?> row(s) returned.</p>
                <table>
                    <thead>
                        <tr>
                            <?php if (!empty($columnNames)): ?>
                                <?php foreach ($columnNames as $column): ?>
                                    <th><?php echo htmlspecialchars($column); ?></th>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <?php foreach ($row as $value): ?>
                                    <td><?php echo htmlspecialchars($value); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="query-executed">Query executed successfully. No rows returned.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="query-executed">Query executed successfully. <?php echo $rowCount; ?> row(s) affected.</p>
        <?php endif; ?>

        <a href="../dbcontrol.php">Back to Database Explorer</a>
    </div>
</body>
</html>