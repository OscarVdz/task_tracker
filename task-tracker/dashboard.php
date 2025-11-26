<?php
include 'config.php';
checkLogin();

if ($_POST) {
    if (isset($_POST['add_task'])) {
        $title = cleanInput($_POST['title']);
        $description = cleanInput($_POST['description']);
        $due_date = $_POST['due_date'];
        $due_time = $_POST['due_time'];
        $status_id = $_POST['status_id'] ?? 1; // default status
        
        // Input validation
        if (empty($title)) {
            $error = "Task title is required";
        } elseif (!validateDate($due_date)) {
            $error = "Please enter a valid due date";
        } elseif (!validateFutureDate($due_date)) {
            $error = "Due date cannot be in the past";
        } elseif (!validateTime($due_time)) {
            $error = "Please enter a valid time (HH:MM format)";
        } else {
            $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, due_date, due_time, status_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $due_date, $due_time, $status_id]);
            $success = "Task added successfully!";
            
            // Refresh the page to show the new task
            header("Location: dashboard.php");
            exit();
        }
    }
    
    if (isset($_POST['delete_task'])) {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE task_id = ? AND user_id = ?");
        $stmt->execute([$_POST['task_id'], $_SESSION['user_id']]);
    }
    
    if (isset($_POST['update_status'])) {
        $stmt = $pdo->prepare("UPDATE tasks SET status_id = ? WHERE task_id = ? AND user_id = ?");
        $stmt->execute([$_POST['status_id'], $_POST['task_id'], $_SESSION['user_id']]);
    }
}

// Get all statuses for dropdown options
    $status_stmt = $pdo->prepare("SELECT * FROM TaskStatus");
    $status_stmt->execute();
    $statuses = $status_stmt->fetchAll();

// Get user's tasks with sttatus namess
    $stmt = $pdo->prepare("SELECT t.*, s.status_name 
                        FROM tasks t 
                        JOIN TaskStatus s ON t.status_id = s.status_id 
                        WHERE t.user_id = ? 
                        ORDER BY t.due_date, t.due_time");
    $stmt->execute([$_SESSION['user_id']]);
    $tasks = $stmt->fetchAll();

// Gets user's first name for welcome message
    $user_stmt = $pdo->prepare("SELECT firstname FROM users WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user = $user_stmt->fetch();

    $calendar_tasks = [];
    foreach ($tasks as $task) {
        $calendar_tasks[] = [
            'title' => $task['title'],
            'start' => $task['due_date'] . ($task['due_time'] ? 'T' . $task['due_time'] : ''),
            'color' => $task['status_id'] == 3 ? '#27ae60' : ($task['status_id'] == 2 ? '#f39c12' : '#e74c3c')
        ];
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Task Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome, <?php echo htmlspecialchars($user['firstname']); ?>!</h1>
            <p>Manage your tasks and schedule</p>
            <div class="nav">
                <a href="index.php">Home</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <div class="view-toggle">
            <button class="view-btn active" onclick="showView('list')">List View</button>
            <button class="view-btn" onclick="showView('calendar')">Calendar View</button>
        </div>

        <div class="task-form">
            <h3>Add New Task</h3>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="text" name="title" placeholder="Task title" required>
                <textarea name="description" placeholder="Description"></textarea>
                <input type="date" name="due_date" required>
                <input type="time" name="due_time">
                <select name="status_id" class="form-input">
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo $status['status_id']; ?>"><?php echo htmlspecialchars($status['status_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="add_task" class="btn">Add Task</button>
            </form>
        </div>

        <div id="listView">
            <h3>Your Tasks (<?php echo count($tasks); ?>)</h3>
            <div class="task-list">
                <?php if (empty($tasks)): ?>
                    <div class="empty-state">
                        <p>No tasks yet. Create your first task above!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-item status-<?php echo $task['status_id']; ?>">
                            <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                            <?php if (!empty($task['description'])): ?>
                                <p><?php echo htmlspecialchars($task['description']); ?></p>
                            <?php endif; ?>
                            <p><strong>Due:</strong> <?php echo $task['due_date'] . ($task['due_time'] ? ' at ' . $task['due_time'] : ''); ?></p>
                            <p><strong>Status:</strong> <span class="status-badge"><?php echo htmlspecialchars($task['status_name']); ?></span></p>
                            <div class="task-actions">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                                    <select name="status_id" onchange="this.form.submit()">
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?php echo $status['status_id']; ?>" <?php echo $status['status_id'] == $task['status_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($status['status_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                                    <button type="submit" name="delete_task" class="btn btn-danger" onclick="return confirm('Delete this task?')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="calendarView" class="hidden">
            <h3>Calendar View</h3>
            <div id="calendar"></div>
        </div>

        <script>
            function showView(view) {
                const listView = document.getElementById('listView');
                const calendarView = document.getElementById('calendarView');
                const buttons = document.querySelectorAll('.view-btn');

                if (view === 'list') {
                    listView.classList.remove('hidden');
                    calendarView.classList.add('hidden');
                    buttons[0].classList.add('active');
                    buttons[1].classList.remove('active');
                } else {
                    listView.classList.add('hidden');
                    calendarView.classList.remove('hidden');
                    buttons[0].classList.remove('active');
                    buttons[1].classList.add('active');
                    calendar.render();
                }
            }

            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: <?php echo json_encode($calendar_tasks); ?>,
                eventClick: function(info) {
                    alert('Task: ' + info.event.title + '\nDue: ' + info.event.start);
                }
            });
            calendar.render();
        </script>
    </div>
</body>
</html>