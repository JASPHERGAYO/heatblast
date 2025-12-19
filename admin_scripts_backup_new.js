// admin_scripts.js - COMPLETE FIXED VERSION

// Global variables
let currentFilters = {
    yearLevel: '',
    course: '',
    section: '',
    gender: '',
    search: ''
};

let currentCaseFilters = {
    category: '',
    status: '',
    violation: '',
    recordedBy: '',
    ch: ''
};

let currentSanctionFilters = {
    category: '',
    status: '',
    violation: '',
    sanctionType: '',
    dueDate: '',
    search: ''
};
// After move_uploaded_file:
// Toggle Sidebar for Mobile (Burger Menu)
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const burgerMenu = document.getElementById('burgerMenu');

    if (sidebar && overlay && burgerMenu) {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        burgerMenu.classList.toggle('active');
    }
}
let currentSort = { column: null, direction: 'asc' };

/// Tab navigation
function showTab(tabName, event) {
    console.log('Switching to tab:', tabName);

    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected tab
    const targetTab = document.getElementById(tabName);
    if (targetTab) {
        targetTab.classList.add('active');
    } else {
        console.error('Tab not found:', tabName);
    }

    // Add active class to clicked button (only if event is provided)
    if (event && event.target) {
        event.target.classList.add('active');
    }

    // Update sidebar navigation active state
    updateSidebarActiveState(tabName);

    // Initialize filters when specific tabs are shown
    if (tabName === 'cases') {
        console.log('Initializing case filters...');
        setTimeout(() => {
            updateCaseFilterOptions();
            initializeCaseFilterListeners();
        }, 100);
    } else if (tabName === 'sanctions') {
        console.log('Initializing sanction filters...');
        setTimeout(() => {
            updateSanctionFilterOptions();
            initializeSanctionFilterListeners();
        }, 100);
    } else if (tabName === 'statistics') {
        loadStatistics();
    }

    // Save active tab to localStorage
    localStorage.setItem('activeTab', tabName);
}
// Update sidebar navigation active state
function updateSidebarActiveState(tabName) {
    // Remove active class from all sidebar items
    document.querySelectorAll('.sidebar-menu a').forEach(item => {
        item.classList.remove('active');
    });

    // Add active class to the corresponding sidebar item
    const sidebarItems = document.querySelectorAll('.sidebar-menu a');
    for (let item of sidebarItems) {
        if (item.textContent.toLowerCase().includes(tabName.toLowerCase()) ||
            (tabName === 'dashboard' && item.textContent === 'Dashboard')) {
            item.classList.add('active');
            break;
        }
    }
}

// Load student profile manually
function loadStudentProfile() {
    const studentId = document.getElementById('manualStudentId').value;
    if (studentId) {
        window.location.href = `?student_id=${studentId}#profile`;
        showTab('profile');
    } else {
        alert('Please enter a student number');
    }
}

// Student Profile Functions
function viewStudentProfile(studentNumber) {
    window.location.href = `?student_id=${studentNumber}#profile`;
}

function recordViolation(studentId) {
    window.location.href = `insert_violation.php?id=${studentId}`;
}

// Assign Sanction to Student (from student management)
function assignSanctionToStudent(studentNumber) {
    document.getElementById('sanctionViolationId').value = '';
    document.getElementById('sanctionStudentNumber').value = studentNumber;
    document.getElementById('sanctionModal').style.display = 'block';
}

// Assign Sanction to Violation (from dashboard/case management)
function assignSanctionToViolation(violationId, studentNumber) {
    document.getElementById('sanctionViolationId').value = violationId;
    document.getElementById('sanctionStudentNumber').value = studentNumber;
    document.getElementById('sanctionModal').style.display = 'block';
}

// Upload Sanction Proof

// Update Sanction Status
// Update Sanction Status
// Update Sanction Status - SIMPLIFIED (calls updated file)
function updateSanctionStatus(sanctionId, status) {
    if (!confirm('Are you sure you want to mark this sanction as ' + status + '?')) {
        return;
    }

    const formData = new FormData();
    formData.append('sanction_id', sanctionId);
    formData.append('status', status);

    fetch('update_sanction_status.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Sanction status updated successfully!');
                location.reload(); // This reloads the page and updates dashboard
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the sanction status.');
        });
}

// Undo Sanction Status - SIMPLIFIED
function undoSanctionStatus(sanctionId) {
    updateSanctionStatus(sanctionId, 'pending');
}

// Update specific sanction row status
function updateSanctionRowStatus(sanctionId, newStatus) {
    const row = document.querySelector(`.sanction-row[data-sanction-id="${sanctionId}"]`);
    if (!row) {
        // Find row by checking each sanction row's first cell (Sanction ID)
        const allRows = document.querySelectorAll('.sanction-row');
        allRows.forEach(r => {
            const firstCell = r.querySelector('td:first-child');
            if (firstCell && firstCell.textContent.includes(`S-${sanctionId.toString().padStart(3, '0')}`)) {
                row = r;
            }
        });
    }

    if (row) {
        // Update status badge
        const statusCell = row.querySelector('td:nth-child(8)');
        if (statusCell) {
            const statusBadge = statusCell.querySelector('.sanction-status');
            if (statusBadge) {
                statusBadge.className = `sanction-status status-${newStatus}`;
                statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            }
        }

        // Update data-status attribute
        row.setAttribute('data-status', newStatus);

        // Update action buttons
        const actionCell = row.querySelector('td:last-child .action-buttons');
        if (actionCell) {
            let actionButtons = '';
            if (newStatus === 'pending') {
                actionButtons = `
                    <button class="btn btn-primary" onclick="updateSanctionStatus(${sanctionId}, 'in-progress')">Mark In Progress</button>
                    <button class="btn btn-success" onclick="uploadSanctionProof(${sanctionId})">Complete</button>
                `;
            } else if (newStatus === 'in-progress') {
                actionButtons = `
                    <button class="btn btn-warning" onclick="undoSanctionStatus(${sanctionId})">Undo to Pending</button>
                    <button class="btn btn-success" onclick="uploadSanctionProof(${sanctionId})">Complete</button>
                `;
            } else if (newStatus === 'completed') {
                actionButtons = `
                    <button class="btn btn-info" onclick="viewSanctionDetails(${sanctionId})">View Details</button>
                    <button class="btn btn-secondary" onclick="editSanctionDetails(${sanctionId})">Edit Details</button>
                `;
            }
            actionCell.innerHTML = actionButtons;
        }
    }
}

// Add dashboard stats update function
function updateDashboardStats() {
    // Update in-progress count
    const inProgressElement = document.getElementById('in-progress-cases');
    if (inProgressElement) {
        const currentCount = parseInt(inProgressElement.textContent);

        // Update border based on count
        const inProgressCard = document.querySelector('.stat-card-in-progress');
        if (inProgressCard) {
            if (currentCount > 0) {
                inProgressCard.style.borderLeft = '5px solid #0dcaf0'; // Blue
            } else {
                inProgressCard.style.borderLeft = '5px solid #6c757d'; // Gray
            }
        }
    }

    // Update completed count border
    const completedElement = document.getElementById('completed-sanctions');
    if (completedElement) {
        const completedCount = parseInt(completedElement.textContent);
        const completedCard = document.querySelector('.stat-card-completed');
        if (completedCard) {
            if (completedCount > 0) {
                completedCard.style.borderLeft = '5px solid #198754'; // Green
            } else {
                completedCard.style.borderLeft = '5px solid #6c757d'; // Gray
            }
        }
    }
}
function uploadSanctionProof(sanctionId) {
    document.getElementById('proofSanctionId').value = sanctionId;
    // Set default completion date to today
    document.getElementById('completionDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('proofModal').style.display = 'block';
}

// View Case Details
function viewCase(caseId) {
    const contentDiv = document.getElementById('caseDetailsContent');
    if (!contentDiv) {
        console.error('Case details content element not found');
        return;
    }

    contentDiv.innerHTML = '<div style="text-align: center; padding: 20px;">Loading case details...</div>';

    const modal = document.getElementById('caseModal');
    if (!modal) {
        console.error('Case modal not found');
        return;
    }

    modal.style.display = 'block';

    fetch('get_case_details.php?case_id=' + caseId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            contentDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading case details:', error);
            contentDiv.innerHTML = '<div style="text-align: center; padding: 20px; color: #e74c3c;">Error loading case details</div>';
        });
}

// View Sanction Details
function viewSanctionDetails(sanctionId) {
    const contentDiv = document.getElementById('sanctionDetailsContent');
    if (!contentDiv) {
        console.error('Sanction details content element not found');
        return;
    }

    contentDiv.innerHTML = '<div style="text-align: center; padding: 20px;">Loading sanction details...</div>';

    const modal = document.getElementById('viewSanctionModal');
    if (!modal) {
        console.error('Sanction modal not found');
        return;
    }

    modal.style.display = 'block';

    fetch('get_sanction_details.php?sanction_id=' + sanctionId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            contentDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading sanction details:', error);
            contentDiv.innerHTML = '<div style="text-align: center; padding: 20px; color: #e74c3c;">Error loading sanction details</div>';
        });
}

// View Violation History
function viewViolationHistory(studentId) {
    const contentDiv = document.getElementById('violationHistoryContent');
    if (!contentDiv) {
        console.error('Violation history content element not found');
        return;
    }

    contentDiv.innerHTML = '<div style="text-align: center; padding: 20px;">Loading violation history...</div>';

    const modal = document.getElementById('violationHistoryModal');
    if (!modal) {
        console.error('Violation history modal not found');
        return;
    }

    modal.style.display = 'block';

    fetch('get_violation_history.php?student_id=' + studentId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            contentDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading violation history:', error);
            contentDiv.innerHTML = '<div style="text-align: center; padding: 20px; color: #e74c3c;">Error loading violation history</div>';
        });
}

// Remove Violation
function removeViolation(violationId) {
    if (confirm('Are you sure you want to remove this violation? This action cannot be undone.')) {
        fetch('remove_violation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `violation_id=${violationId}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Violation removed successfully!');
                    location.reload();
                } else {
                    alert('Error removing violation: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error removing violation: ' + error);
            });
    }
}



// Update category based on violation type selection
function updateEditCategory() {
    const violationType = document.getElementById('editViolationType').value;
    const categoryField = document.getElementById('editViolationCategory');

    const minorViolations = [
        'No ID', 'Improper Attire', 'Improper Uniform', 'Late', 'Mobile Phone Use',
        'Disruptive Behavior', 'Littering', 'Public Display of Affection', 'Vaping',
        'Parking Violation', 'Disrespect', 'Classroom Entry', 'Gambling Materials', 'Other Minor'
    ];

    if (minorViolations.includes(violationType)) {
        categoryField.value = 'minor';
    } else {
        categoryField.value = 'major';
    }
}

// Submit the edited case details
// Submit the edited case details - DEBUG VERSION
function submitCaseEdit(event, caseId) {
    event.preventDefault();
    
    console.log('=== DEBUG: STARTING SUBMIT CASE EDIT ===');
    console.log('Case ID:', caseId);
    
    // Get all form values
    const formData = {
        case_id: caseId,
        violation_type: document.getElementById('editViolationType').value,
        violation_category: document.getElementById('editViolationCategory').value,
        status: document.getElementById('editStatus').value,
        description: document.getElementById('editDescription').value,
        created_at: document.getElementById('editDateRecorded').value,
        sanction_type: document.getElementById('editSanctionType').value,
        sanction_status: document.getElementById('editSanctionStatus').value,
        due_date: document.getElementById('editDueDate').value,
        completion_date: document.getElementById('editCompletedDate').value
    };
    
    // Log all form data
    console.log('=== FORM DATA COLLECTED ===');
    Object.keys(formData).forEach(key => {
        console.log(`${key}:`, formData[key] || '(empty)');
    });
    
    // CLIENT-SIDE VALIDATION: Block pending status without a sanction
    if (formData.status === 'pending' && (!formData.sanction_type || formData.sanction_type === '')) {
        console.log('VALIDATION FAILED: Pending without sanction');
        alert('⚠️ Cannot set case status to PENDING without assigning a sanction!\n\nPlease select a Sanction Type before saving.');
        return;
    }
    
    console.log('=== VALIDATION PASSED ===');
    
    // Create FormData object for submission
    const submitFormData = new FormData();
    Object.keys(formData).forEach(key => {
        submitFormData.append(key, formData[key] || '');
    });
    
    // Log what we're sending to server
    console.log('=== SENDING TO SERVER ===');
    console.log('URL: update_case.php');
    console.log('Method: POST');
    console.log('FormData entries:');
    for (let pair of submitFormData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Show loading
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    // Send to server
    console.log('=== MAKING FETCH REQUEST ===');
    const startTime = Date.now();
    
    fetch('update_case.php', {
        method: 'POST',
        body: submitFormData
    })
    .then(response => {
        console.log('=== SERVER RESPONSE RECEIVED ===');
        console.log('Response time:', Date.now() - startTime + 'ms');
        console.log('Response status:', response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('=== SERVER JSON RESPONSE ===');
        console.log('Response data:', data);
        
        if (data.success) {
            console.log('SUCCESS: Case updated on server');
            alert('✅ Case updated successfully!');
            closeModal('caseModal');
            
            // Verify update by checking the case
            console.log('=== VERIFYING UPDATE ===');
            setTimeout(() => {
                verifyCaseUpdate(caseId);
            }, 500);
            
        } else {
            console.log('ERROR: Server returned failure');
            alert('❌ Error updating case: ' + data.message);
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('=== FETCH ERROR ===');
        console.error('Error details:', error);
        alert('❌ Network error: ' + error.message);
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
    
    console.log('=== DEBUG: SUBMIT CASE EDIT END ===');
}

// Function to verify the update worked
function verifyCaseUpdate(caseId) {
    console.log('=== VERIFYING CASE UPDATE ===');
    console.log('Fetching updated case data for ID:', caseId);
    
    fetch(`get_case_json.php?case_id=${caseId}`)
    .then(response => response.json())
    .then(data => {
        console.log('=== VERIFICATION RESPONSE ===');
        if (data.success) {
            console.log('Current case data after update:', data.data);
            console.log('Status in database:', data.data.status);
            
            // Check if the status matches what we sent
            const currentStatus = document.getElementById('editStatus')?.value;
            if (currentStatus && data.data.status) {
                console.log('Expected status:', currentStatus);
                console.log('Actual status in DB:', data.data.status);
                console.log('Status match:', currentStatus === data.data.status ? '✅ YES' : '❌ NO');
            }
            
            // Ask user if they want to reload
            if (confirm('Update complete! Reload page to see changes?')) {
                location.reload();
            }
        } else {
            console.log('Verification failed:', data.message);
        }
    })
    .catch(error => {
        console.error('Verification error:', error);
    });
}
// Modal management functions
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.onclick = function (event) {
    const modals = document.getElementsByClassName('modal');
    for (let modal of modals) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        const modals = document.getElementsByClassName('modal');
        for (let modal of modals) {
            modal.style.display = 'none';
        }
    }
});

// ========== FILTER AND SORT FUNCTIONS ==========

// Apply Filters for Students
function applyFilters() {
    const yearLevel = document.getElementById('yearLevelFilter').value;
    const course = document.getElementById('courseFilter').value;
    const section = document.getElementById('sectionFilter').value;
    const gender = document.getElementById('genderFilter').value;
    const search = document.getElementById('studentSearch').value.toLowerCase();

    currentFilters = { yearLevel, course, section, gender, search };

    filterStudents();
    updateStudentCount();
}

// Filter Students Function
function filterStudents() {
    const rows = document.querySelectorAll('#studentsTableBody .student-row');
    let visibleCount = 0;

    rows.forEach((row) => {
        const yearLevel = row.getAttribute('data-year-level');
        const course = row.getAttribute('data-course');
        const section = row.getAttribute('data-section');
        const gender = row.getAttribute('data-gender');
        const studentNumber = row.getAttribute('data-student-number').toLowerCase();
        const name = row.getAttribute('data-name').toLowerCase();

        let showRow = true;

        // Apply filters
        if (currentFilters.yearLevel && yearLevel !== currentFilters.yearLevel) {
            showRow = false;
        }

        if (currentFilters.course && course !== currentFilters.course) {
            showRow = false;
        }

        if (currentFilters.section && section !== currentFilters.section) {
            showRow = false;
        }

        if (currentFilters.gender && gender !== currentFilters.gender) {
            showRow = false;
        }

        if (currentFilters.search &&
            !studentNumber.includes(currentFilters.search) &&
            !name.includes(currentFilters.search)) {
            showRow = false;
        }

        if (showRow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    updateSectionOptions();
}

// Update Section Options based on Course Filter
function updateSectionOptions() {
    const courseFilter = document.getElementById('courseFilter').value;
    const sectionFilter = document.getElementById('sectionFilter');
    const allSections = new Set();

    // Get visible sections based on current filters
    const rows = document.querySelectorAll('#studentsTableBody .student-row');

    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const course = row.getAttribute('data-course');
            const section = row.getAttribute('data-section');

            // If course filter is set, only include sections from that course
            if (!courseFilter || course === courseFilter) {
                allSections.add(section);
            }
        }
    });

    // Update section dropdown
    const currentSection = sectionFilter.value;
    sectionFilter.innerHTML = '<option value="">All Sections</option>';

    const sortedSections = Array.from(allSections).sort((a, b) => a - b);
    sortedSections.forEach(section => {
        const option = document.createElement('option');
        option.value = section;
        option.textContent = section;
        sectionFilter.appendChild(option);
    });

    // Restore previous selection if still valid
    if (sortedSections.includes(currentSection)) {
        sectionFilter.value = currentSection;
    }
}

// Search Students
function searchStudents() {
    currentFilters.search = document.getElementById('studentSearch').value.toLowerCase();
    filterStudents();
    updateStudentCount();
}

// Update Student Count
function updateStudentCount() {
    const visibleRows = document.querySelectorAll('#studentsTableBody .student-row[style=""]').length;
    const countElement = document.getElementById('studentCount');
    if (countElement) {
        countElement.textContent = visibleRows;
    }
}

// Clear All Filters for Students
function clearFilters() {
    document.getElementById('yearLevelFilter').value = '';
    document.getElementById('courseFilter').value = '';
    document.getElementById('sectionFilter').value = '';
    document.getElementById('genderFilter').value = '';
    document.getElementById('studentSearch').value = '';

    currentFilters = { yearLevel: '', course: '', section: '', gender: '', search: '' };

    filterStudents();
    updateStudentCount();
}

// ==========================================
// CASE FILTERING FUNCTIONS - FIXED
// ==========================================

// Populate Case Filters Dynamically
function updateCaseFilterOptions() {
    const violationFilter = document.getElementById('caseViolationFilter');
    const recordedByFilter = document.getElementById('caseRecordedByFilter');

    if (!violationFilter || !recordedByFilter) {
        console.log('Case filter elements not found yet');
        return;
    }

    const allViolations = new Set();
    const allRecorders = new Set();

    const rows = document.querySelectorAll('#casesTableBody .case-row');

    rows.forEach(row => {
        const violation = row.getAttribute('data-violation');
        const recordedBy = row.getAttribute('data-recorded-by');

        if (violation) allViolations.add(violation);
        if (recordedBy) allRecorders.add(recordedBy);
    });

    // Populate Violation Dropdown
    const currentViolation = violationFilter.value;
    violationFilter.innerHTML = '<option value="">All Violations</option>';
    Array.from(allViolations).sort().forEach(v => {
        const option = document.createElement('option');
        option.value = v;
        option.textContent = v.charAt(0).toUpperCase() + v.slice(1);
        violationFilter.appendChild(option);
    });
    if (allViolations.has(currentViolation)) violationFilter.value = currentViolation;

    // Populate Recorded By Dropdown
    const currentRecorder = recordedByFilter.value;
    recordedByFilter.innerHTML = '<option value="">All Recorders</option>';
    Array.from(allRecorders).sort().forEach(r => {
        const option = document.createElement('option');
        option.value = r;
        option.textContent = r.charAt(0).toUpperCase() + r.slice(1);
        recordedByFilter.appendChild(option);
    });
    if (allRecorders.has(currentRecorder)) recordedByFilter.value = currentRecorder;
}

function applyCaseFilters() {
    const category = document.getElementById('caseCategoryFilter').value;
    const status = document.getElementById('caseStatusFilter').value;
    const violation = document.getElementById('caseViolationFilter').value;
    const recordedBy = document.getElementById('caseRecordedByFilter').value;
    const search = document.getElementById('caseSearch').value.toLowerCase();

    currentCaseFilters = { category, status, violation, recordedBy, search };

    filterCases();
    updateCaseCount();
}

function filterCases() {
    const rows = document.querySelectorAll('#casesTableBody .case-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const category = row.getAttribute('data-category');
        const status = row.getAttribute('data-status');
        const violation = row.getAttribute('data-violation');
        const recordedBy = row.getAttribute('data-recorded-by');
        const student = row.getAttribute('data-student').toLowerCase();
        const studentNumber = row.getAttribute('data-student-number').toLowerCase();

        let showRow = true;

        // Apply filters
        if (currentCaseFilters.category && category !== currentCaseFilters.category) {
            showRow = false;
        }
        if (currentCaseFilters.status && status !== currentCaseFilters.status) {
            showRow = false;
        }
        if (currentCaseFilters.violation && violation !== currentCaseFilters.violation) {
            showRow = false;
        }
        if (currentCaseFilters.recordedBy && recordedBy !== currentCaseFilters.recordedBy) {
            showRow = false;
        }

        if (currentCaseFilters.search &&
            !student.includes(currentCaseFilters.search) &&
            !studentNumber.includes(currentCaseFilters.search) &&
            !violation.includes(currentCaseFilters.search)) {
            showRow = false;
        }

        if (showRow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    updateCaseCount();
}

function searchCases() {
    currentCaseFilters.search = document.getElementById('caseSearch').value.toLowerCase();
    filterCases();
}

function updateCaseCount() {
    const visibleRows = document.querySelectorAll('#casesTableBody .case-row[style=""]').length;
    const countElement = document.getElementById('caseCount');
    if (countElement) {
        countElement.textContent = visibleRows;
    }
}

function clearCaseFilters() {
    document.getElementById('caseCategoryFilter').value = '';
    document.getElementById('caseStatusFilter').value = '';
    document.getElementById('caseViolationFilter').value = '';
    document.getElementById('caseRecordedByFilter').value = '';
    document.getElementById('caseSearch').value = '';

    currentCaseFilters = { category: '', status: '', violation: '', recordedBy: '', search: '' };

    filterCases();
}

// Initialize case filter event listeners
function initializeCaseFilterListeners() {
    const caseCategoryFilter = document.getElementById('caseCategoryFilter');
    const caseStatusFilter = document.getElementById('caseStatusFilter');
    const caseViolationFilter = document.getElementById('caseViolationFilter');
    const caseRecordedByFilter = document.getElementById('caseRecordedByFilter');

    console.log('Case filter elements found:', {
        category: !!caseCategoryFilter,
        status: !!caseStatusFilter,
        violation: !!caseViolationFilter,
        recordedBy: !!caseRecordedByFilter
    });

    // Remove existing listeners first (to avoid duplicates)
    if (caseCategoryFilter) {
        caseCategoryFilter.onchange = applyCaseFilters;
    }
    if (caseStatusFilter) {
        caseStatusFilter.onchange = applyCaseFilters;
    }
    if (caseViolationFilter) {
        caseViolationFilter.onchange = applyCaseFilters;
    }
    if (caseRecordedByFilter) {
        caseRecordedByFilter.onchange = applyCaseFilters;
    }

    console.log('Case filter listeners initialized');
}

// ==========================================
// SANCTION FILTERING FUNCTIONS
// ==========================================

// Populate Sanction Filters Dynamically
function updateSanctionFilterOptions() {
    const violationFilter = document.getElementById('sanctionViolationFilter');
    const typeFilter = document.getElementById('sanctionTypeFilter');

    const allViolations = new Set();
    const allTypes = new Set();

    const rows = document.querySelectorAll('#sanctionsTableBody .sanction-row');

    rows.forEach(row => {
        const violation = row.getAttribute('data-violation');
        const type = row.getAttribute('data-sanction-type');

        if (violation) allViolations.add(violation);
        if (type) allTypes.add(type);
    });

    // Populate Violation Dropdown
    const currentViolation = violationFilter.value;
    violationFilter.innerHTML = '<option value="">All Violations</option>';
    Array.from(allViolations).sort().forEach(v => {
        const option = document.createElement('option');
        option.value = v;
        option.textContent = v.charAt(0).toUpperCase() + v.slice(1);
        violationFilter.appendChild(option);
    });
    if (allViolations.has(currentViolation)) violationFilter.value = currentViolation;

    // Populate Sanction Type Dropdown
    const currentType = typeFilter.value;
    typeFilter.innerHTML = '<option value="">All Types</option>';
    Array.from(allTypes).sort().forEach(t => {
        const option = document.createElement('option');
        option.value = t;
        option.textContent = t.charAt(0).toUpperCase() + t.slice(1);
        typeFilter.appendChild(option);
    });
    if (allTypes.has(currentType)) typeFilter.value = currentType;
}

function applySanctionFilters() {
    const category = document.getElementById('sanctionCategoryFilter').value;
    const status = document.getElementById('sanctionStatusFilter').value;
    const violation = document.getElementById('sanctionViolationFilter').value;
    const sanctionType = document.getElementById('sanctionTypeFilter').value;
    const dueDate = document.getElementById('sanctionDueDateFilter').value;
    const search = document.getElementById('sanctionSearch').value.toLowerCase();

    currentSanctionFilters = { category, status, violation, sanctionType, dueDate, search };

    filterSanctions();
    updateSanctionCount();
}

function filterSanctions() {
    const rows = document.querySelectorAll('#sanctionsTableBody .sanction-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const category = row.getAttribute('data-category');
        const status = row.getAttribute('data-status');
        const violation = row.getAttribute('data-violation');
        const type = row.getAttribute('data-sanction-type');
        const dueDate = row.getAttribute('data-due-date');
        const student = row.getAttribute('data-student').toLowerCase();
        const studentNumber = row.getAttribute('data-student-number').toLowerCase();

        let showRow = true;

        if (currentSanctionFilters.category && category !== currentSanctionFilters.category) showRow = false;
        if (currentSanctionFilters.status && status !== currentSanctionFilters.status) showRow = false;
        if (currentSanctionFilters.violation && violation !== currentSanctionFilters.violation) showRow = false;
        if (currentSanctionFilters.sanctionType && type !== currentSanctionFilters.sanctionType) showRow = false;
        if (currentSanctionFilters.dueDate && dueDate !== currentSanctionFilters.dueDate) showRow = false;

        if (currentSanctionFilters.search &&
            !student.includes(currentSanctionFilters.search) &&
            !studentNumber.includes(currentSanctionFilters.search) &&
            !type.includes(currentSanctionFilters.search)) {
            showRow = false;
        }

        if (showRow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('sanctionCount').textContent = visibleCount;
}

function searchSanctions() {
    currentSanctionFilters.search = document.getElementById('sanctionSearch').value.toLowerCase();
    filterSanctions();
}

function updateSanctionCount() {
    const visibleRows = document.querySelectorAll('#sanctionsTableBody .sanction-row[style=""]').length;
    const countElement = document.getElementById('sanctionCount');
    if (countElement) {
        countElement.textContent = visibleRows;
    }
}

function clearSanctionFilters() {
    document.getElementById('sanctionCategoryFilter').value = '';
    document.getElementById('sanctionStatusFilter').value = '';
    document.getElementById('sanctionViolationFilter').value = '';
    document.getElementById('sanctionTypeFilter').value = '';
    document.getElementById('sanctionDueDateFilter').value = '';
    document.getElementById('sanctionSearch').value = '';

    currentSanctionFilters = { category: '', status: '', violation: '', sanctionType: '', dueDate: '', search: '' };

    filterSanctions();
}

// Initialize sanction filter event listeners
function initializeSanctionFilterListeners() {
    const sanctionCategoryFilter = document.getElementById('sanctionCategoryFilter');
    const sanctionStatusFilter = document.getElementById('sanctionStatusFilter');
    const sanctionViolationFilter = document.getElementById('sanctionViolationFilter');
    const sanctionTypeFilter = document.getElementById('sanctionTypeFilter');
    const sanctionDueDateFilter = document.getElementById('sanctionDueDateFilter');

    if (sanctionCategoryFilter) {
        sanctionCategoryFilter.onchange = applySanctionFilters;
    }
    if (sanctionStatusFilter) {
        sanctionStatusFilter.onchange = applySanctionFilters;
    }
    if (sanctionViolationFilter) {
        sanctionViolationFilter.onchange = applySanctionFilters;
    }
    if (sanctionTypeFilter) {
        sanctionTypeFilter.onchange = applySanctionFilters;
    }
    if (sanctionDueDateFilter) {
        sanctionDueDateFilter.onchange = applySanctionFilters;
    }
}

// Sort Table Function
function sortTable(columnIndex) {
    const table = document.getElementById('studentsTable');
    const tbody = document.getElementById('studentsTableBody');
    const rows = Array.from(tbody.querySelectorAll('.student-row'));

    // Determine sort direction
    if (currentSort.column === columnIndex) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.column = columnIndex;
        currentSort.direction = 'asc';
    }

    // Sort rows
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();

        // Handle numeric sorting for year level, violations, sanctions
        if ([3, 6, 7].includes(columnIndex)) {
            const aNum = parseInt(aValue) || 0;
            const bNum = parseInt(bValue) || 0;
            return currentSort.direction === 'asc' ? aNum - bNum : bNum - aNum;
        }

        // Text sorting for other columns
        if (currentSort.direction === 'asc') {
            return aValue.localeCompare(bValue);
        } else {
            return bValue.localeCompare(aValue);
        }
    });

    // Reorder rows in table
    rows.forEach(row => tbody.appendChild(row));

    // Update sort indicators
    updateSortIndicators(columnIndex);
}

// Update Sort Indicators
function updateSortIndicators(activeColumn) {
    const headers = document.querySelectorAll('#studentsTable thead th');
    headers.forEach((header, index) => {
        // Find or create sort icon
        let icon = header.querySelector('.sort-icon');
        if (!icon) {
            icon = document.createElement('span');
            icon.className = 'sort-icon';
            header.appendChild(icon);
        }

        if (index === activeColumn) {
            icon.textContent = currentSort.direction === 'asc' ? '↑' : '↓';
            icon.style.color = '#007bff';
        } else {
            icon.textContent = '↕';
            icon.style.color = '#6c757d';
        }
    });
}

// ==========================================
// STATISTICS FUNCTIONS
// ==========================================

let statisticsCharts = {};

// Load Statistics and Create Charts
function loadStatistics() {
    console.log('📊 Loading statistics...');

    fetch('get_statistics.php')
        .then(response => response.json())
        .then(data => {
            console.log('Statistics data received:', data);

            // Create all charts
            createViolationsCategoryChart(data.violations_by_category);
            createViolationsTrendChart(data.violations_by_month);
            createViolationsCourseChart(data.violations_by_course);
            createSanctionsStatusChart(data.sanctions_status);
        })
        .catch(error => {
            console.error('Error loading statistics:', error);
            alert('Failed to load statistics. Please try again.');
        });
}

// Chart 1: Violations by Category (Pie Chart)
function createViolationsCategoryChart(data) {
    const ctx = document.getElementById('violationsCategoryChart');
    if (!ctx) return;

    // Destroy existing chart if it exists
    if (statisticsCharts.category) {
        statisticsCharts.category.destroy();
    }

    const labels = data.map(item => item.violation_category.toUpperCase());
    const values = data.map(item => parseInt(item.count));

    statisticsCharts.category = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',   // Amber for Minor
                    'rgba(220, 53, 69, 0.8)'    // Red for Major
                ],
                borderColor: [
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 13,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1500,
                easing: 'easeInOutQuart'
            }
        }
    });
}

// Chart 2: Violations Trend (Line Chart)
function createViolationsTrendChart(data) {
    const ctx = document.getElementById('violationsTrendChart');
    if (!ctx) return;

    if (statisticsCharts.trend) {
        statisticsCharts.trend.destroy();
    }

    const labels = data.map(item => {
        const date = new Date(item.month + '-01');
        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    });
    const values = data.map(item => parseInt(item.count));

    statisticsCharts.trend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Violations',
                data: values,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            }
        }
    });
}

// Chart 3: Violations by Course (Bar Chart)
function createViolationsCourseChart(data) {
    const ctx = document.getElementById('violationsCourseChart');
    if (!ctx) return;

    if (statisticsCharts.course) {
        statisticsCharts.course.destroy();
    }

    const labels = data.map(item => item.course.toUpperCase());
    const values = data.map(item => parseInt(item.count));

    // Generate gradient colors
    const colors = values.map((_, index) => {
        const hue = (index * 360 / values.length);
        return `hsla(${hue}, 70%, 60%, 0.8)`;
    });

    statisticsCharts.course = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Violations',
                data: values,
                backgroundColor: colors,
                borderColor: colors.map(color => color.replace('0.8', '1')),
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeInOutQuart'
            }
        }
    });
}

// Chart 4: Sanctions Status (Pie Chart)
function createSanctionsStatusChart(data) {
    const ctx = document.getElementById('sanctionsStatusChart');
    if (!ctx) return;

    if (statisticsCharts.sanctions) {
        statisticsCharts.sanctions.destroy();
    }

    const labels = data.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1));
    const values = data.map(item => parseInt(item.count));

    statisticsCharts.sanctions = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',    // Pending - Amber
                    'rgba(54, 162, 235, 0.8)',   // In Progress - Blue
                    'rgba(40, 167, 69, 0.8)'     // Completed - Green
                ],
                borderColor: [
                    'rgba(255, 193, 7, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(40, 167, 69, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 13,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1500,
                easing: 'easeInOutQuart'
            }
        }
    });
}

// Form submission handlers

document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 DOM loaded - initializing filters and forms');

    // Initialize filters when page loads
    updateSectionOptions();
    updateStudentCount();

    // Add event listeners to student filter dropdowns for real-time filtering
    const yearFilter = document.getElementById('yearLevelFilter');
    const courseFilter = document.getElementById('courseFilter');
    const sectionFilter = document.getElementById('sectionFilter');
    const genderFilter = document.getElementById('genderFilter');

    if (yearFilter) yearFilter.addEventListener('change', applyFilters);
    if (courseFilter) courseFilter.addEventListener('change', applyFilters);
    if (sectionFilter) sectionFilter.addEventListener('change', applyFilters);
    if (genderFilter) genderFilter.addEventListener('change', applyFilters);

    console.log('✅ Basic filters initialized successfully');

    const sanctionForm = document.getElementById('sanctionForm');
    if (sanctionForm) {
        sanctionForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Get all values explicitly
            const violationId = document.getElementById('sanctionViolationId').value;
            const studentNumber = document.getElementById('sanctionStudentNumber').value;
            const sanctionType = document.getElementById('sanctionType').value;
            const dueDate = document.getElementById('sanctionDueDate').value;
            const notes = document.getElementById('sanctionNotes').value;

            // Validate required fields on client side first
            if (!studentNumber || !sanctionType || !dueDate) {
                alert('Please fill in all required fields: Student Number, Sanction Type, and Due Date');
                return;
            }

            const formData = new FormData();
            formData.append('sanctionViolationId', violationId);
            formData.append('sanctionStudentNumber', studentNumber);
            formData.append('sanctionType', sanctionType);
            formData.append('sanctionDueDate', dueDate);
            formData.append('sanctionNotes', notes);

            // Append violation proof file if selected
            const violationProofInput = document.getElementById('violationProof');
            if (violationProofInput && violationProofInput.files && violationProofInput.files.length > 0) {
                formData.append('violationProof', violationProofInput.files[0]);
            }

            fetch('assign_sanction.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Sanction assigned successfully!');
                        this.reset();
                        closeModal('sanctionModal');
                        location.reload();
                    } else {
                        alert('Error assigning sanction: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error assigning sanction: ' + error);
                });
        });
    }

    // Form submission for proof upload
    const proofForm = document.getElementById('proofForm');
    if (proofForm) {
        proofForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const sanctionId = document.getElementById('proofSanctionId').value;
            const completionDate = document.getElementById('completionDate').value;
            const counselorNotes = document.getElementById('counselorNotes').value;
            const hoursCompleted = document.getElementById('hoursCompleted').value;
            const proofFile = document.getElementById('completionProof').files[0];

            // Validate only completion date is required now
            if (!completionDate) {
                alert('Please fill in the Completion Date');
                return;
            }

            const formData = new FormData();
            formData.append('proofSanctionId', sanctionId);
            formData.append('completionDate', completionDate);
            formData.append('counselorNotes', counselorNotes);
            formData.append('hoursCompleted', hoursCompleted);

            // Only append file if one was selected
            if (proofFile) {
                formData.append('completionProof', proofFile);
            }

            fetch('update_sanction_proof.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Sanction completed successfully!');
                        this.reset();
                        closeModal('proofModal');
                        location.reload();
                    } else {
                        alert('Error completing sanction: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error completing sanction: ' + error.message);
                });
        });
    }

    // Report functions
    window.generateReport = function (type) {
        alert(`${type} report generated successfully!`);
    }

    window.exportToCSV = function () {
        alert('Data exported to CSV successfully!');
    }

    window.showViolationStats = function () {
        alert('Violation statistics displayed!');
    }

    // On page load, restore active tab from localStorage or URL hash
    const savedTab = localStorage.getItem('activeTab');
    const urlHash = window.location.hash.replace('#', '');

    if (urlHash && document.getElementById(urlHash)) {
        showTab(urlHash);
    } else if (savedTab) {
        showTab(savedTab);
    }

    // If student_id is in URL, automatically show profile tab
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('student_id')) {
        showTab('profile');
    }


    console.log('✅ All initialization complete');
});