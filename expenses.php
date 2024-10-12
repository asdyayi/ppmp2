<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['request_id'])) {
    header('Location: login.php');
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ppmp_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$request_id = $_SESSION['request_id'];

// Insert expenses if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['process'] == '2') {
    if (isset($_POST['expenses'])) {
        $expenses = json_decode($_POST['expenses'], true);
        $total_expense = 0;

        foreach ($expenses as $expense) {
            $particular = $expense['particular'];
            $quantity = (float)$expense['quantity'];
            $unit_cost = (float)$expense['unit_cost'];
            $total_estimated = $quantity * $unit_cost;
            $total_expense += $total_estimated;

            // Insert or update expense into the database
            if (isset($expense['id'])) {
                $sql = "UPDATE expenses SET particular=?, quantity=?, unit_cost=?, total_estimated=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siddi", $particular, $quantity, $unit_cost, $total_estimated, $expense['id']);
                $stmt->execute();
                $stmt->close();
            } else {
                $sql = "INSERT INTO expenses (request_id, particular, quantity, unit_cost, total_estimated) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isidd", $request_id, $particular, $quantity, $unit_cost, $total_estimated);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Fetch total event cost for the account title
        $account_sql = "SELECT at.title, at.total_event_cost FROM account_titles at JOIN requests r ON at.id = r.account_title_id WHERE r.id = ?";
        $account_stmt = $conn->prepare($account_sql);
        $account_stmt->bind_param("i", $request_id);
        $account_stmt->execute();
        $account_result = $account_stmt->get_result();
        $account = $account_result->fetch_assoc();
        $account_total_cost = $account['total_event_cost'];

        if ($total_expense > $account_total_cost) {
            echo "<script>alert('Total expenses exceed the total event cost for the selected account. Please adjust your expenses.'); window.location.href='expenses.php';</script>";
            exit();
        }

        header('Location: people_involved.php');
        exit();
    }
}

// Fetch existing expenses
$expenses_sql = "SELECT id, particular, quantity, unit_cost, total_estimated FROM expenses WHERE request_id = ?";
$expenses_stmt = $conn->prepare($expenses_sql);
$expenses_stmt->bind_param("i", $request_id);
$expenses_stmt->execute();
$expenses_result = $expenses_stmt->get_result();

// Fetch total event cost for the account title
$account_sql = "SELECT at.title, at.total_event_cost FROM account_titles at JOIN requests r ON at.id = r.account_title_id WHERE r.id = ?";
$account_stmt = $conn->prepare($account_sql);
$account_stmt->bind_param("i", $request_id);
$account_stmt->execute();
$account_result = $account_stmt->get_result();
$account = $account_result->fetch_assoc();
$account_total_cost = $account['total_event_cost'];

$expenses_stmt->close();
$account_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expenses</title>
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="styles/expenses.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            calculateTotal();
            updateAddExpenseButton();
        });

        function addExpense() {
            const particular = document.getElementById('new-particular').value.trim();
            const quantity = parseFloat(document.getElementById('new-quantity').value);
            const unitCost = parseFloat(document.getElementById('new-unit-cost').value);

            if (!particular) {
                alert("Please enter the particular before adding the expense.");
                return;
            }

            if (isNaN(quantity) || isNaN(unitCost) || quantity <= 0 || unitCost <= 0) {
                alert("Please enter valid positive numbers for quantity and unit cost.");
                return;
            }

            const totalEstimated = quantity * unitCost;

            // Create a new row in the table
            const table = document.getElementById('expenses-table');
            const row = table.insertRow();
            row.innerHTML = `
                <td>${particular}</td>
                <td>${quantity}</td>
                <td>${unitCost}</td>
                <td>${totalEstimated.toFixed(2)}</td>
                <td>
                    <button type="button" onclick="editRow(this)">Edit</button>
                    <button type="button" onclick="deleteRow(this)">Delete</button>
                </td>
            `;

            document.getElementById('new-particular').value = '';
            document.getElementById('new-quantity').value = '';
            document.getElementById('new-unit-cost').value = '';

            calculateTotal();
        }

        function editRow(button) {
            const row = button.closest('tr');
            const cells = row.getElementsByTagName('td');

            document.getElementById('new-particular').value = cells[0].innerText;
            document.getElementById('new-quantity').value = cells[1].innerText;
            document.getElementById('new-unit-cost').value = cells[2].innerText;

            row.remove();
            calculateTotal();
        }

        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('#expenses-table tr').forEach(row => {
                const cells = row.getElementsByTagName('td');
                if (cells.length > 0) {
                    const totalEstimated = parseFloat(cells[3].innerText);
                    total += totalEstimated;
                }
            });
            document.getElementById('total-amount').innerText = total.toFixed(2);
            updateAddExpenseButton();
        }

        function updateAddExpenseButton() {
            const total = parseFloat(document.getElementById('total-amount').innerText);
            const accountTotal = parseFloat(document.getElementById('account-total').value);
            const addButton = document.getElementById('add-expense-button');

            if (total >= accountTotal) {
                addButton.disabled = true;
                addButton.title = "You cannot add more expenses. The total amount has reached or exceeded the account limit.";
            } else {
                addButton.disabled = false;
                addButton.title = "";
            }
        }

        function deleteRow(button) {
            const row = button.closest('tr');
            row.remove();
            calculateTotal();
        }

        function validateAndSubmit() {
            const total = parseFloat(document.getElementById('total-amount').innerText);
            const accountTotal = parseFloat(document.getElementById('account-total').value);
            if (total > accountTotal) {
                alert(`Total expenses exceed the total event cost for the selected account. Please adjust your expenses.`);
                return false; // Prevent form submission
            }

            const expenses = [];
            document.querySelectorAll('#expenses-table tr').forEach(row => {
                const cells = row.getElementsByTagName('td');
                if (cells.length > 0) {
                    const particular = cells[0].innerText;
                    const quantity = parseFloat(cells[1].innerText);
                    const unitCost = parseFloat(cells[2].innerText);
                    expenses.push({ particular, quantity, unit_cost: unitCost });
                }
            });

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'expenses';
            hiddenInput.value = JSON.stringify(expenses);
            document.forms[0].appendChild(hiddenInput);

            return true; // Allow form submission
        }
    </script>
</head>
<body>

<div class="nav">
  <a href="dashboard.php" onclick="toggleActive(event, this)"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="request_form.php" onclick="toggleActive(event, this)"><i class="fas fa-plus-circle"></i> Create Tracker</a>
  <a href="createPPMP.php" onclick="toggleActive(event, this)"><i class="fas fa-plus"></i> Create PPMP</a>
  <a href="history.php" onclick="toggleActive(event, this)"><i class="fas fa-history"></i> History</a>
  <a href="chatbot.php" onclick="toggleActive(event, this)"><i class="fas fa-comments"></i> Chatbot</a>
  <a href="settings.php" class="settings" onclick="toggleActive(event, this)"><i class="fas fa-cog"></i> Settings</a>
  <a href="#" class="logout" id="logout-link" onclick="toggleActive(event, this)"><i class="fas fa-sign-out-alt"></i> Log out</a>
</div>

<div id="logoutModal" class="modal">
  <div class="modal-content">
    <p>Are you sure you want to log out?</p>
    <button id="yesBtn">Yes</button>
    <button id="noBtn">No</button>
  </div>
</div>

<div class="header">
  <img src="img/logo.png" class="logo">
  <h1>Admin <span id="datetime"></span></h1>
  <div class="user-profile">
    <i class="fas fa-bell notification-icon" onclick="toggleNotificationDropdown()"></i>
    <div class="vertical-line"></div>
    <?php include 'user_profile.php'; ?>
  </div>
</div>

<div class="content">
  <div class="img">
    <div class="image-row">
        <img src="img/info.png" alt="Image 1"> <hr>
        <img src="img/peso.png" alt="Image 2" class=""> <hr>
        <img src="img/people.png" alt="Image 3" class="transparent"> <hr>
        <img src="img/process.png" alt="Image 4" class="transparent"> 
      </div>
      <div class="ex">
          <h2>EXPENSES</h2>
      </div>
    <div class ="cont">
    <div class="budget">
        <div class="expenses">
            <form action="expenses.php" method="post" onsubmit="return validateAndSubmit();">
                <input type="hidden" name="process" value="2">
                <table>
                    <thead>
                        <tr>
                            <th>Particular</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="expenseTableBody">
                        <tr>
                            <td><input type="text" id="new-particular" name="particular" ></td>
                            <td><input type="number" id="new-quantity" name="quantity" step="any" min="0" ></td>
                            <td><input type="number" id="new-unit-cost" name="unit_cost" step="any" min="0" ></td>
                            <td><button type="button" id="add-expense-button" onclick="addExpense()">Add Expense</button></td>
                        </tr>
                    </tbody>
                </table>
                <h2>Added Expenses</h2>
                <table id="expenses-table">
                    <tr>
                        <th>Particular</th>
                        <th>Quantity</th>
                        <th>Unit Cost</th>
                        <th>Total Estimated</th>
                        <th>Action</th>
                    </tr>
                    <?php while ($row = $expenses_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['particular']); ?></td>
                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($row['unit_cost']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_estimated']); ?></td>
                        <td>
                            <button type="button" onclick="editRow(this)">Edit</button>
                            <button type="button" onclick="deleteRow(this)">Delete</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                <button type="submit" class = "btnsub">Next</button>
            </form>
          
        </div>
    </div>
    <h2 class = "h22" > <b style = "font-family: 'bold'">Total Amount: </b> ₱<span id="total-amount" style = "color:black;">0</span></h2>
                <input type="hidden" id="account-total" value="<?php echo htmlspecialchars($account_total_cost); ?>">
               
    
    </div>
</div>

</body>
</html>
