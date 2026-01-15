<!-- models/RestoreM.php -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="../includes/restore.php" method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="restoreModalLabel">Restore CSV Backup</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="restoreFile" class="form-label">Choose CSV file</label>
            <input required accept=".csv,text/csv" class="form-control" type="file" id="restoreFile" name="file">
            <div class="form-text">CSV header should be: <code>ID, Name, Email, Phone</code>. ID is optional; if present we check duplicates by ID.</div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name="restore" class="btn btn-success">Restore</button>
        </div>
      </form>
    </div>
  </div>
</div>
