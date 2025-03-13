<?php
/**
 * NetShield Pro - User Management
 * Current Date: 2025-03-03 17:36:25
 * Current User: Devinbeater
 */

session_start();
require_once __DIR__ . '/../utils/db-connect.php';
require_once __DIR__ . '/../utils/auth-check.php';

// Ensure admin access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

class UserManager {
    private $db;
    private $currentUser;
    private $currentDate;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->currentUser = 'Devinbeater';
        $this->currentDate = '2025-03-03 17:36:25';
    }

    public function getUsers($search = '', $role = '', $status = '', $page = 1, $perPage = 20) {
        $query = "SELECT 
                    u.id, 
                    u.username, 
                    u.email, 
                    u.role, 
                    u.account_status,
                    u.last_login,
                    u.created_at,
                    u.two_factor_enabled
                 FROM users u 
                 WHERE 1=1";
        $params = [];

        if ($search) {
            $query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($role) {
            $query .= " AND u.role = ?";
            $params[] = $role;
        }

        if ($status) {
            $query .= " AND u.account_status = ?";
            $params[] = $status;
        }

        // Add pagination
        $offset = ($page - 1) * $perPage;
        $query .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserCount($search = '', $role = '', $status = '') {
        $query = "SELECT COUNT(*) FROM users u WHERE 1=1";
        $params = [];

        if ($search) {
            $query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($role) {
            $query .= " AND u.role = ?";
            $params[] = $role;
        }

        if ($status) {
            $query .= " AND u.account_status = ?";
            $params[] = $status;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function updateUserStatus($userId, $status) {
        $stmt = $this->db->prepare('
            UPDATE users 
            SET account_status = ?,
                updated_at = ?,
                updated_by = ?
            WHERE id = ?
        ');
        return $stmt->execute([$status, $this->currentDate, $this->currentUser, $userId]);
    }

    public function updateUserRole($userId, $role) {
        $stmt = $this->db->prepare('
            UPDATE users 
            SET role = ?,
                updated_at = ?,
                updated_by = ?
            WHERE id = ?
        ');
        return $stmt->execute([$role, $this->currentDate, $this->currentUser, $userId]);
    }

    public function resetUserPassword($userId) {
        $tempPassword = bin2hex(random_bytes(8));
        $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);
        
        $stmt = $this->db->prepare('
            UPDATE users 
            SET password_hash = ?,
                password_reset_required = 1,
                updated_at = ?,
                updated_by = ?
            WHERE id = ?
        ');
        
        if ($stmt->execute([$passwordHash, $this->currentDate, $this->currentUser, $userId])) {
            return $tempPassword;
        }
        return false;
    }

    public function getUserLoginHistory($userId, $limit = 10) {
        $stmt = $this->db->prepare('
            SELECT 
                login_timestamp,
                ip_address,
                user_agent,
                login_status
            FROM login_history
            WHERE user_id = ?
            ORDER BY login_timestamp DESC
            LIMIT ?
        ');
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$userManager = new UserManager();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                if (isset($_POST['user_id']) && isset($_POST['status'])) {
                    $success = $userManager->updateUserStatus(
                        $_POST['user_id'],
                        $_POST['status']
                    );
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Status updated successfully' : 'Failed to update status'
                    ];
                }
                break;

            case 'update_role':
                if (isset($_POST['user_id']) && isset($_POST['role'])) {
                    $success = $userManager->updateUserRole(
                        $_POST['user_id'],
                        $_POST['role']
                    );
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Role updated successfully' : 'Failed to update role'
                    ];
                }
                break;

            case 'reset_password':
                if (isset($_POST['user_id'])) {
                    $tempPassword = $userManager->resetUserPassword($_POST['user_id']);
                    $response = [
                        'success' => (bool)$tempPassword,
                        'message' => $tempPassword ? "Password reset successful. Temporary password: $tempPassword" : 'Failed to reset password'
                    ];
                }
                break;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get filters from request
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

$users = $userManager->getUsers($search, $role, $status, $page, $perPage);
$totalUsers = $userManager->getUserCount($search, $role, $status);
$totalPages = ceil($totalUsers / $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - NetShield Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body class="bg-gray-900">
    <?php include '../common/header.php'; ?>

    <div class="flex min-h-screen">
        <?php include '../common/admin-sidebar.php'; ?>

        <main class="flex-1 p-6">
            <div class="container mx-auto">
                <div class="glass-card p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-white">User Management</h1>
                        <a href="/admin/add-user.php" 
                           class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded">
                            Add New User
                        </a>
                    </div>

                    <!-- Filters -->
                    <div class="bg-gray-800 rounded-lg p-4 mb-6">
                        <form id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-gray-400 mb-2">Search</label>
                                <input type="text" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       placeholder="Username or email"
                                       class="w-full bg-gray-700 text-white rounded px-4 py-2">
                            </div>

                            <div>
                                <label class="block text-gray-400 mb-2">Role</label>
                                <select name="role" 
                                        class="w-full bg-gray-700 text-white rounded px-4 py-2">
                                    <option value="">All Roles</option>
                                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-gray-400 mb-2">Status</label>
                                <select name="status" 
                                        class="w-full bg-gray-700 text-white rounded px-4 py-2">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                </select>
                            </div>

                            <div class="flex items-end">
                                <button type="submit" 
                                        class="w-full bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded">
                                    Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Users Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-gray-800 rounded-lg">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        Role
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        2FA
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        Last Login
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-700">
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="text-sm font-medium text-white">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </div>
                                            <div class="text-sm text-gray-400">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <select onchange="updateUserRole(<?php echo $user['id']; ?>, this.value)"
                                                class="bg-gray-700 text-white rounded px-2 py-1">
                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>
                                                User
                                            </option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>
                                                Admin
                                            </option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <select onchange="updateUserStatus(<?php echo $user['id']; ?>, this.value)"
                                                class="bg-gray-700 text-white rounded px-2 py-1">
                                            <option value="active" <?php echo $user['account_status'] === 'active' ? 'selected' : ''; ?>>
                                                Active
                                            </option>
                                            <option value="suspended" <?php echo $user['account_status'] === 'suspended' ? 'selected' : ''; ?>>
                                                Suspended
                                            </option>
                                            <option value="pending" <?php echo $user['account_status'] === 'pending' ? 'selected' : ''; ?>>
                                                Pending
                                            </option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?php echo $user['two_factor_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button onclick="viewUserDetails(<?php echo $user['id']; ?>)"
                                                class="text-blue-400 hover:text-blue-300 mr-3">
                                            View
                                        </button>
                                        <button onclick="resetUserPassword(<?php echo $user['id']; ?>)"
                                                class="text-yellow-400 hover:text-yellow-300 mr-3">
                                            Reset Password
                                        </button>
                                        <button onclick="deleteUser(<?php echo $user['id']; ?>)"
                                                class="text-red-400 hover:text-red-300">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="mt-6 flex justify-between items-center">
                        <div class="text-sm text-gray-400">
                            Showing <?php echo ($page - 1) * $perPage + 1; ?> to 
                            <?php echo min($page * $perPage, $totalUsers); ?> of 
                            <?php echo $totalUsers; ?> users
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
                               class="px-4 py-2 text-sm bg-gray-800 text-white rounded hover:bg-gray-700">
                                Previous
                            </a>
                            <?php endif; ?>

                            <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
                               class="px-4 py-2 text-sm bg-gray-800 text-white rounded hover:bg-gray-700">
                                Next
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- User Details Modal -->
    <div id="userDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-gray-900 rounded-lg shadow-xl max-w-2xl w-full">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-white">User Details</h2>
                        <button onclick="closeUserDetails()" class="text-gray-400 hover:text-white">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div id="userDetailsContent"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // User role update
        async function updateUserRole(userId, role) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update_role',
                        user_id: userId,
                        role: role
                    })
                });

                const data = await response.json();
                if (data.success) {
                    showNotification(data.message, 'success');
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                showNotification(error.message, 'error');
            }
        }

        // User status update
        async function updateUserStatus(userId, status) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update_status',
                        user_id: userId,
                        status: status
                    })
                });

                const data = await response.json();
                if (data.success) {
                    showNotification(data.message, 'success');
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                showNotification(error.message, 'error');
            }
        }

        // Password reset
        async function resetUserPassword(userId) {
            if (!confirm('Are you sure you want to reset this user\'s password?')) {
                return;
            }

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'reset_password',
                        user_id: userId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    showNotification(data.message, 'success');
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                showNotification(error.message, 'error');
            }
        }

        // View user details
        async function viewUserDetails(userId) {
            try {
                const response = await fetch(`/api/users/${userId}`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('userDetailsContent').innerHTML = generateUserDetailsHTML(data.user);
                    document.getElementById('userDetailsModal').classList.remove('hidden');
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                showNotification(error.message, 'error');
            }
        }

        // Close user details modal
        function closeUserDetails() {
            document.getElementById('userDetailsModal').classList.add('hidden');
        }

        // Delete user
        async function deleteUser(userId) {
            if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(`/api/users/${userId}`, {
                    method: 'DELETE'
                });

                const data = await response.json();
                if (data.success) {
                    showNotification('User deleted successfully', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                showNotification(error.message, 'error');
            }
        }

        // Notification helper
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded shadow-lg ${
                type === 'success' ? 'bg-green-600' : 'bg-red-600'
            } text-white`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }

        // Generate user details HTML
        function generateUserDetailsHTML(user) {
            return `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400">Username</label>
                            <div class="text-white">${user.username}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400">Email</label>
                            <div class="text-white">${user.email}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400">Role</label>
                            <div class="text-white">${user.role}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400">Status</label>
                            <div class="text-white">${user.account_status}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400">Created At</label>
                            <div class="text-white">${new Date(user.created_at).toLocaleString()}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400">Last Login</label>
                            <div class="text-white">${user.last_login ? new Date(user.last_login).toLocaleString() : 'Never'}</div>
                        </div>
                    </div>
                </div>
            `;
        }
    </script>
</body>
</html>