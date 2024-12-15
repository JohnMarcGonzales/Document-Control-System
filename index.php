<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Control System Practice Prototype</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: #fff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .logout-btn {
            background-color: #ff4b5c;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            text-decoration: none;
            border-radius: 5px;
        }
        .logout-btn:hover {
            background-color: #ff1f3a;
        }
        #drop-zone {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            margin: 20px auto;
            background-color: #fff;
            width: 50%;
            transition: background-color 0.3s, border-color 0.3s;
        }
        #drop-zone.dragover {
            background-color: #e0f7fa;
            border-color: #007bff;
        }
        #drop-zone select, #drop-zone button {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        #drop-zone button {
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
        }
        #drop-zone button:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        #pagination {
            text-align: center;
            margin: 20px 0;
        }
        #pagination button {
            margin: 0 5px;
            padding: 5px 10px;
            cursor: pointer;
        }
        #pagination button.active {
            background-color: #4CAF50;
            color: #fff;
            border: none;
        }
        #preview-list {
            list-style: none;
            padding: 0;
        }
        #preview-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        #preview-list li button {
            background-color: #ff4b5c;
            color: #fff;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
        }
        #preview-list li button:hover {
            background-color: #ff1f3a;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function adjustStyles() {
                const width = window.innerWidth;
                const dropZone = document.getElementById('drop-zone');
                if (width < 600) {
                    dropZone.style.width = '90%';
                } else if (width < 1024) {
                    dropZone.style.width = '70%';
                } else {
                    dropZone.style.width = '50%';
                }
            }

            function detectDevice() {
                const userAgent = navigator.userAgent.toLowerCase();
                if (userAgent.includes('iphone') || userAgent.includes('android')) {
                    return 'mobile';
                } else if (userAgent.includes('mac') || userAgent.includes('windows')) {
                    return 'desktop';
                } else {
                    return 'unknown';
                }
            }

            function applyDeviceSpecificStyles() {
                const deviceType = detectDevice();
                const body = document.body;
                if (deviceType === 'mobile') {
                    body.style.fontSize = '14px';
                } else if (deviceType === 'desktop') {
                    body.style.fontSize = '16px';
                }
            }

            window.addEventListener('resize', adjustStyles);
            adjustStyles();
            applyDeviceSpecificStyles();

            const fileInput = document.getElementById('file-input');
            const uploadBtn = document.getElementById('upload-btn');
            const fileList = document.getElementById('file-list');
            const tableSelect = document.getElementById('table-select');
            const uploadTableSelect = document.getElementById('upload-table-select'); // New upload table select
            const fileCount = document.getElementById('file-count'); // New file count display
            const previewList = document.getElementById('preview-list'); // New preview list

            let selectedFiles = [];

            // Update file count display and preview list
            fileInput.addEventListener('change', () => {
                const files = Array.from(fileInput.files);
                if (files.length + selectedFiles.length > 5) {
                    alert('You can only select up to 5 files.');
                    return;
                }
                selectedFiles = selectedFiles.concat(files);
                updatePreviewList();
            });

            // Update preview list
            function updatePreviewList() {
                previewList.innerHTML = '';
                selectedFiles.forEach((file, index) => {
                    const listItem = document.createElement('li');
                    listItem.textContent = `${file.name} (${file.size} bytes)`;
                    const cancelBtn = document.createElement('button');
                    cancelBtn.textContent = 'Cancel';
                    cancelBtn.addEventListener('click', () => {
                        selectedFiles.splice(index, 1);
                        updatePreviewList();
                    });
                    listItem.appendChild(cancelBtn);
                    previewList.appendChild(listItem);
                });
                fileCount.textContent = `${selectedFiles.length} file(s) selected`;
            }

            // Handle file uploads
            uploadBtn.addEventListener('click', () => {
                const table = uploadTableSelect.value; // Use new upload table select
                if (selectedFiles.length === 0) {
                    alert('Please select a file!');
                    return;
                }

                const formData = new FormData();
                formData.append('table', table);
                for (const file of selectedFiles) {
                    formData.append('files[]', file);
                }

                fetch('upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Files uploaded successfully!');
                        selectedFiles = [];
                        updatePreviewList();
                        loadFiles(); // Refresh file list
                    } else {
                        alert('File upload failed!');
                    }
                })
                .catch(err => console.error('Error:', err));
            });

            // Load uploaded files with pagination
            function loadFiles(page = 1) {
                const table = tableSelect.value;
                fetch(`upload.php?list=true&page=${page}&table=${table}`)
                    .then(response => response.json())
                    .then(data => {
                        fileList.innerHTML = '';

                        // Populate file table
                        data.files.forEach(file => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${file.file_name}</td>
                                <td>${file.file_type}</td>
                                <td>${file.uploaded_at}</td>
                                <td><a href="${file.file_path}" download>Download</a></td>
                                <td>${file.uploaded_by}</td> <!-- Display who uploaded the file -->
                            `;
                            fileList.appendChild(row);
                        });

                        // Pagination controls
                        const pagination = document.getElementById('pagination');
                        pagination.innerHTML = '';

                        for (let i = 1; i <= data.totalPages; i++) {
                            const button = document.createElement('button');
                            button.textContent = i;
                            button.className = i === data.currentPage ? 'active' : '';
                            button.addEventListener('click', () => loadFiles(i));
                            pagination.appendChild(button);
                        }
                    })
                    .catch(err => console.error('Error:', err));
            }

            // Initial load of files
            loadFiles();

            // Reload files when table selection changes
            tableSelect.addEventListener('change', () => loadFiles());

            // Drag and drop functionality
            const dropZone = document.getElementById('drop-zone');
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });
            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('dragover');
            });
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                const files = Array.from(e.dataTransfer.files);
                if (files.length + selectedFiles.length > 5) {
                    alert('You can only select up to 5 files.');
                    return;
                }
                selectedFiles = selectedFiles.concat(files);
                updatePreviewList();
            });
        });
    </script>
</head>
<body>
    <div class="header">
        <h1>Document Control System Practice Prototype</h1>
        <div>Welcome, <?php echo $_SESSION['username']; ?>!</div> <!-- Display logged-in username -->
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <!-- Drag and Drop Zone -->
    <div id="drop-zone">
        <p>Drag and drop files here, or click to select files.</p>
        <input type="file" id="file-input" multiple>
        <p id="file-count">0 file(s) selected</p> <!-- New file count display -->
        <ul id="preview-list"></ul> <!-- New preview list -->
        <!-- New Table Selection Dropdown for Uploads -->
        <div>
            <label for="upload-table-select">Select Folder for Upload:</label>
            <select id="upload-table-select">
                <option value="land_description">Land Description</option>
                <option value="land_files">Land Files</option>
                <option value="government_files">Government Files</option>
                <option value="documents">Example Documents</option>
            </select>
        </div>
        <button id="upload-btn">Upload</button>
    </div>

    <!-- Table to Display Uploaded Files -->
    <h2>Uploaded Documents</h2>
    <!-- Move Table Selection Dropdown to Top Right of Table Preview -->
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div></div>
        <div>
            <label for="table-select">Select Folder Preview:</label>
            <select id="table-select">
                <option value="land_description">Land Description</option>
                <option value="land_files">Land Files</option>
                <option value="government_files">Government Files</option>
                <option value="documents">Example Documents</option>
            </select>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>File Name</th>
                <th>File Type</th>
                <th>Uploaded At</th>
                <th>Action</th>
                <th>Uploaded By</th> <!-- New column for uploaded by -->
            </tr>
        </thead>
        <tbody id="file-list">
            <!-- File list will be dynamically populated -->
        </tbody>
    </table>

    <!-- Pagination Buttons -->
    <div id="pagination"></div>
</body>
</html>