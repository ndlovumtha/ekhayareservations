<?php
require 'config.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, u.full_name AS host_name, u.phone AS host_phone, u.is_verified_safe 
                       FROM properties p 
                       JOIN users u ON p.host_id = u.user_id 
                       WHERE p.property_id = ?");
$stmt->execute([$id]);
$property = $stmt->fetch();

if (!$property) {
    die("Property not found.");
}

$bookingError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_now'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: auth.php");
        exit;
    }

    $check_in  = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $guests    = max(1, (int)$_POST['guests']);

    $d1 = new DateTime($check_in);
    $d2 = new DateTime($check_out);
    $nights = $d1->diff($d2)->days;

    if ($d2 <= $d1 || $nights < 1) {
        $bookingError = "Check-out date must be at least 1 night after check-in.";
    } else {
        $total_price = $nights * $property['price_per_night'];
        $stmt = $pdo->prepare("INSERT INTO bookings (property_id, guest_id, check_in_date, check_out_date, guests_count, total_price) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $_SESSION['user_id'], $check_in, $check_out, $guests, $total_price]);

        header("Location: my_bookings.php?booked=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($property['title']) ?> | Ekhaya Reservations</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-stone-50 text-stone-800">

<nav class="bg-white border-b border-stone-200 px-6 py-4 flex justify-between items-center">
    <a href="index.php" class="text-2xl font-bold text-amber-800">Ekhaya Reservations</a>
    <a href="index.php" class="text-sm text-stone-600 hover:text-amber-800">← Back to Listings</a>
</nav>

<div class="max-w-5xl mx-auto py-10 px-6">
    <img src="<?= e($property['image_path']) ?>" alt="<?= e($property['title']) ?>" class="w-full h-96 object-cover rounded-2xl shadow-sm mb-8">

    <div class="grid md:grid-cols-3 gap-8">
        <!-- Property Info -->
        <div class="md:col-span-2 space-y-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="bg-stone-200 text-xs font-semibold px-2.5 py-1 rounded"><?= e($property['property_type']) ?></span>
                    <?php if ($property['is_verified_safe']): ?>
                        <span class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-2.5 py-1 rounded">✓ Verified Safe Host</span>
                    <?php else: ?>
                        <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-1 rounded">Host Verification Pending</span>
                    <?php endif; ?>
                </div>
                <h1 class="text-3xl font-bold"><?= e($property['title']) ?></h1>
                <p class="text-stone-500 text-sm mt-1"><?= e($property['address']) ?>, <?= e($property['city']) ?> • Hosted by <strong><?= e($property['host_name']) ?></strong></p>
            </div>

            <div class="bg-white p-6 rounded-xl border border-stone-200">
                <h2 class="text-lg font-bold mb-2">About this place</h2>
                <p class="text-stone-700 leading-relaxed whitespace-pre-line"><?= e($property['description']) ?></p>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="bg-white p-5 rounded-xl border border-stone-200">
                    <h3 class="font-bold text-amber-900 mb-2">Check-In Instructions</h3>
                    <p class="text-sm text-stone-700 whitespace-pre-line"><?= e($property['checkin_instructions']) ?></p>
                </div>
                <div class="bg-white p-5 rounded-xl border border-stone-200">
                    <h3 class="font-bold text-amber-900 mb-2">Check-Out Instructions</h3>
                    <p class="text-sm text-stone-700 whitespace-pre-line"><?= e($property['checkout_instructions']) ?></p>
                </div>
            </div>
        </div>

        <!-- Booking Card -->
        <div class="bg-white p-6 rounded-xl border border-stone-200 shadow-sm h-fit">
            <div class="text-2xl font-bold text-amber-900 mb-4">
                $<?= number_format($property['price_per_night'], 2) ?> <span class="text-sm font-normal text-stone-500">/ night</span>
            </div>

            <?php if ($bookingError): ?>
                <div class="bg-red-100 text-red-700 text-xs p-3 rounded mb-4"><?= e($bookingError) ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'guest'): ?>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-stone-500 mb-1">Check-In Date</label>
                        <input type="date" name="check_in" required min="<?= date('Y-m-d') ?>" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-stone-500 mb-1">Check-Out Date</label>
                        <input type="date" name="check_out" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-stone-500 mb-1">Guests</label>
                        <input type="number" name="guests" value="1" min="1" max="20" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <button type="submit" name="book_now" class="w-full bg-amber-800 hover:bg-amber-900 text-white font-semibold py-3 rounded-lg">
                        Reserve Stay
                    </button>
                </form>
            <?php elseif (!isset($_SESSION['user_id'])): ?>
                <a href="auth.php" class="block text-center w-full bg-amber-800 text-white font-semibold py-3 rounded-lg">
                    Log In as Guest to Book
                </a>
            <?php else: ?>
                <p class="text-xs text-stone-500">Logged in as <?= e($_SESSION['role']) ?>. Switch to a Guest account to make reservations.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>