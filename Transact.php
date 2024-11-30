<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Processor</title>
    <script>
        function validateForm() {
            var amount = document.getElementById("amount").value;
            var regex = /^\d{1,15}(\.\d{1,2})?$/;

            if (!regex.test(amount)) {
                alert("Please enter a valid amount with up to 15 digits before the decimal and up to 2 digits after the decimal.");
                return false;
            }
            return true;
        }

        function populateBalance(balance) {
            document.getElementById("balance").value = "$" + balance;
        }
    </script>
</head>
<body>
    <h1>Transaction Processor</h1>

    <h2>Bank Transaction Form</h2>
    <form id="transactionForm" method="POST" onsubmit="return validateForm()">
        <label for="transactionType">Transaction Type:</label>
        <select id="transactionType" name="transactionType">
            <option value="deposit">Deposit</option>
            <option value="withdrawal">Withdrawal</option>
        </select>
        <br><br>

        <label for="amount">Amount:</label>
        <input type="text" id="amount" name="amount" required>
        <br><br>

        <input type="submit" value="Submit">

    <br><br>
    <label for="balance">Current Balance:</label>
    <input type="text" id="balance" name="balance" readonly>
    <br><br>
    </form>

    <?php
    $dsn = 'odbc:PUB400.com';
    $user = 'YourUserName';
    $password = 'YourPassword';

    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
        $sql = "CALL YourLibrary.GETBALANCE(?)";

        $stmt = $pdo->prepare($sql) or die("Error in Prepare: ");
        
        $balance = '';
        $message = '';
        
        $stmt->bindParam(1, $balance, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT);
    
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            echo 'Error: '.$e->getMessage();
        }
    
        $formattedBalance = number_format($balance, 2);

        // Display message and balance after submit button
        echo "<div>";
        echo "<script>populateBalance('" . htmlspecialchars($formattedBalance) . "');</script>";
        if ($message) {
            echo "<p style='color: red;'>" . htmlspecialchars($message) . "</p>";
        }
        echo "</div>";
    
        // Pass the formatted balance to JavaScript
        echo "<script>populateBalance('" . htmlspecialchars($formattedBalance) . "');</script>";
    
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!preg_match('/^\d{1,15}(\.\d{1,2})?$/', $_POST["amount"])) {
            echo "<p style='color: red;'>Invalid amount format. Please enter a valid amount.</p>";
        } else {
            // Get the amount from the form
            $amount = floatval($_POST["amount"]);
            $Ttype = $_POST["transactionType"];

            // Call the stored procedure to deposit, withdraw and return balance
            try { 
                $pdo = new PDO($dsn, $user, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $sql = "CALL YourLibrary.ProcessTransaction(?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql) or die("Error in Prepare: ");

                if ($Ttype === "withdrawal") {
                    $type = 'W';
                } elseif ($Ttype === "deposit") {
                    $type = 'D';
                } else {
                    $type = '';
                }
                
                $stmt->bindParam(1, $amount, PDO::PARAM_STR);
                $stmt->bindParam(2, $type, PDO::PARAM_STR);
                $stmt->bindParam(3, $balance, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT);
                $stmt->bindParam(4, $message, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT);

                try {
                    $stmt->execute();
                } catch (PDOException $e) {
                    echo 'Error: '.$e->getMessage();
                }

                // Format the balance to 2 decimal places
                $formattedBalance = number_format($balance, 2);

                // Display message and balance after submit button
                echo "<div>";
                echo "<script>populateBalance('" . htmlspecialchars($formattedBalance) . "');</script>";
                if ($message) {
                    echo "<p style='color: red;'>" . htmlspecialchars($message) . "</p>";
                }
                echo "</div>";

            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }

    $pdo = null;
    ?>

</body>
</html>
