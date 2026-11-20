<?php
require 'config.php';
requireRole('guest');

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $bid = (int)$_POST['cancel_booking_id'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE booking_id = ? AND guest_id = ?");
    $stmt->execute([$bid, $_SESSION['user_id']]);
}

$stmt = $pdo->prepare("SELECT b.*, p.title, p.city, p.address, p.property_type, p.checkin_instructions, p.checkout_instructions, u.full_name AS host_name, u.phone AS host_phone
                       FROM bookings b
                       JOIN properties p ON b.property_id = p.property_id
                       JOIN users u ON p.host_id = u.user_id
                       WHERE b.guest_id = ?
                       ORDER BY b.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings | Ekhaya Reservations</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-stone-50 text-stone-800">

<nav class="bg-white border-b border-stone-200 px-6 py-4 flex justify-between items-center">
    <a href="index.php" class="text-2xl font-bold text-amber-800">Ekhaya Reservations</a>
    <div class="flex gap-4 text-sm">
        <a href="index.php" class="hover:text-amber-800">Explore Stays</a>
        <a href="logout.php" class="text-red-600">Logout</a>
    </div>
</nav>

<div class="max-w-5xl mx-auto py-10 px-6">
    <h1 class="text-2xl font-bold mb-6">My Online Booking Tracker</h1>

    <?php if (isset($_GET['booked'])): ?>
        <div class="bg-emerald-100 border border-emerald-400 text-emerald-800 px-4 py-3 rounded-lg mb-6">
            Reservation confirmed! Your check-in and check-out instructions are listed below.
        </div>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
        <div class="bg-white p-8 rounded-xl border border-stone-200 text-center">
            <p class="mb-4 text-stone-600">You have no reservations yet.</p>
            <a href="index.php" class="bg-amber-800 text-white px-4 py-2 rounded-lg text-sm">Browse Properties</a>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($bookings as $b): ?>
                <div class="bg-white p-6 rounded-xl border border-stone-200 shadow-sm">
                    <div class="flex flex-wrap justify-between items-start gap-4 border-b pb-4 mb-4">
                        <div>
                            <span class="text-xs uppercase font-bold text-amber-800">Booking #<?= $b['booking_id'] ?> • <?= e($b['property_type']) ?></span>
                            <h2 class="text-xl font-bold"><?= e($b['title']) ?></h2>
                            <p class="text-sm text-stone-500"><?= e($b['address']) ?>, <?= e($b['city']) ?> • Host: <?= e($b['host_name']) ?> (<?= e($b['host_phone']) ?>)</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold <?= $b['status'] === 'Confirmed' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>">
                                <?= e($b['status']) ?>
                            </span>
                            <div class="text-lg font-bold mt-1">$<?= number_format($b['total_price'], 2) ?></div>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-3 gap-4 text-sm mb-4">
                        <div><strong>Check-In:</strong> <?= e($b['check_in_date']) ?></div>
                        <div><strong>Check-Out:</strong> <?= e($b['check_out_date']) ?></div>
                        <div><strong>Guests:</strong> <?= e($b['guests_count']) ?></div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4 bg-stone-50 p-4 rounded-lg text-xs">
                        <div>
                            <strong class="block text-amber-900 mb-1">Check-In Instructions:</strong>
                            <?= e($b['checkin_instructions']) ?>
                        </div>
                        <div>
                            <strong class="block text-amber-900 mb-1">Check-Out Instructions:</strong>
                            <?= e($b['checkout_instructions']) ?>
                        </div>
                    </div>

                    <?php if ($b['status'] === 'Confirmed'): ?>
                        <form method="POST" class="mt-4 text-right">
                            <input type="hidden" name="cancel_booking_id" value="<?= $b['booking_id'] ?>">
                            <button type="submit" onclick="return confirm('Cancel this reservation?')" class="text-xs text-red-600 hover:underline">
                                Cancel Reservation
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>