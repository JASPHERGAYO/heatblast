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
    search: ''
};

let currentSanctionFilters = {
    category: '',
    status: '',
    violation: '',
    sanctionType: '',
    dueDate: '',
    search: ''
};

let currentSort = { column: null, direction: 'asc' };

// Tab navigation
function showTab(tabName) {
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

    // Add active class to clicked button
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
function uploadSanctionProof(sanctionId) {
    document.getElementById('proofSanctionId').value = sanctionId;
    // Set default completion date to today
    const formData = new FormData();
    formData.append('sanction_id', sanctionId);
    formData.append('status', 'pending');

    fetch('update_sanction_status.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Sanction status reverted to pending!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the sanction status.');
        });
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

// Edit Case Details
function editCaseDetails(caseId) {
    const contentDiv = document.getElementById('caseDetailsContent');
    if (!contentDiv) {
        console.error('Case details content element not found');
        return;
    }

    contentDiv.innerHTML = '<div style="text-align: center; padding: 20px;">Loading edit form...</div>';

    const modal = document.getElementById('caseModal');
    if (!modal) {
        console.error('Case modal not found');
        return;
    }

    modal.style.display = 'block';
    loadEditFormDirectly(caseId);
}

// Load edit form directly without showing view details
function loadEditFormDirectly(caseId) {
    const contentDiv = document.getElementById('caseDetailsContent');

    contentDiv.innerHTML = `
        <form id="editCaseForm" onsubmit="submitCaseEdit(event, ${caseId})">
            <div class="case-details-edit">
                <h3>Edit Case Details</h3>
                
                <div class="form-section">
                    <h4>Student Information</h4>
                    <div class="form-group">
                        <label>Student Name</label>
                        <input type="text" class="form-control" id="editStudentName" disabled style="background-color: #f8f9fa;">
                    </div>
                    <div class="form-group">
                        <label>Student Number</label>
                        <input type="text" class="form-control" id="editStudentNumber" disabled style="background-color: #f8f9fa;">
                    </div>
                    <div class="form-group">
                        <label>Course & Section</label>
                        <input type="text" class="form-control" id="editCourseSection" disabled style="background-color: #f8f9fa;">
                    </div>
                </div>

                <div class="form-section">
                    <h4>Violation Details</h4>
                    <div class="form-group">
                        <label>Violation Type *</label>
                        <select class="form-control" id="editViolationType" name="violation_type" required onchange="updateEditCategory()">
                            <optgroup label="Minor Violations">
                                <option value="No ID">No ID</option>
                                <option value="Improper Attire">Improper Attire</option>
                                <option value="Improper Uniform">Improper Uniform</option>
                                <option value="Late">Late</option>
                                <option value="Mobile Phone Use">Mobile Phone Use</option>
                                <option value="Disruptive Behavior">Disruptive Behavior</option>
                                <option value="Littering">Littering</option>
                                <option value="Public Display of Affection">Public Display of Affection</option>
                                <option value="Vaping">Vaping</option>
                                <option value="Parking Violation">Parking Violation</option>
                                <option value="Disrespect">Disrespect</option>
                                <option value="Classroom Entry">Classroom Entry</option>
                                <option value="Gambling Materials">Gambling Materials</option>
                                <option value="Other Minor">Other Minor</option>
                            </optgroup>
                            <optgroup label="Major Violations">
                                <option value="Academic Cheating">Academic Cheating</option>
                                <option value="Plagiarism">Plagiarism</option>
                                <option value="Falsification">Falsification</option>
                                <option value="Physical Assault">Physical Assault</option>
                                <option value="Threats">Threats</option>
                                <option value="Bullying">Bullying</option>
                                <option value="Weapon Possession">Weapon Possession</option>
                                <option value="Drug Violation">Drug Violation</option>
                                <option value="Alcohol Violation">Alcohol Violation</option>
                                <option value="Sexual Harassment">Sexual Harassment</option>
                                <option value="Vandalism">Vandalism</option>
                                <option value="Hazing">Hazing</option>
                                <option value="Unauthorized Organization">Unauthorized Organization</option>
                                <option value="Unauthorized Solicitation">Unauthorized Solicitation</option>
                                <option value="System Tampering">System Tampering</option>
                                <option value="Gambling">Gambling</option>
                                <option value="Lewd Conduct">Lewd Conduct</option>
                                <option value="Disruption of Classes">Disruption of Classes</option>
                                <option value="Smoking">Smoking</option>
                                <option value="Publishing False Information">Publishing False Information</option>
                                <option value="Forging Security Stamps">Forging Security Stamps</option>
                                <option value="ID or Document Misuse">ID or Document Misuse</option>
                                <option value="Accumulation of 4 Minor Offenses">Accumulation of 4 Minor Offenses</option>
                                <option value="Endangering Safety">Endangering Safety</option>
                                <option value="Forcible Entry">Forcible Entry</option>
                                <option value="Unauthorized Use of Rooms">Unauthorized Use of Rooms</option>
                                <option value="Misuse of IT Systems">Misuse of IT Systems</option>
                                <option value="Bribery">Bribery</option>
                                <option value="Stealing">Stealing</option>
                                <option value="Tampering Emergency Devices">Tampering Emergency Devices</option>
                                <option value="Obscene Materials">Obscene Materials</option>
                                <option value="Violent Protest or Coercion">Violent Protest or Coercion</option>
                                <option value="Unauthorized Posting">Unauthorized Posting</option>
                                <option value="Aiding Violations">Aiding Violations</option>
                                <option value="Other Major">Other Major</option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Violation Category *</label>
                        <select class="form-control" id="editViolationCategory" name="violation_category" required>
                            <option value="minor">Minor</option>
                            <option value="major">Major</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status *</label>
                        <select class="form-control" id="editStatus" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="resolved">Resolved</option>
                            <option value="in-progress">In Progress</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Violation Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="4" placeholder="Enter detailed description of the violation..."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Recorded Information</h4>
                    <div class="form-group">
                        <label>Date Recorded</label>
                        <input type="datetime-local" class="form-control" id="editDateRecorded" name="created_at">
                    </div>
                    <div class="form-group">
                        <label>Recorded By</label>
                        <input type="text" class="form-control" id="editRecordedBy" disabled style="background-color: #f8f9fa;">
                    </div>
                </div>

                <div class="form-section">
                    <h4>Sanction Information</h4>
                    <div class="form-group">
                        <label>Sanction Type</label>
                        <select class="form-control" id="editSanctionType" name="sanction_type">
                            <option value="">No Sanction</option>
                            <optgroup label="Minor Offenses">
                                <option value="verbal_reprimand">1st Offense: Verbal Reprimand</option>
                                <option value="written_warning_1">1st Offense: Written Warning</option>
                                <option value="written_warning_2">2nd Offense: Written Warning + 3hrs Community Service</option>
                                <option value="written_warning_3">3rd Offense: Written Warning + 6hrs Community Service + Counseling</option>
                            </optgroup>
                            <optgroup label="Major Offenses">
                                <option value="suspension_6_days">A: Suspension for 6 Days</option>
                                <option value="suspension_10_20_days">B: Suspension for 10-20 Days</option>
                                <option value="non_readmission">C: Non-readmission to the College</option>
                                <option value="dismissal">D: Dismissal from the College</option>
                                <option value="expulsion">E: Expulsion</option>
                                <option value="counseling_mandatory">Mandatory Counseling</option>
                                <option value="community_service_extended">Extended Community Service</option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Sanction Status</label>
                        <select class="form-control" id="editSanctionStatus" name="sanction_status">
                            <option value="pending">Pending</option>
                            <option value="in-progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" class="form-control" id="editDueDate" name="due_date">
                    </div>
                    
                    <div class="form-group">
                        <label>Completed On</label>
                        <input type="date" class="form-control" id="editCompletedDate" name="completion_date">
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top: 20px; text-align: center;">
                    <button type="submit" class="btn btn-primary">Save All Changes</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('caseModal')">Cancel</button>
                </div>
            </div>
        </form>
    `;

    loadCaseDataIntoForm(caseId);
}

// Load current case data into the edit form
function loadCaseDataIntoForm(caseId) {
    fetch('get_case_json.php?case_id=' + caseId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const caseData = data.data;

                // Populate student information (read-only)
                document.getElementById('editStudentName').value = caseData.student_name || '';
                document.getElementById('editStudentNumber').value = caseData.student_number || '';
                document.getElementById('editCourseSection').value = caseData.course_section || '';
                document.getElementById('editRecordedBy').value = caseData.recorded_by_name || '';

                // Populate violation details
                document.getElementById('editViolationType').value = caseData.violation_type || '';
                document.getElementById('editViolationCategory').value = caseData.violation_category || 'minor';
                document.getElementById('editStatus').value = caseData.status || 'pending';
                document.getElementById('editDescription').value = caseData.description || '';

                // Populate date recorded
                if (caseData.created_at) {
                    const date = new Date(caseData.created_at);
                    const localDateTime = date.toISOString().slice(0, 16);
                    document.getElementById('editDateRecorded').value = localDateTime;
                }

                // Populate sanction information
                document.getElementById('editSanctionType').value = caseData.sanction_type || '';
                document.getElementById('editSanctionStatus').value = caseData.sanction_status || 'pending';

                if (caseData.due_date) {
                    document.getElementById('editDueDate').value = caseData.due_date;
                } else {
                    document.getElementById('editDueDate').value = '';
                }

                if (caseData.completion_date) {
                    document.getElementById('editCompletedDate').value = caseData.completion_date;
                } else {
                    document.getElementById('editCompletedDate').value = '';
                }

            } else {
                alert('Error loading case data: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading case data: ' + error);
        });
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
function submitCaseEdit(event, caseId) {
    event.preventDefault();

    // Get form values for validation
    const status = document.getElementById('editStatus').value;
    const sanctionType = document.getElementById('editSanctionType').value;

    // CLIENT-SIDE VALIDATION: Block pending status without a sanction
    if (status === 'pending' && (!sanctionType || sanctionType === '')) {
        alert('⚠️ Cannot set case status to PENDING without assigning a sanction!\n\nPlease select a Sanction Type before saving.');
        return;
    }

    const formData = new FormData();
    formData.append('case_id', caseId);
    formData.append('violation_type', document.getElementById('editViolationType').value);
    formData.append('violation_category', document.getElementById('editViolationCategory').value);
    formData.append('status', document.getElementById('editStatus').value);
    formData.append('description', document.getElementById('editDescription').value);
    formData.append('created_at', document.getElementById('editDateRecorded').value);
    formData.append('sanction_type', document.getElementById('editSanctionType').value);
    formData.append('sanction_status', document.getElementById('editSanctionStatus').value);
    formData.append('due_date', document.getElementById('editDueDate').value);
    formData.append('completion_date', document.getElementById('editCompletedDate').value);

    fetch('update_case.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Case updated successfully!');
                closeModal('caseModal');
                location.reload();
            } else {
                alert('Error updating case: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error updating case: ' + error);
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