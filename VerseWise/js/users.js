
function openViewModal(userId, name, email) {
    const modal = document.getElementById('viewModal');
    const userDetails = document.getElementById('userDetails');
    modal.style.display = 'flex';
    userDetails.textContent = `ID: ${userId}, Name: ${name}, Email: ${email}`;
}

function openEditModal(userId, name, email) {
    const modal = document.getElementById('editModal');
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editUserId').value = userId;
    modal.style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

window.onclick = function(event) {
    const viewModal = document.getElementById('viewModal');
    const editModal = document.getElementById('editModal');
    if (event.target == viewModal) closeModal('viewModal');
    if (event.target == editModal) closeModal('editModal');
}
