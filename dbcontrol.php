<?php
require 'db/pdo_conn.php';

try {
    $conn = getPDOConnection();
    $stmt = $conn->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Error fetching databases: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Explorer</title>
    <style>
        body { font-family: sans-serif; }
        .container { width: 80%; margin: 20px auto; }
        h2 { margin-top: 20px; }
        select { padding: 8px; margin-bottom: 10px; width: 300px; }
        button { padding: 10px 15px; cursor: pointer; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Explorer</h1>

        <h2>Select Database:</h2>
        <form method="GET" action="">
            <select name="database" onchange="this.form.submit()">
                <option value="">-- Select a Database --</option>
                <?php foreach ($databases as $db): ?>
                    <option value="<?php echo htmlspecialchars($db); ?>" <?php if (isset($_GET['database']) && $_GET['database'] === $db) echo 'selected'; ?>><?php echo htmlspecialchars($db); ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if (isset($_GET['database']) && !empty($_GET['database'])): ?>
            <h2>Select Table in Database "<?php echo htmlspecialchars($_GET['database']); ?>":</h2>
            <?php
            try {
                $selectedDatabase = $_GET['database'];
                $conn = getPDOConnection($selectedDatabase);
                $stmt = $conn->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($tables)):
            ?>
                <form method="GET" action="api/view_table.php">
                    <input type="hidden" name="database" value="<?php echo htmlspecialchars($selectedDatabase); ?>">
                    <select name="table">
                        <option value="">-- Select a Table --</option>
                        <?php foreach ($tables as $table): ?>
                            <option value="<?php echo htmlspecialchars($table); ?>"><?php echo htmlspecialchars($table); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">View Table</button>
                </form>

                <h2>Execute Custom Query on "<?php echo htmlspecialchars($selectedDatabase); ?>":</h2>
                <form method="POST" action="api/execute_query.php">
                    <input type="hidden" name="database" value="<?php echo htmlspecialchars($selectedDatabase); ?>">
                    <textarea name="query" rows="5" cols="80" placeholder="Enter SQL query here"></textarea><br>
                    <button type="submit">Execute Query</button>
                </form>
            <?php
                else:
                    echo "<p>No tables found in the selected database.</p>";
                endif;

            } catch (PDOException $e) {
                echo "<p class='error'>Error fetching tables: " . $e->getMessage() . "</p>";
            }
            ?>
        <?php endif; ?>
    </div>
</body>
</html>