<?php
require 'config.php';
requireRole('host');

$host_id = $_SESSION['user_id'];
$msg = '';
$err = '';

// Fetch fresh host verification status
$stmt = $pdo->prepare("SELECT is_verified_safe FROM users WHERE user_id = ?");
$stmt->execute([$host_id]);
$is_verified = (int)$stmt->fetchColumn();

// 1. Handle New Property Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_property'])) {
    $title      = trim($_POST['title']);
    $type       = $_POST['property_type'];
    $city       = trim($_POST['city']);
    $address    = trim($_POST['address']);
    $price      = (float)$_POST['price_per_night'];
    $desc       = trim($_POST['description']);
    $checkin    = trim($_POST['checkin_instructions']);
    $checkout   = trim($_POST['checkout_instructions']);

    if (isset($_FILES['property_image']) && $_FILES['property_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $_FILES['property_image']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            $err = "Only JPG, PNG, and WEBP images are allowed.";
        } else {
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }
            $ext = pathinfo($_FILES['property_image']['name'], PATHINFO_EXTENSION);
            $filename = 'uploads/' . uniqid('ekhaya_', true) . '.' . $ext;

            if (move_uploaded_file($_FILES['property_image']['tmp_name'], $filename)) {
                $stmt = $pdo->prepare("INSERT INTO properties (host_id, title, property_type, city, address, price_per_night, description, checkin_instructions, checkout_instructions, image_path) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$host_id, $title, $type, $city, $address, $price, $desc, $checkin, $checkout, $filename]);
                $msg = "Property listed successfully!";
            } else {
                $err = "Failed to upload image. Check folder permissions on /uploads.";
            }
        }
    } else {
        $err = "Please upload a picture of your property.";
    }
}

// 2. Handle Support Ticket Submission to Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_ticket'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    if ($subject && $message) {
        $stmt = $pdo->prepare("INSERT INTO support_tickets (host_id, subject, message) VALUES (?, ?, ?)");
        $stmt->execute([$host_id, $subject, $message]);
        $msg = "Support request sent to Ekhaya Admin.";
    }
}

// Fetch Host's Properties
$stmt = $pdo->prepare("SELECT * FROM properties WHERE host_id = ? ORDER BY created_at DESC");
$stmt->execute([$host_id]);
$my_properties = $stmt->fetchAll();

// Fetch Bookings on Host's Properties
$stmt = $pdo->prepare("SELECT b.*, p.title, u.full_name AS guest_name, u.phone AS guest_phone 
                       FROM bookings b 
                       JOIN properties p ON b.property_id = p.property_id 
                       JOIN users u ON b.guest_id = u.user_id 
                       WHERE p.host_id = ? ORDER BY b.created_at DESC");
$stmt->execute([$host_id]);
$incoming_bookings = $stmt->fetchAll();

// Fetch Host's Support Tickets
$stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE host_id = ? ORDER BY created_at DESC");
$stmt->execute([$host_id]);
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Host Dashboard | Ekhaya Reservations</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-stone-100 text-stone-800">

<nav class="bg-white border-b px-6 py-4 flex justify-between items-center">
    <a href="index.php" class="text-2xl font-bold text-amber-800">Ekhaya Host Portal</a>
    <div class="flex items-center gap-4 text-sm">
        <a href="index.php" class="hover:text-amber-800">View Public Site</a>
        <a href="logout.php" class="text-red-600 font-medium">Logout</a>
    </div>
</nav>

<div class="max-w-6xl mx-auto py-8 px-6 space-y-8">
    <!-- Verification Status Banner -->
    <div class="p-4 rounded-xl border flex justify-between items-center <?= $is_verified ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-amber-50 border-amber-300 text-amber-900' ?>">
        <div>
            <strong>Host Safety Status:</strong>
            <?= $is_verified ? '✓ Verified Safe Host (Your listings display the Verified Safe badge)' : 'Pending Admin Verification (Contact Admin Support below to request safety verification)' ?>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="bg-emerald-100 text-emerald-800 p-4 rounded-lg"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="bg-red-100 text-red-800 p-4 rounded-lg"><?= e($err) ?></div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Add Property Form -->
        <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-stone-200 shadow-sm">
            <h2 class="text-xl font-bold mb-4">List a Hotel, Lodge, or House</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Property Name</label>
                        <input type="text" name="title" required placeholder="e.g. Zambezi Breeze Lodge" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Category</label>
                        <select name="property_type" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="Hotel">Hotel</option>
                            <option value="Lodge">Lodge</option>
                            <option value="House">House</option>
                        </select>
                    </div>
                </div>

                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">City / Area</label>
                        <input type="text" name="city" required placeholder="Harare / Victoria Falls" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Street Address</label>
                        <input type="text" name="address" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Price per Night ($ USD)</label>
                        <input type="number" step="0.01" name="price_per_night" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Upload Property Photo (JPG/PNG/WEBP)</label>
                    <input type="file" name="property_image" accept="image/*" required class="w-full border rounded-lg px-3 py-2 text-sm bg-stone-50">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Property Description</label>
                    <textarea name="description" rows="3" required placeholder="Describe amenities, rooms, Wi-Fi, backup power, etc." class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Check-In Instructions</label>
                        <textarea name="checkin_instructions" rows="3" required placeholder="e.g. Check-in from 2:00 PM. Collect keys at main reception or gatehouse." class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Check-Out Instructions</label>
                        <textarea name="checkout_instructions" rows="3" required placeholder="e.g. Check-out by 10:00 AM. Leave keys on kitchen counter and lock front door." class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                </div>

                <button type="submit" name="add_property" class="bg-amber-800 hover:bg-amber-900 text-white font-semibold px-6 py-2.5 rounded-lg text-sm">
                    Publish Listing
                </button>
            </form>
        </div>

        <!-- Admin Support Desk Check -->
        <div class="bg-white p-6 rounded-xl border border-stone-200 shadow-sm space-y-4">
            <h2 class="text-xl font-bold">Admin Support Desk</h2>
            <p class="text-xs text-stone-500">Need help with listings or want to request Safety Verification? Message the Ekhaya Admin</p>

            <form method="POST" class="space-y-3">
                <input type="text" name="subject" required placeholder="Subject (e.g. Safety Verification Request)" class="w-full border rounded-lg px-3 py-2 text-sm">
                <textarea name="message" rows="3" required placeholder="Write your message to Admin..." class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                <button type="submit" name="send_ticket" class="w-full bg-stone-900 text-white py-2 rounded-lg text-sm font-medium">
                    Send Support Request
                </button>
            </form>

            <div class="border-t pt-4 space-y-3 max-h-72 overflow-y-auto">
                <h3 class="text-xs font-bold uppercase text-stone-400">Your Support History</h3>
                <?php foreach ($tickets as $t): ?>
                    <div class="bg-stone-50 p-3 rounded-lg border text-xs">
                        <div class="font-bold"><?= e($t['subject']) ?> <span class="text-amber-700">(<?= e($t['status']) ?>)</span></div>
                        <p class="text-stone-600 mt-1"><?= e($t['message']) ?></p>
                        <?php if ($t['admin_reply']): ?>
                            <div class="mt-2 pt-2 border-t border-stone-200 text-emerald-800">
                                <strong>Admin Reply:</strong> <?= e($t['admin_reply']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Host's Active Listings & Incoming Bookings -->
    <div class="grid md:grid-cols-2 gap-8">
        <div class="bg-white p-6 rounded-xl border border-stone-200">
            <h2 class="text-lg font-bold mb-4">My Listed Properties (<?= count($my_properties) ?>)</h2>
            <div class="space-y-3">
                <?php foreach ($my_properties as $p): ?>
                    <div class="flex items-center gap-4 border-b pb-3">
                        <img src="<?= e($p['image_path']) ?>" class="w-16 h-16 rounded object-cover">
                        <div>
                            <div class="font-bold text-sm"><?= e($p['title']) ?> (<?= e($p['property_type']) ?>)</div>
                            <div class="text-xs text-stone-500"><?= e($p['city']) ?> • $<?= number_format($p['price_per_night'], 2) ?>/night</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-stone-200">
            <h2 class="text-lg font-bold mb-4">Guest Reservations (<?= count($incoming_bookings) ?>)</h2>
            <div class="space-y-3">
                <?php foreach ($incoming_bookings as $ib): ?>
                    <div class="border-b pb-3 text-sm">
                        <div class="font-bold"><?= e($ib['title']) ?> — <span class="text-emerald-700"><?= e($ib['status']) ?></span></div>
                        <div class="text-xs text-stone-600">Guest: <?= e($ib['guest_name']) ?> (<?= e($ib['guest_phone']) ?>)</div>
                        <div class="text-xs text-stone-500"><?= e($ib['check_in_date']) ?> to <?= e($ib['check_out_date']) ?> • Total: $<?= number_format($ib['total_price'], 2) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>