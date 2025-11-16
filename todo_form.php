<?php
// Use $t if exists, else defaults
$title = $t['title'] ?? '';
$desc = $t['description'] ?? '';
$status = $t['status'] ?? 'pending';
$priority = $t['priority'] ?? 'low';
$notifications = $t['notifications'] ?? 0;
$due_date = $t['due_date'] ?? '';
$category_id = $t['category_id'] ?? '';
?>
<div class="modal-header glass-header">
    <h5 class="modal-title">Task</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body glass-body">
    <!-- Title -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Title *</label>
        <input type="text" name="title" class="form-control glass-input" value="<?= htmlspecialchars($title) ?>" required>
    </div>

    <!-- Description -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Description</label>
        <textarea name="description" class="form-control glass-input" rows="3"><?= htmlspecialchars($desc) ?></textarea>
    </div>

    <!-- Status & Priority -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Status</label>
            <select name="status" class="form-select glass-input">
                <option value="pending" <?= $status=='pending'?'selected':'' ?>>Pending</option>
                <option value="in_progress" <?= $status=='in_progress'?'selected':'' ?>>In Progress</option>
                <option value="completed" <?= $status=='completed'?'selected':'' ?>>Completed</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Priority</label><br>
            <div class="form-check form-check-inline glass-radio">
                <input type="radio" name="priority" value="low" class="form-check-input" <?= $priority=='low'?'checked':'' ?>>
                <label class="form-check-label ms-1">Low</label>
            </div>
            <div class="form-check form-check-inline glass-radio">
                <input type="radio" name="priority" value="medium" class="form-check-input" <?= $priority=='medium'?'checked':'' ?>>
                <label class="form-check-label ms-1">Medium</label>
            </div>
            <div class="form-check form-check-inline glass-radio">
                <input type="radio" name="priority" value="high" class="form-check-input" <?= $priority=='high'?'checked':'' ?>>
                <label class="form-check-label ms-1">High</label>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    <div class="mb-3 glass-checkbox">
        <div class="form-check">
            <input type="checkbox" name="notifications" class="form-check-input" <?= $notifications?'checked':'' ?>>
            <label class="form-check-label">Email when done</label>
        </div>
    </div>

    <!-- Due Date & Category -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Due Date</label>
            <input type="date" name="due_date" class="form-control glass-input" value="<?= $due_date ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Category</label>
            <select name="category_id" class="form-select glass-input">
                <option value="">None</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $category_id==$c['id']?'selected':'' ?>><?= $c['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Attachment -->
    <div class="mb-3">
        <label class="form-label fw-semibold">Attachment</label>
        <input type="file" name="attachment" class="form-control glass-input" accept=".pdf,.jpg,.jpeg,.png">
    </div>
</div>

<script>
function openTaskModal(task = null) {
    const modalTitle = document.getElementById('taskModalTitle');
    const actionInput = document.getElementById('taskAction');
    const taskIdInput = document.getElementById('taskId');
    const submitBtn = document.getElementById('taskSubmitBtn');

    // Reset form
    const form = document.getElementById('taskForm');
    form.reset();
    document.getElementById('attachmentPreview').style.display = 'none';

    if(task) {
        modalTitle.textContent = 'Edit Task';
        actionInput.value = 'edit';
        taskIdInput.value = task.id;
        document.getElementById('taskTitle').value = task.title;
        document.getElementById('taskDescription').value = task.description;
        document.getElementById('taskStatus').value = task.status;
        document.querySelectorAll('input[name="priority"]').forEach(r => r.checked = r.value === task.priority);
        document.getElementById('taskNotifications').checked = task.notifications == 1;
        document.getElementById('taskDueDate').value = task.due_date;
        document.getElementById('taskCategory').value = task.category_id || '';

        if(task.attachment && /\.(jpe?g|png)$/i.test(task.attachment)) {
            const preview = document.getElementById('attachmentPreview');
            preview.src = task.attachment;
            preview.style.display = 'block';
        }
        submitBtn.textContent = 'Update';
    } else {
        modalTitle.textContent = 'Add Task';
        actionInput.value = 'add';
        taskIdInput.value = '';
        submitBtn.textContent = 'Save';
    }

    const modal = new bootstrap.Modal(document.getElementById('taskModal'));
    modal.show();
}

// Attachment preview
document.getElementById('taskAttachment').addEventListener('change', function(e){
    const file = e.target.files[0];
    if(file && file.type.startsWith('image/')){
        const reader = new FileReader();
        reader.onload = function(ev){
            const img = document.getElementById('attachmentPreview');
            img.src = ev.target.result;
            img.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        document.getElementById('attachmentPreview').style.display = 'none';
    }
});
</script>