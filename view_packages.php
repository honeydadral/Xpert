<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    require 'login_tools.php';
    load();
}

// Connect to the database
require 'includes/connect_db.php';

// Set page title
$page_title = 'Xpert - View Packages';

// Check if user is Sales Staff with manager status
$is_manager = false;
$user_id = $_SESSION['user_id'];
$query = "SELECT role, manager_status FROM users WHERE user_id = ?";
$stmt = $dbc->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $is_manager = ($row['role'] === 'Sales Staff' && $row['manager_status'] == 1);
}
$stmt->close();

// Fetch all packages
$query = "SELECT package_id, package_name, description, price, price_after_discount, created_at FROM packages ORDER BY created_at DESC";
$result = $dbc->query($query);

if (!$result) {
    echo '<div class="container mt-4"><h1>Error!</h1><p>Failed to load packages: ' . htmlspecialchars($dbc->error) . '</p></div>';
    error_log("Query failed in view_packages.php: " . $dbc->error);
    include 'includes/footer.html';
    mysqli_close($dbc);
    exit();
}

$packages = $result->fetch_all(MYSQLI_ASSOC);
mysqli_close($dbc);
?>

<?php include 'includes/header.php'; ?>


<div class="container mt-4">
    <h1>Book your package</h1>
    <!-- Success Message Alert -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="search-container mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by package name..." onkeyup="filterPackages()">
    </div>
    <div class="row" id="packageCards" style="margin-top: 40px;">
        <?php foreach ($packages as $package): ?>
            <div class="col-md-4 mb-4">
                <div class="package-card" data-name="<?php echo strtolower(htmlspecialchars($package['package_name'])); ?>">
                    <h3><?php echo htmlspecialchars($package['package_name']); ?></h3>
                    <p><?php echo htmlspecialchars($package['description']); ?></p>
                    <p class="price">
                        <?php if ($package['price_after_discount'] !== null): ?>
                            <span style="text-decoration: line-through;">£<?php echo htmlspecialchars(number_format($package['price'], 2)); ?></span>
                            £<?php echo htmlspecialchars(number_format($package['price_after_discount'], 2)); ?>
                        <?php else: ?>
                            £<?php echo htmlspecialchars(number_format($package['price'], 2)); ?>
                        <?php endif; ?>
                    </p>
                    <button class="btn btn-custom" onclick="bookPackage(<?php echo $package['package_id']; ?>)">Book Now</button>
                    <?php if ($is_manager): ?>
                        <button class="btn btn-outline-secondary discount-btn" onclick="openDiscountModal(<?php echo $package['package_id']; ?>, <?php echo $package['price']; ?>)">Discount</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($packages)): ?>
        <p>No packages found.</p>
    <?php endif; ?>
</div>

<!-- Bootstrap Modal for Booking -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookingModalLabel">Book Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bookingForm" action="book_package.php" method="POST">
                    <input type="hidden" name="package_id" id="package_id">
                    <div class="mb-3">
                        <label for="event_date" class="form-label">Event Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="event_date" name="event_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-custom">Submit Booking</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Modal for Discount -->
<div class="modal fade" id="discountModal" tabindex="-1" aria-labelledby="discountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="discountModalLabel">Apply Discount</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="discountForm" action="apply_discount.php" method="POST">
                    <input type="hidden" name="package_id" id="discount_package_id">
                    <div class="mb-3">
                        <label for="discount_type" class="form-label">Discount Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="discount_type" name="discount_type" required>
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="discount_value" class="form-label">Discount Value <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="discount_value" name="discount_value" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="original_price" class="form-label">Original Price</label>
                        <input type="text" class="form-control" id="original_price" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="new_price" class="form-label">Price After Discount</label>
                        <input type="text" class="form-control" id="new_price" readonly>
                    </div>
                    <button type="submit" class="btn btn-custom">Apply Discount</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Filter packages based on search input
function filterPackages() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.package-card');
    cards.forEach(card => {
        const name = card.getAttribute('data-name');
        card.style.display = name.includes(input) ? '' : 'none';
    });
}

// Open booking modal with package ID
function bookPackage(packageId) {
    document.getElementById('package_id').value = packageId;
    const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'), {
        backdrop: 'static',
        keyboard: false
    });
    bookingModal.show();
}

// Open discount modal with package ID and price
function openDiscountModal(packageId, price) {
    document.getElementById('discount_package_id').value = packageId;
    document.getElementById('original_price').value = '£' + parseFloat(price).toFixed(2);
    document.getElementById('discount_value').value = '';
    document.getElementById('new_price').value = '';
    
    const discountModal = new bootstrap.Modal(document.getElementById('discountModal'), {
        backdrop: 'static',
        keyboard: false
    });
    discountModal.show();
}

// Calculate new price on discount value change
document.getElementById('discount_value').addEventListener('input', function() {
    const discountType = document.getElementById('discount_type').value;
    const discountValue = parseFloat(this.value) || 0;
    const originalPrice = parseFloat(document.getElementById('original_price').value.replace('£', '')) || 0;
    let newPrice = originalPrice;

    if (discountType === 'percentage') {
        newPrice = originalPrice * (1 - discountValue / 100);
    } else if (discountType === 'fixed') {
        newPrice = originalPrice - discountValue;
    }

    document.getElementById('new_price').value = newPrice >= 0 ? '£' + newPrice.toFixed(2) : '£0.00';
});

// Update new price when discount type changes
document.getElementById('discount_type').addEventListener('change', function() {
    document.getElementById('discount_value').dispatchEvent(new Event('input'));
});
</script>

<?php include 'includes/footer.html'; ?>