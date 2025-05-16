<?php
// Set page title
$page_title = "Messages - Applicant";

// Include header
include('includes/header.php');

// Get list of consultants the applicant has bookings with
$stmt = $conn->prepare("SELECT DISTINCT u.id, u.first_name, u.last_name, u.profile_picture
                      FROM users u
                      JOIN bookings b ON u.id = b.consultant_id
                      WHERE b.user_id = ?
                      ORDER BY u.first_name, u.last_name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$consultants_result = $stmt->get_result();
$consultants = [];

while ($row = $consultants_result->fetch_assoc()) {
    $consultants[] = $row;
}
$stmt->close();

// Get selected consultant (default to first one if not specified)
$selected_consultant_id = isset($_GET['consultant']) ? intval($_GET['consultant']) : 
                         (!empty($consultants) ? $consultants[0]['id'] : 0);

// Send message if form submitted
$message_sent = false;
$message_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $message_content = trim($_POST['message_content']);
    $recipient_id = intval($_POST['recipient_id']);
    
    if (empty($message_content)) {
        $message_error = "Please enter a message.";
    } else {
        // Insert message
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, content, sent_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $user_id, $recipient_id, $message_content);
        
        if ($stmt->execute()) {
            $message_sent = true;
        } else {
            $message_error = "Failed to send message. Please try again.";
        }
        $stmt->close();
    }
}

// Mark messages as read
if ($selected_consultant_id > 0) {
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND recipient_id = ? AND is_read = 0");
    $stmt->bind_param("ii", $selected_consultant_id, $user_id);
    $stmt->execute();
    $stmt->close();
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Messages</h1>
        <p class="page-subtitle">Communicate with your visa consultants</p>
    </div>

    <?php if ($message_sent): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Message sent successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($message_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $message_error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (empty($consultants)): ?>
    <div class="alert alert-info">
        <p>You don't have any consultants yet. Book a consultation to start messaging with a consultant.</p>
    </div>
    <?php else: ?>
    <div class="messaging-container">
        <div class="row">
            <!-- Contacts List -->
            <div class="col-md-4 col-lg-3">
                <div class="card contacts-card">
                    <div class="card-header">
                        <h5 class="mb-0">Consultants</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="contacts-list">
                            <?php foreach ($consultants as $consultant): 
                                // Check for unread messages
                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE sender_id = ? AND recipient_id = ? AND is_read = 0");
                                $stmt->bind_param("ii", $consultant['id'], $user_id);
                                $stmt->execute();
                                $unread_result = $stmt->get_result();
                                $unread_count = $unread_result->fetch_assoc()['count'];
                                $stmt->close();
                                
                                // Prepare profile image
                                $profile_img = '../../assets/images/default-profile.jpg';
                                if (!empty($consultant['profile_picture'])) {
                                    if (file_exists('../../uploads/profiles/' . $consultant['profile_picture'])) {
                                        $profile_img = '../../uploads/profiles/' . $consultant['profile_picture'];
                                    }
                                }
                                
                                $active_class = ($consultant['id'] == $selected_consultant_id) ? 'active' : '';
                            ?>
                            <li class="contact-item <?php echo $active_class; ?>">
                                <a href="?consultant=<?php echo $consultant['id']; ?>" class="contact-link">
                                    <img src="<?php echo $profile_img; ?>" alt="Profile" class="contact-img">
                                    <div class="contact-info">
                                        <h6 class="contact-name"><?php echo htmlspecialchars($consultant['first_name'] . ' ' . $consultant['last_name']); ?></h6>
                                        <span class="contact-status">Consultant</span>
                                    </div>
                                    <?php if ($unread_count > 0): ?>
                                    <span class="unread-badge"><?php echo $unread_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Messages Area -->
            <div class="col-md-8 col-lg-9">
                <?php if ($selected_consultant_id > 0): 
                    // Get consultant details
                    $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                    $stmt->bind_param("i", $selected_consultant_id);
                    $stmt->execute();
                    $consultant_result = $stmt->get_result();
                    $consultant_data = $consultant_result->fetch_assoc();
                    $stmt->close();
                    
                    // Get conversation
                    $stmt = $conn->prepare("SELECT m.*, u.first_name, u.last_name, u.profile_picture 
                                          FROM messages m
                                          JOIN users u ON m.sender_id = u.id
                                          WHERE (m.sender_id = ? AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = ?)
                                          ORDER BY m.sent_at ASC");
                    $stmt->bind_param("iiii", $user_id, $selected_consultant_id, $selected_consultant_id, $user_id);
                    $stmt->execute();
                    $messages_result = $stmt->get_result();
                    $messages = [];
                    
                    while ($row = $messages_result->fetch_assoc()) {
                        $messages[] = $row;
                    }
                    $stmt->close();
                ?>
                <div class="card message-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <?php echo htmlspecialchars($consultant_data['first_name'] . ' ' . $consultant_data['last_name']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="messages-container" id="messagesContainer">
                            <?php if (empty($messages)): ?>
                            <div class="no-messages-placeholder">
                                <i class="fas fa-comments"></i>
                                <p>No messages yet. Start the conversation!</p>
                            </div>
                            <?php else: ?>
                                <?php 
                                $last_date = '';
                                foreach ($messages as $message): 
                                    $message_date = date('Y-m-d', strtotime($message['sent_at']));
                                    $show_date = false;
                                    
                                    if ($message_date != $last_date) {
                                        $show_date = true;
                                        $last_date = $message_date;
                                    }
                                    
                                    $is_outgoing = ($message['sender_id'] == $user_id);
                                    $message_class = $is_outgoing ? 'outgoing' : 'incoming';
                                    
                                    // Format time
                                    $message_time = date('h:i A', strtotime($message['sent_at']));
                                ?>
                                    <?php if ($show_date): ?>
                                    <div class="message-date-divider">
                                        <span><?php echo date('F j, Y', strtotime($message['sent_at'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="message <?php echo $message_class; ?>">
                                        <?php if (!$is_outgoing): ?>
                                        <div class="message-sender">
                                            <?php echo htmlspecialchars($message['first_name']); ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="message-content">
                                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                        </div>
                                        <div class="message-time">
                                            <?php echo $message_time; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <form id="messageForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?consultant=' . $selected_consultant_id); ?>">
                            <div class="message-input-container">
                                <input type="hidden" name="recipient_id" value="<?php echo $selected_consultant_id; ?>">
                                <textarea class="form-control" name="message_content" id="messageContent" placeholder="Type your message..." required></textarea>
                                <button type="submit" name="send_message" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="card message-card">
                    <div class="card-body text-center">
                        <p>Select a consultant to start messaging.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to bottom of messages container
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Auto-resize textarea
    const messageContent = document.getElementById('messageContent');
    if (messageContent) {
        messageContent.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
});
</script>

<style>
.messaging-container {
    height: calc(100vh - 200px);
}

.contacts-card, .message-card {
    height: 100%;
}

.contacts-list {
    list-style: none;
    padding: 0;
    margin: 0;
    overflow-y: auto;
    max-height: calc(100vh - 250px);
}

.contact-item {
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    position: relative;
}

.contact-item.active {
    background-color: #f8f9fa;
}

.contact-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: inherit;
}

.contact-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
    object-fit: cover;
}

.contact-name {
    margin: 0;
    font-size: 14px;
}

.contact-status {
    font-size: 12px;
    color: #6c757d;
}

.unread-badge {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background-color: #0d6efd;
    color: white;
    font-size: 12px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.messages-container {
    height: calc(100vh - 350px);
    overflow-y: auto;
    padding: 15px;
}

.message {
    margin-bottom: 15px;
    max-width: 80%;
}

.message.incoming {
    margin-right: auto;
}

.message.outgoing {
    margin-left: auto;
}

.message-sender {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 3px;
}

.message-content {
    padding: 10px 15px;
    border-radius: 18px;
    background-color: #f1f1f1;
    display: inline-block;
    word-break: break-word;
}

.message.outgoing .message-content {
    background-color: #0d6efd;
    color: white;
}

.message-time {
    font-size: 11px;
    color: #6c757d;
    margin-top: 3px;
    text-align: right;
}

.message-date-divider {
    text-align: center;
    margin: 20px 0;
    position: relative;
}

.message-date-divider span {
    background-color: white;
    padding: 0 10px;
    position: relative;
    z-index: 1;
    font-size: 12px;
    color: #6c757d;
}

.message-date-divider:before {
    content: "";
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background-color: #eee;
    z-index: 0;
}

.message-input-container {
    display: flex;
    margin-top: 15px;
    border-top: 1px solid #eee;
    padding-top: 15px;
}

.message-input-container textarea {
    flex-grow: 1;
    resize: none;
    min-height: 50px;
    max-height: 150px;
    margin-right: 10px;
}

.no-messages-placeholder {
    text-align: center;
    padding: 50px 0;
    color: #6c757d;
}

.no-messages-placeholder i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}
</style>

<?php
// Include footer
include('includes/footer.php');
?> 