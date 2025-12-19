// Handbook Management Functions
function toggleEditHandbook() {
    const editControls = document.getElementById('editHandbookControls');
    const isEditing = editControls.style.display === 'block';
    
    if (isEditing) {
        editControls.style.display = 'none';
        document.getElementById('handbookContent').classList.remove('edit-mode');
    } else {
        editControls.style.display = 'block';
        document.getElementById('handbookContent').classList.add('edit-mode');
    }
}

function filterHandbook(category) {
    const sections = document.querySelectorAll('.handbook-section, .subsection');
    const buttons = document.querySelectorAll('.filter-buttons .btn-outline');
    
    // Update active button
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    sections.forEach(section => {
        if (category === 'all' || section.getAttribute('data-category') === category) {
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
        }
    });
}

function searchHandbook() {
    const searchTerm = document.getElementById('handbookSearch').value.toLowerCase();
    const sections = document.querySelectorAll('.handbook-section, .subsection');
    
    sections.forEach(section => {
        const text = section.textContent.toLowerCase();
        const content = section.querySelector('.section-content');
        
        if (content) {
            // Remove previous highlights
            content.innerHTML = content.innerHTML.replace(/<span class="highlight">|<\/span>/g, '');
            
            if (searchTerm && text.includes(searchTerm)) {
                section.style.display = 'block';
                
                // Highlight search term
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                content.innerHTML = content.innerHTML.replace(regex, '<span class="highlight">$1</span>');
            } else if (searchTerm) {
                section.style.display = 'none';
            } else {
                section.style.display = 'block';
            }
        }
    });
}

function loadSectionForEdit() {
    const sectionId = document.getElementById('sectionSelector').value;
    if (!sectionId) return;
    
    // Remove previous edit highlights
    document.querySelectorAll('.edit-section').forEach(el => {
        el.classList.remove('edit-section');
    });
    
    // Highlight selected section for editing
    const section = document.querySelector(`[data-section="${sectionId}"]`);
    if (section) {
        section.classList.add('edit-section');
        section.scrollIntoView({ behavior: 'smooth' });
    }
}

function saveHandbookChanges() {
    // This would typically save to database
    const sectionId = document.getElementById('sectionSelector').value;
    if (!sectionId) {
        alert('Please select a section to edit.');
        return;
    }
    
    // In a real implementation, you would send an AJAX request to save changes
    alert(`Changes to ${sectionId} have been saved successfully!`);
    cancelHandbookEdit();
}

function cancelHandbookEdit() {
    document.getElementById('editHandbookControls').style.display = 'none';
    document.getElementById('handbookContent').classList.remove('edit-mode');
    document.getElementById('sectionSelector').value = '';
    document.querySelectorAll('.edit-section').forEach(el => {
        el.classList.remove('edit-section');
    });
}

function exportHandbook() {
    // Simulate handbook export
    alert('Handbook exported successfully! This would download a PDF in a real implementation.');
}

function printHandbook() {
    window.print();
}

// Auto-generate violation types from handbook for the violation recording system
function getViolationTypesFromHandbook() {
    const minorOffenses = [
        "Wearing inappropriate attire",
        "Failure to wear College ID",
        "Improper use of facilities",
        "Entering classroom without permission",
        "Disruptive use of mobile phones",
        "Use of mobile phones during exams",
        "Running, loitering, noisy behavior",
        "Misbehavior during programs",
        "Spitting or littering",
        "Public display of affection",
        "Use of electronic cigarettes/vape",
        "Possession of gambling materials",
        "Parking regulation violation",
        "Acts unbecoming of a student"
    ];
    
    const majorOffenses = [
        "Cheating in examinations",
        "Plagiarism",
        "Falsification of documents",
        "Physical assault",
        "Threats and bullying",
        "Sexual harassment",
        "Vandalism",
        "Unauthorized facility use",
        "Hazing",
        "Illegal drugs possession",
        "Gambling on campus",
        "Unauthorized activities"
    ];
    
    return {
        minor: minorOffenses,
        major: majorOffenses
    };
}

// Initialize handbook when tab is shown
function showTab(tabName) {
    // ... existing showTab code ...
    
    if (tabName === 'handbook') {
        // Initialize handbook filters and search
        filterHandbook('all');
    }
}