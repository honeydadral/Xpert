<?php
session_start();
require_once 'includes/connect_db.php'; // Assuming DB is needed for packages

$page_title = 'Xpert Events Management System';
include 'includes/header.php';
?>

<div class="container mt-4">
    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['first_name']) && isset($_SESSION['last_name'])): ?>
        <h1 id="mainhead">Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h1>
        <p class="text-center">You are now logged in to your account.</p>
    <?php else: ?>
        <h1 id="mainhead">Xpert Events</h1>
        <p class="text-center">Please <a href="login.php" class="text-primary">log in</a> or <a href="register.php" class="text-primary">register</a> to access your account.</p>
    <?php endif; ?>

    <section aria-labelledby="welcome-heading">
        <h2 id="welcome-heading">Welcome to Xpert Events – Your Premier Event Management Solution</h2>
        <p>Plan unforgettable corporate events, weddings, and more with our curated packages or custom requests.</p>
    </section>

    <!-- Package display -->
    <section aria-labelledby="packages-heading">
        <h2 id="packages-heading">Explore Our Packages</h2>
        <div class="row mt-4">
            <?php
            // Fetch packages from database, including price_after_discount
            $query = "SELECT package_id, package_name, price, price_after_discount FROM packages LIMIT 3";
            $result = $dbc->query($query);
            if ($result && $result->num_rows > 0):
                while ($package = $result->fetch_assoc()):
            ?>
                <div class="col-md-4 mb-4">
                    <div class="package-card" role="region" aria-labelledby="package-<?php echo $package['package_id']; ?>">
                        <h3 id="package-<?php echo $package['package_id']; ?>"><?php echo htmlspecialchars($package['package_name']); ?></h3>
                        <p class="price">
                            <?php if ($package['price_after_discount'] !== null): ?>
                                <span style="text-decoration: line-through;">£<?php echo htmlspecialchars(number_format($package['price'], 2)); ?></span>
                                £<?php echo htmlspecialchars(number_format($package['price_after_discount'], 2)); ?>
                            <?php else: ?>
                                £<?php echo htmlspecialchars(number_format($package['price'], 2)); ?>
                            <?php endif; ?>
                        </p>
                        <button class="btn btn-custom" onclick="window.location.href='view_packages.php'">View All Packages</button>
                        <!-- <button class="btn btn-custom" onclick="bookPackage(<?php echo $package['package_id']; ?>, this)" aria-label="Book <?php echo htmlspecialchars($package['package_name']); ?> package">Book Now</button> -->
                    </div>
                </div>
            <?php
                endwhile;
            else:
            ?>
                <p class="text-center">No packages available at the moment.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
// JavaScript for booking packages with loading state
function bookPackage(packageId, button) {
    // Disable button and show loading state
    button.disabled = true;
    button.textContent = 'Booking...';

    fetch('book_package.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ package_id: packageId })
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        alert(data.message);
        button.disabled = false;
        button.textContent = 'Book Now';
    })
    .catch(error => {
        alert('Error booking package: ' + error.message);
        button.disabled = false;
        button.textContent = 'Book Now';
    });
}
</script>

<?php
mysqli_close($dbc);
include 'includes/footer.html';
?>