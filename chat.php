<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$dbname = 'markets_markethub';
$username = 'markets_root';
$password = 'Sun123flower@';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$current_user_id = $_SESSION['user_id'];

// Fetch all conversations for the seller
$sql = "
    SELECT DISTINCT m.ad_id, a.title, a.user_id as seller_id, 
           u.id as buyer_id, u.firstName as buyer_firstName, u.lastName as buyer_lastName,
           (SELECT message FROM messages WHERE ad_id = m.ad_id AND 
            ((sender_id = u.id AND receiver_id = a.user_id) OR (sender_id = a.user_id AND receiver_id = u.id)) 
            ORDER BY created_at DESC LIMIT 1) as last_message,
           (SELECT created_at FROM messages WHERE ad_id = m.ad_id AND 
            ((sender_id = u.id AND receiver_id = a.user_id) OR (sender_id = a.user_id AND receiver_id = u.id)) 
            ORDER BY created_at DESC LIMIT 1) as last_message_time,
           (SELECT image_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as image_path
    FROM messages m
    JOIN ads a ON m.ad_id = a.id
    JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id) AND u.id != ?
    WHERE a.user_id = ? 
    AND EXISTS (
        SELECT 1 FROM messages WHERE ad_id = m.ad_id AND 
        ((sender_id = u.id AND receiver_id = a.user_id) OR (sender_id = a.user_id AND receiver_id = u.id))
    )
    ORDER BY last_message_time DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$conversations = [];
while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}
$stmt->close();

function timeAgo($timestamp) {
    // Define timezone, adjust if needed
    $timezone = new DateTimeZone('Asia/Colombo'); // or your server timezone

    $datetime = new DateTime($timestamp, $timezone);
    $now = new DateTime('now', $timezone);

    if ($datetime > $now) {
        return 'Just now';
    }

    $interval = $now->diff($datetime);

    if ($interval->y > 0) {
        return $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
    } elseif ($interval->m > 0) {
        return $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
    } elseif ($interval->d > 0) {
        return $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
    } elseif ($interval->h > 0) {
        return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
    } elseif ($interval->i > 0) {
        return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'Just now';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Markets.lk - Your Modern Marketplace</title>
    <link rel="icon" type="image/png" href="images/image.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Chakra Petch', sans-serif;
        }
        .chat-container {
            display: flex;
            height: calc(100vh - 80px);
        }
        .chat-sidebar {
            width: 300px;
            background: #f7f7f7;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
        }
        .chat-list-item {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
        }
        .chat-list-item:hover {
            background: #e5e7eb;
        }
        .chat-list-item.active {
            background: #ff6200;
            color: white;
        }
        .chat-list-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 10px;
        }
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-main-header {
            background: linear-gradient(90deg, #ff6200, #ff8f00);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-main-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #fff;
        }
        .chat-main-footer {
            padding: 15px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            background: #fff;
        }
        .chat-main-footer input {
            flex: 1;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            outline: none;
        }
        .chat-main-footer button {
            margin-left: 10px;
            padding: 10px 15px;
            background: #ff6200;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .chat-main-footer button:hover {
            background: #ff8f00;
        }
        .chat-message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        .chat-message.sent {
            align-items: flex-end; /* Seller messages on the right */
        }
        .chat-message.received {
            align-items: flex-start; /* Buyer messages on the left */
        }
        .chat-message.sent .message-content {
            background: #ff6200;
            color: white;
        }
        .chat-message.received .message-content {
            background: #e5e7eb;
            color: black;
        }
        .chat-message .message-content {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 14px;
        }
        .chat-message.sent .message-time {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
            text-align: right;
        }
        .chat-message.received .message-time {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
            text-align: left;
        }
        .no-messages {
            text-align: center;
            color: #666;
            font-size: 16px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
            }
            .chat-sidebar {
                width: 100%;
                height: 200px;
            }
            .chat-main {
                height: calc(100vh - 280px);
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-gradient-to-r from-orange-400 to-orange-600 text-white">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
             <div class="flex items-center space-x-2">
                <img src="images/image.png" alt="MarketHub Logo" class="h-14 w-19 object-contain">
                <a href="index.php">
  <a href="index.php">
        <img src="images/mk.png" alt="Markets.lk Logo" class="h-14 object-contain">
    </a>
</a>

            </div>

            <div class="flex items-center space-x-4">
               <a href="index.php" class="flex items-center px-4 py-2 text-black bg-gradient-to-r from-green-100 to-green-200 rounded-full hover:from-green-200 hover:to-green-300 transition-all duration-300">
    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
        <path d="M10.707 1.707a1 1 0 00-1.414 0l-8 8A1 1 0 002 11h1v6a1 1 0 001 1h4a1 1 0 001-1v-4h2v4a1 1 0 001 1h4a1 1 0 001-1v-6h1a1 1 0 00.707-1.707l-8-8z" />
    </svg>
    Home
</a>

                
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8">
        <div class="chat-container">
            <!-- Sidebar: Conversation List -->
            <div class="chat-sidebar">
                <?php if (empty($conversations)): ?>
                    <div class="no-messages">No conversations found.</div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <?php 
                            $image = !empty($conv['image_path']) ? $conv['image_path'] : 'images/placeholder.jpg';
                        ?>
                        <div class="chat-list-item" onclick="loadConversation(<?php echo $conv['ad_id']; ?>, <?php echo $conv['buyer_id']; ?>)">
                            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($conv['title']); ?>">
                            <div>
                                <div class="font-semibold"><?php echo htmlspecialchars($conv['buyer_firstName'] . ' ' . $conv['buyer_lastName']); ?></div>
                                <div class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($conv['title']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($conv['last_message'] ?? 'No messages yet'); ?></div>
                                <div class="text-xs text-gray-400"><?php echo isset($conv['last_message_time']) ? timeAgo($conv['last_message_time']) : ''; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Main Chat Area -->
            <div class="chat-main">
                <div class="chat-main-header" id="chatHeader">
                    <span>Select a conversation</span>
                </div>
                <div class="chat-main-body" id="chatBody">
                    <div class="no-messages">Select a conversation to view messages.</div>
                </div>
                <div class="chat-main-footer" id="chatFooter" style="display: none;">
                    <input type="text" id="chatInput" placeholder="Type a message...">
                    <button onclick="sendMessage()">Send</button>
                </div>
            </div>
        </div>
    </main>

    <script>
    let currentAdId = null;
    let currentBuyerId = null;
    let pollingInterval = null;

    function loadConversation(adId, buyerId) {
        console.log(`Loading conversation for ad_id: ${adId}, buyer_id: ${buyerId}`); // Debug: Log inputs
        currentAdId = adId;
        currentBuyerId = buyerId;

        // Highlight selected conversation
        document.querySelectorAll('.chat-list-item').forEach(item => {
            item.classList.remove('active');
            if (item.onclick.toString().includes(`loadConversation(${adId}, ${buyerId})`)) {
                item.classList.add('active');
            }
        });

        // Fetch conversation details
        fetch(`get_c_messages.php?ad_id=${adId}&buyer_id=${buyerId}`)
            .then(response => {
                console.log('Fetch response status:', response.status); // Debug: Check response status
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Fetched data:', data); // Debug: Log fetched data
                const chatHeader = document.getElementById('chatHeader');
                const chatBody = document.getElementById('chatBody');
                const chatFooter = document.getElementById('chatFooter');

                if (data.success && Array.isArray(data.messages)) {
                    const messages = data.messages;

                    // Update header
                    const buyerName = document.querySelector(`.chat-list-item[onclick="loadConversation(${adId}, ${buyerId})"] .font-semibold`)?.textContent || 'Unknown User';
                    const adTitle = document.querySelector(`.chat-list-item[onclick="loadConversation(${adId}, ${buyerId})"] .text-sm`)?.textContent || 'Unknown Ad';
                    chatHeader.innerHTML = `<span>${buyerName} - ${adTitle}</span>`;

                    // Update body
                    chatBody.innerHTML = '';
                    if (messages.length === 0) {
                        chatBody.innerHTML = '<div class="no-messages">No messages in this conversation yet.</div>';
                    } else {
                        messages.forEach(msg => {
                            const isSent = msg.sender_id == <?php echo $current_user_id; ?>;
                            const messageHtml = `
                                <div class="chat-message ${isSent ? 'sent' : 'received'}">
                                    <div class="message-content">${msg.message}</div>
                                    <div class="message-time">${new Date(msg.created_at).toLocaleTimeString()}</div>
                                </div>
                            `;
                            chatBody.innerHTML += messageHtml;
                        });
                    }
                    chatBody.scrollTop = chatBody.scrollHeight;

                    // Show footer
                    chatFooter.style.display = 'flex';

                    // Start polling
                    stopPolling();
                    startPolling();
                } else {
                    chatBody.innerHTML = '<div class="no-messages">Failed to load messages. Please try again.</div>';
                    chatFooter.style.display = 'none';
                    console.error('Error loading messages:', data.error || 'No success response');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error); // Debug: Log fetch errors
                const chatBody = document.getElementById('chatBody');
                chatBody.innerHTML = '<div class="no-messages">Error loading messages: ' + error.message + '</div>';
                document.getElementById('chatFooter').style.display = 'none';
            });
    }

    function sendMessage() {
        const message = document.getElementById('chatInput').value.trim();
        if (!message || !currentAdId || !currentBuyerId) {
            console.warn('Cannot send message: missing message, ad_id, or buyer_id');
            return;
        }

        const data = {
            ad_id: currentAdId,
            sender_id: <?php echo $current_user_id; ?>,
            receiver_id: currentBuyerId,
            message: message
        };

        fetch('send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('chatInput').value = '';
                    loadConversation(currentAdId, currentBuyerId);
                } else {
                    console.error('Error sending message:', data.error);
                    alert('Failed to send message: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Send message error:', error);
                alert('Error sending message: ' + error.message);
            });
    }

    function startPolling() {
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(() => {
            if (currentAdId && currentBuyerId) {
                console.log('Polling for new messages:', currentAdId, currentBuyerId);
                loadConversation(currentAdId, currentBuyerId);
            }
        }, 3000);
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    // Handle Enter key for sending messages
    document.getElementById('chatInput')?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
    
    </script>
</body>
</html>