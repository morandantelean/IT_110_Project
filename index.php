<?php

session_start();
require 'db.php';

$_SESSION['visits'] = ($_SESSION['visits'] ?? 0) + 1;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author = trim($_POST['author'] ?? '');
    $body   = trim($_POST['body'] ?? '');

    if ($author === '' || $body === '') {
        $error = 'Please enter both a name and a message.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO messages (author, body) VALUES (:author, :body)');
        $stmt->execute([':author' => $author, ':body' => $body]);

        if (isset($_POST['remember'])) {
            setcookie('author', $author, time() + 60 * 60 * 24 * 30, '/');
        } else {
            setcookie('author', '', time() - 3600, '/');
        }
        header('Location: index.php');
        exit;
    }
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare('SELECT * FROM messages WHERE body ILIKE :term ORDER BY created_at DESC, id DESC');
    $stmt->execute([':term' => '%' . $search . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC, id DESC');
}
$messages = $stmt->fetchAll();

$savedName = $_COOKIE['author'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Class Wall</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 640px;
            margin: 2rem auto;
            padding: 0 1rem;
            color: #e0f2fe;
            background-color: #030712;
            background-image: 
                radial-gradient(white 1px, transparent 0),
                radial-gradient(rgba(56, 189, 248, 0.5) 1.5px, transparent 0),
                radial-gradient(white 1px, transparent 0);
            background-size: 50px 50px, 80px 80px, 40px 40px;
            background-position: 0 0, 20px 20px, 10px 30px;
        }

        h1 {
            margin-bottom: 0;
            color: #38bdf8;
            text-shadow: 0 0 10px rgba(56, 189, 248, 0.4);
        }

        h2 {
            color: #38bdf8;
            border-bottom: 1px solid #1e3a8a;
            padding-bottom: 0.3rem;
        }

        .visits {
            color: #93c5fd;
            margin-top: 0.25rem;
            font-size: 0.95rem;
        }

        form {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            border: 1px solid #1e40af;
        }

        input[type=text], textarea {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            box-sizing: border-box;
            background: #020617;
            border: 1px solid #1d4ed8;
            color: #f8fafc;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        input[type=text]:focus, textarea:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 8px rgba(56, 189, 248, 0.5);
        }

        button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            cursor: pointer;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.2s, box-shadow 0.2s;
        }

        button:hover {
            background: #1d4ed8;
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.6);
        }

        .msg {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 0.85rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
            border: 1px solid #1e3a8a;
        }

        .msg strong {
            color: #38bdf8;
        }

        .msg small {
            color: #93c5fd;
        }

        .msg p {
            color: #e2e8f0;
            margin-top: 0.5rem;
        }

        .error {
            background: rgba(127, 29, 29, 0.8);
            color: #fca5a5;
            padding: 0.75rem;
            border-radius: 6px;
            border: 1px solid #b91c1c;
        }

        label {
            color: #bfdbfe;
            font-size: 0.9rem;
            cursor: pointer;
        }

        a {
            color: #60a5fa;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Class Wall</h1>
    <p class="visits">You've visited <?= (int) $_SESSION['visits'] ?> time(s) this session.</p>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Step 3: POST form -->
    <form method="POST" action="index.php">
        <input type="text" name="author" placeholder="Your name" maxlength="100" required
               value="<?= htmlspecialchars($savedName) ?>">
        <textarea name="body" rows="3" placeholder="Write a message..." required></textarea>
        <label>
            <input type="checkbox" name="remember" <?= $savedName !== '' ? 'checked' : '' ?>>
            Remember my name
        </label>
        <br><br>
        <button type="submit">Post message</button>
    </form>

    <!-- Step 6: GET search form -->
    <form method="GET" action="index.php">
        <input type="text" name="search" placeholder="Search messages..."
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
        <?php if ($search !== ''): ?>
            <a href="index.php">Clear</a>
        <?php endif; ?>
    </form>

    <h2>Messages</h2>
    <?php if (!$messages): ?>
        <p style="color: #93c5fd;">No messages found.</p>
    <?php endif; ?>

    <?php foreach ($messages as $m): ?>
        <div class="msg">
            <strong><?= htmlspecialchars($m['author']) ?></strong>
            <small>(<?= htmlspecialchars($m['created_at']) ?>)</small>
            <p><?= nl2br(htmlspecialchars($m['body'])) ?></p>
        </div>
    <?php endforeach; ?>
</body>
</html>