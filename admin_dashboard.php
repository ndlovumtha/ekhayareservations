<?php
require 'config.php';
requireRole('admin');

// 1. Toggle Host Safety Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_verify_host'])) {
    $target_host_id = (int)$_POST['host_id'];
    $new_status     = (int)$_POST['new_status'];
    $stmt = $pdo->prepare("UPDATE users SET is_verified_safe = ? WHERE user_id = ? AND role = 'host'");
    $stmt->execute([$new_status, $target_host_id]);
}

// 2. Reply to Host Support Ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $reply     = trim($_POST['admin_reply']);
    if ($reply !== '') {
        $stmt = $pdo->prepare("UPDATE support_tickets SET admin_reply = ?, status = 'Answered' WHERE ticket_id = ?");
        $stmt->execute([$reply, $ticket_id]);
    }
}

// Fetch All Hosts
$hosts = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM properties p WHERE p.host_id = u.user_id) AS listing_count 
                      FROM users u WHERE u.role = 'host' ORDER BY u.created_at DESC")->fetchAll();

// Fetch All Support Tickets
$tickets = $pdo->query("SELECT t.*, u.full_name AS host_name, u.email AS host_email 
                        FROM support_tickets t 
                        JOIN users u ON t.host_id = u.user_id 
                        ORDER BY t.status ASC, t.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Console | Ekhaya Reservations</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-stone-100 text-stone-800">

<nav class="bg-stone-900 text-white px-6 py-4 flex justify-between items-center">
    <span class="text-xl font-bold text-amber-400">Ekhaya Reservations • Admin Control Center</span>
    <div class="flex gap-4 text-sm">
        <a href="index.php" class="hover:underline">View Marketplace</a>
        <a href="logout.php" class="text-red-400">Logout</a>
    </div>
</nav>

<div class="max-w-6xl mx-auto py-8 px-6 space-y-10">

    <!-- Section 1: Host Safety Verification -->
    <div class="bg-white p-6 rounded-xl border border-stone-200 shadow-sm">
        <h2 class="text-xl font-bold mb-4">Registered Hosts & Safety Verification</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b bg-stone-50 text-stone-600">
                        <th class="p-3">Host Name</th>
                        <th class="p-3">Contact</th>
                        <th class="p-3">Listings</th>
                        <th class="p-3">Safety Status</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hosts as $h): ?>
                        <tr class="border-b">
                            <td class="p-3 font-semibold"><?= e($h['full_name']) ?></td>
                            <td class="p-3"><?= e($h['email']) ?><br><span class="text-xs text-stone-500"><?= e($h['phone']) ?></span></td>
                            <td class="p-3"><?= $h['listing_count'] ?> properties</td>
                            <td class="p-3">
                                <?php if ($h['is_verified_safe']): ?>
                                    <span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded text-xs font-bold">✓ Verified Safe</span>
                                <?php else: ?>
                                    <span class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded text-xs font-bold">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <form method="POST">
                                    <input type="hidden" name="host_id" value="<?= $h['user_id'] ?>">
                                    <?php if ($h['is_verified_safe']): ?>
                                        <input type="hidden" name="new_status" value="0">
                                        <button type="submit" name="toggle_verify_host" class="bg-stone-200 hover:bg-stone-300 text-stone-800 px-3 py-1.5 rounded text-xs font-medium">
                                            Revoke Verification
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="new_status" value="1">
                                        <button type="submit" name="toggle_verify_host" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded text-xs font-semibold">
                                            Verify Host as Safe
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Host Support Inbox -->
    <div class="bg-white p-6 rounded-xl border border-stone-200 shadow-sm">
        <h2 class="text-xl font-bold mb-4">Host Support Tickets</h2>
        <?php if (empty($tickets)): ?>
            <p class="text-sm text-stone-500">No support requests from hosts yet.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($tickets as $t): ?>
                    <div class="border rounded-lg p-4 bg-stone-50">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="font-bold"><?= e($t['subject']) ?></span>
                                <span class="text-xs text-stone-500 ml-2">from <?= e($t['host_name']) ?> (<?= e($t['host_email']) ?>)</span>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded font-semibold <?= $t['status'] === 'Open' ? 'bg-amber-200 text-amber-900' : 'bg-emerald-200 text-emerald-900' ?>">
                                <?= e($t['status']) ?>
                            </span>
                        </div>
                        <p class="text-sm text-stone-700 mb-3"><?= e($t['message']) ?></p>

                        <form method="POST" class="flex gap-2">
                            <input type="hidden" name="ticket_id" value="<?= $t['ticket_id'] ?>">
                            <input type="text" name="admin_reply" required value="<?= e($t['admin_reply'] ?? '') ?>" placeholder="Write support response to host..." class="flex-1 border rounded-lg px-3 py-2 text-sm bg-white">
                            <button type="submit" name="reply_ticket" class="bg-amber-800 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Send Reply
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>