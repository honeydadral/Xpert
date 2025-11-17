<?php
// Set page title and include header
$page_title = 'Display the current users';
include ('includes/home_header.html');

// Connect to the database
require ('includes/connect_db.php');

// Get search input
$search = isset($_POST['search']) ? $dbc->real_escape_string(trim($_POST['search'])) : '';

// Define SQL query
$q = "SELECT CONCAT(last_name, ',', first_name) AS name, DATE_FORMAT(reg_date, '%M %d, %Y') AS dr 
      FROM users 
      WHERE last_name LIKE '%$search%' 
      ORDER BY reg_date ASC";
$r = $dbc->query($q);
$num = $r->num_rows;

// Display results
if ($num > 0) {
    echo "<p>There are currently $num registered users.</p>\n";
    echo "<table align='center' cellspacing='3' cellpadding='3' width='75%'>
          <tr>
              <th>Name</th>
              <th>Date Registered</th>
          </tr>";
    while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
        echo "<tr>
                <td>" . $row["name"] . "</td>
                <td>" . $row["dr"] . "</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>There are currently no registered users</p>";
}

// Free resources and close database
$r->free_result();
$dbc->close();

// Include footer
include ('includes/footer.html');
?>

<!-- Search form -->
<form action="view_users.php" method="post">
    <p>Search by Last Name: <input type="text" name="search" value="<?php if (isset($_POST['search'])) echo $_POST['search']; ?>"></p>
    <p><input type="submit" value="Search"></p>
</form>