<?php
require 'config.php';

$typeFilter = $_GET['type'] ?? '';
$verifiedOnly = isset($_GET['verified']) ? 1 : 0;

$sql = "SELECT p.*, u.full_name AS host_name, u.is_verified_safe 
        FROM properties p 
        JOIN users u ON p.host_id = u.user_id 
        WHERE 1=1";
$params = [];

if (in_array($typeFilter, ['Hotel', 'House', 'Lodge'])) {
    $sql .= " AND p.property_type = ?";
    $params[] = $typeFilter;
}
if ($verifiedOnly) {
    $sql .= " AND u.is_verified_safe = 1";
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ekhaya Reservations | Hotels, Lodges & Homes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-stone-50 text-stone-800">

<!-- Navbar -->
<nav class="bg-white border-b border-stone-200 px-6 py-4 flex flex-wrap justify-between items-center sticky top-0 z-10">
    <a href="index.php" class="text-2xl font-bold text-amber-800 tracking-tight">Ekhaya Reservations</a>
    <div class="flex items-center gap-4 text-sm font-medium">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="text-stone-500">Hi, <?= e($_SESSION['full_name']) ?> (<?= ucfirst(e($_SESSION['role'])) ?>)</span>
            <?php if ($_SESSION['role'] === 'guest'): ?>
                <a href="my_bookings.php" class="text-amber-800 hover:underline">My Bookings</a>
            <?php elseif ($_SESSION['role'] === 'host'): ?>
                <a href="host_dashboard.php" class="text-amber-800 hover:underline">Host Portal</a>
            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="text-amber-800 hover:underline">Admin Console</a>
            <?php endif; ?>
            <a href="logout.php" class="bg-stone-200 px-3 py-1.5 rounded-lg hover:bg-stone-300">Logout</a>
        <?php else: ?>
            <a href="auth.php" class="bg-amber-800 text-white px-4 py-2 rounded-lg hover:bg-amber-900">Log In / Sign Up</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Hero & Filter Bar -->
<header class="bg-amber-900 text-white py-12 px-6">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl md:text-4xl font-bold mb-2">Find your next stay at Ekhaya</h1>
        <p class="text-amber-100 mb-6">Book verified hotels, safari lodges, and private homes with transparent check-in details.</p>

        <form method="GET" class="bg-white text-stone-800 p-4 rounded-xl shadow-md flex flex-wrap gap-4 items-center">
            <select name="type" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">All Property Types</option>
                <option value="Hotel" <?= $typeFilter === 'Hotel' ? 'selected' : '' ?>>Hotels</option>
                <option value="Lodge" <?= $typeFilter === 'Lodge' ? 'selected' : '' ?>>Lodges</option>
                <option value="House" <?= $typeFilter === 'House' ? 'selected' : '' ?>>Houses</option>
            </select>

            <label class="flex items-center gap-2 text-sm font-medium cursor-pointer">
                <input type="checkbox" name="verified" value="1" <?= $verifiedOnly ? 'checked' : '' ?> class="accent-emerald-600 w-4 h-4">
                <span>Verified Safe Hosts Only</span>
            </label>

            <button type="submit" class="bg-amber-800 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-amber-900">
                Filter Stays
            </button>
            <?php if ($typeFilter || $verifiedOnly): ?>
                <a href="index.php" class="text-xs text-stone-500 underline">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</header>

<!-- Listings Grid -->
<main class="max-w-6xl mx-auto py-10 px-6">
    <?php if (empty($properties)): ?>
        <div class="bg-white p-10 rounded-xl text-center border border-stone-200">
            <p class="text-lg font-semibold mb-2">No properties listed yet.</p>
            <p class="text-sm text-stone-500">Sign up as a Host to list your hotel, lodge, or house!</p>
        </div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($properties as $prop): ?>
                <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-stone-200 flex flex-col">
                    <div class="relative h-52 bg-stone-200">
                        <img src="<?= e($prop['image_path']) ?>" alt="<?= e($prop['title']) ?>" class="w-full h-full object-cover">
                        <span class="absolute top-3 left-3 bg-stone-900/80 text-white text-xs px-2.5 py-1 rounded-md">
                            <?= e($prop['property_type']) ?>
                        </span>
                        <?php if ($prop['is_verified_safe']): ?>
                            <span class="absolute top-3 right-3 bg-emerald-600 text-white text-xs font-semibold px-2.5 py-1 rounded-md shadow">
                                ✓ Verified Safe Host
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="text-xs text-stone-500 mb-1"><?= e($prop['city']) ?> • Hosted by <?= e($prop['host_name']) ?></div>
                            <h3 class="text-lg font-bold mb-2"><?= e($prop['title']) ?></h3>
                            <p class="text-sm text-stone-600 line-clamp-2 mb-4"><?= e($prop['description']) ?></p>
                        </div>

                        <div class="pt-4 border-t border-stone-100 flex justify-between items-center">
                            <div>
                                <span class="text-lg font-bold text-amber-900">$<?= number_format($prop['price_per_night'], 2) ?></span>
                                <span class="text-xs text-stone-500">/ night</span>
                            </div>
                            <a href="property.php?id=<?= $prop['property_id'] ?>" class="bg-stone-900 hover:bg-stone-800 text-white text-sm px-4 py-2 rounded-lg">
                                View & Book
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>