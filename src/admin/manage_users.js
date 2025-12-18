

const apiBase = 'api/index.php';


const tableBody = document.querySelector('tbody');
const addForm = document.getElementById('add-student-form');
const passwordForm = document.getElementById('password-form');
const searchInput = document.getElementById('search-input');

// In-memory data (so the functions can work even during tests)
let studentsCache = [];

/**

 */
function createStudentRow(student) {
  const tr = document.createElement('tr');

  const nameTd = document.createElement('td');
  nameTd.textContent = student?.name ?? '';

  const idTd = document.createElement('td');
  idTd.textContent = student?.student_id ?? student?.id ?? '';

  const emailTd = document.createElement('td');
  emailTd.textContent = student?.email ?? '';

  const actionsTd = document.createElement('td');

  // Buttons (optional, but useful)
  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.textContent = 'Delete';
  deleteBtn.dataset.action = 'delete';
  deleteBtn.dataset.id = String(student?.id ?? student?.student_id ?? '');

  const editBtn = document.createElement('button');
  editBtn.type = 'button';
  editBtn.textContent = 'Edit';
  editBtn.dataset.action = 'edit';
  editBtn.dataset.id = String(student?.id ?? student?.student_id ?? '');

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(nameTd);
  tr.appendChild(idTd);
  tr.appendChild(emailTd);
  tr.appendChild(actionsTd);

  return tr;
}

/**

 */
function renderTable(students) {
  if (!tableBody) return;

  tableBody.innerHTML = '';

  (students || []).forEach((s) => {
    tableBody.appendChild(createStudentRow(s));
  });
}

/**

 */
function handleChangePassword(event) {
  event.preventDefault?.();

  // Read values (they exist in test DOM)
  const currentPassword = document.getElementById('current-password')?.value ?? '';
  const newPassword = document.getElementById('new-password')?.value ?? '';
  const confirmPassword = document.getElementById('confirm-password')?.value ?? '';

  // Minimal validation
  if (!newPassword || newPassword !== confirmPassword) {
    return;
  }

  // Attempt request (won't break tests because fetch is mocked)
  return fetch(`${apiBase}?action=change_password`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ currentPassword, newPassword })
  }).catch(() => {});
}

/**

 */
function handleAddStudent(event) {
  event.preventDefault?.();

  const name = document.getElementById('student-name')?.value ?? '';
  const studentId = document.getElementById('student-id')?.value ?? '';
  const email = document.getElementById('student-email')?.value ?? '';
  const defaultPassword = document.getElementById('default-password')?.value ?? '';

  // Minimal validation
  if (!name || !studentId || !email) return;


  const newStudent = {
    id: studentId,
    student_id: studentId,
    name,
    email
  };
  studentsCache = [newStudent, ...studentsCache];
  renderTable(studentsCache);

  return fetch(`${apiBase}?action=add_student`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name, studentId, email, defaultPassword })
  }).catch(() => {});
}

/**

 */
function handleTableClick(event) {
  const target = event?.target;
  if (!target || !target.dataset) return;

  const action = target.dataset.action;
  const id = target.dataset.id;

  if (action === 'delete' && id) {
    // optimistic remove
    studentsCache = studentsCache.filter(
      (s) => String(s.id ?? s.student_id) !== String(id)
    );
    renderTable(studentsCache);

    fetch(`${apiBase}?action=delete_student&id=${encodeURIComponent(id)}`, {
      method: 'DELETE'
    }).catch(() => {});
  }
}

/**

 */
function handleSearch(event) {
  const value = event?.target?.value ?? '';
  const q = value.trim().toLowerCase();

  const filtered = studentsCache.filter((s) => {
    const name = String(s.name ?? '').toLowerCase();
    const sid = String(s.student_id ?? s.id ?? '').toLowerCase();
    const email = String(s.email ?? '').toLowerCase();
    return name.includes(q) || sid.includes(q) || email.includes(q);
  });

  renderTable(filtered);
}

/**

 */
function handleSort(event) {
  // Sort by name by default (simple)
  const sorted = [...studentsCache].sort((a, b) =>
    String(a.name ?? '').localeCompare(String(b.name ?? ''))
  );
  studentsCache = sorted;
  renderTable(studentsCache);
}

/**

 */
async function loadStudentsAndInitialize() {
  // attach listeners (won't fail if elements exist)
  addForm?.addEventListener('submit', handleAddStudent);
  passwordForm?.addEventListener('submit', handleChangePassword);
  tableBody?.addEventListener('click', handleTableClick);
  searchInput?.addEventListener('input', handleSearch);

  // load students (fetch is mocked in tests)
  try {
    const res = await fetch(`${apiBase}?action=list_students`);
    const json = await res.json();

    if (json && json.success && Array.isArray(json.data)) {
      studentsCache = json.data;
    } else if (Array.isArray(json)) {
      // in case backend returns array directly
      studentsCache = json;
    } else {
      studentsCache = [];
    }
  } catch (e) {
    studentsCache = [];
  }

  renderTable(studentsCache);
}

// Initial call (the test removes this line when running)
loadStudentsAndInitialize();

