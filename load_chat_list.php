<?php
include('./include/db_connect.php');

$query = "
SELECT u.username, u.phone, u.email,
       COUNT(s.id) AS total_messages,
       SUM(CASE WHEN s.is_read = 0 AND s.sender='user' THEN 1 ELSE 0 END) AS unread_count
FROM users u
LEFT JOIN support_chat s ON u.username = s.username
GROUP BY u.username, u.phone, u.email
HAVING total_messages > 0
ORDER BY unread_count DESC, total_messages DESC
";

$result = $conn->query($query);
$index = 1;

while($row = $result->fetch_assoc()):
    $msgCount = $row['total_messages'];
    $unread = $row['unread_count'];
?>
<tr>
    <td data-label="#"><?php echo $index++; ?></td>
    <td data-label="Username"><?php echo htmlspecialchars($row['username']); ?></td>
    <td data-label="Phone"><?php echo htmlspecialchars($row['phone']); ?></td>
    <td data-label="Email"><?php echo htmlspecialchars($row['email']); ?></td>

    <td data-label="Messages">
        <?php if($unread > 0): ?>
            <span class="unread"><?php echo $unread; ?> Unread</span>
        <?php else: ?>
            <span class="count-normal"><?php echo $msgCount; ?> msgs</span>
        <?php endif; ?>
    </td>

    <td data-label="View">
        <a class="view-btn" href="admin_reply.php?username=<?php echo urlencode($row['username']); ?>">
            View Chat
        </a>
    </td>
</tr>
<?php endwhile; ?>
